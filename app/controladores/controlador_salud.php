<?php
/**
 * Comprobación de disponibilidad para el monitor externo.
 *
 * Existe porque nada avisaba si el sitio se caía: las alertas que ya había
 * (respaldo, revisión diaria de salud) corren en el mismo servidor, y si el
 * servidor cae, caen con él. El monitor vive fuera —en GitHub Actions, ver
 * .github/workflows/monitor.yml— y consulta esta ruta cada pocos minutos.
 *
 * No basta con que la portada responda: una portada puede salir aunque la base
 * de datos esté caída. Por eso se hace una consulta real.
 *
 * Es pública y por eso no dice nada más que "ok" o "no": ni versiones, ni
 * tiempos, ni el motivo del fallo. El motivo queda en el log del servidor.
 */

class ControladorSalud
{
    public function ping()
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');

        try {
            $valor = BaseDatos::obtener()->obtener_valor('SELECT 1');
            if ((int) $valor !== 1) {
                throw new Exception('SELECT 1 devolvió ' . var_export($valor, true));
            }
        } catch (Throwable $e) {
            error_log('Salud: la base de datos no responde: ' . $e->getMessage());
            http_response_code(503);
            echo 'no';
            exit;
        }

        echo 'ok';
        exit;
    }
}
