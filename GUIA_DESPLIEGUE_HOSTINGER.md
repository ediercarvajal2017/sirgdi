# 🚀 Guía de Despliegue — SIRGDI v2.0 en Hostinger (desde GitHub)

Dominio destino: **https://mantenimiento.ediertech.com**
Estructura en el servidor: `public_html/mantenimiento/` (raíz del proyecto) y el
subdominio apunta a esa carpeta.

---

## 1) Subir el proyecto a GitHub

```bash
# En la carpeta del proyecto (local)
git init
git add .
git commit -m "SIRGDI v2.0 - listo para despliegue"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/TU_REPO.git
git push -u origin main
```

> El `.gitignore` ya evita subir `.env`, datos de runtime y los scripts de
> depuración. **Nunca subas el `.env` real.**

---

## 2) Crear la base de datos en Hostinger

1. hPanel → **Bases de datos MySQL** → crear base + usuario (anota nombre, usuario y contraseña).
2. Abre **phpMyAdmin** de esa base e importa, en este orden:
   - `sql/schema.sql`  (estructura: 25 tablas)
   - `sql/seeds.sql`   (catálogos, roles/permisos, datos demo)

> Después de importar, ejecuta el **paso 7** para dejar una contraseña real al usuario administrador.

---

## 3) Crear el subdominio

1. hPanel → **Subdominios** → crear `mantenimiento` bajo `ediertech.com`.
2. En **document root** del subdominio usa la carpeta donde clonarás el repo:
   `public_html/mantenimiento`.

---

## 4) Desplegar desde GitHub (Git de Hostinger)

1. hPanel → **GIT** → "Crear repositorio".
2. Repositorio: la URL de tu repo de GitHub. Rama: `main`.
   Directorio de instalación: `public_html/mantenimiento`.
3. Pulsa **Deploy** (o configura auto-deploy con el webhook que te da Hostinger
   para que cada `git push` actualice el servidor).

El `.htaccess` de la raíz redirige todas las peticiones a `public/` y fuerza HTTPS,
así que la URL queda limpia: `https://mantenimiento.ediertech.com/`.

---

## 5) Crear el archivo `.env` en el servidor

En `public_html/mantenimiento/.env` (mismo nivel que `app/` y `public/`).
Cópialo desde `.env.example` y completa los valores reales:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=uXXXXXXXX_sirgdi
DB_USER=uXXXXXXXX_admin
DB_PASS=la_contraseña_real_de_la_bd

SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_USER=noreply@ediertech.com
SMTP_PASS=la_contraseña_del_correo
SMTP_FROM_EMAIL=noreply@ediertech.com
SMTP_FROM_NAME=SIRGDI - Reportes de Daños

ENCRYPTION_KEY=...(64 hex)...
JWT_SECRET=...(64 hex)...
CSRF_SALT=...(32 hex)...

ENV=production
DEBUG=false
APP_URL=https://mantenimiento.ediertech.com
TIMEZONE=America/Bogota
FORCE_HTTPS=true
```

Genera las claves con:
```bash
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"   # ENCRYPTION_KEY y JWT_SECRET
php -r "echo bin2hex(random_bytes(16)).PHP_EOL;"    # CSRF_SALT
```

---

## 6) Permisos de carpetas (escritura)

Estas carpetas deben poder escribirse (sesiones, logs, archivos subidos):

```
almacenamiento/sesiones
almacenamiento/logs
almacenamiento/cache
almacenamiento/temp
almacenamiento/archivos/evidencias
public/almacenamiento/logos
```

En Hostinger (Administrador de archivos o SSH) deja estas carpetas en **755**
(en algunos hosts compartidos puede requerir 775). Los `.gitkeep` ya las crean.

---

## 7) Contraseña real del administrador

Los hashes del `seeds.sql` son de ejemplo. Genera un hash real y actualízalo:

```bash
php -r "echo password_hash('TuContraseñaSegura123!', PASSWORD_BCRYPT).PHP_EOL;"
```

Luego en phpMyAdmin:
```sql
UPDATE usuario
SET hash_contrasena = '<<EL_HASH_GENERADO>>'
WHERE correo_electronico = 'superadmin@sirgdi.edu.co';
```

---

## 8) SSL (HTTPS)

hPanel → **SSL** → emite el certificado gratuito (Let's Encrypt) para
`mantenimiento.ediertech.com`. El `.htaccess` ya fuerza HTTPS y `FORCE_HTTPS=true`
activa las cookies seguras.

---

## ✅ Verificación final

- [ ] `https://mantenimiento.ediertech.com/` carga el login (con CSS/JS).
- [ ] El candado de HTTPS aparece (SSL activo).
- [ ] Inicias sesión con el admin (paso 7).
- [ ] Se ven los estilos (CSS) y el logo de la institución.
- [ ] Crear un reporte con foto funciona y la foto se ve en el detalle.
- [ ] Intentar abrir `https://mantenimiento.ediertech.com/configuracion/config.php`
      o `/.env` da error/redirección (NO muestra el contenido). ✅ seguridad OK.

---

## 🔁 Actualizaciones futuras

Con auto-deploy activado, basta con:
```bash
git add .
git commit -m "cambios"
git push
```
Hostinger actualiza el servidor automáticamente. El `.env` y los datos subidos
permanecen intactos (no están en git).
