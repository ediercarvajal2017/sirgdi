<?php
// Librería de encriptación para AES-256 y TOTP (2FA)

class Encriptacion {
    private $clave_encriptacion;

    public function __construct($clave_hex = null) {
        if ($clave_hex === null) {
            $clave_hex = getenv('ENCRYPTION_KEY');
        }
        // Clave debe ser 32 bytes (256 bits) en hexadecimal
        $this->clave_encriptacion = hex2bin($clave_hex);
        if (strlen($this->clave_encriptacion) !== 32) {
            throw new Exception('Encryption key must be 32 bytes (256-bit).');
        }
    }

    /**
     * Encriptar string con AES-256-CBC
     */
    public function encriptar($texto) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encriptado = openssl_encrypt($texto, 'aes-256-cbc', $this->clave_encriptacion, OPENSSL_RAW_DATA, $iv);
        // Retornar IV + ciphertext codificado en base64
        return base64_encode($iv . $encriptado);
    }

    /**
     * Desencriptar string con AES-256-CBC
     */
    public function desencriptar($texto_encriptado) {
        $datos = base64_decode($texto_encriptado);
        $iv_len = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($datos, 0, $iv_len);
        $ciphertext = substr($datos, $iv_len);

        $desencriptado = openssl_decrypt($ciphertext, 'aes-256-cbc', $this->clave_encriptacion, OPENSSL_RAW_DATA, $iv);
        if ($desencriptado === false) {
            throw new Exception('Decryption failed. Data may be corrupted or key incorrect.');
        }
        return $desencriptado;
    }

    /**
     * Generar secreto TOTP (RFC 6238) para 2FA
     * Retorna un string base32 de 32 caracteres (~160 bits)
     */
    public static function generar_secreto_totp() {
        $bytes = random_bytes(20); // 160 bits
        return self::base32_encode($bytes);
    }

    /**
     * Validar código TOTP (6 dígitos, válido por 30 segundos)
     * RFC 6238: TOTP time-step = 30 seconds
     */
    public static function validar_totp($secreto, $codigo, $ventana = 1) {
        $secreto = self::base32_decode($secreto);
        $codigo = intval($codigo);
        $ahora = time();

        for ($i = -$ventana; $i <= $ventana; $i++) {
            $timestamp = intval($ahora / 30) + $i;
            $hmac = hash_hmac('SHA1', pack('N*', 0, $timestamp), $secreto, true);
            $offset = ord($hmac[19]) & 0x0F;
            $otp = (((ord($hmac[$offset]) & 0x7F) << 24) |
                   ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
                   ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
                   (ord($hmac[$offset + 3]) & 0xFF)) % 1000000;

            if ($otp === $codigo) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generar código TOTP actual (útil para testing)
     */
    public static function generar_codigo_totp($secreto) {
        $secreto = self::base32_decode($secreto);
        $timestamp = intval(time() / 30);
        $hmac = hash_hmac('SHA1', pack('N*', 0, $timestamp), $secreto, true);
        $offset = ord($hmac[19]) & 0x0F;
        $otp = (((ord($hmac[$offset]) & 0x7F) << 24) |
               ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
               ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
               (ord($hmac[$offset + 3]) & 0xFF)) % 1000000;
        return str_pad($otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Codificar a base32 (RFC 4648)
     */
    private static function base32_encode($input) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $v = ($v << 8) | ord($input[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }

        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }

        return $output;
    }

    /**
     * Decodificar base32 (RFC 4648)
     */
    private static function base32_decode($input) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $c = strpos($alphabet, $input[$i]);
            if ($c === false) {
                throw new Exception('Invalid base32 character: ' . $input[$i]);
            }
            $v = ($v << 5) | $c;
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }

        return $output;
    }
}
