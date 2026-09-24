<?php
/**
 * Página de error de Apache (directiva ErrorDocument).
 *
 * La aplicación pinta sus propios errores desde index.php; esto cubre lo que
 * nunca llega a PHP: un 403 de RedirectMatch sobre las carpetas internas, o un
 * 500 emitido por el servidor antes de arrancar el intérprete. Sin esto el
 * usuario ve la página gris por defecto de Apache.
 */

require_once dirname(__DIR__) . '/configuracion/config.php';
require_once LIB_PATH . '/errores.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Apache expone el código original aquí; la cadena de consulta es el respaldo
// para servidores que no lo hacen.
$codigo = (int) ($_SERVER['REDIRECT_STATUS'] ?? $_GET['codigo'] ?? 404);
if ($codigo < 400 || $codigo > 599) {
    $codigo = 404;
}

$original = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '';

switch ($codigo) {
    case 403:
        responder_prohibido('Apache denegó el acceso a ' . $original);
        break;
    case 404:
        responder_no_encontrado(null, 'Apache no encontró ' . $original);
        break;
    case 413:
        responder_error(413, 'El archivo pesa demasiado',
            'Lo que intentaste subir supera el tamaño permitido. Reduce el tamaño de las fotos o sube menos a la vez.',
            'Cuerpo demasiado grande en ' . $original);
        break;
    default:
        responder_error_interno('Apache devolvió ' . $codigo . ' en ' . $original);
}
