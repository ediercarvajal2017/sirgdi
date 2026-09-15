## Estado de remediación (actualizado 2026-09-14)

| ID | Hallazgo | Estado |
|---|---|---|
| C1 | Escalada de privilegios (`procesar_usuario`) | ✅ Corregido |
| C2 | Scripts de mantenimiento sin autenticación | ✅ Corregido (guard de superadmin) |
| H1 | IDOR en descarga de evidencia | ✅ Corregido |
| H2 | Ruta 2FA rota (`accion=2fa` vs `dos_fa`) | ✅ Corregido |
| H3 | Sin rate limit en verificación 2FA | ✅ Corregido |
| H4 | Sin rate limit en creación de reporte invitado | ✅ Corregido (rate limit por IP; CAPTCHA queda como mejora opcional) |
| H5 | `cerrar_reporte` sin validar estado previo | ✅ Corregido |
| H6 | Sin máquina de transiciones de estado | ✅ Corregido |
| H7 | HTML duplicado en vistas públicas | ✅ Corregido |
| H8 | Email de invitado sin validar | ✅ Corregido |
| M1 | Enumeración de usuarios (login) | ✅ Corregido |
| M2 | Sesiones no invalidadas al cambiar contraseña / desactivar usuario | ⚠️ Parcial — re-chequeo de `activo` cada 5 min ✅; invalidar sesión al cambiar contraseña requiere migración de BD (columna de versión de credenciales), **pendiente, ver recomendación abajo** |
| M3 | Sin rate limit en recuperación de contraseña | ✅ Corregido |
| M4 | Token de reset persistido con atribución incorrecta | ✅ Corregido (ya no se persiste ese cuerpo) |
| M5 | `asignar_tecnico` no valida rol Técnico | ✅ Corregido |
| M6 | Endpoints AJAX sin permiso específico | ✅ Corregido |
| M7 | "CSRF faltante" en `procesar_usuario`/etc. | ❌ Falso positivo — CSRF ya se valida de forma centralizada en `public/index.php` |
| M8 | Logo institucional: validación débil, sin recodificado | ✅ Corregido |
| M9 | `BaseDatos` interpola nombres de tabla/columna sin whitelist | ✅ Corregido (validación de identificador) |
| M10 | AES-256-CBC sin HMAC | ✅ Corregido (encrypt-then-MAC, retrocompatible) |
| M11 | Clave de cifrado con fallback aleatorio no persistente | ✅ Corregido (falla al iniciar si falta) |
| M12 | `debug_permisos.php` accesible a cualquier usuario | ✅ Corregido (cubierto por el guard de C2) |
| M13 | "Login CSRF" | ✅ Limpiado (rutas no explotables removidas de la whitelist) |
| M14 | Sin límite de longitud server-side (form invitado) | ✅ Corregido |
| M15 | Carga de evidencia sin tope por etapa | ✅ Corregido (máx. 5 por etapa) |
| M16 | Errores de cierre redirigen al Kanban | ✅ Corregido |
| B1 | `.htaccess` case-sensitive | ✅ Corregido |
| B2 | `ModeloEvidencia::eliminar()` sin validar contención de ruta | ✅ Corregido |
| B3 | Falta `X-Content-Type-Options` | ✅ Corregido |
| B4 | Email duplicado entre instituciones sin desambiguar | ⏸️ Diferido (requiere cambio de UX en login, bajo impacto) |
| B5 | Logout no expira cookie explícitamente | ✅ Corregido |
| B6 | User-Agent como control anti-hijacking | ℹ️ Sin acción — ya documentado como no confiable, no aporta quitarlo |
| B7 | Sin rate limit en `contrasena_actual` | ⏸️ Diferido (impacto bajo, requiere sesión ya autenticada) |
| B8 | Nombre del reportante expuesto en seguimiento público | ⏸️ Diferido — es el diseño esperado (capability URL) |
| B9 | Protección 100% dependiente de `.htaccess` de Apache | ℹ️ Recomendación de infraestructura, no de código |
| B10 | Kanban "asignar técnico" sin indicador de carga | ⏸️ Diferido (cosmético) |

