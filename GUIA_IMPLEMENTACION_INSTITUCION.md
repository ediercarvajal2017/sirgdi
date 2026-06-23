# Guía de Implementación: Actualización Tabla Institución

## 📋 Cambios Realizados

### 1. Base de Datos
- ✅ Agregado campo `codigo_dane` (CHAR(10), único)
- ✅ Renombrado `logo_url` → `logo_ruta` (para almacenar rutas de archivo local)
- ✅ Creado directorio `/almacenamiento/logos/`

**Script de migración:**
```bash
# Ejecutar en la raíz del proyecto
php ejecutar_migracion_institucion.php
```

### 2. Formularios Actualizados
✅ `app/vistas/superadmin/vista_crear_institucion.php`
✅ `app/vistas/superadmin/vista_editar_institucion.php`

**Nuevas características:**
- Campo "Código DANE" (validación: 10 dígitos)
- Upload de logo con drag-and-drop
- Preview de imagen antes de guardar
- Validación de tipo (PNG, JPG, WebP)
- Validación de tamaño (máx 5MB)

### 3. Servicios Creados
✅ `app/servicios/servicio_archivos_institucion.php`
- `procesar_logo()` - Guardar archivo
- `eliminar_logo()` - Borrar archivo
- `validar_logo()` - Validar antes de guardar
- `obtener_url_logo()` - Obtener URL para mostrar

### 4. Modelo Creado
✅ `app/modelos/modelo_institucion.php`
- `crear()` - Crear institución
- `obtener_por_id()` - Buscar por ID
- `obtener_por_dane()` - Buscar por código DANE
- `listar()` - Listar todas
- `actualizar()` - Actualizar institución
- `es_codigo_dane_unico()` - Validar DANE único
- Y más métodos auxiliares

---

## 🔧 Actualizar Controlador Superadmin

### En `app/controladores/controlador_superadmin.php`:

**1. En el método `crear_institucion()` - después de POST:**

```php
public function crear_institucion() {
    $this->auth->requerir_autenticacion();
    $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validar CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                throw new Exception('Token CSRF inválido');
            }

            // Validar datos requeridos
            $nombre = trim($_POST['nombre'] ?? '');
            $codigo_dane = trim($_POST['codigo_dane'] ?? '');

            if (empty($nombre) || strlen($nombre) < 3) {
                throw new Exception('El nombre debe tener al menos 3 caracteres');
            }

            // Validar código DANE
            if (!preg_match('/^[0-9]{10}$/', $codigo_dane)) {
                throw new Exception('El código DANE debe contener exactamente 10 dígitos');
            }

            // Cargar modelo
            require_once APP_PATH . '/modelos/modelo_institucion.php';
            $modelo = new ModeloInstitucion();

            // Verificar DANE único
            if (!$modelo->es_codigo_dane_unico($codigo_dane)) {
                throw new Exception('El código DANE ya está registrado');
            }

            // Procesar logo si existe
            $logo_ruta = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                require_once APP_PATH . '/servicios/servicio_archivos_institucion.php';
                $servicio_archivos = new ServicioArchivosInstitucion();

                // Validar
                $validacion = $servicio_archivos->validar_logo($_FILES['logo']);
                if (!$validacion['valid']) {
                    throw new Exception($validacion['error']);
                }

                // Procesar (usamos ID temporal 0, se actualiza después)
                try {
                    // Primero crear la institución SIN logo
                    $datos = [
                        'nombre' => $nombre,
                        'codigo_dane' => $codigo_dane,
                        'logo_ruta' => null
                    ];
                    $id_institucion = $modelo->crear($datos);

                    // Ahora procesar y guardar el logo con el ID real
                    $logo_ruta = $servicio_archivos->procesar_logo($_FILES['logo'], $id_institucion);

                    // Actualizar institución con la ruta del logo
                    if ($logo_ruta) {
                        $modelo->actualizar($id_institucion, ['logo_ruta' => $logo_ruta]);
                    }

                    $exito = 'Institución creada exitosamente';
                    $_SESSION['exito'] = $exito;
                    header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
                    exit;
                } catch (Exception $e) {
                    throw new Exception('Error al procesar logo: ' . $e->getMessage());
                }
            } else {
                // Sin logo
                $datos = [
                    'nombre' => $nombre,
                    'codigo_dane' => $codigo_dane,
                    'logo_ruta' => null
                ];
                $modelo->crear($datos);

                $_SESSION['exito'] = 'Institución creada exitosamente';
                header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }

    $datos = ['titulo' => 'Crear Nueva Institución'];
    $this->renderizar_vista('superadmin/vista_crear_institucion', $datos);
}
```

**2. En el método `editar_institucion()` - después de POST:**

