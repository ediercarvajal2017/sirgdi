<?php
// Servicio de Notificación — envío real por SMTP (PHPMailer)
// Cubre: RF-08, RF-12, RF-21, RF-23, RF-24, RN-13

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class ServicioNotificacion {
    private $bd;
    private $smtp;

    public function __construct() {
        $this->bd   = BaseDatos::obtener();
        $this->smtp = config('smtp');
    }

    // ─── API pública ────────────────────────────────────────────────────────

    /** RF-08: Nuevo reporte — avisa a Gestor y Rector */
    public function notificar_nuevo_reporte($id_reporte, $id_institucion, $numero_ticket, $descripcion) {
        $asunto  = "Nuevo reporte #{$numero_ticket}";
        $cuerpo  = $this->plantilla('Nuevo Reporte Registrado', [
            'Ticket'       => $numero_ticket,
            'Descripción'  => htmlspecialchars(substr($descripcion, 0, 200)),
        ], 'Se ha registrado un nuevo reporte que requiere atención.');

        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor', 'rector']);
    }

    /** RF-12: Técnico asignado — avisa al técnico */
    public function notificar_asignacion_tecnico($id_reporte, $id_institucion, $id_tecnico, $numero_ticket) {
        $tecnico = $this->obtener_usuario($id_tecnico);
        if (!$tecnico) return;

        $asunto = "Se le asignó el reporte #{$numero_ticket}";
        $cuerpo = $this->plantilla('Reporte Asignado', [
            'Ticket'   => $numero_ticket,
            'Técnico'  => htmlspecialchars($tecnico['nombre_completo'] ?? ''),
        ], 'Se le ha asignado un nuevo reporte para su atención técnica.');

        $this->enviar_email(
            $tecnico['correo_electronico'],
            $tecnico['nombre_completo'] ?? 'Técnico',
            $asunto,
            $cuerpo,
            $id_institucion,
            $id_reporte,
            'reporte_asignado'
        );
    }

    /** RF-21: Reporte marcado como solucionado — avisa al Gestor */
    public function notificar_reporte_solucionado($id_reporte, $id_institucion, $numero_ticket) {
        $asunto = "Reporte #{$numero_ticket} listo para validación";
        $cuerpo = $this->plantilla('Reporte Solucionado', [
            'Ticket' => $numero_ticket,
        ], 'El técnico ha marcado el reporte como solucionado. Por favor valide y cierre formalmente.');

        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor']);
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

    /** SLA vencido — avisa a Gestor, Rector y Admin */
    public function notificar_sla_vencido($id_reporte, $id_institucion, $numero_ticket) {
        $asunto = "🚨 CRÍTICO SLA VENCIDO: Reporte #{$numero_ticket}";
        $cuerpo = $this->plantilla('SLA Vencido', [
            'Ticket' => $numero_ticket,
        ], 'El reporte ha superado el tiempo máximo de atención establecido en el SLA.');

        $this->enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, ['gestor', 'rector', 'admin']);
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

    /** Obtener notificaciones pendientes de un usuario (para campana in-app) */
    public function obtener_pendientes($id_usuario, $id_institucion) {
        $sql = 'SELECT * FROM notificacion
                WHERE id_institucion = :inst
                  AND id_usuario_destinatario = :usu
                  AND estado_envio = "pendiente"
                ORDER BY fecha_creacion DESC
                LIMIT 50';

        return $this->bd->obtener_todos($sql, [
            ':inst' => $id_institucion,
            ':usu'  => $id_usuario,
        ]);
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

    /** Busca todos los usuarios con alguno de los roles dados y les envía el email */
    private function enviar_a_roles($id_institucion, $id_reporte, $asunto, $cuerpo, array $roles) {
        if (empty($roles)) return;

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT u.id_usuario, u.correo_electronico, u.nombre_completo
                FROM usuario u
                JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                JOIN rol r          ON r.id_rol = ur.id_rol
                WHERE u.id_institucion = ?
                  AND u.activo = 1
                  AND r.nombre_rol IN ({$placeholders})
                GROUP BY u.id_usuario";

        $params   = array_merge([$id_institucion], $roles);
        $usuarios = $this->bd->ejecutar($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usuarios as $u) {
            $this->enviar_email(
                $u['correo_electronico'],
                $u['nombre_completo'] ?? '',
                $asunto, $cuerpo,
                $id_institucion, $id_reporte,
                'notificacion_rol'
            );
        }
    }

    /** Envía el email por SMTP y registra en la tabla notificacion */
    private function enviar_email($destino, $nombre_dest, $asunto, $cuerpo_html, $id_institucion, $id_reporte, $tipo_evento) {
        // Registrar en BD antes de intentar enviar
        $id_notif = $this->registrar_en_bd($id_institucion, $id_reporte, $asunto, $cuerpo_html, $tipo_evento);

        // Si no hay SMTP configurado, solo loggear
        if (empty($this->smtp['username'])) {
            $this->log("SMTP no configurado — email no enviado a {$destino} [{$asunto}]");
            return false;
        }

        $autoload = ROOT_PATH . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->log("vendor/autoload.php no encontrado — instalar dependencias con: composer install");
            return false;
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

            if ($id_notif) $this->marcar_enviada($id_notif);
            $this->log("OK: {$destino} [{$asunto}]");
            return true;

        } catch (MailException $e) {
            $this->log("ERROR enviando a {$destino}: " . $e->getMessage());
            return false;
        }
    }

    /** Registra la notificación en la tabla para el historial in-app */
    private function registrar_en_bd($id_institucion, $id_reporte, $asunto, $cuerpo_html, $tipo_evento) {
        $id_usuario_dest = $_SESSION['id_usuario'] ?? null;
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
            <img src="{$url_base}/img/logo_sirgdi.png" alt="SIRGDI" style="height:50px;border-radius:8px;">
            <p style="margin:8px 0 0;color:rgba(255,255,255,.65);font-size:12px;letter-spacing:1px;">
              SISTEMA DE REPORTE Y GESTIÓN DE DAÑOS
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
              Este es un mensaje automático de SIRGDI v2.0. No responda a este correo.
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
