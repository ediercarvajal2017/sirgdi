<?php
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
$id_interv = $intervension['id_informe'];

// Agrupar evidencias por etapa
$por_etapa = ['antes' => [], 'durante' => [], 'despues' => []];
foreach (($evidencias ?? []) as $ev) {
    $nombre_etapa = ModeloEvidencia::id_a_etapa($ev['id_etapa']);
    if ($nombre_etapa && isset($por_etapa[$nombre_etapa])) {
        $por_etapa[$nombre_etapa][] = $ev;
    }
}
$etapas_def = [
    'antes'   => ['label' => 'Antes',    'icon' => 'fa-camera-retro',   'color' => '#3498DB'],
    'durante' => ['label' => 'Durante',  'icon' => 'fa-person-digging', 'color' => '#E67E22'],
    'despues' => ['label' => 'Después',  'icon' => 'fa-circle-check',   'color' => '#27AE60'],
];
$completa  = $completitud['completa']  ?? false;
$faltantes = $completitud['faltantes'] ?? [];
?>
<div class="container ev-container">

    <!-- Banner -->
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-camera"></i></div>
        <div class="page-banner__text">
            <h2>Cargar Evidencias</h2>
            <p>Reporte <strong><?php echo htmlspecialchars($reporte['numero_ticket']); ?></strong>
               &mdash; mínimo 1 foto por etapa (RN-03).</p>
        </div>
    </div>

    <!-- Completitud -->
    <div class="completitud-card <?php echo $completa ? 'comp-ok' : 'comp-pend'; ?>">
        <?php if ($completa): ?>
            <i class="fas fa-circle-check"></i>
            <div><strong>Evidencia completa.</strong> Ya puedes marcar el reporte como solucionado.</div>
        <?php else: ?>
            <i class="fas fa-triangle-exclamation"></i>
            <div><strong>Evidencia incompleta.</strong> Faltan fotos de: <?php echo htmlspecialchars(implode(', ', array_map('ucfirst', $faltantes))); ?></div>
        <?php endif; ?>
    </div>

    <!-- 3 Etapas -->
    <div class="etapas-grid">
        <?php foreach ($etapas_def as $clave => $def):
            $fotos = $por_etapa[$clave];
            $tiene = count($fotos) > 0;
        ?>
        <div class="etapa-card <?php echo $tiene ? 'etapa-ok' : ''; ?>">

            <!-- Cabecera etapa -->
            <div class="etapa-head">
                <span class="etapa-titulo" style="--etapa-color:<?php echo $def['color']; ?>">
                    <i class="fas <?php echo $def['icon']; ?>"></i>
                    <?php echo $def['label']; ?>
                </span>
                <span class="etapa-count <?php echo $tiene ? 'badge-ok' : 'badge-pend'; ?>">
                    <?php echo $tiene
                        ? '<i class="fas fa-check"></i> ' . count($fotos)
                        : 'Pendiente'; ?>
                </span>
            </div>

            <!-- Fotos ya subidas -->
            <?php if ($tiene): ?>
            <div class="fotos-lista">
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
            </div>
            <?php endif; ?>

            <!-- Formulario de carga -->
            <form method="POST"
                  enctype="multipart/form-data"
                  action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=cargar_evidencia"
                  class="form-foto"
                  id="form-<?php echo $clave; ?>">
                <input type="hidden" name="csrf_token"       value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="id_intervension"  value="<?php echo $id_interv; ?>">
                <input type="hidden" name="etapa_evidencia"  value="<?php echo $clave; ?>">

                <!-- Preview de la foto seleccionada (antes de subir) -->
                <div id="preview-wrap-<?php echo $clave; ?>" class="ev-preview-wrap" style="display:none;">
                    <img id="preview-img-<?php echo $clave; ?>" src="" alt="Vista previa" class="ev-preview-img">
                    <button type="button" class="ev-preview-remove"
                            onclick="quitarPreview('<?php echo $clave; ?>')"
                            title="Quitar foto">
                        <i class="fas fa-xmark"></i>
                    </button>
                    <div class="ev-preview-label" id="preview-label-<?php echo $clave; ?>"></div>
                </div>

                <!-- Botones de fuente -->
                <div id="source-row-<?php echo $clave; ?>" class="ev-source-row">
                    <!-- Archivo / galería -->
                    <div class="ev-source-card"
                         onclick="document.getElementById('input-file-<?php echo $clave; ?>').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <strong>Subir archivo</strong>
                        <small>JPG · PNG · WebP</small>
                    </div>
                    <!-- Cámara -->
                    <div class="ev-source-card ev-source-cam"
                         onclick="abrirCamaraEtapa('<?php echo $clave; ?>')">
                        <i class="fas fa-camera"></i>
                        <strong>Tomar foto</strong>
                        <small>Cámara en tiempo real</small>
                    </div>
                </div>

                <!-- Inputs ocultos -->
                <input type="file" id="input-file-<?php echo $clave; ?>"
                       name="foto" accept="image/jpeg,image/png,image/webp"
                       style="display:none;"
                       onchange="onFotoSeleccionada(this, '<?php echo $clave; ?>')">
                <!-- Cámara nativa móvil -->
                <input type="file" id="input-cam-movil-<?php echo $clave; ?>"
                       accept="image/*" capture="environment"
                       style="display:none;"
                       onchange="onFotoSeleccionada(this, '<?php echo $clave; ?>')">

                <input type="text"
                       name="descripcion_foto"
                       maxlength="255"
                       placeholder="Descripción de la foto (opcional)"
                       class="input-foto"
                       id="desc-<?php echo $clave; ?>">

                <button type="submit" class="btn-subir" id="btn-subir-<?php echo $clave; ?>" disabled>
                    <i class="fas fa-upload"></i> Subir foto
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Acciones finales -->
    <div class="acciones-finales">
        <a href="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=mis_asignaciones"
           class="btn-modern-secondary" style="text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Volver a Mis Asignaciones
        </a>
        <form method="POST"
              action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=marcar_solucionado"
              style="flex:1;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte"  value="<?php echo $reporte['id_reporte']; ?>">
            <button type="submit" class="btn-modern"
                    <?php echo $completa ? '' : 'disabled'; ?>
                    onclick="return confirm('¿Marcar el reporte como solucionado?');">
                <i class="fas fa-check-double"></i> Marcar como Solucionado
            </button>
        </form>
    </div>
