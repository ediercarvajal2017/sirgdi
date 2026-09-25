<?php
// Controlador de Gestión (Manager Dashboard)
// RF-10 (Kanban), RF-12 (Asignar), RF-13 (Comentarios)

class ControladorGestion {
    private $auth;
    private $autorizacion;
    private $modelo_reporte;
    private $modelo_usuario;
    private $servicio_prioridad;
    private $modelo_sla;

    public function __construct() {
        require_once APP_PATH . '/servicios/servicio_autenticacion.php';
        require_once APP_PATH . '/servicios/servicio_autorizacion.php';
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_usuario.php';
        require_once APP_PATH . '/servicios/servicio_prioridad.php';
        require_once APP_PATH . '/modelos/modelo_sla.php';
        require_once LIB_PATH . '/validacion.php';

        $this->auth = new ServicioAutenticacion();
        $this->autorizacion = new ServicioAutorizacion();
        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_usuario = new ModeloUsuario();
        $this->servicio_prioridad = new ServicioPrioridad();
        $this->modelo_sla = new ModeloSLA();
    }

    /**
     * RF-10: Listar reportes en vista compacta
     */
    public function listar() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VER_TODOS_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Filtros
        $filtros = [];
        if (isset($_GET['estado']) && $_GET['estado'] !== '') {
            $filtros['id_estado'] = intval($_GET['estado']);
        }
        if (isset($_GET['urgencia']) && $_GET['urgencia'] !== '') {
            // La clave es id_urgencia, que es la que lee el modelo. Antes se
            // escribía id_urgencia_calculada —el nombre de la columna, no el
            // del filtro—, así que el desplegable no hacía absolutamente nada.
            $filtros['id_urgencia'] = intval($_GET['urgencia']);
        }

        // Paginación
        $pagina = intval($_GET['pagina'] ?? 1);
        $limite = 50;
        $offset = ($pagina - 1) * $limite;

        // Obtener reportes
        $reportes = $this->modelo_reporte->listar_por_institucion($id_institucion, $filtros, $limite, $offset);

        $datos = [
            'titulo' => 'Gestión de Reportes - ' . config('app.app_name'),
            'reportes' => $reportes,
            'pagina' => $pagina,
            'filtros' => $filtros,
        ];

