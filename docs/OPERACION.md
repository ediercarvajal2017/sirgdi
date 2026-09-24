# Operación: qué hacer cuando algo falla

Guía para quien mantiene el sistema en marcha. No explica cómo usar la
aplicación — eso está dentro, en **Ayuda** (menú de usuario).

---

## Acceso

```bash
ssh -i ~/.ssh/id_ed25519_sirgdi -p 65002 u397951547@82.25.73.209
cd ~/domains/jlcserviciosintegrales.com/public_html/mto
```

El despliegue es automático: `git push origin main` hace que Hostinger vuelva a
clonar el repositorio. **No edite archivos en el servidor**: el siguiente envío
los reemplaza y el cambio se pierde sin dejar rastro.

Lo único que sobrevive a un despliegue es lo que está fuera de `public/`:
`almacenamiento/` y el `.env`.

---

## Dónde mirar primero

Todos los registros están en `almacenamiento/logs/`:

| Archivo | Qué cuenta |
|---|---|
| `cron_sla.log` | Cada pasada de la revisión de tiempos de atención |
| `cron_notificaciones.log` | Reintentos de correo: qué salió, qué falló y por qué |
| `cron_salud.log` | Revisión diaria |
| `notificaciones.log` | Cada envío individual, con su destinatario y resultado |
| `database_errors.log` | Consultas que fallaron. **Si crece, algo está mal** |
| `auditoria.log` | Accesos denegados |

```bash
tail -n 40 almacenamiento/logs/cron_notificaciones.log
grep -c "ERROR" almacenamiento/logs/notificaciones.log
```

---

## Procesos programados

Hostinger **no expone `crontab` por SSH** en este plan: el entorno está dentro
de una jaula CloudLinux y el comando está sustituido por un alias de solo
lectura. Los cron se dan de alta únicamente desde hPanel → Avanzado → Cron Jobs.

Hay tres, más el respaldo:

| Tarea | Frecuencia | Sin ella |
|---|---|---|
| `scripts/cron/evaluar_sla.php` | Cada hora | Nadie se entera de que un reporte incumplió su plazo |
| `scripts/cron/enviar_notificaciones_pendientes.php` | Cada 15 min | Un correo que falla no se reintenta nunca |
| `scripts/cron/revisar_salud.php` | Diaria | Nadie revisa los registros hasta que un cliente se queja |
| Respaldo cifrado | Diaria | — |

Los comandos exactos para pegar en hPanel están en
[`scripts/cron/README.md`](../scripts/cron/README.md).

Hostinger crea un archivo `~/.logs/cronjob_<id>` por tarea la primera vez que
corre. Si ese archivo no aparece, la tarea no se ha ejecutado ni una vez.

```bash
ls -la ~/.logs/cronjob_*
```

Cada script usa un candado de archivo: si una pasada tarda más que su intervalo,
la siguiente no se solapa, se salta.

---

## Problemas frecuentes

### El usuario ve una pantalla de error

Toda página de error muestra un **código de referencia** de ocho caracteres.
Pídaselo y búsquelo:

```bash
grep "ref=ABCD1234" almacenamiento/logs/*.log
```

En la línea está la URL, el código HTTP y el detalle técnico completo. Ese
detalle nunca se muestra en pantalla en producción, solo se registra.

### No llegan los correos

1. Compruebe que el SMTP responde: `tail -n 20 almacenamiento/logs/notificaciones.log`
2. Si hay errores de conexión, revise las variables `SMTP_*` del `.env`.
3. Los correos que fallaron quedan en cola y el cron los reintenta hasta cinco
   veces. Lo que lleve más de 48 horas sin poder enviarse caduca a propósito:
   entregar de golpe diez avisos viejos confunde más de lo que ayuda.

**Mientras tanto nadie se queda a ciegas**: la campana dentro de la aplicación
no depende del correo. Esa es toda su razón de ser.

### Una institución no recibe alertas de SLA

Casi siempre es que no tiene ningún usuario con rol Gestor ni Rector: la alerta
se genera y se descarta por no tener destinatario. El panel de Administración
Global lo marca con la insignia *"Falta N cosas"*.

```bash
tail -n 30 almacenamiento/logs/cron_sla.log
```

### El sitio devuelve 500 en todo

Suele ser un error de sintaxis en un archivo recién desplegado. Compruebe qué
versión está publicada y, si hace falta, vuelva a la anterior:

```bash
git rev-parse --short HEAD          # en el servidor
git log --oneline -5                # en local, para ver a dónde volver
```

Para revertir: `git revert <commit>` en local y `push`. No haga `reset --hard`
en el servidor — el siguiente despliegue lo deshace.

### Un logo de institución desapareció

No debería volver a pasar. Los logos se guardan en `almacenamiento/archivos/logos/`,
fuera de `public/`, precisamente porque el despliegue reemplaza esa carpeta
entera. Si ocurre, compruebe que el archivo sigue ahí y que
`?controlador=institucion&accion=logo` lo sirve.

---

## Antes de dar de alta una institución nueva

El panel de Administración Global marca cada institución como *"Lista para
operar"* o indica qué le falta. Las piezas son:

1. Al menos una **sede** activa.
2. **Categorías** de daño, con las críticas marcadas.
3. **Tiempos de atención (SLA)**. Sin ellos se usa 48 horas por defecto.
4. Un **Admin de Institución**.
5. Alguien con rol **Gestor** o **Rector** — son quienes reciben las alertas.
6. **Técnicos** a quienes asignar.

Si falta alguna, el sistema no falla: simplemente no sirve, y nadie se entera.

---

## Datos personales

El formulario público recoge datos de ciudadanos bajo la Ley 1581 de 2012. Cada
reporte enviado desde septiembre de 2026 guarda la constancia de la autorización
en `reporte.acepto_tratamiento_datos` y `fecha_aceptacion_datos`.

Los reportes anteriores tienen `0`: no se obtuvo autorización y marcarlos como
aceptados sería fabricar una prueba.

Si un ciudadano pide ejercer sus derechos (conocer, corregir, eliminar), el
contacto que ve en la política es el correo del Admin o Rector de su
institución.

---

## Pendiente conocido

- **El `client_id` compartido de rclone deja de funcionar durante 2026.** El
  respaldo lo avisa en cada ejecución. Hay que crear uno propio en Google Cloud
  Console antes de que se corte; mientras tanto el respaldo sigue funcionando.
- **`configuracion_institucion` está vacía y no se lee en ningún punto.** La
  tabla existe con formato de ticket, correo remitente, horario laboral y
  plantillas de correo. Exponerla sin cablear cada campo a un comportamiento
  crearía ajustes que no hacen nada. El de más valor es el horario laboral: hoy
  el SLA cuenta horas de calendario, así que un reporte de viernes por la tarde
  "incumple" durante el fin de semana.