**Pendiente para una siguiente iteración (requiere decisión/migración, no se aplicó sin autorización explícita):**
- **M2 (completo):** invalidar sesiones activas al cambiar/resetear contraseña requiere una migración de esquema (columna `credenciales_actualizadas_en` en `usuario`, comparada contra `fecha_login` de la sesión). No se ejecutó ninguna migración de base de datos en esta sesión.
- **Verificación de incidente:** dado que la escalada de privilegios (C1) pudo haber sido explotable antes de este fix, se recomienda auditar la tabla `usuario_rol` en producción por filas `id_rol = 6` (Superadministrador) que no correspondan a cuentas legítimas conocidas.

---

# Auditoría de Seguridad, UI y Funcionalidad — SIRGDI

**Fecha:** 2026-09-14
**Alcance:** Revisión estática completa del código (backend PHP MVC, vistas, configuración, scripts auxiliares en la raíz del webroot). Solo lectura — no se modificó ningún archivo del proyecto. La verificación de la Sección 5 incluyó peticiones HTTP `GET` no destructivas contra la instancia local (XAMPP) para confirmar códigos de respuesta y cabeceras.
**Metodología:** 6 revisiones especializadas en paralelo, cada una con cita de archivo:línea como evidencia: (1) Autenticación y sesiones, (2) Autorización RBAC y aislamiento multi-tenant, (3) Inyección SQL y carga de archivos, (4) XSS, CSRF y manejo de errores, (5) Superficie pública, secretos y scripts de depuración, (6) UI y funcionalidad.

**Nota sobre auditoría previa:** el repositorio contiene un `AUDIT_REPORT.md` (2026-06-18) que reporta 97.6% de aprobación y afirma que el RBAC/aislamiento multi-tenant es "100% correcto". Esta auditoría es más profunda, cubre commits posteriores, y **no confirma esa conclusión**: se encontró una vulnerabilidad crítica de escalada de privilegios no detectada en la revisión anterior.

---

## Resumen ejecutivo

| Severidad | Cantidad |
|---|---|
| 🔴 Crítico | 2 |
| 🟠 Alto | 8 |
| 🟡 Medio | 16 |
| 🔵 Bajo | 10 |
| ⚪ Informativo | varios (ver §7) |

**Los dos hallazgos críticos deben corregirse antes de cualquier otra prioridad:**
1. Cualquier Admin de Institución puede autoasignarse el rol de Superadministrador global (§2.1).
2. Scripts administrativos destructivos en la raíz del sitio sin ninguna autenticación propia, protegidos hoy solo de forma incidental (§5.1).

---

## 1. Hallazgos CRÍTICOS 🔴

### C1 — Escalada de privilegios: un Admin de Institución puede convertirse en Superadministrador global
- **Archivos:** `app/controladores/controlador_administrador.php:220-463` (`procesar_usuario()`, toma `id_rol` de `$_POST` sin validar jerarquía), `app/controladores/controlador_administrador.php:189` (combo de roles no excluye Superadministrador), `app/servicios/servicio_autorizacion.php:127-155` (`obtener_permisos()` detecta rol Superadmin con `id_rol = 6` **sin filtrar por institución**).
- **Escenario:** un Admin normal de la Institución A envía `POST` a `procesar_usuario` con `id_rol=6` sobre su propio usuario → el sistema crea `usuario_rol(id_usuario, id_rol=6, id_institucion=A)` → en el siguiente login, `obtener_permisos()` le concede **todos los permisos del sistema** (incluido `gestionar_instituciones`), dándole control total sobre todas las instituciones.
- **Agravante:** `procesar_usuario()` no valida token CSRF (ver M10), por lo que el ataque también es viable vía CSRF contra un admin legítimo sin que lo note.
- **Recomendación:** validar server-side que solo un superadmin real puede asignar el rol Superadministrador (lista blanca de roles asignables según jerarquía del actor); corregir `obtener_permisos()` para no conceder privilegio global solo por la existencia de una fila `id_rol=6`; excluir el rol del combo de UI para actores no-superadmin.

