# Especificación de Requerimientos de Software (ERS)
## SIRGDI — Sistema Integrado de Reporte y Gestión de Daños Institucionales

| Campo | Detalle |
|---|---|
| **Versión del documento** | 2.1 (revisada y depurada) |
| **Estado** | Aprobado para diseño |
| **Norma de referencia** | ISO/IEC/IEEE 29148:2018 (sustituye a IEEE 830) |
| **Clasificación** | Documento técnico oficial |
| **Ámbito** | Plataforma web multitenant para gestión de daños en infraestructura educativa |

### Control de versiones

| Versión | Fecha | Descripción del cambio | Responsable |
|---|---|---|---|
| 1.0 | — | Requerimiento inicial del cliente | Cliente |
| 2.0 | — | Profesionalización: multitenant, SLA, analítica, evidencias por etapa | Equipo de análisis |
| **2.1** | 2026-06-15 | **Depuración:** corrección de inconsistencias, reposición de campos faltantes, matriz de roles RACI, reglas de negocio, casos de uso, trazabilidad y criterios de aceptación | Equipo de análisis |

### Resumen de correcciones aplicadas en v2.1

| # | Hallazgo en v2.0 | Corrección en v2.1 |
|---|---|---|
| 1 | El formulario de reporte (RF-06) quedó **sin campo de ubicación** tras eliminar el mapa georreferenciado | Se repone un **selector de ubicación estructurada** (sede → área → sub-área) configurable por institución, sin mapa interactivo |
| 2 | La tabla comparativa prometía un **módulo de inventario** que ya no existía en los RF | Inventario se reclasifica explícitamente como **alcance futuro (Fase 2)**; RF-17 solo registra materiales como texto/lista, sin gestión de stock |
| 3 | Faltaba la **fecha y hora del reporte** (presente en el requerimiento original del cliente) | Se formaliza como dato generado por el sistema (RF-06, RN-12) |
| 4 | **Solapamiento de roles:** el Rector figuraba como reportante y como asignador, traslapándose con el Gestor | Se define una **matriz de permisos y RACI** (sección 3) que resuelve la jerarquía |
| 5 | Estados del ciclo de vida sin reglas formales de transición | Se formaliza la **máquina de estados** (sección 6) con disparadores y actores |
| 6 | Requisitos sin prioridad ni criterio de aceptación | Se añade **prioridad MoSCoW** y criterios de aceptación |

---

## 1. Introducción

### 1.1 Propósito del documento
Este documento especifica de forma completa, consistente y verificable los requerimientos funcionales y no funcionales del sistema **SIRGDI**, destinado a la comunidad educativa para el registro, gestión, atención, documentación y cierre de reportes de daños en la infraestructura y bienes de las instituciones. Sirve como referencia contractual entre las partes interesadas y como base para el diseño, desarrollo, pruebas y aceptación del sistema.

### 1.2 Alcance del producto

**Incluido en el alcance (v2.0):**
- Gestión completa del ciclo de vida del reporte de daño (registro → cierre).
- Modelo de acceso multitenant con aislamiento por institución educativa.
- Roles diferenciados, control de acceso y auditoría.
- Priorización automática, SLA y escalamiento.
- Evidencia fotográfica clasificada por etapa (antes / durante / después).
- Notificaciones automáticas multiactor por correo electrónico.
- Tablero analítico (KPIs) y exportación de informes.
- Administración configurable por institución.

**Fuera del alcance de la v2.0 (ver Roadmap, sección 2.6):**
- Módulo de gestión de inventario y stock de materiales.
- Aplicación móvil nativa (la plataforma será web responsiva).
- Integración con sistemas contables/presupuestales externos.
- Mapa georreferenciado interactivo del campus.
- Firma electrónica con validez legal (PKI); se contempla firma simple de confirmación.

### 1.3 Definiciones, acrónimos y abreviaturas

