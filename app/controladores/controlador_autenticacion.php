<?php
// Controlador de Autenticación (Fase 1)
// Endpoints: login, logout, 2FA, cambiar contraseña, recuperar contraseña

class ControladorAutenticacion {
    private $auth;
    private $modelo_usuario;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/modelos/modelo_usuario.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->modelo_usuario = new ModeloUsuario();
    }

    /**
     * Página de login (GET)
     */
    public function login() {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->auth->esta_autenticado() && $this->auth->validar_sesion_vigente()) {
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        // Generar CSRF token
        $csrf_token = Validacion::generar_csrf_token();

        // Preparar datos para la vista
        $datos = [
            'titulo'     => 'Login - SIRGDI',
            'csrf_token' => $csrf_token,
            'error'      => $_GET['error'] ?? null,
            'exito'      => $_GET['exito'] ?? null,
        ];

        // Renderizar vista
        $this->renderizar_vista('autenticacion/vista_login', $datos);
    }

    /**
     * Procesar login (POST)
     */
    public function procesar_login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login');
            exit;
        }

        // Obtener datos del formulario
        $email = Validacion::sanitizar_email($_POST['email'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $id_institucion = intval($_POST['id_institucion'] ?? 0);

        // Validar entrada
        if (!$email || !$contrasena) {
            $this->redirigir_login('Email y contraseña requeridos.');
            exit;
        }

        // Intentar login
        $resultado = $this->auth->intentar_login($email, $contrasena, $id_institucion ?: null);

        if (!$resultado['exito']) {
            $this->redirigir_login(urlencode($resultado['mensaje']));
            exit;
        }

        // Si requiere 2FA, redirigir a pantalla de 2FA
        if ($resultado['requiere_2fa']) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=2fa');
            exit;
        }

        // Si el técnico externo tiene múltiples instituciones, elegir primero
        if ($resultado['requiere_seleccion_institucion'] ?? false) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=seleccionar_institucion');
            exit;
        }

        // Login exitoso, redirigir al dashboard
        header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
        exit;
    }

    /**
     * Página de 2FA (GET)
     */
    public function dos_fa() {
        // Verificar que hay sesión pendiente de 2FA
        if (!isset($_SESSION['id_usuario_pendiente_2fa'])) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login');
            exit;
        }

        $csrf_token = Validacion::generar_csrf_token();

        $datos = [
            'titulo' => 'Autenticación de Dos Factores - SIRGDI',
            'csrf_token' => $csrf_token,
            'error' => $_GET['error'] ?? null,
        ];

        $this->renderizar_vista('autenticacion/vista_2fa', $datos);
    }

    /**
     * Procesar 2FA (POST)
     */
    public function procesar_2fa() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=2fa');
            exit;
        }

        // Obtener código TOTP
        $codigo_2fa = preg_replace('/[^0-9]/', '', $_POST['codigo_2fa'] ?? '');

        // Validar que tiene 6 dígitos
        if (strlen($codigo_2fa) !== 6) {
            $this->redirigir_2fa('Código debe tener 6 dígitos.');
            exit;
        }

        // Validar 2FA
        $resultado = $this->auth->validar_2fa($codigo_2fa);

        if (!$resultado['exito']) {
            $this->redirigir_2fa(urlencode($resultado['mensaje']));
            exit;
        }

        // Si el técnico externo tiene múltiples instituciones, elegir primero
        if ($resultado['requiere_seleccion_institucion'] ?? false) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=seleccionar_institucion');
            exit;
        }

        // 2FA exitoso, redirigir al dashboard
        header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
        exit;
    }

    /**
     * Logout
     */
    public function logout() {
        $this->auth->logout();
        header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login&logout=1');
        exit;
    }

    /**
     * Página de cambio de contraseña (GET)
     */
    public function cambiar_contrasena() {
        // Requerir autenticación
        $this->auth->requerir_autenticacion();

        $csrf_token = Validacion::generar_csrf_token();

        $datos = [
            'titulo' => 'Cambiar Contraseña - SIRGDI',
            'csrf_token' => $csrf_token,
            'error' => $_GET['error'] ?? null,
            'exito' => $_GET['exito'] ?? null,
        ];

        $this->renderizar_vista('autenticacion/vista_cambiar_contrasena', $datos);
    }

    /**
     * Procesar cambio de contraseña (POST)
     */
    public function procesar_cambiar_contrasena() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=cambiar_contrasena');
            exit;
        }

        // Requerir autenticación
        $this->auth->requerir_autenticacion();

        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        $contrasena_actual = $_POST['contrasena_actual'] ?? '';
        $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';
        $contrasena_confirmar = $_POST['contrasena_confirmar'] ?? '';

        // Validar entrada
        if (!$contrasena_actual || !$contrasena_nueva) {
            $this->redirigir_cambiar_contrasena('Todos los campos son requeridos.', 'error');
            exit;
        }

        if ($contrasena_nueva !== $contrasena_confirmar) {
            $this->redirigir_cambiar_contrasena('Las contraseñas no coinciden.', 'error');
            exit;
        }

        // Validar que sea una buena contraseña
        if (!Validacion::validar_contrasena($contrasena_nueva)) {
            $this->redirigir_cambiar_contrasena('Contraseña débil. Mín 8 caracteres, mayúscula, minúscula, número.', 'error');
            exit;
        }

        try {
            // Cambiar contraseña
            $this->modelo_usuario->cambiar_contrasena(
                $id_usuario,
                $id_institucion,
                $contrasena_actual,
                $contrasena_nueva
            );

            $this->redirigir_cambiar_contrasena('Contraseña cambiada exitosamente.', 'exito');
        } catch (Exception $e) {
            $this->redirigir_cambiar_contrasena($e->getMessage(), 'error');
        }

        exit;
    }

    /**
     * Página de recuperación de contraseña (GET)
     */
    public function recuperar_contrasena() {
        $csrf_token = Validacion::generar_csrf_token();

        $datos = [
            'titulo' => 'Recuperar Contraseña - SIRGDI',
            'csrf_token' => $csrf_token,
            'error' => $_GET['error'] ?? null,
            'exito' => $_GET['exito'] ?? null,
        ];

        $this->renderizar_vista('autenticacion/vista_recuperar_contrasena', $datos);
    }

    /**
     * Procesar recuperación de contraseña — genera token y envía email
     */
    public function procesar_recuperar_contrasena() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=recuperar_contrasena');
            exit;
        }

        $email = Validacion::sanitizar_email($_POST['email'] ?? '');
        if (!$email) {
            $this->redirigir_recuperar_contrasena('Email requerido.', 'error');
            exit;
        }

        // Buscar usuario (sin revelar si existe por seguridad)
        require_once APP_PATH . '/modelos/modelo_usuario.php';
        $modelo_usuario = new ModeloUsuario();
        $usuario = $modelo_usuario->obtener_por_email($email);

        if ($usuario && $usuario['activo']) {
            try {
                $token = $modelo_usuario->generar_token_reset(
                    $usuario['id_usuario'],
                    $usuario['id_institucion']
                );

                $link_reset = config('app.url_base')
                    . '/?controlador=autenticacion&accion=restablecer_contrasena&token='
                    . urlencode($token);

                require_once APP_PATH . '/servicios/servicio_notificacion.php';
                $notif = new ServicioNotificacion();
                $notif->enviar_recuperacion_contrasena(
                    $usuario['correo_electronico'],
                    $usuario['nombre_completo'] ?? 'Usuario',
                    $link_reset
                );
            } catch (Exception $e) {
                // Silenciar — no revelar al usuario si falló
            }
        }

        // Mensaje siempre genérico (no revelar si el email existe)
        $this->redirigir_recuperar_contrasena(
            'Si el correo está registrado, recibirás un enlace en los próximos minutos. Revisa también la carpeta de spam.',
            'exito'
        );
        exit;
    }

    /**
     * Mostrar formulario para ingresar nueva contraseña (GET con token)
     */
    public function restablecer_contrasena() {
        $token = trim($_GET['token'] ?? '');

        if (!$token) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=recuperar_contrasena');
            exit;
        }

        // Verificar que el token sea válido antes de mostrar el formulario
        $sql = 'SELECT id_usuario FROM usuario
                WHERE token_reset_pass = :token
                  AND token_reset_expira > NOW()
                LIMIT 1';
        require_once LIB_PATH . '/basedatos.php';
        $bd      = BaseDatos::obtener();
        $valido  = $bd->obtener_uno($sql, [':token' => $token]);

        if (!$valido) {
            $this->redirigir_recuperar_contrasena(
                'El enlace de recuperación ha expirado o ya fue usado. Solicita uno nuevo.',
                'error'
            );
            exit;
        }

        $datos = [
            'titulo'    => 'Nueva Contraseña - SIRGDI',
            'token'     => $token,
            'error'     => $_GET['error'] ?? null,
        ];
        $this->renderizar_vista('autenticacion/vista_restablecer_contrasena', $datos);
    }

    /**
     * Procesar nueva contraseña (POST)
     */
    public function procesar_restablecer_contrasena() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=recuperar_contrasena');
            exit;
        }

        $token             = trim($_POST['token'] ?? '');
        $nueva_contrasena  = $_POST['nueva_contrasena'] ?? '';
        $confirmar         = $_POST['confirmar_contrasena'] ?? '';

        $url_token = config('app.url_base')
            . '/?controlador=autenticacion&accion=restablecer_contrasena&token='
            . urlencode($token);

        if (!$token || !$nueva_contrasena || !$confirmar) {
            header('Location: ' . $url_token . '&error=' . urlencode('Todos los campos son requeridos.'));
            exit;
        }

        if ($nueva_contrasena !== $confirmar) {
            header('Location: ' . $url_token . '&error=' . urlencode('Las contraseñas no coinciden.'));
            exit;
        }

        if (strlen($nueva_contrasena) < 8) {
            header('Location: ' . $url_token . '&error=' . urlencode('La contraseña debe tener al menos 8 caracteres.'));
            exit;
        }

        try {
            require_once APP_PATH . '/modelos/modelo_usuario.php';
            $modelo_usuario = new ModeloUsuario();
            $modelo_usuario->usar_token_reset($token, $nueva_contrasena);

            header('Location: ' . config('app.url_base')
                . '/?controlador=autenticacion&accion=login&exito='
                . urlencode('Contraseña actualizada correctamente. Ya puedes iniciar sesión.'));

        } catch (Exception $e) {
            header('Location: ' . $url_token . '&error=' . urlencode('El enlace expiró o ya fue usado. Solicita uno nuevo.'));
        }
        exit;
    }

    /**
     * Página de inicio (para usuarios no autenticados)
     */
    public function inicio() {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->auth->esta_autenticado() && $this->auth->validar_sesion_vigente()) {
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        // Obtener instituciones activas para el botón de reporte libre
        $instituciones_publicas = [];
        try {
            require_once APP_PATH . '/modelos/modelo_institucion.php';
            $modelo_inst = new ModeloInstitucion();
            $instituciones_publicas = $modelo_inst->listar(true);
        } catch (Exception $e) {
            // No interrumpir la carga si falla
        }

        // Renderizar página de inicio pública
        $datos = [
            'titulo'                 => 'Bienvenido - SIRGDI v2.0',
            'app_name'               => config('app.app_name'),
            'instituciones_publicas' => $instituciones_publicas,
        ];

        $this->renderizar_vista('autenticacion/vista_inicio', $datos);
    }

    /**
     * Selector de institución para técnicos externos con múltiples vínculos (GET)
     */
    public function seleccionar_institucion() {
        // Debe estar autenticado (sesión válida) pero aún con pendiente
        if (!$this->auth->validar_sesion_vigente()) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login');
            exit;
        }

        if (empty($_SESSION['pendiente_seleccion_institucion'])) {
            // Ya seleccionó o no necesita selector
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        $csrf_token = Validacion::generar_csrf_token();

        $datos = [
            'titulo'       => 'Seleccionar Institución - SIRGDI',
            'csrf_token'   => $csrf_token,
            'instituciones' => $_SESSION['instituciones_disponibles'] ?? [],
            'error'        => $_GET['error'] ?? null,
        ];

        $this->renderizar_vista('autenticacion/vista_seleccionar_institucion', $datos);
    }

    /**
     * Procesar selección de institución (POST)
     */
    public function procesar_seleccion_institucion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=seleccionar_institucion');
            exit;
        }

        if (!$this->auth->validar_sesion_vigente()) {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=login');
            exit;
        }

        $id_institucion = intval($_POST['id_institucion'] ?? 0);

        if (!$id_institucion || !$this->auth->seleccionar_institucion($id_institucion)) {
            $url = config('app.url_base') . '/?controlador=autenticacion&accion=seleccionar_institucion'
                 . '&error=' . urlencode('Selección inválida. Elige una institución de la lista.');
            header('Location: ' . $url);
            exit;
        }

        header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
        exit;
    }

    /**
     * Cambiar institución activa (técnicos con múltiples vínculos) — POST desde el header
     */
    public function cambiar_institucion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        $this->auth->requerir_autenticacion();

        // Solo disponible para técnicos externos (tienen id_institucion_propia)
        if (empty($_SESSION['id_institucion_propia'])) {
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        $id_nuevo = intval($_POST['id_institucion'] ?? 0);
        if (!$id_nuevo) {
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        // Verificar que la institución elegida sea un vínculo válido del técnico
        $instituciones = $this->modelo_usuario->obtener_instituciones_tecnico($_SESSION['id_usuario']);
        $ids_validos = array_column($instituciones, 'id_institucion');

        if (!in_array($id_nuevo, $ids_validos)) {
            header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
            exit;
        }

        // Reactivar selector temporal y usar seleccionar_institucion del servicio
        $_SESSION['pendiente_seleccion_institucion'] = true;
        $_SESSION['instituciones_disponibles']       = $instituciones;
        $this->auth->seleccionar_institucion($id_nuevo);

        header('Location: ' . config('app.url_base') . '/?controlador=dashboard&accion=inicio');
        exit;
    }

    // ===== HELPERS =====

    /**
     * Renderizar vista (incluir archivo de vista)
     */
    private function renderizar_vista($vista, $datos = []) {
        extract($datos);
        $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';

        if (!file_exists($archivo_vista)) {
            die('Vista no encontrada: ' . $archivo_vista);
        }

        ob_start();
        ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo ?? 'SIRGDI'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo config("app.url_base"); ?>/css/estilos_formularios_modernos.css">
    <link rel="stylesheet" href="<?php echo config("app.url_base"); ?>/css/estilos_profesionales.css">
    <link rel="stylesheet" href="<?php echo config('app.url_base'); ?>/css/estilos_base.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #2C3E50; background-color: #ECF0F1; }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_header.php'; endif; ?>
    <main class="main-content">
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo config('app.url_base'); ?>/js/script_base.js"></script>
</body>
</html><?php
        echo ob_get_clean();
    }

    /**
     * Redirigir a login con error
     */
    private function redirigir_login($error_msg = '') {
        $url = config('app.url_base') . '/?controlador=autenticacion&accion=login';
        if ($error_msg) {
            $url .= '&error=' . $error_msg;
        }
        header('Location: ' . $url);
    }

    /**
     * Redirigir a 2FA con error
     */
    private function redirigir_2fa($error_msg = '') {
        $url = config('app.url_base') . '/?controlador=autenticacion&accion=2fa';
        if ($error_msg) {
            $url .= '&error=' . $error_msg;
        }
        header('Location: ' . $url);
    }

    /**
     * Redirigir a cambiar contraseña
     */
    private function redirigir_cambiar_contrasena($mensaje = '', $tipo = 'error') {
        $url = config('app.url_base') . '/?controlador=autenticacion&accion=cambiar_contrasena';
        if ($mensaje) {
            $url .= '&' . $tipo . '=' . urlencode($mensaje);
        }
        header('Location: ' . $url);
    }

    /**
     * Redirigir a recuperar contraseña
     */
    private function redirigir_recuperar_contrasena($mensaje = '', $tipo = 'error') {
        $url = config('app.url_base') . '/?controlador=autenticacion&accion=recuperar_contrasena';
        if ($mensaje) {
            $url .= '&' . $tipo . '=' . urlencode($mensaje);
        }
        header('Location: ' . $url);
    }
}