```php
public function editar_institucion() {
    $this->auth->requerir_autenticacion();
    $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

    $id_institucion = intval($_GET['id'] ?? 0);
    if ($id_institucion <= 0) {
        die('Institución no especificada');
    }

    require_once APP_PATH . '/modelos/modelo_institucion.php';
    $modelo = new ModeloInstitucion();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validar CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                throw new Exception('Token CSRF inválido');
            }

            // Validar datos requeridos
            $nombre = trim($_POST['nombre'] ?? '');
            $codigo_dane = trim($_POST['codigo_dane'] ?? '');
            $es_activa = isset($_POST['es_activa']) ? 1 : 0;

            if (empty($nombre) || strlen($nombre) < 3) {
                throw new Exception('El nombre debe tener al menos 3 caracteres');
            }

            // Validar código DANE
            if (!preg_match('/^[0-9]{10}$/', $codigo_dane)) {
                throw new Exception('El código DANE debe contener exactamente 10 dígitos');
            }

            // Verificar DANE único (excluyendo la institución actual)
            if (!$modelo->es_codigo_dane_unico($codigo_dane, $id_institucion)) {
                throw new Exception('El código DANE ya está registrado en otra institución');
            }

            // Obtener institución actual
            $institucion_actual = $modelo->obtener_por_id($id_institucion);
            if (!$institucion_actual) {
                throw new Exception('Institución no encontrada');
            }

            // Datos básicos a actualizar
            $datos_actualizar = [
                'nombre' => $nombre,
                'codigo_dane' => $codigo_dane,
                'es_activa' => $es_activa
            ];

            // Procesar logo si se subió uno nuevo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                require_once APP_PATH . '/servicios/servicio_archivos_institucion.php';
                $servicio_archivos = new ServicioArchivosInstitucion();

                // Validar
                $validacion = $servicio_archivos->validar_logo($_FILES['logo']);
                if (!$validacion['valid']) {
                    throw new Exception($validacion['error']);
                }

                // Procesar logo (elimina el anterior automáticamente)
                $logo_ruta = $servicio_archivos->procesar_logo(
                    $_FILES['logo'],
                    $id_institucion,
                    $institucion_actual['logo_ruta']
                );

                if ($logo_ruta) {
                    $datos_actualizar['logo_ruta'] = $logo_ruta;
                }
            }

            // Actualizar institución
            $modelo->actualizar($id_institucion, $datos_actualizar);

            $_SESSION['exito'] = 'Institución actualizada exitosamente';
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }

    // Obtener institución para mostrar en formulario
    $institucion = $modelo->obtener_por_id($id_institucion);
    if (!$institucion) {
        die('Institución no encontrada');
    }

    $datos = [
        'titulo' => 'Editar Institución',
        'institucion' => $institucion
    ];
    $this->renderizar_vista('superadmin/vista_editar_institucion', $datos);
}
```

**3. Actualizar vista de institución en `vista_superadmin_inicio.php`:**

Cambiar la referencia del logo de URL a ruta:
```php
<?php if (!empty($institucion['logo_ruta'])): ?>
    <img src="<?php echo config('app.url_base'); ?>/almacenamiento/logos/<?php echo htmlspecialchars($institucion['logo_ruta']); ?>" alt="Logo">
<?php endif; ?>
```

---

## ✅ Pasos de Implementación

1. **Ejecutar migración:**
   ```bash
   php ejecutar_migracion_institucion.php
   ```

2. **Actualizar controlador `controlador_superadmin.php`** con el código anterior

3. **Probar formularios:**
   - Crear institución con DANE y logo
   - Editar institución
   - Verificar que los logos se guardan en `/almacenamiento/logos/`

4. **Verificar en base de datos:**
   ```sql
   DESCRIBE institucion;
   SELECT * FROM institucion;
   ```

---

## 🔍 Validaciones Implementadas

✅ **Campo Código DANE:**
- Exactamente 10 dígitos
- Único en la base de datos
- Requerido

✅ **Upload de Logo:**
- Tipos permitidos: PNG, JPG, WebP
- Tamaño máximo: 5MB
- Validación de tipo MIME
- Preview antes de guardar
- Drag-and-drop

✅ **Nombre de Institución:**
- Mínimo 3 caracteres
- Máximo 150 caracteres
- Requerido

---

## 📂 Estructura de Directorios

```
almacenamiento/logos/
├── institucion_1_1718700000.png
├── institucion_2_1718700100.jpg
└── ...
```

Los logos se guardan con el patrón: `institucion_{id_institucion}_{timestamp}.{ext}`

---

## 🐛 Troubleshooting

**Problema:** "Error al guardar el archivo"
- Verificar permisos del directorio `/almacenamiento/logos/` (755)
- Verificar que el servidor web pueda escribir en el directorio

**Problema:** "Código DANE ya está registrado"
- Verificar que no hay duplicados en base de datos
- Usar código diferente

**Problema:** Logo no se muestra
- Verificar ruta completa del archivo
- Verificar permisos de lectura del archivo (644)

---

## 📝 Notas

- Los logos antiguos (si los hay) permanecen con sus URLs en la BD
- Se recomienda migrar logos existentes manualmente
- El servicio de archivos permite eliminar logos al actualizar
- Los archivos se almacenan en el servidor, no en URLs externas
