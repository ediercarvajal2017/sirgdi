Skill profesional de seguridad y despliegue a producción
(Aplicado a tu proyecto reporte_danos/)

1. Infraestructura y servidor
Dominio y HTTPS:

Certificado TLS: activo y renovado automáticamente (Let’s Encrypt o similar).

Redirección: todo http → https desde el servidor o .htaccess.

Usuario del sistema:

Usuario dedicado: para el servicio web, sin permisos de administración.

Permisos de carpetas:

public/: lectura y ejecución.

almacenamiento/: lectura/escritura solo para el usuario del servidor.

app/, configuracion/, lib/, sql/: solo lectura.

Actualizaciones:

PHP, servidor web (Apache/Nginx), sistema operativo y extensiones actualizados.

2. Configuración de entorno (configuracion/, .env, lib/)
Archivo .env:

Variables clave:

APP_ENV=production

APP_DEBUG=false

Credenciales BD, claves de cifrado, SMTP, etc.

Regla: .env nunca en git, solo .env.example con estructura.

config.php:

Errores: display_errors=0, registro en logs (almacenamiento/logs/).

Zona horaria, locale: configuradas según producción.

lib/encriptacion.php:

Contraseñas: password_hash() / password_verify().

Tokens: random_bytes() + bin2hex() para CSRF, recuperación de contraseña, etc.

lib/validacion.php:

Funciones centralizadas para validar/sanitizar todo input (GET, POST, JSON).

Controladores solo usan datos que pasaron por estas funciones.

3. Seguridad de la aplicación (controladores, servicios, vistas)
3.1 Autenticación y sesiones
servicio_autenticacion.php:

Login:

Bloqueo o retraso tras varios intentos fallidos.

Regenerar ID de sesión tras login: session_regenerate_id(true).

Sesiones:

Cookies con HttpOnly, Secure (si HTTPS) y SameSite=Lax/Strict.

Tiempo de expiración razonable y cierre de sesión manual.

Recuperación de contraseña (vista_recuperar_contrasena.php):

Token de un solo uso, con expiración.

No enviar contraseñas en claro; solo enlaces seguros.

3.2 Autorización y roles
servicio_autorizacion.php:

Funciones tipo requiereRol('ADMIN'), requierePermiso('GESTION_REPORTES').

Cada controlador (controlador_superadmin.php, controlador_administrador.php, controlador_tecnico.php, etc.) llama a este servicio al inicio de cada acción.

Regla:

La vista nunca es la única barrera; el backend siempre valida rol/permisos.

3.3 Protección XSS y CSRF
XSS (vistas en app/vistas/):

Todos los datos dinámicos se imprimen con:

php
echo htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
No usar echo directo de variables sin escapar.

CSRF (formularios sensibles):

Token CSRF generado en servidor, almacenado en sesión.

Incluido en formularios (login, crear/editar reporte, gestión usuarios, etc.).

Verificado en el controlador antes de procesar la acción.

3.4 Manejo de archivos (servicio_archivos.php, almacenamiento/)
Subida de evidencias/logos:

Validar MIME real (no solo extensión).

Renombrar archivos con IDs internos (no nombres originales).

Guardar en almacenamiento/archivos/evidencias/ y servirlos vía controlador si son sensibles.

Regla:

No permitir ejecución de archivos subidos (bloquear .php, .js, etc.).

4. Base de datos y modelos (lib/basedatos.php, app/modelos/, sql/)
Conexión (lib/basedatos.php):

Usar PDO con:

PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION

PDO::ATTR_EMULATE_PREPARES => false

Consultas en modelos:

Solo consultas preparadas (prepare + bindParam/bindValue).

Prohibido concatenar variables en SQL.

Usuario de BD:

Usuario con permisos mínimos (sin DROP/ALTER si no es necesario).

Backups:

Copias de seguridad automáticas (diarias/semanales) del esquema y datos.

5. Logs, monitoreo y auditoría (almacenamiento/logs/, dashboard/vista_auditoria.php)
Logs de aplicación:

Registrar errores, intentos de login fallidos, cambios de rol, borrados de reportes.

No guardar contraseñas ni tokens en claro.

Auditoría:

vista_auditoria.php debe mostrar eventos críticos con filtros por usuario, fecha, acción.

Monitoreo:

Configurar alertas (correo, panel externo) ante errores recurrentes o caídas.

6. public/, router y .htaccess
Raíz pública:

Solo public/ como document root del servidor.

.htaccess en raíz y public/:

Redirigir todo a public/index.php.

Bloquear acceso directo a app/, configuracion/, lib/, sql/, almacenamiento/ (excepto recursos estáticos necesarios).

index.php (front controller):

Inicia sesión segura.

Carga config.php, basedatos.php, validacion.php.

Aplica verificación de CSRF y autenticación antes de delegar a controladores.