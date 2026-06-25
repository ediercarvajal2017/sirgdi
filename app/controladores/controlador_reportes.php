<?php
// Controlador de Reportes (Fase 2)
// Endpoints: RF-06 (crear), RF-06 (listar), RF-09 (seguimiento público)

class ControladorReportes {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_sede;
    private $modelo_area;
    private $modelo_subarea;
    private $modelo_categoria;
    private $modelo_subcategoria;
    private $servicio_notificacion;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/servicios/servicio_notificacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_sede.php';
        require_once APP_PATH . '/modelos/modelo_area.php';
        require_once APP_PATH . '/modelos/modelo_subarea.php';
        require_once APP_PATH . '/modelos/modelo_categoria.php';
        require_once APP_PATH . '/modelos/modelo_subcategoria.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_sede = new ModeloSede();
        $this->modelo_area = new ModeloArea();
        $this->modelo_subarea = new ModeloSubarea();
        $this->modelo_categoria = new ModeloCategoria();
        $this->modelo_subcategoria = new ModeloSubcategoria();
        $this->servicio_notificacion = new ServicioNotificacion();
    }

    /**
     * RF-06: Formulario de creación de reporte (GET)
     */
    public function crear() {
        // Requerir autenticación
        $this->auth->requerir_autenticacion();

        // Requerir permiso
        $this->autorizacion->requerir_permiso(PERMISO_CREAR_REPORTE);

        $id_institucion = $this->auth->obtener_id_institucion();
        $csrf_token = Validacion::generar_csrf_token();

        // Obtener datos para form: sedes activas, categorías
        $sedes = $this->modelo_sede->listar_activas($id_institucion);
        $categorias = $this->modelo_categoria->listar_por_institucion($id_institucion);

        $datos = [
            'titulo' => 'Crear Reporte - SIRGDI',
            'csrf_token' => $csrf_token,
            'sedes' => $sedes,
            'categorias' => $categorias,
            'error' => $_GET['error'] ?? null,
        ];

        $this->renderizar_vista('reportes/vista_crear_reporte', $datos);
    }

    /**
     * RF-06: Procesar creación de reporte (POST)
     */
    public function procesar_crear() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir_crear('Método no permitido.', 'error');
            exit;
        }

        // Requerir autenticación
        $this->auth->requerir_autenticacion();

        // Requerir permiso
        $this->autorizacion->requerir_permiso(PERMISO_CREAR_REPORTE);

        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        // Obtener datos del formulario
        $id_sede = intval($_POST['id_sede'] ?? 0);
        $area_texto = trim($_POST['area'] ?? '');
        $id_subarea = intval($_POST['id_subarea'] ?? 0);
        $id_categoria = intval($_POST['id_categoria'] ?? 0);
        $id_subcategoria = intval($_POST['id_subcategoria'] ?? 0);
        $id_urgencia = intval($_POST['id_urgencia_declarada'] ?? URGENCIA_NO_URGENTE);
        $descripcion = Validacion::sanitizar_texto($_POST['descripcion_problema'] ?? '');

        // Validar entrada (RN-02)
        if (!$id_sede || !$area_texto || !$id_categoria || strlen($descripcion) < 10) {
            $this->redirigir_crear('Verifique que todos los campos obligatorios estén completos.', 'error');
            exit;
        }

        try {
            // Validar multitenant (RN-01)
            $sede = $this->modelo_sede->obtener_por_id($id_sede, $id_institucion);
            if (!$sede) {
                throw new Exception('Sede no válida para esta institución.');
            }

            // Preparar datos para crear reporte
            $datos_reporte = [
                'id_institucion' => $id_institucion,
                'id_sede' => $id_sede,
                'id_area' => null,
                'id_subarea' => null,
                'referencia_ubicacion_libre' => substr($area_texto, 0, 255),
                'id_categoria' => $id_categoria,
                'id_subcategoria' => $id_subcategoria ?: null,
                'id_urgencia_declarada' => $id_urgencia,
                'descripcion_problema' => $descripcion,
                'id_reportante' => $id_usuario,
                'nombre_reportante' => $this->auth->obtener_nombre_usuario(),
                'correo_reportante' => $this->auth->obtener_email_usuario(),
                'es_anonimo' => 0,
            ];

            // Crear reporte
            $id_reporte = $this->modelo_reporte->crear($datos_reporte);

            // Obtener datos del reporte creado
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);

            // Guardar evidencias fotográficas iniciales del reportante (etapa "Antes")
            $this->guardar_evidencias_iniciales($id_reporte, $id_institucion, $id_usuario);

            // Enviar notificación (RF-08)
            $this->servicio_notificacion->notificar_nuevo_reporte(
                $id_reporte,
                $id_institucion,
                $reporte['numero_ticket'],
                $descripcion
            );

            // Redirigir a detalle del reporte creado
            header('Location: ' . config('app.url_base') . '/?controlador=reportes&accion=detalle&id=' . $id_reporte . '&exito=1');
            exit;

        } catch (Exception $e) {
            $this->redirigir_crear($e->getMessage(), 'error');
            exit;
        }
    }

    /**
     * Guardar las fotos que el reportante adjunta al crear el reporte.
     * Se registran como evidencia de etapa "Antes" (estado inicial del daño).
     * No interrumpe la creación del reporte si una foto falla.
     */
    private function guardar_evidencias_iniciales($id_reporte, $id_institucion, $id_usuario = null) {
        if (empty($_FILES['fotos']) || !isset($_FILES['fotos']['tmp_name']) || !is_array($_FILES['fotos']['tmp_name'])) {
            return;
        }

        try {
            require_once APP_PATH . '/servicios/servicio_archivos.php';
            require_once APP_PATH . '/modelos/modelo_evidencia.php';
            $servicio_archivos = new ServicioArchivos();
            $modelo_evidencia = new ModeloEvidencia();

            $total = count($_FILES['fotos']['tmp_name']);
            $max = 5; // máximo 5 fotos
            for ($i = 0; $i < $total && $i < $max; $i++) {
                if (($_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $resultado = $servicio_archivos->procesar_foto(
                    $_FILES['fotos']['tmp_name'][$i],
                    $_FILES['fotos']['name'][$i]
                );
                if (!empty($resultado['exito'])) {
                    $modelo_evidencia->crear([
                        'id_reporte' => $id_reporte,
                        'id_institucion' => $id_institucion,
                        'etapa_evidencia' => 'reportante',
                        'url_archivo' => $resultado['ruta'],
                        'nombre_archivo_original' => $resultado['nombre_original'],
                        'tipo_mime' => 'image/jpeg',
                        'tamanio_bytes' => $resultado['tamaño_bytes'],
                        'descripcion' => 'Evidencia adjuntada por el reportante',
                        'cargada_por' => $id_usuario,
                    ]);
                }
            }
        } catch (Exception $e) {
            // No abortar la creación del reporte si falla el guardado de fotos
            error_log('Evidencias iniciales: ' . $e->getMessage());
        }
    }

    /**
     * Cargar un reporte verificando que pertenece al usuario (o que puede ver todos).
     */
    private function obtener_reporte_propio($id_reporte) {
        $id_institucion = $this->auth->obtener_id_institucion();
        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            throw new Exception('Reporte no encontrado.');
        }
        // Si no puede ver todos los reportes, debe ser el reportante dueño
        if (!$this->autorizacion->verificar_permiso(PERMISO_VER_TODOS_REPORTES)
            && intval($reporte['id_reportante']) !== intval($this->auth->obtener_id_usuario())) {
            throw new Exception('No tienes permiso sobre este reporte.');
        }
        return $reporte;
    }

    /**
     * Formulario de edición de reporte (GET). Solo en estado Registrado.
     */
    public function editar() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_alguno_permiso(PERMISO_VER_REPORTES_PROPIOS, PERMISO_VER_TODOS_REPORTES);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        try {
            $reporte = $this->obtener_reporte_propio($id_reporte);

            if (intval($reporte['id_estado']) !== ESTADO_REGISTRADO) {
                throw new Exception('Solo se puede editar un reporte mientras está en estado "Registrado".');
            }

            $sedes = $this->modelo_sede->listar_activas($id_institucion);
            $categorias = $this->modelo_categoria->listar_por_institucion($id_institucion);
            $subcategorias = $reporte['id_categoria']
                ? $this->modelo_subcategoria->listar_por_categoria($reporte['id_categoria'], $id_institucion)
                : [];

            $datos = [
                'titulo' => 'Editar Reporte - SIRGDI',
                'csrf_token' => Validacion::generar_csrf_token(),
                'reporte' => $reporte,
                'sedes' => $sedes,
                'categorias' => $categorias,
                'subcategorias' => $subcategorias,
            ];
            $this->renderizar_vista('reportes/vista_editar_reporte', $datos);

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=reportes&accion=listar&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Procesar edición de reporte (POST). Solo en estado Registrado.
     */
    public function procesar_editar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_alguno_permiso(PERMISO_VER_REPORTES_PROPIOS, PERMISO_VER_TODOS_REPORTES);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        try {
            $reporte = $this->obtener_reporte_propio($id_reporte);

            if (intval($reporte['id_estado']) !== ESTADO_REGISTRADO) {
                throw new Exception('Solo se puede editar un reporte mientras está en estado "Registrado".');
            }

            $id_sede = intval($_POST['id_sede'] ?? 0);
            $area_texto = trim($_POST['area'] ?? '');
            $id_categoria = intval($_POST['id_categoria'] ?? 0);
            $id_subcategoria = intval($_POST['id_subcategoria'] ?? 0);
            $id_urgencia = intval($_POST['id_urgencia_declarada'] ?? URGENCIA_NO_URGENTE);
            $descripcion = Validacion::sanitizar_texto($_POST['descripcion_problema'] ?? '');

            if (!$id_sede || !$area_texto || !$id_categoria || strlen($descripcion) < 10) {
                throw new Exception('Verifique que todos los campos obligatorios estén completos (descripción mín. 10 caracteres).');
            }

            // Validar sede de la institución (RN-01)
            if (!$this->modelo_sede->obtener_por_id($id_sede, $id_institucion)) {
                throw new Exception('Sede no válida para esta institución.');
            }

            // Recalcular urgencia: si la categoría es crítica, escala a Urgente (RN-06)
            $urgencia_calculada = $this->modelo_categoria->es_critica($id_categoria, $id_institucion)
                ? URGENCIA_URGENTE
                : $id_urgencia;

            $this->modelo_reporte->actualizar($id_reporte, $id_institucion, [
                'id_sede' => $id_sede,
                'referencia_ubicacion_libre' => substr($area_texto, 0, 255),
                'id_categoria' => $id_categoria,
                'id_subcategoria' => $id_subcategoria ?: null,
                'id_urgencia_declarada' => $id_urgencia,
                'id_urgencia_calculada' => $urgencia_calculada,
                'descripcion_problema' => $descripcion,
            ]);

            $_SESSION['exito'] = 'Reporte actualizado correctamente.';
            header('Location: ' . config('app.url_base') . '/?controlador=reportes&accion=listar&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=reportes&accion=editar&id=' . $id_reporte . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Eliminar un reporte propio (POST). Solo en estado Registrado.
     */
    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_alguno_permiso(PERMISO_VER_REPORTES_PROPIOS, PERMISO_VER_TODOS_REPORTES);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        try {
            $reporte = $this->obtener_reporte_propio($id_reporte);

            if (intval($reporte['id_estado']) !== ESTADO_REGISTRADO) {
                throw new Exception('Solo se puede eliminar un reporte mientras está en estado "Registrado". Ya está en gestión.');
            }

            // Elimina el reporte y sus registros relacionados en cascada
            $archivos = $this->modelo_reporte->eliminar($id_reporte, $id_institucion);
            foreach ((array) $archivos as $ruta) {
                if ($ruta && file_exists($ruta)) {
                    @unlink($ruta);
                }
            }

            $_SESSION['exito'] = 'Reporte eliminado correctamente.';
            header('Location: ' . config('app.url_base') . '/?controlador=reportes&accion=listar&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=reportes&accion=listar&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * RF-09: Seguimiento público (sin autenticación, token-based)
     * Accessible desde: http://localhost/reporte_danos/?controlador=reportes&accion=seguimiento&token=UUID
     */
    public function seguimiento() {
        $token = $_GET['token'] ?? null;

        if (!$token || !Validacion::validar_uuid($token)) {
            http_response_code(HTTP_NOT_FOUND);
            die('Token de seguimiento inválido.');
        }

        // Obtener reporte por token (sin autenticación)
        $reporte = $this->modelo_reporte->obtener_por_token_seguimiento($token);

        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte no encontrado.');
        }

        // Obtener información relacionada
        $sede = $this->modelo_sede->obtener_por_id($reporte['id_sede'], $reporte['id_institucion']);
        $categoria = $this->modelo_categoria->obtener_por_id($reporte['id_categoria'], $reporte['id_institucion']);

        $datos = [
            'titulo' => 'Seguimiento de Reporte - SIRGDI',
            'reporte' => $reporte,
            'sede' => $sede,
            'categoria' => $categoria,
        ];

        $this->renderizar_vista('reportes/vista_seguimiento_publico', $datos);
    }

    /**
     * Listar reportes (GET)
     */
    public function listar() {
        // Requerir autenticación
        $this->auth->requerir_autenticacion();

        // Requerir permiso
        $this->autorizacion->requerir_alguno_permiso(PERMISO_VER_REPORTES_PROPIOS, PERMISO_VER_TODOS_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        // Obtener filtros
        $filtros = [
            'id_estado' => intval($_GET['estado'] ?? 0) ?: null,
            'id_categoria' => intval($_GET['categoria'] ?? 0) ?: null,
        ];

        // Si no tiene permiso para ver todos, solo ver propios
        if (!$this->autorizacion->verificar_permiso(PERMISO_VER_TODOS_REPORTES)) {
            $filtros['id_reportante'] = $id_usuario;
        }

        // Paginación
        $pagina = intval($_GET['pagina'] ?? 1);
        $limite = 50;
        $offset = ($pagina - 1) * $limite;

        // Obtener reportes
        $reportes = $this->modelo_reporte->listar_por_institucion($id_institucion, $filtros, $limite, $offset);

        $datos = [
            'titulo' => 'Mis Reportes - SIRGDI',
            'reportes' => $reportes,
            'pagina' => $pagina,
            'filtros' => $filtros,
        ];

        $this->renderizar_vista('reportes/vista_listar_reportes', $datos);
    }

    /**
     * Detalle del reporte (GET)
     */
    public function detalle() {
        // Requerir autenticación
        $this->auth->requerir_autenticacion();

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de reporte requerido.');
        }

        // Obtener reporte (con filtro multitenant)
        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);

        if (!$reporte) {
            http_response_code(HTTP_NOT_FOUND);
            die('Reporte no encontrado.');
        }

        // Validar acceso (solo el reportante o usuarios con permiso)
        $id_usuario = $this->auth->obtener_id_usuario();
        if ($reporte['id_reportante'] != $id_usuario && !$this->autorizacion->verificar_permiso(PERMISO_VER_TODOS_REPORTES)) {
            http_response_code(HTTP_FORBIDDEN);
            die(ERROR_ACCESO_DENEGADO);
        }

        // Cargar evidencias fotográficas y el informe de intervención
        require_once APP_PATH . '/modelos/modelo_evidencia.php';
        require_once APP_PATH . '/modelos/modelo_intervension.php';
        $modelo_evidencia = new ModeloEvidencia();
        $modelo_intervension = new ModeloIntervension();

        $evidencias = $modelo_evidencia->listar_por_reporte($id_reporte, $id_institucion);
        $intervencion = $modelo_intervension->obtener_por_reporte($id_reporte, $id_institucion);

        // Nombres legibles de ubicación y clasificación (en vez de IDs)
        $sede = $reporte['id_sede'] ? $this->modelo_sede->obtener_por_id($reporte['id_sede'], $id_institucion) : null;
        $categoria = $reporte['id_categoria'] ? $this->modelo_categoria->obtener_por_id($reporte['id_categoria'], $id_institucion) : null;
        $subcategoria = !empty($reporte['id_subcategoria'])
            ? $this->modelo_subcategoria->obtener_por_id($reporte['id_subcategoria'], $id_institucion)
            : null;

        $datos = [
            'titulo' => 'Detalle de Reporte - SIRGDI',
            'reporte' => $reporte,
            'evidencias' => $evidencias,
            'intervencion' => $intervencion,
            'sede' => $sede,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'from' => $_GET['from'] ?? '',
        ];

        $this->renderizar_vista('reportes/vista_detalle_reporte', $datos);
    }

    /**
     * Cargar áreas por sede (AJAX)
     */
    public function cargar_areas_json() {
        $this->auth->requerir_autenticacion();

        $id_sede = intval($_POST['id_sede'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_sede) {
            http_response_code(HTTP_BAD_REQUEST);
            header('Content-Type: application/json');
            die('{"error":"id_sede requerido"}');
        }

        // Validar que la sede pertenece a la institución
        $sede = $this->modelo_sede->obtener_por_id($id_sede, $id_institucion);
        if (!$sede) {
            http_response_code(HTTP_FORBIDDEN);
            header('Content-Type: application/json');
            die('{"error":"Sede no válida"}');
        }

        // Obtener áreas
        $areas = $this->modelo_area->listar_por_sede($id_sede, $id_institucion);

        header('Content-Type: application/json');
        echo json_encode($areas);
    }

    /**
     * Cargar subareas por área (AJAX)
     */
    public function cargar_subareas_json() {
        $this->auth->requerir_autenticacion();

        $id_area = intval($_POST['id_area'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_area) {
            http_response_code(HTTP_BAD_REQUEST);
            header('Content-Type: application/json');
            die('{"error":"id_area requerido"}');
        }

        // Validar que el área pertenece a la institución
        $area = $this->modelo_area->obtener_por_id($id_area, $id_institucion);
        if (!$area) {
            http_response_code(HTTP_FORBIDDEN);
            header('Content-Type: application/json');
            die('{"error":"Área no válida"}');
        }

        // Obtener subareas
        $subareas = $this->modelo_subarea->listar_por_area($id_area, $id_institucion);

        header('Content-Type: application/json');
        echo json_encode($subareas);
    }

    /**
     * Cargar subcategorías por categoría (AJAX)
     */
    public function cargar_subcategorias_json() {
        $this->auth->requerir_autenticacion();

        $id_categoria = intval($_POST['id_categoria'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_categoria) {
            http_response_code(HTTP_BAD_REQUEST);
            header('Content-Type: application/json');
            die('{"error":"id_categoria requerido"}');
        }

        // Validar que la categoría pertenece a la institución
        $categoria = $this->modelo_categoria->obtener_por_id($id_categoria, $id_institucion);
        if (!$categoria) {
            http_response_code(HTTP_FORBIDDEN);
            header('Content-Type: application/json');
            die('{"error":"Categoría no válida"}');
        }

        // Obtener subcategorías
        $subcategorias = $this->modelo_subcategoria->listar_por_categoria($id_categoria, $id_institucion);

        header('Content-Type: application/json');
        echo json_encode($subcategorias);
    }

    /**
     * Cargar subcategorías para el formulario público de invitado (AJAX, sin auth).
     * Requiere id_categoria e id_institucion en POST.
     */
    public function cargar_subcategorias_publico_json() {
        $id_categoria   = intval($_POST['id_categoria']   ?? 0);
        $id_institucion = intval($_POST['id_institucion'] ?? 0);

        header('Content-Type: application/json');

        if (!$id_categoria || !$id_institucion) {
            http_response_code(HTTP_BAD_REQUEST);
            die('{"error":"id_categoria e id_institucion requeridos"}');
        }

        $categoria = $this->modelo_categoria->obtener_por_id($id_categoria, $id_institucion);
        if (!$categoria) {
            http_response_code(HTTP_FORBIDDEN);
            die('{"error":"Categoría no válida"}');
        }

        $subcategorias = $this->modelo_subcategoria->listar_por_categoria($id_categoria, $id_institucion);
        echo json_encode($subcategorias);
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
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

    private function redirigir_crear($error_msg = '', $tipo = 'error') {
        $url = config('app.url_base') . '/?controlador=reportes&accion=crear';
        if ($error_msg) {
            $url .= '&' . $tipo . '=' . urlencode($error_msg);
        }
        header('Location: ' . $url);
    }

    // ----------------------------------------------------------------
    // REPORTES DE INVITADOS (sin registro de usuario)
    // ----------------------------------------------------------------

    /**
     * Formulario público para crear reporte sin cuenta.
     * URL: ?controlador=reportes&accion=crear_invitado&inst={id_institucion}
     */
    public function crear_invitado() {
        $id_institucion = intval($_GET['inst'] ?? 0);
        if (!$id_institucion) {
            http_response_code(HTTP_BAD_REQUEST);
            die('URL inválida. Solicite el enlace correcto al administrador del sistema.');
        }

        require_once APP_PATH . '/modelos/modelo_institucion.php';
        $modelo_inst = new ModeloInstitucion();
        $institucion = $modelo_inst->obtener_por_id($id_institucion);

        if (!$institucion || !$institucion['es_activa']) {
            http_response_code(HTTP_NOT_FOUND);
            die('Institución no encontrada o inactiva.');
        }

        $csrf_token = Validacion::generar_csrf_token();
        $sedes = $this->modelo_sede->listar_activas($id_institucion);
        $categorias = $this->modelo_categoria->listar_por_institucion($id_institucion);

        $datos = [
            'titulo' => 'Reportar Daño — ' . $institucion['nombre'],
            'csrf_token' => $csrf_token,
            'sedes' => $sedes,
            'categorias' => $categorias,
            'institucion' => $institucion,
            'id_institucion' => $id_institucion,
            'error' => $_GET['error'] ?? null,
        ];

        $this->renderizar_vista('reportes/vista_crear_reporte_invitado', $datos);
    }

    /**
     * Procesar creación de reporte de invitado (POST).
     */
    public function procesar_crear_invitado() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $id_institucion = intval($_POST['id_institucion'] ?? 0);
        if (!$id_institucion) {
            http_response_code(HTTP_BAD_REQUEST);
            die('Institución requerida.');
        }

        $nombres   = Validacion::sanitizar_texto($_POST['nombres']   ?? '');
        $apellidos = Validacion::sanitizar_texto($_POST['apellidos']  ?? '');
        $correo    = Validacion::sanitizar_texto($_POST['correo']     ?? '');
        $telefono  = Validacion::sanitizar_texto($_POST['telefono']   ?? '');

        $id_sede         = intval($_POST['id_sede']              ?? 0);
        $area_texto      = trim($_POST['area']                   ?? '');
        $id_categoria    = intval($_POST['id_categoria']         ?? 0);
        $id_subcategoria = intval($_POST['id_subcategoria']      ?? 0);
        $id_urgencia     = intval($_POST['id_urgencia_declarada'] ?? URGENCIA_NO_URGENTE);
        $descripcion     = Validacion::sanitizar_texto($_POST['descripcion_problema'] ?? '');

        if (!$nombres || !$apellidos) {
            $this->redirigir_invitado($id_institucion, 'Ingrese sus nombres y apellidos completos.');
            exit;
        }
        if (!$id_sede || !$area_texto || !$id_categoria || strlen($descripcion) < 10) {
            $this->redirigir_invitado($id_institucion, 'Complete todos los campos obligatorios del reporte.');
            exit;
        }

        try {
            $sede = $this->modelo_sede->obtener_por_id($id_sede, $id_institucion);
            if (!$sede) {
                throw new Exception('Sede no válida para esta institución.');
            }

            $datos_reporte = [
                'id_institucion'            => $id_institucion,
                'id_sede'                   => $id_sede,
                'id_area'                   => null,
                'id_subarea'                => null,
                'referencia_ubicacion_libre'=> substr($area_texto, 0, 255),
                'id_categoria'              => $id_categoria,
                'id_subcategoria'           => $id_subcategoria ?: null,
                'id_urgencia_declarada'     => $id_urgencia,
                'descripcion_problema'      => $descripcion,
                'id_reportante'             => null,
                'nombre_reportante'         => trim($nombres . ' ' . $apellidos),
                'correo_reportante'         => $correo ?: null,
                'telefono_reportante'       => $telefono ?: null,
                'es_anonimo'                => 0,
            ];

            $id_reporte = $this->modelo_reporte->crear($datos_reporte);
            $reporte    = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);

            $this->guardar_evidencias_iniciales($id_reporte, $id_institucion, null);

            $this->servicio_notificacion->notificar_nuevo_reporte(
                $id_reporte,
                $id_institucion,
                $reporte['numero_ticket'],
                $descripcion
            );

            header('Location: ' . config('app.url_base')
                . '/?controlador=reportes&accion=seguimiento&token='
                . urlencode($reporte['token_seguimiento_publico'])
                . '&nuevo=1');
            exit;

        } catch (Exception $e) {
            $this->redirigir_invitado($id_institucion, $e->getMessage());
            exit;
        }
    }

    private function redirigir_invitado($id_institucion, $msg = '') {
        $url = config('app.url_base')
            . '/?controlador=reportes&accion=crear_invitado&inst=' . intval($id_institucion);
        if ($msg) {
            $url .= '&error=' . urlencode($msg);
        }
        header('Location: ' . $url);
    }
}
