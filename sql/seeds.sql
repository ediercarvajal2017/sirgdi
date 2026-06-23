-- ============================================================
-- SIRGDI v2.0 — Script de Datos Semilla (Seeds)
-- Ejecutar DESPUÉS de schema.sql
-- ============================================================

USE sirgdi;

-- ============================================================
-- BLOQUE 1: CATÁLOGOS GLOBALES
-- ============================================================

-- Urgencias (RF-06, sección 8 del ERS)
INSERT INTO urgencia (id_urgencia, nombre, nivel_numero, color_hex) VALUES
(1, 'No urgente', 0, '#28A745'),
(2, 'Moderado',   1, '#FFC107'),
(3, 'Importante', 2, '#FD7E14'),
(4, 'Urgente',    3, '#DC3545');

-- Estados del ciclo de vida del reporte (sección 6 del ERS)
INSERT INTO estado (id_estado, nombre, descripcion, es_terminal) VALUES
(1, 'Registrado',         'Reporte creado, pendiente de clasificación y asignación.',          0),
(2, 'Asignado',           'Clasificado, priorizado y asignado a un técnico.',                  0),
(3, 'En proceso',         'El técnico inició la intervención.',                                0),
(4, 'Solucionado',        'Técnico documentó la solución y cargó las evidencias requeridas.',  0),
(5, 'Requiere revisión',  'El gestor verifica el trabajo antes de cerrar formalmente.',        0),
(6, 'Devuelto',           'Devuelto por información insuficiente o trabajo incompleto.',       0),
(7, 'Cerrado',            'Validado y cerrado formalmente. Dispara notificación de cierre.',   1),
(8, 'Anulado',            'Inválido, duplicado o improcedente. Requiere justificación.',       1);

-- Etapas de evidencia fotográfica (RF-18, RN-03)
INSERT INTO etapa_evidencia (id_etapa, nombre, orden) VALUES
(1, 'Antes',   1),
(2, 'Durante', 2),
(3, 'Después', 3);

-- Roles del sistema (sección 1.1 del ERS)
INSERT INTO rol (id_rol, nombre_rol, descripcion) VALUES
(1, 'Reportante',             'Docente, administrativo o coordinador que registra daños.'),
(2, 'Técnico',                'Personal de mantenimiento que ejecuta y documenta reparaciones.'),
(3, 'Gestor',                 'Coordinador de mantenimiento: clasifica, prioriza y asigna reportes.'),
(4, 'Rector',                 'Máxima autoridad: supervisa, aprueba cierres y recibe informes.'),
(5, 'Admin de Institución',   'Configura la plataforma: usuarios, áreas, categorías y SLA.'),
(6, 'Superadministrador',     'Operador global multitenant: gestiona todas las instituciones.');

-- Permisos granulares por módulo (sección 3.1 del ERS)
INSERT INTO permiso (codigo, descripcion, modulo) VALUES
-- Módulo Reportes
('crear_reporte',           'Crear un nuevo reporte de daño.',                                  'reportes'),
('ver_propio_reporte',      'Ver y hacer seguimiento de sus propios reportes.',                 'reportes'),
('ver_todos_reportes',      'Ver todos los reportes de la institución.',                        'reportes'),
-- Módulo Gestión
('clasificar_reporte',      'Cambiar categoría, urgencia y prioridad de un reporte.',           'gestion'),
('asignar_tecnico',         'Asignar un reporte a un técnico de mantenimiento.',                'gestion'),
('fusionar_reportes',       'Fusionar reportes duplicados y vincular relacionados.',            'gestion'),
-- Módulo Técnico
('registrar_informe',       'Registrar el informe de intervención técnica.',                    'tecnico'),
('cargar_evidencia',        'Cargar evidencia fotográfica por etapa (antes/durante/después).',  'tecnico'),
('marcar_solucionado',      'Marcar un reporte como Solucionado.',                              'tecnico'),
-- Módulo Cierre
('validar_cerrar',          'Validar el trabajo del técnico y cerrar formalmente el reporte.',  'cierre'),
('devolver_reporte',        'Devolver el reporte al técnico o reportante con observaciones.',   'cierre'),
('anular_reporte',          'Anular un reporte inválido, duplicado o improcedente.',            'cierre'),
-- Módulo Comentarios
('comentar_interno',        'Agregar comentarios internos visibles solo para el equipo.',       'comentarios'),
-- Módulo Analítica
('ver_dashboard',           'Acceder al dashboard de KPIs e indicadores.',                      'analitica'),
('exportar_informes',       'Exportar reportes e informes en formato PDF y Excel.',             'analitica'),
-- Módulo Administración
('gestionar_usuarios',      'Crear, editar y desactivar usuarios de la institución.',           'admin'),
('gestionar_categorias',    'Configurar categorías y subcategorías de daños.',                  'admin'),
('gestionar_areas',         'Configurar sedes, áreas y sub-áreas.',                            'admin'),
('gestionar_sla',           'Configurar acuerdos de nivel de servicio (SLA).',                 'admin'),
('gestionar_plantillas',    'Editar plantillas de correo electrónico.',                         'admin'),
('ver_auditoria',           'Consultar el registro de auditoría de la institución.',            'admin'),
('configurar_institucion',  'Modificar los parámetros generales de la institución.',            'admin'),
-- Superadmin
('gestionar_instituciones', 'Alta, baja y configuración global de instituciones.',              'superadmin');