| Término | Definición |
|---|---|
| **SIRGDI** | Sistema Integrado de Reporte y Gestión de Daños Institucionales |
| **Multitenant** | Arquitectura en la que una única instancia del sistema sirve a múltiples instituciones, manteniendo sus datos completamente aislados |
| **Institución (tenant)** | Entidad educativa que opera dentro del sistema con su propio espacio de datos |
| **Reporte / Ticket** | Registro de un daño con identificador único y ciclo de vida propio |
| **SLA** | *Service Level Agreement* — tiempo máximo comprometido para atender/resolver un reporte |
| **Escalamiento** | Aumento automático de prioridad por categoría crítica o por vencimiento de tiempos |
| **RACI** | Matriz de responsabilidades: Responsable, Aprobador, Consultado, Informado |
| **KPI** | *Key Performance Indicator* — indicador clave de desempeño |
| **2FA / TOTP** | Doble factor de autenticación / contraseña temporal basada en tiempo |
| **RF / RNF / RN / CU / OBJ** | Requisito Funcional / No Funcional / Regla de Negocio / Caso de Uso / Objetivo |

### 1.4 Referencias
- ISO/IEC/IEEE 29148:2018 — Ingeniería de requisitos.
- ISO/IEC 25010 — Modelo de calidad del producto software.
- WCAG 2.1 — Pautas de accesibilidad para el contenido web.
- OWASP ASVS / Top 10 — Estándar de verificación de seguridad de aplicaciones.
- Normativa de protección de datos personales aplicable (p. ej. Ley 1581 de 2012 — Habeas Data, Colombia).

