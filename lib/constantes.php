<?php
// Constantes globales del sistema SIRGDI

// Estados del reporte (RN-12: inmutables)
define('ESTADO_REGISTRADO', 1);
define('ESTADO_ASIGNADO', 2);
define('ESTADO_EN_PROCESO', 3);
define('ESTADO_SOLUCIONADO', 4);
define('ESTADO_EN_VALIDACION', 5);
define('ESTADO_DEVUELTO', 6);
define('ESTADO_CERRADO', 7);
define('ESTADO_ANULADO', 8);

// Urgencias (RN-06: escalado automático para críticas)
define('URGENCIA_NO_URGENTE', 1);
define('URGENCIA_MODERADO', 2);
define('URGENCIA_IMPORTANTE', 3);
define('URGENCIA_URGENTE', 4);

// Etapas de evidencia (RN-03)
define('ETAPA_ANTES', 1);
define('ETAPA_DURANTE', 2);
define('ETAPA_DESPUES', 3);

// Roles del sistema (6 roles del ERS)
define('ROL_REPORTANTE', 1);
define('ROL_TECNICO', 2);
define('ROL_GESTOR', 3);
define('ROL_RECTOR', 4);
define('ROL_ADMIN', 5);
define('ROL_SUPERADMIN', 6);

// Permisos (23 permisos totales)
define('PERMISO_CREAR_REPORTE', 'crear_reporte');
define('PERMISO_VER_REPORTES_PROPIOS', 'ver_propio_reporte');
define('PERMISO_VER_TODOS_REPORTES', 'ver_todos_reportes');
define('PERMISO_ASIGNAR_TECNICO', 'asignar_tecnico');
define('PERMISO_REGISTRAR_INFORME', 'registrar_informe');
define('PERMISO_CARGAR_EVIDENCIA', 'cargar_evidencia');
define('PERMISO_CERRAR_REPORTE', 'cerrar_reporte');
define('PERMISO_VER_AUDITORIA', 'ver_auditoria');
define('PERMISO_GESTIONAR_USUARIOS', 'gestionar_usuarios');
define('PERMISO_GESTIONAR_ROLES', 'gestionar_roles');
define('PERMISO_CONFIGURAR_INSTITUCION', 'configurar_institucion');
define('PERMISO_VER_DASHBOARD', 'ver_dashboard');
define('PERMISO_EXPORTAR_REPORTES', 'exportar_informes');
define('PERMISO_VALIDAR_CIERRE', 'validar_cerrar');
define('PERMISO_TECNICO', 'registrar_informe');
define('PERMISO_GESTIONAR_REPORTES', 'ver_todos_reportes');
define('PERMISO_GESTIONAR_INSTITUCIONES', 'gestionar_instituciones');

// Códigos HTTP
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_CONFLICT', 409);
define('HTTP_INTERNAL_ERROR', 500);

// Mensajes de error estándar
define('ERROR_NO_AUTENTICADO', 'Usuario no autenticado. Por favor, inicie sesión.');
define('ERROR_ACCESO_DENEGADO', 'No tiene permiso para acceder a este recurso.');
define('ERROR_RECURSO_NO_ENCONTRADO', 'El recurso solicitado no fue encontrado.');
define('ERROR_INSTITUCION_MISMATCH', 'Acceso denegado: datos de otra institución.');
define('ERROR_SESION_EXPIRADA', 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.');
define('ERROR_CSRF_TOKEN', 'Token CSRF inválido o expirado.');
define('ERROR_2FA_REQUERIDO', 'Autenticación de dos factores requerida.');

// Tiempos de sesión (RNF-05)
define('SESSION_TIMEOUT_SECONDS', 1800); // 30 minutos
define('SESSION_ABSOLUTE_TIMEOUT_SECONDS', 28800); // 8 horas

// Compresión de fotos (RNF-04)
define('MAX_FOTO_SIZE_BYTES', 5242880); // 5MB
define('FOTO_QUALITY', 85); // JPG quality 1-100
define('FOTO_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Algoritmo de hash (RNF-01)
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);

// Formato de ticket (RN-07)
define('TICKET_PREFIX', 'SIR'); // SIRGDI Reporte

// Ubicación de logs
define('LOG_DIR', ROOT_PATH . '/almacenamiento/logs');
define('ERROR_LOG', LOG_DIR . '/errores.log');
define('AUDIT_LOG', LOG_DIR . '/auditoria.log');

// Rate limiting de login (sección 3.1 seguridad)
define('MAX_INTENTOS_LOGIN', 5);           // Intentos antes de bloquear
define('VENTANA_INTENTOS_LOGIN', 900);     // 15 minutos (ventana de conteo)
define('BLOQUEO_LOGIN_SEGUNDOS', 900);     // 15 minutos bloqueado
define('RATE_LIMIT_DIR', ROOT_PATH . '/almacenamiento/cache/rate_limit');
