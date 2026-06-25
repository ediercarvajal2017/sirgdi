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

                <!-- Tabs Fotos / Video -->
                <div class="ev-tabs">
                    <button type="button" class="ev-tab ev-tab-active" onclick="switchEvTab('fotos', this)">
                        <i class="fas fa-images"></i> Fotos
                        <span class="ev-tab-badge" id="badge-fotos" style="display:none;">0</span>
                    </button>
                    <button type="button" class="ev-tab" onclick="switchEvTab('video', this)">
                        <i class="fas fa-video"></i> Video
                        <span class="ev-tab-badge ev-tab-badge-rec" id="badge-video" style="display:none;">1</span>
                    </button>
                </div>

                <!-- PANEL FOTOS -->
                <div id="tab-fotos" class="ev-panel">
                    <div class="ev-source-row">
                        <div class="ev-source-card" id="dz-fotos" onclick="document.getElementById('input-fotos').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <strong>Subir archivo</strong>
                            <small>Arrastra o haz clic · JPG, PNG</small>
                        </div>
                        <div class="ev-source-card ev-source-cam" onclick="abrirCamaraFoto()">
                            <i class="fas fa-camera"></i>
                            <strong>Tomar foto</strong>
                            <small>Cámara en tiempo real</small>
                        </div>
                    </div>
                    <input type="file" id="input-fotos" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp" style="display:none;">
                    <input type="file" id="input-camara-movil-foto" accept="image/*" capture="environment" style="display:none;">
                    <div id="preview-fotos" class="ev-preview-grid"></div>
                    <p class="ev-hint"><i class="fas fa-circle-info"></i> Máx. 5 fotos &nbsp;·&nbsp; JPG / PNG / WebP &nbsp;·&nbsp; hasta 5 MB c/u</p>
                </div>

                <!-- PANEL VIDEO -->
                <div id="tab-video" class="ev-panel" style="display:none;">
                    <div class="ev-source-row">
                        <div class="ev-source-card" id="dz-video" onclick="document.getElementById('input-video').click()">
                            <i class="fas fa-file-video"></i>
                            <strong>Subir video</strong>
                            <small>MP4 / WebM · máx 50 MB</small>
                        </div>
                        <div class="ev-source-card ev-source-cam" onclick="abrirCamaraVideo()">
                            <i class="fas fa-circle-dot" style="color:#E74C3C;"></i>
                            <strong>Grabar video</strong>
                            <small>Cámara · máx 20 segundos</small>
                        </div>
                    </div>
                    <input type="file" id="input-video" name="video" accept="video/mp4,video/webm" style="display:none;">
                    <input type="file" id="input-camara-movil-video" accept="video/*" capture="environment" style="display:none;">
                    <div id="preview-video" class="ev-preview-grid"></div>
                    <p class="ev-hint"><i class="fas fa-circle-info"></i> Máx. 1 video &nbsp;·&nbsp; MP4 / WebM &nbsp;·&nbsp; hasta 50 MB &nbsp;·&nbsp; máx. 20 seg</p>
                </div>
            </fieldset>

            <!-- ── MODAL CÁMARA FOTO ── -->
            <div id="modal-cam-foto" class="cam-modal" role="dialog" aria-modal="true" aria-label="Tomar foto" style="display:none;">
                <div class="cam-box">
                    <div class="cam-header">
                        <span><i class="fas fa-camera"></i> Tomar foto</span>
                        <button type="button" class="cam-close" onclick="cerrarCamaraFoto()"><i class="fas fa-xmark"></i></button>
                    </div>
                    <div class="cam-body">
                        <video id="cam-foto-stream" autoplay playsinline muted class="cam-stream"></video>
                        <canvas id="cam-foto-canvas" style="display:none;"></canvas>
                        <div id="cam-foto-flash" class="cam-flash"></div>
                    </div>
                    <div class="cam-footer">
                        <button type="button" class="cam-btn-switch" id="btn-switch-cam-foto" onclick="cambiarCamara('foto')" title="Cambiar cámara" style="display:none;">
                            <i class="fas fa-rotate"></i>
                        </button>
                        <button type="button" class="cam-btn-capture" onclick="capturarFoto()">
                            <i class="fas fa-camera"></i> Capturar
                        </button>
                    </div>
                    <p id="cam-foto-error" class="cam-error" style="display:none;"></p>
                </div>
            </div>

            <!-- ── MODAL CÁMARA VIDEO ── -->
            <div id="modal-cam-video" class="cam-modal" role="dialog" aria-modal="true" aria-label="Grabar video" style="display:none;">
                <div class="cam-box">
                    <div class="cam-header">
                        <span><i class="fas fa-video"></i> Grabar video</span>
                        <button type="button" class="cam-close" onclick="cerrarCamaraVideo()"><i class="fas fa-xmark"></i></button>
                    </div>
                    <div class="cam-body">
                        <video id="cam-video-stream" autoplay playsinline muted class="cam-stream"></video>
                        <div id="rec-indicator" class="rec-indicator" style="display:none;">
                            <span class="rec-dot"></span> REC &nbsp;<span id="rec-timer">00:00</span> / 00:20
                        </div>
                    </div>
                    <div class="cam-footer">
                        <button type="button" class="cam-btn-switch" id="btn-switch-cam-video" onclick="cambiarCamara('video')" title="Cambiar cámara" style="display:none;">
                            <i class="fas fa-rotate"></i>
                        </button>
                        <button type="button" class="cam-btn-capture" id="btn-rec-start" onclick="iniciarGrabacion()">
                            <i class="fas fa-circle-dot" style="color:#E74C3C;"></i> Iniciar grabación
                        </button>
                        <button type="button" class="cam-btn-stop" id="btn-rec-stop" onclick="detenerGrabacion()" style="display:none;">
                            <i class="fas fa-square"></i> Detener
                        </button>
                    </div>
                    <p id="cam-video-error" class="cam-error" style="display:none;"></p>
                </div>
            </div>

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

    /* ── TABS EVIDENCIA ── */
    .ev-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 16px;
    }

    .ev-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 18px;
        border: 1.5px solid #D5E8F5;
        border-radius: 8px;
        background: #F4F9FD;
        color: var(--gray-text);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
        font-family: inherit;
    }

    .ev-tab:hover { background: #EBF5FB; color: var(--dark-text); }

    .ev-tab-active {
        background: var(--primary-blue);
        color: #fff;
        border-color: var(--primary-blue);
    }

    .ev-tab-badge {
        background: rgba(255,255,255,.25);
        color: #fff;
        border-radius: 10px;
        font-size: 11px;
        padding: 1px 6px;
        font-weight: 700;
    }

    .ev-tab-badge-rec { background: #E74C3C; }

    /* ── SOURCE ROW ── */
    .ev-source-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 14px;
    }

    .ev-source-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 22px 16px;
        border: 2px dashed #C8DDEF;
        border-radius: 12px;
        background: #F8FBFE;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
    }

    .ev-source-card i { font-size: 26px; color: var(--primary-blue); transition: transform .2s; }
    .ev-source-card strong { font-size: 13px; font-weight: 700; color: var(--dark-text); }
    .ev-source-card small  { font-size: 11px; color: var(--gray-text); }

    .ev-source-card:hover {
        border-color: var(--primary-blue);
        background: #EBF5FB;
    }

    .ev-source-card:hover i { transform: scale(1.1); }

    .ev-source-cam {
        border-color: #C5EAE0;
        background: #F0FAF7;
    }

    .ev-source-cam i { color: #16A085; }

    .ev-source-cam:hover {
        border-color: #1ABC9C;
        background: #E8F8F5;
    }

    .ev-source-card.dragover {
        border-color: var(--primary-blue);
        background: #EBF5FB;
    }

    /* ── PREVIEW GRID ── */
    .ev-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-bottom: 10px;
    }

    .ev-preview-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        background: #EEF2F5;
        aspect-ratio: 1;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }

    .ev-preview-item img,
    .ev-preview-item video {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }

    .ev-preview-item .ev-remove {
        position: absolute;
        top: 5px; right: 5px;
        background: rgba(231,76,60,.9);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 26px; height: 26px;
        cursor: pointer;
        font-size: 13px;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s;
    }

    .ev-preview-item .ev-remove:hover { background: #E74C3C; }

    .ev-preview-item .ev-label {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,.5);
        color: #fff;
        font-size: 10px;
        padding: 3px 6px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ev-hint {
        font-size: 11.5px;
        color: var(--gray-text);
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 4px;
    }

    /* ── CAMERA MODAL ── */
    .cam-modal {
        position: fixed;
        inset: 0;
        background: rgba(15,25,40,.7);
        backdrop-filter: blur(4px);
        z-index: 9000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .cam-box {
        background: #fff;
        border-radius: 16px;
        width: 100%;
        max-width: 520px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,.3);
    }

    .cam-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: linear-gradient(135deg, #2980B9, #3498DB);
        color: #fff;
        font-weight: 700;
        font-size: 15px;
    }

    .cam-close {
        background: rgba(255,255,255,.15);
        border: none;
        border-radius: 8px;
        color: #fff;
        width: 32px; height: 32px;
        cursor: pointer;
        font-size: 16px;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s;
    }

    .cam-close:hover { background: rgba(255,255,255,.3); }

    .cam-body {
        position: relative;
        background: #000;
        aspect-ratio: 4/3;
        max-height: 340px;
        overflow: hidden;
    }

    .cam-stream {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }

    .cam-flash {
        position: absolute;
        inset: 0;
        background: #fff;
        opacity: 0;
        pointer-events: none;
        transition: opacity .05s;
    }

    .cam-flash.flash { opacity: 1; }

    /* REC indicator */
    .rec-indicator {
        position: absolute;
        top: 12px; left: 12px;
        background: rgba(231,76,60,.9);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .rec-dot {
        width: 8px; height: 8px;
        background: #fff;
        border-radius: 50%;
        animation: blink 1s infinite;
    }

    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

    .cam-footer {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: #F8FBFC;
    }

    .cam-btn-capture {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #2980B9, #3498DB);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: all .2s;
        box-shadow: 0 4px 14px rgba(52,152,219,.35);
    }

    .cam-btn-capture:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(52,152,219,.45); }

    .cam-btn-stop {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #E74C3C;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: all .2s;
    }

    .cam-btn-stop:hover { background: #C0392B; }

    .cam-btn-switch {
        background: #EBF5FB;
        border: 1.5px solid #D5E8F5;
        border-radius: 10px;
        color: var(--primary-blue);
        width: 44px; height: 44px;
        cursor: pointer;
        font-size: 18px;
        display: flex; align-items: center; justify-content: center;
        transition: all .2s;
    }

    .cam-btn-switch:hover { background: #D5E8F5; }

    .cam-error {
        margin: 0;
        padding: 10px 20px 14px;
        background: #FDF0EF;
        color: #C0392B;
        font-size: 13px;
        text-align: center;
    }

    @media (max-width: 600px) {
        .ev-source-row { grid-template-columns: 1fr 1fr; }
        .ev-preview-grid { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
        .btn-modern { width: 100%; justify-content: center; min-width: unset; }
        .form-actions { flex-direction: column; }
    }
</style>

<script>
const apiBase = '<?php echo config("app.url_base"); ?>';

// ─── Estado global ───────────────────────────────────────────────────────────
let fotosSeleccionadas = [];
let videoSeleccionado  = null;

// Cámara
let streamFoto         = null;
let streamVideo        = null;
let mediaRecorder      = null;
let chunksGrabacion    = [];
let recTimerInterval   = null;
let recSegundos        = 0;
let facingModeFoto     = 'environment';  // 'user' | 'environment'
let facingModeVideo    = 'environment';

const esMobil = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

// ─── TABS ────────────────────────────────────────────────────────────────────
function switchEvTab(tab, btn) {
    document.querySelectorAll('.ev-tab').forEach(b => b.classList.remove('ev-tab-active'));
    btn.classList.add('ev-tab-active');
    document.getElementById('tab-fotos').style.display = tab === 'fotos' ? '' : 'none';
    document.getElementById('tab-video').style.display = tab === 'video' ? '' : 'none';
}

// ─── DRAG & DROP Fotos ───────────────────────────────────────────────────────
(function() {
    const dz = document.getElementById('dz-fotos');
    const inp = document.getElementById('input-fotos');

    ['dragenter','dragover','dragleave','drop'].forEach(ev => {
        dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
    });
    dz.addEventListener('dragover',  () => dz.classList.add('dragover'));
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        dz.classList.remove('dragover');
        procesarFotos(Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')));
    });
    inp.addEventListener('change', e => procesarFotos(Array.from(e.target.files)));

    // Cámara nativa móvil (foto)
    document.getElementById('input-camara-movil-foto')
        .addEventListener('change', e => procesarFotos(Array.from(e.target.files)));
})();

// ─── DRAG & DROP Video ───────────────────────────────────────────────────────
(function() {
    const dz  = document.getElementById('dz-video');
    const inp = document.getElementById('input-video');

    ['dragenter','dragover','dragleave','drop'].forEach(ev => {
        dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
    });
    dz.addEventListener('dragover',  () => dz.classList.add('dragover'));
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        dz.classList.remove('dragover');
        const vids = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('video/'));
        if (vids[0]) procesarVideo(vids[0]);
    });
    inp.addEventListener('change', e => procesarVideo(e.target.files[0]));

    // Cámara nativa móvil (video)
    document.getElementById('input-camara-movil-video')
        .addEventListener('change', e => procesarVideo(e.target.files[0]));
})();

