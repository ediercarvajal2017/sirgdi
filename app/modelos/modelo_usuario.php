<?php
// Modelo de Usuario (Tier 2-3)

class ModeloUsuario {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener usuario por ID y validar pertenencia a institución (RN-01)
     */
    public function obtener_por_id($id_usuario, $id_institucion) {
        $sql = 'SELECT * FROM usuario
                WHERE id_usuario = :id_usuario
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_usuario' => $id_usuario,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Obtener usuario por email y institución (para login)
     */
    public function obtener_por_email($email, $id_institucion = null) {
        if ($id_institucion) {
            $sql = 'SELECT * FROM usuario
                    WHERE correo_electronico = :email
                    AND id_institucion = :id_institucion
                    LIMIT 1';

            return $this->bd->obtener_uno($sql, [
                ':email' => $email,
                ':id_institucion' => $id_institucion,
            ]);
        } else {
            // Para login inicial (antes de saber institución)
            $sql = 'SELECT * FROM usuario WHERE correo_electronico = :email LIMIT 1';
            return $this->bd->obtener_uno($sql, [':email' => $email]);
        }
    }

    /**
     * Crear usuario nuevo
     */
    public function crear($datos_usuario) {
        // Validaciones básicas
        if (!$datos_usuario['correo_electronico'] || !$datos_usuario['hash_contrasena']) {
            throw new Exception('Email y contraseña son requeridos.');
        }

        // Validar email
        if (!filter_var($datos_usuario['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido.');
        }

        // Verificar que no existe otro usuario con ese email en la institución
        $usuario_existente = $this->obtener_por_email($datos_usuario['correo_electronico'], $datos_usuario['id_institucion']);
        if ($usuario_existente) {
            throw new Exception('El email ya está registrado en esta institución.');
        }

        // Nota: hash_contrasena ya viene hasheada desde el controlador
        // No hacer double-hash (controlador ya hashea con password_hash)

        // Estado por defecto: activo
        if (!isset($datos_usuario['activo'])) {
            $datos_usuario['activo'] = 1;
        }

        // 2FA deshabilitado por defecto
        if (!isset($datos_usuario['requiere_2fa'])) {
            $datos_usuario['requiere_2fa'] = 0;
        }

        // Timestamps
        $datos_usuario['fecha_creacion'] = date('Y-m-d H:i:s');
        $datos_usuario['ultima_actividad'] = date('Y-m-d H:i:s');

        return $this->bd->insertar('usuario', $datos_usuario);
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($id_usuario, $id_institucion, $datos) {
        $where = 'id_usuario = :id_usuario AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_usuario' => $id_usuario,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('usuario', $datos, $where, $parametros_where);
    }

    /**
     * Cambiar contraseña (con validación de actual)
     */
    public function cambiar_contrasena($id_usuario, $id_institucion, $contrasena_actual, $contrasena_nueva) {
        // Obtener usuario
        $usuario = $this->obtener_por_id($id_usuario, $id_institucion);
        if (!$usuario) {
            throw new Exception('Usuario no encontrado.');
        }

        // Validar contraseña actual
        if (!password_verify($contrasena_actual, $usuario['hash_contrasena'])) {
            throw new Exception('Contraseña actual incorrecta.');
        }

        // Hash de nueva contraseña
        $nueva_contrasena_hash = password_hash(
            $contrasena_nueva,
            PASSWORD_BCRYPT,
            ['cost' => 12]
        );

        // Actualizar
        return $this->actualizar($id_usuario, $id_institucion, [
            'hash_contrasena' => $nueva_contrasena_hash,
        ]);
    }

    /**
     * Generar y guardar token de reset de contraseña
     */
    public function generar_token_reset($id_usuario, $id_institucion) {
        $usuario = $this->obtener_por_id($id_usuario, $id_institucion);
        if (!$usuario) {
            throw new Exception('Usuario no encontrado.');
        }

        // Generar token seguro
        $token = bin2hex(random_bytes(32));

        // Usar DATE_ADD(NOW(), INTERVAL 1 HOUR) en MySQL para evitar
        // desfase cuando PHP (America/Bogota) y MySQL (UTC) difieren.
        $this->bd->ejecutar(
            'UPDATE usuario SET token_reset_pass = ?, token_reset_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR)
             WHERE id_usuario = ? AND id_institucion = ?',
            [$token, $id_usuario, $id_institucion]
        );

        return $token;
    }

    /**
     * Validar y usar token de reset
     */
    public function usar_token_reset($token, $contrasena_nueva) {
        $sql = 'SELECT * FROM usuario
                WHERE token_reset_pass = :token
                AND token_reset_expira > NOW()
                LIMIT 1';

        $usuario = $this->bd->obtener_uno($sql, [':token' => $token]);
        if (!$usuario) {
            throw new Exception('Token de reset inválido o expirado.');
        }

        // Hash de nueva contraseña
        $contrasena_hash = password_hash(
            $contrasena_nueva,
            PASSWORD_BCRYPT,
            ['cost' => 12]
        );

        // Actualizar y limpiar token
        return $this->actualizar($usuario['id_usuario'], $usuario['id_institucion'], [
            'hash_contrasena' => $contrasena_hash,
            'token_reset_pass' => null,
            'token_reset_expira' => null,
        ]);
    }

    /**
     * Habilitar 2FA y generar secreto TOTP
     */
    public function habilitar_2fa($id_usuario, $id_institucion) {
        require_once LIB_PATH . '/encriptacion.php';

        $secreto_totp = Encriptacion::generar_secreto_totp();

        // Encriptar secreto antes de guardar
        $enc = new Encriptacion(config('security.encryption_key'));
        $secreto_encriptado = $enc->encriptar($secreto_totp);

        $this->actualizar($id_usuario, $id_institucion, [
            'totp_secret' => $secreto_encriptado,
            'requiere_2fa' => 1,
        ]);

        // Retornar secreto sin encriptar para que el usuario lo escanee con su app
        return $secreto_totp;
    }

    /**
     * Deshabilitar 2FA
     */
    public function deshabilitar_2fa($id_usuario, $id_institucion) {
        $this->actualizar($id_usuario, $id_institucion, [
            'totp_secret' => null,
            'requiere_2fa' => 0,
        ]);

        return true;
    }

    /**
     * Obtener secreto TOTP (desencriptado)
     */
    public function obtener_secreto_totp($id_usuario, $id_institucion) {
        $usuario = $this->obtener_por_id($id_usuario, $id_institucion);
        if (!$usuario || !$usuario['totp_secret']) {
            return null;
        }

        require_once LIB_PATH . '/encriptacion.php';
        $enc = new Encriptacion(config('security.encryption_key'));
        return $enc->desencriptar($usuario['totp_secret']);
    }

    /**
     * Actualizar último acceso (timestamp) para RNF-05 (session timeout)
     */
    public function actualizar_ultima_actividad($id_usuario, $id_institucion) {
        return $this->actualizar($id_usuario, $id_institucion, [
            'ultima_actividad' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtener roles del usuario
     */
    public function obtener_roles($id_usuario, $id_institucion) {
        $sql = 'SELECT r.* FROM rol r
                INNER JOIN usuario_rol ur ON r.id_rol = ur.id_rol
                WHERE ur.id_usuario = :id_usuario
                AND ur.id_institucion = :id_institucion';

        return $this->bd->obtener_todos($sql, [
            ':id_usuario' => $id_usuario,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Asignar rol a usuario
     */
    public function asignar_rol($id_usuario, $id_institucion, $id_rol) {
        // Verificar que no existe duplicado
        if ($this->tiene_rol($id_usuario, $id_institucion, $id_rol)) {
            return false;
        }

        return $this->bd->insertar('usuario_rol', [
            'id_usuario' => $id_usuario,
            'id_institucion' => $id_institucion,
            'id_rol' => $id_rol,
        ]);
    }

    /**
     * Verificar si usuario tiene rol
     */
    public function tiene_rol($id_usuario, $id_institucion, $id_rol) {
        return $this->bd->existe(
            'usuario_rol',
            'id_usuario = :id_usuario AND id_institucion = :id_institucion AND id_rol = :id_rol',
            [
                ':id_usuario' => $id_usuario,
                ':id_institucion' => $id_institucion,
                ':id_rol' => $id_rol,
            ]
        );
    }

    /**
     * Remover rol del usuario
     */
    public function remover_rol($id_usuario, $id_institucion, $id_rol) {
        return $this->bd->eliminar(
            'usuario_rol',
            'id_usuario = :id_usuario AND id_institucion = :id_institucion AND id_rol = :id_rol',
            [
                ':id_usuario' => $id_usuario,
                ':id_institucion' => $id_institucion,
                ':id_rol' => $id_rol,
            ]
        );
    }

    /**
     * Listar usuarios de una institución con su rol principal
     */
    /**
     * Listar TODOS los usuarios del sistema (para el superadministrador),
     * con su rol y el nombre de su institución.
     */
    public function listar_todos($limite = 500, $offset = 0) {
        $sql = 'SELECT
                    u.*,
                    r.nombre_rol,
                    r.id_rol,
                    i.nombre AS institucion_nombre
                FROM usuario u
                LEFT JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario AND ur.id_institucion = u.id_institucion
                LEFT JOIN rol r ON ur.id_rol = r.id_rol
                LEFT JOIN institucion i ON u.id_institucion = i.id_institucion
                ORDER BY i.nombre ASC, u.nombre_completo ASC
                LIMIT :limite OFFSET :offset';

        return $this->bd->obtener_todos($sql, [
            ':limite' => $limite,
            ':offset' => $offset,
        ]);
    }

    public function listar_por_institucion($id_institucion, $limite = 50, $offset = 0) {
        $sql = 'SELECT
                    u.*,
                    r.nombre_rol,
                    r.id_rol
                FROM usuario u
                LEFT JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario AND ur.id_institucion = u.id_institucion
                LEFT JOIN rol r ON ur.id_rol = r.id_rol
                WHERE u.id_institucion = :id_institucion
                ORDER BY u.fecha_creacion DESC
                LIMIT :limite OFFSET :offset';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
            ':limite' => $limite,
            ':offset' => $offset,
        ]);
    }

    /**
     * Activar/desactivar usuario
     */
    public function cambiar_estado($id_usuario, $id_institucion, $activo) {
        return $this->actualizar($id_usuario, $id_institucion, [
            'activo' => $activo ? 1 : 0,
        ]);
    }
}
