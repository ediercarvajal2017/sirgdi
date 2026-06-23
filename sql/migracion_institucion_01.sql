-- Migración: Actualizar tabla institucion con código DANE y manejo de archivos
-- Fecha: 2026-06-18
-- Descripción: Agregar código DANE y cambiar logo_url a logo_ruta para almacenar archivos

-- Crear columna código_dane (CHAR(10) porque DANE tiene 10 dígitos)
ALTER TABLE institucion
ADD COLUMN codigo_dane CHAR(10) NULL UNIQUE COMMENT 'Código DANE de 10 dígitos (único)' AFTER nombre;

-- Renombrar logo_url a logo_ruta para almacenar rutas de archivo
ALTER TABLE institucion
CHANGE COLUMN logo_url logo_ruta VARCHAR(500) NULL COMMENT 'Ruta local del archivo de logo (ej: /almacenamiento/logos/institucion_1.png)';

-- Agregar índice para búsquedas por código DANE
ALTER TABLE institucion
ADD INDEX idx_codigo_dane (codigo_dane);

-- Agregar comentario actualizado a la tabla
ALTER TABLE institucion COMMENT='Tabla raíz multitenant: instituciones educativas con código DANE y logos almacenados localmente';
