<?php
// Modelo Intervención Técnica (RF-17: Informe de intervención)
// RN-05: Solo técnico asignado puede crear intervención
//
// Tabla real `informe_intervencion`: id_informe (PK), id_reporte (UNIQUE),
// id_institucion, id_usuario_tecnico, descripcion_actividades, causa_raiz,
// solucion_implementada, fecha_hora_inicio, fecha_hora_fin,
// materiales_utilizados_json, costo_estimado, fecha_creacion, fecha_actualizacion

class ModeloIntervension {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener intervención por ID
     */
    public function obtener_por_id($id_informe, $id_institucion) {
        $sql = 'SELECT * FROM informe_intervencion
                WHERE id_informe = :id_informe
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_informe' => $id_informe,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Obtener intervención por reporte (RN-01: con filtro id_institucion)
     */
    public function obtener_por_reporte($id_reporte, $id_institucion) {
        $sql = 'SELECT * FROM informe_intervencion
                WHERE id_reporte = :id_reporte
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Crear informe de intervención (RF-17)
     * RN-05: Solo técnico asignado puede crear
     * Acepta clave heredada 'id_tecnico' => id_usuario_tecnico y
     * 'descripcion_intervencion' => descripcion_actividades
     */
    public function crear($datos) {
        // Normalizar claves heredadas
        if (isset($datos['id_tecnico'])) {
            $datos['id_usuario_tecnico'] = $datos['id_tecnico'];
            unset($datos['id_tecnico']);
        }
        if (isset($datos['descripcion_intervencion'])) {
            $datos['descripcion_actividades'] = $datos['descripcion_intervencion'];
            unset($datos['descripcion_intervencion']);
        }

        if (!isset($datos['id_reporte']) || !isset($datos['id_institucion']) || !isset($datos['id_usuario_tecnico'])) {
            throw new Exception('id_reporte, id_institucion e id_usuario_tecnico son requeridos.');
        }

        if (empty($datos['descripcion_actividades'])) {
            throw new Exception('La descripción de actividades es requerida.');
        }

        // Campos NOT NULL de la tabla
        if (empty($datos['solucion_implementada'])) {
            $datos['solucion_implementada'] = $datos['descripcion_actividades'];
        }
        if (empty($datos['fecha_hora_inicio'])) {
            $datos['fecha_hora_inicio'] = date('Y-m-d H:i:s');
        }

        $datos['fecha_creacion'] = date('Y-m-d H:i:s');

        return $this->bd->insertar('informe_intervencion', $datos);
    }

    /**
     * Actualizar intervención
     */
    public function actualizar($id_informe, $id_institucion, $datos) {
        $where = 'id_informe = :id_informe AND id_institucion = :id_institucion';
        $parametros = [
            ':id_informe' => $id_informe,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('informe_intervencion', $datos, $where, $parametros);
    }

    /**
     * Marcar informe como finalizado (registra hora de fin)
     */
    public function marcar_finalizada($id_informe, $id_institucion) {
        return $this->actualizar($id_informe, $id_institucion, [
            'fecha_hora_fin' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtener informes de un técnico
     */
    public function listar_por_tecnico($id_usuario_tecnico, $id_institucion) {
        $sql = 'SELECT i.*, r.numero_ticket
                FROM informe_intervencion i
                JOIN reporte r ON i.id_reporte = r.id_reporte
                WHERE i.id_usuario_tecnico = :id_tecnico
                AND i.id_institucion = :id_institucion
                ORDER BY i.fecha_hora_inicio DESC';

        return $this->bd->obtener_todos($sql, [
            ':id_tecnico' => $id_usuario_tecnico,
            ':id_institucion' => $id_institucion,
        ]);
    }
}
