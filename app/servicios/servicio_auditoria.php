<?php
/**
 * Servicio de Auditoría — registro de acciones en la tabla registro_auditoria.
 *
 * Único punto de escritura de la auditoría. Cada evento guarda quién lo hizo,
 * desde dónde, sobre qué entidad y, cuando aplica, el estado anterior y el nuevo.
 *
 * Uso:
 *   ServicioAuditoria::registrar('asignar_tecnico', 'reporte', $id_reporte,
 *       ['id_tecnico' => null], ['id_tecnico' => 7]);
 *
 * Nunca lanza excepciones hacia el llamador: un fallo al auditar se anota en el
 * archivo de log y el flujo de negocio continúa.
 */
class ServicioAuditoria {

    /**
     * @param string     $accion     Verbo corto: crear_reporte, asignar_tecnico, login...
     * @param string     $entidad    Tabla o concepto afectado: reporte, usuario, sesion...
     * @param int|null   $id_entidad Clave del registro afectado, si existe.
     * @param array|null $antes      Estado previo (solo campos relevantes).
     * @param array|null $despues    Estado nuevo (solo campos relevantes).
     * @param array      $opciones   id_usuario / id_institucion para sobrescribir los de sesión
     *                               (p. ej. login antes de abrir sesión, o eventos globales con
     *                               id_institucion => null).
     */
    public static function registrar($accion, $entidad, $id_entidad = null, $antes = null, $despues = null, array $opciones = []) {
        try {
            $id_usuario = array_key_exists('id_usuario', $opciones)
                ? $opciones['id_usuario']
                : ($_SESSION['id_usuario'] ?? null);
            $id_institucion = array_key_exists('id_institucion', $opciones)
                ? $opciones['id_institucion']
                : ($_SESSION['id_institucion'] ?? null);

            require_once LIB_PATH . '/basedatos.php';
            $bd = BaseDatos::obtener();
            $bd->insertar('registro_auditoria', [
                'id_institucion'        => $id_institucion !== null ? (int)$id_institucion : null,
                'id_usuario'            => $id_usuario !== null ? (int)$id_usuario : null,
                'accion'                => mb_substr((string)$accion, 0, 80),
                'entidad'               => mb_substr((string)$entidad, 0, 50),
                'id_entidad'            => $id_entidad !== null ? (int)$id_entidad : null,
                'datos_anteriores_json' => self::json($antes),
                'datos_nuevos_json'     => self::json($despues),
                'ip_origen'             => self::ip(),
                'user_agent'            => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Throwable $e) {
            self::log_archivo("ERROR al auditar '{$accion}' sobre {$entidad}#{$id_entidad}: " . $e->getMessage());
        }
    }

    private static function json($datos) {
        if ($datos === null || $datos === []) return null;
        $j = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $j === false ? null : $j;
    }

    /**
     * IP real del cliente. En producción hay un CDN delante (Hostinger), así que
     * REMOTE_ADDR suele ser la del proxy; se prefieren las cabeceras de reenvío.
     */
    private static function ip() {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return mb_substr($ip, 0, 45);
            }
        }
        return null;
    }

    private static function log_archivo($msg) {
        if (!defined('AUDIT_LOG')) return;
        if (!is_dir(dirname(AUDIT_LOG))) @mkdir(dirname(AUDIT_LOG), 0755, true);
        @file_put_contents(AUDIT_LOG, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
    }
}
