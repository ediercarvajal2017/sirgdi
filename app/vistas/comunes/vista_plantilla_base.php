<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo ?? 'SIRGDI'); ?></title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="<?php echo config('app.url_base'); ?>/css/estilos_base.css">

    <!-- CSS por vista (si existe) -->
    <?php
    $vista_nombre = basename($_SERVER['SCRIPT_NAME'] ?? 'index', '.php');
    $vista_css = config('app.url_base') . '/css/estilos_' . str_replace('vista_', '', $vista_nombre) . '.css';
    ?>
    <link rel="stylesheet" href="<?php echo $vista_css; ?>">

    <style>
        /* CSS inline mínimo para estructura básica */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <?php
    // Mostrar header solo si está autenticado
    if (isset($_SESSION['id_usuario'])):
    ?>
        <?php require_once __DIR__ . '/vista_header.php'; ?>
    <?php endif; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Mostrar mensajes de error/éxito si existen -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <!-- El contenido específico de cada vista se inyecta aquí -->
        <!-- (Este archivo se incluye desde el controlador) -->
    </main>

    <?php
    // Mostrar footer solo si está autenticado
    if (isset($_SESSION['id_usuario'])):
    ?>
        <?php require_once __DIR__ . '/vista_footer.php'; ?>
    <?php endif; ?>

    <!-- JS Base -->
    <script src="<?php echo config('app.url_base'); ?>/js/script_base.js"></script>

    <!-- JS por vista -->
    <script src="<?php echo config('app.url_base'); ?>/js/script_<?php echo str_replace('vista_', '', $vista_nombre); ?>.js"></script>
</body>
</html>