### 1.5 Convenciones
Los requisitos se identifican unívocamente: **RF-xx** (funcional), **RNF-xx** (no funcional), **RN-xx** (regla de negocio), **CU-xx** (caso de uso), **OBJ-xx** (objetivo). La prioridad se expresa con el método **MoSCoW**: *Esencial* (Must), *Importante* (Should), *Deseable* (Could), *Futuro* (Won't-now).

---

## 2. Descripción General

### 2.1 Perspectiva y contexto
SIRGDI es un producto nuevo, autónomo, de tipo web. Reemplaza procesos manuales (correos, llamadas, formatos en papel) por un flujo digital trazable. Opera sobre navegador, con backend centralizado y base de datos compartida lógicamente aislada por institución.

### 2.2 Objetivos del sistema

| ID | Objetivo |
|---|---|
| **OBJ-01** | Centralizar el registro de daños con trazabilidad completa de extremo a extremo |
| **OBJ-02** | Reducir los tiempos de respuesta y resolución mediante priorización automática y SLA |
| **OBJ-03** | Garantizar evidencia documental verificable del estado antes, durante y después |
| **OBJ-04** | Asegurar comunicación oportuna y automática entre todos los actores |
| **OBJ-05** | Garantizar la confidencialidad y el aislamiento de la información por institución |
| **OBJ-06** | Proveer información consolidada (analítica) para la toma de decisiones de la rectoría |

### 2.3 Actores y características de los usuarios

| Rol | Descripción | Permisos clave |
|---|---|---|
| **Reportante** | Docente, administrativo, coordinador o rector que registra un daño | Crear y dar seguimiento a sus propios reportes |
| **Técnico** | Personal de mantenimiento que ejecuta y documenta las reparaciones | Atender reportes asignados, registrar informe y evidencias |
| **Gestor** | Coordinador de mantenimiento; recibe, clasifica, prioriza y asigna | Gestión operativa completa del ciclo del reporte |
| **Rector** | Máxima autoridad; supervisa, aprueba el cierre y recibe notificaciones. Puede asumir funciones de Gestor en instituciones pequeñas | Supervisión, validación de cierre, analítica |
| **Administrador de Institución** | Configura la plataforma para su institución | Gestión de usuarios, áreas, categorías, plantillas y SLA |
| **Superadministrador** | Operador de la plataforma multitenant | Alta/baja de instituciones, configuración global |

> **Resolución del solapamiento Rector/Gestor:** por defecto, el **Gestor** es el responsable operativo de clasificar y asignar. El **Rector** posee un superconjunto de permisos (puede asignar y cerrar) en su rol supervisor, pero la operación diaria recae en el Gestor. En instituciones sin un Gestor designado, el Rector asume ese rol (ver **RN-13**).

### 2.4 Restricciones de diseño
- **RES-01** — Aplicación web responsiva; sin app móvil nativa en v2.0.
- **RES-02** — Comunicaciones obligatoriamente sobre HTTPS (TLS 1.2+).
- **RES-03** — El aislamiento multitenant debe garantizarse a nivel de datos en cada consulta.
- **RES-04** — Idioma de la interfaz: español; arquitectura preparada para internacionalización futura.
- **RES-05** — Cumplimiento de la normativa de protección de datos personales aplicable.

### 2.5 Suposiciones y dependencias
- **SUP-01** — Cada institución designa al menos un Administrador de Institución y un Gestor (o un Rector que asuma ambos).
- **SUP-02** — Existe un servidor de correo (SMTP) disponible para el envío de notificaciones.
- **SUP-03** — Los usuarios disponen de un navegador moderno y conexión a Internet.
- **SUP-04** — Las instituciones proporcionan su catálogo de sedes, áreas y categorías al implantar.

### 2.6 Alcance por fases (Roadmap)

| Fase | Capacidad | Estado |
|---|---|---|
| **Fase 1 (v2.0)** | Ciclo completo de reportes, multitenant, SLA, evidencias, notificaciones, analítica | **Esencial** |
| **Fase 2** | Módulo de inventario y control de stock de materiales con alerta de mínimos | Futuro |
| **Fase 2** | Aplicación móvil nativa (PWA u offline) | Futuro |
| **Fase 3** | Integración presupuestal/contable y mapa georreferenciado del campus | Deseable |

---

## 3. Modelo de Roles y Permisos

### 3.1 Matriz de permisos (RBAC)

> Leyenda: ✔ permitido · ◐ limitado a registros propios · — no permitido

| Acción / Rol | Reportante | Técnico | Gestor | Rector | Admin Inst. | Superadmin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Crear reporte | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| Ver reportes propios | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| Ver todos los reportes (de su institución) | — | ◐ asignados | ✔ | ✔ | ✔ | ✔ (todas) |
| Clasificar y priorizar | — | — | ✔ | ✔ | — | — |
| Asignar técnico | — | — | ✔ | ✔ | — | — |
| Registrar informe de atención | — | ✔ | — | — | — | — |
| Cargar evidencia (antes/durante/después) | — | ✔ | — | — | — | — |
| Marcar "Solucionado" | — | ✔ | — | — | — | — |
| Validar y "Cerrar" | — | — | ✔ | ✔ | — | — |
| Devolver / reabrir reporte | — | — | ✔ | ✔ | — | — |
| Comentarios internos | — | ✔ | ✔ | ✔ | ✔ | — |
| Ver tablero analítico (KPIs) | — | ◐ propios | ✔ | ✔ | ✔ | ✔ |
| Exportar informes (PDF/Excel) | — | — | ✔ | ✔ | ✔ | ✔ |
| Configurar institución (áreas, categorías, SLA, plantillas) | — | — | — | ◐ lectura | ✔ | ✔ |
| Gestionar usuarios y roles | — | — | — | — | ✔ | ✔ |
| Consultar registro de auditoría | — | — | — | ◐ lectura | ✔ | ✔ |
| Gestionar instituciones (alta/baja tenant) | — | — | — | — | — | ✔ |

### 3.2 Matriz RACI del proceso principal

> R = Responsable de ejecutar · A = Aprueba / rinde cuentas · C = Consultado · I = Informado

| Actividad | Reportante | Técnico | Gestor | Rector | Sistema |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar reporte | R | | A | I | I |
| Clasificar y priorizar | I | C | R/A | I | |
| Asignar técnico | I | I | R | A | |
| Atender y documentar | | R | A | I | |
| Validar y cerrar | I | C | R | A | |
| Notificar a los actores | | | A | | R |

---

## 4. Requerimientos Funcionales

### 4.1 Módulo de Autenticación y Control de Acceso

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-01** | Autenticación segura con usuario/contraseña y opción de doble factor (2FA por correo o aplicación TOTP) | Esencial |
| **RF-02** | Modelo multitenant: cada institución tiene un espacio de datos aislado; ningún usuario puede ver información de otra institución | Esencial |
| **RF-03** | Gestión de roles con permisos granulares por módulo (crear, leer, actualizar, cerrar, administrar) | Esencial |
| **RF-04** | Registro de auditoría de acciones críticas: usuario, acción, fecha/hora e IP de origen | Esencial |
| **RF-05** | Restablecimiento seguro de contraseña mediante enlace temporal con expiración configurable | Esencial |

### 4.2 Módulo de Registro de Reportes

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-06** | Formulario de reporte con campos estructurados (ver detalle abajo) | Esencial |
| **RF-07** | Generación automática de número de ticket único con formato configurable por institución (ej.: `IE-NOMBRE-2026-00123`) | Esencial |
| **RF-08** | Confirmación inmediata al reportante por correo con el número de ticket y enlace de seguimiento público (sin autenticación) | Esencial |
| **RF-09** | Opción de reporte anónimo controlado: el gestor ve los datos, pero no se difunden internamente | Deseable |

**Detalle del formulario (RF-06):**
- **Datos del reportante:** nombre completo, cargo/rol, correo, teléfono — autocompletados si el usuario está autenticado.
- **Ubicación del daño (repuesto):** selector estructurado **Sede → Área → Sub-área** (p. ej. *Bloque A → Aula 201*, *Bloque B → Baño hombres*), con el catálogo configurado por cada institución y un campo de referencia libre opcional.
- **Categoría:** jerarquía de dos niveles — categoría principal (infraestructura, mobiliario, eléctrico, sanitario, tecnológico, seguridad) y subcategoría específica.
- **Nivel de urgencia declarado:** No urgente / Moderado / Importante / Urgente.
- **Descripción** libre del problema.
- **Fecha y hora del reporte (repuesto):** generada automáticamente por el sistema, no editable.
- **Adjuntos:** hasta 5 imágenes (con compresión automática) o un video corto (máx. 30 s).

### 4.3 Módulo de Gestión y Priorización

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-10** | Panel del gestor con vista Kanban (columnas por estado) y vista lista con filtros: categoría, urgencia, técnico, rango de fechas, estado de SLA | Esencial |
| **RF-11** | Motor de priorización automática por urgencia declarada, categoría (eléctrico/seguridad escalan) y tiempo sin atención (escalado progresivo) | Importante |
| **RF-12** | Asignación de reportes a técnicos mostrando la carga de trabajo actual de cada uno | Importante |
| **RF-13** | Definición de SLA por categoría y urgencia; alerta visual al acercarse o superar el tiempo límite | Importante |
| **RF-14** | Fusión de reportes duplicados y vinculación de reportes relacionados | Deseable |
| **RF-15** | Comentarios internos: hilo privado entre gestor, técnico y rector, con historial cronológico | Importante |

### 4.4 Módulo de Atención Técnica

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-16** | Vista "Mis asignaciones" para el técnico, ordenada por prioridad | Esencial |
| **RF-17** | Registro del informe de intervención (ver detalle abajo) | Esencial |
| **RF-18** | Carga de evidencia fotográfica clasificada por etapa: **Antes**, **Durante**, **Después** (mín. una por etapa al cerrar) | Esencial |
| **RF-19** | Firma de confirmación del técnico al completar el informe (firma en pantalla o código por correo) | Deseable |
| **RF-20** | Base de conocimiento: marcar un cierre como "solución reutilizable" para alimentar un repositorio de soluciones | Deseable |

**Detalle del informe de intervención (RF-17):** descripción de actividades; causa raíz identificada; solución implementada; materiales y recursos utilizados (cantidad y unidad de medida, **como lista/texto, sin control de stock en v2.0**); costo estimado (opcional, control presupuestal); fecha/hora de inicio y de finalización.

### 4.5 Módulo de Cierre y Notificaciones

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-21** | Cierre en dos pasos: el técnico marca "Solucionado" → el gestor/rector valida y "Cierra" formalmente | Esencial |
| **RF-22** | Encuesta de satisfacción opcional al reportante (1–5 estrellas + comentario) enviada al cierre | Deseable |
| **RF-23** | Notificaciones automáticas por correo a reportante, técnico, rector y gestor según el evento (ver matriz, sección 7) | Esencial |
| **RF-24** | El correo de cierre al rector incluye: datos del daño, técnico, descripción de la solución, costo estimado, galería de evidencias y enlace al reporte | Esencial |

### 4.6 Módulo de Reportes y Analítica

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-25** | Dashboard ejecutivo con KPIs en tiempo real: reportes por estado, tiempo promedio de resolución por categoría, % de cumplimiento de SLA, mapa de calor de áreas con más daños, técnico con mayor volumen | Importante |
| **RF-26** | Exportación de informes en PDF y Excel con filtros por rango de fechas, categoría, técnico o área | Importante |
| **RF-27** | Informe mensual/trimestral automático enviado al rector con estadísticas consolidadas | Deseable |
| **RF-28** | Historial completo del ciclo de vida de cada reporte, exportable como PDF con todas las evidencias | Importante |

### 4.7 Módulo de Administración por Institución

| ID | Requisito | Prioridad |
|---|---|---|
| **RF-29** | Panel de configuración por institución: identidad (nombre, logo, datos); sedes; catálogo de áreas (bloques, aulas, baños, oficinas); categorías y subcategorías; plantillas de correo editables; SLA por tipo de daño; usuarios y roles | Esencial |

---

## 5. Reglas de Negocio

| ID | Regla |
|---|---|
| **RN-01** | Toda consulta de datos filtra obligatoriamente por la institución del usuario autenticado (aislamiento multitenant) |
| **RN-02** | Un reporte no puede crearse sin ubicación, categoría y descripción |
| **RN-03** | Un reporte no puede cerrarse sin al menos una evidencia de cada etapa (antes, durante, después) |
| **RN-04** | El cierre es en dos pasos: Técnico → "Solucionado"; Gestor/Rector → "Cerrado" |
| **RN-05** | Solo el técnico asignado puede registrar el informe de intervención del reporte |
| **RN-06** | Los reportes de categoría **eléctrica** o **seguridad** se escalan automáticamente a prioridad alta |
| **RN-07** | El número de ticket es único e inmutable dentro de cada institución |
| **RN-08** | Un reporte cerrado no puede editarse; solo puede reabrirse por Gestor/Rector con justificación registrada |
| **RN-09** | La notificación de cierre se dispara automáticamente al transicionar a estado "Cerrado" |
| **RN-10** | El SLA se calcula desde la fecha/hora de registro; el cronómetro se pausa solo en estado "Devuelto/Requiere revisión" |
| **RN-11** | El reportante puede consultar el estado por enlace público sin login, sin exponer datos de otros reportes |
| **RN-12** | La fecha y hora de registro las genera el sistema; el usuario no puede modificarlas |
| **RN-13** | En instituciones sin Gestor designado, el Rector asume las funciones de gestión y asignación |

---

## 6. Estados del Ciclo de Vida de un Reporte

```
                 ┌─────────────┐
                 │  Registrado │
                 └──────┬──────┘
                        ▼
                 ┌─────────────┐      ┌──────────┐
                 │  Asignado   │─────▶│ Anulado  │ (duplicado/no válido)
                 └──────┬──────┘      └──────────┘
                        ▼
                 ┌─────────────┐
        ┌───────▶│ En proceso  │
        │        └──────┬──────┘
        │               ▼
   ┌────┴─────┐  ┌─────────────┐
   │ Devuelto │◀─│ Solucionado │
   └──────────┘  └──────┬──────┘
                        ▼
                 ┌─────────────────┐   validación
                 │ Requiere revisión│──────────────┐
                 └─────────────────┘               ▼
                                            ┌─────────────┐
                                            │   Cerrado   │
                                            └─────────────┘
```

| Estado | Descripción | Disparador / Actor |
|---|---|---|
| **Registrado** | Reporte recién creado, sin asignar | Reportante crea el reporte |
| **Asignado** | Clasificado, priorizado y asignado a un técnico | Gestor/Rector asigna |
| **En proceso** | El técnico inició la intervención | Técnico inicia atención |
| **Solucionado** | El técnico documentó la solución y cargó evidencias | Técnico marca "Solucionado" |
| **Requiere revisión** | El gestor verifica el trabajo antes de cerrar | Gestor revisa |
| **Devuelto** | Trabajo insuficiente o falta información; vuelve al técnico/reportante | Gestor/Técnico devuelve |
| **Cerrado** | Validado formalmente; dispara notificación de cierre | Gestor/Rector cierra |
| **Anulado** | Reporte inválido, duplicado o improcedente | Gestor anula con justificación |

> Cada transición se registra con usuario, *timestamp* y comentario opcional (trazabilidad — OBJ-01).

---

## 7. Matriz de Notificaciones

| Evento | Reportante | Técnico | Gestor | Rector | Canal |
|---|:---:|:---:|:---:|:---:|---|
| Reporte registrado (acuse de recibo) | ✔ | | ✔ | | Correo |
| Reporte asignado | ✔ | ✔ | | | Correo |
| Cambio de estado (en proceso/solucionado) | ✔ | | | | Correo |
| SLA próximo a vencer | | ✔ | ✔ | | Correo |
| SLA vencido / reporte crítico sin atender | | | ✔ | ✔ | Correo |
| Reporte cerrado (resumen + evidencias) | ✔ | | | ✔ | Correo |
| Encuesta de satisfacción | ✔ | | | | Correo |
| Informe periódico consolidado | | | | ✔ | Correo |

---

## 8. Acuerdos de Nivel de Servicio (SLA) — valores de referencia

> Configurables por institución y categoría (RF-13). Tiempos en horas hábiles.

| Urgencia | Tiempo de respuesta (asignación) | Tiempo de resolución |
|---|---|---|
| **Urgente** | ≤ 2 h | ≤ 8 h |
| **Importante** | ≤ 8 h | ≤ 24 h |
| **Moderado** | ≤ 24 h | ≤ 72 h |
| **No urgente** | ≤ 48 h | ≤ 5 días hábiles |

---

## 9. Casos de Uso Principales

| ID | Caso de uso | Actor principal |
|---|---|---|
| **CU-01** | Registrar reporte de daño | Reportante |
| **CU-02** | Clasificar y asignar reporte | Gestor / Rector |
| **CU-03** | Atender y documentar la reparación | Técnico |
| **CU-04** | Validar y cerrar reporte | Gestor / Rector |
| **CU-05** | Consultar seguimiento por enlace público | Reportante |
| **CU-06** | Configurar institución | Admin. de Institución |
| **CU-07** | Consultar analítica y exportar informes | Rector / Gestor |

### CU-01 — Registrar reporte de daño (detallado)
- **Precondición:** el usuario está autenticado o accede al formulario público habilitado por su institución.
- **Flujo principal:** 1) selecciona ubicación (sede/área); 2) elige categoría y subcategoría; 3) indica urgencia; 4) describe el daño; 5) adjunta evidencia; 6) envía. El sistema genera el número de ticket, registra fecha/hora y envía acuse al reportante.
- **Flujos alternativos:** A1) datos obligatorios faltantes → el sistema bloquea el envío y señala los campos (RN-02). A2) adjunto inválido (tipo/tamaño) → se rechaza con mensaje (RNF-04).
- **Postcondición:** reporte en estado **Registrado**, notificado al gestor.