### C2 — Scripts administrativos en el webroot sin autenticación propia
- **Archivos:** `arreglar_superadmin.php`, `configurar_superadmin.php`, `generar_hashes.php`, `reparar_charset.php`, `reparar_nombres.php`, `recarga_datos_demo.php`, `asignar_permisos_admin.php`, `verificar_admin.php`, `verificar_bd.php`, `cargar_sla.php`, `debug_sla.php`, `diagnostico_errores_bd.php`, `ejecutar_migracion_institucion.php`, `reparar_errores_bd_automatico.php` (raíz del proyecto).
- **Descripción:** ninguno tiene `requerir_autenticacion()`, verificación de rol, ni token. Ejemplos de impacto si se ejecutan: `arreglar_superadmin.php` asigna el rol Superadmin al usuario ID 1 incondicionalmente; `generar_hashes.php` resetea la contraseña de **todos** los usuarios de la institución 1 a un valor hardcodeado (`Temporal123!`); `recarga_datos_demo.php` hace `TRUNCATE` de prácticamente todas las tablas de producción y reimporta datos semilla.
- **Estado actual verificado empíricamente:** en la instancia local, la reescritura del `.htaccess` raíz (`RewriteRule ^(.*)$ public/$1`) redirige estas rutas hacia `public/index.php`, lo que hoy las hace inalcanzables directamente — **pero es un efecto colateral del enrutamiento, no un control deliberado**. La regla de defensa explícita del propio `.htaccess` (`RedirectMatch 403`) solo cubre carpetas (`app|lib|configuracion|sql|pruebas`) y el `<FilesMatch>` solo bloquea extensiones `.env/.md/.sql/.log/.lock/.json` — **no cubre estos `.php` sueltos en la raíz**. Ante cualquier cambio de hosting/servidor (Nginx, IIS, `AllowOverride` desactivado), quedarían inmediatamente ejecutables por cualquier visitante anónimo. Estos archivos ya están fuera de git (`.gitignore`), pero siguen existiendo físicamente en el servidor.
- **Recomendación:** eliminar físicamente estos archivos de cualquier servidor donde no sean estrictamente necesarios. Si se requieren como herramientas de mantenimiento, moverlos fuera del document root y exigir autenticación + verificación explícita de rol superadmin dentro del propio script (no depender del `.htaccess`).

---

## 2. Hallazgos ALTOS 🟠

### H1 — IDOR: cualquier usuario autenticado puede descargar evidencia de cualquier reporte de su institución
- **Archivo:** `app/controladores/controlador_tecnico.php:359-397` (`descargar_evidencia()`).
- Solo valida que la evidencia pertenezca a la misma institución (`$_GET['inst']`), sin verificar que el usuario sea el reportante, el técnico asignado, o tenga `PERMISO_VER_TODOS_REPORTES` (a diferencia de `controlador_reportes::detalle()`, que sí lo hace). Un usuario de bajo privilegio puede iterar `id` y ver evidencia de reportes ajenos.
- **Recomendación:** cargar el reporte asociado y aplicar la misma regla de *ownership* que `detalle()`; dejar de aceptar `id_institucion` desde `$_GET`.

### H2 — Ruta rota: el segundo factor (2FA) es inalcanzable en el flujo normal
- **Archivo:** `app/controladores/controlador_autenticacion.php:73,91,114,560`; router en `public/index.php:86-91`.
- El controlador redirige a `&accion=2fa`, pero el método está declarado `dos_fa()` (un método no puede empezar con dígito en PHP). El router resuelve `method_exists($obj, '2fa')` → `false` → **HTTP 404** para todo usuario con 2FA activado que complete el login correctamente. Falla en modo cerrado (no es un bypass), pero inutiliza el segundo factor y probablemente empuja a desactivarlo.
- **Recomendación:** corregir las redirecciones a `&accion=dos_fa`; agregar una prueba de humo del flujo de login con 2FA.

