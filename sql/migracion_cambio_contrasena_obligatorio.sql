-- Migración: obligar a cambiar la contraseña en el primer ingreso
--
-- Motivo: cuando un administrador crea una cuenta, la contraseña se la entrega
-- por WhatsApp o por teléfono. El sistema decía "(Debe cambiarla al ingresar)"
-- pero eso era solo una frase en un mensaje: nada lo forzaba. Un rector podía
-- operar indefinidamente con la contraseña temporal que le dictaron, conocida
-- por quien se la creó y por cualquiera que leyera ese mensaje.
--
-- La columna se pone en 1 cuando alguien distinto al dueño fija la contraseña
-- (alta de usuario, alta de institución, o restablecimiento hecho por un
-- administrador) y vuelve a 0 en cuanto el usuario la cambia él mismo.
--
-- Los usuarios que ya existen quedan en 0: llevan meses operando y forzarles
-- un cambio sin avisar sería una interrupción sin motivo. La regla aplica de
-- aquí en adelante.

ALTER TABLE usuario
    ADD COLUMN debe_cambiar_contrasena TINYINT(1) NOT NULL DEFAULT 0
    AFTER version_credenciales;