// ─── PROCESAR FOTOS ──────────────────────────────────────────────────────────
function procesarFotos(files) {
    if (!files.length) return;
    if (fotosSeleccionadas.length + files.length > 5) {
        mostrarAlerta('Máximo 5 fotos permitidas. Ya tienes ' + fotosSeleccionadas.length + '.'); return;
    }
    files.forEach(file => {
        if (file.size > 5 * 1024 * 1024) {
            mostrarAlerta(file.name + ' supera 5 MB.'); return;
        }
        const reader = new FileReader();
        reader.onload = ev => {
            fotosSeleccionadas.push({ file, src: ev.target.result });
            renderPreviewFotos();
        };
        reader.readAsDataURL(file);
    });
    document.getElementById('input-fotos').value = '';
}

function renderPreviewFotos() {
    const grid = document.getElementById('preview-fotos');
    grid.innerHTML = '';
    fotosSeleccionadas.forEach((foto, idx) => {
        const el = document.createElement('div');
        el.className = 'ev-preview-item';
        el.innerHTML = `
            <img src="${foto.src}" alt="Foto ${idx+1}">
            <button type="button" class="ev-remove" onclick="removerFoto(${idx})" title="Quitar"><i class="fas fa-xmark"></i></button>
            <div class="ev-label">${(foto.file.size/1024/1024).toFixed(1)} MB</div>`;
        grid.appendChild(el);
    });
    // Sync input file
    const dt = new DataTransfer();
    fotosSeleccionadas.forEach(f => dt.items.add(f.file));
    document.getElementById('input-fotos').files = dt.files;
    // Badge
    const b = document.getElementById('badge-fotos');
    b.textContent = fotosSeleccionadas.length;
    b.style.display = fotosSeleccionadas.length ? '' : 'none';
}

