-- Migración: marcar notificaciones como leídas (campana in-app)
--
-- Motivo: la tabla solo sabía si el CORREO salió (estado_envio), no si la
-- persona vio el aviso. ServicioNotificacion::obtener_pendientes() filtraba por
-- estado_envio = 'pendiente', así que la campana habría mostrado únicamente los
-- avisos cuyo envío falló, y habrían desaparecido en cuanto el correo saliera
-- bien: justo al revés de lo que se necesita.
--
-- Con fecha_leida, la campana es independiente del correo. Eso es lo que la
-- convierte en la red de seguridad cuando el SMTP falla: el técnico ve que le
-- asignaron trabajo aunque el correo no haya llegado nunca.

ALTER TABLE notificacion
    ADD COLUMN fecha_leida DATETIME NULL DEFAULT NULL AFTER fecha_enviada,
    ADD INDEX idx_noti_usuario_leida (id_usuario_destinatario, fecha_leida, fecha_creacion);

-- Todo lo anterior a la campana se da por visto.
--
-- Sin esto, el día del despliegue cada usuario se encontraría un globo con
-- meses de avisos antiguos —13 a 15 por persona en producción— que además ya
-- había recibido por correo en su momento. Ninguno de esos avisos estuvo nunca
-- "sin leer" en una campana, porque la campana no existía: marcarlos como
-- vistos es reflejar la realidad, no ocultar información. Las filas se
-- conservan intactas; solo se rellena la columna nueva.
UPDATE notificacion
   SET fecha_leida = COALESCE(fecha_enviada, fecha_creacion)
 WHERE fecha_leida IS NULL;
