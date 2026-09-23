<?php
// Servicio de Autenticación (Tier 2 - Gatekeeper)
// Maneja login, sesión, 2FA, logout, validación

class ServicioAutenticacion {
    private $modelo_usuario;
    private $encriptacion;

    // Hash bcrypt "señuelo" (no corresponde a ninguna contraseña real): se usa para
    // ejecutar un password_verify() de costo equivalente cuando el usuario no existe
    // o está inactivo, así el tiempo de respuesta no revela si un correo está
    // registrado en el sistema (mitiga enumeración de usuarios por temporización).
    private const HASH_SENUELO = '$2y$12$vAZ7oa68yA6qzsEKSE1FsuyR23WHhbfe.xCNWtDfpsFjyHyijjA8S';

    public function __construct() {
        require_once APP_PATH . '/modelos/modelo_usuario.php';
        require_once LIB_PATH . '/encriptacion.php';

        $this->modelo_usuario = new ModeloUsuario();
        $this->encriptacion = new Encriptacion(config('security.encryption_key'));
    }

    /**
     * Verificar si usuario está autenticado
     */
    public function esta_autenticado() {
        return isset($_SESSION['id_usuario']) && isset($_SESSION['id_institucion']);
    }

    /**
     * Obtener ID de usuario actual
     */
    public function obtener_id_usuario() {
        return $_SESSION['id_usuario'] ?? null;
    }

    /**
     * Obtener ID de institución actual
     */
    public function obtener_id_institucion() {
        return $_SESSION['id_institucion'] ?? null;
    }

    /**
     * Obtener email del usuario actual
     */
    public function obtener_email_usuario() {
        return $_SESSION['email'] ?? null;
    }

    /**
     * Obtener nombre completo del usuario actual
     */
    public function obtener_nombre_usuario() {
        return $_SESSION['nombre_completo'] ?? null;
    }

    /**
     * Intentar login con email y contraseña
     * Retorna ['exito' => bool, 'mensaje' => string, 'requiere_2fa' => bool,
     *          'requiere_seleccion_institucion' => bool]
     */
    public function intentar_login($email, $contrasena, $id_institucion = null) {
        $resultado = [
            'exito'                          => false,
            'mensaje'                        => '',
            'requiere_2fa'                   => false,
            'requiere_seleccion_institucion' => false,
            'id_usuario'                     => null,
        ];

        // Validar entrada
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $resultado['mensaje'] = 'Email inválido.';
            return $resultado;
        }

        if (empty($contrasena)) {
            $resultado['mensaje'] = 'Contraseña requerida.';
            return $resultado;
        }

        // Verificar rate limiting ANTES de consultar BD (sección 3.1 seguridad)
        $bloqueo = $this->verificar_rate_limit($email);
        if ($bloqueo['bloqueado']) {
            $minutos = ceil($bloqueo['segundos_restantes'] / 60);
            $resultado['mensaje'] = "Demasiados intentos fallidos. Espere {$minutos} minuto(s) e intente de nuevo.";
            $this->registrar_intento_fallido($email, 'cuenta_bloqueada_rate_limit');
            return $resultado;
        }

        // Buscar usuario
        $usuario = $this->modelo_usuario->obtener_por_email($email, $id_institucion);

        // Usuario inexistente o inactivo: mismo mensaje genérico que "contraseña
        // incorrecta" (evita enumeración de correos registrados), y se ejecuta un
        // password_verify() contra un hash señuelo para igualar el tiempo de
        // respuesta con el caso de contraseña incorrecta.
        if (!$usuario || !$usuario['activo']) {
            password_verify($contrasena, self::HASH_SENUELO);
            $this->incrementar_rate_limit($email);
            $this->registrar_intento_fallido($email, !$usuario ? 'usuario_no_encontrado' : 'usuario_inactivo');
            $resultado['mensaje'] = 'Email o contraseña incorrectos.';
            return $resultado;
        }

        // Validar contraseña con hash bcrypt
        if (!password_verify($contrasena, $usuario['hash_contrasena'])) {
            $this->incrementar_rate_limit($email);
            $this->registrar_intento_fallido($email, 'contrasena_incorrecta');
            $resultado['mensaje'] = 'Email o contraseña incorrectos.';
            return $resultado;
        }

