<?php
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
$base = config('app.url_base');
$por_etapa = ['antes' => [], 'durante' => [], 'despues' => []];
foreach (($evidencias ?? []) as $ev) {
    $n = ModeloEvidencia::id_a_etapa($ev['id_etapa']);
    if ($n && isset($por_etapa[$n])) { $por_etapa[$n][] = $ev; }
}
$etapas_def = ['antes' => 'Antes', 'durante' => 'Durante', 'despues' => 'Después'];
?>
<div class="container val-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-clipboard-check"></i></div>
        <div class="page-banner__text">
            <h2>Validar Solución</h2>
            <p>Revisa el trabajo del técnico antes de cerrar el reporte <strong><?php echo htmlspecialchars($reporte['numero_ticket']); ?></strong>.</p>
        </div>
    </div>

    <!-- RESUMEN -->
    <div class="reporte-resumen">
        <div class="rr-item"><span class="rr-label"><i class="fas fa-ticket"></i> Ticket</span><span class="rr-value"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span></div>
        <div class="rr-item"><span class="rr-label"><i class="fas fa-user"></i> Reportante</span><span class="rr-value"><?php echo htmlspecialchars($reporte['nombre_reportante']); ?></span></div>
        <div class="rr-item rr-full"><span class="rr-label"><i class="fas fa-comment-dots"></i> Problema</span><span class="rr-value"><?php echo htmlspecialchars($reporte['descripcion_problema']); ?></span></div>
    </div>

    <!-- GALERÍA DE EVIDENCIAS -->
    <div class="evidencias-bloque">
        <h3><i class="fas fa-images"></i> Evidencias por etapa</h3>
        <div class="etapas-grid">
            <?php foreach ($etapas_def as $clave => $titulo): ?>
                <?php $fotos = $por_etapa[$clave]; ?>
                <div class="etapa-col">
                    <div class="etapa-tit <?php echo count($fotos) ? 'ok' : 'pend'; ?>">
                        <?php echo $titulo; ?>
                        <span><?php echo count($fotos); ?> foto(s)</span>
                    </div>
                    <?php if (count($fotos)): ?>
                        <div class="fotos-grid">
                            <?php foreach ($fotos as $i => $f):
                                $url = $base . '/?controlador=tecnico&accion=descargar_evidencia&id=' . (int)$f['id_evidencia'];
                                $foto_titulo = $titulo . ' · foto ' . ($i + 1) . (empty($f['descripcion']) ? '' : ' — ' . $f['descripcion']);
                            ?>
                            <a href="<?php echo $url; ?>" class="foto-thumb" data-visor="evidencias-validacion"
                               title="<?php echo htmlspecialchars($foto_titulo); ?>" data-titulo="<?php echo htmlspecialchars($foto_titulo); ?>">
                                <img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars($foto_titulo); ?>" loading="lazy">
                                <span class="foto-thumb-zoom"><i class="fas fa-magnifying-glass-plus"></i></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="foto-vacia">Sin evidencia</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FORMULARIO DE VALIDACIÓN -->
    <div class="form-modern-card">
        <h3><i class="fas fa-gavel"></i> Decisión de validación</h3>
        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=cierre&accion=validar_solucion" id="form-val">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo $reporte['id_reporte']; ?>">

            <div class="opciones-validacion">
                <label class="opcion opcion-aprobar">
                    <input type="radio" name="validacion" value="aprobada" required>
                    <span class="opcion-content"><i class="fas fa-circle-check"></i> Aprobar — El trabajo es correcto</span>
                </label>
                <label class="opcion opcion-rechazar">
                    <input type="radio" name="validacion" value="rechazada">
                    <span class="opcion-content"><i class="fas fa-circle-xmark"></i> Rechazar — Devolver al técnico</span>
                </label>
            </div>

            <div class="form-group">
                <label for="comentario_validacion"><i class="fas fa-pen"></i> Comentario (obligatorio si rechazas):</label>
                <textarea name="comentario_validacion" id="comentario_validacion" rows="3" class="input-modern" placeholder="Motivo de la decisión…"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-modern"><i class="fas fa-paper-plane"></i> Enviar Decisión</button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=kanban" class="btn-modern-secondary" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </form>
    </div>
</div>

<?php $toast_exito_msg = 'Validación registrada correctamente.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<script src="<?php echo asset_url('js/visor_imagenes.js'); ?>"></script>
<script>
// Hacer obligatorio el comentario si se rechaza
document.getElementById('form-val').addEventListener('submit', function(e) {
    var val = this.querySelector('input[name="validacion"]:checked');
    var com = document.getElementById('comentario_validacion');
    if (val && val.value === 'rechazada' && com.value.trim().length < 5) {
        e.preventDefault();
        if (typeof toast !== 'undefined' && toast) {
            toast.error('Falta comentario', 'Debes indicar el motivo del rechazo (mínimo 5 caracteres).');
        }
        com.focus();
    }
});
</script>

