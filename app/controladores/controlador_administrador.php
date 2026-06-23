<?php
// Controlador Administrador (Admin panel para managers/rectors)
// Gestión de SLA, usuarios, roles, configuración

class ControladorAdministrador {
    private $auth;
    private $autorizacion;
    private $modelo_usuario;
    private $modelo_sla;
    private $modelo_categoria;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_usuario.php';
        require_once APP_PATH . '/modelos/modelo_sla.php';
        require_once APP_PATH . '/modelos/modelo_categoria.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_usuario = new ModeloUsuario();
        $this->modelo_sla = new ModeloSLA();
        $this->modelo_categoria = new ModeloCategoria();
    }

    /**
     * Admin panel principal
     */
    public function inicio() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_CONFIGURAR_INSTITUCION);

        $id_institucion = $this->auth->obtener_id_institucion();

        $datos = [
            'titulo' => 'Admin Panel - SIRGDI',
            'id_institucion' => $id_institucion,
        ];

        $this->renderizar_vista('admin/vista_admin_inicio', $datos);
    }

    /**
     * Gestionar configuración de SLA
     */
    public function gestionar_sla() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->gestionar_sla_form();
        } else {
            return $this->procesar_sla();
        }
    }

    private function gestionar_sla_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_CONFIGURAR_INSTITUCION);

        $id_institucion = $this->auth->obtener_id_institucion();
        $slas = $this->modelo_sla->listar_por_institucion($id_institucion);
        $categorias = $this->modelo_categoria->listar_por_institucion($id_institucion);

        // Mapa de id_urgencia => nombre (para mostrar nombres en vez de IDs)
        $urgencias = [
            URGENCIA_NO_URGENTE  => 'No urgente',
            URGENCIA_MODERADO    => 'Moderado',
            URGENCIA_IMPORTANTE  => 'Importante',
            URGENCIA_URGENTE     => 'Urgente',
        ];

        // Mapa de id_categoria => nombre (para mostrar nombres en la lista)
        $mapa_categorias = [];
        foreach ($categorias as $cat) {
            $mapa_categorias[$cat['id_categoria']] = $cat['nombre'];
        }

        $datos = [
            'titulo' => 'Gestionar SLA - SIRGDI',
            'slas' => $slas,
            'categorias' => $categorias,
            'urgencias' => $urgencias,
            'mapa_categorias' => $mapa_categorias,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('admin/vista_gestionar_sla', $datos);
    }

    private function procesar_sla() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_CONFIGURAR_INSTITUCION);

        $id_institucion = $this->auth->obtener_id_institucion();
        $accion = $_POST['accion'] ?? 'crear';
        $tiempo_respuesta = intval($_POST['tiempo_respuesta_horas'] ?? 0);
        $tiempo_resolucion = intval($_POST['tiempo_resolucion_horas'] ?? 0);
        $id_categoria = intval($_POST['id_categoria'] ?? 0);
        $id_urgencia = intval($_POST['id_urgencia'] ?? 0);

        try {
            // Validaciones solo para crear/editar (NO para eliminar)
            if ($accion === 'crear' || $accion === 'editar' || $accion === 'actualizar') {
                if (!$id_urgencia || $id_urgencia < 1) {
                    throw new Exception('Debes seleccionar un nivel de urgencia.');
                }
                if (!$tiempo_respuesta || $tiempo_respuesta < 1) {
                    throw new Exception('El tiempo de respuesta debe ser de al menos 1 hora.');
                }
                if (!$tiempo_resolucion || $tiempo_resolucion < 1) {
                    throw new Exception('El tiempo de resolución debe ser de al menos 1 hora.');
                }
                if ($tiempo_resolucion < $tiempo_respuesta) {
                    throw new Exception('El tiempo de resolución no puede ser menor al de respuesta.');
                }
            }

            if ($accion === 'crear') {
                $this->modelo_sla->crear([
                    'id_institucion' => $id_institucion,
                    'id_categoria' => $id_categoria ?: null,
                    'id_urgencia' => $id_urgencia ?: null,
                    'tiempo_respuesta_horas' => $tiempo_respuesta,
                    'tiempo_resolucion_horas' => $tiempo_resolucion,
                ]);
                $mensaje = 'SLA creado correctamente.';
            } elseif ($accion === 'editar' || $accion === 'actualizar') {
                $id_sla = intval($_POST['id_sla'] ?? 0);
                if (!$id_sla) {
                    throw new Exception('ID de SLA requerido.');
                }
                $this->modelo_sla->actualizar($id_sla, $id_institucion, [
                    'id_categoria' => $id_categoria ?: null,
                    'id_urgencia' => $id_urgencia ?: null,
                    'tiempo_respuesta_horas' => $tiempo_respuesta,
                    'tiempo_resolucion_horas' => $tiempo_resolucion,
                ]);
                $mensaje = 'SLA actualizado correctamente.';
            } elseif ($accion === 'eliminar') {
                $id_sla = intval($_POST['id_sla'] ?? 0);
                if (!$id_sla) {
                    throw new Exception('ID de SLA requerido.');
                }
                $this->modelo_sla->eliminar($id_sla, $id_institucion);
                $mensaje = 'SLA eliminado correctamente.';
            } else {
                throw new Exception('Acción no válida.');
            }

            header('Location: ' . config('app.url_base') . '/?controlador=administrador&accion=gestionar_sla&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=administrador&accion=gestionar_sla&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Gestionar usuarios
     */
    public function gestionar_usuarios() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->gestionar_usuarios_form();
        } else {
            return $this->procesar_usuario();
        }
    }

    private function gestionar_usuarios_form() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_USUARIOS);

        $id_institucion = $this->auth->obtener_id_institucion();

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();

        // El superadministrador ve los usuarios de TODAS las instituciones
        $es_superadmin = in_array('gestionar_instituciones', $_SESSION['permisos'] ?? []);
        if ($es_superadmin) {
            $usuarios = $this->modelo_usuario->listar_todos();
            $instituciones = $bd->obtener_todos('SELECT id_institucion, nombre FROM institucion WHERE es_activa = 1 ORDER BY nombre', []);
        } else {
            $usuarios = $this->modelo_usuario->listar_por_institucion($id_institucion);
            $instituciones = [];
        }

        // Obtener roles disponibles
        $roles = $bd->obtener_todos('SELECT id_rol, nombre_rol FROM rol ORDER BY id_rol', []);

        $exito = isset($_GET['exito']) ? '✅ Usuario procesado correctamente.' : '';
        $error = isset($_GET['error']) ? $_GET['error'] : '';

        // Credenciales del usuario recién creado (flash de un solo uso)
        $credenciales = $_SESSION['credenciales_nuevo_usuario'] ?? null;
        unset($_SESSION['credenciales_nuevo_usuario']);

        // Datos previos del formulario tras un error de validación (flash de un solo uso)
        $form_old = $_SESSION['form_usuario_old'] ?? null;
        $form_campo_error = $_SESSION['form_usuario_campo'] ?? '';
        unset($_SESSION['form_usuario_old'], $_SESSION['form_usuario_campo']);

        $datos = [
            'titulo' => 'Gestionar Usuarios - SIRGDI',
            'usuarios' => $usuarios,
            'roles' => $roles,
            'csrf_token' => Validacion::generar_csrf_token(),
            'exito' => $exito,
            'error' => $error,
            'credenciales' => $credenciales,
            'form_old' => $form_old,
            'form_campo_error' => $form_campo_error,
            'es_superadmin' => $es_superadmin,
            'instituciones' => $instituciones,
        ];

        $this->renderizar_vista('admin/vista_gestionar_usuarios', $datos);
    }

    private function procesar_usuario() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_USUARIOS);

        $id_institucion = $this->auth->obtener_id_institucion();
        $accion = $_POST['accion'] ?? '';

        // El superadministrador opera sobre cualquier institución:
        // al crear usa la institución elegida en el formulario; al editar/eliminar,
        // la institución real del usuario objetivo.
        $es_superadmin = in_array('gestionar_instituciones', $_SESSION['permisos'] ?? []);
        if ($es_superadmin) {
            require_once LIB_PATH . '/basedatos.php';
            $bd_inst = BaseDatos::obtener();
            if ($accion === 'crear') {
                $sel = intval($_POST['id_institucion_objetivo'] ?? 0);
                if ($sel > 0) {
                    $id_institucion = $sel;
                }
            } else {
                $uid = intval($_POST['id_usuario'] ?? 0);
                if ($uid > 0) {
                    $fila = $bd_inst->obtener_uno('SELECT id_institucion FROM usuario WHERE id_usuario = :id', [':id' => $uid]);
                    if ($fila) {
                        $id_institucion = intval($fila['id_institucion']);
                    }
                }
            }
        }

        $correo = Validacion::sanitizar_email($_POST['correo_electronico'] ?? '');
        $nombre = Validacion::sanitizar_texto($_POST['nombre'] ?? Validacion::sanitizar_texto($_POST['nombre_completo'] ?? ''));
        $cargo = Validacion::sanitizar_texto($_POST['cargo_descripcion'] ?? '');
        $documento = trim($_POST['numero_documento'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $id_rol = intval($_POST['id_rol'] ?? 0);

        $campo_error = ''; // qué campo provocó el error (para resaltarlo)

        try {
            // Validaciones solo para crear/editar (NO para eliminar)
            if ($accion === 'crear' || $accion === 'editar' || $accion === 'actualizar') {
                if (!Validacion::validar_email($correo)) {
                    $campo_error = 'correo_electronico';
                    throw new Exception('Email inválido.');
                }

                if (strlen($nombre) < 3) {
                    $campo_error = 'nombre';
                    throw new Exception('Nombre debe tener al menos 3 caracteres.');
                }

                if (!preg_match('/^[0-9]{5,20}$/', $documento)) {
                    $campo_error = 'numero_documento';
                    throw new Exception('El número de documento debe contener solo números (5 a 20 dígitos).');
                }

                if ($id_rol <= 0) {
                    $campo_error = 'id_rol';
                    throw new Exception('Debes seleccionar un rol.');
                }

                // Unicidad GLOBAL de correo y documento (en todo el sistema, no solo en la institución)
                $id_excluir = ($accion === 'editar' || $accion === 'actualizar') ? intval($_POST['id_usuario'] ?? 0) : 0;
                require_once LIB_PATH . '/basedatos.php';
                $bd = BaseDatos::obtener();

                $dup_correo = $bd->obtener_uno(
                    'SELECT id_usuario FROM usuario WHERE correo_electronico = :correo AND id_usuario <> :excluir',
                    [':correo' => $correo, ':excluir' => $id_excluir]
                );
                if ($dup_correo) {
                    $campo_error = 'correo_electronico';
                    throw new Exception('Ya existe otro usuario con ese correo electrónico.');
                }

                $dup_doc = $bd->obtener_uno(
                    'SELECT id_usuario FROM usuario WHERE numero_documento = :doc AND id_usuario <> :excluir',
                    [':doc' => $documento, ':excluir' => $id_excluir]
                );
                if ($dup_doc) {
                    $campo_error = 'numero_documento';
                    throw new Exception('Ya existe otro usuario con ese número de documento.');
                }
            }

            if ($accion === 'crear') {
                // Contraseña: usar la indicada por el admin, o generar una temporal
                if ($contrasena !== '') {
                    if (strlen($contrasena) < 6) {
                        $campo_error = 'contrasena';
                        throw new Exception('La contraseña debe tener al menos 6 caracteres.');
                    }
                    $contrasena_temporal = $contrasena;
                } else {
                    $contrasena_temporal = bin2hex(random_bytes(5)); // 10 caracteres
                }

                $id_usuario = $this->modelo_usuario->crear([
                    'id_institucion' => $id_institucion,
                    'nombre_completo' => $nombre,
                    'numero_documento' => $documento,
                    'correo_electronico' => $correo,
                    'cargo_descripcion' => $cargo,
                    'hash_contrasena' => password_hash($contrasena_temporal, PASSWORD_BCRYPT),
                    'activo' => 1,
                ]);

                // Guardar credenciales para mostrarlas una vez al admin (flash)
                $_SESSION['credenciales_nuevo_usuario'] = [
                    'email' => $correo,
                    'password' => $contrasena_temporal,
                ];

                // Asignar rol al usuario
                require_once LIB_PATH . '/basedatos.php';
                $bd = BaseDatos::obtener();
                $bd->insertar('usuario_rol', [
                    'id_usuario' => $id_usuario,
                    'id_rol' => $id_rol,
                    'id_institucion' => $id_institucion,
                ]);

                $mensaje = 'Usuario creado. Contraseña temporal: ' . $contrasena_temporal . ' (Debe cambiarla al ingresar)';
                // TODO: Enviar email con credenciales

            } elseif ($accion === 'editar' || $accion === 'actualizar') {
                $id_usuario = intval($_POST['id_usuario'] ?? 0);
                if (!$id_usuario) {
                    throw new Exception('ID de usuario requerido.');
                }

                $datos_actualizar = [
                    'nombre_completo' => $nombre,
                    'numero_documento' => $documento,
                    'correo_electronico' => $correo,
                    'cargo_descripcion' => $cargo,
                ];

                // Si el admin escribió una nueva contraseña, actualizarla
                if ($contrasena !== '') {
                    if (strlen($contrasena) < 6) {
                        $campo_error = 'contrasena';
                        throw new Exception('La contraseña debe tener al menos 6 caracteres.');
                    }
                    $datos_actualizar['hash_contrasena'] = password_hash($contrasena, PASSWORD_BCRYPT);

                    // Mostrar la nueva credencial al admin (flash de un solo uso)
                    $_SESSION['credenciales_nuevo_usuario'] = [
                        'email' => $correo,
                        'password' => $contrasena,
                    ];
                }

                $this->modelo_usuario->actualizar($id_usuario, $id_institucion, $datos_actualizar);

                // Actualizar rol del usuario
                require_once LIB_PATH . '/basedatos.php';
                $bd = BaseDatos::obtener();
                $bd->eliminar('usuario_rol', 'id_usuario = :id_usuario AND id_institucion = :id_institucion', [
                    ':id_usuario' => $id_usuario,
                    ':id_institucion' => $id_institucion,
                ]);
                $bd->insertar('usuario_rol', [
                    'id_usuario' => $id_usuario,
                    'id_rol' => $id_rol,
                    'id_institucion' => $id_institucion,
                ]);

                $mensaje = 'Usuario actualizado correctamente.';

            } elseif ($accion === 'eliminar') {
                $id_usuario = intval($_POST['id_usuario'] ?? 0);
                if (!$id_usuario) {
                    throw new Exception('ID de usuario requerido.');
                }

                // Verificar que el usuario existe y pertenece a la institución
                $usuario = $this->modelo_usuario->obtener_por_id($id_usuario, $id_institucion);
                if (!$usuario) {
                    throw new Exception('Usuario no encontrado.');
                }

                // No eliminar tu propio usuario
                if ($usuario['id_usuario'] == $this->auth->obtener_id_usuario()) {
                    throw new Exception('No puedes eliminar tu propio usuario.');
                }

                require_once LIB_PATH . '/basedatos.php';
                $bd = BaseDatos::obtener();

                // Eliminar primero los roles asociados (FK)
                $bd->eliminar('usuario_rol', 'id_usuario = :id_usuario AND id_institucion = :id_institucion', [
                    ':id_usuario' => $id_usuario,
                    ':id_institucion' => $id_institucion,
                ]);

                // Eliminar el usuario
                $bd->eliminar('usuario', 'id_usuario = :id_usuario AND id_institucion = :id_institucion', [
                    ':id_usuario' => $id_usuario,
                    ':id_institucion' => $id_institucion,
                ]);

                $mensaje = 'Usuario eliminado correctamente.';
            }

            header('Location: ' . config('app.url_base') . '/?controlador=administrador&accion=gestionar_usuarios&exito=1');
            exit;

        } catch (Exception $e) {
            // Preservar lo ingresado para repoblar el formulario (solo crear/editar)
            if (in_array($accion, ['crear', 'editar', 'actualizar'])) {
                // Si el campo no se detectó arriba, inferirlo del mensaje (ej. email duplicado desde el modelo)
                if ($campo_error === '') {
                    $msg = mb_strtolower($e->getMessage());
                    if (strpos($msg, 'email') !== false || strpos($msg, 'correo') !== false) {
                        $campo_error = 'correo_electronico';
                    } elseif (strpos($msg, 'documento') !== false) {
                        $campo_error = 'numero_documento';
                    } elseif (strpos($msg, 'contraseña') !== false || strpos($msg, 'contrasena') !== false) {
                        $campo_error = 'contrasena';
                    } elseif (strpos($msg, 'rol') !== false) {
                        $campo_error = 'id_rol';
                    } elseif (strpos($msg, 'nombre') !== false) {
                        $campo_error = 'nombre';
                    }
                }

                $_SESSION['form_usuario_old'] = [
                    'accion' => $accion,
                    'id_usuario' => intval($_POST['id_usuario'] ?? 0),
                    'nombre' => $nombre,
                    'numero_documento' => $documento,
                    'correo_electronico' => $correo,
                    'cargo_descripcion' => $cargo,
                    'id_rol' => $id_rol,
                ];
                $_SESSION['form_usuario_campo'] = $campo_error;
            }

            header('Location: ' . config('app.url_base') . '/?controlador=administrador&accion=gestionar_usuarios&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Gestionar roles y permisos
     */
    public function gestionar_roles() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_ROLES);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Obtener matriz de permisos (desde DB)
        $sql = 'SELECT DISTINCT r.id_rol, r.nombre_rol
                FROM rol r
                ORDER BY r.id_rol';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $roles = $bd->obtener_todos($sql, []);

        // Obtener permisos
        $sql_permisos = 'SELECT * FROM permiso ORDER BY codigo';
        $permisos = $bd->obtener_todos($sql_permisos, []);

        $datos = [
            'titulo' => 'Gestionar Roles y Permisos - SIRGDI',
            'roles' => $roles,
            'permisos' => $permisos,
        ];

        $this->renderizar_vista('admin/vista_gestionar_roles', $datos);
    }

    /**
     * Obtener matriz de permisos por rol (AJAX)
     */
    public function obtener_matriz_permisos_json() {
        $this->auth->requerir_autenticacion();

        $id_rol = intval($_GET['id_rol'] ?? 0);

        if (!$id_rol) {
            http_response_code(HTTP_BAD_REQUEST);
            die('ID de rol requerido.');
        }

        $sql = 'SELECT DISTINCT p.id_permiso, p.codigo
                FROM rol_permiso rp
                JOIN permiso p ON rp.id_permiso = p.id_permiso
                WHERE rp.id_rol = :id_rol';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $permisos = $bd->obtener_todos($sql, [':id_rol' => $id_rol]);

        header('Content-Type: application/json');
        echo json_encode($permisos);
    }

    // ===== HELPERS =====

    private function renderizar_vista($vista, $datos = []) {
        extract($datos);
        $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';

        if (!file_exists($archivo_vista)) {
            die('Vista no encontrada: ' . $archivo_vista);
        }

        ob_start();
        ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo ?? 'SIRGDI'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo config("app.url_base"); ?>/css/estilos_formularios_modernos.css">
    <link rel="stylesheet" href="<?php echo config("app.url_base"); ?>/css/estilos_profesionales.css">
    <link rel="stylesheet" href="<?php echo config('app.url_base'); ?>/css/estilos_base.css">
    <link rel="stylesheet" href="<?php echo config('app.url_base'); ?>/css/estilos_toasts.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_header.php'; endif; ?>
    <main class="main-content">
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo config('app.url_base'); ?>/js/script_base.js"></script>
</body>
</html><?php
        echo ob_get_clean();
    }
}
