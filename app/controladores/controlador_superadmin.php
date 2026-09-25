<?php
// Controlador Superadministrador
// Gestiona todas las instituciones educativas del sistema

class ControladorSuperadmin {
    private $auth;
    private $autorizacion;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
    }

    /**
     * Panel principal del Superadministrador
     */
    public function inicio() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();

        // Listar todas las instituciones
        $sql = 'SELECT * FROM institucion ORDER BY nombre ASC';
        $instituciones = $bd->obtener_todos($sql);

        // Credenciales del admin recién creado (flash de un solo uso)
        $cred_admin = $_SESSION['credenciales_admin_institucion'] ?? null;
        unset($_SESSION['credenciales_admin_institucion']);

        $datos = [
            'titulo' => 'Administración Global - ' . config('app.app_name'),
            'instituciones' => $instituciones,
            'cred_admin' => $cred_admin,
            'preparacion' => $this->evaluar_preparacion($bd),
        ];

        $this->renderizar_vista('superadmin/vista_superadmin_inicio', $datos);
    }

    /**
     * Crear nueva institución
     */
    public function crear_institucion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Repoblar formulario tras un error (flash de un solo uso)
            $form_old = $_SESSION['form_inst_old'] ?? [];
            $form_campo_error = $_SESSION['form_inst_campo'] ?? '';
            unset($_SESSION['form_inst_old'], $_SESSION['form_inst_campo']);

            $datos = [
                'titulo' => 'Crear Institución - ' . config('app.app_name'),
                'csrf_token' => Validacion::generar_csrf_token(),
                'form_old' => $form_old,
                'form_campo_error' => $form_campo_error,
            ];
            $this->renderizar_vista('superadmin/vista_crear_institucion', $datos);
            return;
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $campo_error = ''; // campo que provocó el error (para resaltarlo)

        try {
            // Validar CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                throw new Exception('Token CSRF inválido');
            }

            // Validar datos requeridos
            $nombre = trim($_POST['nombre'] ?? '');
            $codigo_dane = trim($_POST['codigo_dane'] ?? '');

            // Antes no se podía elegir: todo salía educativa, y una empresa de
            // mantenimiento solo se podía crear tocando la base de datos.
            $tipo = $_POST['tipo'] ?? 'educativa';
            if (!in_array($tipo, ['educativa', 'empresa_mantenimiento'], true)) {
                $campo_error = 'tipo';
                throw new Exception('Elige si es una institución educativa o una empresa de mantenimiento.');
            }

            // Datos del primer administrador
            $admin_nombre = trim($_POST['admin_nombre'] ?? '');
            $admin_documento = trim($_POST['admin_documento'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_password = $_POST['admin_password'] ?? '';

            if (empty($nombre) || strlen($nombre) < 3) {
                $campo_error = 'nombre';
                throw new Exception('El nombre debe tener al menos 3 caracteres');
            }

            if (strlen($nombre) > 90) {
                $campo_error = 'nombre';
                throw new Exception('El nombre no debe exceder 90 caracteres');
            }

            // Validar código DANE - entre 5 y 13 dígitos numéricos
            if (!preg_match('/^[0-9]{5,13}$/', $codigo_dane)) {
                $campo_error = 'codigo_dane';
                throw new Exception('El código DANE debe contener entre 5 y 13 dígitos numéricos');
            }

            // Validar datos del administrador
            if (strlen($admin_nombre) < 3) {
                $campo_error = 'admin_nombre';
                throw new Exception('El nombre del administrador debe tener al menos 3 caracteres');
            }
            if (!preg_match('/^[0-9]{5,20}$/', $admin_documento)) {
                $campo_error = 'admin_documento';
                throw new Exception('El documento del administrador debe contener solo números (5 a 20 dígitos)');
            }
            if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $campo_error = 'admin_email';
                throw new Exception('El email del administrador no es válido');
            }
            if ($admin_password !== '' && !Validacion::validar_contrasena($admin_password)) {
                $campo_error = 'admin_password';
                throw new Exception(Validacion::POLITICA_CONTRASENA);
            }

            // Unicidad GLOBAL: el correo y el documento no pueden existir en NINGUNA institución
            require_once LIB_PATH . '/basedatos.php';
            $bd_check = BaseDatos::obtener();
            if ($bd_check->obtener_uno('SELECT id_usuario FROM usuario WHERE correo_electronico = :c', [':c' => $admin_email])) {
                $campo_error = 'admin_email';
                throw new Exception('Ya existe un usuario con ese correo electrónico en el sistema (otra institución).');
            }
            if ($bd_check->obtener_uno('SELECT id_usuario FROM usuario WHERE numero_documento = :d', [':d' => $admin_documento])) {
                $campo_error = 'admin_documento';
                throw new Exception('Ya existe un usuario con ese número de documento en el sistema (otra institución).');
            }

            // Cargar modelo
            require_once APP_PATH . '/modelos/modelo_institucion.php';
            $modelo = new ModeloInstitucion();

            // Verificar DANE único
            if (!$modelo->es_codigo_dane_unico($codigo_dane)) {
                throw new Exception('El código DANE ya está registrado');
            }

            // Crear institución sin logo primero
            $datos_institucion = [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'codigo_dane' => $codigo_dane,
                'logo_ruta' => null,
                'es_activa' => 1
            ];

            $id_institucion = $modelo->crear($datos_institucion);

            // Procesar logo si existe
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                require_once APP_PATH . '/servicios/servicio_archivos_institucion.php';
                $servicio_archivos = new ServicioArchivosInstitucion();

                // Validar
                $validacion = $servicio_archivos->validar_logo($_FILES['logo']);
                if (!$validacion['valid']) {
                    throw new Exception($validacion['error']);
                }

                // Procesar logo
                $logo_ruta = $servicio_archivos->procesar_logo($_FILES['logo'], $id_institucion);

                // Actualizar institución con la ruta del logo
                if ($logo_ruta) {
                    $modelo->actualizar($id_institucion, ['logo_ruta' => $logo_ruta]);
                }
            }

            // ===== Crear el primer Administrador de la institución =====
            require_once APP_PATH . '/modelos/modelo_usuario.php';
            require_once LIB_PATH . '/basedatos.php';
            $modelo_usuario = new ModeloUsuario();
            $bd = BaseDatos::obtener();

            // Contraseña: la indicada o una generada
            $admin_pass_final = $admin_password !== '' ? $admin_password : bin2hex(random_bytes(5));

            $id_admin = $modelo_usuario->crear([
                'id_institucion' => $id_institucion,
                'nombre_completo' => $admin_nombre,
                'numero_documento' => $admin_documento,
                'correo_electronico' => $admin_email,
                'hash_contrasena' => password_hash($admin_pass_final, PASSWORD_BCRYPT),
                'activo' => 1,
            ]);

            // Asignar rol "Admin de Institución"
            $id_rol_admin = $bd->obtener_uno("SELECT id_rol FROM rol WHERE nombre_rol = 'Admin de Institución'")['id_rol'] ?? null;
            if ($id_rol_admin) {
                $bd->insertar('usuario_rol', [
                    'id_usuario' => $id_admin,
                    'id_rol' => $id_rol_admin,
                    'id_institucion' => $id_institucion,
                ]);
            }

            // Sembrar el catálogo base (categorías, subcategorías y SLA) para la nueva institución
            $this->sembrar_catalogos_institucion($bd, $id_institucion);

            // Guardar credenciales para mostrarlas una vez al superadmin
            $_SESSION['credenciales_admin_institucion'] = [
                'institucion' => $nombre,
                'email' => $admin_email,
                'password' => $admin_pass_final,
            ];

            ServicioAuditoria::registrar('crear_institucion', 'institucion', $id_institucion, null, ['nombre' => $nombre, 'codigo_dane' => $codigo_dane, 'admin_email' => $admin_email], ['id_institucion' => null]);
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio&exito=1');
            exit;

        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log('ERROR CREAR INSTITUCION: ' . $error_msg);

            // Preservar lo ingresado para repoblar el formulario
            $_SESSION['form_inst_old'] = [
                'tipo' => $_POST['tipo'] ?? 'educativa',
                'nombre' => $_POST['nombre'] ?? '',
                'codigo_dane' => $_POST['codigo_dane'] ?? '',
                'admin_nombre' => $_POST['admin_nombre'] ?? '',
                'admin_documento' => $_POST['admin_documento'] ?? '',
                'admin_email' => $_POST['admin_email'] ?? '',
            ];
            $_SESSION['form_inst_campo'] = $campo_error;

            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=crear_institucion&error=' . urlencode($error_msg));
            exit;
        }
    }

    /**
     * Editar institución
     */
    public function editar_institucion() {
        $id_institucion = intval($_GET['id'] ?? 0);

        if (!$id_institucion) {
            responder_peticion_invalida('El enlace no dice qué institución abrir. Vuelve al listado de instituciones.');
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        require_once APP_PATH . '/modelos/modelo_institucion.php';
        $modelo = new ModeloInstitucion();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validar CSRF
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                    throw new Exception('Token CSRF inválido');
                }

                // Validar datos requeridos
                $nombre = trim($_POST['nombre'] ?? '');
                $codigo_dane = trim($_POST['codigo_dane'] ?? '');
                $es_activa = isset($_POST['es_activa']) ? 1 : 0;

                if (empty($nombre) || strlen($nombre) < 3) {
                    throw new Exception('El nombre debe tener al menos 3 caracteres');
                }

                if (strlen($nombre) > 90) {
                    throw new Exception('El nombre no debe exceder 90 caracteres');
                }

                // Validar código DANE - entre 5 y 13 dígitos numéricos
                if (!preg_match('/^[0-9]{5,13}$/', $codigo_dane)) {
                    throw new Exception('El código DANE debe contener entre 5 y 13 dígitos numéricos');
                }

                // Verificar DANE único (excluyendo la institución actual)
                if (!$modelo->es_codigo_dane_unico($codigo_dane, $id_institucion)) {
                    throw new Exception('El código DANE ya está registrado en otra institución');
                }

                // Obtener institución actual
                $institucion_actual = $modelo->obtener_por_id($id_institucion);
                if (!$institucion_actual) {
                    throw new Exception('Institución no encontrada');
                }

                // Datos básicos a actualizar
                $datos_actualizar = [
                    'nombre' => $nombre,
                    'codigo_dane' => $codigo_dane,
                    'es_activa' => $es_activa
                ];

                // Procesar logo si se subió uno nuevo
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    require_once APP_PATH . '/servicios/servicio_archivos_institucion.php';
                    $servicio_archivos = new ServicioArchivosInstitucion();

                    // Validar
                    $validacion = $servicio_archivos->validar_logo($_FILES['logo']);
                    if (!$validacion['valid']) {
                        throw new Exception($validacion['error']);
                    }

                    // Procesar logo (elimina el anterior automáticamente)
                    $logo_ruta = $servicio_archivos->procesar_logo(
                        $_FILES['logo'],
                        $id_institucion,
                        $institucion_actual['logo_ruta']
                    );

                    if ($logo_ruta) {
                        $datos_actualizar['logo_ruta'] = $logo_ruta;
                    }
                }

                // Actualizar institución
                $modelo->actualizar($id_institucion, $datos_actualizar);

                ServicioAuditoria::registrar('editar_institucion', 'institucion', $id_institucion, null, $datos_actualizar, ['id_institucion' => null]);
                $_SESSION['exito'] = 'Institución actualizada exitosamente';
                header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
                exit;

            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        // Obtener institución actual
        $institucion = $modelo->obtener_por_id($id_institucion);

        if (!$institucion) {
            responder_no_encontrado('Esa institución no existe.');
        }

        require_once APP_PATH . '/servicios/servicio_archivos_institucion.php';
        $servicio_logos = new ServicioArchivosInstitucion();

        $datos = [
            'titulo' => 'Editar Institución - ' . config('app.app_name'),
            'institucion' => $institucion,
            // null si el archivo ya no está en disco: la vista oculta la vista previa.
            'logo_url' => $servicio_logos->obtener_url_logo($institucion['logo_ruta'] ?? null),
            'csrf_token' => Validacion::generar_csrf_token(),
            'error' => $error ?? '',
        ];

        $this->renderizar_vista('superadmin/vista_editar_institucion', $datos);
    }

    /**
     * Ver estadísticas de institución
     */
    /**
     * Gestionar sedes de una institución (GET)
     */
    public function gestionar_sedes() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_institucion = intval($_GET['id'] ?? 0);
        if (!$id_institucion) {
            responder_peticion_invalida('El enlace no dice qué institución abrir. Vuelve al listado de instituciones.');
        }

        require_once LIB_PATH . '/basedatos.php';
        require_once APP_PATH . '/modelos/modelo_institucion.php';
        require_once APP_PATH . '/modelos/modelo_sede.php';

        $modelo_institucion = new ModeloInstitucion();
        $modelo_sede = new ModeloSede();

        $institucion = $modelo_institucion->obtener_por_id($id_institucion);
        if (!$institucion) {
            responder_no_encontrado('Esa institución no existe.');
        }

        $sedes = $modelo_sede->listar_por_institucion($id_institucion);

        $datos = [
            'titulo' => 'Gestionar Sedes - ' . htmlspecialchars($institucion['nombre']),
            'institucion' => $institucion,
            'sedes' => $sedes ?? [],
            'csrf_token' => Validacion::generar_csrf_token(),
            'error' => $_GET['error'] ?? '',
        ];

        $this->renderizar_vista('superadmin/vista_gestionar_sedes', $datos);
    }

    /**
     * Editar sede (GET - mostrar formulario)
     */
    public function editar_sede() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_sede = intval($_GET['id'] ?? 0);
        $id_institucion = intval($_GET['inst'] ?? 0);

        if (!$id_sede || !$id_institucion) {
            responder_peticion_invalida('El enlace no dice qué sede abrir. Vuelve al listado de sedes.');
        }

        require_once APP_PATH . '/modelos/modelo_institucion.php';
        require_once APP_PATH . '/modelos/modelo_sede.php';

        $modelo_institucion = new ModeloInstitucion();
        $modelo_sede = new ModeloSede();

        $institucion = $modelo_institucion->obtener_por_id($id_institucion);
        if (!$institucion) {
            responder_no_encontrado('Esa institución no existe.');
        }

        $sede = $modelo_sede->obtener_por_id($id_sede, $id_institucion);
        if (!$sede) {
            responder_no_encontrado('Esa sede no existe.');
        }

        $datos = [
            'titulo' => 'Editar Sede - ' . htmlspecialchars($institucion['nombre']),
            'institucion' => $institucion,
            'sede' => $sede,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('superadmin/vista_editar_sede', $datos);
    }

    /**
     * Procesar creación/actualización de sede (POST)
     */
    public function procesar_sede() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_metodo_no_permitido();
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_institucion = intval($_POST['id_institucion'] ?? 0);
        $id_sede = intval($_POST['id_sede'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo_dane = trim($_POST['direccion'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;

        try {
            if (!$id_institucion) {
                throw new Exception('Institución requerida.');
            }

            if (strlen($nombre) < 3) {
                throw new Exception('El nombre de la sede debe tener al menos 3 caracteres.');
            }

            if (strlen($nombre) > 90) {
                throw new Exception('El nombre de la sede no debe exceder 90 caracteres.');
            }

            if (!preg_match('/^[0-9]{5,13}$/', $codigo_dane)) {
                throw new Exception('El código DANE debe contener entre 5 y 13 dígitos numéricos.');
            }

            require_once APP_PATH . '/modelos/modelo_institucion.php';
            require_once APP_PATH . '/modelos/modelo_sede.php';

            $modelo_institucion = new ModeloInstitucion();
            $modelo_sede = new ModeloSede();

            $institucion = $modelo_institucion->obtener_por_id($id_institucion);
            if (!$institucion) {
                throw new Exception('Institución no encontrada.');
            }

            if ($id_sede) {
                $accion = 'actualizar';
                $modelo_sede->actualizar($id_sede, $id_institucion, [
                    'nombre' => $nombre,
                    'direccion' => $codigo_dane,
                    'activa' => $activa,
                ]);
                $mensaje = 'Sede actualizada correctamente.';
            } else {
                $accion = 'crear';
                $modelo_sede->crear([
                    'id_institucion' => $id_institucion,
                    'nombre' => $nombre,
                    'direccion' => $codigo_dane,
                    'activa' => $activa,
                ]);
                $mensaje = 'Sede creada correctamente.';
            }

            ServicioAuditoria::registrar('sede_' . $accion, 'sede', $id_sede ?: null, null, ['nombre' => $nombre, 'id_institucion' => $id_institucion], ['id_institucion' => null]);
            $_SESSION['exito'] = $mensaje;
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_sedes&id=' . $id_institucion);
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_sedes&id=' . $id_institucion . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Eliminar sede (POST)
     */
    public function eliminar_sede() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_metodo_no_permitido();
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_institucion = intval($_POST['id_institucion'] ?? 0);
        $id_sede = intval($_POST['id_sede'] ?? 0);

        try {
            if (!$id_institucion || !$id_sede) {
                throw new Exception('Institución y sede requeridas.');
            }

            require_once APP_PATH . '/modelos/modelo_sede.php';
            $modelo_sede = new ModeloSede();

            $sede = $modelo_sede->obtener_por_id($id_sede, $id_institucion);
            if (!$sede) {
                throw new Exception('Sede no encontrada.');
            }

            // Guarda: no eliminar si la sede tiene reportes (preservar historial)
            $bd = BaseDatos::obtener();
            $reportes = intval($bd->obtener_uno(
                'SELECT COUNT(*) AS t FROM reporte WHERE id_sede = :id',
                [':id' => $id_sede]
            )['t'] ?? 0);

            if ($reportes > 0) {
                throw new Exception(sprintf(
                    'No se puede eliminar: la sede tiene %d reporte(s) registrados. Reasigna o conserva esos reportes antes de eliminarla.',
                    $reportes
                ));
            }

            // Sin reportes: eliminar la sede y sus áreas/subáreas en cascada
            $modelo_sede->eliminar($id_sede);
            ServicioAuditoria::registrar('sede_eliminar', 'sede', $id_sede, null, null, ['id_institucion' => null]);
            $_SESSION['exito'] = 'Sede eliminada correctamente.';

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_sedes&id=' . $id_institucion);
        exit;
    }

    /**
     * Eliminar institución (con guarda de dependencias para no perder datos)
     */
    public function eliminar_institucion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_metodo_no_permitido();
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_institucion = intval($_POST['id_institucion'] ?? 0);

        try {
            // Validar CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                throw new Exception('Token CSRF inválido.');
            }

            if (!$id_institucion) {
                throw new Exception('Institución requerida.');
            }

            require_once LIB_PATH . '/basedatos.php';
            require_once APP_PATH . '/modelos/modelo_institucion.php';
            $bd = BaseDatos::obtener();
            $modelo_institucion = new ModeloInstitucion();

            $institucion = $modelo_institucion->obtener_por_id($id_institucion);
            if (!$institucion) {
                throw new Exception('Institución no encontrada.');
            }

            // Guarda: proteger datos operativos. Solo se bloquea si tiene REPORTES
            // (historial real a preservar). Usuarios, sedes y catálogos de configuración
            // se eliminan en cascada junto con la institución.
            $reportes = intval($bd->obtener_uno('SELECT COUNT(*) AS t FROM reporte WHERE id_institucion = :id', [':id' => $id_institucion])['t'] ?? 0);

            if ($reportes > 0) {
                throw new Exception(sprintf(
                    'No se puede eliminar: la institución tiene %d reporte(s) registrados. Desactívala en su lugar (Editar → Inactiva) para conservar el historial.',
                    $reportes
                ));
            }

            // Sin reportes: eliminar institución y todos sus datos de configuración en cascada
            $archivos = $modelo_institucion->eliminar($id_institucion);

            // Borrar archivos de evidencia del disco (si existieran)
            foreach ((array) $archivos as $ruta) {
                if ($ruta && file_exists($ruta)) {
                    @unlink($ruta);
                }
            }

            ServicioAuditoria::registrar('eliminar_institucion', 'institucion', $id_institucion, ['nombre' => $institucion['nombre'] ?? null], null, ['id_institucion' => null]);
            $_SESSION['exito'] = 'Institución eliminada correctamente.';
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // ===== TÉCNICOS EXTERNOS =====

    /**
     * Panel para gestionar vínculos técnico ↔ institución educativa.
     */
    public function gestionar_tecnicos() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        require_once LIB_PATH . '/basedatos.php';
        require_once LIB_PATH . '/validacion.php';
        $bd = BaseDatos::obtener();

        // Técnicos externos con sus vínculos activos
        $sql = "SELECT
                    u.id_usuario, u.nombre_completo, u.correo_electronico,
                    emp.nombre AS empresa, emp.id_institucion AS id_empresa,
                    ti.id_institucion AS id_inst_vinculada,
                    ie.nombre AS nombre_inst_vinculada,
                    ti.fecha_vinculacion
                FROM usuario u
                JOIN institucion emp ON emp.id_institucion = u.id_institucion
                    AND emp.tipo = 'empresa_mantenimiento'
                JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                    AND ur.id_institucion = u.id_institucion
                JOIN rol r ON r.id_rol = ur.id_rol AND r.nombre_rol = 'tecnico'
                LEFT JOIN tecnico_institucion ti ON ti.id_usuario = u.id_usuario AND ti.activo = 1
                LEFT JOIN institucion ie ON ie.id_institucion = ti.id_institucion
                WHERE u.activo = 1
                ORDER BY emp.nombre ASC, u.nombre_completo ASC, ie.nombre ASC";

        $rows = $bd->obtener_todos($sql) ?? [];

        // Agrupar por técnico
        $tecnicos = [];
        foreach ($rows as $row) {
            $id = $row['id_usuario'];
            if (!isset($tecnicos[$id])) {
                $tecnicos[$id] = [
                    'id_usuario'        => $id,
                    'nombre_completo'   => $row['nombre_completo'],
                    'correo_electronico'=> $row['correo_electronico'],
                    'empresa'           => $row['empresa'],
                    'id_empresa'        => $row['id_empresa'],
                    'instituciones'     => [],
                ];
            }
            if ($row['id_inst_vinculada']) {
                $tecnicos[$id]['instituciones'][] = [
                    'id_institucion'   => $row['id_inst_vinculada'],
                    'nombre'           => $row['nombre_inst_vinculada'],
                    'fecha_vinculacion'=> $row['fecha_vinculacion'],
                ];
            }
        }

        // Instituciones educativas disponibles para vincular
        $instituciones_educativas = $bd->obtener_todos(
            "SELECT id_institucion, nombre FROM institucion
             WHERE tipo = 'educativa' AND es_activa = 1 ORDER BY nombre ASC"
        ) ?? [];

        $datos = [
            'titulo'                  => 'Técnicos Externos - ' . config('app.app_name'),
            'tecnicos'                => array_values($tecnicos),
            'instituciones_educativas'=> $instituciones_educativas,
            'csrf_token'              => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('superadmin/vista_gestionar_tecnicos', $datos);
    }

    /**
     * Vincular un técnico a una institución educativa (POST).
     */
    public function vincular_tecnico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_tecnicos');
            exit;
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_usuario     = intval($_POST['id_usuario']     ?? 0);
        $id_institucion = intval($_POST['id_institucion'] ?? 0);

        try {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido.');
            }
            if (!$id_usuario || !$id_institucion) {
                throw new Exception('Técnico e institución son requeridos.');
            }

            require_once APP_PATH . '/modelos/modelo_usuario.php';
            $modelo = new ModeloUsuario();
            $modelo->vincular_tecnico_institucion($id_usuario, $id_institucion, $_SESSION['id_usuario']);

            ServicioAuditoria::registrar('vincular_tecnico', 'usuario', $id_usuario, null, ['id_institucion_vinculada' => $id_institucion], ['id_institucion' => null]);
            $_SESSION['exito'] = 'Técnico vinculado correctamente a la institución.';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_tecnicos');
        exit;
    }

    /**
     * Desvincular un técnico de una institución educativa (POST).
     */
    public function desvincular_tecnico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_tecnicos');
            exit;
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        $id_usuario     = intval($_POST['id_usuario']     ?? 0);
        $id_institucion = intval($_POST['id_institucion'] ?? 0);

        try {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido.');
            }
            if (!$id_usuario || !$id_institucion) {
                throw new Exception('Técnico e institución son requeridos.');
            }

            require_once APP_PATH . '/modelos/modelo_usuario.php';
            $modelo = new ModeloUsuario();
            $modelo->desvincular_tecnico_institucion($id_usuario, $id_institucion);

            ServicioAuditoria::registrar('desvincular_tecnico', 'usuario', $id_usuario, ['id_institucion_vinculada' => $id_institucion], null, ['id_institucion' => null]);
            $_SESSION['exito'] = 'Técnico desvinculado correctamente.';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=gestionar_tecnicos');
        exit;
    }

    // ===== HELPERS =====

    /**
     * Siembra el catálogo base (categorías, subcategorías y SLA) para una institución nueva.
     * Sin esto, el formulario de "Crear Reporte" aparece sin categorías para la institución.
     */
    private function sembrar_catalogos_institucion($bd, $id_institucion) {
        // Sede principal por defecto, nombrada con la institución para identificarla claramente
        // (sin ella, el formulario de "Crear Reporte" no tiene dónde ubicar el daño).
        $inst = $bd->obtener_uno('SELECT nombre FROM institucion WHERE id_institucion = :id', [':id' => $id_institucion]);
        $nombre_inst = $inst['nombre'] ?? 'Institución';
        $bd->insertar('sede', [
            'id_institucion' => $id_institucion,
            'nombre' => $nombre_inst . ' - Sede Principal',
            'activa' => 1,
        ]);

        $this->aplicar_catalogo_categorias($bd, $id_institucion);

        // SLA base por urgencia (id_categoria NULL = aplica a todas)
        $slas = [
            [4, 2, 8],    // Urgente:    responder 2h  / resolver 8h
            [3, 8, 24],   // Importante: 8h  / 24h
            [2, 24, 72],  // Moderado:   24h / 72h
            [1, 48, 120], // No urgente: 48h / 120h
        ];
        foreach ($slas as $sla) {
            $bd->insertar('sla', [
                'id_institucion' => $id_institucion,
                'id_categoria' => null,
                'id_urgencia' => $sla[0],
                'tiempo_respuesta_horas' => $sla[1],
                'tiempo_resolucion_horas' => $sla[2],
                'activo' => 1,
            ]);
        }
    }

    /**
     * Aplica el catálogo de configuracion/catalogo_categorias.php a una institución.
     *
     * Es idempotente: solo crea lo que falta. Una categoría o subcategoría que ya
     * exista con el mismo nombre se conserva tal cual, incluidos los ajustes que el
     * administrador haya hecho (crítica, descripción, orden), así que puede volver a
     * ejecutarse sin duplicar ni pisar nada.
     *
     * Devuelve ['categorias' => creadas, 'subcategorias' => creadas].
     */
    private function aplicar_catalogo_categorias($bd, $id_institucion) {
        $catalogo = require CONFIG_PATH . '/catalogo_categorias.php';
        $creadas = ['categorias' => 0, 'subcategorias' => 0];

        $existentes = [];
        $filas = $bd->obtener_todos('SELECT id_categoria, nombre FROM categoria WHERE id_institucion = :i', [':i' => $id_institucion]);
        foreach ($filas as $f) {
            $existentes[mb_strtolower(trim($f['nombre']))] = (int)$f['id_categoria'];
        }

        foreach ($catalogo as $entrada) {
            list($nombre, $descripcion, $critica, $orden, $subcats) = $entrada;
            $clave = mb_strtolower(trim($nombre));

            if (isset($existentes[$clave])) {
                $id_categoria = $existentes[$clave];
            } else {
                $id_categoria = (int)$bd->insertar('categoria', [
                    'id_institucion' => $id_institucion,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'es_critica_escalada' => $critica,
                    'activa' => 1,
                    'orden' => $orden,
                ]);
                $existentes[$clave] = $id_categoria;
                $creadas['categorias']++;
            }

            $subs_existentes = [];
            $filas_sub = $bd->obtener_todos(
                'SELECT nombre FROM subcategoria WHERE id_institucion = :i AND id_categoria = :c',
                [':i' => $id_institucion, ':c' => $id_categoria]
            );
            foreach ($filas_sub as $s) {
                $subs_existentes[mb_strtolower(trim($s['nombre']))] = true;
            }

            $posicion = count($subs_existentes);
            foreach ($subcats as $sub) {
                if (isset($subs_existentes[mb_strtolower(trim($sub))])) {
                    continue;
                }
                $bd->insertar('subcategoria', [
                    'id_institucion' => $id_institucion,
                    'id_categoria' => $id_categoria,
                    'nombre' => $sub,
                    'activa' => 1,
                    'orden' => ++$posicion,
                ]);
                $creadas['subcategorias']++;
            }
        }

        return $creadas;
    }

    /**
     * Aplica el catálogo de categorías a todas las instituciones activas.
     * Pensado para instituciones creadas antes de que existiera el catálogo actual;
     * las nuevas lo reciben solas al crearse.
     */
    public function cargar_catalogo() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
            exit;
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        try {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido.');
            }

            $bd = BaseDatos::obtener();
            $instituciones = $bd->obtener_todos('SELECT id_institucion FROM institucion WHERE es_activa = 1');

            $total = ['categorias' => 0, 'subcategorias' => 0];
            foreach ($instituciones as $inst) {
                $r = $this->aplicar_catalogo_categorias($bd, (int)$inst['id_institucion']);
                $total['categorias'] += $r['categorias'];
                $total['subcategorias'] += $r['subcategorias'];
            }

            ServicioAuditoria::registrar('cargar_catalogo', 'categoria', null, null, ['instituciones' => count($instituciones), 'categorias_nuevas' => $total['categorias'], 'subcategorias_nuevas' => $total['subcategorias']], ['id_institucion' => null]);
            if ($total['categorias'] === 0 && $total['subcategorias'] === 0) {
                $_SESSION['exito'] = 'El catálogo ya estaba completo en las ' . count($instituciones) . ' instituciones activas. No hubo cambios.';
            } else {
                $_SESSION['exito'] = sprintf(
                    'Catálogo aplicado a %d institución(es): %d categorías y %d subcategorías nuevas.',
                    count($instituciones), $total['categorias'], $total['subcategorias']
                );
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
        exit;
    }

    /**
     * Sincroniza la matriz base de permisos (configuracion/permisos_base.php):
     * crea los permisos que falten en la tabla y concede a cada rol los que le
     * corresponden por defecto. Solo añade; nunca retira permisos concedidos.
     */
    public function sincronizar_permisos() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
            exit;
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_INSTITUCIONES);

        try {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido.');
            }

            $base = require CONFIG_PATH . '/permisos_base.php';
            $bd = BaseDatos::obtener();

            $ids = [];
            foreach ($bd->obtener_todos('SELECT id_permiso, codigo FROM permiso') as $p) {
                $ids[$p['codigo']] = (int)$p['id_permiso'];
            }

            $permisos_nuevos = 0;
            foreach ($base['permisos'] as $codigo => [$descripcion, $modulo]) {
                if (isset($ids[$codigo])) continue;
                $ids[$codigo] = (int)$bd->insertar('permiso', [
                    'codigo' => $codigo, 'descripcion' => $descripcion, 'modulo' => $modulo,
                ]);
                $permisos_nuevos++;
            }

            $asignaciones_nuevas = 0;
            foreach ($base['roles'] as $id_rol => $codigos) {
                $actuales = array_column(
                    $bd->obtener_todos('SELECT id_permiso FROM rol_permiso WHERE id_rol = :r', [':r' => $id_rol]),
                    'id_permiso'
                );
                $actuales = array_map('intval', $actuales);
                foreach ($codigos as $codigo) {
                    if (!isset($ids[$codigo]) || in_array($ids[$codigo], $actuales, true)) continue;
                    $bd->insertar('rol_permiso', ['id_rol' => $id_rol, 'id_permiso' => $ids[$codigo]]);
                    $asignaciones_nuevas++;
                }
            }

            ServicioAuditoria::registrar('sincronizar_permisos', 'rol_permiso', null, null, ['permisos_nuevos' => $permisos_nuevos, 'asignaciones_nuevas' => $asignaciones_nuevas], ['id_institucion' => null]);
            $_SESSION['exito'] = ($permisos_nuevos === 0 && $asignaciones_nuevas === 0)
                ? 'La matriz de permisos ya estaba al día. No hubo cambios.'
                : sprintf('Permisos sincronizados: %d permiso(s) nuevo(s) y %d asignación(es) a roles.', $permisos_nuevos, $asignaciones_nuevas);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio');
        exit;
    }

    /**
     * Estado de puesta en marcha de cada institución.
     *
     * Dar de alta un cliente exige varias piezas que hoy se configuran a mano y
     * sin guía: si falta una, el sistema no falla, simplemente no sirve. Un
     * ejemplo real detectado en producción: dos instituciones no tenían ningún
     * Gestor ni Rector, así que sus alertas de SLA se generaban y se
     * descartaban sin destinatario, y nadie lo notaba.
     *
     * Se resuelve en una sola consulta agregada, no una por institución: con
     * diez o más clientes, un bucle de consultas aquí se nota.
     *
     * @return array [id_institucion => ['listo' => bool, 'faltan' => [...]]]
     */
    private function evaluar_preparacion($bd) {
        // Se cuenta por id_rol y no por nombre: los nombres llevan acentos y
        // una base con la codificación mal importada haría que la comparación
        // fallara en silencio (pasa de verdad: en la base de desarrollo
        // 'Admin de Institución' está guardado con los bytes corruptos).
        $filas = $bd->obtener_todos(
            "SELECT i.id_institucion, i.tipo,
                    (SELECT COUNT(*) FROM sede s
                      WHERE s.id_institucion = i.id_institucion AND s.activa = 1) AS sedes,
                    (SELECT COUNT(*) FROM categoria c
                      WHERE c.id_institucion = i.id_institucion) AS categorias,
                    (SELECT COUNT(*) FROM sla sl
                      WHERE sl.id_institucion = i.id_institucion AND sl.activo = 1) AS slas,
                    (SELECT COUNT(DISTINCT u.id_usuario)
                       FROM usuario u
                       JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                      WHERE u.id_institucion = i.id_institucion AND u.activo = 1
                        AND ur.id_rol = " . ROL_ADMIN . ") AS admins,
                    (SELECT COUNT(DISTINCT u.id_usuario)
                       FROM usuario u
                       JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                      WHERE u.id_institucion = i.id_institucion AND u.activo = 1
                        AND ur.id_rol IN (" . ROL_GESTOR . ", " . ROL_RECTOR . ")) AS gestores,
                    -- Propios más externos con vínculo activo: un colegio
                    -- atendido solo por una empresa de mantenimiento no está
                    -- sin técnicos.
                    ((SELECT COUNT(DISTINCT u.id_usuario)
                       FROM usuario u
                       JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                      WHERE u.id_institucion = i.id_institucion AND u.activo = 1
                        AND ur.id_rol = " . ROL_TECNICO . ")
                   + (SELECT COUNT(DISTINCT ti.id_usuario)
                       FROM tecnico_institucion ti
                       JOIN usuario u ON u.id_usuario = ti.id_usuario AND u.activo = 1
                      WHERE ti.id_institucion = i.id_institucion AND ti.activo = 1)) AS tecnicos
               FROM institucion i"
        );

        // Cada requisito dice qué falta y adónde ir a resolverlo.
        $requisitos = [
            'sedes'      => ['Sin sedes', 'No se puede ubicar un daño sin al menos una sede.', 'gestionar_sedes'],
            'categorias' => ['Sin categorías', 'El formulario de reporte no tiene qué ofrecer.', null],
            'slas'       => ['Sin SLA', 'Los tiempos de atención usan el valor por defecto de 48 h.', null],
            'admins'     => ['Sin Admin de Institución', 'Nadie puede gestionar usuarios ni configuración.', null],
            'gestores'   => ['Sin Gestor ni Rector', 'Nadie recibe los avisos de SLA ni asigna técnicos.', null],
            'tecnicos'   => ['Sin técnicos', 'No hay a quién asignarle los reportes.', null],
        ];

        // Una empresa de mantenimiento no recibe reportes: no necesita sedes,
        // categorías, SLA ni gestores. Solo quien administre su personal y
        // técnicos que vincular a los colegios.
        $requisitos_empresa = [
            'admins'   => $requisitos['admins'],
            'tecnicos' => ['Sin técnicos', 'No hay técnicos que vincular a los colegios.', null],
        ];

        $resultado = [];
        foreach ($filas as $f) {
            $faltan = [];
            $aplican = $f['tipo'] === 'empresa_mantenimiento' ? $requisitos_empresa : $requisitos;
            foreach ($aplican as $clave => $info) {
                if ((int) $f[$clave] === 0) {
                    $faltan[] = ['titulo' => $info[0], 'motivo' => $info[1], 'accion' => $info[2]];
                }
            }
            $resultado[(int) $f['id_institucion']] = [
                'listo'  => empty($faltan),
                'faltan' => $faltan,
            ];
        }

        return $resultado;
    }

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
        <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!empty($exito)): ?><div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div><?php endif; ?>
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo asset_url('js/script_base.js'); ?>"></script>
    <script src="<?php echo asset_url('js/tema.js'); ?>"></script>
</body>
</html><?php
        ?>
        <script src="<?php echo asset_url('js/toast.js'); ?>"></script>
        <?php
        // Mostrar toasts de sesión si existen
        if (!empty($_SESSION['exito'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    toast.success('¡Éxito!', '<?php echo addslashes(htmlspecialchars($_SESSION['exito'])); ?>', 4000);
                });
            </script>
            <?php unset($_SESSION['exito']);
        endif;

        if (!empty($_SESSION['error'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    toast.error('Error', '<?php echo addslashes(htmlspecialchars($_SESSION['error'])); ?>', 5000);
                });
            </script>
            <?php unset($_SESSION['error']);
        endif;

        if (!empty($_SESSION['advertencia'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    toast.warning('Advertencia', '<?php echo addslashes(htmlspecialchars($_SESSION['advertencia'])); ?>', 4000);
                });
            </script>
            <?php unset($_SESSION['advertencia']);
        endif;
        ?>
        <?php
        echo ob_get_clean();
    }
}
