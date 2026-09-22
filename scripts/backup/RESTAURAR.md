# Cómo restaurar un backup de SIRGDI/ANA

Todos los backups quedan cifrados con AES-256 (`openssl enc -aes-256-cbc -pbkdf2`)
usando la clave guardada en `~/.backup_key` en el servidor (y en un gestor de
contraseñas fuera del servidor). Sin esa clave, los archivos `.enc` no se
pueden recuperar — guárdala bien.

## 1. Descargar el backup

Desde Google Drive (carpeta `sirgdi-backups/db/` o `sirgdi-backups/archivos/`),
o directamente del servidor:

```bash
scp -P 65002 u397951547@82.25.73.209:~/backups/db/sirgdi_bd_<FECHA>.sql.gz.enc .
```

## 2. Restaurar la base de datos

```bash
# Descifrar y descomprimir
openssl enc -d -aes-256-cbc -pbkdf2 -salt -pass file:./clave_backup \
    -in sirgdi_bd_<FECHA>.sql.gz.enc | gunzip > sirgdi_bd_<FECHA>.sql

# El dump incluye DEFINER=`usuario_original`@`host` en triggers/rutinas (por
# --routines --triggers de mysqldump). Si el usuario que restaura no es
# exactamente ese mismo usuario@host (típico: restaurar en otra BD, otro
# hosting, o un usuario de prueba), MySQL rechaza el DEFINER con "Access
# denied; you need SET USER privilege" a menos que tenga privilegio SUPER.
# Se quita para que el DEFINER quede como el usuario que ejecuta la restauración:
sed -E 's/DEFINER=`[^`]*`@`[^`]*`/DEFINER=CURRENT_USER/g' \
    sirgdi_bd_<FECHA>.sql > sirgdi_bd_<FECHA>_sin_definer.sql

# Restaurar en una BD de prueba (NUNCA directo sobre producción sin verificar antes)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sirgdi_restore_test;"
mysql -u root sirgdi_restore_test < sirgdi_bd_<FECHA>_sin_definer.sql

# Verificar: comparar conteo de filas por tabla contra el origen
mysql -u root sirgdi_restore_test -e "
  SELECT table_name, table_rows
  FROM information_schema.tables
  WHERE table_schema = 'sirgdi_restore_test'
  ORDER BY table_name;"
```

Solo después de confirmar que los datos son correctos se reemplaza la base de
datos real (`DROP DATABASE` + restaurar, o `mysql -u root sirgdi < archivo.sql`
sobre la BD existente si se acepta sobrescribir).

## 3. Restaurar archivos (evidencias/logos)

```bash
openssl enc -d -aes-256-cbc -pbkdf2 -salt -pass file:./clave_backup \
    -in mantenimiento_archivos_<FECHA>.tar.gz.enc | tar -xzf - -C /ruta/de/destino
```

Verificar que la cantidad de fotos en `almacenamiento/archivos/evidencias/`
coincide con lo esperado antes de dar la restauración por buena.

## Simulacro de restauración (hacer al menos una vez, y periódicamente después)

1. Descargar el backup más reciente (BD + archivos de un dominio).
2. Restaurar la BD en una base de prueba local (`sirgdi_restore_test`) y
   comparar conteo de filas por tabla contra producción (vía SSH, solo lectura).
3. Restaurar un paquete de archivos en una carpeta temporal y comparar la
   cantidad de fotos contra `almacenamiento/archivos/evidencias/` en producción.
4. Si algo no cuadra, corregir el script antes de confiar en él — un backup
   que nunca se probó a restaurar no es un backup confiable.
