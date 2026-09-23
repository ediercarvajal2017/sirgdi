<?php
/**
 * Cron: revisión diaria de salud de la plataforma.
 *
 * Por qué existe
 * --------------
 * Hasta ahora, la única forma de saber que algo había fallado era entrar por
 * SSH y mirar los archivos de log a mano. Los errores de base de datos, los
 * correos que no salieron y las instituciones mal configuradas se acumulaban
 * en silencio. La única alerta automática del proyecto era la de fallo de
 * respaldo, y esa solo se dispara si el respaldo llega a ejecutarse: si el
 * cron desaparece, nadie se entera de nada.
 *
 * Qué hace
 * --------
 * Reúne los problemas de las últimas 24 horas y manda UN correo si hay algo
 * que mirar. Si todo está bien no escribe a nadie: una alerta que llega todos
 * los días se deja de leer.
 *
 * Programación sugerida (hPanel -> Cron Jobs), una vez al día:
 *   30 7 * * * /usr/bin/php RUTA/scripts/cron/revisar_salud.php
 */

require_once __DIR__ . '/_arranque.php';
require_once APP_PATH . '/servicios/servicio_notificacion.php';

$candado = cron_candado('revisar_salud');
$log = 'cron_salud.log';

cron_log('=== Inicio revisión de salud ===', $log);

$bd = BaseDatos::obtener();
$problemas = [];

// ── 1. Correos que no se pudieron entregar ──────────────────────────────────
$fila = $bd->obtener_uno(
    "SELECT COUNT(*) AS n FROM notificacion
     WHERE estado_envio = 'fallido' AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
);
if ((int)($fila['n'] ?? 0) > 0) {
    $problemas[] = [
        'titulo' => 'Avisos que no llegaron a su destinatario',
        'detalle' => $fila['n'] . ' notificación(es) quedaron como fallidas en las últimas 24 horas. '
            . 'El motivo concreto está en la columna razon_fallo de la tabla notificacion.',
    ];
}

// ── 2. Errores de base de datos ─────────────────────────────────────────────
$ruta_log_bd = STORAGE_PATH . '/logs/database_errors.log';
if (is_file($ruta_log_bd)) {
    $desde = date('Y-m-d', strtotime('-1 day'));
    $hoy   = date('Y-m-d');
    $errores = 0;

    foreach (file($ruta_log_bd, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $linea, $m)
            && ($m[1] === $hoy || $m[1] === $desde)) {
            $errores++;
        }
    }

    if ($errores > 0) {
        $problemas[] = [
            'titulo' => 'Errores de base de datos',
            'detalle' => $errores . ' error(es) registrados en las últimas 24 horas en '
                . 'almacenamiento/logs/database_errors.log.',
        ];
    }
}

// ── 3. Instituciones que no pueden recibir alertas ──────────────────────────
$sin_destinatarios = $bd->obtener_todos(
    "SELECT i.id_institucion, i.nombre
     FROM institucion i
     WHERE i.es_activa = 1
       AND NOT EXISTS (
         SELECT 1 FROM usuario u
         JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         JOIN rol r ON r.id_rol = ur.id_rol
         WHERE u.id_institucion = i.id_institucion AND u.activo = 1
           AND r.nombre_rol IN ('Gestor', 'Rector')
       )"
);
if ($sin_destinatarios) {
    $nombres = array_map(fn($i) => $i['nombre'] . ' (#' . $i['id_institucion'] . ')', $sin_destinatarios);
    $problemas[] = [
        'titulo' => 'Instituciones sin Gestor ni Rector',
        'detalle' => 'No hay a quién avisar cuando un SLA está por vencer: '
            . implode(', ', $nombres) . '.',
    ];
}

