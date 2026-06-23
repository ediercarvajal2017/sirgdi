<?php
// Modelo Subcategoría (Subcategoría específica del daño)

class ModeloSubcategoria {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener subcategoría por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_subcategoria, $id_institucion) {
        $sql = 'SELECT * FROM subcategoria
                WHERE id_subcategoria = :id_subcategoria
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_subcategoria' => $id_subcategoria,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar subcategorías de una categoría
     */
    public function listar_por_categoria($id_categoria, $id_institucion) {
        $sql = 'SELECT * FROM subcategoria
                WHERE id_categoria = :id_categoria
                AND id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_categoria' => $id_categoria,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar todas las subcategorías de una institución
     */
    public function listar_por_institucion($id_institucion) {
        $sql = 'SELECT * FROM subcategoria
                WHERE id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Crear subcategoría
     */
    public function crear($datos) {
        if (!isset($datos['nombre_completo']) || !isset($datos['id_categoria']) || !isset($datos['id_institucion'])) {
            throw new Exception('Nombre, categoría e institución son requeridos.');
        }

        $datos['fecha_creacion'] = date('Y-m-d H:i:s');

        return $this->bd->insertar('subcategoria', $datos);
    }

    /**
     * Actualizar subcategoría
     */
    public function actualizar($id_subcategoria, $id_institucion, $datos) {
        $where = 'id_subcategoria = :id_subcategoria AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_subcategoria' => $id_subcategoria,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('subcategoria', $datos, $where, $parametros_where);
    }

    /**
     * Eliminar subcategoría (soft delete)
     */
    public function eliminar($id_subcategoria, $id_institucion) {
        return $this->actualizar($id_subcategoria, $id_institucion, [
            'activo' => 0,
        ]);
    }
}
