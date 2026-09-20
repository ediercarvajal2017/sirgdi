<?php
// Controlador de Cierre (Validación y cierre de reportes)
// RF-21 (Validar solución), RF-22 (Encuesta), RF-24 (Cierre y notificación)

class ControladorCierre {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_evidencia;
    private $modelo_avance;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_evidencia.php';
        require_once APP_PATH . '/modelos/modelo_avance.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_evidencia = new ModeloEvidencia();
        $this->modelo_avance = new ModeloAvance();
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
            'titulo' => 'Validar Solución - ' . config('app.app_name'),
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

            // El formulario ya lo exige por JS; se repite en el servidor porque el motivo
            // ahora se envía al técnico y queda visible para el reportante, así que no
            // puede quedar vacío aunque alguien salte el formulario.
            if ($validacion === 'rechazada' && mb_strlen($comentario) < 5) {
                throw new Exception('Debes indicar el motivo del rechazo (mínimo 5 caracteres).');
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

                // El motivo del rechazo se guardaba solo en el historial interno de
                // transiciones, que ninguna pantalla muestra: quedaba invisible tanto
                // para el técnico como para el reportante. Se registra también como
                // avance (mismo hilo que ya se ve en la hoja de trabajo, el detalle del
                // gestor y el seguimiento público) para que quede a la vista de todos.
                $this->modelo_avance->crear([
                    'id_reporte' => $id_reporte,
                    'id_institucion' => $id_institucion,
                    'id_usuario_autor' => $id_usuario,
                    'texto' => mb_substr('Solución rechazada: ' . $comentario, 0, 500),
                ]);

                // Aviso al técnico: antes no se le notificaba de ninguna forma y el
                // ticket simplemente reaparecía en su lista sin explicación.
                require_once APP_PATH . '/servicios/servicio_notificacion.php';
                (new ServicioNotificacion())->notificar_reporte_devuelto(
                    $id_reporte, $id_institucion, $reporte['numero_ticket'], $comentario
                );

                $mensaje = 'Solución rechazada. Devuelto a técnico.';
                $siguiente = '/?controlador=gestion&accion=kanban';
            }