### CU-04 — Validar y cerrar reporte (detallado)
- **Precondición:** reporte en estado **Solucionado** con evidencias de las tres etapas.
- **Flujo principal:** 1) el gestor/rector revisa el informe y evidencias; 2) valida; 3) marca **Cerrado**. El sistema dispara la notificación de cierre al reportante y al rector con el resumen (RF-24) y envía la encuesta de satisfacción.
- **Flujo alternativo:** A1) trabajo insuficiente → estado **Devuelto** con comentario; el cronómetro de SLA se pausa (RN-10).
- **Postcondición:** reporte **Cerrado** y archivado; trazabilidad completa disponible para exportación.

---

## 10. Requerimientos No Funcionales

### 10.1 Seguridad
| ID | Requisito |
|---|---|
| **RNF-01** | Contraseñas almacenadas con hash seguro (bcrypt/Argon2, factor de costo ≥ 12) |
| **RNF-02** | Toda comunicación sobre HTTPS (TLS 1.2+) |
| **RNF-03** | Protección contra inyección SQL, XSS y CSRF en todos los formularios |
| **RNF-04** | Validación de adjuntos por tipo MIME y tamaño antes de almacenar |
| **RNF-05** | Expiración de sesión por inactividad configurable (por defecto: 30 minutos) |
| **RNF-06** | Cumplimiento de la normativa de protección de datos personales; consentimiento y finalidad del tratamiento de datos del reportante |

