<!-- RF-06: Create Report Form -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <!-- Encabezado -->
        <div class="form-head">
            <div class="form-head-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="form-head-text">
                <h2>Crear Nuevo Reporte de Daño</h2>
                <p>Registra un nuevo daño o incidencia en la infraestructura de tu institución.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=procesar_crear" class="form" id="form-crear-reporte" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Sección: Ubicación -->
            <fieldset class="form-section">
                <legend><i class="fas fa-location-dot"></i> Ubicación del daño</legend>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="id_sede">Sede <span class="required">*</span></label>
                        <?php $sede_unica = (count($sedes) === 1); ?>
                        <select id="id_sede" name="id_sede" required class="input-modern">
                            <?php if (!$sede_unica): ?>
                                <option value="">-- Seleccionar Sede --</option>
                            <?php endif; ?>
                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo $sede['id_sede']; ?>" <?php echo $sede_unica ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sede['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($sedes)): ?>
                            <small style="color:#E74C3C;"><i class="fas fa-exclamation-circle"></i> No hay sedes registradas. Pide al administrador que cree una sede.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="area">Ubicación específica <span class="required">*</span></label>
                        <input type="text" id="area" name="area" required class="input-modern"
                               placeholder="Ej: Aula 101, Baño piso 2, Pasillo principal" maxlength="100">
                        <small><i class="fas fa-circle-info"></i> Describe la ubicación específica del daño dentro de la sede.</small>
                    </div>
                </div>
            </fieldset>

            <!-- Sección: Clasificación -->
            <fieldset class="form-section">
                <legend><i class="fas fa-tags"></i> Clasificación</legend>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="id_categoria">Categoría <span class="required">*</span></label>
                        <select id="id_categoria" name="id_categoria" required class="input-modern" onchange="cargarSubcategorias()">
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?><?php echo $cat['es_critica_escalada'] ? ' [CRÍTICA]' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_subcategoria">Subcategoría</label>
                        <select id="id_subcategoria" name="id_subcategoria" class="input-modern">
                            <option value="">-- Seleccionar Subcategoría --</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="id_urgencia_declarada">Nivel de Prioridad de la Incidencia</label>
                    <select id="id_urgencia_declarada" name="id_urgencia_declarada" class="input-modern">
                        <option value="<?php echo URGENCIA_NO_URGENTE; ?>">No Urgente</option>
                        <option value="<?php echo URGENCIA_MODERADO; ?>">Moderado</option>
                        <option value="<?php echo URGENCIA_IMPORTANTE; ?>">Importante</option>
                        <option value="<?php echo URGENCIA_URGENTE; ?>">Urgente</option>
                    </select>
                    <small><i class="fas fa-circle-info"></i> Si la categoría es crítica, se escalará automáticamente a Urgente.</small>
                </div>
            </fieldset>

            <!-- Sección: Detalle -->
            <fieldset class="form-section">
                <legend><i class="fas fa-file-lines"></i> Detalle del problema</legend>
                <div class="form-group">
                    <label for="descripcion_problema">Descripción <span class="required">*</span></label>
                    <textarea id="descripcion_problema" name="descripcion_problema" required class="input-modern"
                              rows="5" placeholder="Describa el daño en detalle…"></textarea>
                    <small><i class="fas fa-circle-info"></i> Mínimo 10 caracteres.</small>
                </div>
            </fieldset>

            <!-- Sección: Evidencias -->
            <fieldset class="form-section">
                <legend><i class="fas fa-camera"></i> Evidencias (opcional)</legend>
                <div class="evidencia-section">
                    <div class="evidencia-type-toggle">
                        <label class="radio-group">
                            <input type="radio" name="evidencia_type" value="fotos" checked onchange="toggleEvidenciaType('fotos')">
                            Fotos (máx 5)
                        </label>
                        <label class="radio-group">
                            <input type="radio" name="evidencia_type" value="video" onchange="toggleEvidenciaType('video')">
                            Video (máx 20 seg)
                        </label>
                    </div>

                    <!-- Carga de Fotos -->
                    <div id="fotos-section" class="evidencia-container">
                        <div class="drop-zone" id="drop-zone-fotos">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Arrastra máximo 5 fotos aquí o haz clic para seleccionar</p>
                            <small>Formatos: JPG, PNG (máx 5MB c/u)</small>
                        </div>
                        <input type="file" id="input-fotos" name="fotos[]" multiple accept="image/jpeg,image/png" style="display:none;">
                        <div id="preview-fotos" class="preview-container"></div>
                    </div>

                    <!-- Carga de Video -->
                    <div id="video-section" class="evidencia-container" style="display:none;">
                        <div class="drop-zone" id="drop-zone-video">
                            <i class="fas fa-video"></i>
                            <p>Arrastra tu video aquí o haz clic para seleccionar</p>
                            <small>Formatos: MP4, WebM (máx 50MB, duración máx 20 seg)</small>
                        </div>
                        <input type="file" id="input-video" name="video" accept="video/mp4,video/webm" style="display:none;">
                        <div id="preview-video" class="preview-container"></div>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-modern btn-primary-modern">
                    <i class="fas fa-check-circle"></i> Crear Reporte
                </button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=listar" class="btn-modern btn-secondary-modern">
                    <i class="fas fa-times-circle"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
    }

    /* ===== Nuevo layout (encabezado + secciones) ===== */
    .form-modern-wrapper { max-width: 760px; margin: 36px auto; padding: 20px; }
    .form-modern-card { background:#fff; border-radius:14px; padding:0; box-shadow:0 8px 28px rgba(52,152,219,.12); overflow:hidden; border:1px solid #E6F0F8; }

    .form-head { display:flex; align-items:center; gap:18px; padding:26px 32px; background:linear-gradient(135deg,#2980B9,#3498DB); color:#fff; }
    .form-head-icon { width:54px; height:54px; flex-shrink:0; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
    .form-head-text { flex:1; }
    .form-head-text h2 { margin:0 0 4px; font-size:23px; font-weight:700; }
    .form-head-text p { margin:0; font-size:13.5px; color:rgba(255,255,255,.9); }

    .form { padding: 28px 32px 32px; }

    .form-section { border:1px solid #E6EDF2; border-radius:12px; padding:20px 22px 8px; margin:0 0 22px; }
    .form-section legend { padding:0 10px; font-size:13px; font-weight:700; color:#2980B9; text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:8px; }
    .form-section legend i { font-size:14px; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }

    .alert { margin:16px 32px 0; }

    @media (max-width: 600px) {
        .grid-2 { grid-template-columns:1fr; gap:0; }
        .form-head { flex-wrap:wrap; padding:20px; }
        .form { padding:20px; }
    }

    .form-container-modern {
        max-width: 700px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .form-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .form-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .form-header h2 i {
        color: var(--primary-blue);
        font-size: 28px;
    }

    .form-subtitle {
        color: var(--gray-text);
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }

    .form-modern {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.1);
        border-left: 4px solid var(--primary-blue);
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 10px;
        font-size: 14px;
    }

    .form-group label i {
        color: var(--primary-blue);
        font-size: 16px;
    }

    .form-group .required {
        color: #E74C3C;
        font-weight: 700;
    }

    .input-modern {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--primary-blue);
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        background-color: var(--light-bg);
        color: var(--dark-text);
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .input-modern::placeholder {
        color: var(--gray-text);
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--dark-blue);
        background-color: white;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    textarea.input-modern {
        resize: vertical;
        min-height: 100px;
    }

    .form-help {
        display: block;
        color: var(--gray-text);
        font-size: 12px;
        margin-top: 8px;
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 35px;
        flex-wrap: wrap;
    }

    .btn-modern {
        padding: 12px 28px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        min-width: 200px;
        justify-content: center;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
    }

    .btn-secondary-modern {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
        min-width: 200px;
        justify-content: center;
    }

    .btn-secondary-modern:hover {
        background: var(--gray-text);
        color: white;
        transform: translateY(-2px);
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #E74C3C;
    }

    .alert-error {
        background-color: rgba(231, 76, 60, 0.1);
        color: #C0392B;
    }

    .evidencia-section {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .evidencia-type-toggle {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .radio-group {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-weight: 500;
    }

    .radio-group input[type="radio"] {
        cursor: pointer;
    }

    .evidencia-container {
        margin-bottom: 20px;
    }

    .drop-zone {
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .drop-zone:hover,
    .drop-zone.dragover {
        background: rgba(102, 126, 234, 0.1);
        border-color: #5568d3;
    }

    .drop-zone i {
        font-size: 32px;
        color: #667eea;
        margin-bottom: 12px;
        display: block;
    }

    .drop-zone p {
        margin: 8px 0;
        font-weight: 500;
        color: #333;
    }

    .drop-zone small {
        display: block;
        color: #666;
        font-size: 12px;
    }

    .preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 12px;
        margin-top: 16px;
    }

    .preview-item {
        position: relative;
        border-radius: 6px;
        overflow: hidden;
        background: #f0f0f0;
        aspect-ratio: 1;
    }

    .preview-item img,
    .preview-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-item .remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .preview-item .remove-btn:hover {
        background: rgba(231, 76, 60, 1);
    }

    .file-info {
        font-size: 12px;
        color: #666;
        padding: 8px 4px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .error-message {
        color: #E74C3C;
        font-size: 12px;
        margin-top: 8px;
        padding: 8px;
        background: rgba(231, 76, 60, 0.1);
        border-radius: 4px;
    }

    @media (max-width: 768px) {
        .form-container-modern {
            padding: 0 15px;
        }

        .form-modern {
            padding: 25px;
        }

        .form-header h2 {
            font-size: 22px;
        }

        .btn-modern {
            width: 100%;
            justify-content: center;
            min-width: unset;
        }

        .form-actions {
            flex-direction: column;
        }

        .drop-zone {
            padding: 30px 15px;
        }

        .drop-zone i {
            font-size: 28px;
        }

        .preview-container {
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        }

        .radio-group {
            font-size: 13px;
        }
    }
</style>

<script>
const apiBase = '<?php echo config("app.url_base"); ?>';
let fotosSeleccionadas = [];
let videoSeleccionado = null;

// Toggle entre Fotos y Video
function toggleEvidenciaType(type) {
    if (type === 'fotos') {
        document.getElementById('fotos-section').style.display = 'block';
        document.getElementById('video-section').style.display = 'none';
        videoSeleccionado = null;
        document.getElementById('input-video').value = '';
        document.getElementById('preview-video').innerHTML = '';
    } else {
        document.getElementById('fotos-section').style.display = 'none';
        document.getElementById('video-section').style.display = 'block';
        fotosSeleccionadas = [];
        document.getElementById('input-fotos').value = '';
        document.getElementById('preview-fotos').innerHTML = '';
    }
}

// Setup Drag & Drop para Fotos
const dropZoneFotos = document.getElementById('drop-zone-fotos');
const inputFotos = document.getElementById('input-fotos');

dropZoneFotos.addEventListener('click', () => inputFotos.click());

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
    dropZoneFotos.addEventListener(evt, e => e.preventDefault());
    dropZoneFotos.addEventListener(evt, e => e.stopPropagation());
});

dropZoneFotos.addEventListener('dragover', () => dropZoneFotos.classList.add('dragover'));
dropZoneFotos.addEventListener('dragleave', () => dropZoneFotos.classList.remove('dragover'));
dropZoneFotos.addEventListener('drop', (e) => {
    dropZoneFotos.classList.remove('dragover');
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    procesarFotos(files);
});

inputFotos.addEventListener('change', (e) => procesarFotos(Array.from(e.target.files)));

function procesarFotos(files) {
    // Máximo 5 fotos
    if (fotosSeleccionadas.length + files.length > 5) {
        alert('Máximo 5 fotos permitidas');
        return;
    }

    files.forEach(file => {
        // Validar tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(`Foto ${file.name} supera 5MB`);
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            fotosSeleccionadas.push({ file, src: e.target.result });
            renderPreviewFotos();
        };
        reader.readAsDataURL(file);
    });

    inputFotos.value = '';
}

function renderPreviewFotos() {
    const container = document.getElementById('preview-fotos');
    container.innerHTML = '';

    fotosSeleccionadas.forEach((foto, idx) => {
        const item = document.createElement('div');
        item.className = 'preview-item';
        item.innerHTML = `
            <img src="${foto.src}" alt="Foto ${idx + 1}">
            <button type="button" class="remove-btn" onclick="removerFoto(${idx})">✕</button>
            <div class="file-info">${(foto.file.size / 1024 / 1024).toFixed(1)}MB</div>
        `;
        container.appendChild(item);
    });

    // Actualizar input file
    const dataTransfer = new DataTransfer();
    fotosSeleccionadas.forEach(f => dataTransfer.items.add(f.file));
    inputFotos.files = dataTransfer.files;
}

function removerFoto(idx) {
    fotosSeleccionadas.splice(idx, 1);
    renderPreviewFotos();
}

// Setup Drag & Drop para Video
const dropZoneVideo = document.getElementById('drop-zone-video');
const inputVideo = document.getElementById('input-video');

dropZoneVideo.addEventListener('click', () => inputVideo.click());

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
    dropZoneVideo.addEventListener(evt, e => e.preventDefault());
    dropZoneVideo.addEventListener(evt, e => e.stopPropagation());
});

dropZoneVideo.addEventListener('dragover', () => dropZoneVideo.classList.add('dragover'));
dropZoneVideo.addEventListener('dragleave', () => dropZoneVideo.classList.remove('dragover'));
dropZoneVideo.addEventListener('drop', (e) => {
    dropZoneVideo.classList.remove('dragover');
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('video/'));
    procesarVideo(files[0]);
});

inputVideo.addEventListener('change', (e) => procesarVideo(e.target.files[0]));

function procesarVideo(file) {
    if (!file) return;

    // Validar tamaño (50MB)
    if (file.size > 50 * 1024 * 1024) {
        alert('Video supera 50MB');
        return;
    }

    // Validar duración
    const video = document.createElement('video');
    video.onloadedmetadata = () => {
        if (video.duration > 20) {
            alert('Video supera 20 segundos de duración');
            inputVideo.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            videoSeleccionado = { file, src: e.target.result, duration: video.duration };
            renderPreviewVideo();
        };
        reader.readAsDataURL(file);
    };
    video.src = URL.createObjectURL(file);
}

function renderPreviewVideo() {
    const container = document.getElementById('preview-video');
    container.innerHTML = '';

    if (videoSeleccionado) {
        const item = document.createElement('div');
        item.className = 'preview-item';
        item.innerHTML = `
            <video controls>
                <source src="${videoSeleccionado.src}" type="${videoSeleccionado.file.type}">
            </video>
            <button type="button" class="remove-btn" onclick="removerVideo()">✕</button>
            <div class="file-info">${videoSeleccionado.duration.toFixed(1)}s</div>
        `;
        container.appendChild(item);

        // Actualizar input file
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(videoSeleccionado.file);
        inputVideo.files = dataTransfer.files;
    }
}

function removerVideo() {
    videoSeleccionado = null;
    inputVideo.value = '';
    renderPreviewVideo();
}

function cargarSubcategorias() {
    const idCategoria = document.getElementById('id_categoria').value;
    const subcatSelect = document.getElementById('id_subcategoria');

    if (!idCategoria) {
        subcatSelect.innerHTML = '<option value="">-- Seleccionar Subcategoría --</option>';
        return;
    }

    fetch(apiBase + '/?controlador=reportes&accion=cargar_subcategorias_json', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_categoria=' + idCategoria
    })
    .then(r => r.json())
    .then(subcats => {
        subcatSelect.innerHTML = '<option value="">-- Seleccionar Subcategoría --</option>';
        subcats.forEach(subcat => {
            const opt = document.createElement('option');
            opt.value = subcat.id_subcategoria;
            opt.textContent = subcat.nombre;
            subcatSelect.appendChild(opt);
        });
    })
    .catch(e => console.error('Error cargando subcategorías:', e));
}

// Validación al enviar
document.getElementById('form-crear-reporte').addEventListener('submit', function(e) {
    const desc = document.getElementById('descripcion_problema').value.trim();
    if (desc.length < 10) {
        e.preventDefault();
        alert('La descripción debe tener al menos 10 caracteres');
        return false;
    }
});
</script>
