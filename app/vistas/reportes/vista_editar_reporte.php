<!-- Editar Reporte (solo en estado Registrado) -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <!-- Encabezado -->
        <div class="form-head">
            <div class="form-head-icon"><i class="fas fa-pen-to-square"></i></div>
            <div class="form-head-text">
                <h2>Editar Reporte de Daño</h2>
                <p>Actualiza la información del reporte mientras está en estado <strong>Registrado</strong>.</p>
            </div>
            <span class="ticket-badge"><i class="fas fa-hashtag"></i><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span>
        </div>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=procesar_editar" class="form" id="form-editar-reporte">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo intval($reporte['id_reporte']); ?>">

            <!-- Sección: Ubicación -->
            <fieldset class="form-section">
                <legend><i class="fas fa-location-dot"></i> Ubicación del daño</legend>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="id_sede">Sede <span class="required">*</span></label>
                        <select id="id_sede" name="id_sede" required class="input-modern">
                            <option value="">-- Seleccionar Sede --</option>
                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo $sede['id_sede']; ?>" <?php echo ((int)$reporte['id_sede'] === (int)$sede['id_sede']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sede['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="area">Ubicación específica <span class="required">*</span></label>
                        <input type="text" id="area" name="area" required class="input-modern" maxlength="100"
                               placeholder="Ej: Aula 101, Baño piso 2, Pasillo principal"
                               value="<?php echo htmlspecialchars($reporte['referencia_ubicacion_libre'] ?? ''); ?>">
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
                                <option value="<?php echo $cat['id_categoria']; ?>" <?php echo ((int)$reporte['id_categoria'] === (int)$cat['id_categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?><?php echo $cat['es_critica_escalada'] ? ' [CRÍTICA]' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_subcategoria">Subcategoría</label>
                        <select id="id_subcategoria" name="id_subcategoria" class="input-modern">
                            <option value="">-- Seleccionar Subcategoría --</option>
                            <?php foreach ($subcategorias as $sub): ?>
                                <option value="<?php echo $sub['id_subcategoria']; ?>" <?php echo ((int)($reporte['id_subcategoria'] ?? 0) === (int)$sub['id_subcategoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="id_urgencia_declarada">Nivel de Prioridad de la Incidencia</label>
                    <select id="id_urgencia_declarada" name="id_urgencia_declarada" class="input-modern">
                        <?php $u = (int)($reporte['id_urgencia_declarada'] ?? URGENCIA_NO_URGENTE); ?>
                        <option value="<?php echo URGENCIA_NO_URGENTE; ?>" <?php echo $u === URGENCIA_NO_URGENTE ? 'selected' : ''; ?>>No Urgente</option>
                        <option value="<?php echo URGENCIA_MODERADO; ?>" <?php echo $u === URGENCIA_MODERADO ? 'selected' : ''; ?>>Moderado</option>
                        <option value="<?php echo URGENCIA_IMPORTANTE; ?>" <?php echo $u === URGENCIA_IMPORTANTE ? 'selected' : ''; ?>>Importante</option>
                        <option value="<?php echo URGENCIA_URGENTE; ?>" <?php echo $u === URGENCIA_URGENTE ? 'selected' : ''; ?>>Urgente</option>
                    </select>
                    <small><i class="fas fa-circle-info"></i> Si la categoría es crítica, se escalará automáticamente a Urgente.</small>
                </div>
            </fieldset>

            <!-- Sección: Detalle -->
            <fieldset class="form-section">
                <legend><i class="fas fa-file-lines"></i> Detalle del problema</legend>
                <div class="form-group">
                    <label for="descripcion_problema">Descripción <span class="required">*</span></label>
                    <textarea id="descripcion_problema" name="descripcion_problema" required class="input-modern" rows="5"
                              placeholder="Describa el daño en detalle…"><?php echo htmlspecialchars($reporte['descripcion_problema'] ?? ''); ?></textarea>
                    <small><i class="fas fa-circle-info"></i> Mínimo 10 caracteres.</small>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-modern"><i class="fas fa-save"></i> Guardar Cambios</button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=listar" class="btn-modern-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>

<style>
    .form-modern-wrapper { max-width: 760px; margin: 36px auto; padding: 20px; }
    .form-modern-card { background: white; border-radius: 14px; padding: 0; box-shadow: 0 8px 28px rgba(52,152,219,.12); overflow: hidden; border: 1px solid #E6F0F8; }

    /* Encabezado */
    .form-head { display:flex; align-items:center; gap:18px; padding:26px 32px; background:linear-gradient(135deg,#2980B9,#3498DB); color:#fff; position:relative; }
    .form-head-icon { width:54px; height:54px; flex-shrink:0; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
    .form-head-text { flex:1; }
    .form-head-text h2 { margin:0 0 4px; font-size:23px; font-weight:700; }
    .form-head-text p { margin:0; font-size:13.5px; color:rgba(255,255,255,.9); }
    .form-head-text p strong { color:#fff; }
    .ticket-badge { display:inline-flex; align-items:center; gap:4px; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.35); padding:7px 14px; border-radius:30px; font-family:'Courier New',monospace; font-weight:700; font-size:13px; white-space:nowrap; }

    .form { padding: 28px 32px 32px; }

    /* Secciones */
    .form-section { border:1px solid #E6EDF2; border-radius:12px; padding:20px 22px 8px; margin:0 0 22px; }
    .form-section legend { padding:0 10px; font-size:13px; font-weight:700; color:#2980B9; text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:8px; }
    .form-section legend i { font-size:14px; }

    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display:block; font-weight:600; margin-bottom:8px; color:#2C3E50; font-size:14px; }
    .required { color:#E74C3C; }
    .input-modern { width:100%; padding:12px 14px; border:2px solid #DCE7EF; background:#F8FBFC; border-radius:8px; font-size:15px; font-family:inherit; transition:all .25s; box-sizing:border-box; }
    .input-modern:focus { outline:none; border-color:#3498DB; background:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.12); }
    textarea.input-modern { resize:vertical; }
    .form-group small { display:flex; align-items:center; gap:6px; color:#7F8C8D; font-size:12.5px; margin-top:6px; }
    .form-group small i { color:#3498DB; }

    .form-actions { display:flex; gap:14px; margin-top:8px; }
    .btn-modern { flex:1; padding:13px 28px; background:linear-gradient(135deg,#3498DB,#2980B9); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; text-transform:uppercase; font-size:14px; transition:all .3s; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-modern:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(52,152,219,.4); }
    .btn-modern-secondary { flex:1; padding:13px 28px; background:#ECF0F1; color:#2C3E50; border:2px solid #D5DBDB; border-radius:8px; cursor:pointer; font-weight:600; text-transform:uppercase; font-size:14px; text-decoration:none; transition:all .3s; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-modern-secondary:hover { background:#D5DBDB; transform:translateY(-2px); }
    .alert { margin:20px 32px 0; padding:14px 18px; border-radius:8px; font-size:14px; display:flex; align-items:center; gap:12px; }
    .alert-error { background:#FADBD8; color:#922B21; border:2px solid #E74C3C; }

    @media (max-width: 600px) {
        .grid-2 { grid-template-columns:1fr; gap:0; }
        .form-head { flex-wrap:wrap; padding:20px; }
        .form { padding:20px; }
        .form-actions { flex-direction:column; }
    }
</style>

<script>
    const apiBase = '<?php echo config("app.url_base"); ?>';

    function cargarSubcategorias() {
        const idCategoria = document.getElementById('id_categoria').value;
        const subcatSelect = document.getElementById('id_subcategoria');
        subcatSelect.innerHTML = '<option value="">-- Seleccionar Subcategoría --</option>';
        if (!idCategoria) return;

        fetch(apiBase + '/?controlador=reportes&accion=cargar_subcategorias_json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_categoria=' + idCategoria
        })
        .then(r => r.json())
        .then(subcats => {
            subcats.forEach(subcat => {
                const opt = document.createElement('option');
                opt.value = subcat.id_subcategoria;
                opt.textContent = subcat.nombre;
                subcatSelect.appendChild(opt);
            });
        })
        .catch(e => console.error('Error cargando subcategorías:', e));
    }

    document.getElementById('form-editar-reporte').addEventListener('submit', function(e) {
        const desc = document.getElementById('descripcion_problema').value.trim();
        if (desc.length < 10) {
            e.preventDefault();
            alert('La descripción debe tener al menos 10 caracteres.');
        }
    });
</script>