### 10.2 Rendimiento y Disponibilidad
| ID | Requisito |
|---|---|
| **RNF-07** | Soportar ≥ 200 usuarios concurrentes por institución sin degradación perceptible |
| **RNF-08** | Tiempo de respuesta de páginas principales < 2 s bajo carga normal |
| **RNF-09** | Disponibilidad mensual ≥ 99.5% (excluye mantenimientos programados notificados) |

### 10.3 Usabilidad y Accesibilidad
| ID | Requisito |
|---|---|
| **RNF-10** | Interfaz responsiva (escritorio, tablet y móvil) |
| **RNF-11** | El formulario de reporte se completa en < 3 minutos sin capacitación previa |
| **RNF-12** | Cumplimiento de accesibilidad WCAG 2.1 nivel AA |

### 10.4 Mantenibilidad y Escalabilidad
| ID | Requisito |
|---|---|
| **RNF-13** | Arquitectura modular que permita incorporar nuevas instituciones sin modificar el código base |
| **RNF-14** | Respaldo automático diario de la base de datos con retención ≥ 30 días |
| **RNF-15** | Logs con niveles configurables (debug, info, warning, error) |

---

## 11. Interfaces Externas
- **Servidor de correo (SMTP):** envío de acuses, notificaciones y resúmenes de cierre.
- **Almacenamiento de archivos:** repositorio para imágenes/video de evidencia (sistema de archivos u object storage), con rutas no públicas y acceso controlado.
- **Proveedor TOTP (opcional):** aplicaciones de autenticación para 2FA (RF-01).
- **Navegadores soportados:** últimas dos versiones de Chrome, Edge, Firefox y Safari.

