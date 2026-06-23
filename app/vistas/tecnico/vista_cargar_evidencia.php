<?php
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
$id_interv = $intervension['id_informe'];

// Agrupar evidencias por etapa (id_etapa: 1=antes, 2=durante, 3=despues)
$por_etapa = ['antes' => [], 'durante' => [], 'despues' => []];
foreach (($evidencias ?? []) as $ev) {
    $nombre_etapa = ModeloEvidencia::id_a_etapa($ev['id_etapa']);
    if ($nombre_etapa && isset($por_etapa[$nombre_etapa])) {
        $por_etapa[$nombre_etapa][] = $ev;
    }
}
$etapas_def = [
    'antes'   => ['Antes', 'fa-camera-retro'],
    'durante' => ['Durante', 'fa-person-digging'],
    'despues' => ['Después', 'fa-circle-check'],
];
$completa = $completitud['completa'] ?? false;
$faltantes = $completitud['faltantes'] ?? [];
?>
<div class="container ev-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-camera"></i></div>
        <div class="page-banner__text">
            <h2>Cargar Evidencias</h2>
            <p>Reporte <strong><?php echo htmlspecialchars($reporte['numero_ticket']); ?></strong> — mínimo 1 foto por etapa (RN-03).</p>
        </div>
    </div>

    <!-- ESTADO DE COMPLETITUD -->
    <div class="completitud-card <?php echo $completa ? 'comp-ok' : 'comp-pend'; ?>">
        <?php if ($completa): ?>
            <i class="fas fa-circle-check"></i>
            <div><strong>Evidencia completa.</strong> Ya puedes marcar el reporte como solucionado.</div>
        <?php else: ?>
            <i class="fas fa-triangle-exclamation"></i>
            <div><strong>Evidencia incompleta.</strong> Faltan fotos de: <?php echo htmlspecialchars(implode(', ', array_map('ucfirst', $faltantes))); ?></div>
        <?php endif; ?>
    </div>

    <!-- 3 ETAPAS -->
    <div class="etapas-grid">
        <?php foreach ($etapas_def as $clave => $def): ?>
            <?php $fotos = $por_etapa[$clave]; $tiene = count($fotos) > 0; ?>
            <div class="etapa-card <?php echo $tiene ? 'etapa-ok' : ''; ?>">
                <div class="etapa-head">
                    <span class="etapa-titulo"><i class="fas <?php echo $def[1]; ?>"></i> <?php echo $def[0]; ?></span>
                    <span class="etapa-count <?php echo $tiene ? 'badge-ok' : 'badge-pend'; ?>">
                        <?php echo $tiene ? '<i class="fas fa-check"></i> ' . count($fotos) : 'Pendiente'; ?>
                    </span>
                </div>

                <div class="fotos-lista">
                    <?php if ($tiene): ?>
                        <?php foreach ($fotos as $f): ?>
                            <div class="foto-item">
                                <i class="fas fa-image"></i>
                                <div class="foto-info">
                                    <span class="foto-nombre"><?php echo htmlspecialchars($f['nombre_archivo_original']); ?></span>
                                    <?php if (!empty($f['descripcion'])): ?>
                                        <span class="foto-desc"><?php echo htmlspecialchars($f['descripcion']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="foto-vacia"><i class="fas fa-inbox"></i> Sin fotos aún</div>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data" action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=cargar_evidencia" class="form-foto">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="id_intervension" value="<?php echo $id_interv; ?>">
                    <input type="hidden" name="etapa_evidencia" value="<?php echo $clave; ?>">
                    <label class="file-drop">
                        <input type="file" name="foto" accept="image/jpeg,image/png" required onchange="this.form.querySelector('.file-name').textContent = this.files[0] ? this.files[0].name : '';">
                        <i class="fas fa-cloud-arrow-up"></i>
                        <span>Seleccionar foto</span>
                        <span class="file-name"></span>
                    </label>
                    <input type="text" name="descripcion_foto" maxlength="255" placeholder="Descripción (opcional)" class="input-foto">
                    <button type="submit" class="btn-subir"><i class="fas fa-upload"></i> Subir foto</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ACCIONES FINALES -->
    <div class="acciones-finales">
        <a href="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=mis_asignaciones" class="btn-modern-secondary" style="text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Volver a Mis Asignaciones
        </a>
        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=marcar_solucionado" style="flex:1;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo $reporte['id_reporte']; ?>">
            <button type="submit" class="btn-modern" <?php echo $completa ? '' : 'disabled'; ?> onclick="return confirm('¿Marcar el reporte como solucionado?');">
                <i class="fas fa-check-double"></i> Marcar como Solucionado
            </button>
        </form>
    </div>
</div>

<?php $toast_exito_msg = 'Foto cargada correctamente.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
:root{--primary-blue:#3498DB;--dark-blue:#2980B9;--gray-text:#7F8C8D;--dark-text:#2C3E50;--light-bg:#F8FBFC;}
.ev-container{max-width:1200px;margin:30px auto;padding:20px;}
.ev-header{margin-bottom:20px;}
.ev-header h2{font-size:30px;font-weight:700;color:var(--dark-text);margin:0 0 8px;display:flex;align-items:center;gap:12px;}
.ev-header h2 i{color:var(--primary-blue);}
.subtitle{color:var(--gray-text);font-size:14px;margin:0;font-weight:500;}
.completitud-card{display:flex;align-items:center;gap:14px;padding:16px 20px;border-radius:12px;margin-bottom:25px;font-size:14px;}
.completitud-card i{font-size:24px;}
.comp-ok{background:rgba(39,174,96,.12);color:#1E8449;border-left:4px solid #27AE60;}
.comp-pend{background:rgba(230,126,34,.12);color:#B9770E;border-left:4px solid #E67E22;}
.etapas-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:25px;}
.etapa-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-top:4px solid #BDC3C7;display:flex;flex-direction:column;gap:14px;}
.etapa-ok{border-top-color:#27AE60;}
.etapa-head{display:flex;align-items:center;justify-content:space-between;}
.etapa-titulo{font-size:16px;font-weight:700;color:var(--dark-text);display:flex;align-items:center;gap:8px;}
.etapa-titulo i{color:var(--primary-blue);}
.etapa-count{font-size:11px;font-weight:600;padding:4px 10px;border-radius:12px;}
.badge-ok{background:rgba(39,174,96,.15);color:#27AE60;}
.badge-pend{background:rgba(189,195,199,.3);color:#7F8C8D;}
.fotos-lista{display:flex;flex-direction:column;gap:8px;min-height:50px;}
.foto-item{display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--light-bg);border-radius:8px;}
.foto-item>i{color:var(--primary-blue);font-size:16px;}
.foto-info{display:flex;flex-direction:column;overflow:hidden;}
.foto-nombre{font-size:12px;color:var(--dark-text);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.foto-desc{font-size:11px;color:var(--gray-text);}
.foto-vacia{color:var(--gray-text);font-size:12px;text-align:center;padding:12px;display:flex;align-items:center;justify-content:center;gap:6px;}
.form-foto{display:flex;flex-direction:column;gap:10px;border-top:1px dashed #E8EDEF;padding-top:14px;}
.file-drop{display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px;border:2px dashed var(--primary-blue);border-radius:8px;cursor:pointer;background:var(--light-bg);transition:all .3s;text-align:center;}
.file-drop:hover{background:#EAF4FB;}
.file-drop input[type=file]{display:none;}
.file-drop i{font-size:22px;color:var(--primary-blue);}
.file-drop span{font-size:12px;color:var(--gray-text);}
.file-name{font-weight:600;color:var(--dark-text)!important;}
.input-foto{padding:10px 12px;border:2px solid var(--primary-blue);background:var(--light-bg);border-radius:8px;font-size:13px;box-sizing:border-box;}
.input-foto:focus{outline:none;border-color:var(--dark-blue);background:#fff;}
.btn-subir{padding:10px;background:linear-gradient(135deg,var(--primary-blue),var(--dark-blue));color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .3s;}
.btn-subir:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(52,152,219,.35);}
.acciones-finales{display:flex;gap:15px;align-items:stretch;flex-wrap:wrap;}
.btn-modern{width:100%;padding:14px 24px;background:linear-gradient(135deg,#27AE60,#1E8449);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 6px 20px rgba(39,174,96,.4);}
.btn-modern:disabled{background:#BDC3C7;cursor:not-allowed;}
.btn-modern-secondary{padding:14px 24px;background:#ECF0F1;color:var(--dark-text);border:2px solid var(--primary-blue);border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern-secondary:hover{background:var(--gray-text);color:#fff;}
@media(max-width:768px){.etapas-grid{grid-template-columns:1fr;}.ev-header h2{font-size:24px;}.acciones-finales{flex-direction:column;}}
</style>
