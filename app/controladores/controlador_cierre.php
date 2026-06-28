<?php
// Controlador de Cierre (Validación y cierre de reportes)
// RF-21 (Validar solución), RF-22 (Encuesta), RF-24 (Cierre y notificación)

class ControladorCierre {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_evidencia;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_evidencia.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_evidencia = new ModeloEvidencia();
    }

    /**
     * RF-21: Validar solución (Gestor)
     * Two-step: Tech marca solucionado → Gestor valida y cierra
     */
    public function validar_solucion() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->validar_solucion_form();
        } else {
            return $this->procesar_validar_solucion();
        }
    }

    private function validar_solucion_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VALIDAR_CIERRE);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de reporte requerido.');
        }

        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte no encontrado.');
        }

        if ($reporte['id_estado'] != ESTADO_SOLUCIONADO) {
            http_response_code(HTTP_BAD_REQUEST);
            die('El reporte no está en estado Solucionado.');
        }

        // Obtener evidencias
        $evidencias = $this->modelo_evidencia->listar_por_reporte($id_reporte, $id_institucion);

        $datos = [
            'titulo' => 'Validar Solución - SIRGDI',
            'reporte' => $reporte,
            'evidencias' => $evidencias,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('cierre/vista_validar_solucion', $datos);
    }

    private function procesar_validar_solucion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VALIDAR_CIERRE);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $validacion = $_POST['validacion'] ?? ''; // 'aprobada' o 'rechazada'
        $comentario = Validacion::sanitizar_texto($_POST['comentario_validacion'] ?? '');
        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        try {
            if (!$id_reporte || !in_array($validacion, ['aprobada', 'rechazada'])) {
                throw new Exception('Datos incompletos.');
            }

            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte || $reporte['id_estado'] != ESTADO_SOLUCIONADO) {
                throw new Exception('Reporte inválido o no en estado Solucionado.');
            }

            if ($validacion === 'aprobada') {
                // RF-22: Solicitar encuesta (próximo paso)
                $this->modelo_reporte->cambiar_estado(
                    $id_reporte,
                    $id_institucion,
                    ESTADO_EN_VALIDACION, // Intermediate state
                    'Validación aprobada por gestor. Pendiente encuesta.',
                    $id_usuario
                );

                $mensaje = 'Solución aprobada. Solicitar encuesta de satisfacción...';
                $siguiente = '/?controlador=cierre&accion=solicitar_encuesta&id=' . $id_reporte;

            } else {
                // RECHAZADA: Devolver a técnico
                $this->modelo_reporte->cambiar_estado(
                    $id_reporte,
                    $id_institucion,
                    ESTADO_DEVUELTO,
                    'Validación rechazada. Razón: ' . $comentario,
                    $id_usuario
                );

                // Reanudar SLA
                $this->modelo_reporte->reanudar_sla($id_reporte, $id_institucion);

                $mensaje = 'Solución rechazada. Devuelto a técnico.';
                $siguiente = '/?controlador=gestion&accion=kanban';
            }

            header('Location: ' . config('app.url_base') . $siguiente . '&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * RF-22: Solicitar encuesta de satisfacción
     */
    public function solicitar_encuesta() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->solicitar_encuesta_form();
        } else {
            return $this->procesar_solicitar_encuesta();
        }
    }

    private function solicitar_encuesta_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VALIDAR_CIERRE);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de reporte requerido.');
        }

        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte no encontrado.');
        }

        $datos = [
            'titulo' => 'Solicitar Encuesta - SIRGDI',
            'reporte' => $reporte,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('cierre/vista_solicitar_encuesta', $datos);
    }

    private function procesar_solicitar_encuesta() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VALIDAR_CIERRE);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        try {
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // Enviar encuesta al reportante (v2.0: email con link + token)
            // Por ahora: log
            $log_msg = sprintf(
                "[%s] Encuesta enviada - Reporte: %s, Reportante: %d\n",
                date('Y-m-d H:i:s'),
                $reporte['numero_ticket'],
                $reporte['id_reportante']
            );
            @file_put_contents(LOG_DIR . '/encuestas.log', $log_msg, FILE_APPEND);

            // Cambiar a estado EN_VALIDACION (esperando respuesta de encuesta)
            // Después de N días sin respuesta, cerrar automáticamente

            header('Location: ' . config('app.url_base') . '/?controlador=cierre&accion=cerrar_reporte&id=' . $id_reporte);
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * RF-24: Cerrar reporte
     * Two-step completo: Tech → Gestor valida → Cierra → Notificación a reportante
     */
    public function cerrar_reporte() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->cerrar_reporte_form();
        } else {
            return $this->procesar_cerrar_reporte();
        }
    }

    private function cerrar_reporte_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VALIDAR_CIERRE);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de reporte requerido.');
        }

        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte no encontrado.');
        }

        $datos = [
            'titulo' => 'Cerrar Reporte - SIRGDI',
            'reporte' => $reporte,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('cierre/vista_cerrar_reporte', $datos);
    }

    private function procesar_cerrar_reporte() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VALIDAR_CIERRE);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        try {
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // Cambiar a CERRADO
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                ESTADO_CERRADO,
                'Reporte cerrado por gestor',
                $id_usuario
            );

            // RF-24: Notificar a reportante y rector
            require_once APP_PATH . '/servicios/servicio_notificacion.php';
            $servicio_notificacion = new ServicioNotificacion();

            // Obtener email del reportante
            require_once APP_PATH . '/modelos/modelo_usuario.php';
            $modelo_usuario = new ModeloUsuario();
            $reportante = $modelo_usuario->obtener_por_id($reporte['id_reportante'], $id_institucion);

            $servicio_notificacion->notificar_reporte_cerrado(
                $id_reporte,
                $id_institucion,
                $reporte['numero_ticket'],
                $reportante['correo_electronico'] ?? null
            );

            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=Reporte cerrado correctamente');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // ===== HELPERS =====

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
    <link rel="stylesheet" href="<?php echo config('app.url_base'); ?>/css/estilos_toasts.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_header.php'; endif; ?>
    <main class="main-content">
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo config('app.url_base'); ?>/js/script_base.js"></script>
    <script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>
    <?php if (!empty($_SESSION['exito'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        toast.success('¡Éxito!', '<?php echo addslashes(htmlspecialchars($_SESSION['exito'])); ?>', 5000);
    });
    </script>
    <?php unset($_SESSION['exito']); endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        toast.error('Error', '<?php echo addslashes(htmlspecialchars($_SESSION['error'])); ?>', 6000);
    });
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html><?php
        echo ob_get_clean();
    }
}
