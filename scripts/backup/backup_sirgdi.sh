#!/bin/bash
# Backup diario de SIRGDI/ANA: BD compartida (mysqldump) + evidencias/logos no
# reproducibles de ambos dominios. Todo cifrado con AES-256 antes de subir a
# Google Drive vía rclone. Retención: 30 días (RNF-14).
#
# Plantilla versionada en el repo (scripts/backup/backup_sirgdi.sh). La copia que
# se ejecuta de verdad vive en ~/scripts/backup_sirgdi.sh en el servidor, fuera de
# public_html de ambos dominios: nunca es accesible por web y el `git push` de
# cada deploy no la toca ni la borra.
#
# Programado vía hPanel > Avanzado > Cron Jobs (no hay acceso a crontab por SSH
# en este hosting compartido).

set -euo pipefail

HOME_DIR="$HOME"
SITIO_A="$HOME_DIR/domains/ediertech.com/public_html/mantenimiento"
SITIO_B="$HOME_DIR/domains/jlcserviciosintegrales.com/public_html/mto"
BACKUP_DIR="$HOME_DIR/backups"
DB_DIR="$BACKUP_DIR/db"
ARCHIVOS_DIR="$BACKUP_DIR/archivos"
LOG="$BACKUP_DIR/backup.log"
CLAVE="$HOME_DIR/.backup_key"
RCLONE="$HOME_DIR/bin/rclone"
REMOTO="gdrive:sirgdi-backups"
ALERTA_PHP="$HOME_DIR/scripts/backup_alerta.php"
DB_ENV_PHP="$HOME_DIR/scripts/backup_db_env.php"
FECHA="$(date +%Y%m%d_%H%M%S)"

mkdir -p "$DB_DIR" "$ARCHIVOS_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"
}

alertar_fallo() {
    local linea=$?
    log "FALLO en la línea $linea"
    if [ -f "$ALERTA_PHP" ]; then
        php "$ALERTA_PHP" "$SITIO_B" "Backup de SIRGDI falló en la línea $linea del script." || true
    fi
}
trap alertar_fallo ERR

log "=== Inicio backup $FECHA ==="

# --- 1. Base de datos compartida por ambos dominios ---
DUMP="$DB_DIR/sirgdi_bd_${FECHA}.sql.gz"

ENV_TMP="$(mktemp)"
php "$DB_ENV_PHP" "$SITIO_B" > "$ENV_TMP"
chmod 600 "$ENV_TMP"
source "$ENV_TMP"
rm -f "$ENV_TMP"

# "localhost" con -h fuerza TCP (y a veces resuelve a ::1) en vez de socket Unix,
# que es donde este usuario de BD sí tiene permisos concedidos.
MYSQLDUMP_ARGS=(--single-transaction --quick --routines --triggers -u "$DB_USER" "$DB_NAME")
if [ "$DB_HOST" = "localhost" ]; then
    MYSQLDUMP_ARGS=(--protocol=socket "${MYSQLDUMP_ARGS[@]}")
else
    MYSQLDUMP_ARGS=(-h "$DB_HOST" -P "$DB_PORT" "${MYSQLDUMP_ARGS[@]}")
fi

MYSQL_PWD="$DB_PASS" mysqldump "${MYSQLDUMP_ARGS[@]}" | gzip > "$DUMP"
unset DB_PASS

openssl enc -aes-256-cbc -pbkdf2 -salt -pass file:"$CLAVE" -in "$DUMP" -out "${DUMP}.enc"
rm -f "$DUMP"
log "BD respaldada: $(basename "${DUMP}.enc") ($(du -h "${DUMP}.enc" | cut -f1))"

# --- 2. Archivos no reproducibles de cada dominio (evidencias + logos) ---
for PAR in "mantenimiento:$SITIO_A" "mto:$SITIO_B"; do
    NOMBRE="${PAR%%:*}"
    RUTA="${PAR#*:}"
    PAQUETE="$ARCHIVOS_DIR/${NOMBRE}_archivos_${FECHA}.tar.gz"

    RUTAS_A_EMPACAR=()
    [ -d "$RUTA/almacenamiento/archivos/evidencias" ] && RUTAS_A_EMPACAR+=("almacenamiento/archivos/evidencias")
    [ -d "$RUTA/almacenamiento/archivos/logos" ] && RUTAS_A_EMPACAR+=("almacenamiento/archivos/logos")

    if [ ${#RUTAS_A_EMPACAR[@]} -eq 0 ]; then
        log "AVISO: $NOMBRE no tiene carpetas de evidencias/logos, se omite"
        continue
    fi

    tar -czf "$PAQUETE" -C "$RUTA" "${RUTAS_A_EMPACAR[@]}"
    openssl enc -aes-256-cbc -pbkdf2 -salt -pass file:"$CLAVE" -in "$PAQUETE" -out "${PAQUETE}.enc"
    rm -f "$PAQUETE"
    log "Archivos de $NOMBRE respaldados: $(basename "${PAQUETE}.enc") ($(du -h "${PAQUETE}.enc" | cut -f1))"
done

# --- 3. Subida a Google Drive (cifrados) ---
"$RCLONE" copy "${DUMP}.enc" "$REMOTO/db/" --log-file="$LOG" --log-level INFO
"$RCLONE" copy "$ARCHIVOS_DIR/" "$REMOTO/archivos/" --include "*_${FECHA}.tar.gz.enc" --log-file="$LOG" --log-level INFO

# --- 4. Rotación: borrar todo lo de más de 30 días, local y remoto ---
find "$DB_DIR" -name '*.sql.gz.enc' -mtime +30 -delete
find "$ARCHIVOS_DIR" -name '*.tar.gz.enc' -mtime +30 -delete
"$RCLONE" delete "$REMOTO" --min-age 30d --log-file="$LOG" --log-level INFO || true

log "=== Fin backup $FECHA: OK ==="
