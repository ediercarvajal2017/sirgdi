# Documento de Diseño de Base de Datos (DDB)
## SIRGDI — Sistema Integrado de Reporte y Gestión de Daños Institucionales

| Campo | Detalle |
|---|---|
| **Versión del documento** | 1.0 |
| **Estado** | Aprobado para desarrollo |
| **Basado en** | ERS-SIRGDI-v2.1.md |
| **Motor de BD** | MySQL 8.0+ · Engine InnoDB · Charset utf8mb4 |
| **Archivos asociados** | `sql/schema.sql` (DDL) · `sql/seeds.sql` (semilla) |

### Control de versiones

| Versión | Fecha | Descripción | Responsable |
|---|---|---|---|
| **1.0** | 2026-06-15 | Diseño inicial completo: 24 tablas, 3 triggers, 20+ índices, DDL y semilla | Equipo de análisis |

---

## 1. Propósito y Alcance

Este documento formaliza el modelo de datos del SIRGDI. Define la estructura lógica y física de todas las tablas, sus relaciones, restricciones de integridad, estrategia de aislamiento multitenant, índices de rendimiento y los datos semilla requeridos para inicializar el sistema.

El modelo cubre la totalidad de los requerimientos funcionales del ERS-SIRGDI-v2.1 y es la referencia autoritativa para el equipo de desarrollo.

---

## 2. Estrategia Multitenant

El SIRGDI adopta el patrón **Single-Schema Multitenant** (base de datos compartida, datos separados lógicamente):

- **Una sola base de datos** `sirgdi` sirve a todas las instituciones.
- Todas las tablas de negocio incluyen la columna `id_institucion BIGINT UNSIGNED NOT NULL` con clave foránea a `institucion`.
- **Toda consulta filtra** por `id_institucion` del usuario autenticado — es obligatorio, no opcional (RN-01).
- Índices compuestos `(id_institucion, ...)` en todas las tablas principales garantizan rendimiento sin cruzar datos entre tenants.
- La tabla `institucion` es la raíz: eliminarla en cascada destruye todos sus datos asociados (FK `ON DELETE CASCADE`).

### 2.1 Tablas sin `id_institucion` (catálogos globales)
Son compartidas por todas las instituciones y solo puede modificarlas el Superadministrador:

`urgencia` · `estado` · `etapa_evidencia` · `rol` · `permiso` · `rol_permiso`

### 2.2 Patrón de consulta segura (ejemplo PHP)
```php
// En cada consulta de negocio, id_institucion proviene de la sesión autenticada
$stmt = $pdo->prepare(
    "SELECT * FROM reporte
     WHERE id_institucion = :inst AND id_estado = :estado"
);
$stmt->execute([':inst' => $_SESSION['id_institucion'], ':estado' => $estadoId]);
```

### 2.3 Token de seguimiento público (RN-11)
Cada reporte tiene un `token_seguimiento_publico` (UUID v4, único global). Permite al reportante consultar el estado de **su** reporte vía enlace sin autenticación. El endpoint público solo devuelve datos del reporte correspondiente a ese token; nunca lista reportes.

---

## 3. Diagrama Entidad-Relación

### 3.1 Vista general (cardinalidades)

```
INSTITUCIÓN (1)
│
├──(1:1)── CONFIGURACION_INSTITUCION
│
├──(1:N)── USUARIO ──(M:M)── ROL ──(M:M)── PERMISO
│
├──(1:N)── SEDE
│           └──(1:N)── AREA
│                      └──(1:N)── SUBAREA
│
├──(1:N)── CATEGORIA
│           └──(1:N)── SUBCATEGORIA
│
├──(1:N)── SLA ──(N:1)── URGENCIA (global)
│               └──(N:1)── CATEGORIA
│
├──(1:N)── REPORTE ────────────────────────────────────────────┐
│           ├──(N:1)── USUARIO (reportante)                    │
│           ├──(N:1)── USUARIO (técnico asignado)             │
│           ├──(N:1)── USUARIO (gestor asignador)             │
│           ├──(N:1)── SEDE / AREA / SUBAREA                  │
│           ├──(N:1)── CATEGORIA / SUBCATEGORIA               │
│           ├──(N:1)── URGENCIA x2 (declarada/calculada)      │
│           ├──(N:1)── ESTADO (global)                        │
│           │                                                   │
│           ├──(1:1)── INFORME_INTERVENCION ◄──────────────────┤
│           ├──(1:N)── EVIDENCIA (etapa: Antes/Durante/Después)│
│           ├──(1:N)── COMENTARIO_INTERNO                      │
│           ├──(1:N)── TRANSICION_ESTADO (auditoría)          │
│           ├──(1:1)── ENCUESTA_SATISFACCION                   │
│           └──(1:N)── NOTIFICACION                            │
│                                                               │
├──(1:N)── REGISTRO_AUDITORIA                                  │
│                                                               │
└──(1:N)── PLANTILLA_SOLUCION ─────────────────────────────────┘
            └──(N:1, opt)── REPORTE (origen de la plantilla)
```

### 3.2 Bloque central: tabla REPORTE