function removerFoto(idx) { fotosSeleccionadas.splice(idx, 1); renderPreviewFotos(); }

// ─── PROCESAR VIDEO ──────────────────────────────────────────────────────────
function procesarVideo(file) {
    if (!file) return;
    if (file.size > 50 * 1024 * 1024) { mostrarAlerta('El video supera 50 MB.'); return; }
    const tmpVid = document.createElement('video');
    tmpVid.onloadedmetadata = () => {
        if (tmpVid.duration > 20) { mostrarAlerta('El video supera los 20 segundos.'); return; }
        const reader = new FileReader();
        reader.onload = ev => {
            videoSeleccionado = { file, src: ev.target.result, duration: tmpVid.duration };
            renderPreviewVideo();
        };
        reader.readAsDataURL(file);
    };
    tmpVid.src = URL.createObjectURL(file);
}

function renderPreviewVideo() {
    const grid = document.getElementById('preview-video');
    grid.innerHTML = '';
    if (!videoSeleccionado) {
        document.getElementById('badge-video').style.display = 'none'; return;
    }
    const el = document.createElement('div');
    el.className = 'ev-preview-item';
    el.innerHTML = `
        <video controls src="${videoSeleccionado.src}"></video>
        <button type="button" class="ev-remove" onclick="removerVideo()" title="Quitar"><i class="fas fa-xmark"></i></button>
        <div class="ev-label">${videoSeleccionado.duration.toFixed(1)} s</div>`;
    grid.appendChild(el);
    // Sync input file
    const dt = new DataTransfer();
    dt.items.add(videoSeleccionado.file);
    document.getElementById('input-video').files = dt.files;
    // Badge
    document.getElementById('badge-video').style.display = '';
}

