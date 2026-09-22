<?php
// Envía un correo de alerta cuando el backup diario falla. Reutiliza la config y
// PHPMailer reales del proyecto (mismo configuracion/config.php y vendor/ que usa
// producción), para no depender de un `sendmail` local que puede no estar
// configurado en el hosting compartido.
//
// Invocado desde backup_sirgdi.sh vía `trap ... ERR`.
// Uso: php backup_alerta.php <ruta_raiz_del_sitio> "<mensaje del error>"

if ($argc < 3) {
    fwrite(STDERR, "Uso: php backup_alerta.php <ruta_raiz_del_sitio> \"<mensaje>\"\n");
    exit(1);
}

$sitio = rtrim($argv[1], '/');
require_once $sitio . '/configuracion/config.php';
require_once $sitio . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mensaje = $argv[2];
$smtp = config('smtp');
$destino = getenv('BACKUP_ALERTA_EMAIL') ?: $smtp['from_email'];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->Port = $smtp['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->setFrom($smtp['from_email'], $smtp['from_name']);
    $mail->addAddress($destino);
    $mail->Subject = '[ANA] Falló el backup diario';
    $mail->Body = $mensaje . "\n\nRevisar el log completo en el servidor: ~/backups/backup.log";
    $mail->send();
} catch (Exception $e) {
    fwrite(STDERR, 'No se pudo enviar la alerta de backup: ' . $mail->ErrorInfo . "\n");
    exit(1);
}