-- ============================================================
-- Asignación de permisos a roles (Matriz RBAC — sección 3.1 del ERS)
-- ============================================================

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM rol r, permiso p
WHERE
    -- REPORTANTE
    (r.nombre_rol = 'Reportante' AND p.codigo IN (
        'crear_reporte', 'ver_propio_reporte'
    ))
    -- TÉCNICO
    OR (r.nombre_rol = 'Técnico' AND p.codigo IN (
        'crear_reporte', 'ver_propio_reporte',
        'registrar_informe', 'cargar_evidencia', 'marcar_solucionado',
        'comentar_interno'
    ))
    -- GESTOR
    OR (r.nombre_rol = 'Gestor' AND p.codigo IN (
        'crear_reporte', 'ver_propio_reporte', 'ver_todos_reportes',
        'clasificar_reporte', 'asignar_tecnico', 'fusionar_reportes',
        'validar_cerrar', 'devolver_reporte', 'anular_reporte',
        'comentar_interno',
        'ver_dashboard', 'exportar_informes',
        'gestionar_categorias', 'gestionar_areas', 'gestionar_sla', 'ver_auditoria'
    ))
    -- RECTOR (superconjunto del Gestor + configuración institucional)
    OR (r.nombre_rol = 'Rector' AND p.codigo IN (
        'crear_reporte', 'ver_propio_reporte', 'ver_todos_reportes',
        'clasificar_reporte', 'asignar_tecnico', 'fusionar_reportes',
        'validar_cerrar', 'devolver_reporte', 'anular_reporte',
        'comentar_interno',
        'ver_dashboard', 'exportar_informes',
        'gestionar_categorias', 'gestionar_areas', 'gestionar_sla',
        'gestionar_plantillas', 'ver_auditoria', 'configurar_institucion'
    ))
    -- ADMIN DE INSTITUCIÓN
    OR (r.nombre_rol = 'Admin de Institución' AND p.codigo IN (
        'crear_reporte', 'ver_propio_reporte', 'ver_todos_reportes',
        'comentar_interno',
        'ver_dashboard', 'exportar_informes',
        'gestionar_usuarios', 'gestionar_categorias', 'gestionar_areas',
        'gestionar_sla', 'gestionar_plantillas', 'ver_auditoria', 'configurar_institucion'
    ))
    -- SUPERADMINISTRADOR (todos los permisos)
    OR (r.nombre_rol = 'Superadministrador');

-- ============================================================
-- BLOQUE 2: INSTITUCIÓN DEMO
-- ============================================================

INSERT INTO institucion (id_institucion, nombre, logo_url, es_activa)
VALUES (1, 'Institución Educativa Demo', NULL, 1);

-- Configuración de la institución demo (TABLA NO EXISTE EN SCHEMA)
-- COMENTADO: Esta tabla no está en schema.sql
-- INSERT INTO configuracion_institucion (
--     id_institucion,
--     formato_numero_ticket,
--     correo_remitente,
--     es_reporte_anonimo_permitido,
--     tiempo_sesion_minutos,
--     horas_laborales_inicio,
--     horas_laborales_fin,
--     dias_no_laborales_json,
--     plantilla_asunto_registro,
--     plantilla_asunto_asignado,
--     plantilla_asunto_cerrado
-- ) VALUES (
--     1,
--     'DEMO-{YYYY}-{SEQ5}',
--     'sirgdi@instituciondemo.edu.co',
--     0,
--     30,
--     7,
--     17,
--     '[6, 7]',
--     'SIRGDI — Reporte #{TICKET} registrado correctamente',
--     'SIRGDI — Su reporte #{TICKET} ha sido asignado a un técnico',
--     'SIRGDI — Reporte #{TICKET} cerrado: {DESCRIPCION_CORTA}'
-- );