function removerVideo() {
    videoSeleccionado = null;
    document.getElementById('input-video').value = '';
    renderPreviewVideo();
}

// ─── CÁMARA FOTO ─────────────────────────────────────────────────────────────
function abrirCamaraFoto() {
    if (esMobil) {
        // Móvil: abrir cámara nativa
        document.getElementById('input-camara-movil-foto').click(); return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarAlerta('Tu navegador no soporta acceso a la cámara. Usa la opción "Subir archivo".');
        return;
    }
    document.getElementById('modal-cam-foto').style.display = 'flex';
    iniciarStreamFoto();
}

async function iniciarStreamFoto() {
    ocultarError('cam-foto-error');
    try {
        if (streamFoto) { streamFoto.getTracks().forEach(t => t.stop()); }
        streamFoto = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingModeFoto, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        });
        document.getElementById('cam-foto-stream').srcObject = streamFoto;

        // Mostrar botón de cambiar cámara si hay >1 dispositivo
        navigator.mediaDevices.enumerateDevices().then(devs => {
            const cams = devs.filter(d => d.kind === 'videoinput');
            document.getElementById('btn-switch-cam-foto').style.display = cams.length > 1 ? '' : 'none';
        });
    } catch(err) {
        mostrarError('cam-foto-error', mensajeErrorCamara(err));
    }
}

