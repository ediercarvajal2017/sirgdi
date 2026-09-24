<?php
// Servicio de Notificación — envío real por SMTP (PHPMailer)
// Cubre: RF-08, RF-12, RF-21, RF-23, RF-24, RN-13

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class ServicioNotificacion {
    /** Intentos de envio antes de dar una notificacion por perdida. */
    const MAX_INTENTOS_ENVIO = 5;

    /**
     * Horas tras las cuales un aviso pendiente deja de tener sentido.
     * Un "reporte listo para validación" entregado tres días tarde confunde
     * más de lo que ayuda: el reporte ya se movió. Se marca como caducado en
     * vez de enviarlo.
     */
    const HORAS_CADUCIDAD_AVISO = 48;

    private $bd;
    private $smtp;

    public function __construct() {
        $this->bd   = BaseDatos::obtener();
        $this->smtp = config('smtp');
    }

    // ─── API pública ────────────────────────────────────────────────────────

    /** RF-08: Nuevo reporte — avisa a Gestor y Rector */
    public function notificar_nuevo_reporte($id_reporte, $id_institucion, $numero_ticket, $descripcion, $marcado_sospechoso = false) {
        $asunto = "Nuevo reporte #{$numero_ticket}";
        $intro  = 'Se ha registrado un nuevo reporte que requiere atención.';
        if ($marcado_sospechoso) {
            $asunto = "[Revisar] Nuevo reporte #{$numero_ticket}";
            $intro  = '⚠️ Este reporte fue enviado por un remitente sin cuenta y el sistema lo marcó '
                . 'con señales típicas de spam (enlaces, texto repetido u otro patrón). '
                . 'Revísalo antes de asignarlo — puede ser legítimo igual, pero conviene verificarlo primero.';
        }

        $cuerpo = $this->plantilla('Nuevo Reporte Registrado', [
            'Ticket'       => $numero_ticket,
            'Descripción'  => htmlspecialchars(substr($descripcion, 0, 200)),
        ], $intro);

        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor', 'rector']);
    }

    /**
     * Acuse de recibo para quien reporta sin tener cuenta.
     *
     * Sin esto, el enlace de seguimiento solo existía en la pestaña que el
     * ciudadano tenía abierta: al cerrarla perdía el acceso a su reporte para
     * siempre, porque no hay ninguna pantalla donde buscarlo por número de
     * ticket. El correo es el único sitio donde ese enlace sobrevive.
     *
     * Solo se envía si dejó correo, que es opcional en el formulario.
     */
    public function notificar_confirmacion_reportante(
        $id_reporte, $id_institucion, $numero_ticket, $email_reportante,
        $nombre_reportante, $token_seguimiento
    ) {
        if (empty($email_reportante) || empty($token_seguimiento)) {
            return;
        }

        $enlace = config('app.url_base')
                . '/?controlador=reportes&accion=seguimiento&token=' . urlencode($token_seguimiento);

        $asunto = "Recibimos su reporte #{$numero_ticket}";
        $cuerpo = $this->plantilla('Reporte recibido', [
            'Número de ticket' => htmlspecialchars($numero_ticket),
        ],
            'Su reporte quedó registrado y ya está en la lista de la institución.
            Guarde este correo: el enlace de abajo es la forma de consultar en qué va,
            y funciona sin necesidad de crear una cuenta.
            <p style="text-align:center;margin:24px 0;">
              <a href="' . htmlspecialchars($enlace) . '"
                 style="background:#1a56db;color:#fff;padding:13px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;">
                 Ver el estado de mi reporte
              </a>
            </p>
            <p style="font-size:13px;color:#6b7280;">
              Si el botón no funciona, copie esta dirección en su navegador:<br>
              <span style="word-break:break-all;">' . htmlspecialchars($enlace) . '</span>
            </p>');

        // Sale solo por SMTP, sin fila en la tabla notificacion, porque esa
        // tabla exige un id_usuario_destinatario y el ciudadano no tiene
        // cuenta. Consecuencia que conviene tener presente: este correo no
        // entra en la cola de reintentos, así que si el SMTP falla justo en
        // ese momento no se vuelve a intentar. Queda anotado en el log de
        // envíos, que es lo que revisa el cron diario de salud.
        //
        // La red de seguridad mientras tanto es que, tras enviar, al ciudadano
        // se le redirige ya a su pantalla de seguimiento: el enlace le queda en
        // el historial del navegador aunque el correo no llegue.
        $this->enviar_email(
            $email_reportante,
            $nombre_reportante ?: 'Reportante',
            $asunto, $cuerpo,
            $id_institucion, $id_reporte, 'confirmacion_reportante'
        );
    }

    /**
     * RF-12: Técnico asignado.
     * Avisa al técnico (con todo lo que necesita para actuar) y al reportante
     * (para que sepa que su ticket ya está en manos de alguien).
     */
    public function notificar_asignacion_tecnico($id_reporte, $id_institucion, $id_tecnico, $numero_ticket) {
        $tecnico = $this->obtener_usuario($id_tecnico);
        if (!$tecnico) return;

        $reporte = $this->obtener_reporte_detallado($id_reporte, $id_institucion);
        if (!$reporte) return;

        $url_base       = config('app.url_base');
        $nombre_tecnico = htmlspecialchars($tecnico['nombre_completo'] ?? 'Técnico');
        $ubicacion      = trim(($reporte['sede'] ?? '') . (empty($reporte['referencia_ubicacion_libre']) ? '' : ' — ' . $reporte['referencia_ubicacion_libre']));
        $clasificacion  = trim(($reporte['categoria'] ?? '') . (empty($reporte['subcategoria']) ? '' : ' / ' . $reporte['subcategoria']));

        // ── 1. Al técnico: qué hay que atender, dónde y con qué prioridad ──
        $link_tecnico = $url_base . '/?controlador=tecnico&accion=mis_asignaciones';
        $asunto_tecnico = "Nuevo ticket asignado #{$numero_ticket} — " . ($reporte['urgencia'] ?? '');
        $cuerpo_tecnico = $this->plantilla('Tiene un ticket pendiente por atender', [
            'Ticket'         => $numero_ticket,
            'Urgencia'       => htmlspecialchars($reporte['urgencia'] ?? ''),
            'Clasificación'  => htmlspecialchars($clasificacion),
            'Ubicación'      => htmlspecialchars($ubicacion),
            'Descripción'    => nl2br(htmlspecialchars($reporte['descripcion_problema'] ?? '')),
            'Reportado por'  => htmlspecialchars($reporte['nombre_reportante'] ?? ''),
            'Registrado el'  => $this->fecha_legible($reporte['fecha_hora_registro'] ?? null),
        ], 'Se le ha asignado el siguiente reporte. Ingrese a la plataforma para registrar su intervención y las evidencias.'
           . $this->boton_enlace($link_tecnico, 'Ver mis asignaciones'));

        $this->enviar_email(
            $tecnico['correo_electronico'],
            $tecnico['nombre_completo'] ?? 'Técnico',
            $asunto_tecnico,
            $cuerpo_tecnico,
            $id_institucion,
            $id_reporte,
            'reporte_asignado',
            true,
            $id_tecnico
        );

        // ── 2. Al reportante: su ticket avanzó y quién lo atenderá ──
        if (!empty($reporte['correo_reportante'])) {
            $link_seguimiento = $url_base . '/?controlador=reportes&accion=seguimiento&token='
                              . urlencode($reporte['token_seguimiento_publico'] ?? '');
            $asunto_reportante = "Su reporte #{$numero_ticket} ya tiene técnico asignado";
            $cuerpo_reportante = $this->plantilla('Su reporte está en proceso', [
                'Ticket'            => $numero_ticket,
                'Estado actual'     => htmlspecialchars($reporte['estado'] ?? 'En proceso'),
                'Técnico asignado'  => $nombre_tecnico,
                'Clasificación'     => htmlspecialchars($clasificacion),
                'Ubicación'         => htmlspecialchars($ubicacion),
            ], 'Le informamos que su reporte ha sido revisado y asignado a un técnico, quien se encargará de atenderlo. '
               . 'Puede consultar el avance en cualquier momento desde el siguiente enlace:'
               . $this->boton_enlace($link_seguimiento, 'Seguir mi reporte'));

            $this->enviar_email(
                $reporte['correo_reportante'],
                $reporte['nombre_reportante'] ?? 'Reportante',
                $asunto_reportante,
                $cuerpo_reportante,
                $id_institucion,
                $id_reporte,
                'reporte_asignado_reportante',
                true,
                !empty($reporte['id_reportante']) ? (int)$reporte['id_reportante'] : null
            );
        }
    }

    /**
     * Solución rechazada por el gestor: avisa al técnico con el motivo y un enlace
     * directo a la hoja de trabajo. Antes el ticket simplemente reaparecía en su lista
     * sin ninguna explicación de qué corregir.
     */
    public function notificar_reporte_devuelto($id_reporte, $id_institucion, $numero_ticket, $motivo) {
        $reporte = $this->obtener_reporte_detallado($id_reporte, $id_institucion);
        if (!$reporte || empty($reporte['id_tecnico_asignado'])) return;

        $tecnico = $this->obtener_usuario($reporte['id_tecnico_asignado']);
        if (!$tecnico) return;

        $link = config('app.url_base') . '/?controlador=tecnico&accion=hoja_trabajo&id=' . $id_reporte;
        $asunto = "Su solución del ticket #{$numero_ticket} fue devuelta";
        $cuerpo = $this->plantilla('Ticket devuelto para corrección', [
            'Ticket' => $numero_ticket,
            'Motivo del rechazo' => nl2br(htmlspecialchars($motivo)),
        ], 'El gestor revisó la solución reportada y la devolvió para que se corrija o se amplíe '
           . 'la evidencia. Ingrese a la hoja de trabajo para continuar:'
           . $this->boton_enlace($link, 'Continuar con este ticket'));

        $this->enviar_email(
            $tecnico['correo_electronico'],
            $tecnico['nombre_completo'] ?? 'Técnico',
            $asunto,
            $cuerpo,
            $id_institucion,
            $id_reporte,
            'reporte_devuelto',
            true,
            (int)$reporte['id_tecnico_asignado']
        );
    }

    /**
     * RF-21: Reporte marcado como solucionado.
     * Avisa al gestor (debe validar) y al reportante (hito de avance de su ticket).
     */
    public function notificar_reporte_solucionado($id_reporte, $id_institucion, $numero_ticket) {
        $reporte = $this->obtener_reporte_detallado($id_reporte, $id_institucion);

        // Enlace directo a la pantalla de validación: antes el correo solo decía "valide y
        // cierre formalmente" sin ningún enlace, y no existía ningún botón en la interfaz
        // que llevara ahí (ver kanban), así que quien debía validar no tenía cómo hacerlo.
        $link_validar = config('app.url_base') . '/?controlador=cierre&accion=validar_solucion&id=' . $id_reporte;

        $asunto = "Reporte #{$numero_ticket} listo para validación";
        $cuerpo = $this->plantilla('Reporte Solucionado', [
            'Ticket' => $numero_ticket,
            'Clasificación' => htmlspecialchars(trim(($reporte['categoria'] ?? '') . (empty($reporte['subcategoria']) ? '' : ' / ' . $reporte['subcategoria']))),
            'Ubicación' => htmlspecialchars(trim(($reporte['sede'] ?? '') . (empty($reporte['referencia_ubicacion_libre']) ? '' : ' — ' . $reporte['referencia_ubicacion_libre']))),
        ], 'El técnico ha marcado el reporte como solucionado. Revise la evidencia y confirme si la solución es correcta:'
           . $this->boton_enlace($link_validar, 'Revisar y validar'));

        // Antes solo llegaba a Gestor. Rector y Admin de Institución ya tienen el permiso
        // de validar (validar_cerrar) pero nunca eran notificados de que había algo pendiente.
        // 'Admin de Institución' va literal: enviar_a_roles compara contra rol.nombre_rol
        // completo, no por prefijo, así que 'admin' no bastaría para encontrarlo.
        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor', 'rector', 'Admin de Institución']);

        if ($reporte && !empty($reporte['correo_reportante'])) {
            $link = config('app.url_base') . '/?controlador=reportes&accion=seguimiento&token='
                  . urlencode($reporte['token_seguimiento_publico'] ?? '');
            $cuerpo_rep = $this->plantilla('Su reporte fue solucionado', [
                'Ticket' => $numero_ticket,
                'Estado actual' => 'Solucionado, pendiente de validación',
            ], 'El técnico terminó la reparación de su reporte. Un gestor de la institución la revisará '
               . 'y confirmará el cierre. Puede ver el detalle, las fotos y los avances desde el siguiente enlace:'
               . $this->boton_enlace($link, 'Ver mi reporte'));

            $this->enviar_email(
                $reporte['correo_reportante'],
                $reporte['nombre_reportante'] ?? 'Reportante',
                "Su reporte #{$numero_ticket} fue solucionado",
                $cuerpo_rep,
                $id_institucion,
                $id_reporte,
                'reporte_solucionado_reportante',
                true,
                !empty($reporte['id_reportante']) ? (int)$reporte['id_reportante'] : null
            );
        }
    }

    /** RF-24: Reporte cerrado — avisa a Reportante y Rector */
    public function notificar_reporte_cerrado($id_reporte, $id_institucion, $numero_ticket, $email_reportante = null) {
        $asunto = "Su reporte #{$numero_ticket} ha sido cerrado";
        $cuerpo = $this->plantilla('Reporte Cerrado', [
            'Ticket' => $numero_ticket,
        ], 'Su reporte ha sido atendido y cerrado satisfactoriamente. Gracias por contribuir al mantenimiento de la institución.');

        // Enviar al reportante si tiene correo
        if ($email_reportante) {
            $this->enviar_email(
                $email_reportante, 'Reportante',
                $asunto, $cuerpo,
                $id_institucion, $id_reporte, 'reporte_cerrado'
            );
        }

        // Enviar al rector institucional
        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['rector']);
    }

    /** RN-13: SLA próximo a vencer — avisa a Gestor y Rector */
    public function notificar_sla_vencimiento_proximo($id_reporte, $id_institucion, $numero_ticket) {
        $asunto = "⚠ ALERTA SLA: Reporte #{$numero_ticket} vence pronto";
        $cuerpo = $this->plantilla('Alerta de SLA', [
            'Ticket' => $numero_ticket,
        ], 'El tiempo de atención del reporte está próximo a vencer. Tome acción inmediata.');

        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor', 'rector']);
    }

    /** SLA vencido — avisa a Gestor, Rector y Admin de Institución */
    public function notificar_sla_vencido($id_reporte, $id_institucion, $numero_ticket) {
        $asunto = "🚨 CRÍTICO SLA VENCIDO: Reporte #{$numero_ticket}";
        $cuerpo = $this->plantilla('SLA Vencido', [
            'Ticket' => $numero_ticket,
        ], 'El reporte ha superado el tiempo máximo de atención establecido en el SLA.');

        // 'admin' no coincidía con nada: enviar_a_roles compara contra
        // rol.nombre_rol completo, y el rol se llama 'Admin de Institución'.
        // El aviso más crítico del sistema no le llegaba nunca.
        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor', 'rector', 'Admin de Institución']);
    }

    /**
     * Cuántas personas recibirían un aviso dirigido a estos roles.
     * El cron lo usa para advertir cuando una institución no tiene a nadie
     * que pueda recibir las alertas de SLA: sin esto, los avisos se generan
     * y se descartan sin que nadie lo note.
     */
    public function contar_destinatarios_por_roles($id_institucion, array $roles) {
        $ids_rol = $this->ids_de_roles($roles);
        if (empty($ids_rol)) return 0;

        $marcadores = implode(',', array_fill(0, count($ids_rol), '?'));
        $sql = "SELECT COUNT(DISTINCT u.id_usuario) AS n
                FROM usuario u
                JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                WHERE u.id_institucion = ? AND u.activo = 1
                  AND ur.id_rol IN ({$marcadores})";

        $fila = $this->bd->ejecutar($sql, array_merge([$id_institucion], $ids_rol))
                         ->fetch(PDO::FETCH_ASSOC);

        return (int)($fila['n'] ?? 0);
    }

    /** RF-23: Encuesta de satisfacción enviada al reportante */
    public function notificar_encuesta($id_reporte, $id_institucion, $numero_ticket, $email_reportante, $link_encuesta) {
        $asunto = "Cuéntenos su experiencia — Reporte #{$numero_ticket}";
        $cuerpo = $this->plantilla('Encuesta de Satisfacción', [
            'Ticket' => $numero_ticket,
        ], 'Su reporte fue atendido. Por favor califique la atención recibida haciendo clic en el siguiente enlace:
            <p style="text-align:center;margin:20px 0;">
              <a href="' . htmlspecialchars($link_encuesta) . '"
                 style="background:#1a56db;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">
                 Responder encuesta
              </a>
            </p>');

        $this->enviar_email(
            $email_reportante, 'Reportante',
            $asunto, $cuerpo,
            $id_institucion, $id_reporte, 'encuesta'
        );
    }

    /** Recuperación de contraseña — envía link de reset al usuario */
    public function enviar_recuperacion_contrasena($email, $nombre, $link_reset) {
        $asunto = 'Recuperación de contraseña — ' . config('app.app_name');
        $cuerpo = $this->plantilla('Recuperación de Contraseña', [
            'Usuario' => htmlspecialchars($nombre),
        ], 'Recibimos una solicitud para restablecer la contraseña de tu cuenta.
            El enlace expira en <strong>1 hora</strong>.
            <p style="text-align:center;margin:24px 0;">
              <a href="' . htmlspecialchars($link_reset) . '"
                 style="background:#1a56db;color:#fff;padding:13px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;">
                 Restablecer contraseña
              </a>
            </p>
            <p style="font-size:13px;color:#6b7280;">Si no solicitaste este cambio, ignora este mensaje. Tu contraseña no será modificada.</p>');

        // No persistir en la tabla notificacion (registrar_bd=false): ese cuerpo lleva el
        // token de reseteo en texto plano, y registrar_en_bd() atribuye el registro al
        // usuario de la sesión actual — no necesariamente al dueño del correo destino,
        // ya que esta acción no requiere estar autenticado. Se envía solo por SMTP.
        $this->enviar_email($email, $nombre, $asunto, $cuerpo, null, null, 'reset_password', false);
    }

    /**
     * Alta de usuario: enlace para que elija su propia contraseña.
     *
     * Cierra el `// TODO: Enviar email con credenciales` que llevaba abierto
     * desde el principio. Hasta ahora la contraseña se mostraba una sola vez en
     * pantalla al administrador, que la hacía llegar por WhatsApp o por
     * teléfono: un canal que no controla nadie y que deja la credencial escrita
     * en la conversación de ambos.
     *
     * Se envía un enlace de un solo uso, como en la recuperación de contraseña,
     * en lugar de la contraseña en claro.
     */
    public function enviar_bienvenida($email, $nombre, $link_activacion, $institucion = '') {
        $app = config('app.app_name');
        $asunto = "Acceso a {$app}" . ($institucion !== '' ? " — {$institucion}" : '');

        $datos = ['Usuario' => htmlspecialchars($email)];
        if ($institucion !== '') {
            $datos['Institución'] = htmlspecialchars($institucion);
        }

        $cuerpo = $this->plantilla(
            'Bienvenido a ' . htmlspecialchars($app),
            $datos,
            'Se creó una cuenta a su nombre. Para entrar, elija su contraseña desde '
            . 'el siguiente enlace. Por seguridad caduca en una hora; si expira, '
            . 'puede pedir uno nuevo desde "¿Olvidó su contraseña?" en la pantalla '
            . 'de acceso.'
            . $this->boton_enlace($link_activacion, 'Elegir mi contraseña')
        );

        // registrar_bd = false: en el momento del alta el usuario aún no tiene
        // por qué aparecer en la campana de nadie, y el cuerpo lleva un enlace
        // con token que no conviene guardar en la tabla.
        return $this->enviar_email($email, $nombre, $asunto, $cuerpo, null, null, 'bienvenida', false);
    }

    /**
     * Notificaciones sin leer de un usuario, para la campana.
     *
     * Deliberadamente NO filtra por estado_envio. La versión anterior
     * (obtener_pendientes) sí lo hacía, lo que dejaba la campana al revés de
     * como debe funcionar: mostraba solo los avisos cuyo correo había fallado,
     * y los hacía desaparecer en cuanto el envío salía bien.
     *
     * Que sea independiente del correo es justo lo que convierte a la campana
     * en la red de seguridad cuando el SMTP se cae: el técnico ve que le
     * asignaron trabajo aunque el correo no llegue nunca.
     */
    public function obtener_no_leidas($id_usuario, $id_institucion, $limite = 15) {
        $limite = max(1, min((int)$limite, 50));

        return $this->bd->obtener_todos(
            'SELECT id_notificacion, id_reporte, tipo_evento, asunto, fecha_creacion
             FROM notificacion
             WHERE id_institucion = :inst
               AND id_usuario_destinatario = :usu
               AND fecha_leida IS NULL
             ORDER BY fecha_creacion DESC
             LIMIT ' . $limite,
            [':inst' => $id_institucion, ':usu' => $id_usuario]
        );
    }

    /** Cuántas notificaciones sin leer tiene el usuario (para el contador). */
    public function contar_no_leidas($id_usuario, $id_institucion) {
        $fila = $this->bd->obtener_uno(
            'SELECT COUNT(*) AS n FROM notificacion
             WHERE id_institucion = :inst
               AND id_usuario_destinatario = :usu
               AND fecha_leida IS NULL',
            [':inst' => $id_institucion, ':usu' => $id_usuario]
        );

        return (int)($fila['n'] ?? 0);
    }

    /**
     * Marca una notificación como leída.
     * El id de usuario e institución van en el WHERE, no solo en la búsqueda
     * previa: así nadie puede marcar como leída la notificación de otro
     * manipulando el id de la URL.
     */
    public function marcar_leida($id_notificacion, $id_usuario, $id_institucion) {
        return $this->bd->ejecutar(
            'UPDATE notificacion SET fecha_leida = NOW()
             WHERE id_notificacion = ? AND id_usuario_destinatario = ?
               AND id_institucion = ? AND fecha_leida IS NULL',
            [$id_notificacion, $id_usuario, $id_institucion]
        );
    }

    /** Marca todas las del usuario como leídas. */
    public function marcar_todas_leidas($id_usuario, $id_institucion) {
        return $this->bd->ejecutar(
            'UPDATE notificacion SET fecha_leida = NOW()
             WHERE id_usuario_destinatario = ? AND id_institucion = ? AND fecha_leida IS NULL',
            [$id_usuario, $id_institucion]
        );
    }

    /**
     * Correo de aviso al responsable técnico de la plataforma (no a un usuario
     * de una institución). Lo usa el cron de salud para reportar problemas que
     * de otro modo solo se verían entrando por SSH a mirar los logs.
     *
     * No se registra en la tabla notificacion: esa tabla es de avisos a
     * usuarios del sistema, y estos van a quien opera la plataforma.
     *
     * El destino sale de ALERTA_EMAIL, o BACKUP_ALERTA_EMAIL (ya se usaba para
     * los fallos de respaldo), o en último caso el remitente configurado.
     */
    public function enviar_alerta_administrador($asunto, $cuerpo_html) {
        $destino = getenv('ALERTA_EMAIL')
            ?: (getenv('BACKUP_ALERTA_EMAIL') ?: ($this->smtp['from_email'] ?? ''));

        if (empty($destino)) {
            $this->log('No hay destinatario para alertas de administración');
            return false;
        }

        $error = $this->enviar_smtp($destino, 'Administración', $asunto, $cuerpo_html);

        if ($error === null) {
            $this->log("ALERTA ADMIN enviada a {$destino} [{$asunto}]");
            return true;
        }

        $this->log("ALERTA ADMIN fallida a {$destino}: {$error}");
        return false;
    }

    /** Marcar notificación como enviada */
    public function marcar_enviada($id_notificacion) {
        $this->bd->actualizar(
            'notificacion',
            ['estado_envio' => 'enviado', 'fecha_enviada' => date('Y-m-d H:i:s')],
            'id_notificacion = :id',
            [':id' => $id_notificacion]
        );
    }

    // ─── Internos ────────────────────────────────────────────────────────────

    /**
     * Traduce nombres de rol a identificadores.
     *
     * Comparar por nombre es frágil: los nombres llevan acentos y basta con una
     * base importada con la codificación equivocada para que la comparación deje
     * de coincidir y las notificaciones se dejen de enviar sin ningún error.
     * Ocurre de verdad: en la base de desarrollo 'Admin de Institución' y
     * 'Técnico' están guardados con los bytes corruptos.
     *
     * Se aceptan los nombres que ya usaban los llamadores para no tener que
     * cambiarlos todos de golpe.
     */
    private function ids_de_roles(array $roles) {
        $mapa = [
            'reportante'            => ROL_REPORTANTE,
            'tecnico'               => ROL_TECNICO,
            'técnico'               => ROL_TECNICO,
            'gestor'                => ROL_GESTOR,
            'rector'                => ROL_RECTOR,
            'admin'                 => ROL_ADMIN,
            'admin de institucion'  => ROL_ADMIN,
            'admin de institución'  => ROL_ADMIN,
            'superadministrador'    => ROL_SUPERADMIN,
        ];

        $ids = [];
        foreach ($roles as $rol) {
            $clave = mb_strtolower(trim((string) $rol));
            if (isset($mapa[$clave])) {
                $ids[] = $mapa[$clave];
            } else {
                $this->log("Rol desconocido al enviar notificación: '{$rol}'");
            }
        }

        return array_values(array_unique($ids));
    }

    /** Busca todos los usuarios con alguno de los roles dados y les envía el email */
    private function enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, array $roles) {
        $ids_rol = $this->ids_de_roles($roles);
        if (empty($ids_rol)) return;

        $placeholders = implode(',', array_fill(0, count($ids_rol), '?'));
        $sql = "SELECT u.id_usuario, u.correo_electronico, u.nombre_completo
                FROM usuario u
                JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                WHERE u.id_institucion = ?
                  AND u.activo = 1
                  AND ur.id_rol IN ({$placeholders})
                GROUP BY u.id_usuario";

        $params   = array_merge([$id_institucion], $ids_rol);
        $usuarios = $this->bd->ejecutar($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usuarios as $u) {
            $this->enviar_email(
                $u['correo_electronico'],
                $u['nombre_completo'] ?? '',
                $asunto, $cuerpo,
                $id_institucion, $id_reporte,
                'notificacion_rol',
                true,
                (int)$u['id_usuario']
            );
        }
    }

    /** Reporte con los nombres de sede, categoría, subcategoría, urgencia y estado ya resueltos */
    private function obtener_reporte_detallado($id_reporte, $id_institucion) {
        $sql = 'SELECT r.*,
                       s.nombre  AS sede,
                       c.nombre  AS categoria,
                       sc.nombre AS subcategoria,
                       u.nombre  AS urgencia,
                       e.nombre  AS estado
                FROM reporte r
                LEFT JOIN sede         s  ON s.id_sede = r.id_sede
                LEFT JOIN categoria    c  ON c.id_categoria = r.id_categoria
                LEFT JOIN subcategoria sc ON sc.id_subcategoria = r.id_subcategoria
                LEFT JOIN urgencia     u  ON u.id_urgencia = r.id_urgencia_calculada
                LEFT JOIN estado       e  ON e.id_estado = r.id_estado
                WHERE r.id_reporte = :r AND r.id_institucion = :i';
        return $this->bd->obtener_uno($sql, [':r' => $id_reporte, ':i' => $id_institucion]);
    }

    /** Botón de acción para el cuerpo del correo (estilos inline: los clientes de correo no cargan CSS externo) */
    private function boton_enlace($url, $texto) {
        return '<p style="text-align:center;margin:22px 0 6px;">'
             . '<a href="' . htmlspecialchars($url) . '" '
             . 'style="background:#1F77B0;color:#ffffff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;">'
             . htmlspecialchars($texto) . '</a></p>';
    }

    private function fecha_legible($fecha) {
        if (empty($fecha)) return '';
        $ts = strtotime($fecha);
        return $ts ? date('d/m/Y \a \l\a\s H:i', $ts) : htmlspecialchars($fecha);
    }

    /**
     * Envía el email por SMTP y lo registra en la tabla notificacion.
     * $id_usuario_dest es el usuario al que pertenece la notificación in-app;
     * si el destinatario no tiene cuenta (invitado) se pasa null y solo se envía el correo.
     */
    private function enviar_email($destino, $nombre_dest, $asunto, $cuerpo_html, $id_institucion, $id_reporte, $tipo_evento, $registrar_bd = true, $id_usuario_dest = null) {
        if (empty($destino) || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $this->log("Destino inválido u omitido para [{$asunto}]");
            return false;
        }

        // Registrar en BD antes de intentar enviar (salvo que el llamador pida lo contrario)
        $id_notif = ($registrar_bd && $id_usuario_dest)
            ? $this->registrar_en_bd($id_institucion, $id_reporte, $asunto, $cuerpo_html, $tipo_evento, $id_usuario_dest)
            : null;

        $error = $this->enviar_smtp($destino, $nombre_dest, $asunto, $cuerpo_html);

        if ($error === null) {
            if ($id_notif) $this->marcar_enviada($id_notif);
            $this->log("OK: {$destino} [{$asunto}]");
            return true;
        }

        // El fallo queda anotado en la fila: así el reintento por cron sabe
        // cuántas veces se ha probado y por qué falló. Antes el valor de
        // retorno se descartaba y la notificación se perdía en silencio.
        if ($id_notif) $this->registrar_fallo($id_notif, $error);
        $this->log("ERROR enviando a {$destino}: {$error}");
        return false;
    }

    /**
     * Envío SMTP puro, sin tocar la base de datos.
     * Devuelve null si se envió, o el motivo del fallo como texto.
     * Lo usan tanto el envío en caliente como el reintento por cron.
     */
    private function enviar_smtp($destino, $nombre_dest, $asunto, $cuerpo_html) {
        if (empty($this->smtp['username'])) {
            return 'SMTP no configurado (falta SMTP_USER en el .env)';
        }

        $autoload = ROOT_PATH . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return 'vendor/autoload.php no encontrado — ejecutar composer install';
        }
        require_once $autoload;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host        = $this->smtp['host'];
            $mail->SMTPAuth    = true;
            $mail->Username    = $this->smtp['username'];
            $mail->Password    = $this->smtp['password'];
            $mail->SMTPSecure  = ((int)$this->smtp['port'] === 587)
                                    ? PHPMailer::ENCRYPTION_STARTTLS
                                    : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port        = (int)$this->smtp['port'];
            $mail->CharSet     = 'UTF-8';

            $mail->setFrom($this->smtp['from_email'], $this->smtp['from_name']);
            $mail->addAddress($destino, $nombre_dest);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpo_html;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $cuerpo_html));

            $mail->send();
            return null;

        } catch (MailException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Anota un intento fallido. Al llegar a $max_intentos la fila pasa a
     * 'fallido' y el cron deja de reintentarla.
     */
    public function registrar_fallo($id_notificacion, $razon, $max_intentos = self::MAX_INTENTOS_ENVIO) {
        $fila = $this->bd->obtener_uno(
            'SELECT intentos FROM notificacion WHERE id_notificacion = :id',
            [':id' => $id_notificacion]
        );
        $intentos = (int)($fila['intentos'] ?? 0) + 1;

        $this->bd->actualizar(
            'notificacion',
            [
                'intentos'     => $intentos,
                'razon_fallo'  => mb_substr((string)$razon, 0, 1000),
                'estado_envio' => $intentos >= $max_intentos ? 'fallido' : 'pendiente',
            ],
            'id_notificacion = :id',
            [':id' => $id_notificacion]
        );

        return $intentos;
    }

    /**
     * Reintenta las notificaciones que quedaron sin enviar.
     *
     * El envío normal es síncrono: si el SMTP está caído en ese momento, el
     * correo se perdía para siempre porque nada volvía a intentarlo. La tabla
     * ya estaba preparada para esto (índice idx_noti_cola, columnas intentos y
     * razon_fallo); solo faltaba quien la recorriera.
     *
     * @return array Resumen: procesadas, enviadas, fallidas, agotadas.
     */
    public function reintentar_pendientes($limite = 50, $max_intentos = self::MAX_INTENTOS_ENVIO) {
        $limite = max(1, min((int)$limite, 200));

        $pendientes = $this->bd->obtener_todos(
            'SELECT id_notificacion, id_usuario_destinatario, asunto, cuerpo_html, intentos,
                    TIMESTAMPDIFF(HOUR, fecha_programada, NOW()) AS horas
             FROM notificacion
             WHERE estado_envio = :estado AND intentos < :max
             ORDER BY fecha_programada ASC
             LIMIT ' . $limite,
            [':estado' => 'pendiente', ':max' => $max_intentos]
        );

        $resumen = ['procesadas' => 0, 'enviadas' => 0, 'fallidas' => 0, 'agotadas' => 0, 'caducadas' => 0];

        foreach ($pendientes as $n) {
            $resumen['procesadas']++;

            // Demasiado viejo para seguir intentándolo: entregarlo ahora haría
            // más daño que bien. Se cierra como fallido, con el motivo visible.
            if ((int)$n['horas'] >= self::HORAS_CADUCIDAD_AVISO) {
                $this->bd->actualizar(
                    'notificacion',
                    [
                        'estado_envio' => 'fallido',
                        'razon_fallo'  => 'Caducada: llevaba más de '
                            . self::HORAS_CADUCIDAD_AVISO . ' horas sin poder enviarse',
                    ],
                    'id_notificacion = :id',
                    [':id' => $n['id_notificacion']]
                );
                $this->log("CADUCADA ({$n['horas']}h): [{$n['asunto']}]");
                $resumen['caducadas']++;
                continue;
            }

            $usuario = $this->obtener_usuario($n['id_usuario_destinatario']);

            if (!$usuario || empty($usuario['correo_electronico'])) {
                $intentos = $this->registrar_fallo($n['id_notificacion'], 'Destinatario sin correo', $max_intentos);
                $resumen[$intentos >= $max_intentos ? 'agotadas' : 'fallidas']++;
                continue;
            }

            $error = $this->enviar_smtp(
                $usuario['correo_electronico'],
                $usuario['nombre_completo'] ?? '',
                $n['asunto'],
                $n['cuerpo_html']
            );

            if ($error === null) {
                $this->marcar_enviada($n['id_notificacion']);
                $this->log("REINTENTO OK: {$usuario['correo_electronico']} [{$n['asunto']}]");
                $resumen['enviadas']++;
                continue;
            }

            $intentos = $this->registrar_fallo($n['id_notificacion'], $error, $max_intentos);
            $this->log("REINTENTO FALLO ({$intentos}/{$max_intentos}): {$usuario['correo_electronico']} — {$error}");
            $resumen[$intentos >= $max_intentos ? 'agotadas' : 'fallidas']++;
        }

        return $resumen;
    }

    /**
     * Registra la notificación en la tabla para el historial in-app del destinatario.
     * Antes se registraba a nombre del usuario con sesión (quien dispara el envío), de
     * modo que el técnico nunca veía la suya y el gestor acumulaba las de todos.
     */
    private function registrar_en_bd($id_institucion, $id_reporte, $asunto, $cuerpo_html, $tipo_evento, $id_usuario_dest) {
        if (!$id_usuario_dest) return null;

        try {
            return $this->bd->insertar('notificacion', [
                'id_institucion'          => $id_institucion,
                'id_reporte'              => $id_reporte,
                'tipo_evento'             => $tipo_evento,
                'asunto'                  => $asunto,
                'cuerpo_html'             => $cuerpo_html,
                'id_usuario_destinatario' => $id_usuario_dest,
                'estado_envio'            => 'pendiente',
            ]);
        } catch (Exception $e) {
            $this->log("BD insert error: " . $e->getMessage());
            return null;
        }
    }

    /** Genera el HTML del email con plantilla corporativa */
    private function plantilla($titulo, array $datos, $intro = '') {
        $url_base = config('app.url_base');
        $app_nombre = htmlspecialchars(config('app.app_name'));
        $app_nombre_completo = htmlspecialchars(config('app.app_full_name'));
        $app_nombre_version = htmlspecialchars(config('app.app_name_version'));
        $filas = '';
        foreach ($datos as $campo => $valor) {
            $filas .= "<tr>
                <td style='padding:8px 12px;font-weight:600;color:#374151;width:140px;'>{$campo}</td>
                <td style='padding:8px 12px;color:#4b5563;'>{$valor}</td>
            </tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center" style="padding:30px 15px;">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);max-width:600px;">

        <!-- Cabecera -->
        <tr>
          <td style="background:#0b1929;padding:24px 32px;text-align:center;">
            <img src="{$url_base}/img/logo_ana.png" alt="{$app_nombre}" style="height:64px;border-radius:10px;background:#ffffff;">
            <p style="margin:8px 0 0;color:rgba(255,255,255,.65);font-size:12px;letter-spacing:1px;text-transform:uppercase;">
              {$app_nombre_completo}
            </p>
          </td>
        </tr>

        <!-- Cuerpo -->
        <tr>
          <td style="padding:32px;">
            <h2 style="margin:0 0 8px;color:#111827;font-size:20px;">{$titulo}</h2>
            <p style="color:#6b7280;margin:0 0 24px;line-height:1.6;">{$intro}</p>

            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;border-collapse:collapse;">
              {$filas}
            </table>
          </td>
        </tr>

        <!-- Pie -->
        <tr>
          <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 32px;text-align:center;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">
              Este es un mensaje automático de {$app_nombre_version}. No responda a este correo.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private function obtener_usuario($id_usuario) {
        return $this->bd->obtener_uno(
            'SELECT id_usuario, correo_electronico, nombre_completo FROM usuario WHERE id_usuario = :id',
            [':id' => $id_usuario]
        );
    }

    private function log($msg) {
        if (!is_dir(LOG_DIR)) @mkdir(LOG_DIR, 0755, true);
        @file_put_contents(
            LOG_DIR . '/notificaciones.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
            FILE_APPEND
        );
    }

}
