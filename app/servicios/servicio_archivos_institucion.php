<?php
/**
 * Servicio para manejo de archivos de instituciones
 * Gestiona subida, validación y almacenamiento de logos
 *
 * IMPORTANTE — por qué los logos NO viven dentro de public/:
 * El despliegue automático de Hostinger reemplaza por completo la carpeta
 * public/ en cada push (vuelve a clonar el repositorio). Cualquier archivo
 * subido allí se perdía en el siguiente despliegue: el logo se veía bien un
 * día o dos y después quedaba el ícono roto, porque la BD seguía apuntando a
 * un archivo que ya no existía en disco. Por eso los logos se guardan junto a
 * las evidencias, dentro de almacenamiento/, que sí sobrevive a los
 * despliegues, y se sirven por PHP (controlador_institucion.php).
 */

class ServicioArchivosInstitucion {

    /** Carpeta persistente (fuera de public/, sobrevive a los despliegues). */
    private $directorio_logos;

    /** Carpeta antigua dentro de public/; solo se lee para migrar lo que quede. */
    private $directorio_legacy;

    private $max_tamaño = 5 * 1024 * 1024; // 5MB
    private $tipos_permitidos = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    private $extensiones_permitidas = ['png', 'jpg', 'jpeg', 'webp'];

    /** Nombres válidos de logo: institucion_<id>_<timestamp>.<ext> */
    const PATRON_NOMBRE = '/^institucion_\d+_\d+\.(png|jpg|jpeg|webp)$/';

    public function __construct() {
        $this->directorio_logos  = STORAGE_PATH . '/archivos/logos';
        $this->directorio_legacy = PUBLIC_PATH . '/almacenamiento/logos';

        if (!is_dir($this->directorio_logos)) {
            @mkdir($this->directorio_logos, 0755, true);
        }

        // Defensa en profundidad: aunque la carpeta ya está fuera de la raíz web,
        // se mantiene el .htaccess por si alguna configuración llegara a exponerla.
        $htaccess_ruta = $this->directorio_logos . '/.htaccess';
        if (is_dir($this->directorio_logos) && !file_exists($htaccess_ruta)) {
            $htaccess_contenido = "<FilesMatch \"(?i)\\.(?:php|phtml|php\\d|phps)$\">\n"
                . "    Deny from all\n"
                . "</FilesMatch>\n\n"
                . "# Prevenir listado de directorio\n"
                . "Options -Indexes\n";
            @file_put_contents($htaccess_ruta, $htaccess_contenido);
        }
    }

