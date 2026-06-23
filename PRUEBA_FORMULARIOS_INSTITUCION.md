# 🧪 Guía de Prueba: Formularios de Institución

## ✅ Paso 3: Pruebas Manuales

### 📍 Ubicación de Formularios
- **Crear:** `http://localhost/reporte_danos/public/?controlador=superadmin&accion=crear_institucion`
- **Editar:** `http://localhost/reporte_danos/public/?controlador=superadmin&accion=editar_institucion&id=1`
- **Listar:** `http://localhost/reporte_danos/public/?controlador=superadmin&accion=inicio`

---

## 🧪 Casos de Prueba

### Test 1: Crear Institución Completa ✓

**Objetivo:** Crear una institución con todos los campos

**Pasos:**
1. Ir a: `http://localhost/reporte_danos/public/?controlador=superadmin&accion=crear_institucion`
2. Completar datos:
   - **Nombre:** `Colegio Técnico San José`
   - **Código DANE:** `1234567890`
   - **Logo:** Seleccionar imagen (PNG, JPG o WebP, máx 5MB)
3. Hacer clic en "Crear Institución"

**Resultado Esperado:**
- ✓ Redireccionar a lista de instituciones
- ✓ Mensaje de éxito: "Institución creada exitosamente"
- ✓ Institución aparece en la tabla
- ✓ Logo se guardó en `/almacenamiento/logos/`

**Verificación:**
```bash
# Verificar archivo creado
ls -la c:/wamp64/www/reporte_danos/almacenamiento/logos/
# Debe haber un archivo: institucion_X_TIMESTAMP.{ext}
```

---

### Test 2: Crear sin Logo ✓

**Objetivo:** Crear institución sin subir logo

**Pasos:**
1. Ir a crear institución
2. Completar datos:
   - **Nombre:** `Instituto Educativo Demo`
   - **Código DANE:** `9876543210`
3. NO seleccionar logo
4. Hacer clic en "Crear Institución"

**Resultado Esperado:**
- ✓ Institución creada sin logo
- ✓ Puede editarse después para agregar logo

---

### Test 3: Validación DANE (Solo 10 dígitos) ✗

**Objetivo:** Verificar que rechaza DANE inválido

**Pasos:**
1. Intentar crear con:
   - **Código DANE:** `123456` (solo 6 dígitos)
2. Enviar formulario

**Resultado Esperado:**
- ✗ El navegador muestra error de validación HTML
- ✗ Mensaje: "El código DANE debe contener exactamente 10 dígitos"
- ✗ Formulario NO se envía

---

### Test 4: Validación DANE Duplicado ✗

**Objetivo:** Rechazar DANE ya registrado

**Pasos:**
1. Crear institución con DANE `1111111111`
2. Intentar crear otra con el mismo DANE `1111111111`

**Resultado Esperado:**
- ✗ Error del servidor: "El código DANE ya está registrado"
- ✗ No se crea la institución

---

### Test 5: Validación Archivo Logo ✗

**Objetivo:** Rechazar archivo inválido

**Pasos:**
1. Ir a crear institución
2. Intentar subir:
   - Archivo de texto (.txt)
   - Archivo ejecutable (.exe)
   - Imagen > 5MB

**Resultado Esperado:**
- ✗ Validación JavaScript rechaza el archivo
- ✗ Mensaje: "Por favor sube un archivo PNG, JPG o WebP"
- ✗ No muestra preview

---

### Test 6: Preview de Logo en Formulario ✓

**Objetivo:** Verificar que se vea preview antes de guardar

**Pasos:**
1. Ir a crear institución
2. Seleccionar un logo (PNG o JPG)
3. Observar la interfaz

**Resultado Esperado:**
- ✓ Desaparece área de drag-drop
- ✓ Se muestra preview de la imagen
- ✓ Botón rojo (X) para eliminar archivo
- ✓ Al eliminar, vuelve el área de drag-drop

---

### Test 7: Drag and Drop Logo ✓

**Objetivo:** Probar arrastrar archivo al área designada

**Pasos:**
1. Ir a crear institución
2. Abrir carpeta con una imagen en otra ventana
3. Arrastrar imagen al área de "Arrastra tu archivo aquí"
4. Soltar

**Resultado Esperado:**
- ✓ El archivo se carga
- ✓ Se muestra preview
- ✓ Funciona como si se hubiera seleccionado manualmente

---

### Test 8: Editar Institución ✓

**Objetivo:** Editar institución existente

**Pasos:**
1. Ir a lista de instituciones (superadmin/inicio)
2. Hacer clic en "Editar" en una institución
3. Cambiar datos:
   - **Nombre:** Agregar sufijo " (Modificado)"
   - **Código DANE:** Cambiar a otro válido único
4. Optionalmente, subir nuevo logo
5. Hacer clic en "Guardar Cambios"

**Resultado Esperado:**
- ✓ Institución actualizada
- ✓ Logo anterior eliminado si se subió nuevo
- ✓ Cambios reflejados en la tabla

---

### Test 9: Ver Logo Actual en Edición ✓

**Objetivo:** Mostrar logo actual cuando se edita

**Pasos:**
1. Editar una institución que tiene logo
2. Observar la sección del logo

**Resultado Esperado:**
- ✓ Se muestra vista previa del logo actual
- ✓ Mensaje: "✓ Logo actual"
- ✓ Indicación de que puede reemplazarse

---

### Test 10: Activar/Desactivar Institución ✓

**Objetivo:** Cambiar estado activo/inactivo

**Pasos:**
1. Editar institución
2. Marcar/desmarcar el checkbox "Institución Activa"
3. Guardar cambios

**Resultado Esperado:**
- ✓ Estado se actualiza
- ✓ En la tabla, el badge cambia color
- ✓ Si inactiva: muestra ícono rojo

---

## 📊 Checklist de Validación

### Campos del Formulario
- [ ] Campo "Nombre" está presente y es requerido
- [ ] Campo "Código DANE" está presente y es requerido
- [ ] Campo "Logo" acepta drag-and-drop
- [ ] Validación DANE: 10 dígitos
- [ ] Validación Logo: PNG, JPG, WebP
- [ ] Validación Logo: Máximo 5MB

### Comportamiento
- [ ] Preview aparece al seleccionar imagen
- [ ] Botón X elimina archivo seleccionado
- [ ] Error DANE duplicado se muestra correctamente
- [ ] Error archivo inválido se muestra correctamente
- [ ] Redirecciona a lista después de crear/editar
- [ ] Mensajes de éxito/error son claros

### Base de Datos
- [ ] Campo `codigo_dane` existe en tabla
- [ ] Campo `logo_ruta` existe en tabla (no logo_url)
- [ ] DANE es único (no hay duplicados)
- [ ] Logos se guardan con nombres únicos

### Archivos Generados
- [ ] Logos se guardan en `/almacenamiento/logos/`
- [ ] Nombres de archivo: `institucion_X_TIMESTAMP.ext`
- [ ] Permisos: Archivos legibles (644)

---

## 🚀 Resultado Final

**Todos los tests pasan? → Sistema listo para Paso 4**

Si hay errores, verificar:
1. ✓ Migración se ejecutó correctamente
2. ✓ Controlador se actualizó
3. ✓ Formularios tienen los campos nuevos
4. ✓ Servicios y modelos están en su lugar
5. ✓ Directorio `/almacenamiento/logos/` existe
