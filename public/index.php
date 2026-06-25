<?php
/**
 * SIRGDI v2.0 - Front Controller (Router)
 * Punto de entrada único para toda la aplicación
 * Todos los requests son dirigidos aquí por .htaccess
 */

// === SETUP INICIAL ===
error_reporting(E_ALL);
ini_set('display_errors', 0); // Loggear, no mostrar en navegador
ini_set('log_errors', 1);

// Determinar raíz del proyecto
$root = dirname(dirname(__FILE__));

// Cargar configuración
require_once $root . '/configuracion/config.php';

// Cargar librerías base
require_once LIB_PATH . '/encriptacion.php';
require_once LIB_PATH . '/basedatos.php';

// Iniciar sesión segura (RN-01, RNF-05)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar CSRF en POST (excepto login/2FA/AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rutas_sin_csrf = [
        'autenticacion/login',
        'autenticacion/2fa',
        'autenticacion/recuperar_contrasena',
        'autenticacion/procesar_recuperar_contrasena',
        'reportes/cargar_areas_json',
        'reportes/cargar_subareas_json',
        'reportes/cargar_subcategorias_json',
        'reportes/cargar_subcategorias_publico_json',
    ];

    $controlador_accion = ($_GET['controlador'] ?? '') . '/' . ($_GET['accion'] ?? '');
    $requiere_csrf = !in_array($controlador_accion, $rutas_sin_csrf);

    if ($requiere_csrf && !isset($_POST['csrf_token'])) {
        http_response_code(HTTP_FORBIDDEN);
        die('CSRF token requerido.');
    }

    if ($requiere_csrf && isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            http_response_code(HTTP_FORBIDDEN);
            die('CSRF token inválido.');
        }
    }
}

// === ROUTING ===
$controlador = $_GET['controlador'] ?? 'autenticacion';
$accion = $_GET['accion'] ?? 'inicio';

// Mapeo de controladores a archivos
$controlador_archivo = APP_PATH . '/controladores/controlador_' . $controlador . '.php';

// Validar que el archivo existe
if (!file_exists($controlador_archivo)) {
    http_response_code(HTTP_NOT_FOUND);
    die('Controlador no encontrado: ' . htmlspecialchars($controlador));
}

// Cargar controlador
require_once $controlador_archivo;

// Nombre de clase: ControladorAutenticacion, ControladorReportes, etc.
$nombre_clase = 'Controlador' . implode('', array_map('ucfirst', explode('_', $controlador)));

// Validar que la clase existe
if (!class_exists($nombre_clase)) {
    http_response_code(HTTP_INTERNAL_ERROR);
    die('Clase controladora no encontrada: ' . htmlspecialchars($nombre_clase));
}

// Instanciar controlador
$controlador_obj = new $nombre_clase();

// Validar que el método existe
$nombre_metodo = strtolower(str_replace('-', '_', $accion));
if (!method_exists($controlador_obj, $nombre_metodo)) {
    http_response_code(HTTP_NOT_FOUND);
    die('Acción no encontrada: ' . htmlspecialchars($nombre_metodo));
}

// Ejecutar acción
try {
    $controlador_obj->$nombre_metodo();
} catch (Exception $e) {
    // Log del error
    error_log("[" . date('Y-m-d H:i:s') . "] Error en $nombre_clase->$nombre_metodo(): " . $e->getMessage());

    if (config('app.debug')) {
        // En desarrollo, mostrar el error
        http_response_code(HTTP_INTERNAL_ERROR);
        echo '<pre>';
        echo 'Error: ' . $e->getMessage() . "\n";
        echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo '</pre>';
    } else {
        // En producción, mostrar mensaje genérico
        http_response_code(HTTP_INTERNAL_ERROR);
        die('Error interno del servidor. Por favor, intente más tarde.');
    }
}
