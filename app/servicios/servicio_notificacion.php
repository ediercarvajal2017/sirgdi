<?php
// Servicio de Notificación (Fase 2 - stub para RF-08, RF-23, RF-24)
// Envía notificaciones por email
// En v1.0: logging only
// En v2.0: integrar PHPMailer o similar

class ServicioNotificacion {
    private $bd;
    private $smtp_config;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
        $this->smtp_config = config('smtp');
    }

    /**
     * Notificar: nuevo reporte creado (RF-08)
     * Envía a: Gestor, Rector
     */
    public function notificar_nuevo_reporte($id_reporte, $id_institucion, $numero_ticket, $descripcion) {
        $mensaje = sprintf(
            "Nuevo reporte: %s - %s\nContacte a la institución para más detalles.",
            $numero_ticket,
            substr($descripcion, 0, 100)
        );

        $this->registrar_notificacion(
            $id_institucion,
            'nuevo_reporte',
            $id_reporte,
            $mensaje,
            ['gestor', 'rector']
        );
    }

    /**
     * Notificar: reporte asignado a técnico (RF-12)
     * Envía a: Técnico asignado
     */
    public function notificar_asignacion_tecnico($id_reporte, $id_institucion, $id_tecnico, $numero_ticket) {
        $mensaje = sprintf(
            "Se le ha asignado el reporte: %s",
            $numero_ticket
        );

        $this->registrar_notificacion(
            $id_institucion,
            'reporte_asignado',
            $id_reporte,
            $mensaje,
            ['tecnico'],
            $id_tecnico
        );
    }

    /**
     * Notificar: reporte solucionado (RF-21)
     * Envía a: Gestor (para validación)
     */
    public function notificar_reporte_solucionado($id_reporte, $id_institucion, $numero_ticket) {
        $mensaje = sprintf(
            "El reporte %s ha sido marcado como solucionado. Requiere validación.",
            $numero_ticket
        );

        $this->registrar_notificacion(
            $id_institucion,
            'reporte_solucionado',
            $id_reporte,
            $mensaje,
            ['gestor']
        );
    }

    /**
     * Notificar: reporte cerrado (RF-24)
     * Envía a: Reportante + Rector (cierre notificación)
     */
    public function notificar_reporte_cerrado($id_reporte, $id_institucion, $numero_ticket, $email_reportante = null) {
        $mensaje = sprintf(
            "Su reporte %s ha sido cerrado. Gracias por reportar el daño.",
            $numero_ticket
        );

        // Registrar en BD
        $this->registrar_notificacion(
            $id_institucion,
            'reporte_cerrado',
            $id_reporte,
            $mensaje,
            ['reportante', 'rector']
        );

        // Si se proporciona email del reportante, enviar directo
        if ($email_reportante) {
            $this->enviar_email(
                $email_reportante,
                'Reporte Cerrado: ' . $numero_ticket,
                $mensaje
            );
        }
    }

    /**
     * Notificar: SLA cerca de vencerse (RN-13)
     * Envía a: Gestor, Rector
     */
    public function notificar_sla_vencimiento_proximo($id_reporte, $id_institucion, $numero_ticket) {
        $mensaje = sprintf(
            "ALERTA: El reporte %s está cerca de vencer el SLA.",
            $numero_ticket
        );

        $this->registrar_notificacion(
            $id_institucion,
            'sla_alerta',
            $id_reporte,
            $mensaje,
            ['gestor', 'rector']
        );
    }

    /**
     * Notificar: SLA vencido
     * Envía a: Gestor, Rector, Admin
     */
    public function notificar_sla_vencido($id_reporte, $id_institucion, $numero_ticket) {
        $mensaje = sprintf(
            "CRÍTICO: El reporte %s ha vencido el SLA.",
            $numero_ticket
        );

        $this->registrar_notificacion(
            $id_institucion,
            'sla_vencido',
            $id_reporte,
            $mensaje,
            ['gestor', 'rector', 'admin']
        );
    }

    /**
     * Registrar notificación en BD (v1.0: sin envío de email)
     * En v2.0: integrar cola de emails con reintentos
     */
    private function registrar_notificacion($id_institucion, $tipo, $id_reporte, $mensaje, $roles = [], $id_usuario_destino = null) {
        // Para v1.0: usar usuario de sesión actual como destinatario si no se especifica
        if (!$id_usuario_destino && isset($_SESSION['id_usuario'])) {
            $id_usuario_destino = $_SESSION['id_usuario'];
        }

        if (!$id_usuario_destino) {
            return null; // No registrar si no hay destinatario
        }

        $datos_notificacion = [
            'id_institucion' => $id_institucion,
            'id_reporte' => $id_reporte,
            'tipo_evento' => $tipo,
            'asunto' => $tipo,
            'cuerpo_html' => $mensaje,
            'id_usuario_destinatario' => $id_usuario_destino,
            'estado_envio' => 'pendiente',
        ];

        $id_notificacion = $this->bd->insertar('notificacion', $datos_notificacion);

        // Log para auditoría
        $log_msg = sprintf(
            "[%s] Notificación registrada - ID: %d, Tipo: %s, Reporte: %d, Roles: %s\n",
            date('Y-m-d H:i:s'),
            $id_notificacion,
            $tipo,
            $id_reporte,
            implode(',', $roles)
        );

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents(LOG_DIR . '/notificaciones.log', $log_msg, FILE_APPEND);

        return $id_notificacion;
    }

    /**
     * Enviar email (placeholder - implementar con PHPMailer en v2.0)
     */
    private function enviar_email($destinatario, $asunto, $mensaje) {
        // v1.0: Solo loggear
        $log_msg = sprintf(
            "[%s] Email encolado - Destinatario: %s, Asunto: %s\n",
            date('Y-m-d H:i:s'),
            $destinatario,
            $asunto
        );

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents(LOG_DIR . '/emails.log', $log_msg, FILE_APPEND);

        // TODO: v2.0 - Integrar PHPMailer
        // $mail = new PHPMailer();
        // $mail->isSMTP();
        // $mail->Host = $this->smtp_config['host'];
        // $mail->SMTPAuth = true;
        // $mail->Username = $this->smtp_config['username'];
        // $mail->Password = $this->smtp_config['password'];
        // $mail->SMTPSecure = 'tls';
        // $mail->Port = $this->smtp_config['port'];
        // $mail->setFrom($this->smtp_config['from_email'], $this->smtp_config['from_name']);
        // $mail->addAddress($destinatario);
        // $mail->Subject = $asunto;
        // $mail->Body = $mensaje;
        // return $mail->send();
    }

    /**
     * Obtener notificaciones pendientes de un usuario
     */
    public function obtener_pendientes($id_usuario, $id_institucion) {
        $sql = 'SELECT * FROM notificacion
                WHERE id_institucion = :id_institucion
                AND id_usuario_destinatario = :id_usuario
                AND estado_envio = :estado
                ORDER BY fecha_creacion DESC
                LIMIT 50';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
            ':id_usuario' => $id_usuario,
            ':estado' => 'pendiente',
        ]);
    }

    /**
     * Marcar notificación como enviada
     */
    public function marcar_enviada($id_notificacion) {
        $this->bd->actualizar(
            'notificacion',
            ['estado_envio' => 'enviado', 'fecha_enviada' => date('Y-m-d H:i:s')],
            'id_notificacion = :id',
            [':id' => $id_notificacion]
        );
    }
}
