<?php
/**
 * Cron: reintentar las notificaciones que quedaron sin enviar.
 *
 * Por qué existe
 * --------------
 * El envío de correo es síncrono: ocurre dentro de la misma petición que
 * dispara el evento. Si el SMTP está caído o rechaza el mensaje en ese
 * instante, la fila quedaba en 'pendiente' y nadie volvía a intentarlo: el
 * aviso se perdía sin que el destinatario ni el administrador se enteraran.
 * La tabla ya estaba preparada para una cola (índice idx_noti_cola, columnas
 * intentos y razon_fallo); solo faltaba quien la recorriera.
 *
 * Programación sugerida (hPanel -> Cron Jobs), cada 15 minutos:
 *   /usr/bin/php /home/USUARIO/ruta/al/sitio/scripts/cron/enviar_notificaciones_pendientes.php
 */

require_once __DIR__ . '/_arranque.php';
require_once APP_PATH . '/servicios/servicio_notificacion.php';

$candado = cron_candado('notificaciones');
$log = 'cron_notificaciones.log';

cron_log('=== Inicio reintento de notificaciones ===', $log);

$servicio = new ServicioNotificacion();

try {
    $r = $servicio->reintentar_pendientes(50);

    cron_log(sprintf(
        'procesadas=%d enviadas=%d fallidas=%d agotadas=%d',
        $r['procesadas'], $r['enviadas'], $r['fallidas'], $r['agotadas']
    ), $log);

    // "Agotadas" significa que se alcanzó el máximo de intentos y la fila pasó
    // a 'fallido': ya no se reintentará más. Eso merece atención humana.
    if ($r['agotadas'] > 0) {
        cron_log(
            "AVISO: {$r['agotadas']} notificación(es) se dieron por perdidas tras "
            . ServicioNotificacion::MAX_INTENTOS_ENVIO . ' intentos. '
            . 'Revisar la columna razon_fallo de la tabla notificacion.',
            $log
        );
    }

} catch (Throwable $e) {
    cron_log('ERROR: ' . $e->getMessage(), $log);
    cron_log('=== Fin con error ===', $log);
    exit(1);
}

cron_log('=== Fin ===', $log);
exit(0);
