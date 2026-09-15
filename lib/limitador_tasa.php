<?php
/**
 * Limitador de tasa genérico basado en archivos (mismo mecanismo que el rate
 * limiting de login en ServicioAutenticacion, generalizado para proteger
 * cualquier endpoint público costoso — ej. creación de reportes de invitado —
 * contra spam/abuso automatizado.
 */

class LimitadorTasa {
    /**
     * Retorna true si $clave ya alcanzó $max_intentos dentro de $ventana_segundos.
     */
    public static function excede_limite($clave, $max_intentos, $ventana_segundos) {
        $datos = self::leer($clave);
        if (!$datos) {
            return false;
        }

        if ((time() - $datos['primera_vez']) > $ventana_segundos) {
            return false; // ventana expirada
        }

        return $datos['intentos'] >= $max_intentos;
    }

    /**
     * Registra un intento para $clave (llamar una vez por request real recibido).
     */
    public static function registrar($clave, $ventana_segundos) {
        $datos = self::leer($clave);
        $ahora = time();

        if (!$datos || ($ahora - $datos['primera_vez']) > $ventana_segundos) {
            $datos = ['intentos' => 0, 'primera_vez' => $ahora];
        }

        $datos['intentos']++;
        self::escribir($clave, $datos);
    }

    private static function ruta($clave) {
        if (!is_dir(RATE_LIMIT_DIR)) {
            @mkdir(RATE_LIMIT_DIR, 0750, true);
        }
        return RATE_LIMIT_DIR . '/generico_' . md5($clave) . '.json';
    }

    private static function leer($clave) {
        $archivo = self::ruta($clave);
        if (!file_exists($archivo)) {
            return null;
        }
        $contenido = @file_get_contents($archivo);
        return $contenido ? json_decode($contenido, true) : null;
    }

    private static function escribir($clave, $datos) {
        @file_put_contents(self::ruta($clave), json_encode($datos), LOCK_EX);
    }
}
