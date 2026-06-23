<?php
// Modelo Sede (Ubicaciones físicas de la institución)

class ModeloSede {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener sede por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_sede, $id_institucion) {
        $sql = 'SELECT * FROM sede
                WHERE id_sede = :id_sede
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_sede' => $id_sede,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar sedes de una institución (todas: activas e inactivas)
     */
    public function listar_por_institucion($id_institucion) {
        $sql = 'SELECT * FROM sede
                WHERE id_institucion = :id_institucion
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar sedes activas de una institución
     */
    public function listar_activas($id_institucion) {
        $sql = 'SELECT * FROM sede
                WHERE id_institucion = :id_institucion
                AND activa = 1
                ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Crear sede
     */
    public function crear($datos) {
        if (!isset($datos['nombre']) || !isset($datos['id_institucion'])) {
            throw new Exception('Nombre e institución son requeridos.');
        }

        $datos_insertar = [
            'id_institucion' => $datos['id_institucion'],
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'activa' => $datos['activa'] ?? 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];

        return $this->bd->insertar('sede', $datos_insertar);
    }

    /**
     * Actualizar sede
     */
    public function actualizar($id_sede, $id_institucion, $datos) {
        $where = 'id_sede = :id_sede AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_sede' => $id_sede,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('sede', $datos, $where, $parametros_where);
    }

    /**
     * Eliminar sede (hard delete)
     */
    public function eliminar($id_sede) {
        // Borrar en cascada las ubicaciones hijas (subáreas → áreas → sede) en una transacción.
        // Los reportes se validan/bloquean en el controlador para preservar datos.
        $this->bd->transaccion(function ($bd) use ($id_sede) {
            // Subáreas de las áreas de esta sede
            $bd->eliminar('subarea',
                'id_area IN (SELECT id_area FROM area WHERE id_sede = :id)',
                [':id' => $id_sede]);
            // Áreas de esta sede
            $bd->eliminar('area', 'id_sede = :id', [':id' => $id_sede]);
            // La sede
            $bd->eliminar('sede', 'id_sede = :id', [':id' => $id_sede]);
        });
        return true;
    }

    /**
     * Contar sedes de una institución
     */
    public function contar($id_institucion) {
        $sql = 'SELECT COUNT(*) as total FROM sede
                WHERE id_institucion = :id_institucion
                AND activa = 1';

        return intval($this->bd->obtener_valor($sql, [
            ':id_institucion' => $id_institucion,
        ]));
    }
}
