<?php
/**
 * Servicio para manejo de archivos de instituciones
 * Gestiona subida, validación y almacenamiento de logos
 */

class ServicioArchivosInstitucion {

    private $directorio_logos = 'almacenamiento/logos';
    private $max_tamaño = 5 * 1024 * 1024; // 5MB
    private $tipos_permitidos = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    private $extensiones_permitidas = ['png', 'jpg', 'jpeg', 'webp'];

    public function __construct() {
        // Crear directorio si no existe
        if (!is_dir($this->directorio_logos)) {
            mkdir($this->directorio_logos, 0755, true);
        }

        // Defensa en profundidad: impedir que un archivo subido a esta carpeta pública
        // pueda ejecutarse como PHP, igual que en la carpeta de evidencias.
        $htaccess_ruta = $this->directorio_logos . '/.htaccess';
        if (!file_exists($htaccess_ruta)) {
            $htaccess_contenido = <<<'EOT'
<FilesMatch "(?i)\.(?:php|phtml|php\d|phps)$">
    Deny from all
</FilesMatch>

# Prevenir listado de directorio
Options -Indexes
EOT;
            @file_put_contents($htaccess_ruta, $htaccess_contenido);
        }
    }

    /**
     * Procesar y guardar logo de institución
     * @param array $archivo $_FILES['logo']
     * @param int $id_institucion ID de la institución
     * @param string $logo_actual Ruta del logo actual (para reemplazar)
     * @return string Ruta relativa del archivo guardado, o null si falla
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

        // Eliminar logo anterior si existe
        if ($logo_actual && file_exists($this->directorio_logos . '/' . $logo_actual)) {
            unlink($this->directorio_logos . '/' . $logo_actual);
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
        if (empty($nombre_archivo)) {
            return false;
        }

        $ruta = $this->directorio_logos . '/' . $nombre_archivo;

        if (file_exists($ruta)) {
            return unlink($ruta);
        }

        return false;
    }

    /**
     * Obtener URL completa del logo
     * @param string $nombre_archivo Nombre del archivo
     * @return string URL relativa del logo
     */
    public function obtener_url_logo($nombre_archivo) {
        if (empty($nombre_archivo)) {
            return null;
        }

        return '/' . $this->directorio_logos . '/' . $nombre_archivo;
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
        if (empty($nombre_archivo)) {
            return null;
        }

        $ruta = $this->directorio_logos . '/' . $nombre_archivo;

        if (!file_exists($ruta)) {
            return null;
        }

        return [
            'nombre_completo' => $nombre_archivo,
            'tamaño' => filesize($ruta),
            'tipo' => mime_content_type($ruta),
            'fecha_modificacion' => filemtime($ruta)
        ];
    }
}