        // Contraseña correcta, ahora validar 2FA si está habilitado
        if ($usuario['requiere_2fa']) {
            // Generar código temporal de 2FA (válido por 5 minutos)
            $codigo_2fa = bin2hex(random_bytes(16));
            $_SESSION['id_usuario_pendiente_2fa'] = $usuario['id_usuario'];
            $_SESSION['id_institucion_pendiente_2fa'] = $usuario['id_institucion'];
            $_SESSION['codigo_2fa_temporal'] = $codigo_2fa;
            $_SESSION['fecha_expiracion_2fa'] = time() + 300; // 5 minutos

            $resultado['exito'] = true;
            $resultado['requiere_2fa'] = true;
            $resultado['id_usuario'] = $usuario['id_usuario'];
            $resultado['mensaje'] = '2FA requerido.';
            return $resultado;
        }

        // Login exitoso (sin 2FA): limpiar rate limit
        $this->limpiar_rate_limit($email);
        $this->crear_sesion($usuario);
        $resultado['exito']      = true;
        $resultado['id_usuario'] = $usuario['id_usuario'];
        $resultado['mensaje']    = 'Login exitoso.';

        if (!empty($_SESSION['pendiente_seleccion_institucion'])) {
            $resultado['requiere_seleccion_institucion'] = true;
        }

