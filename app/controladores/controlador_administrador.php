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
            'titulo' => 'Admin Panel - ' . config('app.app_name'),
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

    /**
     * Horario laboral de la institución: con él, el SLA cuenta horas hábiles.
     *
     * Mismo permiso que la pantalla de SLA: el horario es parte de cómo se
     * mide el plazo, y así el menú y la pantalla piden lo mismo.
     */
    public function horario_laboral() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_SLA);
        require_once LIB_PATH . '/horario_laboral.php';

        $id_institucion = $this->auth->obtener_id_institucion();
        $url = config('app.url_base') . '/?controlador=administrador&accion=horario_laboral';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (($_POST['accion'] ?? '') === 'restablecer') {
                    $this->modelo_sla->guardar_horario($id_institucion, null);
                    ServicioAuditoria::registrar('horario_restablecido', 'institucion', $id_institucion);
                    header('Location: ' . $url . '&exito=' . urlencode('Horario por defecto restablecido.'));
                    exit;
                }

                $semana = [];
                for ($d = 1; $d <= 7; $d++) {
                    $dia = $_POST['dia'][$d] ?? [];
                    $semana[(string) $d] = !empty($dia['activo'])
                        ? [trim((string) ($dia['inicio'] ?? '')), trim((string) ($dia['fin'] ?? ''))]
                        : null;
                }

                $error = HorarioLaboral::validar($semana);
                if ($error !== null) {
                    throw new Exception($error);
                }

                $antes = $this->modelo_sla->horario_de_institucion($id_institucion)->a_arreglo();
                $this->modelo_sla->guardar_horario($id_institucion, $semana);
                ServicioAuditoria::registrar('horario_actualizado', 'institucion', $id_institucion, $antes, $semana);

                header('Location: ' . $url . '&exito=' . urlencode('Horario guardado. Los plazos de todos los reportes se calculan ya con él.'));
                exit;
            } catch (Exception $e) {
                header('Location: ' . $url . '&error=' . urlencode($e->getMessage()));
                exit;
            }
        }

        $anio = (int) date('Y');
        $this->renderizar_vista('admin/vista_horario_laboral', [
            'titulo'      => 'Horario laboral - ' . config('app.app_name'),
            'semana'      => $this->modelo_sla->horario_de_institucion($id_institucion)->a_arreglo(),
            'es_propio'   => $this->modelo_sla->tiene_horario_propio($id_institucion),
            'festivos'    => array_merge(FestivosColombia::del_anio($anio), FestivosColombia::del_anio($anio + 1)),
            'csrf_token'  => Validacion::generar_csrf_token(),
        ]);
    }

    private function gestionar_sla_form() {
        $this->auth->requerir_autenticacion();
        // Se exige gestionar_sla, que es el permiso que existe para esto y el
        // que mira el menú. Antes se pedía configurar_institucion: el Gestor
        // tiene el primero pero no el segundo, así que veia la opción
        // "Configurar SLA" en su menú y al pulsarla recibía un 403.
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_SLA);

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
            'titulo' => 'Gestionar SLA - ' . config('app.app_name'),
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
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_SLA);

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

            ServicioAuditoria::registrar('sla_' . $accion, 'sla', intval($_POST['id_sla'] ?? 0) ?: null, null,
                $accion === 'eliminar' ? null : ['id_categoria' => $_POST['id_categoria'] ?? null, 'id_urgencia' => $_POST['id_urgencia'] ?? null,
                    'respuesta_h' => $_POST['tiempo_respuesta_horas'] ?? null, 'resolucion_h' => $_POST['tiempo_resolucion_horas'] ?? null]);
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

        // Obtener roles disponibles (Superadministrador solo es visible/asignable por un Superadministrador real)
        $sql_roles = 'SELECT id_rol, nombre_rol FROM rol'
            . ($es_superadmin ? '' : ' WHERE id_rol <> ' . ROL_SUPERADMIN)
            . ' ORDER BY id_rol';
        $roles = $bd->obtener_todos($sql_roles, []);

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
            'titulo' => 'Gestionar Usuarios - ' . config('app.app_name'),
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

                // RBAC: solo un Superadministrador real puede otorgar el rol de Superadministrador.
                // Evita que un Admin de institución se autoasigne (o asigne a terceros) privilegios globales.
                if ($id_rol === ROL_SUPERADMIN && !$this->autorizacion->es_superadmin()) {
                    $campo_error = 'id_rol';
                    throw new Exception('No tienes permiso para asignar el rol de Superadministrador.');
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
                    if (!Validacion::validar_contrasena($contrasena)) {
                        $campo_error = 'contrasena';
                        throw new Exception(Validacion::POLITICA_CONTRASENA);
                    }
                    $contrasena_temporal = $contrasena;
                } else {
                    // Temporal que cumple la política: el usuario la cambia al
                    // entrar, pero mientras tanto no debe ser más débil que las
                    // que se le exigen a él.
                    $contrasena_temporal = Validacion::generar_contrasena_temporal();
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

                // Asignar rol al usuario
                require_once LIB_PATH . '/basedatos.php';
                $bd = BaseDatos::obtener();
                $bd->insertar('usuario_rol', [
                    'id_usuario' => $id_usuario,
                    'id_rol' => $id_rol,
                    'id_institucion' => $id_institucion,
                ]);

                // Intentar que el propio usuario elija su contraseña desde su
                // correo. Es preferible a dictarle una credencial por WhatsApp:
                // el enlace caduca en una hora y es de un solo uso.
                $correo_enviado = $this->enviar_bienvenida_usuario(
                    $id_usuario, $id_institucion, $correo, $nombre
                );

                if ($correo_enviado) {
                    $mensaje = 'Usuario creado. Se envió a ' . $correo
                        . ' un enlace para que defina su contraseña.';
                } else {
                    // Sin correo (SMTP caído o sin configurar) queda el camino
                    // de siempre: mostrar la credencial una sola vez al admin.
                    // El usuario tendrá que cambiarla al entrar, eso ya se exige.
                    $_SESSION['credenciales_nuevo_usuario'] = [
                        'email' => $correo,
                        'password' => $contrasena_temporal,
                    ];
                    $mensaje = 'Usuario creado, pero no se pudo enviar el correo. '
                        . 'Entregue esta contraseña temporal: ' . $contrasena_temporal
                        . ' — el sistema le exigirá cambiarla al ingresar.';
                }

            } elseif ($accion === 'editar' || $accion === 'actualizar') {
                $id_usuario = intval($_POST['id_usuario'] ?? 0);
                if (!$id_usuario) {
                    throw new Exception('ID de usuario requerido.');
                }

                // Igual que al eliminar: el usuario tiene que ser de esta
                // institución. Sin esto, un id de otra institución pasaba y se
                // le creaba un rol aquí.
                if (!$this->modelo_usuario->obtener_por_id($id_usuario, $id_institucion)) {
                    throw new Exception('Usuario no encontrado.');
                }

                $datos_actualizar = [
                    'nombre_completo' => $nombre,
                    'numero_documento' => $documento,
                    'correo_electronico' => $correo,
                    'cargo_descripcion' => $cargo,
                ];

                // Si el admin escribió una nueva contraseña, actualizarla
                if ($contrasena !== '') {
                    if (!Validacion::validar_contrasena($contrasena)) {
                        $campo_error = 'contrasena';
                        throw new Exception(Validacion::POLITICA_CONTRASENA);
                    }
                    $datos_actualizar['hash_contrasena'] = password_hash($contrasena, PASSWORD_BCRYPT);
                    // La puso un administrador, no el dueño de la cuenta:
                    // se le exige cambiarla en el siguiente ingreso.
                    $datos_actualizar['debe_cambiar_contrasena'] = 1;

                    // Mostrar la nueva credencial al admin (flash de un solo uso)
                    $_SESSION['credenciales_nuevo_usuario'] = [
                        'email' => $correo,
                        'password' => $contrasena,
                    ];
                }

                $this->modelo_usuario->actualizar($id_usuario, $id_institucion, $datos_actualizar);

                // Actualizar rol del usuario
                if (!$this->modelo_usuario->reemplazar_rol($id_usuario, $id_institucion, $id_rol)) {
                    throw new Exception('Usuario no encontrado.');
                }

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

            ServicioAuditoria::registrar('usuario_' . $accion, 'usuario', $id_usuario ?? null,
                ($accion === 'eliminar' && is_array($usuario)) ? ['nombre' => $usuario['nombre_completo'], 'email' => $usuario['correo_electronico']] : null,
                $accion === 'eliminar' ? null : ['nombre' => $_POST['nombre'] ?? null, 'email' => $_POST['correo_electronico'] ?? null, 'id_rol' => $_POST['id_rol'] ?? null]);
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
    /**
     * Matriz de roles × permisos.
     * La tabla rol_permiso es global (afecta a todas las instituciones), así que
     * solo el Superadministrador puede guardar cambios; Admin y Rector la consultan.
     */
    public function gestionar_roles() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_ROLES);

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();

        $roles = $bd->obtener_todos('SELECT id_rol, nombre_rol, descripcion FROM rol ORDER BY id_rol');
        $permisos = $bd->obtener_todos('SELECT id_permiso, codigo, descripcion, modulo FROM permiso ORDER BY modulo, codigo');

        // [id_rol][id_permiso] => true
        $matriz = [];
        foreach ($bd->obtener_todos('SELECT id_rol, id_permiso FROM rol_permiso') as $rp) {
            $matriz[(int)$rp['id_rol']][(int)$rp['id_permiso']] = true;
        }

        $datos = [
            'titulo' => 'Gestionar Roles y Permisos - ' . config('app.app_name'),
            'roles' => $roles,
            'permisos' => $permisos,
            'matriz' => $matriz,
            'puede_editar' => $this->autorizacion->es_superadmin(),
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('admin/vista_gestionar_roles', $datos);
    }

    /**
     * Guarda la matriz completa de permisos de los roles institucionales.
     * El rol Superadministrador no se toca: tiene acceso a todo por código.
     */
    public function guardar_permisos() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=administrador&accion=gestionar_roles');
            exit;
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_ROLES);
        $volver = config('app.url_base') . '/?controlador=administrador&accion=gestionar_roles';

        try {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido.');
            }
            if (!$this->autorizacion->es_superadmin()) {
                throw new Exception('La matriz de permisos es global para todas las instituciones; solo el Superadministrador puede modificarla.');
            }

            require_once LIB_PATH . '/basedatos.php';
            $bd = BaseDatos::obtener();

            $ids_permiso_validos = array_map('intval', array_column($bd->obtener_todos('SELECT id_permiso FROM permiso'), 'id_permiso'));
            $roles_editables = array_map('intval', array_column(
                $bd->obtener_todos('SELECT id_rol FROM rol WHERE id_rol <> :sa', [':sa' => ROL_SUPERADMIN]), 'id_rol'
            ));

            $enviado = $_POST['permisos'] ?? [];
            $antes = []; $despues = [];
            $altas = 0; $bajas = 0;

            foreach ($roles_editables as $id_rol) {
                $actuales = array_map('intval', array_column(
                    $bd->obtener_todos('SELECT id_permiso FROM rol_permiso WHERE id_rol = :r', [':r' => $id_rol]), 'id_permiso'
                ));
                $deseados = array_values(array_intersect(
                    array_map('intval', (array)($enviado[$id_rol] ?? [])),
                    $ids_permiso_validos
                ));
                sort($actuales); sort($deseados);
                $antes[$id_rol] = $actuales;
                $despues[$id_rol] = $deseados;

                foreach (array_diff($deseados, $actuales) as $id_permiso) {
                    $bd->insertar('rol_permiso', ['id_rol' => $id_rol, 'id_permiso' => $id_permiso]);
                    $altas++;
                }
                foreach (array_diff($actuales, $deseados) as $id_permiso) {
                    $bd->eliminar('rol_permiso', 'id_rol = :r AND id_permiso = :p', [':r' => $id_rol, ':p' => $id_permiso]);
                    $bajas++;
                }
            }

            if ($altas || $bajas) {
                ServicioAuditoria::registrar('modificar_matriz_permisos', 'rol_permiso', null, $antes, $despues, ['id_institucion' => null]);
                $_SESSION['exito'] = sprintf('Matriz guardada: %d permiso(s) concedido(s) y %d retirado(s).', $altas, $bajas);
            } else {
                $_SESSION['exito'] = 'No había cambios que guardar.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . $volver);
        exit;
    }

    /**
     * Obtener matriz de permisos por rol (AJAX)
     */
    public function obtener_matriz_permisos_json() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_ROLES);

        $id_rol = intval($_GET['id_rol'] ?? 0);

        if (!$id_rol) {
            responder_peticion_invalida('El enlace no dice qué rol abrir. Vuelve a la lista de roles.');
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

    /**
     * Envía al usuario recién creado un enlace de un solo uso para que defina
     * su propia contraseña. Reutiliza el mecanismo de recuperación, que ya
     * está implementado y probado, en vez de inventar otro token.
     *
     * Devuelve false si no se pudo enviar, para que el llamador use el camino
     * antiguo (mostrar la credencial al administrador) en lugar de dejar al
     * usuario sin forma de entrar.
     */
    private function enviar_bienvenida_usuario($id_usuario, $id_institucion, $correo, $nombre) {
        try {
            require_once APP_PATH . '/servicios/servicio_notificacion.php';

            $token = $this->modelo_usuario->generar_token_reset($id_usuario, $id_institucion);
            $link = config('app.url_base')
                . '/?controlador=autenticacion&accion=restablecer_contrasena&token=' . urlencode($token);

            $institucion = '';
            require_once LIB_PATH . '/basedatos.php';
            $fila = BaseDatos::obtener()->obtener_uno(
                'SELECT nombre FROM institucion WHERE id_institucion = :id',
                [':id' => $id_institucion]
            );
            if ($fila) {
                $institucion = $fila['nombre'];
            }

            $servicio = new ServicioNotificacion();
            return (bool) $servicio->enviar_bienvenida($correo, $nombre, $link, $institucion);

        } catch (Throwable $e) {
            error_log('Bienvenida no enviada a ' . $correo . ': ' . $e->getMessage());
            return false;
        }
    }

    // ===== HELPERS =====

    private function renderizar_vista($vista, $datos = []) {
        // Recoge el &error= / &exito= con que llega la redirección de la acción
        // anterior; sin esto la plantilla de abajo no encuentra nada que mostrar.
        mensajes_de_la_url();

        extract($datos);
        $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';

        if (!file_exists($archivo_vista)) {
            responder_error_interno('Vista no encontrada: ' . $archivo_vista);
        }

        ob_start();
        ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){try{var t=localStorage.getItem('sirgdi_tema');if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches)?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
    <title><?php echo htmlspecialchars($titulo ?? config('app.app_name')); ?></title>
    <link rel="icon" type="image/png" sizes="64x64" href="<?php echo asset_url('img/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset_url('img/apple-touch-icon.png'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_formularios_modernos.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_profesionales.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_base.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/estilos_toasts.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: var(--color-text); background-color: var(--color-bg); transition: background-color .2s ease, color .2s ease; }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_header.php'; endif; ?>
    <main class="main-content">
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo asset_url('js/script_base.js'); ?>"></script>
    <script src="<?php echo asset_url('js/tema.js'); ?>"></script>
    <script src="<?php echo asset_url('js/toast.js'); ?>"></script>
    <?php if (!empty($_SESSION['exito'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        toast.success('¡Éxito!', '<?php echo addslashes(htmlspecialchars($_SESSION['exito'])); ?>', 5000);
    });
    </script>
    <?php unset($_SESSION['exito']); endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        toast.error('Error', '<?php echo addslashes(htmlspecialchars($_SESSION['error'])); ?>', 6000);
    });
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html><?php
        echo ob_get_clean();
    }
}
