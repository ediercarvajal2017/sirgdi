<?php
/**
 * Páginas de error con diseño.
 *
 * Reemplaza a die('texto plano'), que dejaba al usuario en una pantalla blanca
 * sin estilos, sin explicación y sin forma de volver: el punto en que la gente
 * abandona y llama por teléfono.
 *
 * Tres reglas que justifican que esto sea una función y no un echo suelto:
 *
 *  1. El detalle técnico nunca llega al navegador en producción. Se escribe en
 *     el log junto a un código de referencia corto que sí se muestra, para que
 *     el usuario pueda citarlo al pedir soporte. Antes, siete die() imprimían
 *     la ruta absoluta del disco del servidor.
 *  2. Si quien pide es JavaScript (fetch/XHR) responde JSON, no HTML. Una
 *     página de error dentro de un JSON.parse() produce un fallo peor que el
 *     original.
 *  3. Limpia los búferes de salida antes de pintar. Un error a mitad de una
 *     vista dejaba media página renderizada y el error encima.
 */

if (!function_exists('error_referencia')) {

    /** Código corto e irrepetible para correlacionar pantalla y log. */
    function error_referencia() {
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * ¿Quien pide espera JSON?
     *
     * Se mira primero cómo llegó la petición (cabeceras), que es lo general, y
     * como último recurso el nombre de la acción.
     */
    function error_espera_json() {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return true;
        }
        $acepta = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($acepta, 'application/json') !== false && stripos($acepta, 'text/html') === false) {
            return true;
        }

        // Convención del proyecto: las acciones que acaban en _json responden
        // JSON. Los fetch() de la aplicación no mandan X-Requested-With ni
        // acotan el Accept, así que sin esto una página de error acabaría
        // dentro de un JSON.parse().
        $accion = (string) ($_GET['accion'] ?? '');
        if ($accion !== '' && substr($accion, -5) === '_json') {
            return true;
        }

        return false;
    }

    /**
     * Enlace de vuelta seguro.
     *
     * Solo se acepta un referente del propio sitio: un Referer externo
     * convertiría la página de error en un redirector abierto.
     */
    function error_enlace_volver() {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref === '') return null;

        $host_ref = parse_url($ref, PHP_URL_HOST);
        if (!$host_ref) return null;

        $host_actual = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        if (strcasecmp($host_ref, $host_actual) !== 0) return null;

        // Volver a la misma URL que falló repetiría el error.
        $actual = ($_SERVER['REQUEST_URI'] ?? '');
        if ($actual !== '' && strpos($ref, $actual) !== false) return null;

        return $ref;
    }

    /**
     * Termina la petición mostrando una página de error.
     *
     * @param int         $codigo   Código HTTP (404, 403, 500, ...).
     * @param string      $titulo   Titular corto, en lenguaje de usuario.
     * @param string      $mensaje  Qué pasó y qué puede hacer. Se muestra.
     * @param string|null $detalle  Detalle técnico. Solo al log (y a pantalla
     *                              si app.debug está activo).
     */
    function responder_error($codigo, $titulo, $mensaje, $detalle = null) {
        $referencia = error_referencia();

        if ($detalle !== null && $detalle !== '') {
            error_log(sprintf(
                '[%s] error %d ref=%s uri=%s :: %s',
                date('Y-m-d H:i:s'), $codigo, $referencia,
                $_SERVER['REQUEST_URI'] ?? 'cli', $detalle
            ));
        }

        // Descartar lo que se hubiera pintado antes de fallar.
        while (ob_get_level() > 0) { @ob_end_clean(); }

        if (!headers_sent()) {
            http_response_code($codigo);
            header('X-Content-Type-Options: nosniff');
            // Una página de error nunca se cachea: el siguiente intento puede ir bien.
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        $mostrar_detalle = $detalle !== null && $detalle !== '' && function_exists('config') && config('app.debug');

        if (error_espera_json()) {
            if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
            $carga = ['error' => $mensaje, 'referencia' => $referencia];
            if ($mostrar_detalle) $carga['detalle'] = $detalle;
            echo json_encode($carga, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');

        $datos_error = [
            'codigo'          => (int) $codigo,
            'titulo'          => $titulo,
            'mensaje'         => $mensaje,
            'referencia'      => $referencia,
            'detalle'         => $mostrar_detalle ? $detalle : null,
            'enlace_volver'   => error_enlace_volver(),
        ];

        $vista = APP_PATH . '/vistas/comunes/vista_error.php';
        if (is_readable($vista)) {
            extract($datos_error);
            require $vista;
        } else {
            // Último recurso: que al menos se lea algo si falta la vista.
            echo '<!doctype html><meta charset="utf-8"><title>' . htmlspecialchars($titulo)
               . '</title><h1>' . htmlspecialchars($titulo) . '</h1><p>'
               . htmlspecialchars($mensaje) . '</p><p>Referencia: ' . $referencia . '</p>';
        }
        exit;
    }

    /** 403 con el mensaje estándar del sistema. */
    function responder_prohibido($detalle = null) {
        responder_error(
            403,
            'No tienes permiso para esto',
            'Tu cuenta no tiene acceso a esta sección. Si crees que deberías tenerlo, pídeselo al administrador de tu institución.',
            $detalle
        );
    }

    /** 404 genérico. */
    function responder_no_encontrado($mensaje = null, $detalle = null) {
        responder_error(
            404,
            'No encontramos esa página',
            $mensaje ?: 'El enlace puede estar mal escrito, o el contenido ya no existe.',
            $detalle
        );
    }

    /** 500 genérico: el detalle se registra, nunca se muestra en producción. */
    function responder_error_interno($detalle = null) {
        responder_error(
            500,
            'Algo falló de nuestro lado',
            'No pudimos completar la operación. El equipo ya tiene el registro del fallo. Vuelve a intentarlo en unos minutos.',
            $detalle
        );
    }

    /** 400: la petición no trae lo que hace falta. */
    function responder_peticion_invalida($mensaje, $detalle = null) {
        responder_error(400, 'Falta información', $mensaje, $detalle);
    }

    /**
     * 405: se pidió por GET algo que solo se procesa por POST.
     *
     * En la práctica esto pasa cuando alguien pega en la barra de direcciones
     * la URL de un formulario, o cuando vuelve atrás tras enviarlo. No es un
     * fallo del sistema, así que el mensaje explica qué hacer en su lugar.
     */
    function responder_metodo_no_permitido($detalle = null) {
        responder_error(
            405,
            'Esta dirección no se abre directamente',
            'Llegaste aquí por un enlace directo, pero esta acción solo funciona desde el botón de la pantalla anterior.',
            $detalle ?: ('Método ' . ($_SERVER['REQUEST_METHOD'] ?? '?') . ' no permitido')
        );
    }

    /** 409: la operación ya no aplica porque el dato cambió de estado. */
    function responder_conflicto($mensaje, $detalle = null) {
        responder_error(409, 'Esto ya cambió', $mensaje, $detalle);
    }
}
