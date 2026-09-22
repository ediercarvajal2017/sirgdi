<?php
// Imprime las credenciales de BD del entorno real (el mismo configuracion/config.php
// que usa la aplicación) como sentencias `export` listas para `source` en bash.
// Se ejecuta solo dentro de backup_sirgdi.sh en el servidor; su salida nunca debe
// quedar en un log ni mostrarse en pantalla.
//
// Uso: php backup_db_env.php <ruta_raiz_del_sitio>

if ($argc < 2) {
    fwrite(STDERR, "Uso: php backup_db_env.php <ruta_raiz_del_sitio>\n");
    exit(1);
}

$config = require rtrim($argv[1], '/') . '/configuracion/config.php';
$db = $config['db'];

function shell_export($nombre, $valor) {
    $valor = str_replace("'", "'\\''", (string) $valor);
    echo "export $nombre='$valor'\n";
}

shell_export('DB_HOST', $db['host']);
shell_export('DB_PORT', $db['port']);
shell_export('DB_NAME', $db['name']);
shell_export('DB_USER', $db['user']);
shell_export('DB_PASS', $db['pass']);
