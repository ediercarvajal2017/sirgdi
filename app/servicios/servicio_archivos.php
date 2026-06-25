<?php
// Servicio de Archivos (Upload, compresión, validación)
// RNF-04: Fotos comprimidas automáticamente (JPG, max 5MB)

class ServicioArchivos {
    private $directorio_almacenamiento;
    private $max_tamaño = 5242880; // 5MB
    private $mimes_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    private $calidad_jpg = 85; // Compresión JPG

    public function __construct() {
        $this->directorio_almacenamiento = STORAGE_PATH . '/archivos/evidencias';
        $this->asegurar_directorio();
    }

    /**
     * Validar y procesar archivo de foto
     * Retorna: ['exito' => bool, 'ruta' => string, 'error' => string]
     */
    public function procesar_foto($archivo_temporal, $nombre_original) {
        // Validar que el archivo existe
        if (!file_exists($archivo_temporal) || !is_uploaded_file($archivo_temporal)) {
            return [
                'exito' => false,
                'error' => 'Archivo no válido o no fue cargado correctamente.',
            ];
        }

        // Validar tamaño
        $tamaño = filesize($archivo_temporal);
        if ($tamaño > $this->max_tamaño) {
            return [
                'exito' => false,
                'error' => 'Archivo demasiado grande. Máximo: 5MB.',
            ];
        }

        // Validar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo_temporal);
        finfo_close($finfo);

        if (!in_array($mime_type, $this->mimes_permitidos)) {
            return [
                'exito' => false,
                'error' => 'Formato de imagen no permitido. Use JPG, PNG o WebP.',
            ];
        }

        try {
            // Generar nombre único
            $nombre_archivo = 'evidencia_' . uniqid() . '.jpg';
            $ruta_final = $this->directorio_almacenamiento . '/' . $nombre_archivo;

            // Cargar imagen original
            $imagen = null;
            switch ($mime_type) {
                case 'image/jpeg':
                    $imagen = imagecreatefromjpeg($archivo_temporal);
                    break;
                case 'image/png':
                    $imagen = imagecreatefrompng($archivo_temporal);
                    break;
                case 'image/webp':
                    $imagen = imagecreatefromwebp($archivo_temporal);
                    break;
            }

            if (!$imagen) {
                throw new Exception('No se pudo leer la imagen.');
            }

            // Comprimir y guardar como JPG
            if (!imagejpeg($imagen, $ruta_final, $this->calidad_jpg)) {
                throw new Exception('No se pudo guardar la imagen comprimida.');
            }

            imagedestroy($imagen);

            // Verificar que el archivo se creó
            if (!file_exists($ruta_final)) {
                throw new Exception('El archivo no se guardó correctamente.');
            }

            // Limpiar archivo temporal
            @unlink($archivo_temporal);

            return [
                'exito' => true,
                'ruta' => $ruta_final,
                'nombre_original' => $nombre_archivo,
                'tamaño_bytes' => filesize($ruta_final),
            ];

        } catch (Exception $e) {
            @unlink($archivo_temporal);
            return [
                'exito' => false,
                'error' => 'Error al procesar imagen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validar y mover archivo de video (MP4/WebM, máx 50 MB)
     */
    public function procesar_video($archivo_temporal, $nombre_original) {
        if (!file_exists($archivo_temporal) || !is_uploaded_file($archivo_temporal)) {
            return ['exito' => false, 'error' => 'Archivo de video no válido.'];
        }

        $tamaño = filesize($archivo_temporal);
        if ($tamaño > 50 * 1024 * 1024) {
            return ['exito' => false, 'error' => 'Video demasiado grande. Máximo: 50 MB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo_temporal);
        finfo_close($finfo);

        $mimes_video = ['video/mp4', 'video/webm', 'video/quicktime'];
        if (!in_array($mime_type, $mimes_video)) {
            return ['exito' => false, 'error' => 'Formato de video no permitido. Use MP4 o WebM.'];
        }

        $ext_map = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mp4'];
        $ext = $ext_map[$mime_type] ?? 'mp4';
        $nombre_archivo = 'evidencia_video_' . uniqid() . '.' . $ext;
        $ruta_final = $this->directorio_almacenamiento . '/' . $nombre_archivo;

        if (!move_uploaded_file($archivo_temporal, $ruta_final)) {
            return ['exito' => false, 'error' => 'No se pudo guardar el video.'];
        }

        return [
            'exito'           => true,
            'ruta'            => $ruta_final,
            'nombre_original' => $nombre_original,
            'tamaño_bytes'    => filesize($ruta_final),
            'mime_type'       => $mime_type,
        ];
    }

    /**
     * Eliminar archivo de evidencia
     */
    public function eliminar_archivo($ruta_archivo) {
        if (file_exists($ruta_archivo) && strpos($ruta_archivo, $this->directorio_almacenamiento) === 0) {
            return @unlink($ruta_archivo);
        }
        return false;
    }

    /**
     * Obtener URL de descarga para evidencia (protegida)
     */
    public function obtener_url_descarga($id_evidencia, $id_institucion) {
        return config('app.url_base') . '/?controlador=tecnico&accion=descargar_evidencia&id=' . $id_evidencia . '&inst=' . $id_institucion;
    }

    /**
     * Asegurar que el directorio de almacenamiento existe
     */
    private function asegurar_directorio() {
        if (!is_dir($this->directorio_almacenamiento)) {
            @mkdir($this->directorio_almacenamiento, 0750, true);
        }

        // Crear .htaccess para prevenir ejecución directa
        $htaccess_ruta = $this->directorio_almacenamiento . '/.htaccess';
        if (!file_exists($htaccess_ruta)) {
            $htaccess_contenido = <<<'EOT'
<FilesMatch "\.(?:php|phtml|php3|php4|php5|phps)$">
    Deny from all
</FilesMatch>

# Prevenir list directory
Options -Indexes
EOT;
            @file_put_contents($htaccess_ruta, $htaccess_contenido);
        }
    }

    /**
     * Obtener información del archivo (para descargas)
     */
    public function obtener_info_archivo($ruta_archivo) {
        if (!file_exists($ruta_archivo) || !is_file($ruta_archivo)) {
            return null;
        }

        return [
            'nombre_completo' => basename($ruta_archivo),
            'tamaño' => filesize($ruta_archivo),
            'tipo' => mime_content_type($ruta_archivo),
            'fecha_modificacion' => filemtime($ruta_archivo),
        ];
    }

    /**
     * Limpiar archivos huérfanos (sin registro en BD)
     * Ejecutar via cron job periódicamente
     */
    public function limpiar_archivos_huerfanos() {
        $archivos_en_disco = glob($this->directorio_almacenamiento . '/*.jpg');
        $archivos_eliminados = 0;

        require_once APP_PATH . '/modelos/modelo_evidencia.php';
        $modelo_evidencia = new ModeloEvidencia();

        foreach ($archivos_en_disco as $archivo) {
            $nombre_archivo = basename($archivo);

            // Buscar en BD
            $sql = 'SELECT COUNT(*) as total FROM evidencia WHERE nombre_archivo = :nombre AND is_deleted = 0';
            $bd = BaseDatos::obtener();
            $resultado = $bd->obtener_uno($sql, [':nombre' => $nombre_archivo]);

            if (intval($resultado['total'] ?? 0) === 0) {
                // Archivo huérfano, eliminar
                if (@unlink($archivo)) {
                    $archivos_eliminados++;
                }
            }
        }

        return $archivos_eliminados;
    }
}
