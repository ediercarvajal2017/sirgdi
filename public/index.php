<?php
/**
 * ANA v2.0 - Front Controller (Router)
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
require_once LIB_PATH . '/errores.php';
require_once LIB_PATH . '/mensajes.php';
require_once LIB_PATH . '/encriptacion.php';
require_once LIB_PATH . '/basedatos.php';
require_once APP_PATH . '/servicios/servicio_auditoria.php';

// Iniciar sesión segura (RN-01, RNF-05)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Red de seguridad para lo que el try/catch de abajo no puede atrapar.
 *
 * Un error fatal de PHP (memoria agotada, un require que falta, un error de
 * sintaxis en un archivo incluido) no lanza ninguna excepción: aborta el
 * proceso. Con display_errors en 0 —correcto en producción— el navegador
 * recibe un 500 con el cuerpo vacío: la pantalla en blanco.
 *
 * Esto convierte ese caso en la misma página de error que todo lo demás.
 */
register_shutdown_function(function () {
    $fatal = error_get_last();
    if ($fatal === null) return;
    if (!in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }
    responder_error_interno(sprintf(
        'Error fatal: %s en %s:%d', $fatal['message'], $fatal['file'], $fatal['line']
    ));
});

/**
 * Envío descartado por superar post_max_size.
 *
 * Cuando el cuerpo excede ese límite PHP lo tira entero: $_POST y $_FILES
 * llegan vacíos aunque el formulario venía lleno. Hay que atenderlo ANTES de
 * validar el CSRF, porque el token viaja en $_POST y desaparece con todo lo
 * demás: la respuesta era "CSRF token requerido", que no dice nada del
 * problema real. Esa es también la razón por la que la guarda de tamaño que
 * ya existía dentro del controlador de reportes no llegaba a ejecutarse nunca.
 *
 * El CSRF sigue validándose para todo lo demás. Saltárselo aquí, aunque no
 * haya datos que procesar, convertiría un POST inflado a propósito en una
 * forma de esquivarlo.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && empty($_POST) && empty($_FILES)
    && !empty($_SERVER['CONTENT_LENGTH'])) {

    error_log(sprintf(
        '[%s] POST descartado por tamaño: %s bytes (post_max_size=%s) en %s',
        date('Y-m-d H:i:s'), $_SERVER['CONTENT_LENGTH'], ini_get('post_max_size'),
        $_SERVER['REQUEST_URI'] ?? '?'
    ));

    $aviso_tamano = 'Los archivos que adjuntaste pesan demasiado en conjunto. '
                  . 'Envía el reporte con menos fotos, o sin video, y añade el resto después desde el seguimiento.';

    // El formulario público es el único punto de entrada anónimo y el que más
    // se usa: allí se devuelve a la persona a su formulario con el motivo, en
    // vez de dejarla en una página de error sin salida.
    $destino_post = ($_GET['controlador'] ?? '') . '/' . ($_GET['accion'] ?? '');
    if ($destino_post === 'reportes/procesar_crear_invitado' && !empty($_GET['inst'])) {
        header('Location: ' . config('app.url_base')
            . '/?controlador=reportes&accion=crear_invitado&inst=' . intval($_GET['inst'])
            . '&error=' . urlencode($aviso_tamano));
        exit;
    }

    responder_error(413, 'Los archivos pesan demasiado', $aviso_tamano,
        'post_max_size excedido: CONTENT_LENGTH=' . $_SERVER['CONTENT_LENGTH']);
}

// Validar CSRF en POST (excepto login/2FA/AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rutas_sin_csrf = [
        // Nota: 'autenticacion/login' y 'autenticacion/2fa' (accion=login, accion=dos_fa)
        // son solo renderizado GET del formulario — no procesan $_POST. Los handlers reales
        // (procesar_login, procesar_2fa) SÍ exigen CSRF y no están en esta lista.
        'autenticacion/recuperar_contrasena',
        'autenticacion/procesar_recuperar_contrasena',
        'autenticacion/procesar_restablecer_contrasena',
        'reportes/cargar_areas_json',
        'reportes/cargar_subareas_json',
        'reportes/cargar_subcategorias_json',
        'reportes/cargar_subcategorias_publico_json',
    ];

    $controlador_accion = ($_GET['controlador'] ?? '') . '/' . ($_GET['accion'] ?? '');
    $requiere_csrf = !in_array($controlador_accion, $rutas_sin_csrf);

    // El motivo real casi siempre es que la sesión caducó con el formulario
    // abierto, no un ataque. El mensaje lo dice, porque "CSRF token inválido"
    // no le sirve de nada a quien acaba de perder lo que escribió.
    $mensaje_csrf = 'Tu sesión caducó mientras llenabas el formulario, así que no lo enviamos. '
                  . 'Vuelve a entrar e inténtalo de nuevo.';

    if ($requiere_csrf && !isset($_POST['csrf_token'])) {
        responder_error(HTTP_FORBIDDEN, 'No pudimos enviar el formulario', $mensaje_csrf,
            'CSRF ausente en ' . $controlador_accion);
    }

    if ($requiere_csrf && isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            responder_error(HTTP_FORBIDDEN, 'No pudimos enviar el formulario', $mensaje_csrf,
                'CSRF inválido en ' . $controlador_accion);
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
    responder_no_encontrado(null, 'Controlador inexistente: ' . $controlador);
}

// Cargar controlador
require_once $controlador_archivo;

// Nombre de clase: ControladorAutenticacion, ControladorReportes, etc.
$nombre_clase = 'Controlador' . implode('', array_map('ucfirst', explode('_', $controlador)));

// Validar que la clase existe
if (!class_exists($nombre_clase)) {
    responder_error_interno('Clase controladora inexistente: ' . $nombre_clase);
}

// Instanciar controlador
$controlador_obj = new $nombre_clase();

// Validar que el método existe
$nombre_metodo = strtolower(str_replace('-', '_', $accion));
if (!method_exists($controlador_obj, $nombre_metodo)) {
    responder_no_encontrado(null, "Acción inexistente: $nombre_clase->$nombre_metodo()");
}

// Ejecutar acción.
//
// Se atrapa Throwable y no solo Exception: un TypeError o un Error de PHP 7+
// NO es una Exception, así que antes se escapaba de aquí y el usuario veía una
// página en blanco. Es justo el fallo que más aparece al cambiar una firma de
// método o al pasar null donde se esperaba un objeto.
try {
    $controlador_obj->$nombre_metodo();
} catch (Throwable $e) {
    responder_error_interno(sprintf(
        '%s en %s->%s(): %s [%s:%d]',
        get_class($e), $nombre_clase, $nombre_metodo, $e->getMessage(),
        $e->getFile(), $e->getLine()
    ));
}
