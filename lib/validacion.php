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

    /**
     * Verificar un token de Cloudflare Turnstile contra la API de Cloudflare.
     * Si $clave_secreta viene vacía (CAPTCHA no configurado aún), no bloquea:
     * devuelve true para no romper el formulario mientras no se active.
     */
    public static function verificar_turnstile($token, $clave_secreta, $ip_remota = null) {
        if ($clave_secreta === '' || $clave_secreta === null) {
            return true;
        }
        if (empty($token)) {
            return false;
        }

        $datos = [
            'secret'   => $clave_secreta,
            'response' => $token,
        ];
        if ($ip_remota) {
            $datos['remoteip'] = $ip_remota;
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($datos),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
        ]);
        $respuesta = curl_exec($ch);
        $fallo_red = curl_errno($ch) !== 0;
        curl_close($ch);

        if ($fallo_red || $respuesta === false) {
            // Fallo de red hacia Cloudflare: no bloquear a un usuario legítimo
            // por una caída del servicio externo.
            return true;
        }

        $resultado = json_decode($respuesta, true);
        return !empty($resultado['success']);
    }

    /**
     * Heurística ligera anti-spam para texto libre (RN de moderación de
     * reportes de invitado). No es un filtro perfecto ni pretende serlo:
     * su función es marcar reportes sospechosos para revisión prioritaria,
     * nunca bloquear el envío (evita falsos positivos contra ciudadanos
     * reales reportando un daño urgente).
     */
    public static function contiene_spam_probable($texto) {
        $texto = (string) $texto;
        if ($texto === '') {
            return false;
        }

        // 1) Demasiados enlaces
        if (preg_match_all('/\bhttps?:\/\/|\bwww\./i', $texto) >= 2) {
            return true;
        }

        // 2) Caracter repetido excesivamente (ej. "aaaaaaaaaa", "!!!!!!!!")
        if (preg_match('/(.)\1{7,}/u', $texto)) {
            return true;
        }

        // 3) Texto largo casi todo en mayúsculas
        $letras = preg_replace('/[^\p{L}]/u', '', $texto);
        if (mb_strlen($letras) >= 25) {
            $mayusculas = preg_replace('/[^\p{Lu}]/u', '', $letras);
            if (mb_strlen($mayusculas) / mb_strlen($letras) > 0.7) {
                return true;
            }
        }

        // 4) Palabras/frases típicas de spam (lista corta, ampliable)
        $patrones_spam = [
            'haz clic aqui', 'haz click aqui', 'gana dinero', 'dinero facil',
            'prestamo urgente', 'casino', 'viagra', 'oferta exclusiva',
            'compra ahora', 'suscribete', 'bit.ly', 'tinyurl',
        ];
        $normalizado = strtolower(preg_replace(
            ['/[áàä]/u', '/[éèë]/u', '/[íìï]/u', '/[óòö]/u', '/[úùü]/u'],
            ['a', 'e', 'i', 'o', 'u'],
            $texto
        ));
        foreach ($patrones_spam as $patron) {
            if (strpos($normalizado, $patron) !== false) {
                return true;
            }
        }

        return false;
    }
}
