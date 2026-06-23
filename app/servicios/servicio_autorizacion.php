<?php
// Servicio de Autorización (Tier 2-3 - RBAC enforcement)
// Valida permisos antes de permitir acciones
// Matriz de permisos: 23 permisos × 6 roles (definida en BD)

class ServicioAutorizacion {
    private $bd;
    private $id_usuario;
    private $id_institucion;
    private $permisos_cache = [];

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

        // Query: verificar si usuario tiene permiso a través de sus roles
        $sql = 'SELECT 1 FROM rol_permiso rp
                INNER JOIN usuario_rol ur ON rp.id_rol = ur.id_rol
                INNER JOIN permiso p ON rp.id_permiso = p.id_permiso
                WHERE ur.id_usuario = :id_usuario
                AND ur.id_institucion = :id_institucion
                AND p.codigo = :nombre_permiso
                LIMIT 1';

        $resultado = $this->bd->obtener_uno($sql, [
            ':id_usuario' => $this->id_usuario,
            ':id_institucion' => $this->id_institucion,
            ':nombre_permiso' => $nombre_permiso,
        ]);

        $tiene_permiso = $resultado !== null;

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

        return $this->bd->existe(
            'usuario_rol',
            'id_usuario = :id_usuario AND id_institucion = :id_institucion AND id_rol = :id_rol',
            [
                ':id_usuario' => $this->id_usuario,
                ':id_institucion' => $this->id_institucion,
                ':id_rol' => $id_rol,
            ]
        );
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
                    INNER JOIN usuario_rol ur ON rp.id_rol = ur.id_rol
                    WHERE ur.id_usuario = :id_usuario
                    AND ur.id_institucion = :id_institucion';

            $resultado = $this->bd->obtener_todos($sql, [
                ':id_usuario' => $this->id_usuario,
                ':id_institucion' => $this->id_institucion,
            ]);
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
                INNER JOIN usuario_rol ur ON r.id_rol = ur.id_rol
                WHERE ur.id_usuario = :id_usuario
                AND ur.id_institucion = :id_institucion';

        return $this->bd->obtener_todos($sql, [
            ':id_usuario' => $this->id_usuario,
            ':id_institucion' => $this->id_institucion,
        ]);
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
            die(ERROR_ACCESO_DENEGADO);
        }
    }

    /**
     * Requerir alguno de los permisos (redirigir si no tiene ninguno)
     */
    public function requerir_alguno_permiso(...$permisos) {
        if (!$this->verificar_alguno_permiso(...$permisos)) {
            http_response_code(HTTP_FORBIDDEN);
            $this->registrar_acceso_denegado(implode(',', $permisos));
            die(ERROR_ACCESO_DENEGADO);
        }
    }

    /**
     * Requerir rol específico
     */
    public function requerir_rol($id_rol) {
        if (!$this->tiene_rol($id_rol)) {
            http_response_code(HTTP_FORBIDDEN);
            $this->registrar_acceso_denegado("rol:$id_rol");
            die(ERROR_ACCESO_DENEGADO);
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
            die(ERROR_INSTITUCION_MISMATCH);
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
    }

    /**
     * Limpiar caché de permisos (útil después de actualizar roles)
     */
    public function limpiar_cache() {
        $this->permisos_cache = [];
    }
}
