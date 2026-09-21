<?php
// Bootstrap exclusivo para PHPStan: declara las constantes de rutas que
// configuracion/config.php define en tiempo de ejecución (líneas 32-37),
// para que el análisis estático las reconozca sin tener que ejecutar
// config.php de verdad (que requiere .env, extensiones PDO/OpenSSL, etc.).
// Si cambian los valores en config.php, actualizar aquí también.

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('LIB_PATH', ROOT_PATH . '/lib');
define('CONFIG_PATH', ROOT_PATH . '/configuracion');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/almacenamiento');
