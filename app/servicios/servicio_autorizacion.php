<?php
// Servicio de Autorización (Tier 2-3 - RBAC enforcement)
// Valida permisos antes de permitir acciones
// Matriz de permisos: 23 permisos × 6 roles (definida en BD)

// Las denegaciones terminan la petición con una página de error, no con
// texto plano. Se incluye aquí y no solo desde el enrutador porque este
// servicio también lo cargan los scripts de mantenimiento.
require_once LIB_PATH . '/errores.php';

class ServicioAutorizacion {
    private $bd;
    private $id_usuario;
    private $id_institucion;
    private $permisos_cache = [];

    /**
     * Roles que el usuario tiene EN la institución de trabajo.
     *
     * Son dos fuentes:
     *
     *  1. Los roles asignados en esa institución (usuario_rol).
     *  2. El rol Técnico, cuando es un técnico de una empresa de mantenimiento
     *     con un vínculo activo a esa institución (tecnico_institucion).
     *
     * La segunda existía a medias: el superadministrador podía vincular un
     * técnico a varios colegios y el técnico los elegía al entrar, pero nada
     * de lo que venía después reconocía el vínculo y en cada colegio tenía
     * cero permisos. No se resolvió creando un usuario_rol por colegio porque
     * la clave única de esa tabla es (id_usuario, id_rol): un usuario solo
     * puede tener el rol Técnico en una institución.
     *
     * Un vínculo solo puede dar el rol Técnico, y solo a quien ya es técnico
     * en su propia empresa. Nunca Gestor ni Admin: vincular no puede servir
     * para escalar privilegios.
     *
     * Las marcas llevan nombre distinto en cada rama porque, con las
     * sentencias preparadas nativas, PDO no admite repetir un mismo nombre.
     */
    private const SQL_ROLES_EFECTIVOS =
        'SELECT ur.id_rol FROM usuario_rol ur
          WHERE ur.id_usuario = :ru_propio AND ur.id_institucion = :ri_propio
         UNION
         SELECT ur.id_rol FROM tecnico_institucion ti
           JOIN usuario u      ON u.id_usuario = ti.id_usuario
           JOIN institucion e  ON e.id_institucion = u.id_institucion
                              AND e.tipo = \'empresa_mantenimiento\'
           JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                              AND ur.id_institucion = u.id_institucion
                              AND ur.id_rol = ' . ROL_TECNICO . '
          WHERE ti.id_usuario = :ru_vinculo AND ti.id_institucion = :ri_vinculo
            AND ti.activo = 1';

    private function parametros_roles(): array {
        return [
            ':ru_propio'  => $this->id_usuario,
            ':ri_propio'  => $this->id_institucion,
            ':ru_vinculo' => $this->id_usuario,
            ':ri_vinculo' => $this->id_institucion,
        ];
    }

    public function __construct($id_usuario = null, $id_institucion = null) {
        $this->bd = BaseDatos::obtener();

        // Usar sesión si no se proporcionan parámetros
        if ($id_usuario === null) {
            $this->id_usuario = $_SESSION['id_usuario'] ?? null;
        } else {
            $this->id_usuario = $id_usuario;
        }

        if ($id_institucion === null) {
            $this->id_institucion = $_SESSION['id_institucion'] ?? null;
        } else {
            $this->id_institucion = $id_institucion;
        }
    }

    /**
     * Verificar si usuario tiene un permiso específico
     * Retorna true si tiene permiso, false si no
     */
    public function verificar_permiso($nombre_permiso) {
        if (!$this->id_usuario || !$this->id_institucion) {
            return false;
        }

        // Verificar caché primero
        $cache_key = $this->id_usuario . ':' . $nombre_permiso;
        if (isset($this->permisos_cache[$cache_key])) {
            return $this->permisos_cache[$cache_key];
        }

        // El superadmin tiene acceso a todo (igual que en obtener_permisos): así no
        // queda bloqueado aunque un permiso nuevo aún no exista en la tabla.
        if ($this->es_superadmin()) {
            $this->permisos_cache[$cache_key] = true;
            return true;
        }

        // Query: verificar si usuario tiene permiso a través de sus roles
        $sql = 'SELECT 1 FROM rol_permiso rp
                INNER JOIN permiso p ON rp.id_permiso = p.id_permiso
                WHERE p.codigo = :nombre_permiso
                AND rp.id_rol IN (' . self::SQL_ROLES_EFECTIVOS . ')
                LIMIT 1';

        $resultado = $this->bd->obtener_uno($sql, $this->parametros_roles() + [
            ':nombre_permiso' => $nombre_permiso,
        ]);

        // fetch() devuelve false (no null) cuando no hay fila. Comparar con !== null
        // hacía que todo usuario autenticado pasara cualquier comprobación de permiso.
        $tiene_permiso = !empty($resultado);

        // Cachear resultado
        $this->permisos_cache[$cache_key] = $tiene_permiso;

        return $tiene_permiso;
    }

