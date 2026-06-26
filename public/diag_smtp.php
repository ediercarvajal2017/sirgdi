<?php
// Diagnóstico SMTP — ELIMINAR después de usar
// Acceder en: https://mantenimiento.ediertech.com/diag_smtp.php?clave=sirgdi2026

if (($_GET['clave'] ?? '') !== 'sirgdi2026') {
    http_response_code(403);
    die('Acceso denegado.');
}

// Cargar config
$config_path = dirname(__DIR__) . '/configuracion/config.php';
if (!file_exists($config_path)) {
    die('❌ config.php no encontrado en: ' . $config_path);
}
require_once $config_path;

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;font-size:13px;padding:20px;">';
echo "=== DIAGNÓSTICO SIRGDI SMTP ===\n\n";

// 1. Ruta del proyecto
echo "ROOT_PATH: " . ROOT_PATH . "\n";

// 2. ¿Existe el .env?
$env_file = ROOT_PATH . '/.env';
echo ".env existe: " . (file_exists($env_file) ? 'SÍ' : '❌ NO') . "\n";
echo ".env ruta: $env_file\n\n";

// 3. Variables SMTP cargadas
$smtp_host  = getenv('SMTP_HOST');
$smtp_port  = getenv('SMTP_PORT');
$smtp_user  = getenv('SMTP_USER');
$smtp_pass  = getenv('SMTP_PASS');
$smtp_from  = getenv('SMTP_FROM_EMAIL');
$app_url    = getenv('APP_URL');
$enc_key    = getenv('ENCRYPTION_KEY');

echo "SMTP_HOST:      " . ($smtp_host ?: '❌ VACÍO') . "\n";
echo "SMTP_PORT:      " . ($smtp_port ?: '❌ VACÍO') . "\n";
echo "SMTP_USER:      " . ($smtp_user ?: '❌ VACÍO') . "\n";
echo "SMTP_PASS:      " . ($smtp_pass ? '***OK (' . strlen($smtp_pass) . ' chars)' : '❌ VACÍO') . "\n";
echo "SMTP_FROM_EMAIL:" . ($smtp_from ?: '❌ VACÍO') . "\n";
echo "APP_URL:        " . ($app_url ?: '❌ VACÍO') . "\n";
echo "ENCRYPTION_KEY: " . ($enc_key ? strlen($enc_key) . " chars (" . (strlen($enc_key) === 64 ? '✓ válida' : '❌ debe ser 64') . ")" : '❌ VACÍO') . "\n\n";

// 4. ¿Existe vendor/autoload.php?
$autoload = ROOT_PATH . '/vendor/autoload.php';
echo "vendor/autoload.php: " . (file_exists($autoload) ? '✓ existe' : '❌ NO existe') . "\n\n";

// 5. Log de notificaciones
$log_file = ROOT_PATH . '/almacenamiento/logs/notificaciones.log';
echo "Log path: $log_file\n";
echo "Log existe: " . (file_exists($log_file) ? 'SÍ' : 'NO (aún sin entradas)') . "\n";
if (file_exists($log_file)) {
    $lineas = file($log_file, FILE_IGNORE_NEW_LINES);
    $ultimas = array_slice($lineas, -10);
    echo "Últimas 10 líneas del log:\n";
    foreach ($ultimas as $l) echo "  $l\n";
}
echo "\n";

// 6. Intentar enviar email de prueba si todo está OK
if ($smtp_user && $smtp_pass && file_exists($autoload)) {
    echo "=== PRUEBA DE ENVÍO ===\n";
    require_once $autoload;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception as MailException;

    $destino = $smtp_user; // enviarse a sí mismo como prueba

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = ((int)$smtp_port === 587)
                            ? PHPMailer::ENCRYPTION_STARTTLS
                            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)$smtp_port;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = 2; // mostrar comunicación SMTP
        $mail->Debugoutput = function($str, $level) {
            echo "  SMTP> " . htmlspecialchars(trim($str)) . "\n";
        };

        $mail->setFrom($smtp_from ?: $smtp_user, 'SIRGDI Diagnóstico');
        $mail->addAddress($destino);
        $mail->isHTML(false);
        $mail->Subject = 'Prueba SMTP SIRGDI - ' . date('Y-m-d H:i:s');
        $mail->Body    = 'Si recibes este mensaje, el SMTP funciona correctamente.';

        $mail->send();
        echo "\n✅ EMAIL ENVIADO a $destino\n";
    } catch (MailException $e) {
        echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ Prueba de envío omitida — SMTP no configurado o vendor faltante.\n";
}

echo '</pre>';
