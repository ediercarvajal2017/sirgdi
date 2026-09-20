<?php
/**
 * Matriz base de permisos por rol (RBAC).
 *
 * Es la fuente única de qué permisos existen y cuáles recibe cada rol por defecto.
 * La usa la acción "Sincronizar permisos" del panel de superadministrador, que
 * añade lo que falte sin quitar nada que un administrador haya concedido después.
 * Los roles se identifican por id (constantes ROL_* en lib/constantes.php).
 *
 * El Superadministrador no aparece: tiene acceso a todo por código
 * (ServicioAutorizacion::verificar_permiso).
 */
return [
    // [codigo => [descripcion, modulo]]
    'permisos' => [
        'crear_reporte'          => ['Crear un nuevo reporte de daño.', 'reportes'],
        'ver_propio_reporte'     => ['Ver y hacer seguimiento de sus propios reportes.', 'reportes'],
        'ver_todos_reportes'     => ['Ver todos los reportes de la institución.', 'reportes'],
        'clasificar_reporte'     => ['Cambiar categoría, urgencia y prioridad de un reporte.', 'gestion'],
        'asignar_tecnico'        => ['Asignar un reporte a un técnico de mantenimiento.', 'gestion'],
        'fusionar_reportes'      => ['Fusionar reportes duplicados.', 'gestion'],
        'validar_cerrar'         => ['Validar la solución y cerrar formalmente un reporte.', 'cierre'],
        'devolver_reporte'       => ['Devolver un reporte al técnico por solución insuficiente.', 'cierre'],
        'anular_reporte'         => ['Anular un reporte con justificación.', 'cierre'],
        'registrar_informe'      => ['Registrar el informe técnico de una intervención.', 'tecnico'],
        'cargar_evidencia'       => ['Cargar evidencia fotográfica de la intervención.', 'tecnico'],
        'marcar_solucionado'     => ['Marcar un reporte como solucionado.', 'tecnico'],
        'comentar_interno'       => ['Participar en el hilo interno de un reporte.', 'comentarios'],
        'ver_dashboard'          => ['Ver el tablero de indicadores.', 'analitica'],
        'exportar_informes'      => ['Exportar informes y listados.', 'analitica'],
        'ver_auditoria'          => ['Consultar el registro de auditoría.', 'analitica'],
        'gestionar_usuarios'     => ['Crear, editar y desactivar usuarios de la institución.', 'admin'],
        'gestionar_roles'        => ['Consultar y ajustar la matriz de roles y permisos.', 'admin'],
        'gestionar_categorias'   => ['Administrar categorías y subcategorías.', 'admin'],
        'gestionar_areas'        => ['Administrar sedes, áreas y subáreas.', 'admin'],
        'gestionar_sla'          => ['Configurar tiempos de atención (SLA).', 'admin'],
        'gestionar_plantillas'   => ['Administrar plantillas de solución.', 'admin'],
        'configurar_institucion' => ['Configurar los datos de la institución.', 'admin'],
        'gestionar_instituciones'=> ['Administrar todas las instituciones (multitenant).', 'superadmin'],
    ],

    // [id_rol => [codigos...]]
    'roles' => [
        ROL_REPORTANTE => [
            'crear_reporte', 'ver_propio_reporte',
        ],
        ROL_TECNICO => [
            'crear_reporte', 'ver_propio_reporte',
            'registrar_informe', 'cargar_evidencia', 'marcar_solucionado',
            'comentar_interno',
        ],
        ROL_GESTOR => [
            'crear_reporte', 'ver_propio_reporte', 'ver_todos_reportes',
            'clasificar_reporte', 'asignar_tecnico', 'fusionar_reportes',
            'validar_cerrar', 'devolver_reporte', 'anular_reporte',
            'comentar_interno',
            'ver_dashboard', 'exportar_informes',
            'gestionar_categorias', 'gestionar_areas', 'gestionar_sla', 'ver_auditoria',
        ],
        ROL_RECTOR => [
            'crear_reporte', 'ver_propio_reporte', 'ver_todos_reportes',
            'clasificar_reporte', 'asignar_tecnico', 'fusionar_reportes',
            'validar_cerrar', 'devolver_reporte', 'anular_reporte',
            'comentar_interno',
            'ver_dashboard', 'exportar_informes',
            'gestionar_categorias', 'gestionar_areas', 'gestionar_sla',
            'gestionar_plantillas', 'gestionar_roles', 'ver_auditoria', 'configurar_institucion',
        ],
        // Además de configurar la plataforma, el Admin de Institución opera los
        // tickets (asignar, validar, devolver, anular): en instituciones pequeñas
        // suele ser la misma persona que gestiona.
        ROL_ADMIN => [
            'crear_reporte', 'ver_propio_reporte', 'ver_todos_reportes',
            'clasificar_reporte', 'asignar_tecnico', 'fusionar_reportes',
            'validar_cerrar', 'devolver_reporte', 'anular_reporte',
            'comentar_interno',
            'ver_dashboard', 'exportar_informes',
            'gestionar_usuarios', 'gestionar_roles', 'gestionar_categorias', 'gestionar_areas',
            'gestionar_sla', 'gestionar_plantillas', 'ver_auditoria', 'configurar_institucion',
        ],
    ],
];
