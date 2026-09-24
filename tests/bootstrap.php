<?php
// Bootstrap de pruebas. Carga la configuración real del proyecto (constantes,
// conexión a BD, helpers config()/asset_url()) para que tanto las pruebas
// unitarias puras como las de integración contra la BD local compartan un
// mismo arranque, igual que hace public/index.php en producción.

// PHPUnit carga este bootstrap desde dentro de una función interna suya, no
// desde el scope global real del script. Las asignaciones de nivel superior
// de config.php ($db_config, $smtp_config, etc.) quedarían entonces locales a
// esa función y global $db_config dentro de config() no las encontraría
// (config('db') devolvería null). config.php sí retorna esos mismos datos al
// final, así que los capturamos aquí y los escribimos directamente en
// $GLOBALS para que config() los vea sin importar desde qué scope se cargó.
$__config = require_once dirname(__DIR__) . '/configuracion/config.php';
foreach (['db_config', 'smtp_config', 'security_config', 'session_config', 'app_config', 'storage_config'] as $__clave) {
    $__seccion = str_replace('_config', '', $__clave);
    $GLOBALS[$__clave] = $__config[$__seccion];
}
unset($__config, $__clave, $__seccion);

require_once LIB_PATH . '/basedatos.php';
require_once LIB_PATH . '/encriptacion.php';
require_once LIB_PATH . '/validacion.php';
require_once LIB_PATH . '/errores.php';
require_once LIB_PATH . '/mensajes.php';
require_once __DIR__ . '/Integration/BaseDbTestCase.php';

// Las pruebas corren en modo estricto, aunque la base local y la de producción
// no lo estén.
//
// Sin esto, insertar NULL o nada en una columna NOT NULL se convierte en
// silencio en una cadena vacía y la prueba pasa; la integración continua usa
// MariaDB con STRICT_TRANS_TABLES y falla. Eso ya pasó una vez: una prueba
// verde en el equipo del desarrollador y roja al publicar.
//
// Más importante que la molestia: en producción ese mismo descuido guarda un
// reporte con el nombre del reportante vacío en vez de avisar.
try {
    BaseDatos::obtener()->ejecutar(
        "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'"
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Aviso: no se pudo activar el modo estricto: " . $e->getMessage() . "
");
}