---

## 12. Matriz de Trazabilidad (Objetivos → Requisitos)

| Objetivo | Requisitos funcionales asociados | Reglas/casos |
|---|---|---|
| **OBJ-01** Trazabilidad | RF-04, RF-06, RF-07, RF-15, RF-28 | RN-12, sección 6 |
| **OBJ-02** Tiempos de respuesta | RF-10, RF-11, RF-12, RF-13 | RN-06, RN-10, sección 8 |
| **OBJ-03** Evidencia documental | RF-18, RF-24, RF-28 | RN-03 |
| **OBJ-04** Comunicación | RF-08, RF-22, RF-23, RF-24, RF-27 | RN-09, sección 7 |
| **OBJ-05** Confidencialidad/aislamiento | RF-02, RF-03, RF-04, RF-05 | RN-01, RES-03 |
| **OBJ-06** Analítica para decisiones | RF-25, RF-26, RF-27, RF-28 | — |

---

## 13. Criterios de Aceptación del Sistema (Definition of Done)
1. Un usuario de la Institución A **no puede**, por ningún medio, acceder a datos de la Institución B (verificación de RN-01).
2. Ningún reporte alcanza el estado **Cerrado** sin las evidencias de las tres etapas (RN-03).
3. Toda transición de estado queda registrada en auditoría con usuario, fecha/hora e IP (RF-04).
4. Las notificaciones de la sección 7 se envían automáticamente ante cada evento correspondiente.
5. Los reportes eléctricos y de seguridad se escalan automáticamente a prioridad alta (RN-06).
6. El dashboard refleja los KPIs de la sección RF-25 con datos consistentes con la base de datos.
7. El sistema cumple los umbrales de rendimiento (RNF-07, RNF-08) en pruebas de carga.
8. La aplicación supera una revisión de seguridad basada en OWASP Top 10 (RNF-03).

---

## 14. Anexos

### Anexo A — Catálogo de categorías (configurable por institución)
| Categoría principal | Subcategorías de ejemplo |
|---|---|
| Infraestructura | Techos, muros, pisos, puertas, ventanas |
| Mobiliario | Sillas, mesas, tableros, estantes |
| Eléctrico | Tomas, luminarias, tableros, cableado |
| Sanitario | Inodoros, lavamanos, tuberías, fugas |
| Tecnológico | Computadores, proyectores, red, audio |
| Seguridad | Cercas, extintores, cámaras, salidas de emergencia |

### Anexo B — Estructura de ubicación (configurable por institución)
`Institución → Sede → Bloque/Zona → Área (aula, baño, oficina, laboratorio, patio) → Referencia opcional`

---

*Fin del documento ERS-SIRGDI v2.1.*
