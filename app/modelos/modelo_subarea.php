<?php
// Modelo Subárea (Aulas, baños, oficinas dentro de un área)

class ModeloSubarea {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener subárea por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_subarea, $id_institucion) {
        $sql = 'SELECT * FROM subarea
                WHERE id_subarea = :id_subarea
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_subarea' => $id_subarea,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar subareas de un área
     */
    public function listar_por_area($id_area, $id_institucion) {
        $sql = 'SELECT * FROM subarea
                WHERE id_area = :id_area
                AND id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_area' => $id_area,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar todas las subareas de una institución
     */
    public function listar_por_institucion($id_institucion) {
        $sql = 'SELECT * FROM subarea
                WHERE id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Crear subárea
     */
    public function crear($datos) {
        if (!isset($datos['nombre_completo']) || !isset($datos['id_area']) || !isset($datos['id_institucion'])) {
            throw new Exception('Nombre, área e institución son requeridos.');
        }

        $datos['fecha_creacion'] = date('Y-m-d H:i:s');

        return $this->bd->insertar('subarea', $datos);
    }

    /**
     * Actualizar subárea
     */
    public function actualizar($id_subarea, $id_institucion, $datos) {
        $where = 'id_subarea = :id_subarea AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_subarea' => $id_subarea,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('subarea', $datos, $where, $parametros_where);
    }

    /**
     * Eliminar subárea (soft delete)
     */
    public function eliminar($id_subarea, $id_institucion) {
        return $this->actualizar($id_subarea, $id_institucion, [
            'activo' => 0,
        ]);
    }
}
