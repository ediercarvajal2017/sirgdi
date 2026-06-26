<?php
// Diagnóstico SMTP + BD — ELIMINAR después de usar
// Acceder: https://mantenimiento.ediertech.com/diag_smtp.php?clave=sirgdi2026

if (($_GET['clave'] ?? '') !== 'sirgdi2026') {
    http_response_code(403); die('Acceso denegado.');
}

$config_path = dirname(__DIR__) . '/configuracion/config.php';
if (!file_exists($config_path)) die('config.php no encontrado: ' . $config_path);
require_once $config_path;
require_once LIB_PATH . '/basedatos.php';

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;font-size:13px;padding:20px;background:#111;color:#0f0;">';
echo "=== DIAGNOSTICO SIRGDI ===\n\n";

// --- SMTP ---
$smtp_host = getenv('SMTP_HOST');
$smtp_port = getenv('SMTP_PORT');
$smtp_user = getenv('SMTP_USER');
$smtp_pass = getenv('SMTP_PASS');
$smtp_from = getenv('SMTP_FROM_EMAIL');
$enc_key   = getenv('ENCRYPTION_KEY');

echo "PHP         : " . PHP_VERSION . "\n";
echo ".env        : " . (file_exists(ROOT_PATH.'/.env') ? 'OK' : 'NO EXISTE') . "\n";
echo "SMTP_HOST   : " . ($smtp_host ?: 'VACIO') . "\n";
echo "SMTP_USER   : " . ($smtp_user ?: 'VACIO') . "\n";
echo "SMTP_PASS   : " . ($smtp_pass ? 'OK ('.strlen($smtp_pass).' chars)' : 'VACIO') . "\n";
echo "ENC_KEY     : " . ($enc_key ? strlen($enc_key).' chars'.(strlen($enc_key)===64?' OK':' ERROR') : 'VACIO') . "\n";
echo "vendor      : " . (file_exists(ROOT_PATH.'/vendor/autoload.php') ? 'OK' : 'NO EXISTE') . "\n\n";

// --- BASE DE DATOS ---
echo "=== BASE DE DATOS ===\n";
try {
    $bd = BaseDatos::obtener();

    // Verificar columnas token_reset en tabla usuario
    $cols = $bd->ejecutar(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'usuario'
           AND COLUMN_NAME IN ('token_reset_pass','token_reset_expira')",
        []
    )->fetchAll(PDO::FETCH_COLUMN);

    echo "Col token_reset_pass   : " . (in_array('token_reset_pass',   $cols) ? 'existe' : 'FALTA') . "\n";
    echo "Col token_reset_expira : " . (in_array('token_reset_expira', $cols) ? 'existe' : 'FALTA') . "\n\n";

    // Verificar el email que el usuario está intentando recuperar
    $email_prueba = $_GET['email'] ?? '';
    if ($email_prueba) {
        $usu = $bd->obtener_uno(
            "SELECT id_usuario, nombre_completo, activo FROM usuario WHERE correo_electronico = :e LIMIT 1",
            [':e' => $email_prueba]
        );
        if ($usu) {
            echo "Usuario '$email_prueba': encontrado (activo=" . $usu['activo'] . ", id=" . $usu['id_usuario'] . ")\n";
        } else {
            echo "Usuario '$email_prueba': NO ENCONTRADO en la BD\n";
        }
        echo "\n";
    } else {
        echo "Tip: agrega &email=tu@correo.com a la URL para verificar si existe en la BD\n\n";
    }

    // Si faltan columnas, mostrar el ALTER TABLE necesario
    if (!in_array('token_reset_pass', $cols) || !in_array('token_reset_expira', $cols)) {
        echo "SOLUCION — ejecutar en phpMyAdmin:\n";
        echo "ALTER TABLE usuario\n";
        if (!in_array('token_reset_pass', $cols))
            echo "  ADD COLUMN token_reset_pass VARCHAR(100) NULL,\n";
        if (!in_array('token_reset_expira', $cols))
            echo "  ADD COLUMN token_reset_expira DATETIME NULL;\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "ERROR BD: " . $e->getMessage() . "\n\n";
}

// --- LOG ---
$log_file = ROOT_PATH . '/almacenamiento/logs/notificaciones.log';
echo "=== LOG NOTIFICACIONES ===\n";
if (file_exists($log_file)) {
    $lineas = file($log_file, FILE_IGNORE_NEW_LINES);
    foreach (array_slice($lineas, -15) as $l) echo "  $l\n";
} else {
    echo "  (sin entradas aun)\n";
}
echo "\n";

// --- PRUEBA SMTP DIRECTA ---
$autoload = ROOT_PATH . '/vendor/autoload.php';
if ($smtp_user && $smtp_pass && file_exists($autoload) && isset($_GET['test_mail'])) {
    echo "=== PRUEBA ENVIO ===\n";
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
        $mail->setFrom($smtp_from ?: $smtp_user, 'SIRGDI');
        $mail->addAddress($smtp_user);
        $mail->Subject = 'Prueba SMTP ' . date('H:i:s');
        $mail->Body    = 'SMTP funciona.';
        $mail->send();
        echo "EMAIL ENVIADO OK\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo '</pre>';
