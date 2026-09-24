<?php
// Configuración global de ANA (Asistente de Necesidades de Ambientes Escolares)

// Cargar variables de entorno desde .env
// parse_ini_file en PHP 7+ no reconoce # como comentario y falla silenciosamente;
// usamos un parser propio que los maneja correctamente.
function _cargar_env($archivo) {
    if (!file_exists($archivo)) return;
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas === false) return;
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || $linea[0] === ';') continue;
        if (strpos($linea, '=') === false) continue;
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        // Eliminar comillas envolventes si existen
        if (strlen($valor) >= 2) {
            $q = $valor[0];
            if (($q === '"' || $q === "'") && substr($valor, -1) === $q) {
                $valor = substr($valor, 1, -1);
            }
        }
        // El entorno real manda sobre el archivo. Antes era al revés: .env
        // pisaba cualquier variable ya definida, así que en integración
        // continua o en un contenedor no había forma de apuntar a otra base
        // de datos sin editar el archivo.
        if (getenv($clave) !== false) continue;

        putenv("$clave=$valor");
        $_ENV[$clave] = $valor;
    }
}
_cargar_env(dirname(__DIR__) . '/.env');

// Definir constantes de rutas (antes de cargar constantes.php)
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', ROOT_PATH . '/app');
define('LIB_PATH', ROOT_PATH . '/lib');
define('CONFIG_PATH', ROOT_PATH . '/configuracion');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/almacenamiento');

// Cargar constantes globales
require_once LIB_PATH . '/constantes.php';

// === BASE DE DATOS ===
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'name' => getenv('DB_NAME') ?: 'sirgdi',
    'port' => getenv('DB_PORT') ?: 3306,
    'charset' => 'utf8mb4',
];

// === SMTP (Notificaciones por email) ===
$smtp_config = [
    'host' => getenv('SMTP_HOST') ?: 'smtp.hostinger.com',
    'port' => getenv('SMTP_PORT') ?: 465,
    'username' => getenv('SMTP_USER') ?: '',
    'password' => getenv('SMTP_PASS') ?: '',
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'noreply@sirgdi.local',
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'ANA - Asistente de Necesidades de Ambientes Escolares',
];

// === SEGURIDAD ===
// El nombre del entorno se resuelve aquí porque en producción se exige lo que
// en desarrollo puede faltar. Se reutiliza más abajo en $app_config.
$env_actual = getenv('ENV') ?: 'development';
$_es_produccion = ($env_actual === 'production');

// Estos tres secretos NO pueden autogenerarse. Si se regeneraran en cada
// request: los datos ya cifrados quedarían indescifrables (ENCRYPTION_KEY, que
// guarda los secretos TOTP de 2FA) y los tokens emitidos dejarían de validar de
// forma intermitente (JWT_SECRET, CSRF_SALT), lo que se manifiesta como sesiones
// que "se caen solas" sin ningún error. Mejor fallar fuerte y claro al arrancar.
// Se comprueba también el formato, no solo que la variable exista.
//
// ENCRYPTION_KEY tiene que ser 64 caracteres hexadecimales, porque
// Encriptacion la pasa por hex2bin() y exige 32 bytes. Antes bastaba con que
// no estuviera vacía: una clave de 32 caracteres arrancaba sin queja y
// reventaba con "Encryption key must be 32 bytes" la primera vez que alguien
// usaba el 2FA. Fallar al arrancar es mucho mejor que fallar ahí.
$_secretos_obligatorios = [
    'ENCRYPTION_KEY' => [
        'bytes'   => 32,
        'valida'  => function ($v) { return (bool) preg_match('/^[0-9a-fA-F]{64}$/', $v); },
        'exige'   => '64 caracteres hexadecimales (32 bytes)',
    ],
    'JWT_SECRET' => [
        'bytes'   => 32,
        'valida'  => function ($v) { return strlen($v) >= 32; },
        'exige'   => 'al menos 32 caracteres',
    ],
    'CSRF_SALT' => [
        'bytes'   => 16,
        'valida'  => function ($v) { return strlen($v) >= 16; },
        'exige'   => 'al menos 16 caracteres',
    ],
];
$_secretos = [];
$_faltantes = [];
foreach ($_secretos_obligatorios as $_clave => $_regla) {
    $_valor = getenv($_clave);
    $_como = 'genérala con: php -r "echo bin2hex(random_bytes(' . $_regla['bytes'] . '));"';

    if ($_valor === false || $_valor === '') {
        $_faltantes[] = $_clave . ' falta (' . $_como . ')';
        continue;
    }
    if (!$_regla['valida']($_valor)) {
        $_faltantes[] = $_clave . ' no sirve: exige ' . $_regla['exige'] . ' (' . $_como . ')';
        continue;
    }
    $_secretos[$_clave] = $_valor;
}
if ($_faltantes) {
    die('Configuración incompleta en el archivo .env de la raíz del proyecto. '
        . implode(' | ', $_faltantes));
}

// CAPTCHA (Cloudflare Turnstile) del formulario público de reporte de invitado.
$_turnstile_site   = getenv('TURNSTILE_SITE_KEY') ?: '';
$_turnstile_secret = getenv('TURNSTILE_SECRET_KEY') ?: '';

// En producción el CAPTCHA es obligatorio: si falta, el formulario público
// queda abierto a bots y nada lo indica en pantalla, así que el despliegue
// parecería correcto mientras no lo es.
if ($_es_produccion && ($_turnstile_site === '' || $_turnstile_secret === '')) {
    die('Configuración incompleta: en producción TURNSTILE_SITE_KEY y '
        . 'TURNSTILE_SECRET_KEY son obligatorias porque protegen el formulario '
        . 'público de reportes. Se obtienen en el panel de Cloudflare Turnstile.');
}