```
┌─────────────────────────────────────────────┐
│                   REPORTE                   │
├─────────────────────────────────────────────┤
│ PK  id_reporte                              │
│ FK  id_institucion  ──► institucion         │
│     numero_ticket  (UNIQUE por institución) │
│     token_seguimiento_publico  (UUID UNIQUE)│
│ FK  id_reportante   ──► usuario             │
│     nombre/cargo/correo/tel_reportante      │
│     es_anonimo                              │
│ FK  id_sede         ──► sede                │
│ FK  id_area         ──► area                │
│ FK  id_subarea      ──► subarea (NULL)      │
│     referencia_ubicacion_libre              │
│ FK  id_categoria    ──► categoria           │
│ FK  id_subcategoria ──► subcategoria (NULL) │
│ FK  id_urgencia_declarada ──► urgencia      │
│ FK  id_urgencia_calculada ──► urgencia      │
│     descripcion_problema (TEXT NOT NULL)    │
│ FK  id_estado       ──► estado              │
│ FK  id_tecnico_asignado ──► usuario (NULL)  │
│ FK  id_gestor_asignador ──► usuario (NULL)  │
│     fecha_hora_registro  (IMMUTABLE)        │
│     fecha_hora_asignacion                   │
│     fecha_hora_inicio_tecnico               │
│     fecha_hora_solucionado                  │
│     fecha_hora_cierre                       │
│     fecha_pausa_sla                         │
│     total_horas_pausadas                    │
│     justificacion_anulacion                 │
│     es_reutilizable_solucion                │
└─────────────────────────────────────────────┘
```

---

## 4. Diccionario de Datos

### Convenciones
- `PK` = Primary Key · `FK` = Foreign Key · `UQ` = Unique · `NN` = Not Null
- Tipos: `TINYINT(1)` = booleano · `JSON` = objeto/array JSON nativo MySQL 8

---

### 4.1 Catálogos Globales

