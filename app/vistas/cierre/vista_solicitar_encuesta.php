<?php
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
?>
<div class="container enc-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-star"></i></div>
        <div class="page-banner__text">
            <h2>Encuesta de Satisfacción</h2>
            <p>Solicitar la valoración del reportante antes del cierre (RF-22).</p>
        </div>
    </div>

    <div class="form-modern-card">
        <h3><i class="fas fa-paper-plane"></i> Enviar encuesta al reportante</h3>

        <div class="enc-info">
            <div class="estrellas-demo">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <p>Se enviará al reportante <strong><?php echo htmlspecialchars($reporte['nombre_reportante']); ?></strong>
            (<?php echo htmlspecialchars($reporte['correo_reportante']); ?>) una encuesta para calificar la atención del reporte
            <strong><?php echo htmlspecialchars($reporte['numero_ticket']); ?></strong> de 1 a 5 estrellas con un comentario opcional.</p>
        </div>

        <div class="aviso-enc">
            <i class="fas fa-circle-info"></i>
            <div>La encuesta es <strong>opcional</strong> para el reportante. Tras enviarla, continuarás al cierre formal del reporte.</div>
        </div>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=cierre&accion=solicitar_encuesta">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo $reporte['id_reporte']; ?>">
            <div class="form-actions">
                <button type="submit" class="btn-modern"><i class="fas fa-envelope-circle-check"></i> Enviar Encuesta y Continuar</button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=cierre&accion=cerrar_reporte&id=<?php echo $reporte['id_reporte']; ?>" class="btn-modern-secondary" style="text-decoration:none;">
                    <i class="fas fa-forward"></i> Omitir e ir al Cierre
                </a>
            </div>
        </form>
    </div>
</div>

<?php $toast_exito_msg = 'Encuesta enviada al reportante.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
:root{--primary-blue:#3498DB;--dark-blue:#2980B9;--gray-text:#7F8C8D;--dark-text:#2C3E50;--light-bg:#F8FBFC;}
.enc-container{max-width:800px;margin:30px auto;padding:20px;}
.enc-header{margin-bottom:25px;}
.enc-header h2{font-size:30px;font-weight:700;color:var(--dark-text);margin:0 0 8px;display:flex;align-items:center;gap:12px;}
.enc-header h2 i{color:#F39C12;}
.subtitle{color:var(--gray-text);font-size:14px;margin:0;font-weight:500;}
.form-modern-card{background:#fff;border-radius:12px;padding:30px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-left:4px solid var(--primary-blue);}
.form-modern-card h3{margin-bottom:22px;color:var(--dark-text);font-size:20px;display:flex;align-items:center;gap:10px;}
.form-modern-card h3 i{color:var(--primary-blue);}
.enc-info{text-align:center;margin-bottom:22px;}
.estrellas-demo{font-size:34px;color:#F1C40F;margin-bottom:14px;letter-spacing:6px;}
.enc-info p{font-size:14px;color:var(--dark-text);line-height:1.7;}
.aviso-enc{display:flex;gap:12px;align-items:flex-start;background:rgba(52,152,219,.08);border-radius:10px;padding:16px;margin-bottom:22px;font-size:13px;color:var(--dark-text);}
.aviso-enc i{color:var(--primary-blue);font-size:20px;margin-top:2px;}
.form-actions{display:flex;gap:15px;flex-wrap:wrap;}
.btn-modern{flex:1;min-width:220px;padding:14px 24px;background:linear-gradient(135deg,#F39C12,#E67E22);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(243,156,18,.4);}
.btn-modern-secondary{flex:1;min-width:170px;padding:14px 24px;background:#ECF0F1;color:var(--dark-text);border:2px solid var(--primary-blue);border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern-secondary:hover{background:var(--gray-text);color:#fff;}
@media(max-width:768px){.enc-header h2{font-size:24px;}.form-actions{flex-direction:column;}.btn-modern,.btn-modern-secondary{flex:none;width:100%;}}
</style>
