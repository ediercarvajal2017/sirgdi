# SIRGDI / ANA

Sistema de reportes de daños para instituciones educativas. Un ciudadano o un
docente reporta un desperfecto, alguien lo clasifica y lo asigna, un técnico lo
resuelve, y un gestor valida y cierra. Multiinquilino: cada institución ve solo
sus datos.

En producción: **mto.jlcserviciosintegrales.com**

---

## Qué necesita

| | |
|---|---|
| PHP | 8.2 o superior |
| Base de datos | MariaDB 10.4+ / MySQL 8 |
| Servidor web | Apache con `mod_rewrite` y `mod_headers` |
| Dependencias | Composer (solo PHPMailer y PHPUnit) |

## Levantarlo en local

```bash
git clone https://github.com/ediercarvajal2017/sirgdi.git
cd sirgdi
composer install

# Crear la base y cargar el esquema
mysql -u root -e "CREATE DATABASE sirgdi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root sirgdi < sql/schema.sql

cp .env.ejemplo .env      # y rellenarlo (ver abajo)
```

El proyecto se sirve desde `public/`. Con XAMPP basta dejarlo en `htdocs/` y
abrir `http://localhost/reporte_danos/public/`.

### Variables obligatorias

El arranque **falla a propósito** si falta alguno de estos secretos, en lugar de
generar uno nuevo en cada petición y dejar las sesiones rotas de forma
intermitente:

| Variable | Mínimo | Para qué |
|---|---|---|
| `ENCRYPTION_KEY` | 32 caracteres | Cifra los secretos de 2FA en la base |
| `JWT_SECRET` | 32 caracteres | Firma de tokens |
| `CSRF_SALT` | 16 caracteres | Protección contra envíos forjados |

En `ENV=production` también son obligatorias `TURNSTILE_SITE_KEY` y
`TURNSTILE_SECRET_KEY`: sin ellas el formulario público quedaría sin protección
contra bots y nada lo indicaría.

## Cómo está organizado

```
app/
  controladores/   Un archivo por controlador. El enrutador los descubre solo:
                   ?controlador=gestion  ->  controlador_gestion.php
                                        ->  clase ControladorGestion
  modelos/         Acceso a datos. Toda consulta filtra por id_institucion.
  servicios/       Autenticación, autorización, notificaciones, SLA, exportación
  vistas/          Plantillas PHP. Reciben sus datos por extract()
configuracion/     config.php (única fuente), permisos_base.php
lib/               Base de datos, validación, cifrado, errores, mensajes
public/            Raíz web. index.php es el único punto de entrada
scripts/cron/      Procesos programados
sql/               Esquema y migraciones
tests/             PHPUnit
docs/              Especificación, diseño de datos, despliegue, seguridad
almacenamiento/    Subidas de usuarios y logs. NO está bajo public/ (ver abajo)
```

### Dos reglas que no son obvias

**Nada que suba un usuario puede vivir dentro de `public/`.** El despliegue
automático vuelve a clonar el repositorio y reemplaza esa carpeta entera, así
que cualquier archivo subido ahí desaparece al siguiente `push`. Los logos y las
evidencias van a `almacenamiento/` y se sirven por PHP.

**Las migraciones aditivas se aplican a producción antes de publicar el código
que lee la columna nueva.** Al revés, toda página autenticada devuelve un error
hasta que la migración corra.

## Pruebas

```bash
vendor/bin/phpunit
```

Las pruebas de integración usan la base local real y envuelven cada caso en una
transacción que se revierte, así que no dejan rastro.

## Despliegue

`git push origin main` publica en producción: Hostinger vuelve a clonar el
repositorio en cada envío. No hay paso manual.

Detalles, accesos y procesos programados: [docs/DESPLIEGUE.md](docs/DESPLIEGUE.md)
y [docs/OPERACION.md](docs/OPERACION.md).

## Documentación

| Documento | Qué contiene |
|---|---|
| [docs/OPERACION.md](docs/OPERACION.md) | Qué hacer cuando algo falla. Cron, logs, respaldos |
| [docs/DESPLIEGUE.md](docs/DESPLIEGUE.md) | Puesta en marcha en Hostinger |
| [docs/ERS-SIRGDI-v2.1.md](docs/ERS-SIRGDI-v2.1.md) | Especificación de requisitos |
| [docs/DDB-SIRGDI-v1.0.md](docs/DDB-SIRGDI-v1.0.md) | Diseño de la base de datos |
| [docs/INFORME_SEGURIDAD_2026-09.md](docs/INFORME_SEGURIDAD_2026-09.md) | Auditoría de septiembre de 2026 |
| [docs/LISTA_SEGURIDAD_DESPLIEGUE.md](docs/LISTA_SEGURIDAD_DESPLIEGUE.md) | Lista de verificación de seguridad |

### Sobre la documentación anterior

Hasta septiembre de 2026 la raíz tenía diecinueve documentos, quince de ellos
congelados en junio. Había cuatro archivos distintos describiendo la misma
funcionalidad de avisos, y dos (`AUDIT_REPORT.md` y `SISTEMA_COMPLETO.md`) que
proclamaban *"97.6%, PRODUCTION-READY"* y *"PROYECTO FINALIZADO - 100%
FUNCIONAL"*. El informe de seguridad de septiembre desmiente esa conclusión de
forma explícita: encontró una escalada de privilegios crítica.

Se retiraron. Siguen en el historial de git para quien necesite consultarlos,
pero ya no son lo primero que lee alguien que abre el repositorio.