-- ============================================================
-- BLOQUE 3: SEDES Y ÁREAS DEMO
-- ============================================================

INSERT INTO sede (id_sede, id_institucion, nombre, direccion, activa) VALUES
(1, 1, 'Sede Principal',    'Calle 1 # 2-3, ciudad de referencia', 1),
(2, 1, 'Sede Secundaria',   'Carrera 4 # 5-6, ciudad de referencia', 1);

INSERT INTO area (id_area, id_institucion, id_sede, nombre, orden) VALUES
(1, 1, 1, 'Bloque A',       1),
(2, 1, 1, 'Bloque B',       2),
(3, 1, 1, 'Bloque C',       3),
(4, 1, 1, 'Zona Deportiva', 4),
(5, 1, 1, 'Servicios',      5),
(6, 1, 2, 'Bloque Único',   1);

INSERT INTO subarea (id_institucion, id_area, nombre, orden) VALUES
-- Bloque A
(1, 1, 'Aula 101', 1), (1, 1, 'Aula 102', 2), (1, 1, 'Aula 103', 3),
(1, 1, 'Baño hombres', 4), (1, 1, 'Baño mujeres', 5),
-- Bloque B
(1, 2, 'Laboratorio de Ciencias', 1), (1, 2, 'Laboratorio de Sistemas', 2),
(1, 2, 'Sala de Profesores', 3), (1, 2, 'Oficina Coordinación', 4),
-- Bloque C
(1, 3, 'Aula 301', 1), (1, 3, 'Aula 302', 2), (1, 3, 'Biblioteca', 3),
-- Servicios
(1, 5, 'Rectoría', 1), (1, 5, 'Secretaría', 2), (1, 5, 'Cafetería', 3),
(1, 5, 'Portería Principal', 4);

-- ============================================================
-- BLOQUE 4: CATEGORÍAS Y SUBCATEGORÍAS DEMO
-- ============================================================

INSERT INTO categoria (id_categoria, id_institucion, nombre, descripcion, es_critica_escalada, orden) VALUES
(1, 1, 'Infraestructura', 'Techos, muros, pisos, puertas y ventanas.',         0, 1),
(2, 1, 'Mobiliario',      'Sillas, mesas, tableros, estantes y lockers.',       0, 2),
(3, 1, 'Eléctrico',       'Tomas, luminarias, tableros eléctricos, cableado.',  1, 3),
(4, 1, 'Sanitario',       'Inodoros, lavamanos, tuberías y fugas de agua.',     0, 4),
(5, 1, 'Tecnológico',     'Computadores, proyectores, red y sistemas de audio.', 0, 5),
(6, 1, 'Seguridad',       'Cercas, extintores, cámaras y salidas de emergencia.', 1, 6);

INSERT INTO subcategoria (id_institucion, id_categoria, nombre, orden) VALUES
-- Infraestructura
(1, 1, 'Techos y cubierta', 1), (1, 1, 'Muros y paredes', 2),
(1, 1, 'Pisos y pavimento', 3), (1, 1, 'Puertas y marcos', 4),
(1, 1, 'Ventanas y vidrios', 5), (1, 1, 'Escaleras y rampas', 6),
-- Mobiliario
(1, 2, 'Sillas y pupitres', 1), (1, 2, 'Mesas y escritorios', 2),
(1, 2, 'Tableros (pizarrones)', 3), (1, 2, 'Estantes y armarios', 4),
-- Eléctrico
(1, 3, 'Tomas y enchufes', 1), (1, 3, 'Luminarias y bombillas', 2),
(1, 3, 'Tablero eléctrico', 3), (1, 3, 'Cableado y ductos', 4),
(1, 3, 'Sistemas de emergencia', 5),
-- Sanitario
(1, 4, 'Inodoros y sanitarios', 1), (1, 4, 'Lavamanos y grifería', 2),
(1, 4, 'Tuberías y desagües', 3), (1, 4, 'Fugas de agua', 4),
(1, 4, 'Tanques y cisternas', 5),
-- Tecnológico
(1, 5, 'Computadores y portátiles', 1), (1, 5, 'Proyectores y pantallas', 2),
(1, 5, 'Red e internet', 3), (1, 5, 'Sistemas de audio', 4),
-- Seguridad
(1, 6, 'Cercas y rejas', 1), (1, 6, 'Extintores', 2),
(1, 6, 'Cámaras de seguridad', 3), (1, 6, 'Salidas de emergencia', 4),
(1, 6, 'Señalización', 5);