$security_config = [
    // Encryption key: 32 bytes en hexadecimal (256-bit AES)
    'encryption_key' => $_secretos['ENCRYPTION_KEY'],

    // JWT Secret para tokens de acceso (si se implementa)
    'jwt_secret' => $_secretos['JWT_SECRET'],

    // Salt para CSRF tokens
    'csrf_salt' => $_secretos['CSRF_SALT'],

    // Fuera de producción pueden quedar vacías: el widget no se muestra y la
    // verificación se omite, para no estorbar las pruebas locales.
    'turnstile_site_key'   => $_turnstile_site,
    'turnstile_secret_key' => $_turnstile_secret,
];

// === SESIÓN ===
// cookie_secure: activo si HTTPS real está presente O si se fuerza por env
$_https_activo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? 80) == 443
    || getenv('FORCE_HTTPS') === 'true';

$session_config = [
    'timeout' => SESSION_TIMEOUT_SECONDS,
    'absolute_timeout' => SESSION_ABSOLUTE_TIMEOUT_SECONDS,
    'cookie_secure' => $_https_activo,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
];

// === APLICACIÓN ===
// $env_actual ya se resolvió en el bloque de seguridad.
$app_config = [
    // En producción el debug se apaga (no mostrar errores al usuario).
    // Si DEBUG está definido en el .env, ese valor manda; si no, debug = (no es producción).
    'debug' => getenv('DEBUG') !== false
        ? filter_var(getenv('DEBUG'), FILTER_VALIDATE_BOOLEAN)
        : ($env_actual !== 'production'),
    // Identidad del producto. Todo texto visible que nombre la aplicación
    // debe salir de aquí, nunca escribirse en duro en vistas o controladores.
    'app_name' => 'ANA',
    'app_full_name' => 'Asistente de Necesidades de Ambientes Escolares',
    'app_name_version' => 'ANA v2.0',
    'version' => '2.0.0',
    'environment' => $env_actual, // development, staging, production
    'url_base' => getenv('APP_URL') ?: 'http://localhost/reporte_danos/public',

    // A quién escribe un usuario que se atasca. Se resuelve en el mismo orden
    // que usan las alertas de los cron, para no tener dos correos de soporte
    // distintos conviviendo sin que nadie sepa cuál está vigente.
    'correo_soporte' => getenv('SOPORTE_EMAIL')
        ?: (getenv('ALERTA_EMAIL')
        ?: (getenv('BACKUP_ALERTA_EMAIL')
        ?: (getenv('SMTP_FROM_EMAIL') ?: 'soporte@jlcserviciosintegrales.com'))),
];

// === DIRECTORIO DE ALMACENAMIENTO ===
$storage_config = [
    'uploads_dir' => STORAGE_PATH . '/archivos',
    'evidencias_dir' => STORAGE_PATH . '/archivos/evidencias',
    'temp_dir' => STORAGE_PATH . '/temp',
    'logs_dir' => STORAGE_PATH . '/logs',
    'cache_dir' => STORAGE_PATH . '/cache',
];

// Crear directorios si no existen (0750: sin lectura para "otros")
foreach ($storage_config as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
}

// === CONFIGURACIÓN DE RESPUESTA ===
ini_set('display_errors', $app_config['debug'] ? '1' : '0');
error_reporting(E_ALL);

// Timezone (América/Bogotá para Colombia - ajustar según región)
date_default_timezone_set(getenv('TIMEZONE') ?: 'America/Bogota');

// Configuración de sesión (sección 3.1 seguridad)
session_name('SIRGDI_SESS');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $session_config['cookie_secure'] ? 1 : 0);
ini_set('session.cookie_samesite', $session_config['cookie_samesite']);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', $session_config['timeout']);
session_save_path(STORAGE_PATH . '/sesiones');

// === FUNCIÓN HELPER: Obtener configuración ===
function config($key, $default = null) {
    global $db_config, $smtp_config, $security_config, $session_config, $app_config, $storage_config;

    $parts = explode('.', $key);
    $section = array_shift($parts);

    $config = [
        'db' => $db_config,
        'smtp' => $smtp_config,
        'security' => $security_config,
        'session' => $session_config,
        'app' => $app_config,
        'storage' => $storage_config,
    ];

    if (!isset($config[$section])) {
        return $default;
    }

    $value = $config[$section];
    foreach ($parts as $part) {
        if (!isset($value[$part])) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

// === FUNCIÓN HELPER: URL de un asset propio (css/js) con versión ===
// Añade ?v=<fecha de modificación> para que cada despliegue que cambie el
// archivo produzca una URL nueva. Sin esto, el navegador y el CDN del hosting
// (Cache-Control de 30 días) siguen sirviendo la versión anterior.
function asset_url($ruta) {
    $ruta = ltrim($ruta, '/');
    $archivo = PUBLIC_PATH . '/' . $ruta;
    $version = is_file($archivo) ? filemtime($archivo) : null;
    return config('app.url_base') . '/' . $ruta . ($version ? '?v=' . $version : '');
}

// === VALIDAR REQUERIMIENTOS ===
if (version_compare(PHP_VERSION, '7.4', '<')) {
    die('ANA requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION);
}

if (!extension_loaded('pdo_mysql')) {
    die('ANA requiere la extensión pdo_mysql.');
}

if (!extension_loaded('openssl')) {
    die('ANA requiere la extensión openssl para encriptación.');
}

return [
    'db' => $db_config,
    'smtp' => $smtp_config,
    'security' => $security_config,
    'session' => $session_config,
    'app' => $app_config,
    'storage' => $storage_config,
];
