-- ---------------------------------------------------------------------------
-- Tabla avance_intervencion
-- ---------------------------------------------------------------------------
-- Hasta ahora esta tabla se creaba desde el código: ModeloAvance ejecutaba un
-- CREATE TABLE IF NOT EXISTS en cada petición que la tocara. Funcionaba, pero
-- obligaba a que el usuario de base de datos de producción conservara de forma
-- permanente permisos de CREATE TABLE.
--
-- Un usuario de aplicación solo debería poder leer y escribir filas. Si alguien
-- encuentra una inyección SQL, la diferencia entre poder consultar y poder
-- borrar tablas es exactamente ese permiso.
--
-- Es idempotente (IF NOT EXISTS): en producción la tabla ya existe y esto no
-- hace nada.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS avance_intervencion (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Avances del técnico visibles al reportante.';