function capturarFoto() {
    const video  = document.getElementById('cam-foto-stream');
    const canvas = document.getElementById('cam-foto-canvas');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    // Flash visual
    const flash = document.getElementById('cam-foto-flash');
    flash.classList.add('flash');
    setTimeout(() => flash.classList.remove('flash'), 150);

    canvas.toBlob(blob => {
        if (!blob) return;
        if (fotosSeleccionadas.length >= 5) {
            mostrarAlerta('Ya tienes 5 fotos. Elimina una antes de capturar otra.'); return;
        }
        const nombre = 'foto_camara_' + Date.now() + '.jpg';
        const file = new File([blob], nombre, { type: 'image/jpeg' });
        const reader = new FileReader();
        reader.onload = ev => {
            fotosSeleccionadas.push({ file, src: ev.target.result });
            renderPreviewFotos();
        };
        reader.readAsDataURL(file);
    }, 'image/jpeg', 0.88);
}

function cerrarCamaraFoto() {
    if (streamFoto) { streamFoto.getTracks().forEach(t => t.stop()); streamFoto = null; }
    document.getElementById('cam-foto-stream').srcObject = null;
    document.getElementById('modal-cam-foto').style.display = 'none';
    ocultarError('cam-foto-error');
}

// ─── CÁMARA VIDEO ─────────────────────────────────────────────────────────────
function abrirCamaraVideo() {
    if (esMobil) {
        document.getElementById('input-camara-movil-video').click(); return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarAlerta('Tu navegador no soporta acceso a la cámara. Usa la opción "Subir archivo".');
        return;
    }
    document.getElementById('modal-cam-video').style.display = 'flex';
    iniciarStreamVideo();
}

async function iniciarStreamVideo() {
    ocultarError('cam-video-error');
    try {
        if (streamVideo) { streamVideo.getTracks().forEach(t => t.stop()); }
        streamVideo = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingModeVideo, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: true
        });
        document.getElementById('cam-video-stream').srcObject = streamVideo;

        navigator.mediaDevices.enumerateDevices().then(devs => {
            const cams = devs.filter(d => d.kind === 'videoinput');
            document.getElementById('btn-switch-cam-video').style.display = cams.length > 1 ? '' : 'none';
        });
    } catch(err) {
        mostrarError('cam-video-error', mensajeErrorCamara(err));
    }
}

