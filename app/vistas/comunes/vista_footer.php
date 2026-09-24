<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(config('app.app_name_version')); ?> | <?php echo htmlspecialchars(config('app.app_full_name')); ?></p>
        <?php
        // Los tres enlaces apuntaban a "#". Ahora llevan a donde dicen, y
        // "Términos de Servicio" desaparece: no existe ese documento, y un
        // enlace muerto en el pie es peor que no tenerlo.
        ?>
        <p>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=legal&accion=privacidad<?php echo !empty($_SESSION['id_institucion']) ? '&inst=' . (int) $_SESSION['id_institucion'] : ''; ?>">Privacidad</a> |
            <a href="<?php echo config('app.url_base'); ?>/?controlador=ayuda&accion=inicio">Ayuda</a> |
            <a href="mailto:<?php echo htmlspecialchars(config('app.correo_soporte')); ?>">Soporte</a>
        </p>
    </div>
</footer>
