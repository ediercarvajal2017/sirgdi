<?php
// Modelo Categoría (Categoría principal del daño)
// RN-06: Campo es_critica_escalada para auto-escalación a URGENTE

class ModeloCategoria {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener categoría por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_categoria, $id_institucion) {
        $sql = 'SELECT * FROM categoria
                WHERE id_categoria = :id_categoria
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_categoria' => $id_categoria,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar categorías de una institución
     */
    public function listar_por_institucion($id_institucion) {
        $sql = 'SELECT * FROM categoria
                WHERE id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar categorías críticas que requieren escalación (RN-06)
     */
    public function listar_criticas($id_institucion) {
        $sql = 'SELECT * FROM categoria
                WHERE id_institucion = :id_institucion
                AND es_critica_escalada = 1
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Verificar si categoría es crítica (para escalación automática)
     */
    public function es_critica($id_categoria, $id_institucion) {
        $sql = 'SELECT es_critica_escalada FROM categoria
                WHERE id_categoria = :id_categoria
                AND id_institucion = :id_institucion';

        $resultado = $this->bd->obtener_valor($sql, [
            ':id_categoria' => $id_categoria,
            ':id_institucion' => $id_institucion,
        ]);

        return $resultado == 1;
    }

    /**
     * Crear categoría
     */
    public function crear($datos) {
        if (!isset($datos['nombre_completo']) || !isset($datos['id_institucion'])) {
            throw new Exception('Nombre e institución son requeridos.');
        }

        $datos['fecha_creacion'] = date('Y-m-d H:i:s');

        // Por defecto, no es crítica
        if (!isset($datos['es_critica_escalada'])) {
            $datos['es_critica_escalada'] = 0;
        }

        return $this->bd->insertar('categoria', $datos);
    }

    /**
     * Actualizar categoría
     */
    public function actualizar($id_categoria, $id_institucion, $datos) {
        $where = 'id_categoria = :id_categoria AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_categoria' => $id_categoria,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('categoria', $datos, $where, $parametros_where);
    }

    /**
     * Marcar categoría como crítica (RN-06)
     */
    public function marcar_critica($id_categoria, $id_institucion) {
        return $this->actualizar($id_categoria, $id_institucion, [
            'es_critica_escalada' => 1,
        ]);
    }

    /**
     * Desmarcar categoría como crítica
     */
    public function desmarcar_critica($id_categoria, $id_institucion) {
        return $this->actualizar($id_categoria, $id_institucion, [
            'es_critica_escalada' => 0,
        ]);
    }

    /**
     * Eliminar categoría (soft delete)
     */
    public function eliminar($id_categoria, $id_institucion) {
        return $this->actualizar($id_categoria, $id_institucion, [
            'activo' => 0,
        ]);
    }
}