### H3 — Sin rate limiting en la verificación de código 2FA
- **Archivo:** `app/servicios/servicio_autenticacion.php:143-206` (`validar_2fa()`).
- A diferencia del login (bloqueo tras 5 intentos/15 min), la verificación TOTP no tiene ningún límite de intentos. Con una contraseña ya comprometida, un atacante puede probar el espacio de códigos de 6 dígitos sin fricción.
- **Recomendación:** aplicar el mismo mecanismo de rate limiting del login a `validar_2fa()`, por usuario pendiente de 2FA.

### H4 — Sin rate limiting ni CAPTCHA en la creación pública de reportes de invitado
- **Archivo:** `app/controladores/controlador_reportes.php:743-821` (`procesar_crear_invitado()`).
- Sin control de frecuencia, CAPTCHA ni honeypot. Cada envío admite hasta 5 fotos + 1 video y dispara un correo real. Riesgo de spam masivo, agotamiento de disco/SMTP.
- **Recomendación:** agregar CAPTCHA (hCaptcha/Turnstile) o rate limiting por IP/sesión.

### H5 — `cerrar_reporte` no valida el estado previo del reporte
- **Archivo:** `app/controladores/controlador_cierre.php:254-305` (`procesar_cerrar_reporte()`).
- A diferencia de `validar_solucion_form()` (que sí exige `ESTADO_SOLUCIONADO`), este método no verifica que el reporte esté en `ESTADO_EN_VALIDACION` antes de cerrarlo. Cualquier usuario con `PERMISO_VALIDAR_CIERRE` puede cerrar directamente un reporte recién creado, sin técnico, evidencia ni encuesta.
- **Recomendación:** añadir el mismo guard de estado usado en `validar_solucion_form()`.

### H6 — Cambio de estado de reporte sin máquina de transiciones válida
- **Archivos:** `app/controladores/controlador_gestion.php:287-339`, `app/modelos/modelo_reporte.php:230-253`, `app/vistas/gestion/vista_cambiar_estado.php:44-50`.
- `cambiar_estado()` escribe cualquier `id_estado_nuevo` recibido sin validar que la transición sea legal; el propio formulario ofrece cualquier estado como opción, incluyendo saltos directos (Registrado→Cerrado) o retrocesos (Cerrado→Registrado). Un ID de estado inválido deja el reporte "huérfano" del tablero Kanban silenciosamente.
- **Recomendación:** definir y validar una matriz de transiciones de estado válidas, tanto en el modelo como en el `<select>` del formulario.

### H7 — HTML duplicado/anidado en las dos vistas públicas principales
- **Archivos:** `app/vistas/reportes/vista_crear_reporte_invitado.php:13-18,988-989`, `vista_seguimiento_publico.php:2-7,819-820`, envueltas por `controlador_reportes.php:644-688` (`renderizar_vista()`).
- Ambas vistas son documentos HTML completos que además se envuelven en el `<!DOCTYPE html><html>...` del método `renderizar_vista()`, resultando en doctype/html/head/body duplicados y CDN de FontAwesome cargado dos veces, en las dos páginas más usadas por ciudadanos sin cuenta.
- **Recomendación:** convertir estas vistas en fragmentos (igual que `vista_crear_reporte.php`), o crear una ruta de renderizado independiente para vistas públicas.