<style>
:root{--primary-blue:var(--color-primary);--dark-blue:var(--color-primary-dark);--gray-text:var(--color-text-muted);--dark-text:var(--color-text);--light-bg:var(--color-bg-subtle);}
.val-container{max-width:1000px;margin:30px auto;padding:20px;}
.val-header{margin-bottom:25px;}
.val-header h2{font-size:30px;font-weight:700;color:var(--dark-text);margin:0 0 8px;display:flex;align-items:center;gap:12px;}
.val-header h2 i{color:var(--primary-blue);}
.subtitle{color:var(--gray-text);font-size:14px;margin:0;font-weight:500;}
.reporte-resumen{background:var(--color-bg-elevated);color:var(--color-text);border-radius:12px;padding:20px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-left:4px solid var(--primary-blue);margin-bottom:25px;display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.rr-item{display:flex;flex-direction:column;gap:3px;}
.rr-full{grid-column:1/-1;}
.rr-label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-text);font-weight:600;display:flex;align-items:center;gap:6px;}
.rr-label i{color:var(--primary-blue);}
.rr-value{font-size:14px;color:var(--dark-text);}
.evidencias-bloque{background:var(--color-bg-elevated);color:var(--color-text);border-radius:12px;padding:24px;box-shadow:0 4px 16px rgba(52,152,219,.08);margin-bottom:25px;}
.evidencias-bloque h3{margin-bottom:18px;color:var(--dark-text);font-size:18px;display:flex;align-items:center;gap:10px;}
.evidencias-bloque h3 i{color:var(--primary-blue);}
.etapas-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
.etapa-col{background:var(--light-bg);border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:8px;}
.etapa-tit{font-weight:700;color:var(--dark-text);font-size:14px;display:flex;justify-content:space-between;align-items:center;padding-bottom:8px;border-bottom:2px solid #E8EDEF;}
.etapa-tit span{font-size:11px;font-weight:500;color:var(--gray-text);}
.etapa-tit.ok{border-bottom-color: var(--color-success);}
.etapa-tit.pend{border-bottom-color:#E67E22;}
.foto-vacia{font-size:12px;color:var(--gray-text);font-style:italic;}
.fotos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:8px;}
.foto-thumb{position:relative;display:block;aspect-ratio:1;border-radius:8px;overflow:hidden;background:var(--light-bg);border:1px solid #E8EDEF;cursor:zoom-in;transition:transform .15s,box-shadow .15s;}
.foto-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.foto-thumb:hover,.foto-thumb:focus-visible{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.25);outline:none;border-color:var(--primary-blue);}
.foto-thumb-zoom{position:absolute;top:5px;right:5px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;font-size:10px;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s;}
.foto-thumb:hover .foto-thumb-zoom{opacity:1;}
.form-modern-card{background:var(--color-bg-elevated);color:var(--color-text);border-radius:12px;padding:30px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-left:4px solid var(--primary-blue);}
.form-modern-card h3{margin-bottom:22px;color:var(--dark-text);font-size:20px;display:flex;align-items:center;gap:10px;}
.form-modern-card h3 i{color:var(--primary-blue);}
.opciones-validacion{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;}
.opcion{display:flex;align-items:center;cursor:pointer;border:2px solid #E8EDEF;border-radius:10px;padding:16px;transition:all .3s;}
.opcion input{display:none;}
.opcion-content{display:flex;align-items:center;gap:10px;font-weight:600;font-size:14px;color:var(--dark-text);}
.opcion-content i{font-size:20px;}
.opcion-aprobar .opcion-content i{color: var(--color-success);}
.opcion-rechazar .opcion-content i{color: var(--color-danger);}
.opcion-aprobar input:checked + .opcion-content{color:#1E8449;}
.opcion-rechazar input:checked + .opcion-content{color:#C0392B;}
.opcion-aprobar:has(input:checked){border-color: var(--color-success);background:rgba(39,174,96,.08);}
.opcion-rechazar:has(input:checked){border-color: var(--color-danger);background:rgba(231,76,60,.08);}
.form-group{display:flex;flex-direction:column;margin-bottom:20px;}
.form-group label{font-weight:600;margin-bottom:10px;color:var(--dark-text);font-size:14px;display:flex;align-items:center;gap:8px;}
.form-group label i{color:var(--primary-blue);}
.input-modern{padding:12px 15px;border:2px solid var(--primary-blue);background:var(--light-bg);border-radius:8px;font-family:inherit;font-size:14px;box-sizing:border-box;width:100%;resize:vertical;}
.input-modern:focus{outline:none;border-color:var(--dark-blue);background:var(--color-bg-elevated);box-shadow:0 0 0 4px rgba(52,152,219,.1);}
.form-actions{display:flex;gap:15px;flex-wrap:wrap;}
.btn-modern{flex:1;min-width:200px;padding:12px 24px;background:linear-gradient(135deg,var(--primary-blue),var(--dark-blue));color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(52,152,219,.4);}
.btn-modern-secondary{flex:1;min-width:150px;padding:12px 24px;background:var(--color-bg-subtle);color:var(--dark-text);border:2px solid var(--primary-blue);border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern-secondary:hover{background:var(--gray-text);color:#fff;}
@media(max-width:768px){.reporte-resumen,.etapas-grid,.opciones-validacion{grid-template-columns:1fr;}.val-header h2{font-size:24px;}.form-actions{flex-direction:column;}.btn-modern,.btn-modern-secondary{flex:none;width:100%;}}
</style>
