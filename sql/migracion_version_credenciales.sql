-- Migración: versión de credenciales por usuario
--
-- Motivo: al cambiar o restablecer una contraseña, las sesiones ya abiertas
-- seguían siendo válidas (hallazgo M2 del informe de seguridad 2026-09).
-- Si alguien robaba una sesión, cambiar la contraseña no lo expulsaba.
--
-- Con esta columna, ServicioAutenticacion compara en cada petición la versión
-- guardada en la sesión contra la de la BD; si no coinciden, cierra la sesión.
-- ModeloUsuario::actualizar() la incrementa siempre que cambia hash_contrasena.

ALTER TABLE usuario
    ADD COLUMN version_credenciales INT UNSIGNED NOT NULL DEFAULT 1
    AFTER hash_contrasena;