### H8 — Correo del reportante invitado sin validar en ningún punto de la cadena
- **Archivos:** `app/vistas/reportes/vista_crear_reporte_invitado.php:270` (`novalidate` en el formulario, líneas 955-982 el JS de envío tampoco valida el formato), `app/controladores/controlador_reportes.php:757,794` (solo `sanitizar_texto()`, nunca `Validacion::validar_email()` pese a existir).
- Un ciudadano puede escribir cualquier texto como correo; el sistema lo acepta sin avisar y nunca recibirá notificaciones de su propio reporte (fallo silencioso).
- **Recomendación:** aplicar `Validacion::validar_email()` en el servidor y quitar `novalidate` (o validar formato) en el cliente.

---

## 3. Hallazgos MEDIOS 🟡

| # | Hallazgo | Evidencia |
|---|---|---|
| M1 | Enumeración de usuarios en login: mensaje distinto para cuenta inactiva + diferencia de tiempo de respuesta (`password_verify` solo se ejecuta si el usuario existe) | `servicio_autenticacion.php:87-108` |
| M2 | Cambiar/resetear contraseña o desactivar un usuario no invalida sus sesiones activas existentes | `modelo_usuario.php:102-125,153-177`, `servicio_autenticacion.php:313-347` |
| M3 | Sin rate limiting en la solicitud de recuperación de contraseña (mail bombing / agotamiento SMTP) | `controlador_autenticacion.php:246-292` |
| M4 | Token de reset de contraseña también se persiste en tabla `notificacion`, atribuido al usuario de la sesión activa (no al destinatario real) — riesgo si en el futuro se expone esa tabla al usuario | `servicio_notificacion.php:123-138,245-264` |
| M5 | `asignar_tecnico()` no valida que el usuario asignado tenga realmente rol Técnico (`// TODO` del propio desarrollador) | `controlador_gestion.php:130-197` (líneas 161-165) |
| M6 | Endpoints AJAX privilegiados protegidos solo por `requerir_autenticacion()`, sin verificar el permiso específico (fuga de nombres de técnicos, matriz de permisos del sistema visible a cualquier usuario autenticado) | `controlador_gestion.php:344-368,373-401`, `controlador_administrador.php:499-520` |
| M7 | Validación CSRF aplicada de forma inconsistente en escritura privilegiada (agrava C1) | `controlador_administrador.php::procesar_usuario/procesar_sla`, `controlador_superadmin.php::procesar_sede/eliminar_sede`, `controlador_gestion.php::asignar_tecnico/procesar_cambiar_estado` |
| M8 | Carga de logos institucionales: valida solo `Content-Type` del cliente + extensión, no recodifica con GD (a diferencia de evidencias); carpeta `public/almacenamiento/logos/` sin `.htaccess` anti-ejecución. No explotable hoy (whitelist de extensión fija), pero sin defensa en profundidad | `servicio_archivos_institucion.php:40-53,65` |
| M9 | `BaseDatos::insertar/actualizar/eliminar/contar` interpola nombres de tabla/columna sin whitelist (patrón frágil; hoy no hay ninguna ruta que pase `$_POST` sin filtrar, pero es una trampa para futuros desarrolladores) | `lib/basedatos.php:105-124,130-149,155-166,188-191` |
| M10 | AES-256-CBC sin HMAC/modo autenticado (vulnerable a padding oracle/bit-flipping en teoría) | `lib/encriptacion.php:18-42` |
| M11 | Claves de seguridad (`ENCRYPTION_KEY`/`JWT_SECRET`/`CSRF_SALT`) generan fallback aleatorio no persistente si faltan en `.env`, pudiendo volver indescifrables datos cifrados entre requests | `configuracion/config.php:63-72` |
| M12 | `debug_permisos.php` accesible a cualquier usuario autenticado (no solo admin); expone estructura interna de roles/permisos | archivo en raíz, `isset($_SESSION['id_usuario'])` únicamente |
| M13 | Login CSRF: `login`/`2fa` están explícitamente exentos de verificación CSRF pese a que el formulario ya envía el token | `public/index.php:28-43` |
| M14 | Sin límite de longitud server-side en campos del formulario de invitado (`maxlength` solo en cliente, se salta enviando POST directo) | `controlador_reportes.php:755-796` |
| M15 | Carga de evidencia por etapas (Antes/Durante/Después): sin orden obligatorio ni tope de fotos por etapa | `controlador_tecnico.php:240-298`, `modelo_evidencia.php:96-107` |
| M16 | Errores en cierre/validación/encuesta siempre redirigen al Kanban, perdiendo el contexto y el texto ya escrito por el usuario | `controlador_cierre.php:130-132,209-212,301-304` |

