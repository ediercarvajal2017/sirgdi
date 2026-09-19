<?php
// Modelo Avance de intervención — bitácora del técnico por reporte.
//
// Es la nota breve con fecha que el técnico deja mientras trabaja ("Falta material,
// regreso mañana"). A diferencia de comentario_interno (hilo privado gestor/técnico),
// estos avances se muestran al reportante en el seguimiento público.

class ModeloAvance {
    private $bd;
    private static $tabla_verificada = false;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
        $this->asegurar_tabla();
    }

    /**
     * Crea la tabla si no existe. Es idempotente y se ejecuta una sola vez por
     * petición, así la función queda operativa en producción sin un paso manual de
     * migración. El DDL canónico está también en sql/schema.sql.
     */
    private function asegurar_tabla() {
        if (self::$tabla_verificada) return;
        self::$tabla_verificada = true;

        $this->bd->ejecutar('CREATE TABLE IF NOT EXISTS avance_intervencion (
            id_avance        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_reporte       BIGINT UNSIGNED NOT NULL,
            id_institucion   BIGINT UNSIGNED NOT NULL,
            id_informe       BIGINT UNSIGNED NULL,
            id_usuario_autor BIGINT UNSIGNED NOT NULL,
            texto            VARCHAR(500)    NOT NULL,
            fecha_creacion   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_avance),
            INDEX idx_avance_rpt_fecha (id_reporte, fecha_creacion),
            CONSTRAINT fk_avance_rpt  FOREIGN KEY (id_reporte)       REFERENCES reporte(id_reporte),
            CONSTRAINT fk_avance_inst FOREIGN KEY (id_institucion)   REFERENCES institucion(id_institucion),
            CONSTRAINT fk_avance_usr  FOREIGN KEY (id_usuario_autor) REFERENCES usuario(id_usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function crear($datos) {
        return $this->bd->insertar('avance_intervencion', [
            'id_reporte'       => $datos['id_reporte'],
            'id_institucion'   => $datos['id_institucion'],
            'id_informe'       => $datos['id_informe'] ?? null,
            'id_usuario_autor' => $datos['id_usuario_autor'],
            'texto'            => $datos['texto'],
        ]);
    }

    /** Avances de un reporte, del más reciente al más antiguo, con el nombre del autor */
    public function listar_por_reporte($id_reporte, $id_institucion) {
        $sql = 'SELECT a.id_avance, a.texto, a.fecha_creacion, u.nombre_completo AS autor
                FROM avance_intervencion a
                JOIN usuario u ON u.id_usuario = a.id_usuario_autor
                WHERE a.id_reporte = :r AND a.id_institucion = :i
                ORDER BY a.fecha_creacion DESC, a.id_avance DESC';
        return $this->bd->obtener_todos($sql, [':r' => $id_reporte, ':i' => $id_institucion]);
    }

    /**
     * Versión para el seguimiento público: sin filtro de institución por sesión
     * (el acceso ya se validó por token) y sin datos del autor más allá del nombre.
     */
    public function listar_publicos($id_reporte) {
        $sql = 'SELECT a.texto, a.fecha_creacion, u.nombre_completo AS autor
                FROM avance_intervencion a
                JOIN usuario u ON u.id_usuario = a.id_usuario_autor
                WHERE a.id_reporte = :r
                ORDER BY a.fecha_creacion DESC, a.id_avance DESC';
        return $this->bd->obtener_todos($sql, [':r' => $id_reporte]);
    }
}
