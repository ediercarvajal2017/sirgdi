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
 * ServicioPrioridad::revisar_sla() decida: si el SLA está cerca o vencido,
 * escala la urgencia a URGENTE, lo registra en auditoría y avisa a gestor,
 * rector y Admin de Institución.
 *
 * El aviso se manda aunque no haya nada que escalar. Un reporte que ya estaba
 * en URGENTE y además incumple su SLA es el caso más grave, y antes era
 * justamente el que no generaba ninguna alerta. Para no repetir el aviso en
 * cada ejecución, se marca en auditoría y solo se envía una vez por estado.
 *
 * También avisa si una institución no tiene a nadie con rol Gestor o Rector:
 * en ese caso las alertas de SLA no tendrían destinatario y se perderían sin
 * que nadie se entere.
 *
 * Programación sugerida (hPanel -> Cron Jobs), cada hora:
 *   /usr/bin/php /home/USUARIO/ruta/al/sitio/scripts/cron/evaluar_sla.php
 */

require_once __DIR__ . '/_arranque.php';
require_once APP_PATH . '/servicios/servicio_prioridad.php';
require_once APP_PATH . '/servicios/servicio_notificacion.php';

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
$total_avisados  = 0;
$total_errores   = 0;

$notificacion = new ServicioNotificacion();

// Quién recibe cada alerta de SLA (ver ServicioNotificacion):
//   - "por vencer": Gestor y Rector
//   - "vencido":    Gestor, Rector y Admin de Institución
$roles_por_vencer = ['gestor', 'rector'];
$roles_vencido    = ['gestor', 'rector', 'Admin de Institución'];

$instituciones = $bd->obtener_todos(
    'SELECT id_institucion, nombre FROM institucion WHERE es_activa = 1 ORDER BY id_institucion'
);

foreach ($instituciones as $inst) {
    $id_institucion = (int) $inst['id_institucion'];
    $nombre_inst = mb_substr($inst['nombre'], 0, 40);

    $reportes = $bd->obtener_todos(
        'SELECT id_reporte, numero_ticket FROM reporte
         WHERE id_institucion = ? AND id_estado IN (' . $marcadores . ')
         ORDER BY fecha_hora_registro ASC',
        array_merge([$id_institucion], $estados_abiertos)
    );

    if (!$reportes) {
        continue;
    }

    // Comprobar antes de nada que haya alguien a quien avisar.
    $dest_vencido    = $notificacion->contar_destinatarios_por_roles($id_institucion, $roles_vencido);
    $dest_por_vencer = $notificacion->contar_destinatarios_por_roles($id_institucion, $roles_por_vencer);

    if ($dest_vencido === 0) {
        cron_log(sprintf(
            '  AVISO: la institución %d (%s) tiene %d reportes abiertos y ningún usuario activo '
            . 'con rol Gestor, Rector o Admin de Institución. Ninguna alerta de SLA llegará a nadie.',
            $id_institucion, $nombre_inst, count($reportes)
        ), $log);
    } elseif ($dest_por_vencer === 0) {
        cron_log(sprintf(
            '  AVISO: la institución %d (%s) no tiene Gestor ni Rector. Recibirá las alertas de '
            . 'SLA vencido (llegan al Admin de Institución) pero no las de "por vencer", '
            . 'que son las que permiten reaccionar a tiempo.',
            $id_institucion, $nombre_inst
        ), $log);
    }

    $escalados_inst = 0;
    $avisados_inst  = 0;

    foreach ($reportes as $r) {
        $total_revisados++;
        try {
            $res = $servicio->revisar_sla((int) $r['id_reporte'], $id_institucion);

            if ($res['escalado']) {
                $escalados_inst++;
                $total_escalados++;
            }
            if ($res['avisado']) {
                $avisados_inst++;
                $total_avisados++;
            }
            if ($res['escalado'] || $res['avisado']) {
                cron_log(sprintf(
                    '  %s: SLA %s%s%s',
                    $r['numero_ticket'],
                    $res['estado_sla'],
                    $res['escalado'] ? ' — urgencia escalada' : '',
                    $res['avisado'] ? ' — aviso enviado' : ' — aviso ya enviado antes'
                ), $log);
            }
        } catch (Throwable $e) {
            $total_errores++;
            cron_log("  ERROR en {$r['numero_ticket']}: " . $e->getMessage(), $log);
        }
    }

    cron_log(sprintf(
        '  institución %d (%s): %d abiertos, %d escalados, %d avisados',
        $id_institucion, $nombre_inst, count($reportes), $escalados_inst, $avisados_inst
    ), $log);
}

cron_log(sprintf(
    '=== Fin: %d instituciones, %d reportes revisados, %d escalados, %d avisados, %d errores ===',
    count($instituciones),
    $total_revisados,
    $total_escalados,
    $total_avisados,
    $total_errores
), $log);

exit($total_errores > 0 ? 1 : 0);
