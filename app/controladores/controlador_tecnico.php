<?php
// Controlador Técnico (Workflow de técnico)
// RF-16 (Mis asignaciones), RF-17 (Intervención), RF-18 (Evidencia), RF-20 (Cierre técnico)

class ControladorTecnico {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_intervension;
    private $modelo_evidencia;
    private $servicio_archivos;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_intervension.php';
        require_once APP_PATH . '/modelos/modelo_evidencia.php';
        require_once APP_PATH . '/servicios/servicio_archivos.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_intervension = new ModeloIntervension();
        $this->modelo_evidencia = new ModeloEvidencia();
        $this->servicio_archivos = new ServicioArchivos();
    }

    /**
     * RF-16: Listar mis asignaciones (técnico autenticado)
     */
    public function mis_asignaciones() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        // Obtener reportes asignados al técnico (en proceso o devueltos)
        $reportes = $this->modelo_reporte->listar_por_tecnico($id_usuario, $id_institucion, null);

        $datos = [
            'titulo' => 'Mis Asignaciones - SIRGDI',
            'reportes' => $reportes,
        ];

        $this->renderizar_vista('tecnico/vista_mis_asignaciones', $datos);
    }

    /**
     * RF-17: Crear intervención técnica
     */
    public function crear_intervension() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->crear_intervension_form();
        } else {
            return $this->procesar_crear_intervension();
        }
    }

    private function crear_intervension_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_usuario = $this->auth->obtener_id_usuario();
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

        // RN-05: Validar que es el técnico asignado
        if ($reporte['id_tecnico_asignado'] != $id_usuario) {
            http_response_code(HTTP_FORBIDDEN);
            die('No eres el técnico asignado a este reporte.');
        }

        // Si ya existe un informe para este reporte, ir directo a evidencias
        $intervension_existente = $this->modelo_intervension->obtener_por_reporte($id_reporte, $id_institucion);

        $datos = [
            'titulo' => 'Crear Intervención - SIRGDI',
            'reporte' => $reporte,
            'intervension_existente' => $intervension_existente,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('tecnico/vista_crear_intervension', $datos);
    }

    private function procesar_crear_intervension() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $descripcion = Validacion::sanitizar_texto($_POST['descripcion_actividades'] ?? '');
        $causa_raiz = Validacion::sanitizar_texto($_POST['causa_raiz'] ?? '');
        $solucion = Validacion::sanitizar_texto($_POST['solucion_implementada'] ?? '');
        $materiales_texto = trim($_POST['materiales'] ?? '');
        $costo = $_POST['costo_estimado'] !== '' ? floatval($_POST['costo_estimado'] ?? 0) : null;
        $fecha_inicio = $_POST['fecha_hora_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_hora_fin'] ?? '';
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte || strlen($descripcion) < 20) {
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=crear_intervension&id=' . $id_reporte . '&error=' . urlencode('La descripción de actividades debe tener al menos 20 caracteres.'));
            exit;
        }

        try {
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // RN-05: Validar técnico asignado
            if ($reporte['id_tecnico_asignado'] != $id_usuario) {
                throw new Exception('No eres el técnico asignado.');
            }

            // Si ya existe informe, no duplicar: redirigir a evidencias
            $existente = $this->modelo_intervension->obtener_por_reporte($id_reporte, $id_institucion);
            if ($existente) {
                header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=cargar_evidencia&id_intervension=' . $existente['id_informe']);
                exit;
            }

            // Construir JSON de materiales (una línea = un material)
            $materiales_json = null;
            if ($materiales_texto !== '') {
                $lineas = array_filter(array_map('trim', explode("\n", $materiales_texto)));
                $materiales = [];
                foreach ($lineas as $linea) {
                    $materiales[] = ['nombre' => $linea];
                }
                $materiales_json = json_encode($materiales, JSON_UNESCAPED_UNICODE);
            }

            // Crear informe de intervención
            $id_intervension = $this->modelo_intervension->crear([
                'id_reporte' => $id_reporte,
                'id_institucion' => $id_institucion,
                'id_usuario_tecnico' => $id_usuario,
                'descripcion_actividades' => $descripcion,
                'causa_raiz' => $causa_raiz ?: null,
                'solucion_implementada' => $solucion ?: $descripcion,
                'fecha_hora_inicio' => $fecha_inicio ? str_replace('T', ' ', $fecha_inicio) . ':00' : date('Y-m-d H:i:s'),
                'fecha_hora_fin' => $fecha_fin ? str_replace('T', ' ', $fecha_fin) . ':00' : null,
                'materiales_utilizados_json' => $materiales_json,
                'costo_estimado' => $costo,
            ]);

            // Cambiar estado a "En Proceso" si no lo está
            if ($reporte['id_estado'] != ESTADO_EN_PROCESO) {
                $this->modelo_reporte->cambiar_estado(
                    $id_reporte,
                    $id_institucion,
                    ESTADO_EN_PROCESO,
                    'Intervención iniciada por técnico',
                    $id_usuario
                );
            }

            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=cargar_evidencia&id_intervension=' . $id_intervension . '&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=mis_asignaciones&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * RF-18: Cargar evidencia fotográfica (3 etapas)
     * RN-03: Mínimo 1 foto por etapa antes de cerrar
     */
    public function cargar_evidencia() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->cargar_evidencia_form();
        } else {
            return $this->procesar_cargar_evidencia();
        }
    }

    private function cargar_evidencia_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_intervension = intval($_GET['id_intervension'] ?? 0);
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_intervension) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de intervención requerido.');
        }

        // Obtener intervención y validar que pertenece al técnico
        $intervension = $this->modelo_intervension->obtener_por_id($id_intervension, $id_institucion);

        if (!$intervension || $intervension['id_usuario_tecnico'] != $id_usuario) {
            http_response_code(HTTP_FORBIDDEN);
            die('No tienes acceso a esta intervención.');
        }

        $reporte = $this->modelo_reporte->obtener_por_id($intervension['id_reporte'], $id_institucion);
        $evidencias = $this->modelo_evidencia->listar_por_reporte($intervension['id_reporte'], $id_institucion);
        $completitud = $this->modelo_evidencia->verificar_completitud($intervension['id_reporte'], $id_institucion);

        $etapas = ['antes' => 'Antes', 'durante' => 'Durante', 'despues' => 'Después'];

        $datos = [
            'titulo' => 'Cargar Evidencia - SIRGDI',
            'intervension' => $intervension,
            'reporte' => $reporte,
            'evidencias' => $evidencias,
            'completitud' => $completitud,
            'etapas' => $etapas,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('tecnico/vista_cargar_evidencia', $datos);
    }

    private function procesar_cargar_evidencia() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_intervension = intval($_POST['id_intervension'] ?? 0);
        $etapa = $_POST['etapa_evidencia'] ?? '';
        $descripcion = Validacion::sanitizar_texto($_POST['descripcion_foto'] ?? '');
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_intervension || !in_array($etapa, ['antes', 'durante', 'despues']) || empty($_FILES['foto'])) {
            http_response_code(HTTP_BAD_REQUEST);
            die('Datos incompletos.');
        }

        try {
            // Validar intervención
            $intervension = $this->modelo_intervension->obtener_por_id($id_intervension, $id_institucion);

            if (!$intervension || $intervension['id_usuario_tecnico'] != $id_usuario) {
                throw new Exception('No tienes acceso a esta intervención.');
            }

            // Procesar archivo (RNF-04: compresión automática)
            $resultado_archivo = $this->servicio_archivos->procesar_foto(
                $_FILES['foto']['tmp_name'],
                $_FILES['foto']['name']
            );

            if (!$resultado_archivo['exito']) {
                throw new Exception($resultado_archivo['error']);
            }

            // Guardar en BD (el modelo mapea etapa string -> id_etapa y nombres de columnas)
            $this->modelo_evidencia->crear([
                'id_reporte' => $intervension['id_reporte'],
                'id_institucion' => $id_institucion,
                'etapa_evidencia' => $etapa,
                'url_archivo' => $resultado_archivo['ruta'],
                'nombre_archivo_original' => $resultado_archivo['nombre_original'],
                'tipo_mime' => 'image/jpeg',
                'tamanio_bytes' => $resultado_archivo['tamaño_bytes'],
                'descripcion' => $descripcion,
                'cargada_por' => $id_usuario,
            ]);

            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=cargar_evidencia&id_intervension=' . $id_intervension . '&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=cargar_evidencia&id_intervension=' . $id_intervension . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * RF-20: Marcar reporte como solucionado (técnico)
     * Requiere evidencia completa (RN-03)
     */
    public function marcar_solucionado() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        try {
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte || $reporte['id_tecnico_asignado'] != $id_usuario) {
                throw new Exception('No tienes acceso a este reporte.');
            }

            // RN-03: Verificar completitud de evidencia
            $completitud = $this->modelo_evidencia->verificar_completitud($id_reporte, $id_institucion);
            if (!$completitud['completa']) {
                throw new Exception('Evidencia incompleta. Faltan fotos de: ' . implode(', ', $completitud['faltantes']));
            }

            // Cambiar estado a SOLUCIONADO (requiere validación de gestor)
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                ESTADO_SOLUCIONADO,
                'Técnico marca como solucionado',
                $id_usuario
            );

            // Notificar a gestor/rector
            require_once APP_PATH . '/servicios/servicio_notificacion.php';
            $servicio_notificacion = new ServicioNotificacion();
            $servicio_notificacion->notificar_reporte_solucionado(
                $id_reporte,
                $id_institucion,
                $reporte['numero_ticket']
            );

            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=mis_asignaciones&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=mis_asignaciones&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Descargar evidencia (con validación de seguridad)
     */
    public function descargar_evidencia() {
        $this->auth->requerir_autenticacion();

        $id_evidencia = intval($_GET['id'] ?? 0);
        $id_institucion = intval($_GET['inst'] ?? 0);

        if (!$id_evidencia) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de evidencia requerido.');
        }

        $evidencia = $this->modelo_evidencia->obtener_por_id($id_evidencia, $id_institucion);
        if (!$evidencia) {
            http_response_code(HTTP_NOT_FOUND);
            die('Evidencia no encontrada.');
        }

        // Validar acceso (RN-01: misma institución)
        if ($evidencia['id_institucion'] != $this->auth->obtener_id_institucion()) {
            http_response_code(HTTP_FORBIDDEN);
            die('No tienes acceso a este archivo.');
        }

        $ruta = $evidencia['url_archivo'] ?? '';
        if (!$ruta || !file_exists($ruta)) {
            http_response_code(HTTP_NOT_FOUND);
            die('Archivo no encontrado en servidor.');
        }

        // Mostrar inline (para <img>) o forzar descarga si ?descargar=1
        $disposition = isset($_GET['descargar']) ? 'attachment' : 'inline';
        $mime = $evidencia['tipo_mime'] ?: 'image/jpeg';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
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
