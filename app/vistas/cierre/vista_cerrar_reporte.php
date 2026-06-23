<?php
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
?>
<div class="container cerrar-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-lock"></i></div>
        <div class="page-banner__text">
            <h2>Cerrar Reporte</h2>
            <p>Cierre formal del reporte <strong><?php echo htmlspecialchars($reporte['numero_ticket']); ?></strong> (RF-24).</p>
        </div>
    </div>

    <div class="form-modern-card">
        <h3><i class="fas fa-clipboard-list"></i> Resumen del reporte</h3>

        <div class="resumen-grid">
            <div class="rr-item"><span class="rr-label"><i class="fas fa-ticket"></i> Ticket</span><span class="rr-value"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span></div>
            <div class="rr-item"><span class="rr-label"><i class="fas fa-user"></i> Reportante</span><span class="rr-value"><?php echo htmlspecialchars($reporte['nombre_reportante']); ?></span></div>
            <div class="rr-item"><span class="rr-label"><i class="fas fa-envelope"></i> Correo</span><span class="rr-value"><?php echo htmlspecialchars($reporte['correo_reportante']); ?></span></div>
            <?php if (!empty($reporte['referencia_ubicacion_libre'])): ?>
                <div class="rr-item"><span class="rr-label"><i class="fas fa-map-marker-alt"></i> Ubicación</span><span class="rr-value"><?php echo htmlspecialchars($reporte['referencia_ubicacion_libre']); ?></span></div>
            <?php endif; ?>
            <div class="rr-item rr-full"><span class="rr-label"><i class="fas fa-comment-dots"></i> Problema</span><span class="rr-value"><?php echo htmlspecialchars($reporte['descripcion_problema']); ?></span></div>
        </div>

        <div class="aviso-cierre">
            <i class="fas fa-circle-info"></i>
            <div>Al cerrar el reporte se notificará automáticamente al <strong>reportante</strong> y al <strong>rector</strong> con el resumen y las evidencias. Esta acción marca el reporte como <strong>Cerrado</strong>.</div>
        </div>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=cierre&accion=cerrar_reporte">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo $reporte['id_reporte']; ?>">
            <div class="form-actions">
                <button type="submit" class="btn-modern" onclick="return confirm('¿Confirmas el cierre formal de este reporte?');">
                    <i class="fas fa-lock"></i> Cerrar Reporte Formalmente
                </button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=kanban" class="btn-modern-secondary" style="text-decoration:none;">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>
</div>

<?php $toast_exito_msg = 'Reporte cerrado correctamente.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
:root{--primary-blue:#3498DB;--dark-blue:#2980B9;--gray-text:#7F8C8D;--dark-text:#2C3E50;--light-bg:#F8FBFC;}
.cerrar-container{max-width:850px;margin:30px auto;padding:20px;}
.cerrar-header{margin-bottom:25px;}
.cerrar-header h2{font-size:30px;font-weight:700;color:var(--dark-text);margin:0 0 8px;display:flex;align-items:center;gap:12px;}
.cerrar-header h2 i{color:var(--primary-blue);}
.subtitle{color:var(--gray-text);font-size:14px;margin:0;font-weight:500;}
.form-modern-card{background:#fff;border-radius:12px;padding:30px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-left:4px solid var(--primary-blue);}
.form-modern-card h3{margin-bottom:22px;color:var(--dark-text);font-size:20px;display:flex;align-items:center;gap:10px;}
.form-modern-card h3 i{color:var(--primary-blue);}
.resumen-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;}
.rr-item{display:flex;flex-direction:column;gap:3px;}
.rr-full{grid-column:1/-1;}
.rr-label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-text);font-weight:600;display:flex;align-items:center;gap:6px;}
.rr-label i{color:var(--primary-blue);}
.rr-value{font-size:14px;color:var(--dark-text);}
.aviso-cierre{display:flex;gap:12px;align-items:flex-start;background:rgba(52,152,219,.08);border-radius:10px;padding:16px;margin-bottom:22px;font-size:13px;color:var(--dark-text);}
.aviso-cierre i{color:var(--primary-blue);font-size:20px;margin-top:2px;}
.form-actions{display:flex;gap:15px;flex-wrap:wrap;}
.btn-modern{flex:1;min-width:220px;padding:14px 24px;background:linear-gradient(135deg,#2C3E50,#1A252F);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(44,62,80,.4);}
.btn-modern-secondary{flex:1;min-width:150px;padding:14px 24px;background:#ECF0F1;color:var(--dark-text);border:2px solid var(--primary-blue);border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern-secondary:hover{background:var(--gray-text);color:#fff;}
@media(max-width:768px){.resumen-grid{grid-template-columns:1fr;}.cerrar-header h2{font-size:24px;}.form-actions{flex-direction:column;}.btn-modern,.btn-modern-secondary{flex:none;width:100%;}}
</style>