#### Tabla: `urgencia`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_urgencia` | TINYINT UNSIGNED | NN | PK, AI | — | Identificador del nivel de urgencia |
| `nombre` | VARCHAR(30) | NN | — | — | Etiqueta: No urgente, Moderado, Importante, Urgente |
| `nivel_numero` | TINYINT UNSIGNED | NN | UQ | — | Valor ordinal 0–3 para ordenamiento y comparación |
| `color_hex` | CHAR(7) | NN | — | `#CCCCCC` | Color para representación visual en UI (#RRGGBB) |

#### Tabla: `estado`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_estado` | TINYINT UNSIGNED | NN | PK, AI | — | Identificador del estado |
| `nombre` | VARCHAR(30) | NN | UQ | — | Nombre del estado (Registrado, Asignado…, Cerrado, Anulado) |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | Descripción del estado |
| `es_terminal` | TINYINT(1) | NN | — | 0 | 1 = estado final, no admite más transiciones |

#### Tabla: `etapa_evidencia`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_etapa` | TINYINT UNSIGNED | NN | PK, AI | — | Identificador de la etapa |
| `nombre` | VARCHAR(20) | NN | — | — | Antes, Durante, Después |
| `orden` | TINYINT UNSIGNED | NN | UQ | — | Orden de visualización (1, 2, 3) |

#### Tabla: `rol`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_rol` | TINYINT UNSIGNED | NN | PK, AI | — | Identificador del rol |
| `nombre_rol` | VARCHAR(50) | NN | UQ | — | Reportante, Técnico, Gestor, Rector, Admin de Institución, Superadministrador |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | Descripción del rol |

#### Tabla: `permiso`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_permiso` | SMALLINT UNSIGNED | NN | PK, AI | — | Identificador del permiso |
| `codigo` | VARCHAR(60) | NN | UQ | — | Código máquina: `crear_reporte`, `asignar_tecnico`, etc. |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | Descripción legible del permiso |
| `modulo` | VARCHAR(40) | NN | — | — | Módulo al que pertenece: reportes, gestion, tecnico, cierre, etc. |

#### Tabla: `rol_permiso` (tabla intermedia M:M global)
| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_rol` | TINYINT UNSIGNED | NN | PK, FK → rol | Rol |
| `id_permiso` | SMALLINT UNSIGNED | NN | PK, FK → permiso | Permiso asignado al rol |

---

### 4.2 Institución y Configuración

#### Tabla: `institucion`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_institucion` | BIGINT UNSIGNED | NN | PK, AI | — | Identificador único del tenant |
| `nombre` | VARCHAR(150) | NN | — | — | Nombre oficial de la institución educativa |
| `logo_url` | VARCHAR(500) | SÍ | — | NULL | URL del logo institucional |
| `es_activa` | TINYINT(1) | NN | — | 1 | 0 = institución suspendida (no puede acceder) |
| `fecha_creacion` | DATETIME | NN | — | NOW() | Timestamp de alta del tenant |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | Última modificación |

#### Tabla: `configuracion_institucion`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_configuracion` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | UQ, FK → institucion | — | Relación 1:1 con institución |
| `formato_numero_ticket` | VARCHAR(60) | NN | — | `IE-{INST}-{YYYY}-{SEQ5}` | Plantilla del número de ticket. Variables: `{INST}`, `{YYYY}`, `{SEQ5}` |
| `correo_remitente` | VARCHAR(150) | SÍ | — | NULL | Dirección "De:" para correos del sistema |
| `es_reporte_anonimo_permitido` | TINYINT(1) | NN | — | 0 | RF-09: habilita reportes anónimos |
| `tiempo_sesion_minutos` | SMALLINT UNSIGNED | NN | — | 30 | Expiración de sesión por inactividad (RNF-05) |
| `horas_laborales_inicio` | TINYINT UNSIGNED | NN | — | 7 | Hora de inicio de jornada (0-23) para cálculo SLA |
| `horas_laborales_fin` | TINYINT UNSIGNED | NN | — | 17 | Hora de fin de jornada (0-23) para cálculo SLA |
| `dias_no_laborales_json` | JSON | SÍ | — | NULL | Días no laborales: `[6,7]` = sáb y dom |
| `plantilla_asunto_registro` | VARCHAR(200) | SÍ | — | NULL | Asunto del correo de confirmación de registro |
| `plantilla_asunto_asignado` | VARCHAR(200) | SÍ | — | NULL | Asunto del correo de asignación |
| `plantilla_asunto_cerrado` | VARCHAR(200) | SÍ | — | NULL | Asunto del correo de cierre |
| `plantilla_cuerpo_registro` | MEDIUMTEXT | SÍ | — | NULL | Cuerpo HTML del correo de confirmación |
| `plantilla_cuerpo_asignado` | MEDIUMTEXT | SÍ | — | NULL | Cuerpo HTML del correo de asignación |
| `plantilla_cuerpo_cerrado` | MEDIUMTEXT | SÍ | — | NULL | Cuerpo HTML del correo de cierre |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | — |

---

### 4.3 Usuarios y Roles

#### Tabla: `usuario`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_usuario` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | Tenant al que pertenece (RN-01) |
| `nombre_completo` | VARCHAR(150) | NN | — | — | Nombre legal completo |
| `correo_electronico` | VARCHAR(150) | NN | UQ(inst,correo) | — | Correo de acceso; único dentro de la institución |
| `telefono` | VARCHAR(20) | SÍ | — | NULL | Teléfono de contacto opcional |
| `cargo_descripcion` | VARCHAR(100) | SÍ | — | NULL | Cargo o rol institucional del usuario |
| `hash_contrasena` | VARCHAR(255) | NN | — | — | Hash bcrypt/Argon2 (factor ≥ 12). RNF-01 |
| `activo` | TINYINT(1) | NN | — | 1 | 0 = cuenta deshabilitada |
| `requiere_2fa` | TINYINT(1) | NN | — | 0 | 1 = se exige 2FA al iniciar sesión (RF-01) |
| `totp_secret` | VARCHAR(100) | SÍ | — | NULL | Secreto TOTP cifrado en AES-256 (RF-01) |
| `token_reset_pass` | VARCHAR(100) | SÍ | — | NULL | Token de restablecimiento de contraseña (RF-05) |
| `token_reset_expira` | DATETIME | SÍ | — | NULL | Expiración del token de restablecimiento |
| `ultima_actividad` | DATETIME | SÍ | — | NULL | Para cálculo de expiración de sesión (RNF-05) |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | — |

**Índice único:** `(id_institucion, correo_electronico)` — correo único dentro de cada institución.

#### Tabla: `usuario_rol` (tabla intermedia M:M)
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_usuario_rol` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_usuario` | BIGINT UNSIGNED | NN | FK → usuario | — | Usuario |
| `id_rol` | TINYINT UNSIGNED | NN | FK → rol | — | Rol asignado |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | Contexto de institución (para auditoría y FK) |
| `fecha_asignacion` | DATETIME | NN | — | NOW() | Cuándo se asignó el rol |

**Índice único:** `(id_usuario, id_rol)` — un usuario no puede tener el mismo rol dos veces.

---

### 4.4 Estructura de Ubicación

#### Tabla: `sede`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_sede` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | Tenant propietario |
| `nombre` | VARCHAR(100) | NN | — | — | Ej.: Sede Principal, Sede Norte |
| `direccion` | VARCHAR(255) | SÍ | — | NULL | Dirección física |
| `activa` | TINYINT(1) | NN | — | 1 | 0 = sede deshabilitada |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

#### Tabla: `area`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_area` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_sede` | BIGINT UNSIGNED | NN | FK → sede | — | Sede a la que pertenece |
| `nombre` | VARCHAR(100) | NN | — | — | Ej.: Bloque A, Bloque B, Zona Deportiva |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | — |
| `activa` | TINYINT(1) | NN | — | 1 | — |
| `orden` | SMALLINT UNSIGNED | NN | — | 0 | Orden de visualización en selectores |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

#### Tabla: `subarea`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_subarea` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_area` | BIGINT UNSIGNED | NN | FK → area | — | Área a la que pertenece |
| `nombre` | VARCHAR(100) | NN | — | — | Ej.: Aula 201, Baño hombres, Laboratorio |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | — |
| `activa` | TINYINT(1) | NN | — | 1 | — |
| `orden` | SMALLINT UNSIGNED | NN | — | 0 | — |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

---

### 4.5 Clasificación de Daños

#### Tabla: `categoria`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_categoria` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `nombre` | VARCHAR(80) | NN | — | — | Ej.: Eléctrico, Sanitario, Seguridad |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | — |
| `es_critica_escalada` | TINYINT(1) | NN | — | 0 | **RN-06:** 1 = escala automáticamente a urgencia "Urgente" |
| `activa` | TINYINT(1) | NN | — | 1 | — |
| `orden` | SMALLINT UNSIGNED | NN | — | 0 | — |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

#### Tabla: `subcategoria`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_subcategoria` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_categoria` | BIGINT UNSIGNED | NN | FK → categoria | — | Categoría padre |
| `nombre` | VARCHAR(100) | NN | — | — | Ej.: Luminarias, Fugas de agua |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | — |
| `activa` | TINYINT(1) | NN | — | 1 | — |
| `orden` | SMALLINT UNSIGNED | NN | — | 0 | — |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

---

### 4.6 SLA

#### Tabla: `sla`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_sla` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_categoria` | BIGINT UNSIGNED | SÍ | FK → categoria | NULL | NULL = aplica a todas las categorías |
| `id_urgencia` | TINYINT UNSIGNED | NN | FK → urgencia | — | Nivel de urgencia al que aplica este SLA |
| `tiempo_respuesta_horas` | SMALLINT UNSIGNED | NN | — | — | Horas hábiles máximas para asignar técnico |
| `tiempo_resolucion_horas` | SMALLINT UNSIGNED | NN | — | — | Horas hábiles máximas para resolver y cerrar |
| `activo` | TINYINT(1) | NN | — | 1 | — |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | — |

**Índice único:** `(id_institucion, id_categoria, id_urgencia)` — un SLA por combinación.

---

### 4.7 Reporte (Entidad Central)

#### Tabla: `reporte`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_reporte` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | Aislamiento multitenant (RN-01) |
| `numero_ticket` | VARCHAR(30) | NN | UQ(inst,ticket) | — | Generado automáticamente, **inmutable** (RN-07) |
| `token_seguimiento_publico` | CHAR(36) | NN | UQ | — | UUID para enlace público sin login (RN-11) |
| `id_reportante` | BIGINT UNSIGNED | NN | FK → usuario | — | Usuario que registra el reporte |
| `nombre_reportante` | VARCHAR(150) | NN | — | — | Desnormalizado (por si el usuario cambia datos o es anónimo) |
| `cargo_reportante` | VARCHAR(100) | SÍ | — | NULL | — |
| `correo_reportante` | VARCHAR(150) | NN | — | — | — |
| `telefono_reportante` | VARCHAR(20) | SÍ | — | NULL | — |
| `es_anonimo` | TINYINT(1) | NN | — | 0 | RF-09: el gestor ve los datos, no se difunden |
| `id_sede` | BIGINT UNSIGNED | NN | FK → sede | — | **RN-02:** NOT NULL |
| `id_area` | BIGINT UNSIGNED | NN | FK → area | — | **RN-02:** NOT NULL |
| `id_subarea` | BIGINT UNSIGNED | SÍ | FK → subarea | NULL | Opcional |
| `referencia_ubicacion_libre` | VARCHAR(255) | SÍ | — | NULL | Detalle adicional de ubicación |
| `id_categoria` | BIGINT UNSIGNED | NN | FK → categoria | — | **RN-02:** NOT NULL |
| `id_subcategoria` | BIGINT UNSIGNED | SÍ | FK → subcategoria | NULL | — |
| `id_urgencia_declarada` | TINYINT UNSIGNED | NN | FK → urgencia | — | Nivel declarado por el reportante |
| `id_urgencia_calculada` | TINYINT UNSIGNED | NN | FK → urgencia | — | Nivel real después de aplicar RN-06 (puede ser mayor) |
| `descripcion_problema` | TEXT | NN | — | — | **RN-02:** NOT NULL |
| `id_estado` | TINYINT UNSIGNED | NN | FK → estado | — | Estado actual del reporte |
| `id_tecnico_asignado` | BIGINT UNSIGNED | SÍ | FK → usuario | NULL | NULL hasta asignación |
| `id_gestor_asignador` | BIGINT UNSIGNED | SÍ | FK → usuario | NULL | Gestor/Rector que asignó |
| `fecha_hora_registro` | DATETIME | NN | — | NOW() | **RN-12:** generada por sistema, **inmutable** (trigger TRG-01) |
| `fecha_hora_asignacion` | DATETIME | SÍ | — | NULL | Cuándo se asignó a un técnico |
| `fecha_hora_inicio_tecnico` | DATETIME | SÍ | — | NULL | Cuándo el técnico inició la atención |
| `fecha_hora_solucionado` | DATETIME | SÍ | — | NULL | Cuándo el técnico marcó "Solucionado" |
| `fecha_hora_cierre` | DATETIME | SÍ | — | NULL | Cuándo fue cerrado formalmente |
| `fecha_pausa_sla` | DATETIME | SÍ | — | NULL | **RN-10:** inicio de la pausa del SLA (estado Devuelto) |
| `total_horas_pausadas` | DECIMAL(8,2) | NN | — | 0 | Horas acumuladas fuera del SLA (RN-10) |
| `justificacion_anulacion` | TEXT | SÍ | — | NULL | Obligatoria al anular (RN-08) |
| `es_reutilizable_solucion` | TINYINT(1) | NN | — | 0 | RF-20: si 1, genera plantilla de solución al cerrar |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | — |

---

### 4.8 Informe de Intervención

#### Tabla: `informe_intervencion`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_informe` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_reporte` | BIGINT UNSIGNED | NN | UQ, FK → reporte | — | **1:1 con reporte** — máximo un informe activo |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_usuario_tecnico` | BIGINT UNSIGNED | NN | FK → usuario | — | **RN-05:** debe coincidir con `reporte.id_tecnico_asignado` |
| `descripcion_actividades` | TEXT | NN | — | — | Descripción de lo realizado |
| `causa_raiz` | TEXT | SÍ | — | NULL | Causa identificada del daño |
| `solucion_implementada` | TEXT | NN | — | — | Solución aplicada |
| `fecha_hora_inicio` | DATETIME | NN | — | — | Inicio de la intervención |
| `fecha_hora_fin` | DATETIME | SÍ | — | NULL | Fin de la intervención |
| `materiales_utilizados_json` | JSON | SÍ | — | NULL | Array: `[{"nombre":"Cemento","cantidad":10,"unidad_medida":"kg"}]` |
| `costo_estimado` | DECIMAL(12,2) | SÍ | — | NULL | Costo estimado de la reparación (RF-17, opcional) |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | — |

---

### 4.9 Evidencia Fotográfica

#### Tabla: `evidencia`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_evidencia` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_reporte` | BIGINT UNSIGNED | NN | FK → reporte | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_etapa` | TINYINT UNSIGNED | NN | FK → etapa_evidencia | — | 1=Antes, 2=Durante, 3=Después |
| `url_archivo` | VARCHAR(500) | NN | — | — | Ruta no pública en el storage del servidor |
| `nombre_archivo_original` | VARCHAR(255) | NN | — | — | Nombre original para la descarga |
| `tipo_mime` | VARCHAR(80) | NN | — | — | Validado antes de guardar (RNF-04) |
| `tamanio_bytes` | INT UNSIGNED | NN | — | — | Tamaño del archivo |
| `hash_archivo` | CHAR(64) | SÍ | — | NULL | SHA-256 para verificación de integridad |
| `descripcion` | VARCHAR(255) | SÍ | — | NULL | Comentario opcional sobre la evidencia |
| `cargada_por` | BIGINT UNSIGNED | NN | FK → usuario | — | Usuario que cargó el archivo |
| `fecha_hora_carga` | DATETIME | NN | — | NOW() | — |

**Índice:** `(id_reporte, id_etapa)` — para validar las 3 etapas obligatorias (RN-03).

---

### 4.10 Comentario Interno

#### Tabla: `comentario_interno`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_comentario` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_reporte` | BIGINT UNSIGNED | NN | FK → reporte | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_usuario_autor` | BIGINT UNSIGNED | NN | FK → usuario | — | Autor del comentario |
| `texto` | TEXT | NN | — | — | Contenido del comentario |
| `es_editado` | TINYINT(1) | NN | — | 0 | 1 = fue editado posteriormente |
| `fecha_hora_edicion` | DATETIME | SÍ | — | NULL | Cuándo fue editado |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

---

### 4.11 Historial de Estados (Trazabilidad)

#### Tabla: `transicion_estado`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_transicion` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_reporte` | BIGINT UNSIGNED | NN | FK → reporte | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_estado_origen` | TINYINT UNSIGNED | SÍ | FK → estado | NULL | NULL en la creación del reporte |
| `id_estado_destino` | TINYINT UNSIGNED | NN | FK → estado | — | Estado al que transiciona |
| `id_usuario_ejecutor` | BIGINT UNSIGNED | NN | FK → usuario | — | Quién realizó el cambio |
| `comentario` | TEXT | SÍ | — | NULL | Justificación opcional (obligatoria al devolver/anular) |
| `ip_origen` | VARCHAR(45) | SÍ | — | NULL | IP del cliente (IPv4/IPv6) |
| `fecha_hora_transicion` | DATETIME | NN | — | NOW() | — |

---

### 4.12 Notificaciones

#### Tabla: `notificacion`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_notificacion` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_reporte` | BIGINT UNSIGNED | SÍ | FK → reporte | NULL | NULL para notificaciones generales (ej.: informe periódico) |
| `id_usuario_destinatario` | BIGINT UNSIGNED | NN | FK → usuario | — | Destinatario del correo |
| `tipo_evento` | VARCHAR(60) | NN | — | — | `reporte_registrado`, `reporte_asignado`, `sla_vencido`, `reporte_cerrado`, etc. |
| `asunto` | VARCHAR(250) | NN | — | — | Asunto del correo |
| `cuerpo_html` | MEDIUMTEXT | NN | — | — | Cuerpo HTML del correo |
| `estado_envio` | ENUM('pendiente','enviado','fallido') | NN | — | 'pendiente' | Estado del envío |
| `fecha_programada` | DATETIME | NN | — | NOW() | Cuándo debe enviarse |
| `fecha_enviada` | DATETIME | SÍ | — | NULL | Cuándo fue enviado efectivamente |
| `razon_fallo` | TEXT | SÍ | — | NULL | Detalle del error si falla el envío |
| `intentos` | TINYINT UNSIGNED | NN | — | 0 | Número de intentos de envío |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |

**Índice de cola:** `(id_institucion, estado_envio, fecha_programada)` — para el proceso de envío en batch.

---

### 4.13 Encuesta de Satisfacción

#### Tabla: `encuesta_satisfaccion`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_encuesta` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_reporte` | BIGINT UNSIGNED | NN | UQ, FK → reporte | — | **1:1 con reporte** |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_usuario_reportante` | BIGINT UNSIGNED | NN | FK → usuario | — | A quién se le envió la encuesta |
| `puntuacion` | TINYINT UNSIGNED | SÍ | — | NULL | 1–5 estrellas. NULL = no respondió (CHECK constraint) |
| `comentario` | TEXT | SÍ | — | NULL | Comentario abierto del reportante |
| `fue_respondida` | TINYINT(1) | NN | — | 0 | 1 = se completó la encuesta |
| `fecha_enviada` | DATETIME | NN | — | NOW() | Cuándo se envió al reportante |
| `fecha_completada` | DATETIME | SÍ | — | NULL | Cuándo fue respondida |

---

### 4.14 Registro de Auditoría

#### Tabla: `registro_auditoria`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_auditoria` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | SÍ | FK → institucion | NULL | NULL para acciones globales del sistema |
| `id_usuario` | BIGINT UNSIGNED | SÍ | FK → usuario | NULL | NULL para procesos automáticos |
| `accion` | VARCHAR(80) | NN | — | — | `crear_reporte`, `cerrar_reporte`, `asignar_tecnico`, `login_exitoso`, etc. |
| `entidad` | VARCHAR(50) | NN | — | — | Nombre de la tabla afectada |
| `id_entidad` | BIGINT UNSIGNED | SÍ | — | NULL | PK del registro afectado |
| `datos_anteriores_json` | JSON | SÍ | — | NULL | Valores antes del cambio |
| `datos_nuevos_json` | JSON | SÍ | — | NULL | Valores después del cambio |
| `ip_origen` | VARCHAR(45) | SÍ | — | NULL | IP del cliente |
| `user_agent` | VARCHAR(500) | SÍ | — | NULL | Navegador/cliente |
| `fecha_hora_accion` | DATETIME | NN | — | NOW() | — |

---

### 4.15 Base de Conocimiento (Plantillas de Solución)

#### Tabla: `plantilla_solucion`
| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---|---|---|---|---|---|
| `id_plantilla` | BIGINT UNSIGNED | NN | PK, AI | — | — |
| `id_institucion` | BIGINT UNSIGNED | NN | FK → institucion | — | — |
| `id_categoria` | BIGINT UNSIGNED | SÍ | FK → categoria | NULL | NULL = aplica a todas las categorías |
| `id_subcategoria` | BIGINT UNSIGNED | SÍ | FK → subcategoria | NULL | — |
| `titulo` | VARCHAR(150) | NN | — | — | Título descriptivo de la solución |
| `descripcion_problema` | TEXT | SÍ | — | NULL | Descripción típica del problema |
| `descripcion_solucion` | TEXT | NN | — | — | Solución documentada |
| `pasos_json` | JSON | SÍ | — | NULL | Array de pasos: `[{"orden":1,"descripcion":"..."}]` |
| `materiales_json` | JSON | SÍ | — | NULL | Materiales típicos: `[{"nombre":"...", "cantidad":0}]` |
| `tiempo_estimado_minutos` | SMALLINT UNSIGNED | SÍ | — | NULL | Tiempo estimado de resolución |
| `id_reporte_origen` | BIGINT UNSIGNED | SÍ | FK → reporte | NULL | Reporte que originó esta plantilla |
| `veces_utilizada` | INT UNSIGNED | NN | — | 0 | Contador de reutilizaciones |
| `activa` | TINYINT(1) | NN | — | 1 | — |
| `fecha_creacion` | DATETIME | NN | — | NOW() | — |
| `fecha_actualizacion` | DATETIME | NN | — | NOW() | — |

---

## 5. Máquina de Estados del Reporte

### 5.1 Diagrama de transiciones

```
                          [Registrado]
                               │
                    Gestor/Rector asigna técnico
                               │
                          [Asignado] ──────────────── Gestor anula ──► [Anulado]
                               │
                  Técnico inicia intervención
                               │
                          [En proceso]
                               │
                  Técnico documenta y carga evidencias
                               │
                          [Solucionado]
                          ┌────┴─────────────────────────┐
               Gestor devuelve         Gestor pasa a revisión
                    │                           │
              [Devuelto]              [Requiere revisión]
                    │                      ┌────┴────┐
       Técnico reintenta         Gestor devuelve  Gestor/Rector cierra
                    │                 │                  │
                    └──► [En proceso] │            [Cerrado]
                                      └──► [Devuelto]
```

### 5.2 Tabla de transiciones válidas

| Estado origen | Estado destino | Actor | Condición |
|---|---|---|---|
| — (creación) | Registrado | Sistema | Al guardar el formulario |
| Registrado | Asignado | Gestor / Rector | Selección de técnico |
| Registrado | Anulado | Gestor | Justificación obligatoria |
| Asignado | En proceso | Técnico | Inicio de intervención |
| Asignado | Anulado | Gestor | Justificación obligatoria |
| En proceso | Solucionado | Técnico | Informe + evidencias (≥1 por etapa) |
| Solucionado | Requiere revisión | Sistema / Gestor | Paso automático para validación |
| Solucionado | Devuelto | Gestor | Trabajo insuficiente — SLA pausa |
| Requiere revisión | Cerrado | Gestor / Rector | Validación exitosa — dispara notificación |
| Requiere revisión | Devuelto | Gestor | Revisión fallida — SLA pausa |
| Devuelto | En proceso | Técnico | Reintento de atención — SLA reanuda |

### 5.3 Cálculo del SLA con pausas (RN-10)

```
SLA consumido = (NOW() - fecha_hora_registro) 
                - total_horas_pausadas
                - horas_no_laborales_transcurridas
```

- El SLA se **pausa** cuando el estado cambia a `Devuelto` (campo `fecha_pausa_sla = NOW()`).
- El SLA se **reanuda** cuando el estado vuelve a `En proceso` (se suma el tiempo pausado a `total_horas_pausadas`, `fecha_pausa_sla = NULL`).
- El cálculo solo cuenta horas laborales definidas en `configuracion_institucion`.

---

## 6. Índices y Estrategia de Rendimiento

Los índices siguientes están dimensionados para soportar ≥ 200 usuarios concurrentes con tiempo de respuesta < 2 s (RNF-07, RNF-08).

| Tabla | Índice | Columnas | Propósito |
|---|---|---|---|
| `usuario` | `uk_usuario_inst_correo` | `(id_institucion, correo_electronico)` | Login multitenant |
| `reporte` | `idx_rpt_inst_estado` | `(id_institucion, id_estado)` | Vista Kanban y lista del gestor |
| `reporte` | `idx_rpt_inst_tecnico` | `(id_institucion, id_tecnico_asignado, id_estado)` | "Mis asignaciones" del técnico |
| `reporte` | `idx_rpt_inst_fecha` | `(id_institucion, fecha_hora_registro DESC)` | Listado cronológico |
| `reporte` | `idx_rpt_inst_cat_urg` | `(id_institucion, id_categoria, id_urgencia_calculada)` | Filtros analíticos y KPIs |
| `transicion_estado` | `idx_ts_rpt_fecha` | `(id_reporte, fecha_hora_transicion DESC)` | Historial del reporte |
| `evidencia` | `idx_ev_rpt_etapa` | `(id_reporte, id_etapa)` | Validación de 3 etapas (RN-03) |
| `notificacion` | `idx_noti_cola` | `(id_institucion, estado_envio, fecha_programada)` | Cola de envío en batch |
| `registro_auditoria` | `idx_aud_inst_fecha` | `(id_institucion, fecha_hora_accion DESC)` | Consultas de auditoría |
| `registro_auditoria` | `idx_aud_entidad` | `(entidad, id_entidad)` | Rastrear cambios a un registro |
| `comentario_interno` | `idx_com_rpt_fecha` | `(id_reporte, fecha_creacion)` | Hilo cronológico por reporte |
| `sla` | `uk_sla_inst_cat_urg` | `(id_institucion, id_categoria, id_urgencia)` | Búsqueda de SLA aplicable |

---

## 7. Constraints y Reglas de Integridad

### 7.1 Constraints de base de datos

| Nombre | Tabla | Tipo | Columnas / Condición | RN asociada |
|---|---|---|---|---|
| `uk_rpt_ticket` | `reporte` | UNIQUE | `(id_institucion, numero_ticket)` | RN-07 |
| `uk_rpt_token` | `reporte` | UNIQUE | `token_seguimiento_publico` | RN-11 |
| `uk_inf_reporte` | `informe_intervencion` | UNIQUE | `id_reporte` | RF-17 (1:1) |
| `uk_enc_reporte` | `encuesta_satisfaccion` | UNIQUE | `id_reporte` | RF-22 (1:1) |
| `chk_enc_puntuacion` | `encuesta_satisfaccion` | CHECK | `puntuacion IS NULL OR (puntuacion BETWEEN 1 AND 5)` | RF-22 |
| `uk_config_inst` | `configuracion_institucion` | UNIQUE | `id_institucion` | RF-29 (1:1) |
| `uk_urgencia_nivel` | `urgencia` | UNIQUE | `nivel_numero` | — |

### 7.2 Triggers de integridad

| Trigger | Tabla | Evento | Protege |
|---|---|---|---|
| `trg_reporte_fecha_inmutable` | `reporte` | BEFORE UPDATE | Inmutabilidad de `fecha_hora_registro` (RN-12) |
| `trg_reporte_ticket_inmutable` | `reporte` | BEFORE UPDATE | Inmutabilidad de `numero_ticket` (RN-07) |
| `trg_reporte_token_inmutable` | `reporte` | BEFORE UPDATE | Inmutabilidad de `token_seguimiento_publico` (RN-11) |

### 7.3 Validaciones en la capa de aplicación (no implementables solo en BD)

| Validación | RN | Momento |
|---|---|---|
| Verificar ≥ 1 evidencia de cada etapa (Antes, Durante, Después) antes de cerrar | RN-03 | Al marcar "Solucionado" |
| Verificar que `id_usuario_tecnico` del informe coincide con `id_tecnico_asignado` del reporte | RN-05 | Al guardar informe |
| Escalado automático de urgencia calculada si la categoría es `es_critica_escalada = 1` | RN-06 | Al crear/clasificar reporte |
| Bloquear edición de campos de reporte Cerrado o Anulado; solo permitir reapertura con justificación | RN-08 | En todas las actualizaciones |
| Disparar INSERT en `notificacion` al transicionar a "Cerrado" | RN-09 | Post-update de estado |
| Pausar/reanudar SLA al transicionar a/desde "Devuelto" | RN-10 | Post-update de estado |

---

## 8. Resumen del Modelo

### 8.1 Conteo de objetos creados por schema.sql

| Tipo de objeto | Cantidad |
|---|---|
| Tablas | 24 (6 catálogos globales + 18 multitenant) |
| Índices únicos (UK) | 11 |
| Índices de rendimiento (IDX) | 12 |
| Claves foráneas (FK) | 38 |
| Triggers | 3 |
| Check constraints | 1 |

### 8.2 Conteo de registros iniciales (seeds.sql)

| Tabla | Registros |
|---|---|
| urgencia | 4 |
| estado | 8 |
| etapa_evidencia | 3 |
| rol | 6 |
| permiso | 23 |
| rol_permiso | ~55 |
| institucion (demo) | 1 |
| configuracion_institucion | 1 |
| sede | 2 |
| area | 6 |
| subarea | 16 |
| categoria | 6 |
| subcategoria | 28 |
| sla | 8 |
| usuario (demo) | 8 |
| usuario_rol | 8 |
| **Total** | **~183 registros** |

---

## 9. Guía de Instalación y Verificación

### 9.1 Requisitos previos
- MySQL 8.0 o superior
- WAMP, XAMPP o servidor MySQL equivalente
- Cliente: phpMyAdmin, MySQL Workbench o MySQL CLI

### 9.2 Pasos de instalación

**Opción A — phpMyAdmin (WAMP):**
1. Abrir phpMyAdmin (`http://localhost/phpmyadmin`)
2. Ir a la pestaña **SQL**
3. Ejecutar primero el contenido de `sql/schema.sql`
4. Verificar que aparezcan 24 tablas en la base `sirgdi`
5. Ejecutar luego `sql/seeds.sql`
6. Verificar los registros con: `SELECT COUNT(*) FROM rol;` (debe retornar 6)

**Opción B — MySQL CLI:**
```bash
mysql -u root -p < sql/schema.sql
mysql -u root -p sirgdi < sql/seeds.sql
```

### 9.3 Verificación de integridad post-instalación

```sql
USE sirgdi;

-- Verificar tablas creadas
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sirgdi'
ORDER BY TABLE_NAME;

-- Verificar catálogos
SELECT * FROM urgencia ORDER BY nivel_numero;
SELECT * FROM estado;
SELECT * FROM rol;
SELECT COUNT(*) AS total_permisos FROM permiso;   -- Debe ser 23
SELECT COUNT(*) AS asignaciones_rbac FROM rol_permiso; -- Debe ser ~55

-- Verificar institución demo
SELECT i.nombre, COUNT(u.id_usuario) AS usuarios
FROM institucion i
LEFT JOIN usuario u ON u.id_institucion = i.id_institucion
GROUP BY i.id_institucion;

-- Verificar triggers
SHOW TRIGGERS FROM sirgdi;  -- Debe mostrar 3 triggers
```

### 9.4 Regeneración de hashes en desarrollo

Los hashes de contraseña en seeds.sql son **placeholders** que no funcionan para login. Antes de probar la autenticación, actualizar con hashes reales:

```php
// PHP — generar hash válido
$hash = password_hash('Cambiar@2026!', PASSWORD_BCRYPT, ['cost' => 12]);
// Luego: UPDATE usuario SET hash_contrasena = '$hash' WHERE id_usuario = X;
```

---

## 10. Consideraciones de Seguridad del Modelo

| Aspecto | Implementación en BD |
|---|---|
| **Contraseñas** | Nunca almacenar en texto plano; solo `hash_contrasena` (bcrypt/Argon2, factor ≥ 12). RNF-01 |
| **Secreto TOTP** | Cifrar `totp_secret` en AES-256 antes de guardar (la BD solo ve el cifrado). RF-01 |
| **Token de seguimiento** | UUID v4 generado en la capa de aplicación; UNIQUE en BD. No expone id_reporte. RN-11 |
| **Token de reset** | Expira por tiempo (`token_reset_expira`); invalidar después del primer uso. RF-05 |
| **Archivos adjuntos** | URL no pública; acceso solo a través de la aplicación con verificación de permisos. RNF-04 |
| **Aislamiento** | `id_institucion` en cada tabla + filtro obligatorio en consultas. RN-01, RES-03 |
| **Auditoría** | `registro_auditoria` inmutable (sin UPDATE, solo INSERT); retener mínimo 30 días. RF-04 |

---

*Fin del Documento de Diseño de Base de Datos — SIRGDI v2.0 · DDB v1.0*
