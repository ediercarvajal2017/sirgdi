-- Migración: ampliar usuario.totp_secret
--
-- El secreto TOTP se guarda cifrado. Para un secreto base32 de 32 caracteres,
-- Encriptacion::encriptar() produce 128 caracteres, pero la columna era
-- VARCHAR(100): MySQL lo truncaba en silencio al guardarlo y después la
-- desencriptación fallaba siempre con "Decryption failed".
--
-- Es decir: la verificación en dos pasos no podía funcionar. El fallo estuvo
-- oculto porque habilitar_2fa() no tenía ningún llamador en la aplicación, así
-- que nadie llegó nunca a ejecutar ese camino.
--
-- 255 deja margen suficiente si en el futuro cambia el formato del cifrado.

ALTER TABLE usuario
    MODIFY totp_secret VARCHAR(255) NULL DEFAULT NULL;

-- Los secretos ya guardados están truncados y son inservibles: al intentar
-- usarlos, la desencriptación falla. Se limpian para que quien tuviera una
-- configuración a medias vuelva a empezar desde cero en vez de encontrarse un
-- error incomprensible.
UPDATE usuario
   SET totp_secret = NULL, requiere_2fa = 0
 WHERE totp_secret IS NOT NULL AND LENGTH(totp_secret) < 128;
