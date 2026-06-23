<?php
// Servicio de Autenticación (Tier 2 - Gatekeeper)
// Maneja login, sesión, 2FA, logout, validación

class ServicioAutenticacion {
    private $modelo_usuario;
    private $encriptacion;

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
     * Retorna ['éxito' => bool, 'mensaje' => string, 'requiere_2fa' => bool]
     */
    public function intentar_login($email, $contrasena, $id_institucion = null) {
        $resultado = [
            'exito' => false,
            'mensaje' => '',
            'requiere_2fa' => false,
            'id_usuario' => null,
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

        // Buscar usuario
        $usuario = $this->modelo_usuario->obtener_por_email($email, $id_institucion);
        if (!$usuario) {
            // Log de intento fallido (sin revelar si existe)
            $this->registrar_intento_fallido($email, 'usuario_no_encontrado');
            $resultado['mensaje'] = 'Email o contraseña incorrectos.';
            return $resultado;
        }

        // Validar que usuario está activo
        if (!$usuario['activo']) {
            $this->registrar_intento_fallido($email, 'usuario_inactivo');
            $resultado['mensaje'] = 'Usuario inactivo. Contacte al administrador.';
            return $resultado;
        }

        // Validar contraseña con hash bcrypt
        if (!password_verify($contrasena, $usuario['hash_contrasena'])) {
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

        // Login exitoso (sin 2FA)
        $this->crear_sesion($usuario);
        $resultado['exito'] = true;
        $resultado['id_usuario'] = $usuario['id_usuario'];
        $resultado['mensaje'] = 'Login exitoso.';

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
            $this->registrar_intento_fallido($usuario['correo_electronico'], '2fa_incorrecto');
            $resultado['mensaje'] = 'Código 2FA incorrecto.';
            return $resultado;
        }

        // Código correcto, crear sesión
        $this->crear_sesion($usuario);

        // Limpiar datos pendientes de 2FA
        unset($_SESSION['id_usuario_pendiente_2fa']);
        unset($_SESSION['id_institucion_pendiente_2fa']);
        unset($_SESSION['codigo_2fa_temporal']);
        unset($_SESSION['fecha_expiracion_2fa']);

        $resultado['exito'] = true;
        $resultado['mensaje'] = 'Login 2FA exitoso.';
        return $resultado;
    }

    /**
     * Crear sesión después de autenticación exitosa (RN-01, RNF-05)
     */
    private function crear_sesion($usuario) {
        // Session security (RNF-05)
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['id_institucion'] = $usuario['id_institucion'];
        $_SESSION['correo'] = $usuario['correo_electronico'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['email'] = $usuario['correo_electronico'];

        // Timestamps para session timeout
        $_SESSION['fecha_login'] = time(); // Absolute timeout
        $_SESSION['ultima_actividad'] = time(); // Inactivity timeout

        // User agent para validación adicional
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Regenerar ID de sesión (prevenir session fixation - OWASP)
        session_regenerate_id(true);

        // Cargar permisos en sesión (después de regenerar)
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        $autorizacion = new ServicioAutorizacion($usuario['id_usuario'], $usuario['id_institucion']);
        $_SESSION['permisos'] = $autorizacion->obtener_permisos();

        // Cargar rol(es) para mostrarlos en la cabecera (consistente en todas las vistas)
        $roles_usuario = $this->modelo_usuario->obtener_roles($usuario['id_usuario'], $usuario['id_institucion']);
        $nombres_roles = array_map(function ($r) { return $r['nombre_rol']; }, $roles_usuario);
        $_SESSION['rol'] = !empty($nombres_roles) ? implode(' · ', $nombres_roles) : 'Usuario';

        // Actualizar última actividad en BD
        $this->modelo_usuario->actualizar_ultima_actividad($usuario['id_usuario'], $usuario['id_institucion']);

        // Registrar login en auditoría
        $this->registrar_login_exitoso($usuario);

        // Generar CSRF token
        Validacion::generar_csrf_token();
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

        // Actualizar último acceso
        $_SESSION['ultima_actividad'] = $tiempo_actual;

        return true;
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
        session_destroy();
    }

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
    }

    /**
     * Requerir autenticación (redirigir a login si no está autenticado)
     */
    public function requerir_autenticacion() {
        if (!$this->validar_sesion_vigente()) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login');
            exit;
        }
    }
}
