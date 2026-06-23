<?php
// Controlador Dashboard (RF-25: KPI Dashboard)
// Estadísticas en tiempo real para managers y rectors

class ControladorDashboard {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_encuesta;
    private $servicio_prioridad;
    private $modelo_sla;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_encuesta.php';
        require_once APP_PATH . '/servicios/servicio_prioridad.php';
        require_once APP_PATH . '/modelos/modelo_sla.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_encuesta = new ModeloEncuesta();
        $this->servicio_prioridad = new ServicioPrioridad();
        $this->modelo_sla = new ModeloSLA();
    }

    /**
     * RF-25: Página de Inicio - Información de la Institución
     */
    public function inicio() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VER_DASHBOARD);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Obtener información de la institución
        $sql = 'SELECT id_institucion, nombre, logo_ruta, es_activa, fecha_creacion
                FROM institucion WHERE id_institucion = :id_inst';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $institucion = $bd->obtener_uno($sql, [':id_inst' => $id_institucion]);

        // Construir la URL pública del logo a partir del archivo almacenado
        if (!empty($institucion['logo_ruta'])) {
            $institucion['logo_url'] = config('app.url_base') . '/almacenamiento/logos/' . $institucion['logo_ruta'];
        }

        $datos = [
            'titulo' => 'Inicio - ' . ($institucion['nombre'] ?? 'SIRGDI'),
            'institucion' => $institucion,
        ];

        $this->renderizar_vista('dashboard/vista_inicio', $datos);
    }

    /**
     * Obtener KPIs principales
     */
    private function obtener_kpis($id_institucion) {
        $sql_total = 'SELECT COUNT(*) as total FROM reporte WHERE id_institucion = :id_inst';
        $sql_activos = 'SELECT COUNT(*) as total FROM reporte WHERE id_institucion = :id_inst AND id_estado IN (1,2,3)';
        $sql_cerrados = 'SELECT COUNT(*) as total FROM reporte WHERE id_institucion = :id_inst AND id_estado = 7';
        $sql_urgentes = 'SELECT COUNT(*) as total FROM reporte WHERE id_institucion = :id_inst AND id_urgencia_calculada = 4';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();

        return [
            'total_reportes' => intval($bd->obtener_uno($sql_total, [':id_inst' => $id_institucion])['total'] ?? 0),
            'reportes_activos' => intval($bd->obtener_uno($sql_activos, [':id_inst' => $id_institucion])['total'] ?? 0),
            'reportes_cerrados' => intval($bd->obtener_uno($sql_cerrados, [':id_inst' => $id_institucion])['total'] ?? 0),
            'reportes_urgentes' => intval($bd->obtener_uno($sql_urgentes, [':id_inst' => $id_institucion])['total'] ?? 0),
            'tasa_cierre' => $this->calcular_tasa_cierre($id_institucion),
        ];
    }

    /**
     * Obtener reportes agrupados por estado
     */
    private function obtener_reportes_por_estado($id_institucion) {
        $sql = 'SELECT id_estado, COUNT(*) as cantidad
                FROM reporte
                WHERE id_institucion = :id_inst
                GROUP BY id_estado
                ORDER BY id_estado';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $datos = $bd->obtener_todos($sql, [':id_inst' => $id_institucion]);

        $estados = [
            1 => 'Registrado',
            2 => 'En Proceso',
            3 => 'Devuelto',
            4 => 'Solucionado',
            5 => 'En Validación',
            6 => 'Cerrado',
        ];

        $resultado = [];
        foreach ($datos as $row) {
            $resultado[] = [
                'estado' => $estados[$row['id_estado']] ?? 'Otro',
                'cantidad' => $row['cantidad'],
            ];
        }

        return $resultado;
    }

    /**
     * Obtener reportes por urgencia
     */
    private function obtener_reportes_por_urgencia($id_institucion) {
        $sql = 'SELECT id_urgencia_calculada, COUNT(*) as cantidad
                FROM reporte
                WHERE id_institucion = :id_inst
                GROUP BY id_urgencia_calculada
                ORDER BY id_urgencia_calculada';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $datos = $bd->obtener_todos($sql, [':id_inst' => $id_institucion]);

        $urgencias = [
            1 => 'No Urgente',
            2 => 'Moderado',
            3 => 'Importante',
            4 => 'Urgente',
        ];

        $resultado = [];
        foreach ($datos as $row) {
            $resultado[] = [
                'urgencia' => $urgencias[$row['id_urgencia_calculada']],
                'cantidad' => $row['cantidad'],
            ];
        }

        return $resultado;
    }

    /**
     * Promedio de días de resolución
     */
    private function obtener_promedio_dias_resolucion($id_institucion) {
        $sql = 'SELECT ROUND(AVG(DATEDIFF(fecha_actualizacion, fecha_hora_registro)), 1) as promedio
                FROM reporte
                WHERE id_institucion = :id_inst
                AND id_estado = 7';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $resultado = $bd->obtener_uno($sql, [':id_inst' => $id_institucion]);

        return floatval($resultado['promedio'] ?? 0);
    }

    /**
     * Tasa de cierre (%)
     */
    private function calcular_tasa_cierre($id_institucion) {
        $sql_total = 'SELECT COUNT(*) as total FROM reporte WHERE id_institucion = :id_inst';
        $sql_cerrados = 'SELECT COUNT(*) as total FROM reporte WHERE id_institucion = :id_inst AND id_estado = 7';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();

        $total = $bd->obtener_uno($sql_total, [':id_inst' => $id_institucion])['total'];
        $cerrados = $bd->obtener_uno($sql_cerrados, [':id_inst' => $id_institucion])['total'];

        return $total > 0 ? round(($cerrados / $total) * 100, 1) : 0;
    }

    /**
     * Exportar reportes (RF-26)
     */
    public function exportar() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_EXPORTAR_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();
        $tipo = $_GET['tipo'] ?? 'csv'; // csv, encuestas, auditoria

        require_once APP_PATH . '/servicios/servicio_exportacion.php';
        $servicio_exportacion = new ServicioExportacion($id_institucion);

        $filtros = [
            'id_estado' => intval($_GET['estado'] ?? 0) ?: null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        switch ($tipo) {
            case 'encuestas':
                $contenido = $servicio_exportacion->exportar_encuestas_csv();
                $nombre = 'encuestas_' . date('Y-m-d') . '.csv';
                break;

            case 'auditoria':
                $contenido = $servicio_exportacion->exportar_auditoria_csv(
                    $filtros['fecha_desde'],
                    $filtros['fecha_hasta']
                );
                $nombre = 'auditoria_' . date('Y-m-d') . '.csv';
                break;

            default: // reportes
                $contenido = $servicio_exportacion->exportar_reportes_csv($filtros);
                $nombre = 'reportes_' . date('Y-m-d') . '.csv';
        }

        ServicioExportacion::descargar_csv($contenido, $nombre);
    }

    /**
     * RF-04: Registro de Auditoría — trazabilidad de acciones críticas
     */
    public function auditoria() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VER_AUDITORIA);

        $id_institucion = $this->auth->obtener_id_institucion();

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();

        // Filtros opcionales
        $fecha_desde = $_GET['fecha_desde'] ?? '';
        $fecha_hasta = $_GET['fecha_hasta'] ?? '';
        $buscar      = trim($_GET['buscar'] ?? '');

        // Paginación
        $por_pagina = 50;
        $pagina = max(1, intval($_GET['pagina'] ?? 1));
        $offset = ($pagina - 1) * $por_pagina;

        $where = 'WHERE a.id_institucion = :id_inst';
        $params = [':id_inst' => $id_institucion];

        if ($fecha_desde !== '') {
            $where .= ' AND DATE(a.fecha_hora_accion) >= :fdesde';
            $params[':fdesde'] = $fecha_desde;
        }
        if ($fecha_hasta !== '') {
            $where .= ' AND DATE(a.fecha_hora_accion) <= :fhasta';
            $params[':fhasta'] = $fecha_hasta;
        }
        if ($buscar !== '') {
            $where .= ' AND (a.accion LIKE :busca OR a.entidad LIKE :busca OR u.nombre_completo LIKE :busca)';
            $params[':busca'] = '%' . $buscar . '%';
        }

        // Total para paginación
        $sql_total = "SELECT COUNT(*) AS t
                      FROM registro_auditoria a
                      LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
                      $where";
        $total = intval($bd->obtener_uno($sql_total, $params)['t'] ?? 0);
        $total_paginas = max(1, (int) ceil($total / $por_pagina));

        // Registros de la página actual
        $sql = "SELECT a.id_auditoria, a.accion, a.entidad, a.id_entidad,
                       a.ip_origen, a.fecha_hora_accion,
                       u.nombre_completo,
                       CASE WHEN a.id_usuario IS NULL THEN 'Sistema' ELSE COALESCE(u.nombre_completo, CONCAT('Usuario #', a.id_usuario)) END AS actor
                FROM registro_auditoria a
                LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
                $where
                ORDER BY a.fecha_hora_accion DESC
                LIMIT $por_pagina OFFSET $offset";
        $registros = $bd->obtener_todos($sql, $params);

        $datos = [
            'titulo' => 'Auditoría - SIRGDI',
            'registros' => $registros,
            'total' => $total,
            'pagina' => $pagina,
            'total_paginas' => $total_paginas,
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'buscar' => $buscar,
        ];

        $this->renderizar_vista('dashboard/vista_auditoria', $datos);
    }

    /**
     * Helper: contar donde
     */
    private function contar_donde($sql, $id_institucion) {
        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $resultado = $bd->obtener_uno($sql, [':id_inst' => $id_institucion]);
        return intval($resultado['total'] ?? 0);
    }

    // ===== HELPERS =====

    private function renderizar_vista($vista, $datos = []) {
        extract($datos);
        $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';

        if (!file_exists($archivo_vista)) {
            die('Vista no encontrada: ' . $archivo_vista);
        }

        // Iniciar plantilla base
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
