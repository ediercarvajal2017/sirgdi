<?php
/**
 * TEMPLATE: Integración de Toasts en Controladores
 *
 * Copia este código en el método renderizar_vista() de tus controladores
 * para obtener soporte automático de notificaciones Toast.
 *
 * INSTRUCCIONES:
 * 1. Encuentra el método renderizar_vista() en tu controlador
 * 2. En la sección <head>, añade la línea de CSS de toasts
 * 3. Antes de </head>, reemplaza la sección de manejo de errores/éxito
 * 4. Antes de </body>, añade el script de toasts
 */

// ============================================================================
// PASO 1: Añadir en la sección <head>
// ============================================================================

?>
<!-- En la sección <head> del controlador, añade esta línea: -->
<link rel="stylesheet" href="<?php echo config('app.url_base'); ?>/css/estilos_toasts.css">

<?php
// ============================================================================
// PASO 2: Mostrar alertas tradicionales (ANTES de los toasts)
// ============================================================================

// Este es el código ANTIGUO que probablemente tienes:
?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!empty($exito)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div>
<?php endif; ?>

<?php
// ============================================================================
// PASO 3: Reemplazar con Toasts (RECOMENDADO)
// ============================================================================

// OPCIÓN A: Mostrar toasts basados en variables de sesión (Automático)
// Añade este código antes de cerrar el HTML
?>

<!-- COPIAR ESTO AL FINAL DEL CONTROLADOR, antes de </body> -->
<script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>

<?php
// Mostrar toast de éxito si existe en sesión
if (!empty($_SESSION['exito'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toast.success(
                '¡Éxito!',
                '<?php echo addslashes(htmlspecialchars($_SESSION['exito'])); ?>',
                4000
            );
        });
    </script>
    <?php unset($_SESSION['exito']);
endif;

// Mostrar toast de error si existe en sesión
if (!empty($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toast.error(
                'Error',
                '<?php echo addslashes(htmlspecialchars($_SESSION['error'])); ?>',
                5000
            );
        });
    </script>
    <?php unset($_SESSION['error']);
endif;

// Mostrar toast de advertencia si existe en sesión
if (!empty($_SESSION['advertencia'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toast.warning(
                'Advertencia',
                '<?php echo addslashes(htmlspecialchars($_SESSION['advertencia'])); ?>',
                4000
            );
        });
    </script>
    <?php unset($_SESSION['advertencia']);
endif;

// Mostrar toast de información si existe en sesión
if (!empty($_SESSION['info'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toast.info(
                'Información',
                '<?php echo addslashes(htmlspecialchars($_SESSION['info'])); ?>',
                4000
            );
        });
    </script>
    <?php unset($_SESSION['info']);
endif;
?>

<?php
// ============================================================================
// PASO 4: Usar en tu controlador
// ============================================================================

/**
 * En tu controlador, simplemente haz:
 *
 * // Éxito
 * $_SESSION['exito'] = 'Datos guardados correctamente';
 *
 * // Error
 * $_SESSION['error'] = 'No se pudo guardar los datos';
 *
 * // Advertencia
 * $_SESSION['advertencia'] = 'Verifica los datos antes de continuar';
 *
 * // Información
 * $_SESSION['info'] = 'Operación en progreso...';
 *
 * Y automáticamente aparecerá un toast con el mensaje.
 */
?>

<?php
// ============================================================================
// OPCIÓN B: Mostrar toasts directamente desde JavaScript
// ============================================================================

/**
 * Puedes también mostrar toasts directamente:
 *
 * <script>
 *     // Cuando la página carga
 *     document.addEventListener('DOMContentLoaded', function() {
 *         toast.success('Título', 'Mensaje detallado', 4000);
 *     });
 * </script>
 */
?>

<?php
// ============================================================================
// PASO 5: Ejemplo Completo de Integración
// ============================================================================

/**
 * Aquí está cómo debería verse tu controlador:
 *
 * class MiControlador {
 *     private function renderizar_vista($vista, $datos = []) {
 *         extract($datos);
 *         $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';
 *
 *         if (!file_exists($archivo_vista)) {
 *             die('Vista no encontrada: ' . $archivo_vista);
 *         }
 *
 *         ob_start();
 *         ?>
 *         <!DOCTYPE html>
 *         <html lang="es">
 *         <head>
 *             <meta charset="UTF-8">
 *             <meta name="viewport" content="width=device-width, initial-scale=1.0">
 *             <title><?php echo htmlspecialchars($titulo ?? 'SIRGDI'); ?></title>
 *             <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 *             <link rel="stylesheet" href="<?php echo config("app.url_base"); ?>/css/estilos_base.css">
 *             <link rel="stylesheet" href="<?php echo config("app.url_base"); ?>/css/estilos_toasts.css">
 *         </head>
 *         <body>
 *             <?php if (isset($_SESSION['id_usuario'])):
 *                 require_once APP_PATH . '/vistas/comunes/vista_header.php';
 *             endif; ?>
 *
 *             <main class="main-content">
 *                 <?php require $archivo_vista; ?>
 *             </main>
 *
 *             <?php if (isset($_SESSION['id_usuario'])):
 *                 require_once APP_PATH . '/vistas/comunes/vista_footer.php';
 *             endif; ?>
 *
 *             <script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>
 *
 *             <?php if (!empty($_SESSION['exito'])): ?>
 *                 <script>
 *                     document.addEventListener('DOMContentLoaded', function() {
 *                         toast.success('¡Éxito!', '<?php echo addslashes(htmlspecialchars($_SESSION['exito'])); ?>');
 *                     });
 *                 </script>
 *                 <?php unset($_SESSION['exito']); endif;
 *             ?>
 *
 *             <?php if (!empty($_SESSION['error'])): ?>
 *                 <script>
 *                     document.addEventListener('DOMContentLoaded', function() {
 *                         toast.error('Error', '<?php echo addslashes(htmlspecialchars($_SESSION['error'])); ?>');
 *                     });
 *                 </script>
 *                 <?php unset($_SESSION['error']); endif;
 *             ?>
 *         </body>
 *         </html>
 *         <?php
 *         echo ob_get_clean();
 *     }
 * }
 */
?>