---

## 4. Hallazgos BAJOS 🔵

| # | Hallazgo | Evidencia |
|---|---|---|
| B1 | `.htaccess` de evidencias usa patrón case-sensitive para bloquear `.php` (no explotable hoy porque el nombre siempre lo genera el servidor) | `almacenamiento/archivos/evidencias/.htaccess` |
| B2 | `ModeloEvidencia::eliminar()` no valida contención de ruta antes de `unlink()` (a diferencia de `ServicioArchivos`) | `modelo_evidencia.php:164-179` |
| B3 | Falta cabecera `X-Content-Type-Options: nosniff` al servir evidencias | `controlador_tecnico.php:388-396` |
| B4 | `obtener_por_email()` sin `id_institucion` selecciona fila arbitraria si el mismo correo existe en más de una institución (el login no envía `id_institucion`) | `modelo_usuario.php:28-44` |
| B5 | Logout no expira explícitamente la cookie de sesión en el navegador | `servicio_autenticacion.php:359-368` |
| B6 | Validación de User-Agent como anti-hijacking es trivialmente evadible (no debe considerarse control real) | `servicio_autenticacion.php:224,338-341` |
| B7 | Sin rate limit en el campo `contrasena_actual` de `cambiar_contrasena` (impacto limitado, requiere sesión) | `controlador_autenticacion.php:177-225` |
| B8 | Nombre completo del reportante invitado se expone siempre en la página pública de seguimiento (diseño esperado tipo "capability URL", pero sin advertencia al reenviar el link) | `vista_seguimiento_publico.php:691-699` |
| B9 | Protección de `configuracion/`, `lib/`, `app/` y scripts sueltos depende 100% de directivas de Apache (`.htaccess`), sin ningún guard a nivel PHP; frágil ante migración de hosting | `.htaccess` raíz |
| B10 | Kanban "asignar técnico" sin indicador de carga ni protección de doble envío (a diferencia de crear-reporte/cargar-evidencia, que sí lo tienen) | `vista_kanban_gestion.php:147` |

---

## 5. Hallazgos cosméticos / de código

- Doble escape de campos de texto libre: se sanitiza con `strip_tags()+htmlspecialchars()` al guardar **y** se vuelve a escapar al mostrar → texto "roto" visualmente (`&amp;`, etc.), no es una falla de seguridad sino de datos. Recomendación: sanitizar solo en la salida. (`lib/validacion.php:92-96`, múltiples controladores)
- HTML/JS inline embebido directamente en 7 controladores para mostrar toasts (rompe separación MVC, aunque el escape es correcto).
- `setInterval` del overlay de carga en `vista_crear_reporte.php` nunca se limpia (`clearInterval`) — sin efecto visible porque la página navega inmediatamente después.
- Botón de envío no deshabilitado en `vista_crear_reporte.php` (sí lo está en la versión de invitado) — inconsistencia menor.
- Reimplementación manual de toasts en `vista_gestionar_usuarios.php`/`vista_gestionar_sla.php` en vez de reutilizar `toast_helper.php` (violación DRY).
- Mensajes de éxito genéricos en SLA/usuarios, sin distinguir creación/edición/eliminación.
- Checkbox "Recuérdame" en login no está implementado en el backend (cosmético).
- Variable `$_SESSION['codigo_2fa_temporal']` es código muerto (se genera pero nunca se usa).

---

