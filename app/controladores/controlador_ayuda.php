<?php
/**
 * ControladorAyuda
 *
 * No existía ninguna ayuda dentro de la aplicación: ni pantalla, ni manual, ni
 * entrada de soporte en el menú, ni un correo de contacto en el pie. Lo único
 * que explicaba algo era la sección "¿Cómo funciona?" de la portada, que solo
 * ve quien NO ha iniciado sesión — justo al revés de lo que hace falta.
 *
 * Con un cliente eso se suple con una llamada. Con diez, no.
 */

require_once APP_PATH . '/servicios/servicio_autenticacion.php';
require_once APP_PATH . '/servicios/servicio_autorizacion.php';

class ControladorAyuda
{
    private $auth;
    private $autorizacion;

    public function __construct()
    {
        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
    }

    public function inicio()
    {
        $this->auth->requerir_autenticacion();

        // La ayuda se filtra por rol: enseñarle a un reportante cómo se
        // configura el SLA solo le hace buscar entre cosas que no puede tocar.
        $roles = array_map('intval', $this->autorizacion->obtener_roles());

        $datos = [
            'titulo'          => 'Ayuda — ' . config('app.app_name'),
            'roles'           => $roles,
            'es_tecnico'      => in_array(ROL_TECNICO, $roles, true),
            'es_gestor'       => (bool) array_intersect([ROL_GESTOR, ROL_RECTOR, ROL_ADMIN, ROL_SUPERADMIN], $roles),
            'es_admin'        => (bool) array_intersect([ROL_ADMIN, ROL_RECTOR, ROL_SUPERADMIN], $roles),
            'es_superadmin'   => in_array(ROL_SUPERADMIN, $roles, true),
            'correo_soporte'  => config('app.correo_soporte'),
        ];

        $this->renderizar_vista('ayuda/vista_ayuda', $datos);
    }

    private function renderizar_vista($vista, $datos = [])
    {
        mensajes_de_la_url();
        extract($datos);
        $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';

        if (!file_exists($archivo_vista)) {
            responder_error_interno('Vista no encontrada: ' . $archivo_vista);
        }

        ob_start();
        ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){try{var t=localStorage.getItem('sirgdi_tema');if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches)?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
    <title><?php echo htmlspecialchars($titulo ?? config('app.app_name')); ?></title>
    <link rel="icon" type="image/png" sizes="64x64" href="<?php echo asset_url('img/favicon.png'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_profesionales.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_base.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_toasts.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: var(--color-text); background-color: var(--color-bg); transition: background-color .2s ease, color .2s ease; }
    </style>
</head>
<body>
    <?php require_once APP_PATH . '/vistas/comunes/vista_header.php'; ?>
    <main class="main-content">
        <?php require $archivo_vista; ?>
    </main>
    <?php require_once APP_PATH . '/vistas/comunes/vista_footer.php'; ?>
    <script src="<?php echo asset_url('js/script_base.js'); ?>"></script>
    <script src="<?php echo asset_url('js/tema.js'); ?>"></script>
    <script src="<?php echo asset_url('js/toast.js'); ?>"></script>
</body>
</html><?php
        echo ob_get_clean();
    }
}
