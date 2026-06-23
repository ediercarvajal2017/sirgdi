<?php
/**
 * Helper de Toasts reutilizable.
 * Incluye toast.js y muestra toasts según parámetros GET ?exito / ?error.
 * Uso en una vista:  <?php require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>
 *
 * Opcional: definir $toast_exito_msg antes de incluirlo para personalizar el mensaje de éxito.
 */
$toast_exito_msg = $toast_exito_msg ?? 'Operación realizada correctamente.';
?>
<script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>
<script>
(function() {
    var intentos = 0;
    var EXITO_MSG = <?php echo json_encode($toast_exito_msg); ?>;

    function mostrarToast() {
        if (typeof toast === 'undefined' || !toast) {
            if (intentos < 30) { intentos++; setTimeout(mostrarToast, 100); }
            return;
        }
        var url = new URL(window.location);
        var params = url.searchParams;

        if (params.has('exito')) {
            setTimeout(function() {
                if (toast && toast.success) { toast.success('Éxito', EXITO_MSG); }
            }, 50);
            url.searchParams.delete('exito');
            window.history.replaceState({}, '', url.toString());
        }
        if (params.has('error')) {
            var msg = params.get('error');
            setTimeout(function() {
                if (toast && toast.error) { toast.error('Error', decodeURIComponent(msg)); }
            }, 50);
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url.toString());
        }
    }
    mostrarToast();
})();
</script>