</div>

<!-- ── MODAL CÁMARA ── (compartido para las 3 etapas) -->
<div id="modal-camara-ev" class="cam-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="cam-box">
        <div class="cam-header">
            <span id="cam-header-title"><i class="fas fa-camera"></i> Tomar foto — <span id="cam-etapa-label">Etapa</span></span>
            <button type="button" class="cam-close" onclick="cerrarCamara()">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="cam-body">
            <video id="cam-stream" autoplay playsinline muted class="cam-stream"></video>
            <canvas id="cam-canvas" style="display:none;"></canvas>
            <div id="cam-flash" class="cam-flash"></div>
        </div>
        <div class="cam-footer">
            <button type="button" class="cam-btn-switch" id="btn-switch-cam"
                    onclick="cambiarCamara()" title="Cambiar cámara" style="display:none;">
                <i class="fas fa-rotate"></i>
            </button>
            <button type="button" class="cam-btn-capture" onclick="capturarFoto()">
                <i class="fas fa-camera"></i> Capturar foto
            </button>
        </div>
        <p id="cam-error" class="cam-error" style="display:none;"></p>
    </div>
</div>

<?php $toast_exito_msg = 'Foto cargada correctamente.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
:root {
    --primary-blue: #3498DB;
    --dark-blue:    #2980B9;
    --gray-text:    #7F8C8D;
    --dark-text:    #2C3E50;
    --light-bg:     #F8FBFC;
}

/* ── Layout ── */
.ev-container { max-width: 1200px; margin: 30px auto; padding: 20px; }

