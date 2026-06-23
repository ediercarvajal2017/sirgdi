<?php
/**
 * Modelo: Institución
 * Gestiona CRUD de instituciones educativas (tenants)
 */

class ModeloInstitucion {

    private $tabla = 'institucion';
    private $bd;

    public function __construct() {
        require_once LIB_PATH . '/basedatos.php';
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Crear nueva institución
     * @param array $datos [nombre, codigo_dane, logo_ruta (opcional)]
     * @return int ID de la institución creada
     */
    public function crear($datos) {
        $datos_insertar = [
            'nombre' => $datos['nombre'] ?? '',
            'codigo_dane' => $datos['codigo_dane'] ?? '',
            'logo_ruta' => $datos['logo_ruta'] ?? null,
            'es_activa' => $datos['es_activa'] ?? 1,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'fecha_actualizacion' => date('Y-m-d H:i:s')
        ];

        return $this->bd->insertar($this->tabla, $datos_insertar);
    }

    /**
     * Obtener institución por ID
     * @param int $id_institucion
     * @return array|null
     */
    public function obtener_por_id($id_institucion) {
        $sql = 'SELECT * FROM ' . $this->tabla . ' WHERE id_institucion = :id';
        return $this->bd->obtener_uno($sql, [':id' => $id_institucion]);
    }

    /**
     * Obtener institución por código DANE
     * @param string $codigo_dane
     * @return array|null
     */
    public function obtener_por_dane($codigo_dane) {
        $sql = 'SELECT * FROM ' . $this->tabla . ' WHERE codigo_dane = :dane';
        return $this->bd->obtener_uno($sql, [':dane' => $codigo_dane]);
    }

    /**
     * Listar todas las instituciones
     * @param bool $solo_activas Si true, solo retorna instituciones activas
     * @return array
     */
    public function listar($solo_activas = false) {
        $sql = 'SELECT * FROM ' . $this->tabla;
        $parametros = [];

        if ($solo_activas) {
            $sql .= ' WHERE es_activa = 1';
        }

        $sql .= ' ORDER BY nombre ASC';

        return $this->bd->obtener_todos($sql, $parametros);
    }

    /**
     * Actualizar institución
     * @param int $id_institucion
     * @param array $datos [nombre, codigo_dane, logo_ruta (opcional), es_activa (opcional)]
     * @return bool
     */
    public function actualizar($id_institucion, $datos) {
        $campos = [];
        $parametros = [':id' => $id_institucion];

        if (isset($datos['nombre'])) {
            $campos[] = 'nombre = :nombre';
            $parametros[':nombre'] = $datos['nombre'];
        }

        if (isset($datos['codigo_dane'])) {
            $campos[] = 'codigo_dane = :codigo_dane';
            $parametros[':codigo_dane'] = $datos['codigo_dane'];
        }

        if (isset($datos['logo_ruta'])) {
            $campos[] = 'logo_ruta = :logo_ruta';
            $parametros[':logo_ruta'] = $datos['logo_ruta'];
        }

        if (isset($datos['es_activa'])) {
            $campos[] = 'es_activa = :es_activa';
            $parametros[':es_activa'] = $datos['es_activa'] ? 1 : 0;
        }

        if (empty($campos)) {
            return false;
        }

        $campos[] = 'fecha_actualizacion = NOW()';

        $sql = 'UPDATE ' . $this->tabla . ' SET ' . implode(', ', $campos) . ' WHERE id_institucion = :id';

        return $this->bd->ejecutar($sql, $parametros);
    }

    /**
     * Activar/Desactivar institución
     * @param int $id_institucion
     * @param bool $es_activa
     * @return bool
     */
    public function cambiar_estado($id_institucion, $es_activa) {
        $sql = 'UPDATE ' . $this->tabla . ' SET es_activa = :activa, fecha_actualizacion = NOW() WHERE id_institucion = :id';
        return $this->bd->ejecutar($sql, [
            ':activa' => $es_activa ? 1 : 0,
            ':id' => $id_institucion
        ]);
    }

    /**
     * Eliminar institución (hard delete - usar con cuidado)
     * @param int $id_institucion
     * @return bool
     */
    public function eliminar($id_institucion) {
        // Recolectar archivos de evidencia para borrarlos del disco tras el commit
        $evidencias = $this->bd->obtener_todos(
            'SELECT url_archivo FROM evidencia WHERE id_institucion = :id',
            [':id' => $id_institucion]
        );
        $archivos = array_column($evidencias ?? [], 'url_archivo');

        // Borrar todas las tablas hijas en orden de dependencia (FK), luego la institución
        $tablas = [
            // Registros ligados a reportes (más profundos primero)
            'transicion_estado', 'evidencia', 'informe_intervencion',
            'comentario_interno', 'encuesta_satisfaccion', 'notificacion',
            'plantilla_solucion',
            'reporte',
            // Catálogos y ubicaciones
            'sla', 'subcategoria', 'categoria',
            'subarea', 'area', 'sede',
            'configuracion_institucion',
            // Auditoría y usuarios
            'registro_auditoria', 'usuario_rol', 'usuario',
        ];

        $this->bd->transaccion(function ($bd) use ($id_institucion, $tablas) {
            foreach ($tablas as $tabla) {
                $bd->eliminar($tabla, 'id_institucion = :id', [':id' => $id_institucion]);
            }
            $bd->eliminar('institucion', 'id_institucion = :id', [':id' => $id_institucion]);
        });

        return $archivos;
    }

    /**
     * Validar código DANE único
     * @param string $codigo_dane
     * @param int $id_institucion_actual (para actualización, excluir la institución actual)
     * @return bool True si es único, false si ya existe
     */
    public function es_codigo_dane_unico($codigo_dane, $id_institucion_actual = null) {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->tabla . ' WHERE codigo_dane = :dane';
        $parametros = [':dane' => $codigo_dane];

        if ($id_institucion_actual) {
            $sql .= ' AND id_institucion != :id';
            $parametros[':id'] = $id_institucion_actual;
        }

        $resultado = $this->bd->obtener_uno($sql, $parametros);
        return $resultado['total'] == 0;
    }

    /**
     * Contar instituciones
     * @param bool $solo_activas
     * @return int
     */
    public function contar($solo_activas = false) {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->tabla;

        if ($solo_activas) {
            $sql .= ' WHERE es_activa = 1';
        }

        $resultado = $this->bd->obtener_uno($sql, []);
        return intval($resultado['total'] ?? 0);
    }

    /**
     * Buscar instituciones por nombre o código DANE
     * @param string $termino
     * @return array
     */
    public function buscar($termino) {
        $sql = '
            SELECT * FROM ' . $this->tabla . '
            WHERE nombre LIKE :termino
            OR codigo_dane LIKE :termino
            ORDER BY nombre ASC
        ';

        $parametros = [':termino' => '%' . $termino . '%'];
        return $this->bd->obtener_todos($sql, $parametros);
    }
}
