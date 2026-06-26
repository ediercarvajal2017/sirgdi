<?php
// Diagnóstico SMTP — ELIMINAR después de usar
// Acceder en: https://mantenimiento.ediertech.com/diag_smtp.php?clave=sirgdi2026

if (($_GET['clave'] ?? '') !== 'sirgdi2026') {
    http_response_code(403);
    die('Acceso denegado.');
}

$config_path = dirname(__DIR__) . '/configuracion/config.php';
if (!file_exists($config_path)) {
    die('config.php no encontrado en: ' . $config_path);
}
require_once $config_path;

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;font-size:13px;padding:20px;background:#111;color:#0f0;">';
echo "=== DIAGNÓSTICO SIRGDI SMTP ===\n\n";

// Rutas
echo "ROOT_PATH : " . ROOT_PATH . "\n";
$env_file = ROOT_PATH . '/.env';
echo ".env ruta  : $env_file\n";
echo ".env existe: " . (file_exists($env_file) ? 'SI' : 'NO') . "\n\n";

// Variables SMTP
$smtp_host = getenv('SMTP_HOST');
$smtp_port = getenv('SMTP_PORT');
$smtp_user = getenv('SMTP_USER');
$smtp_pass = getenv('SMTP_PASS');
$smtp_from = getenv('SMTP_FROM_EMAIL');
$app_url   = getenv('APP_URL');
$enc_key   = getenv('ENCRYPTION_KEY');

echo "SMTP_HOST       : " . ($smtp_host ?: '--- VACIO ---') . "\n";
echo "SMTP_PORT       : " . ($smtp_port ?: '--- VACIO ---') . "\n";
echo "SMTP_USER       : " . ($smtp_user ?: '--- VACIO ---') . "\n";
echo "SMTP_PASS       : " . ($smtp_pass ? 'OK (' . strlen($smtp_pass) . ' chars)' : '--- VACIO ---') . "\n";
echo "SMTP_FROM_EMAIL : " . ($smtp_from ?: '--- VACIO ---') . "\n";
echo "APP_URL         : " . ($app_url   ?: '--- VACIO ---') . "\n";
echo "ENCRYPTION_KEY  : " . ($enc_key ? strlen($enc_key) . " chars" . (strlen($enc_key) === 64 ? " OK" : " ERROR debe ser 64") : '--- VACIO ---') . "\n";
echo "PHP version     : " . PHP_VERSION . "\n\n";

// vendor/autoload.php
$autoload = ROOT_PATH . '/vendor/autoload.php';
echo "vendor/autoload.php: " . (file_exists($autoload) ? 'existe' : 'NO EXISTE') . "\n\n";

// Log de notificaciones
$log_file = ROOT_PATH . '/almacenamiento/logs/notificaciones.log';
echo "Log: $log_file\n";
if (file_exists($log_file)) {
    $lineas  = file($log_file, FILE_IGNORE_NEW_LINES);
    $ultimas = array_slice($lineas, -15);
    echo "Ultimas lineas:\n";
    foreach ($ultimas as $l) { echo "  $l\n"; }
} else {
    echo "Log no existe aun\n";
}
echo "\n";

// Prueba de envío
if ($smtp_user && $smtp_pass && file_exists($autoload)) {
    echo "=== PRUEBA DE ENVIO ===\n";
    require_once $autoload;

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = ((int)$smtp_port === 587)
                            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)$smtp_port;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = function($str, $level) {
            echo htmlspecialchars(trim($str)) . "\n";
        };
        $mail->setFrom($smtp_from ?: $smtp_user, 'SIRGDI Diagnostico');
        $mail->addAddress($smtp_user);
        $mail->isHTML(false);
        $mail->Subject = 'Prueba SMTP SIRGDI ' . date('Y-m-d H:i:s');
        $mail->Body    = 'Si recibes esto, el SMTP funciona correctamente.';
        $mail->send();
        echo "\nEMAIL ENVIADO OK a $smtp_user\n";
    } catch (\Exception $e) {
        echo "\nERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "Prueba omitida: SMTP no configurado o vendor faltante.\n";
}

echo '</pre>';
