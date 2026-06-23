<?php
// Controlador de Gestión (Manager Dashboard)
// RF-10 (Kanban), RF-12 (Asignar), RF-13 (Comentarios)

class ControladorGestion {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_usuario;
    private $servicio_prioridad;
    private $modelo_sla;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_usuario.php';
        require_once APP_PATH . '/servicios/servicio_prioridad.php';
        require_once APP_PATH . '/modelos/modelo_sla.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_usuario = new ModeloUsuario();
        $this->servicio_prioridad = new ServicioPrioridad();
        $this->modelo_sla = new ModeloSLA();
    }

    /**
     * RF-10: Listar reportes en vista compacta
     */
    public function listar() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VER_TODOS_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Filtros
        $filtros = [];
        if (isset($_GET['estado']) && $_GET['estado'] !== '') {
            $filtros['id_estado'] = intval($_GET['estado']);
        }
        if (isset($_GET['urgencia']) && $_GET['urgencia'] !== '') {
            $filtros['id_urgencia_calculada'] = intval($_GET['urgencia']);
        }

        // Paginación
        $pagina = intval($_GET['pagina'] ?? 1);
        $limite = 50;
        $offset = ($pagina - 1) * $limite;

        // Obtener reportes
        $reportes = $this->modelo_reporte->listar_por_institucion($id_institucion, $filtros, $limite, $offset);

        $datos = [
            'titulo' => 'Gestión de Reportes - SIRGDI',
            'reportes' => $reportes,
            'pagina' => $pagina,
            'filtros' => $filtros,
        ];

        $this->renderizar_vista('gestion/vista_gestion_reportes_lista', $datos);
    }

    /**
     * RF-10: Dashboard Kanban - Vista por estados
     */
    public function kanban() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VER_TODOS_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Obtener reportes ordenados por prioridad
        $reportes_por_prioridad = $this->servicio_prioridad->listar_por_prioridad($id_institucion);

        // Agrupar por estado
        $columnas = [
            ESTADO_REGISTRADO => [],
            ESTADO_EN_PROCESO => [],
            ESTADO_DEVUELTO => [],
            ESTADO_SOLUCIONADO => [],
        ];

        $estados_nombres = [
            ESTADO_REGISTRADO => 'Registrado',
            ESTADO_EN_PROCESO => 'En Proceso',
            ESTADO_DEVUELTO => 'Devuelto',
            ESTADO_SOLUCIONADO => 'Solucionado',
        ];

        foreach ($reportes_por_prioridad as $item) {
            $estado = $item['reporte']['id_estado'];
            if (isset($columnas[$estado])) {
                $columnas[$estado][] = $item;
            }
        }

        // Estadísticas
        $stats = $this->servicio_prioridad->obtener_estadisticas_prioridad($id_institucion);

        // Colores de urgencia (mapeo id_urgencia => color hex)
        $colores_urgencia = [
            URGENCIA_NO_URGENTE => '#28A745',
            URGENCIA_MODERADO => '#FFC107',
            URGENCIA_IMPORTANTE => '#FD7E14',
            URGENCIA_URGENTE => '#DC3545',
        ];

        $datos = [
            'titulo' => 'Gestión de Reportes - SIRGDI',
            'columnas' => $columnas,
            'estados_nombres' => $estados_nombres,
            'stats' => $stats,
            'colores_urgencia' => $colores_urgencia,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('gestion/vista_kanban_gestion', $datos);
    }

    /**
     * RF-12: Asignar técnico a reporte
     */
    public function asignar_tecnico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_ASIGNAR_TECNICO);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_tecnico = intval($_POST['id_tecnico'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte || !$id_tecnico) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de reporte y técnico requeridos.');
        }

        try {
            // Validar que el reporte pertenece a la institución
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // Validar que el técnico existe y pertenece a la institución
            $tecnico = $this->modelo_usuario->obtener_por_id($id_tecnico, $id_institucion);
            if (!$tecnico) {
                throw new Exception('Técnico no encontrado.');
            }

            // Verificar que el técnico tiene rol de técnico (verificar en tabla usuario_rol)
            // TODO: Implementar validación de rol en usuario_rol
            if (!isset($tecnico['id_usuario'])) {
                throw new Exception('El usuario seleccionado no es válido.');
            }

            // Asignar
            $this->modelo_reporte->asignar_tecnico($id_reporte, $id_institucion, $id_tecnico);

            // Cambiar estado a "En Proceso"
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                ESTADO_EN_PROCESO,
                'Asignado a técnico: ' . htmlspecialchars($tecnico['nombre_completo']),
                $this->auth->obtener_id_usuario()
            );

            // Notificar al técnico
            require_once APP_PATH . '/servicios/servicio_notificacion.php';
            $servicio_notificacion = new ServicioNotificacion();
            $servicio_notificacion->notificar_asignacion_tecnico(
                $id_reporte,
                $id_institucion,
                $id_tecnico,
                $reporte['numero_ticket']
            );

            // Redirigir de vuelta al Kanban con mensaje de éxito
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Eliminar un reporte y todos sus registros relacionados (POST)
     */
    public function eliminar_reporte() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();
        $id_reporte = intval($_POST['id_reporte'] ?? 0);

        try {
            // Validar CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                throw new Exception('Token CSRF inválido.');
            }

            if (!$id_reporte) {
                throw new Exception('Reporte requerido.');
            }

            // Eliminar reporte + hijos (devuelve rutas de evidencia para borrar del disco)
            $archivos = $this->modelo_reporte->eliminar($id_reporte, $id_institucion);

            // Borrar archivos de evidencia del disco (si existen)
            foreach ($archivos as $ruta) {
                if ($ruta && file_exists($ruta)) {
                    @unlink($ruta);
                }
            }

            $_SESSION['exito'] = 'Reporte eliminado correctamente.';
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Cambiar estado de reporte (GET form)
     */
    public function cambiar_estado() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_REPORTES);

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

        $estados = [
            ESTADO_REGISTRADO => 'Registrado',
            ESTADO_EN_PROCESO => 'En Proceso',
            ESTADO_DEVUELTO => 'Devuelto',
            ESTADO_SOLUCIONADO => 'Solucionado',
            ESTADO_EN_VALIDACION => 'En Validación',
            ESTADO_CERRADO => 'Cerrado',
        ];

        $datos = [
            'titulo' => 'Cambiar Estado - SIRGDI',
            'reporte' => $reporte,
            'estados' => $estados,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('gestion/vista_cambiar_estado', $datos);
    }

    /**
     * Procesar cambio de estado (POST)
     */
    public function procesar_cambiar_estado() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            die('Método no permitido.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_REPORTES);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_estado_nuevo = intval($_POST['id_estado_nuevo'] ?? 0);
        $justificacion = Validacion::sanitizar_texto($_POST['justificacion'] ?? '');
        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        try {
            if (!$id_reporte || !$id_estado_nuevo) {
                throw new Exception('Reporte y estado nuevo requeridos.');
            }

            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // Cambiar estado
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                $id_estado_nuevo,
                $justificacion,
                $id_usuario
            );

            // Si vuelve a "En Proceso", reanudar SLA (RN-10)
            if ($id_estado_nuevo == ESTADO_EN_PROCESO && $reporte['fecha_pausa_sla']) {
                $this->modelo_reporte->reanudar_sla($id_reporte, $id_institucion);
            }

            // Si pasa a "Devuelto", pausar SLA (RN-10)
            if ($id_estado_nuevo == ESTADO_DEVUELTO) {
                $this->modelo_reporte->pausar_sla($id_reporte, $id_institucion);
            }

            // Redirigir
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Obtener técnicos disponibles (AJAX JSON)
     */
    public function obtener_tecnicos_json() {
        $this->auth->requerir_autenticacion();

        $id_institucion = $this->auth->obtener_id_institucion();

        // Obtener usuarios con rol de Técnico (vía usuario_rol)
        $sql = 'SELECT DISTINCT u.id_usuario, u.nombre_completo AS nombre, u.correo_electronico AS email
                FROM usuario u
                JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario AND ur.id_institucion = u.id_institucion
                JOIN rol r ON ur.id_rol = r.id_rol
                WHERE u.id_institucion = :id_institucion
                AND r.nombre_rol = :rol_tecnico
                AND u.activo = 1
                ORDER BY nombre ASC';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $tecnicos = $bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
            ':rol_tecnico' => 'Técnico',
        ]);

        header('Content-Type: application/json');
        echo json_encode($tecnicos);
    }

    /**
     * Obtener carga de trabajo de técnico (AJAX)
     */
    public function obtener_carga_tecnico_json() {
        $this->auth->requerir_autenticacion();

        $id_tecnico = intval($_POST['id_tecnico'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_tecnico) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID técnico requerido.');
        }

        // Contar reportes activos asignados al técnico
        $sql = 'SELECT COUNT(*) as total FROM reporte
                WHERE id_tecnico_asignado = :id_tecnico
                AND id_institucion = :id_institucion
                AND id_estado IN (' . ESTADO_EN_PROCESO . ',' . ESTADO_DEVUELTO . ')';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $resultado = $bd->obtener_uno($sql, [
            ':id_tecnico' => $id_tecnico,
            ':id_institucion' => $id_institucion,
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'reportes_activos' => intval($resultado['total'] ?? 0),
        ]);
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
}
