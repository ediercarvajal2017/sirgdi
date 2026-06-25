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

        /* ── Evidencia tabs ── */
        .ev-tabs { display:flex; gap:6px; margin-bottom:16px; }
        .ev-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 18px; border:1.5px solid #D5E8F5; border-radius:8px; background:#F4F9FD; color:#808B96; font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; font-family:inherit; }
        .ev-tab:hover { background:#EBF5FB; color:#2C3E50; }
        .ev-tab-active { background:#3498DB; color:#fff; border-color:#3498DB; }
        .ev-tab-badge { background:rgba(255,255,255,.25); color:#fff; border-radius:10px; font-size:11px; padding:1px 6px; font-weight:700; }
        .ev-tab-badge-rec { background:#E74C3C; }

        /* ── Source row ── */
        .ev-source-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
        .ev-source-card { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; padding:22px 16px; border:2px dashed #C8DDEF; border-radius:12px; background:#F8FBFE; cursor:pointer; transition:all .2s; text-align:center; }
        .ev-source-card i { font-size:26px; color:#3498DB; transition:transform .2s; }
        .ev-source-card strong { font-size:13px; font-weight:700; color:#2C3E50; }
        .ev-source-card small { font-size:11px; color:#808B96; }
        .ev-source-card:hover { border-color:#3498DB; background:#EBF5FB; }
        .ev-source-card:hover i { transform:scale(1.1); }
        .ev-source-cam { border-color:#C5EAE0; background:#F0FAF7; }
        .ev-source-cam i { color:#16A085; }
        .ev-source-cam:hover { border-color:#1ABC9C; background:#E8F8F5; }
        .ev-source-card.dragover { border-color:#3498DB; background:#EBF5FB; }

        /* ── Preview grid ── */
        .ev-preview-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); gap:10px; margin-bottom:10px; }
        .ev-preview-item { position:relative; border-radius:10px; overflow:hidden; background:#EEF2F5; aspect-ratio:1; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .ev-preview-item img, .ev-preview-item video { width:100%; height:100%; object-fit:cover; display:block; }
        .ev-preview-item .ev-remove { position:absolute; top:5px; right:5px; background:rgba(231,76,60,.9); color:#fff; border:none; border-radius:50%; width:24px; height:24px; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center; }
        .ev-preview-item .ev-remove:hover { background:#E74C3C; }
        .ev-preview-item .ev-label { position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,.5); color:#fff; font-size:10px; padding:3px 6px; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ev-hint { font-size:11.5px; color:#808B96; display:flex; align-items:center; gap:5px; margin-top:4px; }

        /* ── Camera modal ── */
        .cam-modal { position:fixed; inset:0; background:rgba(15,25,40,.7); backdrop-filter:blur(4px); z-index:9000; display:flex; align-items:center; justify-content:center; padding:16px; }
        .cam-box { background:#fff; border-radius:16px; width:100%; max-width:520px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.3); }
        .cam-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:linear-gradient(135deg,#2980B9,#3498DB); color:#fff; font-weight:700; font-size:15px; }
        .cam-close { background:rgba(255,255,255,.15); border:none; border-radius:8px; color:#fff; width:32px; height:32px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:background .2s; }
        .cam-close:hover { background:rgba(255,255,255,.3); }
        .cam-body { position:relative; background:#000; aspect-ratio:4/3; max-height:340px; overflow:hidden; }
        .cam-stream { width:100%; height:100%; object-fit:cover; display:block; }
        .cam-flash { position:absolute; inset:0; background:#fff; opacity:0; pointer-events:none; transition:opacity .05s; }
        .cam-flash.flash { opacity:1; }
        .rec-indicator { position:absolute; top:12px; left:12px; background:rgba(231,76,60,.9); color:#fff; font-size:12px; font-weight:700; padding:5px 12px; border-radius:20px; display:flex; align-items:center; gap:6px; }
        .rec-dot { width:8px; height:8px; background:#fff; border-radius:50%; animation:blink 1s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
        .cam-footer { padding:16px 20px; display:flex; align-items:center; justify-content:center; gap:12px; background:#F8FBFC; }
        .cam-btn-capture { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#2980B9,#3498DB); color:#fff; border:none; border-radius:10px; padding:12px 28px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; transition:all .2s; box-shadow:0 4px 14px rgba(52,152,219,.35); }
        .cam-btn-capture:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(52,152,219,.45); }
        .cam-btn-stop { display:inline-flex; align-items:center; gap:8px; background:#E74C3C; color:#fff; border:none; border-radius:10px; padding:12px 28px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; transition:all .2s; }
        .cam-btn-stop:hover { background:#C0392B; }
        .cam-btn-switch { background:#EBF5FB; border:1.5px solid #D5E8F5; border-radius:10px; color:#3498DB; width:44px; height:44px; cursor:pointer; font-size:18px; display:flex; align-items:center; justify-content:center; transition:all .2s; }
        .cam-btn-switch:hover { background:#D5E8F5; }
        .cam-error { margin:0; padding:10px 20px 14px; background:#FDF0EF; color:#C0392B; font-size:13px; text-align:center; }

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

        <!-- ── Sección 5: Evidencias ── -->
        <div class="inv-card">
            <div class="inv-card-head">
                <div class="inv-card-head-icon"><i class="fas fa-photo-film"></i></div>
                <div>
                    <h3>Evidencias</h3>
                    <p>Adjunte fotos o video del daño (opcional)</p>
                </div>
            </div>
            <div class="inv-card-body">

                <!-- Tabs -->
                <div class="ev-tabs">
                    <button type="button" class="ev-tab ev-tab-active" onclick="switchEvTabInv('fotos', this)">
                        <i class="fas fa-images"></i> Fotos
                        <span class="ev-tab-badge" id="badge-fotos-inv" style="display:none;">0</span>
                    </button>
                    <button type="button" class="ev-tab" onclick="switchEvTabInv('video', this)">
                        <i class="fas fa-video"></i> Video
                        <span class="ev-tab-badge ev-tab-badge-rec" id="badge-video-inv" style="display:none;">1</span>
                    </button>
                </div>

                <!-- PANEL FOTOS -->
                <div id="tab-fotos-inv">
                    <div class="ev-source-row">
                        <div class="ev-source-card" id="dz-fotos-inv" onclick="document.getElementById('input-fotos-inv').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <strong>Subir archivo</strong>
                            <small>Arrastra o haz clic · JPG, PNG</small>
                        </div>
                        <div class="ev-source-card ev-source-cam" onclick="abrirCamaraFotoInv()">
                            <i class="fas fa-camera"></i>
                            <strong>Tomar foto</strong>
                            <small>Cámara en tiempo real</small>
                        </div>
                    </div>
                    <input type="file" id="input-fotos-inv" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp" style="display:none;">
                    <input type="file" id="input-camara-movil-foto-inv" accept="image/*" capture="environment" style="display:none;">
                    <div id="preview-fotos-inv" class="ev-preview-grid"></div>
                    <p class="ev-hint"><i class="fas fa-circle-info"></i> Máx. 5 fotos &nbsp;·&nbsp; JPG / PNG / WebP &nbsp;·&nbsp; hasta 5 MB c/u</p>
                </div>

                <!-- PANEL VIDEO -->
                <div id="tab-video-inv" style="display:none;">
                    <div class="ev-source-row">
                        <div class="ev-source-card" id="dz-video-inv" onclick="document.getElementById('input-video-inv').click()">
                            <i class="fas fa-file-video"></i>
                            <strong>Subir video</strong>
                            <small>MP4 / WebM · máx 50 MB</small>
                        </div>
                        <div class="ev-source-card ev-source-cam" onclick="abrirCamaraVideoInv()">
                            <i class="fas fa-circle-dot" style="color:#E74C3C;"></i>
                            <strong>Grabar video</strong>
                            <small>Cámara · máx 20 segundos</small>
                        </div>
                    </div>
                    <input type="file" id="input-video-inv" name="video" accept="video/mp4,video/webm" style="display:none;">
                    <input type="file" id="input-camara-movil-video-inv" accept="video/*" capture="environment" style="display:none;">
                    <div id="preview-video-inv" class="ev-preview-grid"></div>
                    <p class="ev-hint"><i class="fas fa-circle-info"></i> Máx. 1 video &nbsp;·&nbsp; MP4 / WebM &nbsp;·&nbsp; hasta 50 MB &nbsp;·&nbsp; máx. 20 seg</p>
                </div>
            </div>
        </div>

        <!-- ── MODAL CÁMARA FOTO ── -->
        <div id="modal-cam-foto-inv" class="cam-modal" role="dialog" aria-modal="true" aria-label="Tomar foto" style="display:none;">
            <div class="cam-box">
                <div class="cam-header">
                    <span><i class="fas fa-camera"></i> Tomar foto</span>
                    <button type="button" class="cam-close" onclick="cerrarCamaraFotoInv()"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="cam-body">
                    <video id="cam-foto-stream-inv" autoplay playsinline muted class="cam-stream"></video>
                    <canvas id="cam-foto-canvas-inv" style="display:none;"></canvas>
                    <div id="cam-foto-flash-inv" class="cam-flash"></div>
                </div>
                <div class="cam-footer">
                    <button type="button" class="cam-btn-switch" id="btn-switch-cam-foto-inv" onclick="cambiarCamaraInv('foto')" title="Cambiar cámara" style="display:none;">
                        <i class="fas fa-rotate"></i>
                    </button>
                    <button type="button" class="cam-btn-capture" onclick="capturarFotoInv()">
                        <i class="fas fa-camera"></i> Capturar
                    </button>
                </div>
                <p id="cam-foto-error-inv" class="cam-error" style="display:none;"></p>
            </div>
        </div>

        <!-- ── MODAL CÁMARA VIDEO ── -->
        <div id="modal-cam-video-inv" class="cam-modal" role="dialog" aria-modal="true" aria-label="Grabar video" style="display:none;">
            <div class="cam-box">
                <div class="cam-header">
                    <span><i class="fas fa-video"></i> Grabar video</span>
                    <button type="button" class="cam-close" onclick="cerrarCamaraVideoInv()"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="cam-body">
                    <video id="cam-video-stream-inv" autoplay playsinline muted class="cam-stream"></video>
                    <div id="rec-indicator-inv" class="rec-indicator" style="display:none;">
                        <span class="rec-dot"></span> REC &nbsp;<span id="rec-timer-inv">00:00</span> / 00:20
                    </div>
                </div>
                <div class="cam-footer">
                    <button type="button" class="cam-btn-switch" id="btn-switch-cam-video-inv" onclick="cambiarCamaraInv('video')" title="Cambiar cámara" style="display:none;">
                        <i class="fas fa-rotate"></i>
                    </button>
                    <button type="button" class="cam-btn-capture" id="btn-rec-start-inv" onclick="iniciarGrabacionInv()">
                        <i class="fas fa-circle-dot" style="color:#E74C3C;"></i> Iniciar grabación
                    </button>
                    <button type="button" class="cam-btn-stop" id="btn-rec-stop-inv" onclick="detenerGrabacionInv()" style="display:none;">
                        <i class="fas fa-square"></i> Detener
                    </button>
                </div>
                <p id="cam-video-error-inv" class="cam-error" style="display:none;"></p>
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
const idInstitucion = <?php echo intval($id_institucion); ?>;

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

/* ── Estado global de evidencias ── */
let fotosSeleccionadas = [];
let videoSeleccionado  = null;
let streamFotoInv = null, streamVideoInv = null;
let mediaRecorderInv = null, chunksInv = [], recTimerInv = null, recSegsInv = 0;
let facingFoto = 'environment', facingVideo = 'environment';
const esMobil = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

/* ── Tabs ── */
function switchEvTabInv(tab, btn) {
    document.querySelectorAll('.ev-tab').forEach(b => b.classList.remove('ev-tab-active'));
    btn.classList.add('ev-tab-active');
    document.getElementById('tab-fotos-inv').style.display = tab === 'fotos' ? '' : 'none';
    document.getElementById('tab-video-inv').style.display = tab === 'video' ? '' : 'none';
}

/* ── Drag & drop fotos ── */
(function() {
    const dz  = document.getElementById('dz-fotos-inv');
    const inp = document.getElementById('input-fotos-inv');
    ['dragenter','dragover','dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); }));
    dz.addEventListener('dragover',  () => dz.classList.add('dragover'));
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        dz.classList.remove('dragover');
        procesarFotosInv(Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')));
    });
    inp.addEventListener('change', e => { procesarFotosInv(Array.from(e.target.files)); e.target.value = ''; });
    document.getElementById('input-camara-movil-foto-inv')
        .addEventListener('change', e => { procesarFotosInv(Array.from(e.target.files)); e.target.value = ''; });
})();

/* ── Drag & drop video ── */
(function() {
    const dz  = document.getElementById('dz-video-inv');
    const inp = document.getElementById('input-video-inv');
    ['dragenter','dragover','dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); }));
    dz.addEventListener('dragover',  () => dz.classList.add('dragover'));
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        dz.classList.remove('dragover');
        const vids = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('video/'));
        if (vids[0]) procesarVideoInv(vids[0]);
    });
    inp.addEventListener('change', e => procesarVideoInv(e.target.files[0]));
    document.getElementById('input-camara-movil-video-inv')
        .addEventListener('change', e => procesarVideoInv(e.target.files[0]));
})();

/* ── Procesar fotos ── */
function procesarFotosInv(files) {
    if (!files.length) return;
    files.forEach(file => {
        if (fotosSeleccionadas.length >= 5) { mostrarToast('Máximo 5 fotos permitidas.'); return; }
        if (file.size > 5 * 1024 * 1024) { mostrarToast('"' + file.name + '" supera 5 MB.'); return; }
        const reader = new FileReader();
        reader.onload = ev => {
            // Copiar a File en memoria para evitar ERR_UPLOAD_FILE_CHANGED en Android
            const b64 = ev.target.result.split(',');
            const mime = b64[0].match(/:(.*?);/)[1];
            const bin = atob(b64[1]);
            const u8 = new Uint8Array(bin.length);
            for (let i = 0; i < bin.length; i++) u8[i] = bin.charCodeAt(i);
            const memFile = new File([u8], file.name || 'foto.jpg', { type: mime });
            fotosSeleccionadas.push({ file: memFile, src: ev.target.result });
            renderFotosInv();
        };
        reader.readAsDataURL(file);
    });
}

function renderFotosInv() {
    const grid = document.getElementById('preview-fotos-inv');
    grid.innerHTML = '';
    fotosSeleccionadas.forEach((foto, idx) => {
        const el = document.createElement('div');
        el.className = 'ev-preview-item';
        el.innerHTML = `<img src="${foto.src}" alt="Foto ${idx+1}">
            <button type="button" class="ev-remove" onclick="quitarFotoInv(${idx})" title="Quitar"><i class="fas fa-xmark"></i></button>
            <div class="ev-label">${(foto.file.size/1024/1024).toFixed(1)} MB</div>`;
        grid.appendChild(el);
    });
    const dt = new DataTransfer();
    fotosSeleccionadas.forEach(f => dt.items.add(f.file));
    document.getElementById('input-fotos-inv').files = dt.files;
    const b = document.getElementById('badge-fotos-inv');
    b.textContent = fotosSeleccionadas.length;
    b.style.display = fotosSeleccionadas.length ? '' : 'none';
}

function quitarFotoInv(idx) { fotosSeleccionadas.splice(idx, 1); renderFotosInv(); }

/* ── Procesar video ── */
function procesarVideoInv(file) {
    if (!file) return;
    if (file.size > 50 * 1024 * 1024) { mostrarToast('El video supera 50 MB.'); return; }
    const tmpVid = document.createElement('video');
    tmpVid.onloadedmetadata = () => {
        if (tmpVid.duration > 20) { mostrarToast('El video supera los 20 segundos.'); return; }
        const reader = new FileReader();
        reader.onload = ev => {
            videoSeleccionado = { file, src: ev.target.result, duration: tmpVid.duration };
            renderVideoInv();
        };
        reader.readAsDataURL(file);
    };
    tmpVid.src = URL.createObjectURL(file);
}

function renderVideoInv() {
    const grid = document.getElementById('preview-video-inv');
    grid.innerHTML = '';
    if (!videoSeleccionado) {
        document.getElementById('badge-video-inv').style.display = 'none'; return;
    }
    const el = document.createElement('div');
    el.className = 'ev-preview-item';
    el.innerHTML = `<video controls src="${videoSeleccionado.src}"></video>
        <button type="button" class="ev-remove" onclick="quitarVideoInv()" title="Quitar"><i class="fas fa-xmark"></i></button>
        <div class="ev-label">${videoSeleccionado.duration.toFixed(1)} s</div>`;
    grid.appendChild(el);
    const dt = new DataTransfer();
    dt.items.add(videoSeleccionado.file);
    document.getElementById('input-video-inv').files = dt.files;
    document.getElementById('badge-video-inv').style.display = '';
}

function quitarVideoInv() {
    videoSeleccionado = null;
    document.getElementById('input-video-inv').value = '';
    renderVideoInv();
}

/* ── Cámara foto ── */
function abrirCamaraFotoInv() {
    if (esMobil) { document.getElementById('input-camara-movil-foto-inv').click(); return; }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarToast('Tu navegador no soporta la cámara. Usa "Subir archivo".');
        return;
    }
    document.getElementById('modal-cam-foto-inv').style.display = 'flex';
    iniciarStreamFotoInv();
}

async function iniciarStreamFotoInv() {
    ocultarErrorInv('cam-foto-error-inv');
    try {
        if (streamFotoInv) streamFotoInv.getTracks().forEach(t => t.stop());
        streamFotoInv = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingFoto, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false
        });
        document.getElementById('cam-foto-stream-inv').srcObject = streamFotoInv;
        navigator.mediaDevices.enumerateDevices().then(devs => {
            const cams = devs.filter(d => d.kind === 'videoinput');
            document.getElementById('btn-switch-cam-foto-inv').style.display = cams.length > 1 ? '' : 'none';
        });
    } catch(err) {
        mostrarErrorInv('cam-foto-error-inv', mensajeErrorCamaraInv(err));
    }
}

function capturarFotoInv() {
    const video  = document.getElementById('cam-foto-stream-inv');
    const canvas = document.getElementById('cam-foto-canvas-inv');
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const flash = document.getElementById('cam-foto-flash-inv');
    flash.classList.add('flash');
    setTimeout(() => flash.classList.remove('flash'), 150);
    canvas.toBlob(blob => {
        if (!blob) return;
        if (fotosSeleccionadas.length >= 5) { mostrarToast('Ya tienes 5 fotos. Elimina una antes de capturar otra.'); return; }
        const file = new File([blob], 'foto_camara_' + Date.now() + '.jpg', { type: 'image/jpeg' });
        const reader = new FileReader();
        reader.onload = ev => { fotosSeleccionadas.push({ file, src: ev.target.result }); renderFotosInv(); };
        reader.readAsDataURL(file);
    }, 'image/jpeg', 0.88);
}

function cerrarCamaraFotoInv() {
    if (streamFotoInv) { streamFotoInv.getTracks().forEach(t => t.stop()); streamFotoInv = null; }
    document.getElementById('cam-foto-stream-inv').srcObject = null;
    document.getElementById('modal-cam-foto-inv').style.display = 'none';
    ocultarErrorInv('cam-foto-error-inv');
}

/* ── Cámara video ── */
function abrirCamaraVideoInv() {
    if (esMobil) { document.getElementById('input-camara-movil-video-inv').click(); return; }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarToast('Tu navegador no soporta la cámara. Usa "Subir archivo".');
        return;
    }
    document.getElementById('modal-cam-video-inv').style.display = 'flex';
    iniciarStreamVideoInv();
}

async function iniciarStreamVideoInv() {
    ocultarErrorInv('cam-video-error-inv');
    try {
        if (streamVideoInv) streamVideoInv.getTracks().forEach(t => t.stop());
        streamVideoInv = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingVideo, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: true
        });
        document.getElementById('cam-video-stream-inv').srcObject = streamVideoInv;
        navigator.mediaDevices.enumerateDevices().then(devs => {
            const cams = devs.filter(d => d.kind === 'videoinput');
            document.getElementById('btn-switch-cam-video-inv').style.display = cams.length > 1 ? '' : 'none';
        });
    } catch(err) {
        mostrarErrorInv('cam-video-error-inv', mensajeErrorCamaraInv(err));
    }
}

function iniciarGrabacionInv() {
    if (!streamVideoInv) return;
    chunksInv = []; recSegsInv = 0;
    const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9') ? 'video/webm;codecs=vp9'
        : (MediaRecorder.isTypeSupported('video/webm') ? 'video/webm' : 'video/mp4');
    mediaRecorderInv = new MediaRecorder(streamVideoInv, { mimeType });
    mediaRecorderInv.ondataavailable = e => { if (e.data.size > 0) chunksInv.push(e.data); };
    mediaRecorderInv.onstop = finalizarGrabacionInv;
    mediaRecorderInv.start(200);
    document.getElementById('btn-rec-start-inv').style.display = 'none';
    document.getElementById('btn-rec-stop-inv').style.display = '';
    document.getElementById('rec-indicator-inv').style.display = 'flex';
    recTimerInv = setInterval(() => {
        recSegsInv++;
        const mm = String(Math.floor(recSegsInv / 60)).padStart(2,'0');
        const ss = String(recSegsInv % 60).padStart(2,'0');
        document.getElementById('rec-timer-inv').textContent = mm + ':' + ss;
        if (recSegsInv >= 20) detenerGrabacionInv();
    }, 1000);
}

function detenerGrabacionInv() {
    if (mediaRecorderInv && mediaRecorderInv.state !== 'inactive') mediaRecorderInv.stop();
    clearInterval(recTimerInv);
    document.getElementById('btn-rec-start-inv').style.display = '';
    document.getElementById('btn-rec-stop-inv').style.display = 'none';
    document.getElementById('rec-indicator-inv').style.display = 'none';
    document.getElementById('rec-timer-inv').textContent = '00:00';
}

function finalizarGrabacionInv() {
    const mimeType = chunksInv[0]?.type || 'video/webm';
    const blob = new Blob(chunksInv, { type: mimeType });
    const ext  = mimeType.includes('mp4') ? 'mp4' : 'webm';
    const file = new File([blob], 'video_camara_' + Date.now() + '.' + ext, { type: mimeType });
    videoSeleccionado = { file, src: URL.createObjectURL(blob), duration: recSegsInv };
    renderVideoInv();
    cerrarCamaraVideoInv();
}

function cerrarCamaraVideoInv() {
    detenerGrabacionInv();
    if (streamVideoInv) { streamVideoInv.getTracks().forEach(t => t.stop()); streamVideoInv = null; }
    document.getElementById('cam-video-stream-inv').srcObject = null;
    document.getElementById('modal-cam-video-inv').style.display = 'none';
    ocultarErrorInv('cam-video-error-inv');
}

function cambiarCamaraInv(tipo) {
    if (tipo === 'foto') {
        facingFoto = facingFoto === 'environment' ? 'user' : 'environment';
        iniciarStreamFotoInv();
    } else {
        facingVideo = facingVideo === 'environment' ? 'user' : 'environment';
        iniciarStreamVideoInv();
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarCamaraFotoInv(); cerrarCamaraVideoInv(); }
});

/* ── Helpers ── */
function mostrarErrorInv(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.style.display = ''; }
}
function ocultarErrorInv(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'none'; el.textContent = ''; }
}
function mensajeErrorCamaraInv(err) {
    if (err.name === 'NotAllowedError')  return 'Acceso a la cámara denegado. Permite el permiso en tu navegador.';
    if (err.name === 'NotFoundError')    return 'No se encontró ninguna cámara en este dispositivo.';
    if (err.name === 'NotReadableError') return 'La cámara está siendo usada por otra aplicación.';
    return 'No se pudo acceder a la cámara: ' + err.message;
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
        e.preventDefault(); mostrarToast('Ingrese sus nombres y apellidos.');
        document.getElementById('nombres').focus(); return;
    }
    if (!sede) {
        e.preventDefault(); mostrarToast('Seleccione una sede.');
        document.getElementById('id_sede').focus(); return;
    }
    if (!area) {
        e.preventDefault(); mostrarToast('Indique el área o espacio del daño.');
        document.getElementById('area').focus(); return;
    }
    if (!cat) {
        e.preventDefault(); mostrarToast('Seleccione una categoría de daño.');
        document.getElementById('id_categoria').focus(); return;
    }
    if (desc.length < 10) {
        e.preventDefault(); mostrarToast('La descripción debe tener al menos 10 caracteres.');
        document.getElementById('descripcion_problema').focus(); return;
    }

    document.getElementById('btn-submit-inv').disabled = true;
    document.getElementById('btn-submit-inv').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando…';
});
</script>
</body>
</html>
