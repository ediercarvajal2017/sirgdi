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
            'titulo' => 'Administración Global - SIRGDI',
            'instituciones' => $instituciones,
            'cred_admin' => $cred_admin,
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
                'titulo' => 'Crear Institución - SIRGDI',
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
            if ($admin_password !== '' && strlen($admin_password) < 6) {
                $campo_error = 'admin_password';
                throw new Exception('La contraseña del administrador debe tener al menos 6 caracteres');
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

            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio&exito=1');
            exit;

        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log('ERROR CREAR INSTITUCION: ' . $error_msg);

            // Preservar lo ingresado para repoblar el formulario
            $_SESSION['form_inst_old'] = [
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
            die('ID de institución requerido.');
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
            die('Institución no encontrada.');
        }

        $datos = [
            'titulo' => 'Editar Institución - SIRGDI',
            'institucion' => $institucion,
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
            die('ID de institución requerido.');
        }

        require_once LIB_PATH . '/basedatos.php';
        require_once APP_PATH . '/modelos/modelo_institucion.php';
        require_once APP_PATH . '/modelos/modelo_sede.php';

        $modelo_institucion = new ModeloInstitucion();
        $modelo_sede = new ModeloSede();

        $institucion = $modelo_institucion->obtener_por_id($id_institucion);
        if (!$institucion) {
            die('Institución no encontrada.');
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
            die('ID de sede e institución requeridos.');
        }

        require_once APP_PATH . '/modelos/modelo_institucion.php';
        require_once APP_PATH . '/modelos/modelo_sede.php';

        $modelo_institucion = new ModeloInstitucion();
        $modelo_sede = new ModeloSede();

        $institucion = $modelo_institucion->obtener_por_id($id_institucion);
        if (!$institucion) {
            die('Institución no encontrada.');
        }

        $sede = $modelo_sede->obtener_por_id($id_sede, $id_institucion);
        if (!$sede) {
            die('Sede no encontrada.');
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
            die('Método no permitido.');
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
                $modelo_sede->actualizar($id_sede, $id_institucion, [
                    'nombre' => $nombre,
                    'direccion' => $codigo_dane,
                    'activa' => $activa,
                ]);
                $mensaje = 'Sede actualizada correctamente.';
            } else {
                $modelo_sede->crear([
                    'id_institucion' => $id_institucion,
                    'nombre' => $nombre,
                    'direccion' => $codigo_dane,
                    'activa' => $activa,
                ]);
                $mensaje = 'Sede creada correctamente.';
            }

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
            die('Método no permitido.');
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
            die('Método no permitido.');
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

            $_SESSION['exito'] = 'Institución eliminada correctamente.';
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=superadmin&accion=inicio&error=' . urlencode($e->getMessage()));
            exit;
        }
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

        // Categorías base: [nombre, descripción, es_critica_escalada, orden, [subcategorías...]]
        $catalogo = [
            ['Infraestructura', 'Techos, muros, pisos, puertas y ventanas.', 0, 1,
                ['Techos y cubierta', 'Muros y paredes', 'Pisos y pavimento', 'Puertas y marcos', 'Ventanas y vidrios', 'Escaleras y rampas']],
            ['Mobiliario', 'Sillas, mesas, tableros, estantes y lockers.', 0, 2,
                ['Sillas y pupitres', 'Mesas y escritorios', 'Tableros (pizarrones)', 'Estantes y armarios']],
            ['Eléctrico', 'Tomas, luminarias, tableros eléctricos, cableado.', 1, 3,
                ['Tomas y enchufes', 'Luminarias y bombillas', 'Tablero eléctrico', 'Cableado y ductos', 'Sistemas de emergencia']],
            ['Sanitario', 'Inodoros, lavamanos, tuberías y fugas de agua.', 0, 4,
                ['Inodoros y sanitarios', 'Lavamanos y grifería', 'Tuberías y desagües', 'Fugas de agua', 'Tanques y cisternas']],
            ['Tecnológico', 'Computadores, proyectores, red y sistemas de audio.', 0, 5,
                ['Computadores y portátiles', 'Proyectores y pantallas', 'Red e internet', 'Sistemas de audio']],
            ['Seguridad', 'Cercas, extintores, cámaras y salidas de emergencia.', 1, 6,
                ['Cercas y rejas', 'Extintores', 'Cámaras de seguridad', 'Salidas de emergencia', 'Señalización']],
        ];

        foreach ($catalogo as $cat) {
            list($nombre, $descripcion, $critica, $orden, $subcats) = $cat;

            $id_categoria = $bd->insertar('categoria', [
                'id_institucion' => $id_institucion,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'es_critica_escalada' => $critica,
                'activa' => 1,
                'orden' => $orden,
            ]);

            $i = 1;
            foreach ($subcats as $sub) {
                $bd->insertar('subcategoria', [
                    'id_institucion' => $id_institucion,
                    'id_categoria' => $id_categoria,
                    'nombre' => $sub,
                    'activa' => 1,
                    'orden' => $i++,
                ]);
            }
        }

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
        <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!empty($exito)): ?><div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div><?php endif; ?>
        <?php require $archivo_vista; ?>
    </main>
    <?php if (isset($_SESSION['id_usuario'])): require_once APP_PATH . '/vistas/comunes/vista_footer.php'; endif; ?>
    <script src="<?php echo config('app.url_base'); ?>/js/script_base.js"></script>
</body>
</html><?php
        ?>
        <script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>
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