## 6. Aspectos correctamente implementados (verificados, sin acción requerida)

- **Inyección SQL:** 100% prepared statements con parámetros bindeados en `lib/basedatos.php` y los 12 modelos; `ATTR_EMULATE_PREPARES=false`. Sin concatenación de variables de usuario en SQL.
- **Hashing de contraseñas:** bcrypt, cost=12, siempre vía `password_verify()`.
- **2FA (TOTP):** implementación RFC 6238 correcta; secreto cifrado en reposo con AES-256-CBC + IV aleatorio.
- **Tokens de reseteo de contraseña:** `random_bytes(32)`, expiran en 1h, de un solo uso, mensaje de respuesta siempre genérico (sin enumeración en ese punto concreto).
- **Sesión:** cookies `HttpOnly`, `Secure` condicional, `SameSite=Lax`, `session.use_strict_mode=1`; `session_regenerate_id(true)` tras login (previene fijación de sesión); timeout de inactividad (30 min) y absoluto (8h) correctamente aplicados.
- **Rate limiting de login:** 5 intentos/15 min con bloqueo, por email.
- **Multi-tenant:** prácticamente todas las queries de lectura/escritura en los modelos filtran por `id_institucion`; selección/cambio de institución de técnicos valida contra vínculos reales en BD, nunca confía en el cliente.
- **Ownership de reportante y técnico:** validado server-side antes de ejecutar acciones (no solo oculto en la UI), en `controlador_reportes` y `controlador_tecnico`.
- **XSS:** ~150 puntos de salida revisados en 37 vistas escapan consistentemente con `htmlspecialchars()`, incluidas las vistas públicas sin autenticación.
- **CSRF:** implementación centralizada en `public/index.php`, `hash_equals()`, tokens `random_bytes(32)`, presente en todos los formularios POST revisados (con las excepciones ya listadas en M7/M13).
- **Manejo de errores:** sin `var_dump`/`print_r`; excepciones de BD nunca exponen SQL crudo al usuario; `display_errors=0` en la configuración desplegada (`DEBUG=false`).
- **Carga de evidencias (fotos/video):** valida MIME real vía `finfo` (no `Content-Type` del cliente), recodifica imágenes con GD (elimina payloads embebidos), nombres de archivo aleatorios, almacenamiento fuera del docroot público, `.htaccess` que bloquea ejecución de `.php` en la carpeta de evidencias.
- **Token de seguimiento público:** UUIDv4 real (`random_bytes(16)`), no enumerable; el número de ticket visible nunca se usa como clave de búsqueda.
- **`.env` / `composer.json` / `composer.lock`:** correctamente excluidos de git y verificados con HTTP 403 al acceder directamente.
- **PHPMailer:** versión 6.12.0 (reciente), sin CVE conocida aplicable.

---

## 7. Prioridad de remediación recomendada

1. **C1** — Corregir la asignación de roles en `procesar_usuario()` (escalada de privilegios) — explotable hoy por cualquier Admin de Institución legítimo.
2. **C2** — Eliminar del servidor los scripts administrativos sin autenticación, o protegerlos explícitamente.
3. **H2 + H3** — Arreglar la ruta rota de 2FA y agregar rate limiting a su verificación (el 2FA hoy no protege nada porque es inalcanzable, y cuando se arregle necesitará el límite de intentos).
4. **H1** — Corregir el IDOR de descarga de evidencias.
5. **H5 + H6** — Añadir validación de estado/transiciones en cierre y cambio de estado de reportes.
6. **M2, M7** — Invalidar sesiones al cambiar contraseña/desactivar usuario; unificar validación CSRF en todos los endpoints de escritura privilegiada.
7. Resto de hallazgos Altos y Medios, según capacidad del equipo.
8. Hallazgos Bajos/cosméticos como mejora continua.

---

*Informe generado mediante revisión estática de código (solo lectura). Ningún archivo del proyecto fue modificado durante esta auditoría.*