-- ============================================================
-- BLOQUE 5: SLA (Valores de referencia — Sección 8 del ERS)
-- ============================================================

-- SLA (Acuerdos de Nivel de Servicio)
INSERT INTO sla (id_institucion, id_categoria, id_urgencia, tiempo_respuesta_horas, tiempo_resolucion_horas, activo) VALUES
-- SLA globales por urgencia (id_categoria NULL = aplica a todas)
(1, NULL, 4, 2,  8, 1),   -- Urgente: responder en 2h, resolver en 8h
(1, NULL, 3, 8,  24, 1),  -- Importante: 8h / 24h
(1, NULL, 2, 24, 72, 1),  -- Moderado: 24h / 72h
(1, NULL, 1, 48, 120, 1); -- No urgente: 48h / 120h (5 días hábiles)

-- ============================================================
-- BLOQUE 6: USUARIOS DEMO
-- ============================================================
-- IMPORTANTE: Los hashes aquí son FICTICIOS (placeholders).
-- En producción, generar con bcrypt/Argon2 factor≥12 (RNF-01).
-- Contraseña de referencia para desarrollo: 'Cambiar@2026!'
-- ============================================================

INSERT INTO usuario (id_usuario, id_institucion, nombre_completo, correo_electronico, cargo_descripcion, hash_contrasena, activo) VALUES
(1, 1, 'Superadministrador Demo', 'superadmin@sirgdi.edu.co',       'Superadministrador del sistema', '$2y$12$PLACEHOLDER_SUPERADMIN_HASH_00', 1),
(2, 1, 'Admin Institución Demo',  'admin@instituciondemo.edu.co',   'Administrador de la institución', '$2y$12$PLACEHOLDER_ADMIN_HASH_000000', 1),
(3, 1, 'María González',          'rector@instituciondemo.edu.co',  'Rectora',                         '$2y$12$PLACEHOLDER_RECTOR_HASH_000000', 1),
(4, 1, 'Carlos Pérez',            'gestor@instituciondemo.edu.co',  'Coordinador de Mantenimiento',    '$2y$12$PLACEHOLDER_GESTOR_HASH_000000', 1),
(5, 1, 'Luis Hernández',          'tecnico1@instituciondemo.edu.co','Técnico de Mantenimiento',         '$2y$12$PLACEHOLDER_TECNICO1_HASH_0000', 1),
(6, 1, 'Ana Martínez',            'tecnico2@instituciondemo.edu.co','Técnico de Mantenimiento',         '$2y$12$PLACEHOLDER_TECNICO2_HASH_0000', 1),
(7, 1, 'Pedro Ramírez',           'docente1@instituciondemo.edu.co','Docente — Matemáticas',            '$2y$12$PLACEHOLDER_REPORTANTE1_HASH00', 1),
(8, 1, 'Laura Torres',            'docente2@instituciondemo.edu.co','Docente — Ciencias Naturales',     '$2y$12$PLACEHOLDER_REPORTANTE2_HASH00', 1);

-- Asignación de roles a usuarios demo
INSERT INTO usuario_rol (id_usuario, id_rol, id_institucion) VALUES
(1, 6, 1),  -- superadmin → Superadministrador
(2, 5, 1),  -- admin      → Admin de Institución
(3, 4, 1),  -- rector     → Rector
(4, 3, 1),  -- gestor     → Gestor
(5, 2, 1),  -- técnico1   → Técnico
(6, 2, 1),  -- técnico2   → Técnico
(7, 1, 1),  -- docente1   → Reportante
(8, 1, 1);  -- docente2   → Reportante

-- ============================================================
-- FIN DEL SCRIPT SEEDS — SIRGDI v2.0
-- ============================================================
-- Registros insertados:
--   urgencia:          4 filas
--   estado:            8 filas
--   etapa_evidencia:   3 filas
--   rol:               6 filas
--   permiso:          23 filas
--   rol_permiso:      ~55 filas (calculado por SELECT)
--   institucion:       1 fila (demo)
--   configuracion:     1 fila
--   sede:              2 filas
--   area:              6 filas
--   subarea:          16 filas
--   categoria:         6 filas
--   subcategoria:     28 filas
--   sla:               8 filas
--   usuario:           8 filas (demo)
--   usuario_rol:       8 filas
-- ============================================================
