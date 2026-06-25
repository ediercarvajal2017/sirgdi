<?php
/* Vista pública — Crear Reporte sin cuenta de usuario
 * Variables inyectadas: $institucion, $id_institucion, $sedes, $categorias, $csrf_token, $error
 */
$base = config('app.url_base');
$urgencias = [
    1 => ['label' => 'No urgente',  'color' => '#27AE60', 'icon' => 'fa-circle-info'],
    2 => ['label' => 'Moderado',    'color' => '#F39C12', 'icon' => 'fa-triangle-exclamation'],
    3 => ['label' => 'Importante',  'color' => '#E67E22', 'icon' => 'fa-circle-exclamation'],
    4 => ['label' => 'Urgente',     'color' => '#E74C3C', 'icon' => 'fa-bolt'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($institucion['nombre']); ?> — Reportar Daño</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #EBF5FB 0%, #F0F9FF 100%);
            min-height: 100vh;
            color: #2C3E50;
        }

        /* ── Barra superior ── */
        .inv-navbar {
            background: linear-gradient(135deg, #1A5276 0%, #2980B9 100%);
            color: #fff;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 12px rgba(26,82,118,.35);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .inv-navbar-icon {
            width: 42px; height: 42px;
            background: rgba(255,255,255,.15);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .inv-navbar-title { font-size: 18px; font-weight: 700; }
        .inv-navbar-sub { font-size: 12px; opacity: .8; margin-top: 2px; }
        .inv-navbar-badge {
            margin-left: auto;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px; font-weight: 600;
            white-space: nowrap;
        }

        /* ── Contenedor principal ── */
        .inv-main {
            max-width: 760px;
            margin: 36px auto 60px;
            padding: 0 20px;
        }

        /* ── Alerta de error ── */
        .inv-alert {
            display: flex; align-items: flex-start; gap: 12px;
            background: #FDEDEC; border: 1px solid #F5B7B1;
            border-left: 4px solid #E74C3C;
            border-radius: 10px; padding: 16px 20px; margin-bottom: 24px;
            color: #922B21; font-size: 14px;
        }
        .inv-alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        /* ── Tarjeta sección ── */
        .inv-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(52,152,219,.09);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .inv-card-head {
            display: flex; align-items: center; gap: 12px;
            padding: 18px 24px;
            background: linear-gradient(135deg, #EBF5FB, #D6EAF8);
            border-bottom: 2px solid #3498DB;
        }
        .inv-card-head-icon {
            width: 40px; height: 40px;
            background: #3498DB; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px; flex-shrink: 0;
        }
        .inv-card-head h3 { font-size: 16px; font-weight: 700; color: #1A5276; margin: 0; }
        .inv-card-head p  { font-size: 12px; color: #5D6D7E; margin: 2px 0 0; }
        .inv-card-body { padding: 24px; }

        /* ── Grid de campos ── */
        .inv-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .inv-grid-1 { display: grid; grid-template-columns: 1fr; gap: 18px; }
        @media (max-width: 600px) { .inv-grid-2 { grid-template-columns: 1fr; } }

        /* ── Campo de formulario ── */
        .inv-field { display: flex; flex-direction: column; gap: 7px; }
        .inv-label {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .4px; color: #5D6D7E;
            display: flex; align-items: center; gap: 6px;
        }
        .inv-label i { color: #3498DB; font-size: 13px; }
        .inv-label .req { color: #E74C3C; margin-left: 2px; }
        .inv-input, .inv-select, .inv-textarea {
            width: 100%; padding: 11px 14px;
            border: 2px solid #D6EAF8;
            border-radius: 8px;
            font-family: inherit; font-size: 14px;
            background: #F8FBFC; color: #2C3E50;
            transition: border-color .25s, box-shadow .25s;
        }
        .inv-input:focus, .inv-select:focus, .inv-textarea:focus {
            outline: none;
            border-color: #3498DB;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(52,152,219,.1);
        }
        .inv-input::placeholder, .inv-textarea::placeholder { color: #AEB6BF; }
        .inv-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233498DB' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 12px center; background-size: 18px;
            padding-right: 38px;
        }
        .inv-textarea { resize: vertical; min-height: 110px; line-height: 1.6; }
        .inv-hint { font-size: 11px; color: #85929E; margin-top: 2px; }

        /* ── Urgencia pills ── */
        .urgencia-pills { display: flex; gap: 10px; flex-wrap: wrap; }
        .urg-pill {
            display: flex; align-items: center; gap: 7px;
            padding: 9px 16px; border-radius: 22px; cursor: pointer;
            border: 2px solid transparent; transition: all .2s;
            font-size: 13px; font-weight: 600; user-select: none;
        }
        .urg-pill input[type="radio"] { display: none; }
        .urg-pill.urg-1 { background: rgba(39,174,96,.1); color: #27AE60; border-color: rgba(39,174,96,.3); }
        .urg-pill.urg-2 { background: rgba(243,156,18,.1); color: #E67E22; border-color: rgba(243,156,18,.3); }
        .urg-pill.urg-3 { background: rgba(230,126,34,.1); color: #D35400; border-color: rgba(230,126,34,.3); }
        .urg-pill.urg-4 { background: rgba(231,76,60,.1);  color: #E74C3C; border-color: rgba(231,76,60,.3); }
        .urg-pill.selected.urg-1 { background: #27AE60; color: #fff; border-color: #27AE60; box-shadow: 0 4px 12px rgba(39,174,96,.3); }
        .urg-pill.selected.urg-2 { background: #E67E22; color: #fff; border-color: #E67E22; box-shadow: 0 4px 12px rgba(230,126,34,.3); }
        .urg-pill.selected.urg-3 { background: #D35400; color: #fff; border-color: #D35400; box-shadow: 0 4px 12px rgba(211,84,0,.3); }
        .urg-pill.selected.urg-4 { background: #E74C3C; color: #fff; border-color: #E74C3C; box-shadow: 0 4px 12px rgba(231,76,60,.3); }

        /* ── Foto upload ── */
        .foto-zone {
            border: 2px dashed #AED6F1; border-radius: 12px;
            padding: 24px; text-align: center;
            background: #F0F9FF; cursor: pointer; transition: all .2s;
        }
        .foto-zone:hover { border-color: #3498DB; background: #EBF5FB; }
        .foto-zone i { font-size: 32px; color: #AED6F1; display: block; margin-bottom: 10px; }
        .foto-zone p { font-size: 14px; color: #85929E; margin: 0; }
        .foto-zone span { font-size: 12px; color: #AEB6BF; }
        #input-fotos-inv { display: none; }

        /* Botones de fuente */
        .foto-source-row { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 14px; }
        .foto-source-btn {
            flex: 1; min-width: 140px;
            display: flex; flex-direction: column; align-items: center; gap: 7px;
            padding: 16px 12px; border: 2px dashed #AED6F1; border-radius: 10px;
            background: #F0F9FF; cursor: pointer; transition: all .2s;
            color: #5D6D7E; font-size: 13px; font-weight: 600;
        }
        .foto-source-btn i { font-size: 22px; color: #3498DB; }
        .foto-source-btn:hover { border-color: #3498DB; background: #EBF5FB; color: #1A5276; }
        #input-cam-movil-inv { display: none; }

        /* Previews */
        .foto-previews { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .foto-preview-item {
            position: relative; width: 82px; height: 82px;
            border-radius: 8px; overflow: hidden;
            border: 2px solid #AED6F1; box-shadow: 0 2px 6px rgba(0,0,0,.08);
        }
        .foto-preview-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .foto-preview-remove {
            position: absolute; top: 3px; right: 3px;
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(231,76,60,.9); color: #fff;
            border: none; cursor: pointer; font-size: 11px;
            display: flex; align-items: center; justify-content: center;
        }
        .foto-count { font-size: 12px; color: #85929E; margin-top: 8px; }

        /* ── Botón de envío ── */
        .inv-submit-wrap { text-align: center; margin-top: 10px; }
        .inv-btn-submit {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 16px 48px; font-size: 16px; font-weight: 700;
            background: linear-gradient(135deg, #1ABC9C, #17A589);
            color: #fff; border: none; border-radius: 12px; cursor: pointer;
            text-transform: uppercase; letter-spacing: .5px;
            box-shadow: 0 6px 20px rgba(26,188,156,.35);
            transition: all .25s;
        }
        .inv-btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(26,188,156,.45); }
        .inv-btn-submit:active { transform: translateY(0); }
        .inv-btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        /* ── Toast ── */
        .inv-toast {
            position: fixed; bottom: 24px; right: 24px;
            background: #E74C3C; color: #fff;
            padding: 14px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 600;
            box-shadow: 0 6px 20px rgba(231,76,60,.4);
            z-index: 9999; display: none; max-width: 340px;
        }

        /* ── Footer info ── */
        .inv-footer {
            text-align: center; margin-top: 40px;
            font-size: 12px; color: #AEB6BF;
        }
        .inv-footer a { color: #3498DB; text-decoration: none; }
    </style>
</head>
<body>

<!-- Barra superior -->
<nav class="inv-navbar">
    <div class="inv-navbar-icon"><i class="fas fa-clipboard-list"></i></div>
    <div>
        <div class="inv-navbar-title"><?php echo htmlspecialchars($institucion['nombre']); ?></div>
        <div class="inv-navbar-sub">Sistema de Reporte de Daños</div>
    </div>
    <span class="inv-navbar-badge"><i class="fas fa-pen-to-square"></i> Nuevo Reporte</span>
</nav>

<div class="inv-main">

    <!-- Mensaje de error -->
    <?php if (!empty($error)): ?>
        <div class="inv-alert">
            <i class="fas fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="<?php echo $base; ?>/?controlador=reportes&accion=procesar_crear_invitado"
          enctype="multipart/form-data"
          id="form-invitado"
          novalidate>

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="id_institucion" value="<?php echo intval($id_institucion); ?>">

        <!-- ── Sección 1: Datos del reportante ── -->
        <div class="inv-card">
            <div class="inv-card-head">
                <div class="inv-card-head-icon"><i class="fas fa-user"></i></div>
                <div>
                    <h3>Sus datos</h3>
                    <p>Información para identificar quién reporta el daño</p>
                </div>
            </div>
            <div class="inv-card-body">
                <div class="inv-grid-2">
                    <div class="inv-field">
                        <label class="inv-label" for="nombres">
                            <i class="fas fa-id-badge"></i> Nombres <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="nombres"
                            name="nombres"
                            class="inv-input"
                            placeholder="Ej: Carlos Andrés"
                            value="<?php echo htmlspecialchars($_GET['nombres'] ?? ''); ?>"
                            required
                            autocomplete="given-name"
                            maxlength="75">
                    </div>
                    <div class="inv-field">
                        <label class="inv-label" for="apellidos">
                            <i class="fas fa-id-badge"></i> Apellidos <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="apellidos"
                            name="apellidos"
                            class="inv-input"
                            placeholder="Ej: Gómez Martínez"
                            value="<?php echo htmlspecialchars($_GET['apellidos'] ?? ''); ?>"
                            required
                            autocomplete="family-name"
                            maxlength="75">
                    </div>
                    <div class="inv-field">
                        <label class="inv-label" for="correo">
                            <i class="fas fa-envelope"></i> Correo electrónico
                        </label>
                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            class="inv-input"
                            placeholder="correo@ejemplo.com (opcional)"
                            value="<?php echo htmlspecialchars($_GET['correo'] ?? ''); ?>"
                            autocomplete="email"
                            maxlength="150">
                        <span class="inv-hint">Para recibir notificaciones del estado de su reporte.</span>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label" for="telefono">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            class="inv-input"
                            placeholder="300 000 0000 (opcional)"
                            value="<?php echo htmlspecialchars($_GET['telefono'] ?? ''); ?>"
                            autocomplete="tel"
                            maxlength="20">
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Sección 2: Ubicación ── -->
        <div class="inv-card">
            <div class="inv-card-head">
                <div class="inv-card-head-icon"><i class="fas fa-location-dot"></i></div>
                <div>
                    <h3>Ubicación del daño</h3>
                    <p>Indique dónde ocurrió el problema</p>
                </div>
            </div>
            <div class="inv-card-body">
                <div class="inv-grid-2">
                    <div class="inv-field">
                        <label class="inv-label" for="id_sede">
                            <i class="fas fa-building"></i> Sede <span class="req">*</span>
                        </label>
                        <select id="id_sede" name="id_sede" class="inv-select" required>
                            <option value="">-- Seleccione la sede --</option>
                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo intval($sede['id_sede']); ?>">
                                    <?php echo htmlspecialchars($sede['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label" for="area">
                            <i class="fas fa-door-open"></i> Área / Aula / Espacio <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="area"
                            name="area"
                            class="inv-input"
                            placeholder="Ej: Aula 203, Baño 1er piso, Cancha"
                            required
                            maxlength="255">
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Sección 3: Tipo de daño ── -->
        <div class="inv-card">
            <div class="inv-card-head">
                <div class="inv-card-head-icon"><i class="fas fa-tags"></i></div>
                <div>
                    <h3>Tipo de daño</h3>
                    <p>Clasifique el daño para que llegue al equipo correcto</p>
                </div>
            </div>
            <div class="inv-card-body">
                <div class="inv-grid-2">
                    <div class="inv-field">
                        <label class="inv-label" for="id_categoria">
                            <i class="fas fa-folder"></i> Categoría <span class="req">*</span>
                        </label>
                        <select id="id_categoria" name="id_categoria" class="inv-select" required onchange="cargarSubcats()">
                            <option value="">-- Seleccione la categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo intval($cat['id_categoria']); ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label" for="id_subcategoria">
                            <i class="fas fa-folder-open"></i> Subcategoría
                        </label>
                        <select id="id_subcategoria" name="id_subcategoria" class="inv-select" disabled>
                            <option value="">-- Seleccione primero la categoría --</option>
                        </select>
                    </div>
                </div>

                <div class="inv-field" style="margin-top:18px;">
                    <label class="inv-label">
                        <i class="fas fa-triangle-exclamation"></i> Urgencia
                    </label>
                    <div class="urgencia-pills">
                        <?php foreach ($urgencias as $id => $urg): ?>
                            <label class="urg-pill urg-<?php echo $id; ?> <?php echo $id === 1 ? 'selected' : ''; ?>">
                                <input type="radio" name="id_urgencia_declarada" value="<?php echo $id; ?>" <?php echo $id === 1 ? 'checked' : ''; ?> onchange="selUrgencia(this)">
                                <i class="fas <?php echo $urg['icon']; ?>"></i>
                                <?php echo $urg['label']; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Sección 4: Descripción ── -->
        <div class="inv-card">
            <div class="inv-card-head">
                <div class="inv-card-head-icon"><i class="fas fa-file-lines"></i></div>
                <div>
                    <h3>Descripción del daño</h3>
                    <p>Explique qué ocurrió con el mayor detalle posible</p>
                </div>
            </div>
            <div class="inv-card-body">
                <div class="inv-field">
                    <label class="inv-label" for="descripcion_problema">
                        <i class="fas fa-pen"></i> Descripción <span class="req">*</span>
                    </label>
                    <textarea
                        id="descripcion_problema"
                        name="descripcion_problema"
                        class="inv-textarea"
                        placeholder="Describa el daño: qué falló, cuándo ocurrió, qué consecuencias tiene…"
                        required
                        minlength="10"
                        maxlength="2000"></textarea>
                    <span class="inv-hint" id="desc-contador">0 / 2000 caracteres (mínimo 10)</span>
                </div>
            </div>
        </div>

        <!-- ── Sección 5: Evidencia fotográfica ── -->
        <div class="inv-card">
            <div class="inv-card-head">
                <div class="inv-card-head-icon"><i class="fas fa-camera"></i></div>
                <div>
                    <h3>Evidencia fotográfica</h3>
                    <p>Adjunte fotos del daño (opcional, máximo 5 fotos)</p>
                </div>
            </div>
            <div class="inv-card-body">
                <input type="file" id="input-fotos-inv" name="fotos[]" multiple accept="image/*,.pdf">
                <input type="file" id="input-cam-movil-inv" name="fotos[]" accept="image/*" capture="environment">

                <div class="foto-source-row">
                    <div class="foto-source-btn" onclick="document.getElementById('input-fotos-inv').click()">
                        <i class="fas fa-folder-open"></i>
                        Subir desde galería
                    </div>
                    <div class="foto-source-btn" onclick="abrirCamaraInv()">
                        <i class="fas fa-camera"></i>
                        Tomar foto
                    </div>
                </div>

                <div class="foto-previews" id="foto-previews-inv"></div>
                <p class="foto-count" id="foto-count-inv" style="display:none;"></p>
            </div>
        </div>

        <!-- ── Botón enviar ── -->
        <div class="inv-submit-wrap">
            <button type="submit" class="inv-btn-submit" id="btn-submit-inv">
                <i class="fas fa-paper-plane"></i> Enviar Reporte
            </button>
        </div>

    </form>

    <div class="inv-footer">
        ¿Ya tiene cuenta? <a href="<?php echo $base; ?>/?controlador=autenticacion&accion=inicio">Iniciar sesión</a>
        &nbsp;·&nbsp; SIRGDI v2.0
    </div>
</div>

<!-- Toast de error -->
<div class="inv-toast" id="inv-toast"></div>

<script>
const apiBase = '<?php echo $base; ?>';
const fotosSeleccionadas = [];
const MAX_FOTOS = 5;
const MAX_MB = 5;

/* ── Urgencia pills ── */
function selUrgencia(radio) {
    document.querySelectorAll('.urg-pill').forEach(p => p.classList.remove('selected'));
    radio.closest('.urg-pill').classList.add('selected');
}

/* ── Contador descripción ── */
document.getElementById('descripcion_problema').addEventListener('input', function() {
    const n = this.value.length;
    const el = document.getElementById('desc-contador');
    el.textContent = n + ' / 2000 caracteres (mínimo 10)';
    el.style.color = n < 10 ? '#E74C3C' : '#85929E';
});

/* ── Subcategorías AJAX ── */
const idInstitucion = <?php echo intval($id_institucion); ?>;
function cargarSubcats() {
    const idCat = document.getElementById('id_categoria').value;
    const sel = document.getElementById('id_subcategoria');
    sel.innerHTML = '<option value="">Cargando…</option>';
    sel.disabled = true;
    if (!idCat) { sel.innerHTML = '<option value="">-- Seleccione primero la categoría --</option>'; return; }
    fetch(apiBase + '/?controlador=reportes&accion=cargar_subcategorias_publico_json', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_categoria=' + encodeURIComponent(idCat) + '&id_institucion=' + encodeURIComponent(idInstitucion)
    })
    .then(r => r.json())
    .then(data => {
        sel.innerHTML = '<option value="">-- Ninguna --</option>';
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(sc => {
                const o = document.createElement('option');
                o.value = sc.id_subcategoria;
                o.textContent = sc.nombre;
                sel.appendChild(o);
            });
            sel.disabled = false;
        } else {
            sel.innerHTML = '<option value="">-- Sin subcategorías --</option>';
        }
    })
    .catch(() => {
        sel.innerHTML = '<option value="">-- Error al cargar --</option>';
        sel.disabled = false;
    });
}

/* ── Cámara (móvil nativo / desktop se usa galería) ── */
const esMobil = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
function abrirCamaraInv() {
    document.getElementById('input-cam-movil-inv').click();
}

/* ── Selección de archivos ── */
document.getElementById('input-fotos-inv').addEventListener('change', function() {
    procesarArchivos(Array.from(this.files));
    this.value = '';
});
document.getElementById('input-cam-movil-inv').addEventListener('change', function() {
    procesarArchivos(Array.from(this.files));
    this.value = '';
});

function procesarArchivos(files) {
    files.forEach(file => {
        if (fotosSeleccionadas.length >= MAX_FOTOS) {
            mostrarToast('Máximo ' + MAX_FOTOS + ' fotos permitidas.'); return;
        }
        if (file.size > MAX_MB * 1024 * 1024) {
            mostrarToast('"' + file.name + '" supera ' + MAX_MB + 'MB.'); return;
        }
        fotosSeleccionadas.push(file);
    });
    renderPreviews();
    sincronizarInput();
}

function renderPreviews() {
    const wrap = document.getElementById('foto-previews-inv');
    const count = document.getElementById('foto-count-inv');
    wrap.innerHTML = '';
    fotosSeleccionadas.forEach((f, idx) => {
        const div = document.createElement('div');
        div.className = 'foto-preview-item';
        const reader = new FileReader();
        reader.onload = e => {
            div.innerHTML = '<img src="' + e.target.result + '" alt="foto">'
                + '<button type="button" class="foto-preview-remove" onclick="quitarFoto(' + idx + ')"><i class="fas fa-times"></i></button>';
        };
        reader.readAsDataURL(f);
        wrap.appendChild(div);
    });
    if (fotosSeleccionadas.length > 0) {
        count.style.display = 'block';
        count.textContent = fotosSeleccionadas.length + ' foto(s) seleccionada(s)';
    } else {
        count.style.display = 'none';
    }
}

function quitarFoto(idx) {
    fotosSeleccionadas.splice(idx, 1);
    renderPreviews();
    sincronizarInput();
}

function sincronizarInput() {
    const dt = new DataTransfer();
    fotosSeleccionadas.forEach(f => dt.items.add(f));
    document.getElementById('input-fotos-inv').files = dt.files;
}

/* ── Toast ── */
function mostrarToast(msg) {
    const t = document.getElementById('inv-toast');
    t.textContent = msg;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

/* ── Validación antes de enviar ── */
document.getElementById('form-invitado').addEventListener('submit', function(e) {
    const nombres   = document.getElementById('nombres').value.trim();
    const apellidos = document.getElementById('apellidos').value.trim();
    const sede      = document.getElementById('id_sede').value;
    const area      = document.getElementById('area').value.trim();
    const cat       = document.getElementById('id_categoria').value;
    const desc      = document.getElementById('descripcion_problema').value.trim();

    if (!nombres || !apellidos) {
        e.preventDefault();
        mostrarToast('Ingrese sus nombres y apellidos.');
        document.getElementById('nombres').focus();
        return;
    }
    if (!sede) {
        e.preventDefault();
        mostrarToast('Seleccione una sede.');
        document.getElementById('id_sede').focus();
        return;
    }
    if (!area) {
        e.preventDefault();
        mostrarToast('Indique el área o espacio del daño.');
        document.getElementById('area').focus();
        return;
    }
    if (!cat) {
        e.preventDefault();
        mostrarToast('Seleccione una categoría de daño.');
        document.getElementById('id_categoria').focus();
        return;
    }
    if (desc.length < 10) {
        e.preventDefault();
        mostrarToast('La descripción debe tener al menos 10 caracteres.');
        document.getElementById('descripcion_problema').focus();
        return;
    }

    document.getElementById('btn-submit-inv').disabled = true;
    document.getElementById('btn-submit-inv').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando…';
});
</script>
</body>
</html>
