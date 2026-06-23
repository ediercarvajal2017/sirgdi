<?php
// Modelo Evidencia (Fotos - RF-18: Evidencia fotográfica)
// RN-03: Mínimo 1 foto por etapa (Antes, Durante, Después) antes de cerrar
// RNF-04: Fotos comprimidas automáticamente (JPG, max 5MB)
//
// Tabla real `evidencia`: id_etapa (1=Antes,2=Durante,3=Después), url_archivo,
// nombre_archivo_original, tipo_mime, tamanio_bytes, hash_archivo, descripcion,
// cargada_por, fecha_hora_carga

class ModeloEvidencia {
    private $bd;

    // Mapeo etapa string <=> id_etapa (catálogo etapa_evidencia)
    // 'reportante' (4) = fotos que adjunta el reportante al crear el reporte.
    // 'antes/durante/despues' (1,2,3) = fotos que sube el técnico durante la reparación.
    const ETAPAS = [
        'antes'      => 1,
        'durante'    => 2,
        'despues'    => 3,
        'reportante' => 4,
    ];

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Convertir nombre de etapa a id_etapa
     */
    public static function etapa_a_id($etapa) {
        return self::ETAPAS[$etapa] ?? null;
    }

    /**
     * Convertir id_etapa a nombre de etapa
     */
    public static function id_a_etapa($id_etapa) {
        $invertido = array_flip(self::ETAPAS);
        return $invertido[$id_etapa] ?? null;
    }

    /**
     * Obtener evidencia por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_evidencia, $id_institucion) {
        $sql = 'SELECT * FROM evidencia
                WHERE id_evidencia = :id_evidencia
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_evidencia' => $id_evidencia,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar evidencias por reporte
     */
    public function listar_por_reporte($id_reporte, $id_institucion) {
        $sql = 'SELECT * FROM evidencia
                WHERE id_reporte = :id_reporte
                AND id_institucion = :id_institucion
                ORDER BY id_etapa ASC, fecha_hora_carga DESC';

        return $this->bd->obtener_todos($sql, [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Contar fotos por etapa (acepta nombre 'antes'|'durante'|'despues' o id numérico)
     */
    public function contar_por_etapa($id_reporte, $id_institucion, $etapa) {
        $id_etapa = is_numeric($etapa) ? intval($etapa) : self::etapa_a_id($etapa);

        $sql = 'SELECT COUNT(*) as total FROM evidencia
                WHERE id_reporte = :id_reporte
                AND id_institucion = :id_institucion
                AND id_etapa = :id_etapa';

        $resultado = $this->bd->obtener_uno($sql, [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
            ':id_etapa' => $id_etapa,
        ]);

        return intval($resultado['total'] ?? 0);
    }

    /**
     * Verificar si todas las etapas tienen evidencia (RN-03)
     * Retorna: ['completa' => bool, 'faltantes' => array]
     */
    public function verificar_completitud($id_reporte, $id_institucion) {
        $etapas = ['antes', 'durante', 'despues'];
        $faltantes = [];

        foreach ($etapas as $etapa) {
            $cantidad = $this->contar_por_etapa($id_reporte, $id_institucion, $etapa);
            if ($cantidad === 0) {
                $faltantes[] = $etapa;
            }
        }

        return [
            'completa' => count($faltantes) === 0,
            'faltantes' => $faltantes,
        ];
    }

    /**
     * Crear registro de evidencia
     * Acepta etapa por nombre ('antes'...) o por id_etapa numérico.
     * Los archivos ya fueron procesados por ServicioArchivos.
     */
    public function crear($datos) {
        // Normalizar etapa
        if (isset($datos['etapa_evidencia'])) {
            $datos['id_etapa'] = self::etapa_a_id($datos['etapa_evidencia']);
            unset($datos['etapa_evidencia']);
        }
        // Normalizar nombres de columnas heredados
        if (isset($datos['ruta_archivo'])) {
            $datos['url_archivo'] = $datos['ruta_archivo'];
            unset($datos['ruta_archivo']);
        }
        if (isset($datos['nombre_archivo'])) {
            $datos['nombre_archivo_original'] = $datos['nombre_archivo'];
            unset($datos['nombre_archivo']);
        }
        if (isset($datos['descripcion_foto'])) {
            $datos['descripcion'] = $datos['descripcion_foto'];
            unset($datos['descripcion_foto']);
        }
        if (isset($datos['tamaño_bytes'])) {
            $datos['tamanio_bytes'] = $datos['tamaño_bytes'];
            unset($datos['tamaño_bytes']);
        }

        if (!isset($datos['id_reporte']) || !isset($datos['id_institucion']) ||
            !isset($datos['id_etapa']) || !isset($datos['url_archivo'])) {
            throw new Exception('Datos incompletos para crear evidencia.');
        }

        // Valores por defecto requeridos por la tabla
        if (!isset($datos['tipo_mime'])) {
            $datos['tipo_mime'] = 'image/jpeg';
        }
        if (!isset($datos['nombre_archivo_original'])) {
            $datos['nombre_archivo_original'] = basename($datos['url_archivo']);
        }
        if (!isset($datos['tamanio_bytes'])) {
            $datos['tamanio_bytes'] = 0;
        }
        $datos['fecha_hora_carga'] = date('Y-m-d H:i:s');

        return $this->bd->insertar('evidencia', $datos);
    }

    /**
     * Eliminar evidencia (borrado real + archivo físico)
     */
    public function eliminar($id_evidencia, $id_institucion) {
        // Obtener ruta del archivo antes de eliminar BD
        $evidencia = $this->obtener_por_id($id_evidencia, $id_institucion);
        if ($evidencia && !empty($evidencia['url_archivo']) && file_exists($evidencia['url_archivo'])) {
            @unlink($evidencia['url_archivo']);
        }

        return $this->bd->eliminar(
            'evidencia',
            'id_evidencia = :id_evidencia AND id_institucion = :id_institucion',
            [
                ':id_evidencia' => $id_evidencia,
                ':id_institucion' => $id_institucion,
            ]
        );
    }
}
