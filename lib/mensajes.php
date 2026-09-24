<?php
/**
 * Mensajes de una acción a la siguiente pantalla.
 *
 * Varias pantallas redirigen tras actuar y llevan el resultado en la URL
 * (&error=, &exito=), pero la plantilla que las dibuja solo muestra lo que
 * encuentra en $_SESSION. Nadie hacía el paso intermedio, así que el mensaje
 * viajaba y se perdía: al fallar "Asignar técnico" o "Rechazar solución" la
 * pantalla se recargaba igual que estaba y el gestor no tenía forma de saber
 * si había funcionado o no.
 */

if (!function_exists('mensajes_de_la_url')) {

    function mensajes_de_la_url() {
        if (!empty($_GET['error']) && empty($_SESSION['error'])) {
            $_SESSION['error'] = mb_substr((string) $_GET['error'], 0, 500);
        }

        if (!empty($_GET['exito']) && empty($_SESSION['exito'])) {
            // Algunas acciones redirigen con "exito=1", que es una señal
            // interna y no un texto que se le pueda enseñar a nadie.
            $_SESSION['exito'] = ((string) $_GET['exito'] === '1')
                ? 'Cambio guardado correctamente.'
                : mb_substr((string) $_GET['exito'], 0, 500);
        }
    }
}