// ── 4. Reportes abiertos sin técnico desde hace más de 3 días ───────────────
$fila = $bd->obtener_uno(
    "SELECT COUNT(*) AS n FROM reporte r
     JOIN estado e ON e.id_estado = r.id_estado
     WHERE e.es_terminal = 0 AND r.id_tecnico_asignado IS NULL
       AND r.fecha_hora_registro < DATE_SUB(NOW(), INTERVAL 3 DAY)"
);
if ((int)($fila['n'] ?? 0) > 0) {
    $problemas[] = [
        'titulo' => 'Reportes sin asignar',
        'detalle' => $fila['n'] . ' reporte(s) llevan más de 3 días abiertos sin técnico asignado.',
    ];
}

// ── 5. ¿Sigue corriendo el respaldo? ────────────────────────────────────────
// La alerta de respaldo solo salta si el script se ejecuta y falla. Si el cron
// se borra o el servidor cambia, no avisa nadie. Esto sí lo detecta.
$dir_backups = getenv('HOME') . '/backups/db';
if (is_dir($dir_backups)) {
    $mas_reciente = 0;
    foreach (glob($dir_backups . '/*.enc') ?: [] as $archivo) {
        $mas_reciente = max($mas_reciente, filemtime($archivo));
    }

    $horas = $mas_reciente ? floor((time() - $mas_reciente) / 3600) : null;

    if ($horas === null) {
        $problemas[] = [
            'titulo' => 'No hay ningún respaldo',
            'detalle' => 'La carpeta de respaldos existe pero está vacía.',
        ];
    } elseif ($horas > 48) {
        $problemas[] = [
            'titulo' => 'El respaldo lleva demasiado tiempo sin ejecutarse',
            'detalle' => "El respaldo más reciente tiene {$horas} horas. "
                . 'Revisar la tarea programada de respaldo en hPanel.',
        ];
    }
}

// ── Resultado ───────────────────────────────────────────────────────────────
if (!$problemas) {
    cron_log('Todo en orden: no se envía ninguna alerta.', $log);
    cron_log('=== Fin ===', $log);
    exit(0);
}

foreach ($problemas as $p) {
    cron_log('  PROBLEMA: ' . $p['titulo'] . ' — ' . $p['detalle'], $log);
}

$filas_html = '';
foreach ($problemas as $p) {
    $filas_html .= '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">'
        . '<strong style="color:#b91c1c;">' . htmlspecialchars($p['titulo']) . '</strong><br>'
        . '<span style="color:#4b5563;font-size:14px;">' . htmlspecialchars($p['detalle']) . '</span>'
        . '</td></tr>';
}

$app = htmlspecialchars(config('app.app_name'));
$url = htmlspecialchars(config('app.url_base'));
$cuerpo = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
    . '<body style="font-family:Arial,sans-serif;background:#f3f4f6;padding:24px;">'
    . '<div style="max-width:620px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;">'
    . '<div style="background:#1f2937;color:#fff;padding:18px 20px;">'
    . '<strong style="font-size:16px;">' . $app . ' — revisión diaria</strong></div>'
    . '<p style="padding:16px 20px 0;color:#374151;font-size:14px;">'
    . 'Se detectaron ' . count($problemas) . ' asunto(s) que requieren atención:</p>'
    . '<table style="width:100%;border-collapse:collapse;">' . $filas_html . '</table>'
    . '<p style="padding:16px 20px;color:#6b7280;font-size:12px;">'
    . 'Generado automáticamente por scripts/cron/revisar_salud.php en ' . $url . '. '
    . 'Si todo está en orden, este correo no se envía.</p>'
    . '</div></body></html>';

$notificacion = new ServicioNotificacion();
$enviado = $notificacion->enviar_alerta_administrador(
    '[' . config('app.app_name') . '] ' . count($problemas) . ' asunto(s) requieren atención',
    $cuerpo
);

cron_log($enviado ? 'Alerta enviada al administrador.' : 'NO se pudo enviar la alerta.', $log);
cron_log('=== Fin ===', $log);

exit($enviado ? 0 : 1);