        $this->renderizar_vista('gestion/vista_gestion_reportes_lista', $datos);
    }

    /**
     * RF-10: Dashboard Kanban - Vista por estados
     */
    public function kanban() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_VER_TODOS_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Obtener reportes ordenados por prioridad
        $reportes_por_prioridad = $this->servicio_prioridad->listar_por_prioridad($id_institucion);

        // Agrupar por estado
        $columnas = [
            ESTADO_REGISTRADO => [],
            ESTADO_EN_PROCESO => [],
            ESTADO_DEVUELTO => [],
            ESTADO_SOLUCIONADO => [],
            ESTADO_EN_VALIDACION => [],
            ESTADO_CERRADO => [],
        ];

        $estados_nombres = [
            ESTADO_REGISTRADO => 'Registrado',
            ESTADO_EN_PROCESO => 'En Proceso',
            ESTADO_DEVUELTO => 'Devuelto',
            ESTADO_SOLUCIONADO => 'Solucionado',
            ESTADO_EN_VALIDACION => 'En Validación',
            ESTADO_CERRADO => 'Cerrado',
        ];

        foreach ($reportes_por_prioridad as $item) {
            $estado = $item['reporte']['id_estado'];
            if (isset($columnas[$estado])) {
                $columnas[$estado][] = $item;
            }
        }

        // Estadísticas: se derivan de lo ya calculado, sin repetir el recorrido.
        $stats = $this->servicio_prioridad->obtener_estadisticas_prioridad(
            $id_institucion, $reportes_por_prioridad
        );

        // Colores de urgencia (mapeo id_urgencia => color hex)
        $colores_urgencia = [
            URGENCIA_NO_URGENTE => '#28A745',
            URGENCIA_MODERADO => '#FFC107',
            URGENCIA_IMPORTANTE => '#FD7E14',
            URGENCIA_URGENTE => '#DC3545',
        ];

        $datos = [
            'titulo' => 'Gestión de Reportes - ' . config('app.app_name'),
            'columnas' => $columnas,
            'estados_nombres' => $estados_nombres,
            'stats' => $stats,
            'colores_urgencia' => $colores_urgencia,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('gestion/vista_kanban_gestion', $datos);
    }

    /**
     * RF-12: Asignar técnico a reporte
     */
    public function asignar_tecnico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_metodo_no_permitido();
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_ASIGNAR_TECNICO);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_tecnico = intval($_POST['id_tecnico'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte || !$id_tecnico) {
            responder_peticion_invalida('Faltan datos para asignar el reporte. Vuelve al tablero e inténtalo de nuevo.');
        }

        try {
            // Validar que el reporte pertenece a la institución
            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // Un técnico propio de la institución, o uno de una empresa de
            // mantenimiento con vínculo activo. Antes se exigía que el usuario
            // perteneciera a la institución, y los externos nunca pasaban.
            $tecnico = $this->modelo_usuario->obtener_tecnico_para_institucion($id_tecnico, $id_institucion);
            if (!$tecnico) {
                throw new Exception('Ese técnico no puede atender esta institución.');
            }

            // Asignar
            $this->modelo_reporte->asignar_tecnico(
                $id_reporte, $id_institucion, $id_tecnico, $this->auth->obtener_id_usuario()
            );

            // Cambiar estado a "En Proceso" directamente: por diseño se salta
            // ESTADO_ASIGNADO (queda vestigial, ver lib/constantes.php) en vez de
            // esperar a que el técnico abra la hoja de trabajo para recién ahí
            // marcar que empezó.
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                ESTADO_EN_PROCESO,
                'Asignado a técnico: ' . htmlspecialchars($tecnico['nombre_completo']),
                $this->auth->obtener_id_usuario()
            );

            // Notificar al técnico
            require_once APP_PATH . '/servicios/servicio_notificacion.php';
            $servicio_notificacion = new ServicioNotificacion();
            $servicio_notificacion->notificar_asignacion_tecnico(
                $id_reporte,
                $id_institucion,
                $id_tecnico,
                $reporte['numero_ticket']
            );

            ServicioAuditoria::registrar('asignar_tecnico', 'reporte', $id_reporte,
                ['id_tecnico' => $reporte['id_tecnico_asignado'], 'id_estado' => $reporte['id_estado']],
                ['id_tecnico' => $id_tecnico, 'tecnico' => $tecnico['nombre_completo'], 'id_estado' => ESTADO_EN_PROCESO, 'ticket' => $reporte['numero_ticket']]);

            // Redirigir de vuelta al Kanban con mensaje de éxito
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Eliminar un reporte y todos sus registros relacionados (POST)
     */
    public function eliminar_reporte() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_metodo_no_permitido();
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_REPORTES);

        $id_institucion = $this->auth->obtener_id_institucion();
        $id_reporte = intval($_POST['id_reporte'] ?? 0);

        try {
            // Validar CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
                throw new Exception('Token CSRF inválido.');
            }

            if (!$id_reporte) {
                throw new Exception('Reporte requerido.');
            }

            $previo = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);

            // Eliminar reporte + hijos (devuelve rutas de evidencia para borrar del disco)
            $archivos = $this->modelo_reporte->eliminar($id_reporte, $id_institucion);

            ServicioAuditoria::registrar('eliminar_reporte', 'reporte', $id_reporte,
                $previo ? ['ticket' => $previo['numero_ticket'], 'id_estado' => $previo['id_estado'], 'descripcion' => mb_substr($previo['descripcion_problema'], 0, 200)] : null,
                null);

            // Borrar archivos de evidencia del disco (si existen)
            foreach ($archivos as $ruta) {
                if ($ruta && file_exists($ruta)) {
                    @unlink($ruta);
                }
            }

            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Cambiar estado de reporte (GET form)
     */
    public function cambiar_estado() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_REPORTES);

        $id_reporte = intval($_GET['id'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_reporte) {
            responder_peticion_invalida('El enlace no dice qué reporte abrir. Vuelve al listado y entra desde ahí.');
        }

        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            responder_no_encontrado('Ese reporte no existe, o pertenece a otra institución.');
        }

        $estados = [
            ESTADO_REGISTRADO => 'Registrado',
            ESTADO_EN_PROCESO => 'En Proceso',
            ESTADO_DEVUELTO => 'Devuelto',
            ESTADO_SOLUCIONADO => 'Solucionado',
            ESTADO_EN_VALIDACION => 'En Validación',
            ESTADO_CERRADO => 'Cerrado',
        ];

        // "Anulado" existía en el esquema, en la tabla de transiciones y como
        // permiso concedido a Gestor, Rector y Admin, pero no aparecía en
        // ninguna pantalla: no había forma de llegar a él. Con el formulario
        // público abierto hace falta, porque la única alternativa para un
        // reporte de spam era borrarlo de forma permanente —perdiendo la
        // evidencia de que llegó— o dejarlo en el tablero para siempre.
        if ($this->autorizacion->verificar_permiso(PERMISO_ANULAR_REPORTE)) {
            $estados[ESTADO_ANULADO] = 'Anulado';
        }

        $datos = [
            'titulo' => 'Cambiar Estado - ' . config('app.app_name'),
            'reporte' => $reporte,
            'estados' => $estados,
            'csrf_token' => Validacion::generar_csrf_token(),
        ];

        $this->renderizar_vista('gestion/vista_cambiar_estado', $datos);
    }

    /**
     * Procesar cambio de estado (POST)
     */
    public function procesar_cambiar_estado() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_metodo_no_permitido();
        }

        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_GESTIONAR_REPORTES);

        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $id_estado_nuevo = intval($_POST['id_estado_nuevo'] ?? 0);
        $justificacion = Validacion::sanitizar_texto($_POST['justificacion'] ?? '');
        $id_institucion = $this->auth->obtener_id_institucion();
        $id_usuario = $this->auth->obtener_id_usuario();

        try {
            if (!$id_reporte || !$id_estado_nuevo) {
                throw new Exception('Reporte y estado nuevo requeridos.');
            }

            $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
            if (!$reporte) {
                throw new Exception('Reporte no encontrado.');
            }

            // Anular es irreversible —el estado 8 es terminal— y borra el
            // reporte del trabajo pendiente, así que se comprueba el permiso
            // específico y se exige decir por qué. Sin justificación, dentro de
            // seis meses nadie sabrá si fue spam o un descuido.
            if ($id_estado_nuevo === ESTADO_ANULADO) {
                $this->autorizacion->requerir_permiso(PERMISO_ANULAR_REPORTE);
                if (mb_strlen($justificacion) < 10) {
                    throw new Exception('Para anular un reporte hay que explicar el motivo (mínimo 10 caracteres).');
                }
            }

            // Cambiar estado
            $this->modelo_reporte->cambiar_estado(
                $id_reporte,
                $id_institucion,
                $id_estado_nuevo,
                $justificacion,
                $id_usuario
            );

            ServicioAuditoria::registrar('cambiar_estado', 'reporte', $id_reporte,
                ['id_estado' => $reporte['id_estado']],
                ['id_estado' => $id_estado_nuevo, 'ticket' => $reporte['numero_ticket']]);

            // Si vuelve a "En Proceso", reanudar SLA (RN-10)
            if ($id_estado_nuevo == ESTADO_EN_PROCESO && $reporte['fecha_pausa_sla']) {
                $this->modelo_reporte->reanudar_sla($id_reporte, $id_institucion);
            }

            // Si pasa a "Devuelto", pausar SLA (RN-10)
            if ($id_estado_nuevo == ESTADO_DEVUELTO) {
                $this->modelo_reporte->pausar_sla($id_reporte, $id_institucion);
            }

            // Cerrar por esta vía no avisaba a nadie: el ciudadano no llegaba a
            // enterarse de que su reporte se había cerrado. El cierre formal
            // (ControladorCierre) sí lo hacía, así que el aviso dependía de por
            // qué pantalla hubiera pasado el gestor.
            //
            // Al anular no se escribe a quien reportó, a propósito: un reporte
            // se anula justamente cuando no debe tramitarse, y lo habitual es
            // que sea spam con un correo inventado. El motivo queda en la
            // auditoría y el estado se ve en la página de seguimiento.
            if ($id_estado_nuevo == ESTADO_CERRADO) {
                require_once APP_PATH . '/servicios/servicio_notificacion.php';
                $servicio_notificacion = new ServicioNotificacion();
                $servicio_notificacion->notificar_reporte_cerrado(
                    $id_reporte,
                    $id_institucion,
                    $reporte['numero_ticket'],
                    $reporte['correo_reportante'] ?? null
                );
            }

            // Redirigir
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&exito=1');
            exit;

        } catch (Exception $e) {
            header('Location: ' . config('app.url_base') . '/?controlador=gestion&accion=kanban&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Obtener técnicos disponibles (AJAX JSON)
     */
    public function obtener_tecnicos_json() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_ASIGNAR_TECNICO);

        $id_institucion = $this->auth->obtener_id_institucion();

        // Los propios y los de empresas de mantenimiento vinculadas: la misma
        // regla que decide si el técnico puede entrar a trabajar aquí.
        $tecnicos = array_map(fn($t) => [
            'id_usuario' => $t['id_usuario'],
            'nombre'     => $t['nombre_completo'],
            'email'      => $t['correo_electronico'],
            'empresa'    => $t['empresa'],
        ], $this->modelo_usuario->tecnicos_de_institucion($id_institucion));

        header('Content-Type: application/json');
        echo json_encode($tecnicos);
    }

    /**
     * Obtener carga de trabajo de técnico (AJAX)
     */
    public function obtener_carga_tecnico_json() {
        $this->auth->requerir_autenticacion();
        $this->autorizacion->requerir_permiso(PERMISO_ASIGNAR_TECNICO);

        $id_tecnico = intval($_POST['id_tecnico'] ?? 0);
        $id_institucion = $this->auth->obtener_id_institucion();

        if (!$id_tecnico) {
            responder_peticion_invalida('El enlace no dice qué técnico consultar.');
        }

        // Contar reportes activos asignados al técnico
        $sql = 'SELECT COUNT(*) as total FROM reporte
                WHERE id_tecnico_asignado = :id_tecnico
                AND id_institucion = :id_institucion
                AND id_estado IN (' . ESTADO_EN_PROCESO . ',' . ESTADO_DEVUELTO . ')';

        require_once LIB_PATH . '/basedatos.php';
        $bd = BaseDatos::obtener();
        $resultado = $bd->obtener_uno($sql, [
            ':id_tecnico' => $id_tecnico,
            ':id_institucion' => $id_institucion,
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'reportes_activos' => intval($resultado['total'] ?? 0),
        ]);
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
        <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!empty($exito)): ?><div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div><?php endif; ?>
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
