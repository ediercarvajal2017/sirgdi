<?php
// Configuración global de SIRGDI
// Cargar variables de entorno desde .env (si existe)
if (file_exists(dirname(__DIR__) . '/.env')) {
    $env_vars = parse_ini_file(dirname(__DIR__) . '/.env');
    foreach ($env_vars as $key => $value) {
        putenv("$key=$value");
    }
}

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
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'SIRGDI - Reportes de Daños',
];

// === SEGURIDAD ===
$security_config = [
    // Encryption key: 32 bytes en hexadecimal (256-bit AES)
    'encryption_key' => getenv('ENCRYPTION_KEY') ?: bin2hex(random_bytes(32)),

    // JWT Secret para tokens de acceso (si se implementa)
    'jwt_secret' => getenv('JWT_SECRET') ?: bin2hex(random_bytes(32)),

    // Salt para CSRF tokens
    'csrf_salt' => getenv('CSRF_SALT') ?: bin2hex(random_bytes(16)),
];

// === SESIÓN ===
$session_config = [
    'timeout' => SESSION_TIMEOUT_SECONDS,
    'absolute_timeout' => SESSION_ABSOLUTE_TIMEOUT_SECONDS,
    'cookie_secure' => getenv('FORCE_HTTPS') === 'true' ? true : false,
    'cookie_httponly' => true,
];

// === APLICACIÓN ===
$env_actual = getenv('ENV') ?: 'development';
$app_config = [
    // En producción el debug se apaga (no mostrar errores al usuario).
    // Si DEBUG está definido en el .env, ese valor manda; si no, debug = (no es producción).
    'debug' => getenv('DEBUG') !== false
        ? filter_var(getenv('DEBUG'), FILTER_VALIDATE_BOOLEAN)
        : ($env_actual !== 'production'),
    'app_name' => 'SIRGDI v2.0',
    'version' => '2.0.0',
    'environment' => $env_actual, // development, staging, production
    'url_base' => getenv('APP_URL') ?: 'http://localhost/reporte_danos/public',
];

// === DIRECTORIO DE ALMACENAMIENTO ===
$storage_config = [
    'uploads_dir' => STORAGE_PATH . '/archivos',
    'evidencias_dir' => STORAGE_PATH . '/archivos/evidencias',
    'temp_dir' => STORAGE_PATH . '/temp',
    'logs_dir' => STORAGE_PATH . '/logs',
    'cache_dir' => STORAGE_PATH . '/cache',
];

// Crear directorios si no existen
foreach ($storage_config as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// === CONFIGURACIÓN DE RESPUESTA ===
ini_set('display_errors', $app_config['debug'] ? '1' : '0');
error_reporting(E_ALL);

// Timezone (América/Bogotá para Colombia - ajustar según región)
date_default_timezone_set(getenv('TIMEZONE') ?: 'America/Bogota');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $session_config['cookie_secure'] ? 1 : 0);
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

// === VALIDAR REQUERIMIENTOS ===
if (version_compare(PHP_VERSION, '7.4', '<')) {
    die('SIRGDI requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION);
}

if (!extension_loaded('pdo_mysql')) {
    die('SIRGDI requiere la extensión pdo_mysql.');
}

if (!extension_loaded('openssl')) {
    die('SIRGDI requiere la extensión openssl para encriptación.');
}

return [
    'db' => $db_config,
    'smtp' => $smtp_config,
    'security' => $security_config,
    'session' => $session_config,
    'app' => $app_config,
    'storage' => $storage_config,
];
