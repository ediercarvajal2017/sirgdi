<?php
// Librería de validación de entrada (server-side)

class Validacion {
    /**
     * Validar email
     */
    public static function validar_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validar URL
     */
    public static function validar_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validar números enteros
     */
    public static function validar_entero($valor) {
        return filter_var($valor, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validar números flotantes
     */
    public static function validar_flotante($valor) {
        return filter_var($valor, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Validar que no esté vacío
     */
    public static function validar_requerido($valor) {
        return trim($valor) !== '';
    }

    /**
     * Validar longitud mínima
     */
    public static function validar_minimo($valor, $minimo) {
        return strlen($valor) >= $minimo;
    }

    /**
     * Validar longitud máxima
     */
    public static function validar_maximo($valor, $maximo) {
        return strlen($valor) <= $maximo;
    }

    /**
     * Validar que coincida con regex
     */
    public static function validar_regex($valor, $patron) {
        return preg_match($patron, $valor) === 1;
    }

    /**
     * Validar contraseña (RNF-02)
     * Requisitos: mín 8 caracteres, mayúscula, minúscula, número
     */
    public static function validar_contrasena($contrasena) {
        // Mínimo 8 caracteres
        if (strlen($contrasena) < 8) {
            return false;
        }

        // Al menos una mayúscula
        if (!preg_match('/[A-Z]/', $contrasena)) {
            return false;
        }

        // Al menos una minúscula
        if (!preg_match('/[a-z]/', $contrasena)) {
            return false;
        }

        // Al menos un número
        if (!preg_match('/[0-9]/', $contrasena)) {
            return false;
        }

        return true;
    }

    /**
     * Sanitizar entrada: remover etiquetas HTML y espacios extras
     */
    public static function sanitizar_texto($texto) {
        $texto = strip_tags($texto);
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        return trim($texto);
    }

    /**
     * Sanitizar email
     */
    public static function sanitizar_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Escapar para SQL (aunque debería usarse prepared statements)
     * NOTA: Preferir prepared statements en basedatos.php
     */
    public static function escapar_sql($valor) {
        if (is_array($valor)) {
            return array_map([self::class, 'escapar_sql'], $valor);
        }

        if (is_numeric($valor)) {
            return $valor;
        }

        return addslashes($valor);
    }

    /**
     * Validar MIME type de archivo
     */
    public static function validar_mime_type($archivo_tmp, $mime_types_permitidos) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo_tmp);
        finfo_close($finfo);

        return in_array($mime_type, $mime_types_permitidos);
    }

    /**
     * Validar tamaño de archivo
     */
    public static function validar_tamano_archivo($archivo_tmp, $tamanio_maximo_bytes) {
        return filesize($archivo_tmp) <= $tamanio_maximo_bytes;
    }

    /**
     * Validar fecha en formato YYYY-MM-DD
     */
    public static function validar_fecha($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    /**
     * Validar rango de números
     */
    public static function validar_rango($valor, $minimo, $maximo) {
        $valor = intval($valor);
        return $valor >= $minimo && $valor <= $maximo;
    }

    /**
     * Validar UUID (RFC 4122)
     */
    public static function validar_uuid($uuid) {
        $patron = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($patron, $uuid) === 1;
    }

    /**
     * Validar que el campo sea un valor permitido (enum-like)
     */
    public static function validar_enum($valor, $valores_permitidos) {
        return in_array($valor, $valores_permitidos, true);
    }

    /**
     * Validar CSRF token (se usa en index.php)
     */
    public static function validar_csrf_token($token_enviado) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token_enviado);
    }

    /**
     * Generar CSRF token
     */
    public static function generar_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
