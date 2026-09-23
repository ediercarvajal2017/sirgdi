<?php
/**
 * Cron: revisar el SLA de los reportes abiertos y avisar.
 *
 * Por qué existe
 * --------------
 * ServicioPrioridad::evaluar_escalacion_sla() estaba escrito y completo desde
 * el principio, pero ningún punto del sistema lo invocaba: no había ningún
 * proceso programado más allá del respaldo diario. Resultado: los correos de
 * "SLA por vencer" y "SLA vencido" nunca se enviaron. El SLA solo se veía si
 * alguien abría el Kanban por su cuenta.
 *
 * Qué hace
 * --------
 * Recorre los reportes no terminados de cada institución activa y deja que
 * ServicioPrioridad decida: si el SLA está cerca o vencido, escala la urgencia
 * a URGENTE, lo registra en auditoría y notifica a gestor, rector y admin.
 * La escalación solo ocurre una vez por reporte, porque tras escalar la
 * urgencia ya es URGENTE y la condición deja de cumplirse.
 *
 * Programación sugerida (hPanel -> Cron Jobs), cada hora:
 *   /usr/bin/php /home/USUARIO/ruta/al/sitio/scripts/cron/evaluar_sla.php
 */

require_once __DIR__ . '/_arranque.php';
require_once APP_PATH . '/servicios/servicio_prioridad.php';

$candado = cron_candado('evaluar_sla');
$log = 'cron_sla.log';

cron_log('=== Inicio evaluación de SLA ===', $log);

$bd = BaseDatos::obtener();
$servicio = new ServicioPrioridad();

// Estados no terminales: los únicos en los que un SLA sigue corriendo.
$estados_abiertos = [
    ESTADO_REGISTRADO,
    ESTADO_ASIGNADO,
    ESTADO_EN_PROCESO,
    ESTADO_SOLUCIONADO,
    ESTADO_EN_VALIDACION,
    ESTADO_DEVUELTO,
];
$marcadores = implode(',', array_fill(0, count($estados_abiertos), '?'));

$total_revisados = 0;
$total_escalados = 0;
$total_errores   = 0;

$instituciones = $bd->obtener_todos(
    'SELECT id_institucion, nombre FROM institucion WHERE es_activa = 1 ORDER BY id_institucion'
);

foreach ($instituciones as $inst) {
    $id_institucion = (int) $inst['id_institucion'];

    $reportes = $bd->obtener_todos(
        'SELECT id_reporte, numero_ticket FROM reporte
         WHERE id_institucion = ? AND id_estado IN (' . $marcadores . ')
         ORDER BY fecha_hora_registro ASC',
        array_merge([$id_institucion], $estados_abiertos)
    );

    if (!$reportes) {
        continue;
    }

    $escalados_inst = 0;

    foreach ($reportes as $r) {
        $total_revisados++;
        try {
            if ($servicio->evaluar_escalacion_sla((int) $r['id_reporte'], $id_institucion)) {
                $escalados_inst++;
                $total_escalados++;
                cron_log("  escalado por SLA: {$r['numero_ticket']} (institución {$id_institucion})", $log);
            }
        } catch (Throwable $e) {
            $total_errores++;
            cron_log("  ERROR en {$r['numero_ticket']}: " . $e->getMessage(), $log);
        }
    }

    cron_log(sprintf(
        '  institución %d (%s): %d reportes abiertos, %d escalados',
        $id_institucion,
        mb_substr($inst['nombre'], 0, 40),
        count($reportes),
        $escalados_inst
    ), $log);
}

cron_log(sprintf(
    '=== Fin: %d instituciones, %d reportes revisados, %d escalados, %d errores ===',
    count($instituciones),
    $total_revisados,
    $total_escalados,
    $total_errores
), $log);

exit($total_errores > 0 ? 1 : 0);
