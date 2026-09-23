<?php
/**
 * ControladorNotificaciones
 *
 * Campana in-app. Existe porque el correo es un canal que puede fallar sin
 * que nadie se entere: el envío es síncrono y, si el SMTP está caído, el
 * técnico no tenía ningún otro modo de saber que le habían asignado trabajo.
 * Aquí las notificaciones se leen desde la propia aplicación, con
 * independencia de si el correo salió o no.
 */

require_once APP_PATH . '/servicios/servicio_autenticacion.php';
require_once APP_PATH . '/servicios/servicio_notificacion.php';

class ControladorNotificaciones {

    private $auth;
    private $servicio;

    public function __construct() {
        $this->auth = new ServicioAutenticacion();
        $this->servicio = new ServicioNotificacion();
    }

    /**
     * GET ?controlador=notificaciones&accion=listar
     * Devuelve las no leídas en JSON, para el desplegable de la campana.
     */
    public function listar() {
        $this->auth->requerir_autenticacion();

        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        $no_leidas = $this->servicio->obtener_no_leidas($id_usuario, $id_institucion);

        $items = [];
        foreach ($no_leidas as $n) {
            $items[] = [
                'id'      => (int) $n['id_notificacion'],
                'asunto'  => $n['asunto'],
                'reporte' => $n['id_reporte'] !== null ? (int) $n['id_reporte'] : null,
                'fecha'   => $this->hace_cuanto($n['fecha_creacion']),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'total' => count($items),
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET ?controlador=notificaciones&accion=abrir&id=N
     * Marca la notificación como leída y lleva al reporte asociado.
     * Es GET porque es un enlace de navegación, no una acción destructiva.
     */
    public function abrir() {
        $this->auth->requerir_autenticacion();

        $id_notificacion = (int) ($_GET['id'] ?? 0);
        $id_usuario = $this->auth->obtener_id_usuario();
        $id_institucion = $this->auth->obtener_id_institucion();

        if ($id_notificacion > 0) {
            // El WHERE incluye usuario e institución: no se puede marcar la de otro.
            $this->servicio->marcar_leida($id_notificacion, $id_usuario, $id_institucion);
        }

        $id_reporte = (int) ($_GET['reporte'] ?? 0);
        $destino = $id_reporte > 0
            ? '/?controlador=reportes&accion=detalle&id=' . $id_reporte
            : '/?controlador=dashboard&accion=inicio';

        header('Location: ' . config('app.url_base') . $destino);
        exit;
    }

    /**
     * POST ?controlador=notificaciones&accion=marcar_todas
     * Deja la campana en cero. Lleva CSRF porque cambia estado.
     */
    public function marcar_todas() {
        $this->auth->requerir_autenticacion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(HTTP_BAD_REQUEST);
            exit;
        }

        $this->servicio->marcar_todas_leidas(
            $this->auth->obtener_id_usuario(),
            $this->auth->obtener_id_institucion()
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    /** "hace 5 min", "hace 2 h", "hace 3 d" — más legible que una fecha completa. */
    private function hace_cuanto($fecha) {
        $t = strtotime((string) $fecha);
        if (!$t) return '';

        $segundos = max(0, time() - $t);

        if ($segundos < 60)    return 'ahora mismo';
        if ($segundos < 3600)  return 'hace ' . floor($segundos / 60) . ' min';
        if ($segundos < 86400) return 'hace ' . floor($segundos / 3600) . ' h';
        if ($segundos < 2592000) return 'hace ' . floor($segundos / 86400) . ' d';

        return date('d/m/Y', $t);
    }
}
