-- ============================================================
-- SIRGDI v2.0 — Script DDL (Data Definition Language)
-- Motor: MySQL 8.0+  |  Engine: InnoDB  |  Charset: utf8mb4
-- Norma de referencia: ERS-SIRGDI-v2.1.md
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Crear y seleccionar la base de datos
-- (CREATE DATABASE omitido para Hostinger: ya seleccionas u397951547_bdmtt)
-- (USE omitido para Hostinger)
-- ============================================================
-- BLOQUE 1: CATÁLOGOS GLOBALES (sin id_institucion)
-- Son tablas de referencia compartidas por todas las instituciones.
-- ============================================================

-- 1.1 Niveles de urgencia
CREATE TABLE IF NOT EXISTS urgencia (
    id_urgencia     TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(30)         NOT NULL,
    nivel_numero    TINYINT UNSIGNED    NOT NULL COMMENT '0=No urgente … 3=Urgente',
    color_hex       CHAR(7)             NOT NULL DEFAULT '#CCCCCC',
    PRIMARY KEY (id_urgencia),
    UNIQUE KEY uk_urgencia_nivel (nivel_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo global de niveles de urgencia';

-- 1.2 Estados del ciclo de vida del reporte
CREATE TABLE IF NOT EXISTS estado (
    id_estado       TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(30)         NOT NULL,
    descripcion     VARCHAR(255)        NULL,
    es_terminal     TINYINT(1)          NOT NULL DEFAULT 0 COMMENT '1=estado final, no transita',
    PRIMARY KEY (id_estado),
    UNIQUE KEY uk_estado_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo global de estados del reporte (Registrado→Cerrado/Anulado)';

-- 1.3 Etapas de evidencia fotográfica
CREATE TABLE IF NOT EXISTS etapa_evidencia (
    id_etapa    TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(20)         NOT NULL,
    orden       TINYINT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_etapa),
    UNIQUE KEY uk_etapa_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo: Antes (1), Durante (2), Después (3). RN-03';

-- 1.4 Roles del sistema
CREATE TABLE IF NOT EXISTS rol (
    id_rol          TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre_rol      VARCHAR(50)         NOT NULL,
    descripcion     VARCHAR(255)        NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uk_rol_nombre (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo global de roles (6 roles predefinidos)';

-- 1.5 Permisos granulares por módulo
CREATE TABLE IF NOT EXISTS permiso (
    id_permiso      SMALLINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    codigo          VARCHAR(60)         NOT NULL,
    descripcion     VARCHAR(255)        NULL,
    modulo          VARCHAR(40)         NOT NULL,
    PRIMARY KEY (id_permiso),
    UNIQUE KEY uk_permiso_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Permisos granulares (crear_reporte, asignar_tecnico, etc.)';

-- 1.6 Asignación de permisos a roles (M:M global)
CREATE TABLE IF NOT EXISTS rol_permiso (
    id_rol          TINYINT UNSIGNED    NOT NULL,
    id_permiso      SMALLINT UNSIGNED   NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rp_rol    FOREIGN KEY (id_rol)     REFERENCES rol(id_rol)     ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm   FOREIGN KEY (id_permiso) REFERENCES permiso(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla intermedia rol↔permiso (RBAC global)';

-- ============================================================
-- BLOQUE 2: TENANT RAÍZ
-- ============================================================

-- 2.1 Institución (tenant)
CREATE TABLE IF NOT EXISTS institucion (
    id_institucion          BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nombre                  VARCHAR(150)        NOT NULL,
    logo_ruta               VARCHAR(500)        NULL,
    es_activa               TINYINT(1)          NOT NULL DEFAULT 1,
    fecha_creacion          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                         ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_institucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla raíz multitenant: cada fila es un tenant (institución educativa)';

-- 2.2 Configuración por institución (1:1)
CREATE TABLE IF NOT EXISTS configuracion_institucion (
    id_configuracion                    BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion                      BIGINT UNSIGNED     NOT NULL,
    formato_numero_ticket               VARCHAR(60)         NOT NULL DEFAULT 'IE-{INST}-{YYYY}-{SEQ5}',
    correo_remitente                    VARCHAR(150)        NULL,
    es_reporte_anonimo_permitido        TINYINT(1)          NOT NULL DEFAULT 0,
    tiempo_sesion_minutos               SMALLINT UNSIGNED   NOT NULL DEFAULT 30,
    horas_laborales_inicio              TINYINT UNSIGNED    NOT NULL DEFAULT 7,
    horas_laborales_fin                 TINYINT UNSIGNED    NOT NULL DEFAULT 17,
    dias_no_laborales_json              JSON                NULL     COMMENT '[6,7] = sáb, dom',
    -- Plantillas de correo electrónico (variables: {TICKET}, {NOMBRE}, {DESCRIPCION}, etc.)
    plantilla_asunto_registro           VARCHAR(200)        NULL,
    plantilla_asunto_asignado           VARCHAR(200)        NULL,
    plantilla_asunto_cerrado            VARCHAR(200)        NULL,
    plantilla_cuerpo_registro           MEDIUMTEXT          NULL,
    plantilla_cuerpo_asignado           MEDIUMTEXT          NULL,
    plantilla_cuerpo_cerrado            MEDIUMTEXT          NULL,
    fecha_creacion                      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion                 DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                                     ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_configuracion),
    UNIQUE KEY uk_config_inst (id_institucion),
    CONSTRAINT fk_cfg_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion)
                           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Parámetros operativos y plantillas de correo por institución. RF-29';

-- ============================================================
-- BLOQUE 3: USUARIOS Y ROLES
-- ============================================================

-- 3.1 Usuarios
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario              BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    nombre_completo         VARCHAR(150)        NOT NULL,
    correo_electronico      VARCHAR(150)        NOT NULL,
    telefono                VARCHAR(20)         NULL,
    cargo_descripcion       VARCHAR(100)        NULL,
    hash_contrasena         VARCHAR(255)        NOT NULL COMMENT 'bcrypt/Argon2, factor≥12. RNF-01',
    activo                  TINYINT(1)          NOT NULL DEFAULT 1,
    requiere_2fa            TINYINT(1)          NOT NULL DEFAULT 0,
    totp_secret             VARCHAR(100)        NULL     COMMENT 'Secreto TOTP cifrado en AES. RF-01',
    token_reset_pass        VARCHAR(100)        NULL,
    token_reset_expira      DATETIME            NULL,
    ultima_actividad        DATETIME            NULL     COMMENT 'Para expiración de sesión. RNF-05',
    fecha_creacion          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                         ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uk_usuario_inst_correo (id_institucion, correo_electronico),
    CONSTRAINT fk_usr_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuarios del sistema. Aislamiento garantizado por id_institucion. RN-01';

-- 3.2 Asignación de roles a usuarios (M:M por institución)
CREATE TABLE IF NOT EXISTS usuario_rol (
    id_usuario_rol      BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_usuario          BIGINT UNSIGNED     NOT NULL,
    id_rol              TINYINT UNSIGNED    NOT NULL,
    id_institucion      BIGINT UNSIGNED     NOT NULL COMMENT 'Redundante: permite FK y auditoría',
    fecha_asignacion    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario_rol),
    UNIQUE KEY uk_usr_rol (id_usuario, id_rol),
    CONSTRAINT fk_ur_usuario FOREIGN KEY (id_usuario)     REFERENCES usuario(id_usuario)     ON DELETE CASCADE,
    CONSTRAINT fk_ur_rol     FOREIGN KEY (id_rol)         REFERENCES rol(id_rol),
    CONSTRAINT fk_ur_inst    FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla intermedia usuario↔rol con contexto de institución';

-- ============================================================
-- BLOQUE 4: ESTRUCTURA DE UBICACIÓN (Sede → Área → Sub-área)
-- ============================================================

-- 4.1 Sede
CREATE TABLE IF NOT EXISTS sede (
    id_sede             BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion      BIGINT UNSIGNED     NOT NULL,
    nombre              VARCHAR(100)        NOT NULL,
    direccion           VARCHAR(255)        NULL,
    activa              TINYINT(1)          NOT NULL DEFAULT 1,
    fecha_creacion      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_sede),
    CONSTRAINT fk_sede_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    INDEX idx_sede_inst (id_institucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nivel 1 de ubicación configurable por institución. RF-29';

-- 4.2 Área (Bloques, Zonas)
CREATE TABLE IF NOT EXISTS area (
    id_area             BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion      BIGINT UNSIGNED     NOT NULL,
    id_sede             BIGINT UNSIGNED     NOT NULL,
    nombre              VARCHAR(100)        NOT NULL,
    descripcion         VARCHAR(255)        NULL,
    activa              TINYINT(1)          NOT NULL DEFAULT 1,
    orden               SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    fecha_creacion      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_area),
    CONSTRAINT fk_area_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    CONSTRAINT fk_area_sede FOREIGN KEY (id_sede)        REFERENCES sede(id_sede),
    INDEX idx_area_sede (id_institucion, id_sede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nivel 2 de ubicación: bloques, zonas, etc.';

-- 4.3 Sub-área (Aulas, Baños, Oficinas)
CREATE TABLE IF NOT EXISTS subarea (
    id_subarea          BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion      BIGINT UNSIGNED     NOT NULL,
    id_area             BIGINT UNSIGNED     NOT NULL,
    nombre              VARCHAR(100)        NOT NULL,
    descripcion         VARCHAR(255)        NULL,
    activa              TINYINT(1)          NOT NULL DEFAULT 1,
    orden               SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    fecha_creacion      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_subarea),
    CONSTRAINT fk_sub_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    CONSTRAINT fk_sub_area FOREIGN KEY (id_area)        REFERENCES area(id_area),
    INDEX idx_sub_area (id_institucion, id_area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nivel 3 de ubicación: aulas, baños, oficinas, etc. RF-06';

-- ============================================================
-- BLOQUE 5: CLASIFICACIÓN DE DAÑOS
-- ============================================================

-- 5.1 Categoría principal
CREATE TABLE IF NOT EXISTS categoria (
    id_categoria            BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    nombre                  VARCHAR(80)         NOT NULL,
    descripcion             VARCHAR(255)        NULL,
    es_critica_escalada     TINYINT(1)          NOT NULL DEFAULT 0
                            COMMENT 'RN-06: 1=escala automáticamente a urgencia Urgente',
    activa                  TINYINT(1)          NOT NULL DEFAULT 1,
    orden                   SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    fecha_creacion          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_categoria),
    CONSTRAINT fk_cat_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    INDEX idx_cat_inst (id_institucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categoría principal: Infraestructura, Eléctrico, etc. RF-06, RN-06';

-- 5.2 Subcategoría
CREATE TABLE IF NOT EXISTS subcategoria (
    id_subcategoria         BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    id_categoria            BIGINT UNSIGNED     NOT NULL,
    nombre                  VARCHAR(100)        NOT NULL,
    descripcion             VARCHAR(255)        NULL,
    activa                  TINYINT(1)          NOT NULL DEFAULT 1,
    orden                   SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    fecha_creacion          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_subcategoria),
    CONSTRAINT fk_scat_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    CONSTRAINT fk_scat_cat  FOREIGN KEY (id_categoria)   REFERENCES categoria(id_categoria),
    INDEX idx_scat_cat (id_institucion, id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subcategoría: Techos, Muros, Luminarias, etc. RF-06';

-- ============================================================
-- BLOQUE 6: SLA (Acuerdos de Nivel de Servicio)
-- ============================================================

CREATE TABLE IF NOT EXISTS sla (
    id_sla                      BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion              BIGINT UNSIGNED     NOT NULL,
    id_categoria                BIGINT UNSIGNED     NULL COMMENT 'NULL = aplica a todas las categorías',
    id_urgencia                 TINYINT UNSIGNED    NOT NULL,
    tiempo_respuesta_horas      SMALLINT UNSIGNED   NOT NULL COMMENT 'Horas hábiles máx. para asignar',
    tiempo_resolucion_horas     SMALLINT UNSIGNED   NOT NULL COMMENT 'Horas hábiles máx. para resolver',
    activo                      TINYINT(1)          NOT NULL DEFAULT 1,
    fecha_creacion              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion         DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_sla),
    UNIQUE KEY uk_sla_inst_cat_urg (id_institucion, id_categoria, id_urgencia),
    CONSTRAINT fk_sla_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    CONSTRAINT fk_sla_cat  FOREIGN KEY (id_categoria)   REFERENCES categoria(id_categoria),
    CONSTRAINT fk_sla_urg  FOREIGN KEY (id_urgencia)    REFERENCES urgencia(id_urgencia),
    INDEX idx_sla_inst (id_institucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='SLA configurables por institución, categoría y urgencia. RF-13, Sección 8 del ERS';

-- ============================================================
-- BLOQUE 7: REPORTE (Entidad central del sistema)
-- ============================================================

CREATE TABLE IF NOT EXISTS reporte (
    id_reporte                      BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion                  BIGINT UNSIGNED     NOT NULL,
    numero_ticket                   VARCHAR(30)         NOT NULL COMMENT 'RN-07: único e inmutable',
    token_seguimiento_publico       CHAR(36)            NOT NULL COMMENT 'UUID para acceso sin login. RN-11',
    -- Datos del reportante (desnormalizados para soportar reportes anónimos y cambios de datos)
    id_reportante                   BIGINT UNSIGNED     NOT NULL,
    nombre_reportante               VARCHAR(150)        NOT NULL,
    cargo_reportante                VARCHAR(100)        NULL,
    correo_reportante               VARCHAR(150)        NOT NULL,
    telefono_reportante             VARCHAR(20)         NULL,
    es_anonimo                      TINYINT(1)          NOT NULL DEFAULT 0 COMMENT 'RF-09, RN-09',
    -- Ubicación (RN-02: sede y area son NOT NULL)
    id_sede                         BIGINT UNSIGNED     NOT NULL,
    id_area                         BIGINT UNSIGNED     NOT NULL,
    id_subarea                      BIGINT UNSIGNED     NULL,
    referencia_ubicacion_libre      VARCHAR(255)        NULL,
    -- Clasificación
    id_categoria                    BIGINT UNSIGNED     NOT NULL COMMENT 'RN-02: NOT NULL',
    id_subcategoria                 BIGINT UNSIGNED     NULL,
    id_urgencia_declarada           TINYINT UNSIGNED    NOT NULL,
    id_urgencia_calculada           TINYINT UNSIGNED    NOT NULL COMMENT 'Puede ser mayor por RN-06',
    -- Descripción (RN-02: NOT NULL)
    descripcion_problema            TEXT                NOT NULL,
    -- Asignación
    id_estado                       TINYINT UNSIGNED    NOT NULL,
    id_tecnico_asignado             BIGINT UNSIGNED     NULL,
    id_gestor_asignador             BIGINT UNSIGNED     NULL,
    -- Timestamps del ciclo de vida
    fecha_hora_registro             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    COMMENT 'RN-12: inmutable, generada por el sistema',
    fecha_hora_asignacion           DATETIME            NULL,
    fecha_hora_inicio_tecnico       DATETIME            NULL,
    fecha_hora_solucionado          DATETIME            NULL,
    fecha_hora_cierre               DATETIME            NULL,
    -- SLA
    fecha_pausa_sla                 DATETIME            NULL COMMENT 'RN-10: inicio de pausa del SLA',
    total_horas_pausadas            DECIMAL(8,2)        NOT NULL DEFAULT 0 COMMENT 'Acumulado de horas fuera de SLA',
    -- Flags adicionales
    justificacion_anulacion         TEXT                NULL,
    es_reutilizable_solucion        TINYINT(1)          NOT NULL DEFAULT 0 COMMENT 'RF-20',
    fecha_actualizacion             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                                 ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reporte),
    UNIQUE KEY uk_rpt_ticket (id_institucion, numero_ticket),
    UNIQUE KEY uk_rpt_token  (token_seguimiento_publico),
    CONSTRAINT fk_rpt_inst        FOREIGN KEY (id_institucion)        REFERENCES institucion(id_institucion),
    CONSTRAINT fk_rpt_reportante  FOREIGN KEY (id_reportante)         REFERENCES usuario(id_usuario),
    CONSTRAINT fk_rpt_sede        FOREIGN KEY (id_sede)               REFERENCES sede(id_sede),
    CONSTRAINT fk_rpt_area        FOREIGN KEY (id_area)               REFERENCES area(id_area),
    CONSTRAINT fk_rpt_subarea     FOREIGN KEY (id_subarea)            REFERENCES subarea(id_subarea),
    CONSTRAINT fk_rpt_cat         FOREIGN KEY (id_categoria)          REFERENCES categoria(id_categoria),
    CONSTRAINT fk_rpt_scat        FOREIGN KEY (id_subcategoria)       REFERENCES subcategoria(id_subcategoria),
    CONSTRAINT fk_rpt_urg_decl    FOREIGN KEY (id_urgencia_declarada) REFERENCES urgencia(id_urgencia),
    CONSTRAINT fk_rpt_urg_calc    FOREIGN KEY (id_urgencia_calculada) REFERENCES urgencia(id_urgencia),
    CONSTRAINT fk_rpt_estado      FOREIGN KEY (id_estado)             REFERENCES estado(id_estado),
    CONSTRAINT fk_rpt_tecnico     FOREIGN KEY (id_tecnico_asignado)   REFERENCES usuario(id_usuario),
    CONSTRAINT fk_rpt_gestor      FOREIGN KEY (id_gestor_asignador)   REFERENCES usuario(id_usuario),
    -- Índices compuestos para performance (RNF-07, RNF-08)
    INDEX idx_rpt_inst_estado     (id_institucion, id_estado),
    INDEX idx_rpt_inst_tecnico    (id_institucion, id_tecnico_asignado, id_estado),
    INDEX idx_rpt_inst_fecha      (id_institucion, fecha_hora_registro DESC),
    INDEX idx_rpt_inst_cat_urg    (id_institucion, id_categoria, id_urgencia_calculada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Entidad central: registro completo del ciclo de vida del daño reportado';

-- ============================================================
-- BLOQUE 8: INFORME DE INTERVENCIÓN (1:1 con reporte)
-- ============================================================

CREATE TABLE IF NOT EXISTS informe_intervencion (
    id_informe                  BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_reporte                  BIGINT UNSIGNED     NOT NULL,
    id_institucion              BIGINT UNSIGNED     NOT NULL,
    id_usuario_tecnico          BIGINT UNSIGNED     NOT NULL COMMENT 'RN-05: debe ser el técnico asignado',
    descripcion_actividades     TEXT                NOT NULL,
    causa_raiz                  TEXT                NULL,
    solucion_implementada       TEXT                NOT NULL,
    fecha_hora_inicio           DATETIME            NOT NULL,
    fecha_hora_fin              DATETIME            NULL,
    materiales_utilizados_json  JSON                NULL
                                COMMENT 'Array: [{nombre, cantidad, unidad_medida}]. RF-17',
    costo_estimado              DECIMAL(12,2)       NULL COMMENT 'Opcional, control presupuestal. RF-17',
    fecha_creacion              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion         DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_informe),
    UNIQUE KEY uk_inf_reporte (id_reporte) COMMENT 'Máximo 1 informe activo por reporte',
    CONSTRAINT fk_inf_rpt     FOREIGN KEY (id_reporte)         REFERENCES reporte(id_reporte),
    CONSTRAINT fk_inf_inst    FOREIGN KEY (id_institucion)     REFERENCES institucion(id_institucion),
    CONSTRAINT fk_inf_tecnico FOREIGN KEY (id_usuario_tecnico) REFERENCES usuario(id_usuario),
    INDEX idx_inf_inst (id_institucion, id_reporte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Informe técnico de la intervención. RF-17, RN-05';

-- ============================================================
-- BLOQUE 9: EVIDENCIA FOTOGRÁFICA / VIDEO
-- ============================================================

CREATE TABLE IF NOT EXISTS evidencia (
    id_evidencia            BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_reporte              BIGINT UNSIGNED     NOT NULL,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    id_etapa                TINYINT UNSIGNED    NOT NULL COMMENT 'Antes=1, Durante=2, Después=3',
    url_archivo             VARCHAR(500)        NOT NULL COMMENT 'Ruta no pública en storage',
    nombre_archivo_original VARCHAR(255)        NOT NULL,
    tipo_mime               VARCHAR(80)         NOT NULL COMMENT 'Validado. RNF-04',
    tamanio_bytes           INT UNSIGNED        NOT NULL,
    hash_archivo            CHAR(64)            NULL COMMENT 'SHA-256 para verificación de integridad',
    descripcion             VARCHAR(255)        NULL,
    cargada_por             BIGINT UNSIGNED     NOT NULL,
    fecha_hora_carga        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evidencia),
    CONSTRAINT fk_ev_rpt     FOREIGN KEY (id_reporte)    REFERENCES reporte(id_reporte),
    CONSTRAINT fk_ev_inst    FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    CONSTRAINT fk_ev_etapa   FOREIGN KEY (id_etapa)       REFERENCES etapa_evidencia(id_etapa),
    CONSTRAINT fk_ev_usuario FOREIGN KEY (cargada_por)    REFERENCES usuario(id_usuario),
    INDEX idx_ev_rpt_etapa   (id_reporte, id_etapa) COMMENT 'RN-03: validar ≥1 por etapa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Evidencias fotográficas/video clasificadas por etapa. RF-18, RN-03';

-- ============================================================
-- BLOQUE 10: COMENTARIOS INTERNOS
-- ============================================================

CREATE TABLE IF NOT EXISTS comentario_interno (
    id_comentario       BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_reporte          BIGINT UNSIGNED     NOT NULL,
    id_institucion      BIGINT UNSIGNED     NOT NULL,
    id_usuario_autor    BIGINT UNSIGNED     NOT NULL,
    texto               TEXT                NOT NULL,
    es_editado          TINYINT(1)          NOT NULL DEFAULT 0,
    fecha_hora_edicion  DATETIME            NULL,
    fecha_creacion      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_comentario),
    CONSTRAINT fk_com_rpt  FOREIGN KEY (id_reporte)       REFERENCES reporte(id_reporte),
    CONSTRAINT fk_com_inst FOREIGN KEY (id_institucion)   REFERENCES institucion(id_institucion),
    CONSTRAINT fk_com_usr  FOREIGN KEY (id_usuario_autor) REFERENCES usuario(id_usuario),
    INDEX idx_com_rpt_fecha (id_reporte, fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hilo privado de comunicación por reporte. RF-15';

-- ============================================================
-- BLOQUE 11: HISTORIAL DE ESTADOS (Trazabilidad)
-- ============================================================

CREATE TABLE IF NOT EXISTS transicion_estado (
    id_transicion           BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_reporte              BIGINT UNSIGNED     NOT NULL,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    id_estado_origen        TINYINT UNSIGNED    NULL COMMENT 'NULL en la creación inicial del reporte',
    id_estado_destino       TINYINT UNSIGNED    NOT NULL,
    id_usuario_ejecutor     BIGINT UNSIGNED     NOT NULL,
    comentario              TEXT                NULL,
    ip_origen               VARCHAR(45)         NULL COMMENT 'IPv4 o IPv6',
    fecha_hora_transicion   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_transicion),
    CONSTRAINT fk_ts_rpt     FOREIGN KEY (id_reporte)          REFERENCES reporte(id_reporte),
    CONSTRAINT fk_ts_inst    FOREIGN KEY (id_institucion)      REFERENCES institucion(id_institucion),
    CONSTRAINT fk_ts_origen  FOREIGN KEY (id_estado_origen)    REFERENCES estado(id_estado),
    CONSTRAINT fk_ts_destino FOREIGN KEY (id_estado_destino)   REFERENCES estado(id_estado),
    CONSTRAINT fk_ts_usr     FOREIGN KEY (id_usuario_ejecutor) REFERENCES usuario(id_usuario),
    INDEX idx_ts_rpt_fecha   (id_reporte, fecha_hora_transicion DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro inmutable de cada cambio de estado. OBJ-01, RF-04';

-- ============================================================
-- BLOQUE 12: NOTIFICACIONES (Cola + Historial)
-- ============================================================

CREATE TABLE IF NOT EXISTS notificacion (
    id_notificacion             BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion              BIGINT UNSIGNED     NOT NULL,
    id_reporte                  BIGINT UNSIGNED     NULL COMMENT 'NULL para notificaciones generales',
    id_usuario_destinatario     BIGINT UNSIGNED     NOT NULL,
    tipo_evento                 VARCHAR(60)         NOT NULL COMMENT 'reporte_registrado, sla_vencido…',
    asunto                      VARCHAR(250)        NOT NULL,
    cuerpo_html                 MEDIUMTEXT          NOT NULL,
    estado_envio                ENUM('pendiente','enviado','fallido') NOT NULL DEFAULT 'pendiente',
    fecha_programada            DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_enviada               DATETIME            NULL,
    razon_fallo                 TEXT                NULL,
    intentos                    TINYINT UNSIGNED    NOT NULL DEFAULT 0,
    fecha_creacion              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_notificacion),
    CONSTRAINT fk_noti_inst  FOREIGN KEY (id_institucion)          REFERENCES institucion(id_institucion),
    CONSTRAINT fk_noti_rpt   FOREIGN KEY (id_reporte)              REFERENCES reporte(id_reporte),
    CONSTRAINT fk_noti_usr   FOREIGN KEY (id_usuario_destinatario) REFERENCES usuario(id_usuario),
    INDEX idx_noti_cola (id_institucion, estado_envio, fecha_programada) COMMENT 'Procesamiento de cola'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cola de envío y registro histórico de correos. RF-23, RF-24';

-- ============================================================
-- BLOQUE 13: ENCUESTA DE SATISFACCIÓN
-- ============================================================

CREATE TABLE IF NOT EXISTS encuesta_satisfaccion (
    id_encuesta             BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_reporte              BIGINT UNSIGNED     NOT NULL,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    id_usuario_reportante   BIGINT UNSIGNED     NOT NULL,
    puntuacion              TINYINT UNSIGNED    NULL     COMMENT '1-5 estrellas, NULL si no respondió',
    comentario              TEXT                NULL,
    fue_respondida          TINYINT(1)          NOT NULL DEFAULT 0,
    fecha_enviada           DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_completada        DATETIME            NULL,
    PRIMARY KEY (id_encuesta),
    UNIQUE KEY uk_enc_reporte (id_reporte),
    CONSTRAINT fk_enc_rpt  FOREIGN KEY (id_reporte)            REFERENCES reporte(id_reporte),
    CONSTRAINT fk_enc_inst FOREIGN KEY (id_institucion)        REFERENCES institucion(id_institucion),
    CONSTRAINT fk_enc_usr  FOREIGN KEY (id_usuario_reportante) REFERENCES usuario(id_usuario),
    CONSTRAINT chk_enc_puntuacion CHECK (puntuacion IS NULL OR (puntuacion BETWEEN 1 AND 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Evaluación del reportante al cierre del reporte. RF-22';

-- ============================================================
-- BLOQUE 14: REGISTRO DE AUDITORÍA GENERAL
-- ============================================================

CREATE TABLE IF NOT EXISTS registro_auditoria (
    id_auditoria            BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion          BIGINT UNSIGNED     NULL COMMENT 'NULL para acciones de sistema global',
    id_usuario              BIGINT UNSIGNED     NULL COMMENT 'NULL para procesos automáticos',
    accion                  VARCHAR(80)         NOT NULL COMMENT 'crear_reporte, asignar, cerrar…',
    entidad                 VARCHAR(50)         NOT NULL COMMENT 'Nombre de la tabla afectada',
    id_entidad              BIGINT UNSIGNED     NULL     COMMENT 'PK del registro modificado',
    datos_anteriores_json   JSON                NULL,
    datos_nuevos_json       JSON                NULL,
    ip_origen               VARCHAR(45)         NULL,
    user_agent              VARCHAR(500)        NULL,
    fecha_hora_accion       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    CONSTRAINT fk_aud_inst FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
    CONSTRAINT fk_aud_usr  FOREIGN KEY (id_usuario)    REFERENCES usuario(id_usuario),
    INDEX idx_aud_inst_fecha (id_institucion, fecha_hora_accion DESC),
    INDEX idx_aud_entidad    (entidad, id_entidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Trazabilidad general de acciones críticas. RF-04, OBJ-01, RNF-14';

-- ============================================================
-- BLOQUE 15: BASE DE CONOCIMIENTO (Soluciones Reutilizables)
-- ============================================================

CREATE TABLE IF NOT EXISTS plantilla_solucion (
    id_plantilla            BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_institucion          BIGINT UNSIGNED     NOT NULL,
    id_categoria            BIGINT UNSIGNED     NULL,
    id_subcategoria         BIGINT UNSIGNED     NULL,
    titulo                  VARCHAR(150)        NOT NULL,
    descripcion_problema    TEXT                NULL,
    descripcion_solucion    TEXT                NOT NULL,
    pasos_json              JSON                NULL COMMENT 'Array de pasos ordenados',
    materiales_json         JSON                NULL COMMENT 'Materiales típicos para esta solución',
    tiempo_estimado_minutos SMALLINT UNSIGNED   NULL,
    id_reporte_origen       BIGINT UNSIGNED     NULL COMMENT 'Reporte que originó esta plantilla',
    veces_utilizada         INT UNSIGNED        NOT NULL DEFAULT 0,
    activa                  TINYINT(1)          NOT NULL DEFAULT 1,
    fecha_creacion          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                         ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_plantilla),
    CONSTRAINT fk_ps_inst   FOREIGN KEY (id_institucion)  REFERENCES institucion(id_institucion),
    CONSTRAINT fk_ps_cat    FOREIGN KEY (id_categoria)    REFERENCES categoria(id_categoria),
    CONSTRAINT fk_ps_scat   FOREIGN KEY (id_subcategoria) REFERENCES subcategoria(id_subcategoria),
    CONSTRAINT fk_ps_rpt    FOREIGN KEY (id_reporte_origen) REFERENCES reporte(id_reporte),
    INDEX idx_ps_inst_cat (id_institucion, id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Repositorio de soluciones reutilizables. RF-20';

-- ============================================================
-- BLOQUE 16: TRIGGERS DE INTEGRIDAD
-- ============================================================

-- TRG-01: Proteger fecha_hora_registro de modificaciones (RN-12)
DROP TRIGGER IF EXISTS trg_reporte_fecha_inmutable;
DELIMITER $$
CREATE TRIGGER trg_reporte_fecha_inmutable
BEFORE UPDATE ON reporte
FOR EACH ROW
BEGIN
    IF NEW.fecha_hora_registro <> OLD.fecha_hora_registro THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'RN-12: fecha_hora_registro es inmutable.';
    END IF;
END$$
DELIMITER ;

-- TRG-02: Proteger numero_ticket de modificaciones (RN-07)
DROP TRIGGER IF EXISTS trg_reporte_ticket_inmutable;
DELIMITER $$
CREATE TRIGGER trg_reporte_ticket_inmutable
BEFORE UPDATE ON reporte
FOR EACH ROW
BEGIN
    IF NEW.numero_ticket <> OLD.numero_ticket THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'RN-07: numero_ticket es inmutable una vez generado.';
    END IF;
END$$
DELIMITER ;

-- TRG-03: Proteger token_seguimiento_publico de modificaciones (RN-11)
DROP TRIGGER IF EXISTS trg_reporte_token_inmutable;
DELIMITER $$
CREATE TRIGGER trg_reporte_token_inmutable
BEFORE UPDATE ON reporte
FOR EACH ROW
BEGIN
    IF NEW.token_seguimiento_publico <> OLD.token_seguimiento_publico THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'RN-11: token_seguimiento_publico es inmutable.';
    END IF;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DEL SCRIPT DDL — SIRGDI v2.0
-- ============================================================
-- Tablas creadas: 22 tablas de negocio + 2 tablas intermedias = 24 total
-- Triggers:       3 (inmutabilidad de campos críticos)
-- Índices:        20+ índices compuestos para rendimiento multitenant
-- ============================================================
