<?php
/**
 * Guardia de acceso para scripts de mantenimiento sueltos en la raíz del proyecto.
 *
 * Estos scripts (reset de contraseñas demo, recarga de datos, reparación de BD, etc.)
 * ejecutan operaciones destructivas o de alto privilegio y NO pasan por el front
 * controller (public/index.php), por lo que no heredan su validación CSRF ni el
 * enrutamiento. Antes de esta guardia no tenían ningún control de acceso propio:
 * solo quedaban inalcanzables como efecto colateral de la reescritura del .htaccess
 * raíz, algo que no debe asumirse como control de seguridad real.
 *
 * Uso: incluir justo después de 'configuracion/config.php', antes de cualquier
 * lógica del script:
 *
 *   require_once __DIR__ . '/configuracion/config.php';
 *   require_once __DIR__ . '/lib/guardia_mantenimiento.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once LIB_PATH . '/errores.php';
require_once LIB_PATH . '/basedatos.php';
require_once APP_PATH . '/servicios/servicio_autenticacion.php';
require_once APP_PATH . '/servicios/servicio_autorizacion.php';

$__guardia_auth = new ServicioAutenticacion();

if (!$__guardia_auth->validar_sesion_vigente()) {
    responder_prohibido('Script de mantenimiento sin sesión iniciada');
}

$__guardia_autorizacion = new ServicioAutorizacion();

if (!$__guardia_autorizacion->es_superadmin()) {
    responder_prohibido('Script de mantenimiento sin rol de Superadministrador');
}

unset($__guardia_auth, $__guardia_autorizacion);
