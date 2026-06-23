<?php
// Modelo Encuesta de Satisfacción (RF-22: Encuesta al reportante)
// Se genera al cerrar un reporte exitosamente

class ModeloEncuesta {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Crear encuesta para un reporte (RF-22)
     */
    public function crear($id_reporte, $id_institucion, $id_usuario_reportante) {
        $datos_encuesta = [
            'id_reporte' => $id_reporte,
            'id_institucion' => $id_institucion,
            'id_usuario_reportante' => $id_usuario_reportante,
            'fue_respondida' => 0,
        ];

        return $this->bd->insertar('encuesta_satisfaccion', $datos_encuesta);
    }

    /**
     * Obtener encuesta por reporte
     */
    public function obtener_por_reporte($id_reporte, $id_institucion) {
        $sql = 'SELECT * FROM encuesta_satisfaccion
                WHERE id_reporte = :id_reporte
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Obtener encuesta por ID
     */
    public function obtener_por_id($id_encuesta) {
        $sql = 'SELECT * FROM encuesta_satisfaccion WHERE id_encuesta = :id_encuesta';
        return $this->bd->obtener_uno($sql, [':id_encuesta' => $id_encuesta]);
    }

    /**
     * Guardar respuestas de encuesta
     */
    public function registrar_respuesta($id_encuesta, $puntuacion, $comentario) {
        $sql = 'UPDATE encuesta_satisfaccion
                SET puntuacion = :puntuacion,
                    comentario = :comentario,
                    fecha_completada = NOW(),
                    fue_respondida = 1
                WHERE id_encuesta = :id_encuesta';

        return $this->bd->ejecutar($sql, [
            ':id_encuesta' => $id_encuesta,
            ':puntuacion' => intval($puntuacion),
            ':comentario' => $comentario,
        ]);
    }

    /**
     * Obtener estadísticas de satisfacción
     */
    public function obtener_estadisticas($id_institucion) {
        $sql = 'SELECT
                    COUNT(*) as total_encuestas,
                    SUM(CASE WHEN fue_respondida = 1 THEN 1 ELSE 0 END) as encuestas_respondidas,
                    AVG(CASE WHEN fue_respondida = 1 THEN puntuacion ELSE NULL END) as calificacion_promedio,
                    SUM(CASE WHEN fue_respondida = 1 AND puntuacion >= 4 THEN 1 ELSE 0 END) as satisfechos,
                    SUM(CASE WHEN fue_respondida = 1 AND puntuacion <= 2 THEN 1 ELSE 0 END) as insatisfechos
                FROM encuesta_satisfaccion
                WHERE id_institucion = :id_institucion';

        $resultado = $this->bd->obtener_uno($sql, [
            ':id_institucion' => $id_institucion,
        ]);

        return [
            'total' => intval($resultado['total_encuestas'] ?? 0),
            'respondidas' => intval($resultado['encuestas_respondidas'] ?? 0),
            'tasa_respuesta' => $resultado['total_encuestas'] > 0
                ? round(($resultado['encuestas_respondidas'] / $resultado['total_encuestas']) * 100, 1)
                : 0,
            'calificacion_promedio' => round($resultado['calificacion_promedio'] ?? 0, 2),
            'satisfechos' => intval($resultado['satisfechos'] ?? 0),
            'insatisfechos' => intval($resultado['insatisfechos'] ?? 0),
        ];
    }
}
