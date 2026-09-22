-- Migración: agrega la columna marcado_sospechoso a la tabla reporte.
-- Heurística anti-spam para reportes de invitado (RN de moderación) — no
-- bloquea el envío, solo marca el reporte para revisión prioritaria por
-- Gestor/Rector. Ejecutar una sola vez contra una BD ya existente (schema.sql
-- ya la incluye para instalaciones nuevas).

ALTER TABLE reporte
    ADD COLUMN marcado_sospechoso TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Heurística anti-spam en reportes de invitado; no bloquea, solo marca para revisión'
    AFTER es_reutilizable_solucion;
