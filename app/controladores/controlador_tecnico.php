<?php
// Controlador Técnico (Workflow de técnico)
// RF-16 (Mis asignaciones), RF-17 (Intervención), RF-18 (Evidencia), RF-20 (Cierre técnico)

class ControladorTecnico {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_intervension;
    private $modelo_evidencia;
    private $modelo_avance;
    private $servicio_archivos;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_intervension.php';
        require_once APP_PATH . '/modelos/modelo_evidencia.php';
        require_once APP_PATH . '/modelos/modelo_avance.php';
        require_once APP_PATH . '/servicios/servicio_archivos.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_intervension = new ModeloIntervension();
        $this->modelo_evidencia = new ModeloEvidencia();
        $this->modelo_avance = new ModeloAvance();
        $this->servicio_archivos = new ServicioArchivos();
    }

    // ───────────────────────────────────────────────────────────────────────
    // HOJA DE TRABAJO: una sola pantalla por ticket que el técnico completa de
    // forma progresiva (iniciar → evidencias → notas → informe → solucionado).
    // ───────────────────────────────────────────────────────────────────────

    /**
     * Abre la hoja de trabajo de un reporte. Si el técnico aún no la había
     * iniciado, crea la intervención con solo la fecha de inicio: el informe se
     * completa después, a medida que avanza el trabajo.
     */
    public function hoja_trabajo() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        $reporte = $id_reporte ? $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion) : null;
        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte no encontrado.');
        }
        if ($reporte['id_tecnico_asignado'] != $id_usuario) {
            http_response_code(HTTP_FORBIDDEN);
            die('No eres el técnico asignado a este reporte.');
        }

        // Se captura antes de mutar el estado: la vista lo usa para seguir avisando
        // "el gestor devolvió este reporte" aunque, abajo, ya se haya reabierto a
        // En Proceso para permitir que el técnico vuelva a marcarlo como solucionado.
        $estaba_devuelto = ((int)$reporte['id_estado'] === ESTADO_DEVUELTO);

        $intervension = $this->modelo_intervension->obtener_por_reporte($id_reporte, $id_institucion);
        if (!$intervension) {
            $id_informe = $this->modelo_intervension->crear([
                'id_reporte' => $id_reporte,
                'id_institucion' => $id_institucion,
                'id_usuario_tecnico' => $id_usuario,
                'descripcion_actividades' => '',
                'solucion_implementada' => '',
                'fecha_hora_inicio' => date('Y-m-d H:i:s'),
            ]);
            $intervension = $this->modelo_intervension->obtener_por_id($id_informe, $id_institucion);
            ServicioAuditoria::registrar('iniciar_intervencion', 'reporte', $id_reporte, null,
                ['ticket' => $reporte['numero_ticket'], 'id_informe' => (int)$id_informe]);
        }

        // Reabrir el trabajo: si llega Asignado/Registrado (primera vez) o Devuelto
        // (el gestor pidió corregir), pasa a En Proceso. Antes esto vivía dentro del
        // "if (!$intervension)" de arriba, así que un ticket Devuelto —que ya tiene
        // intervención creada desde el intento anterior— nunca se reabría y el
        // técnico quedaba sin forma de volver a marcarlo como solucionado.
        if (in_array($reporte['id_estado'], [ESTADO_ASIGNADO, ESTADO_REGISTRADO, ESTADO_DEVUELTO])) {
            $this->modelo_reporte->cambiar_estado($id_reporte, $id_institucion, ESTADO_EN_PROCESO,
                $estaba_devuelto ? 'Técnico retoma el reporte devuelto' : 'Intervención iniciada por técnico',
                $id_usuario);
            $reporte['id_estado'] = ESTADO_EN_PROCESO;
        }

        $reporte_detalle = $this->modelo_reporte->obtener_detallado($id_reporte, $id_institucion);
        $evidencias = $this->modelo_evidencia->listar_por_reporte($id_reporte, $id_institucion);
        $completitud = $this->modelo_evidencia->verificar_completitud($id_reporte, $id_institucion);
        $avances = $this->modelo_avance->listar_por_reporte($id_reporte, $id_institucion);
        $informe_faltante = ModeloIntervension::campos_faltantes($intervension);

        $this->renderizar_vista('tecnico/vista_hoja_trabajo', [
            'titulo' => 'Hoja de trabajo ' . $reporte['numero_ticket'] . ' - ' . config('app.app_name'),
            'reporte' => $reporte,
            'detalle' => $reporte_detalle,
            'intervension' => $intervension,
            'evidencias' => $evidencias,
            'completitud' => $completitud,
            'avances' => $avances,
            'informe_faltante' => $informe_faltante,
            'ya_solucionado' => in_array($reporte['id_estado'], [ESTADO_SOLUCIONADO, ESTADO_EN_VALIDACION, ESTADO_CERRADO]),
            'estaba_devuelto' => $estaba_devuelto,
            'csrf_token' => Validacion::generar_csrf_token(),
        ]);
    }

    /** Guarda el informe técnico (parcial o completo). Se puede llamar tantas veces como haga falta. */
    public function guardar_informe() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();
        $volver = config('app.url_base') . '/?controlador=tecnico&accion=hoja_trabajo&id=' . $id_reporte;

        try {
            $intervension = $this->intervension_del_tecnico($id_reporte, $id_usuario, $id_institucion);

            $materiales_json = null;
            $materiales_texto = trim($_POST['materiales'] ?? '');
            if ($materiales_texto !== '') {
                $lineas = array_filter(array_map('trim', explode("\n", $materiales_texto)));
                $materiales_json = json_encode(array_map(fn($l) => ['nombre' => $l], array_values($lineas)), JSON_UNESCAPED_UNICODE);
            }
            $costo = ($_POST['costo_estimado'] ?? '') !== '' ? floatval($_POST['costo_estimado']) : null;

            $this->modelo_intervension->actualizar($intervension['id_informe'], $id_institucion, [
                'descripcion_actividades' => Validacion::sanitizar_texto($_POST['descripcion_actividades'] ?? ''),
                'causa_raiz' => Validacion::sanitizar_texto($_POST['causa_raiz'] ?? '') ?: null,
                'solucion_implementada' => Validacion::sanitizar_texto($_POST['solucion_implementada'] ?? ''),
                'materiales_utilizados_json' => $materiales_json,
                'costo_estimado' => $costo,
            ]);

            ServicioAuditoria::registrar('guardar_informe', 'informe_intervencion', $intervension['id_informe'],
                ['descripcion' => mb_substr($intervension['descripcion_actividades'] ?? '', 0, 200), 'solucion' => mb_substr($intervension['solucion_implementada'] ?? '', 0, 200)],
                ['descripcion' => mb_substr($_POST['descripcion_actividades'] ?? '', 0, 200), 'solucion' => mb_substr($_POST['solucion_implementada'] ?? '', 0, 200), 'id_reporte' => $id_reporte]);
            header('Location: ' . $volver . '&exito=informe');
        } catch (Exception $e) {
            header('Location: ' . $volver . '&error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /** Registra una nota de avance del técnico (visible también para el reportante). */
    public function agregar_avance() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_TECNICO);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $texto = Validacion::sanitizar_texto($_POST['texto'] ?? '');
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();
        $volver = config('app.url_base') . '/?controlador=tecnico&accion=hoja_trabajo&id=' . $id_reporte;

        try {
            $intervension = $this->intervension_del_tecnico($id_reporte, $id_usuario, $id_institucion);
            if (mb_strlen($texto) < 5) {
                throw new Exception('La nota debe tener al menos 5 caracteres.');
            }
            $this->modelo_avance->crear([
                'id_reporte' => $id_reporte,
                'id_institucion' => $id_institucion,
                'id_informe' => $intervension['id_informe'],
                'id_usuario_autor' => $id_usuario,
                'texto' => mb_substr($texto, 0, 500),
            ]);
            ServicioAuditoria::registrar('agregar_avance', 'reporte', $id_reporte, null, ['nota' => mb_substr($texto, 0, 200)]);
            header('Location: ' . $volver . '&exito=avance');
        } catch (Exception $e) {
            header('Location: ' . $volver . '&error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /** Intervención del reporte, validando que quien la pide es el técnico asignado. */
    private function intervension_del_tecnico($id_reporte, $id_usuario, $id_institucion) {
        $reporte = $id_reporte ? $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion) : null;
        if (!$reporte || $reporte['id_tecnico_asignado'] != $id_usuario) {
            throw new Exception('No tienes acceso a este reporte.');
        }
        $intervension = $this->modelo_intervension->obtener_por_reporte($id_reporte, $id_institucion);
        if (!$intervension) {
            throw new Exception('Primero debes iniciar la hoja de trabajo.');
        }
        return $intervension;
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
            'titulo' => 'Mis Asignaciones - ' . config('app.app_name'),
            'reportes' => $reportes,
        ];

        $this->renderizar_vista('tecnico/vista_mis_asignaciones', $datos);
    }

    /**
     * RF-17 (compatibilidad): la intervención ya no se crea con un formulario
     * previo; la hoja de trabajo la inicia sola. Se conserva la ruta para los
     * enlaces existentes.
     */
    public function crear_intervension() {
        $id_reporte = intval($_GET['id'] ?? $_POST['id_reporte'] ?? 0);
        header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=hoja_trabajo&id=' . $id_reporte);
        exit;
    }
    /**
     * RF-18: Cargar evidencia fotográfica (3 etapas)
     * RN-03: Mínimo 1 foto por etapa antes de cerrar
     */
    public function cargar_evidencia() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->procesar_cargar_evidencia();
        }
        // Compatibilidad: la pantalla de evidencias ahora forma parte de la hoja de trabajo.
        $this->auth->requerir_autenticacion();
        $id_intervension = intval($_GET['id_intervension'] ?? 0);
        $intervension = $id_intervension
            ? $this->modelo_intervension->obtener_por_id($id_intervension, $this->auth->obtener_id_institucion())
            : null;
        $destino = $intervension
            ? '&accion=hoja_trabajo&id=' . $intervension['id_reporte']
            : '&accion=mis_asignaciones';
        header('Location: ' . config('app.url_base') . '/?controlador=tecnico' . $destino);
        exit;
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

            // Límite de fotos por etapa (evita subidas ilimitadas por error o abuso)
            $fotos_en_etapa = $this->modelo_evidencia->contar_por_etapa($intervension['id_reporte'], $id_institucion, $etapa);
            if ($fotos_en_etapa >= 5) {
                throw new Exception('Ya se alcanzó el máximo de 5 fotos para esta etapa.');
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

            ServicioAuditoria::registrar('cargar_evidencia', 'reporte', $intervension['id_reporte'], null,
                ['etapa' => $etapa, 'archivo' => $resultado_archivo['nombre_original']]);
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=hoja_trabajo&id=' . $intervension['id_reporte'] . '&exito=foto');
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

            // El informe técnico también debe estar completo antes de cerrar
            $intervension = $this->modelo_intervension->obtener_por_reporte($id_reporte, $id_institucion);
            if (!$intervension) {
                throw new Exception('Primero debes iniciar la hoja de trabajo.');
            }
            $faltan = ModeloIntervension::campos_faltantes($intervension);
            if ($faltan) {
                throw new Exception('Informe técnico incompleto. Falta: ' . implode('; ', $faltan) . '.');
            }

            // Cierra la intervención con la hora real de finalización
            if (empty($intervension['fecha_hora_fin'])) {
                $this->modelo_intervension->marcar_finalizada($intervension['id_informe'], $id_institucion);
            }

            // Cambiar estado a SOLUCIONADO (requiere validación de gestor)
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                ESTADO_SOLUCIONADO,
                'Técnico marca como solucionado',
                $id_usuario
            );

            // RN-10: mientras espera revisión del gestor no corre por cuenta del
            // técnico, así que el reloj del SLA se pausa aquí. Se reanuda en
            // ControladorCierre::procesar_validar_solucion() si el gestor lo rechaza
            // y vuelve a manos del técnico (antes esa reanudación existía pero nunca
            // pausaba nada primero, así que el SLA en realidad nunca se detenía).
            $this->modelo_reporte->pausar_sla($id_reporte, $id_institucion);

            // Hito: avisa al gestor (validación) y al reportante (avance de su ticket)
            require_once APP_PATH . '/servicios/servicio_notificacion.php';
            $servicio_notificacion = new ServicioNotificacion();
            $servicio_notificacion->notificar_reporte_solucionado(
                $id_reporte,
                $id_institucion,
                $reporte['numero_ticket']
            );

            ServicioAuditoria::registrar('marcar_solucionado', 'reporte', $id_reporte,
                ['id_estado' => $reporte['id_estado']], ['id_estado' => ESTADO_SOLUCIONADO, 'ticket' => $reporte['numero_ticket']]);
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=mis_asignaciones&exito=1');
            exit;

        } catch (Exception $e) {
            // Volver a la hoja de trabajo, que es donde el técnico puede corregir lo que falta
            header('Location: ' . config('app.url_base') . '/?controlador=tecnico&accion=hoja_trabajo&id=' . $id_reporte . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Descargar evidencia (con validación de seguridad)
     */
    public function descargar_evidencia() {
        $this->auth->requerir_autenticacion();

        $id_evidencia = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_evidencia) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de evidencia requerido.');
        }

        // RN-01: la institución siempre viene de la sesión, nunca de la URL.
        $evidencia = $this->modelo_evidencia->obtener_por_id($id_evidencia, $id_institucion);
        if (!$evidencia) {
            http_response_code(HTTP_NOT_FOUND);
            die('Evidencia no encontrada.');
        }

        // Validar acceso al reporte asociado (mismo criterio que ControladorReportes::detalle()):
        // solo el reportante, el técnico asignado, o quien tenga permiso para ver todos los reportes.
        $reporte = $this->modelo_reporte->obtener_por_id($evidencia['id_reporte'], $id_institucion);
        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte asociado no encontrado.');
        }

        $id_usuario = $this->auth->obtener_id_usuario();
        $tiene_acceso = $reporte['id_reportante'] == $id_usuario
            || $reporte['id_tecnico_asignado'] == $id_usuario
            || $this->autorizacion->verificar_permiso(PERMISO_VER_TODOS_REPORTES);

        if (!$tiene_acceso) {
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
        header('X-Content-Type-Options: nosniff');
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
