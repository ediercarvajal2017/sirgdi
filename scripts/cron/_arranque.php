<?php
/**
 * Arranque común de los scripts programados (cron).
 *
 * Los cron se ejecutan desde la raíz del despliegue, no desde public/, así que
 * cargan la configuración por ruta absoluta calculada desde este archivo.
 *
 * Seguridad: aunque .htaccess bloquea la carpeta scripts/ por web (403), estos
 * archivos se niegan a correr fuera de la línea de comandos. Un cron no debe
 * poder dispararse con una petición HTTP.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta por línea de comandos.\n");
}

$raiz = dirname(__DIR__, 2);

require_once $raiz . '/configuracion/config.php';
require_once $raiz . '/lib/basedatos.php';

/** Escribe una línea con marca de tiempo en la salida y en el log indicado. */
function cron_log($mensaje, $archivo_log = 'cron.log') {
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
    echo $linea . "\n";

    $ruta = STORAGE_PATH . '/logs/' . $archivo_log;
    @file_put_contents($ruta, $linea . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Evita que dos ejecuciones del mismo cron se pisen si una tarda más que el
 * intervalo programado. Devuelve el recurso del candado (hay que conservarlo
 * en una variable: si se libera, el candado se suelta).
 */
function cron_candado($nombre) {
    $ruta = STORAGE_PATH . '/temp/cron_' . preg_replace('/[^a-z0-9_]/i', '', $nombre) . '.lock';
    $fp = @fopen($ruta, 'c');

    if ($fp === false) {
        cron_log("AVISO: no se pudo abrir el candado {$ruta}; se continúa sin él.");
        return null;
    }

    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        cron_log("Ya hay otra ejecución de '{$nombre}' en curso. Se omite esta.");
        exit(0);
    }

    return $fp;
}