            ServicioAuditoria::registrar($validacion === 'aprobada' ? 'validar_solucion' : 'devolver_reporte', 'reporte', $id_reporte,
                ['id_estado' => ESTADO_SOLUCIONADO],
                ['id_estado' => $validacion === 'aprobada' ? ESTADO_EN_VALIDACION : ESTADO_DEVUELTO, 'ticket' => $reporte['numero_ticket'], 'validacion' => $validacion]);
            header('Location: ' . config('app.url_base') . $siguiente . '&exito=1');
            exit;

        } catch (Exception $e) {
            // Volver al formulario de origen (no al Kanban) para no perder el contexto/comentario del gestor.
            $destino = $id_reporte
                ? '/?controlador=cierre&accion=validar_solucion&id=' . $id_reporte
                : '/?controlador=gestion&accion=kanban';
            header('Location: ' . config('app.url_base') . $destino . '&error=' . urlencode($e->getMessage()));
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
            'titulo' => 'Solicitar Encuesta - ' . config('app.app_name'),
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

        try {
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            $encuesta_enviada = false;

            // RF-22: la encuesta solo aplica a reportantes con cuenta registrada —
            // encuesta_satisfaccion exige id_usuario_reportante (FK NOT NULL). Los
            // reportes de invitado se omiten sin bloquear el flujo de cierre.
            if (!empty($reporte['id_reportante']) && !empty($reporte['correo_reportante']) && !empty($reporte['token_seguimiento_publico'])) {
                require_once APP_PATH . '/modelos/modelo_encuesta.php';
                $modelo_encuesta = new ModeloEncuesta();

                if (!$modelo_encuesta->obtener_por_reporte($id_reporte, $id_institucion)) {
                    $modelo_encuesta->crear($id_reporte, $id_institucion, $reporte['id_reportante']);
                }

                $link_encuesta = config('app.url_base')
                    . '/?controlador=reportes&accion=seguimiento&token=' . urlencode($reporte['token_seguimiento_publico'])
                    . '#encuesta';

                require_once APP_PATH . '/servicios/servicio_notificacion.php';
                (new ServicioNotificacion())->notificar_encuesta(
                    $id_reporte,
                    $id_institucion,
                    $reporte['numero_ticket'],
                    $reporte['correo_reportante'],
                    $link_encuesta
                );
                $encuesta_enviada = true;
            }

            ServicioAuditoria::registrar('solicitar_encuesta', 'reporte', $id_reporte, null, [
                'ticket' => $reporte['numero_ticket'] ?? null,
                'encuesta_enviada' => $encuesta_enviada,
            ]);
            header('Location: ' . config('app.url_base') . '/?controlador=cierre&accion=cerrar_reporte&id=' . $id_reporte);
            exit;

        } catch (Exception $e) {
            $destino = $id_reporte
                ? '/?controlador=cierre&accion=solicitar_encuesta&id=' . $id_reporte
                : '/?controlador=gestion&accion=kanban';
            header('Location: ' . config('app.url_base') . $destino . '&error=' . urlencode($e->getMessage()));
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

        if ($reporte['id_estado'] != ESTADO_EN_VALIDACION) {
            http_response_code(HTTP_BAD_REQUEST);
            die('El reporte no está en estado En Validación.');
        }

        $datos = [
            'titulo' => 'Cerrar Reporte - ' . config('app.app_name'),
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
            if ($reporte['id_estado'] != ESTADO_EN_VALIDACION) {
                throw new Exception('El reporte no está en estado En Validación. No se puede cerrar.');
            }

            // Cambiar a CERRADO
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                ESTADO_CERRADO,
                'Reporte cerrado por gestor',
                $id_usuario
            );

            // RF-24: Notificar a reportante y rector. El correo del reportante ya vive
            // en el propio reporte (correo_reportante) tanto para usuarios registrados
            // como para invitados; re-consultarlo por id_reportante fallaba en silencio
            // para invitados (id_reportante es NULL, la consulta no encuentra fila) y el
            // correo de cierre nunca les llegaba.
            require_once APP_PATH . '/servicios/servicio_notificacion.php';
            $servicio_notificacion = new ServicioNotificacion();

            $servicio_notificacion->notificar_reporte_cerrado(
                $id_reporte,
                $id_institucion,
                $reporte['numero_ticket'],
                $reporte['correo_reportante'] ?? null
            );

            ServicioAuditoria::registrar('cerrar_reporte', 'reporte', $id_reporte,
                ['id_estado' => $reporte['id_estado']], ['id_estado' => ESTADO_CERRADO, 'ticket' => $reporte['numero_ticket']]);
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=Reporte cerrado correctamente');
            exit;

        } catch (Exception $e) {
            $destino = $id_reporte
                ? '/?controlador=cierre&accion=cerrar_reporte&id=' . $id_reporte
                : '/?controlador=gestion&accion=kanban';
            header('Location: ' . config('app.url_base') . $destino . '&error=' . urlencode($e->getMessage()));
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
    <script>(function(){try{var t=localStorage.getItem('sirgdi_tema');if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches)?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
    <title><?php echo htmlspecialchars($titulo ?? config('app.app_name')); ?></title>
    <link rel="icon" type="image/png" sizes="64x64" href="<?php echo asset_url('img/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset_url('img/apple-touch-icon.png'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_formularios_modernos.css'); ?>">
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
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_header.php'; endif; ?>
    <main class="main-content">
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo asset_url('js/script_base.js'); ?>"></script>
    <script src="<?php echo asset_url('js/tema.js'); ?>"></script>
    <script src="<?php echo asset_url('js/toast.js'); ?>"></script>
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
