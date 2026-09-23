<?php
/**
 * ControladorInstitucion
 *
 * Sirve los archivos públicos de la institución (hoy: el logo).
 *
 * Los logos se guardan fuera de public/ porque el despliegue automático
 * reemplaza esa carpeta en cada push (ver servicio_archivos_institucion.php).
 * Al no ser accesibles por URL directa, se entregan desde aquí.
 */

require_once APP_PATH . '/servicios/servicio_archivos_institucion.php';

class ControladorInstitucion {

    /** Tipo MIME por extensión; se decide aquí, no a partir del archivo. */
    private const MIMES = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    /**
     * GET ?controlador=institucion&accion=logo&archivo=institucion_11_1789874665.png
     *
     * Público a propósito: el logo es la imagen de marca de la institución y
     * se muestra también en pantallas sin sesión. El nombre del archivo lo
     * valida el servicio contra un patrón fijo, así que no hay forma de pedir
     * otra cosa que un logo.
     */
    public function logo() {
        $servicio = new ServicioArchivosInstitucion();
        $ruta = $servicio->ruta_en_disco($_GET['archivo'] ?? '');

        if ($ruta === null) {
            // Sin cuerpo: el <img> simplemente no pinta nada.
            http_response_code(HTTP_NOT_FOUND);
            exit;
        }

        $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = self::MIMES[$ext] ?? 'application/octet-stream';

        // El nombre del archivo incluye un timestamp, así que su contenido nunca
        // cambia: se puede cachear de forma agresiva. Al reemplazar el logo
        // cambia el nombre y con él la URL.
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: public, max-age=2592000, immutable');

        readfile($ruta);
        exit;
    }
}