        return $resultado;
    }

    /**
     * Validar código TOTP para 2FA
     */
    public function validar_2fa($codigo_totp) {
        $resultado = [
            'exito' => false,
            'mensaje' => '',
        ];

        // Verificar que hay sesión pendiente de 2FA
        if (!isset($_SESSION['id_usuario_pendiente_2fa'])) {
            $resultado['mensaje'] = 'No hay sesión pendiente de 2FA.';
            return $resultado;
        }

        // Validar que el código 2FA no ha expirado
        if (time() > $_SESSION['fecha_expiracion_2fa']) {
            unset($_SESSION['id_usuario_pendiente_2fa']);
            unset($_SESSION['id_institucion_pendiente_2fa']);
            unset($_SESSION['codigo_2fa_temporal']);
            unset($_SESSION['fecha_expiracion_2fa']);
            $resultado['mensaje'] = 'Código 2FA expirado. Intente login nuevamente.';
            return $resultado;
        }

        $id_usuario = $_SESSION['id_usuario_pendiente_2fa'];
        $id_institucion = $_SESSION['id_institucion_pendiente_2fa'];

        // Rate limiting específico de 2FA (independiente del de login): evita que,
        // con una contraseña ya comprometida, se pueda fuerza-brutear el código TOTP.
        $clave_rate_limit_2fa = '2fa:' . $id_usuario;
        $bloqueo = $this->verificar_rate_limit($clave_rate_limit_2fa);
        if ($bloqueo['bloqueado']) {
            $minutos = ceil($bloqueo['segundos_restantes'] / 60);
            $resultado['mensaje'] = "Demasiados intentos fallidos. Espere {$minutos} minuto(s) e intente de nuevo.";
            return $resultado;
        }

        // Obtener usuario
        $usuario = $this->modelo_usuario->obtener_por_id($id_usuario, $id_institucion);
        if (!$usuario) {
            $resultado['mensaje'] = 'Usuario no encontrado.';
            return $resultado;
        }

        // Obtener secreto TOTP
        $secreto_totp = $this->modelo_usuario->obtener_secreto_totp($id_usuario, $id_institucion);
        if (!$secreto_totp) {
            $resultado['mensaje'] = 'No hay secreto 2FA configurado.';
            return $resultado;
        }

        // Validar código TOTP (RFC 6238)
        if (!Encriptacion::validar_totp($secreto_totp, $codigo_totp)) {
            $this->incrementar_rate_limit($clave_rate_limit_2fa);
            $this->registrar_intento_fallido($usuario['correo_electronico'], '2fa_incorrecto');
            $resultado['mensaje'] = 'Código 2FA incorrecto.';
            return $resultado;
        }

        // Código correcto, crear sesión
        $this->limpiar_rate_limit($clave_rate_limit_2fa);
        $this->crear_sesion($usuario);

        // Limpiar datos pendientes de 2FA
        unset($_SESSION['id_usuario_pendiente_2fa']);
        unset($_SESSION['id_institucion_pendiente_2fa']);
        unset($_SESSION['codigo_2fa_temporal']);
        unset($_SESSION['fecha_expiracion_2fa']);

        $resultado['exito']   = true;
        $resultado['mensaje'] = 'Login 2FA exitoso.';

        if (!empty($_SESSION['pendiente_seleccion_institucion'])) {
            $resultado['requiere_seleccion_institucion'] = true;
        }

        return $resultado;
    }

    /**
     * Crear sesión después de autenticación exitosa (RN-01, RNF-05)
     */
    private function crear_sesion($usuario) {
        // Session security (RNF-05)
        $_SESSION['id_usuario']      = $usuario['id_usuario'];
        $_SESSION['id_institucion']  = $usuario['id_institucion']; // institución "propia" (empresa o colegio)
        $_SESSION['correo']          = $usuario['correo_electronico'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['email']           = $usuario['correo_electronico'];

        // Timestamps para session timeout
        $_SESSION['fecha_login']      = time();
        $_SESSION['ultima_actividad'] = time();

        // User agent para validación adicional
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Versión de credenciales: si cambia en BD (por un cambio o
        // restablecimiento de contraseña), esta sesión deja de ser válida.
        $_SESSION['version_credenciales'] = (int)($usuario['version_credenciales'] ?? 1);

        // Regenerar ID de sesión (prevenir session fixation - OWASP)
        session_regenerate_id(true);

        // Cargar rol(es)
        $roles_usuario = $this->modelo_usuario->obtener_roles($usuario['id_usuario'], $usuario['id_institucion']);
        $nombres_roles = array_map(function ($r) { return $r['nombre_rol']; }, $roles_usuario);
        $_SESSION['rol'] = !empty($nombres_roles) ? implode(' · ', $nombres_roles) : 'Usuario';

        // ── Técnico externo: detectar si pertenece a empresa_mantenimiento ──
        $es_tecnico = in_array('tecnico', $nombres_roles);
        if ($es_tecnico && $this->modelo_usuario->es_empresa_mantenimiento($usuario['id_institucion'])) {
            $instituciones = $this->modelo_usuario->obtener_instituciones_tecnico($usuario['id_usuario']);
            if (!empty($instituciones)) {
                $_SESSION['id_institucion_propia'] = $usuario['id_institucion'];

                if (count($instituciones) === 1) {
                    // Auto-seleccionar la única institución vinculada
                    $_SESSION['id_institucion']             = $instituciones[0]['id_institucion'];
                    $_SESSION['nombre_institucion_trabajo'] = $instituciones[0]['nombre'];
                } else {
                    // Necesita elegir manualmente
                    $_SESSION['instituciones_disponibles']       = $instituciones;
                    $_SESSION['pendiente_seleccion_institucion'] = true;
                }
            }
            // Si no hay instituciones vinculadas aún, el técnico opera solo dentro de su empresa
        }

        // Cargar permisos usando el id_institucion de trabajo final
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        $id_inst_trabajo = $_SESSION['id_institucion'];
        $autorizacion = new ServicioAutorizacion($usuario['id_usuario'], $id_inst_trabajo);
        $_SESSION['permisos'] = $autorizacion->obtener_permisos();

        // Actualizar última actividad en BD
        $this->modelo_usuario->actualizar_ultima_actividad($usuario['id_usuario'], $usuario['id_institucion']);

        // Registrar login en auditoría
        $this->registrar_login_exitoso($usuario);

        // Generar CSRF token
        Validacion::generar_csrf_token();
    }

    /**
     * Confirmar la institución de trabajo para técnicos con múltiples vínculos.
     * Retorna true si la selección fue válida, false si no.
     */
    public function seleccionar_institucion($id_institucion) {
        if (empty($_SESSION['pendiente_seleccion_institucion'])) {
            return false;
        }

        $disponibles = $_SESSION['instituciones_disponibles'] ?? [];
        $ids_validos = array_column($disponibles, 'id_institucion');

        if (!in_array($id_institucion, $ids_validos)) {
            return false;
        }

        // Confirmar contexto de trabajo
        $_SESSION['id_institucion'] = $id_institucion;
        $nombre_inst = '';
        foreach ($disponibles as $inst) {
            if ((int)$inst['id_institucion'] === (int)$id_institucion) {
                $nombre_inst = $inst['nombre'];
                break;
            }
        }
        $_SESSION['nombre_institucion_trabajo'] = $nombre_inst;

        // Limpiar datos temporales del selector
        unset($_SESSION['pendiente_seleccion_institucion']);
        unset($_SESSION['instituciones_disponibles']);

        // Recargar permisos para la institución seleccionada
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        $autorizacion = new ServicioAutorizacion($_SESSION['id_usuario'], $id_institucion);
        $_SESSION['permisos'] = $autorizacion->obtener_permisos();

        return true;
    }

    /**
     * Validar que sesión es vigente (RNF-05: session timeout)
     * Retorna true si sesión válida, false si expiró
     */
    public function validar_sesion_vigente() {
        if (!$this->esta_autenticado()) {
            return false;
        }

        $timeout = config('session.timeout'); // 1800 segundos = 30 minutos
        $absolute_timeout = config('session.absolute_timeout'); // 28800 = 8 horas

        $tiempo_actual = time();
        $ultima_actividad = $_SESSION['ultima_actividad'] ?? $tiempo_actual;
        $fecha_login = $_SESSION['fecha_login'] ?? $tiempo_actual;

        // Validar inactivity timeout
        if ($tiempo_actual - $ultima_actividad > $timeout) {
            $this->destruir_sesion('session_inactivity_timeout');
            return false;
        }

        // Validar absolute timeout
        if ($tiempo_actual - $fecha_login > $absolute_timeout) {
            $this->destruir_sesion('session_absolute_timeout');
            return false;
        }

        // Validar user agent (prevenir session hijacking)
        if (($_SESSION['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->destruir_sesion('user_agent_mismatch');
            return false;
        }

        // Revalidar contra la BD en cada petición: una única consulta por clave
        // primaria que trae solo dos columnas. Antes esto se hacía cada 5 minutos,
        // lo que dejaba una ventana en la que una cuenta desactivada —o una
        // contraseña ya cambiada tras un robo de sesión— seguía operando.
        $estado = $this->modelo_usuario->obtener_estado_sesion(
            $_SESSION['id_usuario'],
            $_SESSION['id_institucion']
        );

        if (!$estado || !$estado['activo']) {
            $this->destruir_sesion('usuario_desactivado');
            return false;
        }

        // Cambio o restablecimiento de contraseña: las demás sesiones caen.
        // La sesión que originó el cambio actualiza su propia versión, así que
        // quien cambia su contraseña no se expulsa a sí mismo.
        $version_sesion = (int)($_SESSION['version_credenciales'] ?? 1);
        if ((int)$estado['version_credenciales'] !== $version_sesion) {
            $this->destruir_sesion('credenciales_cambiadas');
            return false;
        }

        // Actualizar último acceso
        $_SESSION['ultima_actividad'] = $tiempo_actual;

        return true;
    }

    /**
     * Sincroniza esta sesión con la versión de credenciales que hay en BD.
     * Se llama justo después de que el propio usuario cambie su contraseña,
     * para cerrar sus otras sesiones sin cerrar la que está usando.
     */
    public function refrescar_version_credenciales() {
        if (!$this->esta_autenticado()) {
            return;
        }

        $estado = $this->modelo_usuario->obtener_estado_sesion(
            $_SESSION['id_usuario'],
            $_SESSION['id_institucion']
        );

        if ($estado) {
            $_SESSION['version_credenciales'] = (int)$estado['version_credenciales'];
        }
    }

    /**
     * Logout: destruir sesión
     */
    public function logout($razon = 'user_logout') {
        $this->destruir_sesion($razon);
    }

    /**
     * Destruir sesión (privado)
     */
    private function destruir_sesion($razon = 'logout') {
        // Registrar logout en auditoría
        if (isset($_SESSION['id_usuario']) && isset($_SESSION['id_institucion'])) {
            $this->registrar_logout($_SESSION['id_usuario'], $_SESSION['id_institucion'], $razon);
        }

        // Destruir sesión
        $_SESSION = [];

        // Expirar también la cookie en el navegador (higiene; use_strict_mode ya
        // evita que un ID de sesión no inicializado se reutilice, pero no está de más).
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }

        session_destroy();
    }

    // ===== RATE LIMITING (sección 3.1 seguridad — bloqueo tras N intentos) =====

    /**
     * Verificar si el email/IP está bloqueado por rate limiting
     * Retorna ['bloqueado' => bool, 'segundos_restantes' => int]
     */
    private function verificar_rate_limit($email) {
        $datos = $this->leer_rate_limit_archivo($email);
        if (!$datos) {
            return ['bloqueado' => false, 'segundos_restantes' => 0];
        }

        $ahora = time();

        if (!empty($datos['bloqueado_hasta']) && $ahora < $datos['bloqueado_hasta']) {
            return [
                'bloqueado' => true,
                'segundos_restantes' => $datos['bloqueado_hasta'] - $ahora,
            ];
        }

        // Ventana expirada: limpiar automáticamente
        if (!empty($datos['primera_vez']) && ($ahora - $datos['primera_vez']) > VENTANA_INTENTOS_LOGIN) {
            $this->limpiar_rate_limit($email);
        }

        return ['bloqueado' => false, 'segundos_restantes' => 0];
    }

    /**
     * Incrementar contador de intentos fallidos
     */
    private function incrementar_rate_limit($email) {
        $datos = $this->leer_rate_limit_archivo($email) ?: [
            'intentos' => 0,
            'primera_vez' => time(),
            'bloqueado_hasta' => null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconocida',
        ];

        $ahora = time();

        // Reiniciar si la ventana expiró
        if (!empty($datos['primera_vez']) && ($ahora - $datos['primera_vez']) > VENTANA_INTENTOS_LOGIN) {
            $datos['intentos'] = 0;
            $datos['primera_vez'] = $ahora;
            $datos['bloqueado_hasta'] = null;
        }

        $datos['intentos']++;

        if ($datos['intentos'] >= MAX_INTENTOS_LOGIN) {
            $datos['bloqueado_hasta'] = $ahora + BLOQUEO_LOGIN_SEGUNDOS;
        }

        $this->escribir_rate_limit_archivo($email, $datos);
    }

    /**
     * Eliminar datos de rate limit para el email (login exitoso)
     */
    private function limpiar_rate_limit($email) {
        $archivo = $this->ruta_rate_limit_archivo($email);
        if (file_exists($archivo)) {
            @unlink($archivo);
        }
    }

    private function ruta_rate_limit_archivo($email) {
        if (!is_dir(RATE_LIMIT_DIR)) {
            @mkdir(RATE_LIMIT_DIR, 0750, true);
        }
        return RATE_LIMIT_DIR . '/' . md5(strtolower(trim($email))) . '.json';
    }

    private function leer_rate_limit_archivo($email) {
        $archivo = $this->ruta_rate_limit_archivo($email);
        if (!file_exists($archivo)) {
            return null;
        }
        $contenido = @file_get_contents($archivo);
        return $contenido ? json_decode($contenido, true) : null;
    }

    private function escribir_rate_limit_archivo($email, $datos) {
        $archivo = $this->ruta_rate_limit_archivo($email);
        @file_put_contents($archivo, json_encode($datos), LOCK_EX);
    }

    // ===== AUDITORÍA =====

    /**
     * Registrar intento de login fallido (para auditoría y detección de ataques)
     */
    private function registrar_intento_fallido($email, $razon) {
        $log_msg = sprintf(
            "[%s] Login fallido - Email: %s, Razón: %s, IP: %s\n",
            date('Y-m-d H:i:s'),
            $email,
            $razon,
            $_SERVER['REMOTE_ADDR'] ?? 'desconocida'
        );

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents(AUDIT_LOG, $log_msg, FILE_APPEND);

        if (class_exists('ServicioAuditoria')) {
            ServicioAuditoria::registrar('login_fallido', 'sesion', null, null,
                ['email' => $email, 'razon' => $razon], ['id_usuario' => null, 'id_institucion' => null]);
        }
    }

    /**
     * Registrar login exitoso
     */
    private function registrar_login_exitoso($usuario) {
        $log_msg = sprintf(
            "[%s] Login exitoso - Usuario ID: %d (%s), Institución: %d, IP: %s\n",
            date('Y-m-d H:i:s'),
            $usuario['id_usuario'],
            $usuario['correo_electronico'],
            $usuario['id_institucion'],
            $_SERVER['REMOTE_ADDR'] ?? 'desconocida'
        );

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents(AUDIT_LOG, $log_msg, FILE_APPEND);

        if (class_exists('ServicioAuditoria')) {
            ServicioAuditoria::registrar('login', 'sesion', (int)$usuario['id_usuario'], null,
                ['email' => $usuario['correo_electronico']],
                ['id_usuario' => (int)$usuario['id_usuario'], 'id_institucion' => (int)$usuario['id_institucion']]);
        }
    }

    /**
     * Registrar logout
     */
    private function registrar_logout($id_usuario, $id_institucion, $razon) {
        $log_msg = sprintf(
            "[%s] Logout - Usuario ID: %d, Institución: %d, Razón: %s, IP: %s\n",
            date('Y-m-d H:i:s'),
            $id_usuario,
            $id_institucion,
            $razon,
            $_SERVER['REMOTE_ADDR'] ?? 'desconocida'
        );

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents(AUDIT_LOG, $log_msg, FILE_APPEND);

        if (class_exists('ServicioAuditoria')) {
            ServicioAuditoria::registrar('logout', 'sesion', (int)$id_usuario, null, ['razon' => $razon],
                ['id_usuario' => (int)$id_usuario, 'id_institucion' => (int)$id_institucion]);
        }
    }

    /**
     * Requerir autenticación (redirigir a login si no está autenticado).
     * También redirige al selector de institución si el técnico no ha elegido contexto aún.
     */
    public function requerir_autenticacion() {
        if (!$this->validar_sesion_vigente()) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login');
            exit;
        }
        if (!empty($_SESSION['pendiente_seleccion_institucion'])) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=seleccionar_institucion');
            exit;
        }
    }
}
