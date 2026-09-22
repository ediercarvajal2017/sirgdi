# Backups automáticos de SIRGDI/ANA

Estos archivos son **plantillas versionadas** en el repo. A propósito **no se
despliegan automáticamente** con `git push` — el backup vive fuera de
`public_html` de ambos dominios, para que nunca sea accesible por web y para
que un deploy nunca lo toque ni lo borre.

## Archivos

- `backup_sirgdi.sh` — script principal. Vuelca la base de datos compartida,
  empaqueta las evidencias/logos de cada dominio, cifra todo con AES-256 y
  sube el resultado a Google Drive. Rota localmente y en remoto todo lo de
  más de 30 días.
- `backup_db_env.php` — helper que lee las credenciales de BD reales desde
  `configuracion/config.php` (sin duplicarlas en un tercer lugar) y se las
  pasa al script de bash de forma segura.
- `backup_alerta.php` — envía un correo si el backup falla, reutilizando el
  SMTP y PHPMailer que ya usa la aplicación.

Ambos helpers reciben la ruta raíz del sitio como argumento. `backup_sirgdi.sh`
los invoca apuntando a `mto.jlcserviciosintegrales.com` (`$SITIO_B`), que es el
dominio activo — `mantenimiento.ediertech.com` (`$SITIO_A`) quedó solo para no
perder la base de datos compartida tras la migración, y su cuenta de correo
tiene el envío saliente deshabilitado por Hostinger.

## Instalación en el servidor (una sola vez)

```bash
mkdir -p ~/scripts
scp scripts/backup/backup_sirgdi.sh   u397951547@servidor:~/scripts/
scp scripts/backup/backup_db_env.php  u397951547@servidor:~/scripts/
scp scripts/backup/backup_alerta.php  u397951547@servidor:~/scripts/
chmod +x ~/scripts/backup_sirgdi.sh
```

Requisitos ya verificados en el servidor: `rclone` instalado en `~/bin`,
remoto `gdrive:` configurado (`rclone config`), y clave de cifrado en
`~/.backup_key` (permisos 600, generada aparte — **no vive en este repo**).

## Correo de alerta

Por defecto la alerta llega al correo `SMTP_FROM_NAME`/`SMTP_FROM_EMAIL`
configurado en el `.env` de `mto` (el sitio activo). Para enviarla a otra
dirección, agregar en ese mismo `.env` de producción:

```
BACKUP_ALERTA_EMAIL=tu-correo@ejemplo.com
```

## Programación

hPanel → Avanzado → Cron Jobs (no hay `crontab` por SSH en este hosting).
Comando a programar, una vez al día:

```
/bin/bash /home/u397951547/scripts/backup_sirgdi.sh >> /home/u397951547/backups/backup.log 2>&1
```

## Restaurar

Ver `RESTAURAR.md` en esta misma carpeta.
