<?php
// Modelo Área (Bloques/zonas dentro de una sede)

class ModeloArea {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener área por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_area, $id_institucion) {
        $sql = 'SELECT * FROM area
                WHERE id_area = :id_area
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_area' => $id_area,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar áreas de una sede
     */
    public function listar_por_sede($id_sede, $id_institucion) {
        $sql = 'SELECT * FROM area
                WHERE id_sede = :id_sede
                AND id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_sede' => $id_sede,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar todas las áreas de una institución
     */
    public function listar_por_institucion($id_institucion) {
        $sql = 'SELECT * FROM area
                WHERE id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Crear área
     */
    public function crear($datos) {
        if (!isset($datos['nombre_completo']) || !isset($datos['id_sede']) || !isset($datos['id_institucion'])) {
            throw new Exception('Nombre, sede e institución son requeridos.');
        }

        $datos['fecha_creacion'] = date('Y-m-d H:i:s');

        return $this->bd->insertar('area', $datos);
    }

    /**
     * Actualizar área
     */
    public function actualizar($id_area, $id_institucion, $datos) {
        $where = 'id_area = :id_area AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_area' => $id_area,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('area', $datos, $where, $parametros_where);
    }

    /**
     * Eliminar área (soft delete)
     */
    public function eliminar($id_area, $id_institucion) {
        return $this->actualizar($id_area, $id_institucion, [
            'activo' => 0,
        ]);
    }
}