    /**
     * Verificar múltiples permisos (alguno de ellos)
     * Retorna true si tiene AL MENOS UNO de los permisos
     */
    public function verificar_alguno_permiso(...$permisos) {
        foreach ($permisos as $permiso) {
            if ($this->verificar_permiso($permiso)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar múltiples permisos (todos)
     * Retorna true si tiene TODOS los permisos
     */
    public function verificar_todos_permisos(...$permisos) {
        foreach ($permisos as $permiso) {
            if (!$this->verificar_permiso($permiso)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verificar si usuario tiene un rol específico
     */
    public function tiene_rol($id_rol) {
        if (!$this->id_usuario || !$this->id_institucion) {
            return false;
        }

        $sql = 'SELECT 1 FROM (' . self::SQL_ROLES_EFECTIVOS . ') roles
                WHERE roles.id_rol = :id_rol LIMIT 1';

        return (bool) $this->bd->obtener_uno($sql, $this->parametros_roles() + [
            ':id_rol' => $id_rol,
        ]);
    }

    /**
     * Verificar si usuario tiene alguno de los roles
     */
    public function tiene_alguno_rol(...$ids_rol) {
        foreach ($ids_rol as $id_rol) {
            if ($this->tiene_rol($id_rol)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener lista de permisos del usuario (para mostrar en UI)
     */
    public function obtener_permisos() {
        if (!$this->id_usuario) {
            return [];
        }

        // Verificar si es Superadmin (rol 6)
        $sql_check_superadmin = 'SELECT id_rol FROM usuario_rol WHERE id_usuario = :id_usuario AND id_rol = 6';
        $es_superadmin = $this->bd->obtener_uno($sql_check_superadmin, [':id_usuario' => $this->id_usuario]);

        if ($es_superadmin) {
            // Superadmin obtiene TODOS los permisos
            $sql = 'SELECT DISTINCT p.codigo FROM permiso p';
            $resultado = $this->bd->obtener_todos($sql);
        } else {
            // Para usuarios de institución específica
            $sql = 'SELECT DISTINCT p.codigo FROM permiso p
                    INNER JOIN rol_permiso rp ON p.id_permiso = rp.id_permiso
                    WHERE rp.id_rol IN (' . self::SQL_ROLES_EFECTIVOS . ')';

            $resultado = $this->bd->obtener_todos($sql, $this->parametros_roles());
        }

        return array_map(fn($row) => $row['codigo'], $resultado);
    }

    /**
     * Obtener lista de roles del usuario
     */
    public function obtener_roles() {
        if (!$this->id_usuario || !$this->id_institucion) {
            return [];
        }

        $sql = 'SELECT r.* FROM rol r
                WHERE r.id_rol IN (' . self::SQL_ROLES_EFECTIVOS . ')';

        return $this->bd->obtener_todos($sql, $this->parametros_roles());
    }

    /**
     * Verificar si usuario es SUPERADMIN
     * El superadmin tiene acceso a TODO
     */
    public function es_superadmin() {
        return $this->tiene_rol(ROL_SUPERADMIN);
    }

    /**
     * Verificar si usuario es ADMIN de la institución
     */
    public function es_admin() {
        return $this->tiene_rol(ROL_ADMIN);
    }

    /**
     * Requerir permiso específico (redirigir si no tiene)
     */
    public function requerir_permiso($nombre_permiso) {
        if (!$this->verificar_permiso($nombre_permiso)) {
            http_response_code(HTTP_FORBIDDEN);
            $this->registrar_acceso_denegado($nombre_permiso);
            responder_prohibido();
        }
    }

    /**
     * Requerir alguno de los permisos (redirigir si no tiene ninguno)
     */
    public function requerir_alguno_permiso(...$permisos) {
        if (!$this->verificar_alguno_permiso(...$permisos)) {
            http_response_code(HTTP_FORBIDDEN);
            $this->registrar_acceso_denegado(implode(',', $permisos));
            responder_prohibido();
        }
    }

    /**
     * Requerir rol específico
     */
    public function requerir_rol($id_rol) {
        if (!$this->tiene_rol($id_rol)) {
            http_response_code(HTTP_FORBIDDEN);
            $this->registrar_acceso_denegado("rol:$id_rol");
            responder_prohibido();
        }
    }

    /**
     * Validar acceso a recurso de otra institución (RN-01 - multitenant isolation)
     * Se usa cuando se accede a un recurso que tiene id_institucion
     */
    public function validar_institucion($id_institucion_recurso) {
        if ($id_institucion_recurso != $this->id_institucion) {
            http_response_code(HTTP_FORBIDDEN);
            $this->registrar_acceso_denegado('cross_tenant_access_attempt');
            responder_prohibido('Intento de acceso a datos de otra institución');
        }
    }

    /**
     * Registrar acceso denegado en log de auditoría
     */
    private function registrar_acceso_denegado($recurso) {
        $log_msg = sprintf(
            "[%s] Acceso denegado - Usuario ID: %d, Institución: %d, Recurso: %s, IP: %s\n",
            date('Y-m-d H:i:s'),
            $this->id_usuario ?? 'desconocido',
            $this->id_institucion ?? 'desconocida',
            $recurso,
            $_SERVER['REMOTE_ADDR'] ?? 'desconocida'
        );

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents(AUDIT_LOG, $log_msg, FILE_APPEND);

        if (class_exists('ServicioAuditoria')) {
            ServicioAuditoria::registrar('acceso_denegado', 'seguridad', null, null, ['recurso' => $recurso],
                ['id_usuario' => $this->id_usuario, 'id_institucion' => $this->id_institucion]);
        }
    }

    /**
     * Limpiar caché de permisos (útil después de actualizar roles)
     */
    public function limpiar_cache() {
        $this->permisos_cache = [];
    }
}
