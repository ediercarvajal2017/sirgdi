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
            'titulo' => 'Login - SIRGDI',
            'csrf_token' => $csrf_token,
            'error' => $_GET['error'] ?? null,
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
     * Procesar recuperación de contraseña (POST)
     * Nota: En versión 1.0 solo registra el email para admin
     * En versión 2.0 se implementaría envío de email con token
     */
    public function procesar_recuperar_contrasena() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=autenticacion&accion=recuperar_contrasena');
            exit;
        }

        $email = Validacion::sanitizar_email($_POST['email'] ?? '');

        // Validar entrada
        if (!$email) {
            $this->redirigir_recuperar_contrasena('Email requerido.', 'error');
            exit;
        }

        // En v1.0, siempre mostrar mensaje genérico (no revelar si existe email)
        // En v2.0: generar token y enviar por email
        $this->redirigir_recuperar_contrasena('Si el email existe, recibirás instrucciones de recuperación.', 'exito');

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
        <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!empty($exito)): ?><div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div><?php endif; ?>
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