    /**
     * Procesar y guardar logo de institución
     * @param array $archivo $_FILES['logo']
     * @param int $id_institucion ID de la institución
     * @param string $logo_actual Nombre del logo actual (para reemplazar)
     * @return string Nombre del archivo guardado, o null si falla
     */
    public function procesar_logo($archivo, $id_institucion, $logo_actual = null) {
        // Validar que el archivo exista
        if (!isset($archivo) || !isset($archivo['tmp_name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!is_uploaded_file($archivo['tmp_name'])) {
            throw new Exception("Archivo no válido o no fue cargado correctamente.");
        }

        // Validar tamaño
        if ($archivo['size'] > $this->max_tamaño) {
            throw new Exception("El archivo excede el tamaño máximo permitido (5MB)");
        }

        // Validar tipo real por contenido (finfo), no el Content-Type que envía el
        // cliente ($archivo['type']), que es trivialmente falsificable.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_real = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_real, $this->tipos_permitidos, true)) {
            throw new Exception("Tipo de archivo no permitido. Use PNG, JPG o WebP");
        }

        // Re-codificar con GD (como con las evidencias): elimina cualquier payload
        // no-imagen embebido en el archivo (defensa en profundidad ante polyglots).
        switch ($mime_real) {
            case 'image/png':
                $imagen = @imagecreatefrompng($archivo['tmp_name']);
                $ext = 'png';
                break;
            case 'image/webp':
                $imagen = @imagecreatefromwebp($archivo['tmp_name']);
                $ext = 'webp';
                break;
            default: // image/jpeg, image/jpg
                $imagen = @imagecreatefromjpeg($archivo['tmp_name']);
                $ext = 'jpg';
                break;
        }

        if (!$imagen) {
            throw new Exception("El archivo no es una imagen válida");
        }

        if (!is_dir($this->directorio_logos) && !@mkdir($this->directorio_logos, 0755, true)) {
            imagedestroy($imagen);
            throw new Exception("No se pudo preparar la carpeta de logos");
        }

        // Generar nombre único
        $nombre_archivo = 'institucion_' . $id_institucion . '_' . time() . '.' . $ext;
        $ruta_completa = $this->directorio_logos . '/' . $nombre_archivo;

        switch ($ext) {
            case 'png':
                imagesavealpha($imagen, true);
                $guardado = imagepng($imagen, $ruta_completa, 6);
                break;
            case 'webp':
                $guardado = imagewebp($imagen, $ruta_completa, 90);
                break;
            default:
                $guardado = imagejpeg($imagen, $ruta_completa, 90);
                break;
        }
        imagedestroy($imagen);

        if (!$guardado) {
            throw new Exception("Error al guardar el archivo de logo");
        }

        // Eliminar logo anterior si existe (en la carpeta nueva y en la antigua)
        if ($logo_actual) {
            $this->eliminar_logo($logo_actual);
        }

        // Hacer el archivo legible para el servidor web
        chmod($ruta_completa, 0644);

        // Retornar solo el nombre del archivo (se almacena en DB)
        return $nombre_archivo;
    }

    /**
     * Eliminar logo de una institución
     * @param string $nombre_archivo Nombre del archivo a eliminar
     * @return bool Éxito de la operación
     */
    public function eliminar_logo($nombre_archivo) {
        $nombre = $this->nombre_seguro($nombre_archivo);
        if ($nombre === null) {
            return false;
        }

        $borrado = false;
        foreach ([$this->directorio_logos, $this->directorio_legacy] as $carpeta) {
            $ruta = $carpeta . '/' . $nombre;
            if (is_file($ruta)) {
                $borrado = @unlink($ruta) || $borrado;
            }
        }

        return $borrado;
    }

    /**
     * Ruta absoluta en disco del logo, o null si el archivo no existe.
     * Si todavía está en la carpeta antigua dentro de public/, lo migra.
     * @param string $nombre_archivo
     * @return string|null
     */
    public function ruta_en_disco($nombre_archivo) {
        $nombre = $this->nombre_seguro($nombre_archivo);
        if ($nombre === null) {
            return null;
        }

        $ruta = $this->directorio_logos . '/' . $nombre;
        if (is_file($ruta)) {
            return $ruta;
        }

        // Migración perezosa desde la ubicación antigua (public/almacenamiento/logos).
        $legacy = $this->directorio_legacy . '/' . $nombre;
        if (is_file($legacy)) {
            if (!is_dir($this->directorio_logos)) {
                @mkdir($this->directorio_logos, 0755, true);
            }
            if (@rename($legacy, $ruta) || (@copy($legacy, $ruta) && @unlink($legacy))) {
                @chmod($ruta, 0644);
                return $ruta;
            }
            return $legacy;
        }

        return null;
    }

    /**
     * URL para mostrar el logo, o null si el archivo ya no está en disco.
     * Devolver null permite que las vistas oculten la imagen en vez de dejar
     * un ícono roto cuando el archivo falta.
     * @param string $nombre_archivo Nombre del archivo
     * @return string|null
     */
    public function obtener_url_logo($nombre_archivo) {
        if ($this->ruta_en_disco($nombre_archivo) === null) {
            return null;
        }

        return config('app.url_base')
            . '/?controlador=institucion&accion=logo&archivo='
            . rawurlencode($this->nombre_seguro($nombre_archivo));
    }

    /**
     * Normaliza y valida el nombre de archivo de un logo.
     * Evita cualquier salto de carpeta y limita a los nombres que genera
     * este mismo servicio.
     * @param string $nombre_archivo
     * @return string|null
     */
    public function nombre_seguro($nombre_archivo) {
        if (empty($nombre_archivo) || !is_string($nombre_archivo)) {
            return null;
        }

        $nombre = basename($nombre_archivo);

        return preg_match(self::PATRON_NOMBRE, $nombre) ? $nombre : null;
    }

    /**
     * Validar que un archivo de logo sea válido
     * @param array $archivo $_FILES['logo']
     * @return array [valid => bool, error => string|null]
     */
    public function validar_logo($archivo) {
        $resultado = [
            'valid' => true,
            'error' => null
        ];

        if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            // No hay archivo es válido (opcional)
            return $resultado;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $resultado['valid'] = false;
            $resultado['error'] = 'Error al subir el archivo';
            return $resultado;
        }

        // Validar tamaño
        if ($archivo['size'] > $this->max_tamaño) {
            $resultado['valid'] = false;
            $resultado['error'] = 'El archivo excede 5MB';
            return $resultado;
        }

        // Validar tipo MIME
        if (!in_array($archivo['type'], $this->tipos_permitidos)) {
            $resultado['valid'] = false;
            $resultado['error'] = 'Tipo de archivo no permitido';
            return $resultado;
        }

        // Validar extensión
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->extensiones_permitidas)) {
            $resultado['valid'] = false;
            $resultado['error'] = 'Extensión no permitida';
            return $resultado;
        }

        return $resultado;
    }

    /**
     * Obtener información del archivo
     * @param string $nombre_archivo
     * @return array|null
     */
    public function obtener_info_archivo($nombre_archivo) {
        $ruta = $this->ruta_en_disco($nombre_archivo);

        if ($ruta === null) {
            return null;
        }

        return [
            'nombre_completo' => basename($ruta),
            'tamaño' => filesize($ruta),
            'tipo' => mime_content_type($ruta),
            'fecha_modificacion' => filemtime($ruta)
        ];
    }
}