function iniciarGrabacion() {
    if (!streamVideo) return;
    chunksGrabacion = [];
    recSegundos     = 0;

    const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9')
        ? 'video/webm;codecs=vp9'
        : (MediaRecorder.isTypeSupported('video/webm') ? 'video/webm' : 'video/mp4');

    mediaRecorder = new MediaRecorder(streamVideo, { mimeType });
    mediaRecorder.ondataavailable = e => { if (e.data.size > 0) chunksGrabacion.push(e.data); };
    mediaRecorder.onstop = finalizarGrabacion;
    mediaRecorder.start(200);

    // UI
    document.getElementById('btn-rec-start').style.display = 'none';
    document.getElementById('btn-rec-stop').style.display  = '';
    document.getElementById('rec-indicator').style.display = 'flex';

    // Timer y auto-stop a 20s
    recTimerInterval = setInterval(() => {
        recSegundos++;
        const mm = String(Math.floor(recSegundos / 60)).padStart(2,'0');
        const ss = String(recSegundos % 60).padStart(2,'0');
        document.getElementById('rec-timer').textContent = mm + ':' + ss;
        if (recSegundos >= 20) detenerGrabacion();
    }, 1000);
}

function detenerGrabacion() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    clearInterval(recTimerInterval);
    document.getElementById('btn-rec-start').style.display = '';
    document.getElementById('btn-rec-stop').style.display  = 'none';
    document.getElementById('rec-indicator').style.display = 'none';
    document.getElementById('rec-timer').textContent = '00:00';
}

function finalizarGrabacion() {
    const mimeType = chunksGrabacion[0]?.type || 'video/webm';
    const blob = new Blob(chunksGrabacion, { type: mimeType });
    const ext  = mimeType.includes('mp4') ? 'mp4' : 'webm';
    const file = new File([blob], 'video_camara_' + Date.now() + '.' + ext, { type: mimeType });
    videoSeleccionado = { file, src: URL.createObjectURL(blob), duration: recSegundos };
    renderPreviewVideo();
    cerrarCamaraVideo();
}

function cerrarCamaraVideo() {
    detenerGrabacion();
    if (streamVideo) { streamVideo.getTracks().forEach(t => t.stop()); streamVideo = null; }
    document.getElementById('cam-video-stream').srcObject = null;
    document.getElementById('modal-cam-video').style.display = 'none';
    ocultarError('cam-video-error');
}

// ─── CAMBIAR CÁMARA (front/back) ─────────────────────────────────────────────
function cambiarCamara(tipo) {
    if (tipo === 'foto') {
        facingModeFoto = facingModeFoto === 'environment' ? 'user' : 'environment';
        iniciarStreamFoto();
    } else {
        facingModeVideo = facingModeVideo === 'environment' ? 'user' : 'environment';
        iniciarStreamVideo();
    }
}

// Cerrar modales con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarCamaraFoto(); cerrarCamaraVideo(); }
});

// ─── SUBCATEGORÍAS (AJAX) ────────────────────────────────────────────────────
function cargarSubcategorias() {
    const idCategoria = document.getElementById('id_categoria').value;
    const subcatSelect = document.getElementById('id_subcategoria');

    if (!idCategoria) {
        subcatSelect.innerHTML = '<option value="">-- Seleccionar Subcategoría --</option>';
        return;
    }

    fetch(apiBase + '/?controlador=reportes&accion=cargar_subcategorias_json', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

// ─── VALIDACIÓN SUBMIT ───────────────────────────────────────────────────────
document.getElementById('form-crear-reporte').addEventListener('submit', function(e) {
    const desc = document.getElementById('descripcion_problema').value.trim();
    if (desc.length < 10) {
        e.preventDefault();
        mostrarAlerta('La descripción debe tener al menos 10 caracteres.');
    }
});

// ─── HELPERS ─────────────────────────────────────────────────────────────────
function mostrarAlerta(msg) {
    // Toast no-intrusivo en lugar de alert()
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#C0392B;color:#fff;padding:10px 22px;border-radius:24px;font-size:13px;font-weight:600;box-shadow:0 6px 20px rgba(0,0,0,.2);z-index:9999;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function mostrarError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.style.display = ''; }
}

function ocultarError(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'none'; el.textContent = ''; }
}

function mensajeErrorCamara(err) {
    if (err.name === 'NotAllowedError')  return 'Acceso a la cámara denegado. Permite el permiso en tu navegador e intenta de nuevo.';
    if (err.name === 'NotFoundError')    return 'No se encontró ninguna cámara en este dispositivo.';
    if (err.name === 'NotReadableError') return 'La cámara está siendo usada por otra aplicación.';
    return 'No se pudo acceder a la cámara: ' + err.message;
}
</script>
