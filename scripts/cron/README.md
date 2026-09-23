# Tareas programadas (cron)

Sin estas tareas el sistema **no avisa por su cuenta**: los correos de SLA no
salen y las notificaciones que fallaron al enviarse se pierden. Registrarlas es
parte del despliegue, no un extra.

A diferencia de los scripts de respaldo (`scripts/backup/`, que viven fuera del
repositorio en `~/scripts/` y hay que actualizar a mano), estos se despliegan
solos con cada `git push`: la carpeta `scripts/` está dentro del repositorio y
`.htaccess` la bloquea por web (403). Además, cada script se niega a ejecutarse
fuera de la línea de comandos.

## Qué hace cada uno

| Script | Frecuencia sugerida | Qué hace |
|---|---|---|
| `evaluar_sla.php` | cada hora | Recorre los reportes abiertos de cada institución activa. Si el SLA está por vencer o ya venció, escala la urgencia a URGENTE, lo anota en auditoría y avisa por correo a gestor, rector y admin. |
| `enviar_notificaciones_pendientes.php` | cada 15 minutos | Reintenta los correos que no salieron. Anota el motivo y el número de intentos; tras 5 intentos marca la notificación como `fallido` y deja de insistir. |

Ambos usan un candado de archivo, así que dos ejecuciones no se pisan si una
tarda más que su intervalo.

## Registrar en Hostinger

Hostinger no expone `crontab` por SSH en este plan: los cron solo se dan de
alta desde el panel.

hPanel → **Avanzado** → **Cron Jobs** → *Crear nuevo cron job*, tipo
"Comando personalizado". Los dos comandos ya llevan la ruta real del
despliegue actual, así que se pegan tal cual.

**SLA — cada hora, al minuto 5:**

```
/usr/bin/php /home/u397951547/domains/jlcserviciosintegrales.com/public_html/mto/scripts/cron/evaluar_sla.php >/dev/null 2>&1
```

**Notificaciones pendientes — cada 15 minutos:**

```
/usr/bin/php /home/u397951547/domains/jlcserviciosintegrales.com/public_html/mto/scripts/cron/enviar_notificaciones_pendientes.php >/dev/null 2>&1
```

Si el panel pide la frecuencia por separado en vez de una línea de cron, usar
"Cada hora" para el primero y "Cada 15 minutos" para el segundo.

Se redirige la salida a `/dev/null` porque cada script ya escribe su propio
registro; si no, Hostinger enviaría un correo en cada ejecución.

Al cambiar de dominio o de carpeta de despliegue hay que actualizar la ruta de
ambos comandos.

## Dónde mirar cuando algo no cuadra

Cada script deja su propio archivo en `almacenamiento/logs/`:

- `cron_sla.log` — cuántos reportes se revisaron y cuáles se escalaron
- `cron_notificaciones.log` — cuántos correos salieron, fallaron o se dieron
  por perdidos

Ojo: `almacenamiento/logs/` está en `.gitignore`, así que estos archivos viven
solo en el servidor. Si una notificación aparece como `fallido`, el motivo
concreto queda en la columna `razon_fallo` de la tabla `notificacion`.

## Probar a mano antes de confiar en el cron

Conectado por SSH, desde la raíz del sitio:

```bash
php scripts/cron/evaluar_sla.php
php scripts/cron/enviar_notificaciones_pendientes.php
```

Ambos imprimen un resumen y terminan con código 0 si todo fue bien.