.completitud-card { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:12px; margin-bottom:25px; font-size:14px; }
.completitud-card i { font-size:24px; }
.comp-ok   { background:rgba(39,174,96,.12);  color:#1E8449; border-left:4px solid #27AE60; }
.comp-pend { background:rgba(230,126,34,.12); color:#B9770E; border-left:4px solid #E67E22; }

/* ── Grid 3 etapas ── */
.etapas-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:25px; }

.etapa-card {
    background:#fff;
    border-radius:14px;
    padding:20px;
    box-shadow:0 4px 18px rgba(52,152,219,.09);
    border-top:4px solid #BDC3C7;
    display:flex; flex-direction:column; gap:14px;
}
.etapa-ok { border-top-color:#27AE60; }

.etapa-head { display:flex; align-items:center; justify-content:space-between; }
.etapa-titulo {
    font-size:15px; font-weight:700; color:var(--dark-text);
    display:flex; align-items:center; gap:8px;
}
.etapa-titulo i { color: var(--etapa-color, var(--primary-blue)); }
.etapa-count { font-size:11px; font-weight:600; padding:4px 10px; border-radius:12px; }
.badge-ok   { background:rgba(39,174,96,.15);    color:#27AE60; }
.badge-pend { background:rgba(189,195,199,.3);   color:#7F8C8D; }

/* ── Lista fotos subidas ── */
.fotos-lista { display:flex; flex-direction:column; gap:8px; }
.foto-item   { display:flex; align-items:center; gap:10px; padding:8px 10px; background:var(--light-bg); border-radius:8px; }
.foto-item>i { color:var(--primary-blue); font-size:16px; }
.foto-info   { display:flex; flex-direction:column; overflow:hidden; }
.foto-nombre { font-size:12px; color:var(--dark-text); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.foto-desc   { font-size:11px; color:var(--gray-text); }

/* ── Formulario carga ── */
.form-foto { display:flex; flex-direction:column; gap:10px; border-top:1px dashed #E8EDEF; padding-top:14px; }

/* Preview de foto seleccionada */
.ev-preview-wrap {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 4/3;
    background: #000;
}
.ev-preview-img  { width:100%; height:100%; object-fit:cover; display:block; }
.ev-preview-remove {
    position:absolute; top:6px; right:6px;
    background:rgba(231,76,60,.9); color:#fff;
    border:none; border-radius:50%;
    width:28px; height:28px;
    cursor:pointer; font-size:14px;
    display:flex; align-items:center; justify-content:center;
    transition:background .2s;
}
.ev-preview-remove:hover { background:#E74C3C; }
.ev-preview-label {
    position:absolute; bottom:0; left:0; right:0;
    background:rgba(0,0,0,.5); color:#fff;
    font-size:11px; padding:4px 8px; text-align:center;
}

/* ── Source row (archivo / cámara) ── */
.ev-source-row {
    display:grid; grid-template-columns:1fr 1fr; gap:10px;
}
.ev-source-card {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:5px; padding:16px 10px;
    border:2px dashed #C8DDEF; border-radius:10px;
    background:#F8FBFE; cursor:pointer;
    transition:all .2s; text-align:center;
}
.ev-source-card i      { font-size:22px; color:var(--primary-blue); transition:transform .2s; }
.ev-source-card strong { font-size:12px; font-weight:700; color:var(--dark-text); }
.ev-source-card small  { font-size:10px; color:var(--gray-text); }
.ev-source-card:hover  { border-color:var(--primary-blue); background:#EBF5FB; }
.ev-source-card:hover i { transform:scale(1.1); }

.ev-source-cam            { border-color:#C5EAE0; background:#F0FAF7; }
.ev-source-cam i          { color:#16A085; }
.ev-source-cam:hover      { border-color:#1ABC9C; background:#E8F8F5; }

.input-foto {
    padding:10px 12px;
    border:2px solid var(--primary-blue);
    background:var(--light-bg);
    border-radius:8px;
    font-size:13px;
    box-sizing:border-box;
    font-family:inherit;
}
.input-foto:focus { outline:none; border-color:var(--dark-blue); background:#fff; }

.btn-subir {
    padding:10px;
    background:linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
    color:#fff; border:none; border-radius:8px;
    cursor:pointer; font-weight:600; font-size:13px;
    display:flex; align-items:center; justify-content:center; gap:6px;
    transition:all .3s;
    font-family:inherit;
}
.btn-subir:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 4px 14px rgba(52,152,219,.35); }
.btn-subir:disabled { background:#BDC3C7; cursor:not-allowed; }

/* ── Acciones finales ── */
.acciones-finales { display:flex; gap:15px; align-items:stretch; flex-wrap:wrap; }
.btn-modern {
    width:100%; padding:14px 24px;
    background:linear-gradient(135deg,#27AE60,#1E8449);
    color:#fff; border:none; border-radius:8px;
    cursor:pointer; font-weight:600; text-transform:uppercase;
    font-size:14px; transition:all .3s;
    display:flex; align-items:center; justify-content:center; gap:8px;
    font-family:inherit;
}
.btn-modern:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 6px 20px rgba(39,174,96,.4); }
.btn-modern:disabled { background:#BDC3C7; cursor:not-allowed; }
.btn-modern-secondary {
    padding:14px 24px;
    background:#ECF0F1; color:var(--dark-text);
    border:2px solid var(--primary-blue); border-radius:8px;
    cursor:pointer; font-weight:600; text-transform:uppercase;
    font-size:14px; transition:all .3s;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-modern-secondary:hover { background:var(--gray-text); color:#fff; }

/* ── MODAL CÁMARA ── */
.cam-modal {
    position:fixed; inset:0;
    background:rgba(15,25,40,.75);
    backdrop-filter:blur(4px);
    z-index:9000;
    display:flex; align-items:center; justify-content:center;
    padding:16px;
}
.cam-box {
    background:#fff; border-radius:16px;
    width:100%; max-width:520px;
    overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
}
.cam-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px;
    background:linear-gradient(135deg,#2980B9,#3498DB);
    color:#fff; font-weight:700; font-size:14px;
}
.cam-close {
    background:rgba(255,255,255,.15); border:none; border-radius:8px;
    color:#fff; width:32px; height:32px;
    cursor:pointer; font-size:16px;
    display:flex; align-items:center; justify-content:center;
    transition:background .2s;
}
.cam-close:hover { background:rgba(255,255,255,.3); }
.cam-body {
    position:relative; background:#000;
    aspect-ratio:4/3; max-height:340px; overflow:hidden;
}
.cam-stream   { width:100%; height:100%; object-fit:cover; display:block; }
.cam-flash    { position:absolute; inset:0; background:#fff; opacity:0; pointer-events:none; transition:opacity .05s; }
.cam-flash.flash { opacity:1; }
.cam-footer {
    padding:14px 20px;
    display:flex; align-items:center; justify-content:center; gap:12px;
    background:#F8FBFC;
}
.cam-btn-capture {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#2980B9,#3498DB);
    color:#fff; border:none; border-radius:10px;
    padding:12px 28px; font-size:14px; font-weight:700;
    cursor:pointer; font-family:inherit; transition:all .2s;
    box-shadow:0 4px 14px rgba(52,152,219,.35);
}
.cam-btn-capture:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(52,152,219,.45); }
.cam-btn-switch {
    background:#EBF5FB; border:1.5px solid #D5E8F5;
    border-radius:10px; color:var(--primary-blue);
    width:44px; height:44px; cursor:pointer; font-size:18px;
    display:flex; align-items:center; justify-content:center;
    transition:all .2s;
}
.cam-btn-switch:hover { background:#D5E8F5; }
.cam-error { margin:0; padding:10px 20px 14px; background:#FDF0EF; color:#C0392B; font-size:13px; text-align:center; }

@media(max-width:768px) {
    .etapas-grid { grid-template-columns:1fr; }
    .acciones-finales { flex-direction:column; }
}
</style>

<script>
const esMobil = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
const etapasLabels = { antes:'Antes', durante:'Durante', despues:'Después' };

let streamCam      = null;
let etapaActiva    = null;
let facingMode     = 'environment';

// ─── Abrir cámara para una etapa ────────────────────────────────────────────
function abrirCamaraEtapa(clave) {
    if (esMobil) {
        document.getElementById('input-cam-movil-' + clave).click();
        return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarToastError('Tu navegador no soporta cámara. Usa "Subir archivo".');
        return;
    }
    etapaActiva = clave;
    document.getElementById('cam-etapa-label').textContent = etapasLabels[clave] || clave;
    document.getElementById('modal-camara-ev').style.display = 'flex';
    iniciarStream();
}

async function iniciarStream() {
    ocultarError();
    try {
        if (streamCam) streamCam.getTracks().forEach(t => t.stop());
        streamCam = await navigator.mediaDevices.getUserMedia({
            video: { facingMode, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        });
        document.getElementById('cam-stream').srcObject = streamCam;

        navigator.mediaDevices.enumerateDevices().then(devs => {
            const cams = devs.filter(d => d.kind === 'videoinput');
            document.getElementById('btn-switch-cam').style.display = cams.length > 1 ? '' : 'none';
        });
    } catch(err) {
        mostrarError(mensajeErrorCamara(err));
    }
}

// ─── Capturar foto ───────────────────────────────────────────────────────────
function capturarFoto() {
    const video  = document.getElementById('cam-stream');
    const canvas = document.getElementById('cam-canvas');
    canvas.width  = video.videoWidth  || 1280;
    canvas.height = video.videoHeight || 720;
    canvas.getContext('2d').drawImage(video, 0, 0);

    // Flash visual
    const flash = document.getElementById('cam-flash');
    flash.classList.add('flash');
    setTimeout(() => flash.classList.remove('flash'), 150);

    canvas.toBlob(blob => {
        if (!blob || !etapaActiva) return;
        const nombre = 'foto_' + etapaActiva + '_' + Date.now() + '.jpg';
        const file   = new File([blob], nombre, { type: 'image/jpeg' });

        // Asignar al input del formulario correcto
        const inputFile = document.getElementById('input-file-' + etapaActiva);
        const dt = new DataTransfer();
        dt.items.add(file);
        inputFile.files = dt.files;

        // Mostrar preview
        const reader = new FileReader();
        reader.onload = ev => mostrarPreviewEtapa(etapaActiva, ev.target.result, nombre, file.size);
        reader.readAsDataURL(file);

        cerrarCamara();
    }, 'image/jpeg', 0.88);
}

// ─── Foto desde archivo o cámara nativa móvil ────────────────────────────────
function onFotoSeleccionada(input, clave) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        mostrarToastError('La foto supera 10 MB.');
        input.value = '';
        return;
    }
    // Si vino del input de galería, sincronizar con input-file principal
    if (input.id !== 'input-file-' + clave) {
        const inputPrincipal = document.getElementById('input-file-' + clave);
        const dt = new DataTransfer();
        dt.items.add(file);
        inputPrincipal.files = dt.files;
    }
    const reader = new FileReader();
    reader.onload = ev => mostrarPreviewEtapa(clave, ev.target.result, file.name, file.size);
    reader.readAsDataURL(file);
}

// ─── Mostrar/quitar preview ───────────────────────────────────────────────────
function mostrarPreviewEtapa(clave, src, nombre, size) {
    document.getElementById('preview-img-' + clave).src = src;
    document.getElementById('preview-label-' + clave).textContent =
        nombre + ' · ' + (size / 1024).toFixed(0) + ' KB';
    document.getElementById('preview-wrap-' + clave).style.display = '';
    document.getElementById('source-row-' + clave).style.display   = 'none';
    document.getElementById('btn-subir-' + clave).disabled = false;
}

function quitarPreview(clave) {
    document.getElementById('preview-wrap-' + clave).style.display = 'none';
    document.getElementById('source-row-' + clave).style.display   = '';
    document.getElementById('btn-subir-' + clave).disabled = true;
    document.getElementById('input-file-' + clave).value = '';
    document.getElementById('input-cam-movil-' + clave).value = '';
}

// ─── Cerrar cámara ────────────────────────────────────────────────────────────
function cerrarCamara() {
    if (streamCam) { streamCam.getTracks().forEach(t => t.stop()); streamCam = null; }
    document.getElementById('cam-stream').srcObject = null;
    document.getElementById('modal-camara-ev').style.display = 'none';
    ocultarError();
}

function cambiarCamara() {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    iniciarStream();
}

// Cerrar con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCamara(); });

// ─── Helpers ─────────────────────────────────────────────────────────────────
function mostrarError(msg) {
    const el = document.getElementById('cam-error');
    el.textContent = msg; el.style.display = '';
}
function ocultarError() {
    const el = document.getElementById('cam-error');
    el.style.display = 'none'; el.textContent = '';
}
function mostrarToastError(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#C0392B;color:#fff;padding:10px 22px;border-radius:24px;font-size:13px;font-weight:600;box-shadow:0 6px 20px rgba(0,0,0,.2);z-index:9999;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}
function mensajeErrorCamara(err) {
    if (err.name === 'NotAllowedError')  return 'Permiso de cámara denegado. Actívalo en el navegador e intenta de nuevo.';
    if (err.name === 'NotFoundError')    return 'No se encontró ninguna cámara en este dispositivo.';
    if (err.name === 'NotReadableError') return 'La cámara está siendo usada por otra aplicación.';
    return 'No se pudo acceder a la cámara: ' + err.message;
}
</script>
