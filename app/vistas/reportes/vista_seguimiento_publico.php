<!-- RF-09: Seguimiento Público de Reporte (sin autenticación) -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Reporte — SIRGDI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --blue:        #3498DB;
            --blue-dark:   #2980B9;
            --blue-deep:   #1A5276;
            --teal:        #1ABC9C;
            --teal-dark:   #16A085;
            --indigo:      #3F51B5;
            --text:        #2C3E50;
            --text-light:  #7F8C8D;
            --bg:          #F0F4F8;
            --white:       #ffffff;
            --border:      #E2E8F0;
            --shadow:      0 4px 24px rgba(26,82,118,.10);
            --radius:      14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: linear-gradient(135deg, var(--blue-deep) 0%, #2471A3 100%);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 62px;
            box-shadow: 0 2px 16px rgba(0,0,0,.18);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--blue) 0%, var(--teal) 100%);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; color: #fff; font-weight: 800;
            box-shadow: 0 3px 10px rgba(52,152,219,.4);
        }

        .nav-brand-text { display: flex; flex-direction: column; }
        .nav-brand-name { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -.3px; }
        .nav-brand-sub  { font-size: 10px; color: rgba(255,255,255,.55); letter-spacing: .5px; text-transform: uppercase; }

        .nav-pill {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            color: rgba(255,255,255,.9);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: .3px;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, #0D2D4A 0%, var(--blue-deep) 45%, #2471A3 100%);
            padding: 48px 20px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -80px; right: -60px;
            width: 360px; height: 360px;
            background: radial-gradient(circle, rgba(26,188,156,.15) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(52,152,219,.12) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,.1);
            border: 2px solid rgba(255,255,255,.2);
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; color: var(--teal);
            margin-bottom: 16px;
        }

        .hero h1 {
            font-size: clamp(22px, 4vw, 32px);
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
            letter-spacing: -.5px;
        }

        .hero-sub {
            color: rgba(255,255,255,.65);
            font-size: 15px;
            margin-bottom: 24px;
        }

        .ticket-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,.1);
            border: 1.5px solid rgba(255,255,255,.25);
            backdrop-filter: blur(8px);
            padding: 10px 22px;
            border-radius: 50px;
        }

        .ticket-badge i { color: var(--teal); font-size: 16px; }

        .ticket-num {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 2px;
        }

        /* ── LAYOUT ── */
        .page-body {
            max-width: 860px;
            margin: -36px auto 48px;
            padding: 0 16px;
            position: relative;
            z-index: 1;
        }

        /* ── STATUS CARD ── */
        .status-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px 28px 24px;
            margin-bottom: 20px;
        }

        .status-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-light);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .badge-registrado   { background: #EBF5FB; color: #1A6FA3; }
        .badge-asignado     { background: #EAF0FB; color: var(--indigo); }
        .badge-en-proceso   { background: #FEF9E7; color: #D35400; }
        .badge-solucionado  { background: #E9F7EF; color: #1E8449; }
        .badge-en-validacion{ background: #F5EEF8; color: #7D3C98; }
        .badge-devuelto     { background: #FEF5E7; color: #B7770D; }
        .badge-cerrado      { background: #E8F8F5; color: #0E6655; }
        .badge-anulado      { background: #FDEDEC; color: #922B21; }

        .badge-urgencia-1   { background: #EBF5FB; color: #1A6FA3; }
        .badge-urgencia-2   { background: #FEF9E7; color: #B7770D; }
        .badge-urgencia-3   { background: #FEF0E7; color: #C0392B; }
        .badge-urgencia-4   { background: #FDEDEC; color: #922B21; }

        /* ── STEPPER ── */
        .stepper {
            display: flex;
            align-items: flex-start;
            gap: 0;
            position: relative;
            margin-top: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 72px;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 17px;
            left: calc(50% + 17px);
            right: calc(-50% + 17px);
            height: 3px;
            background: var(--border);
            z-index: 0;
            transition: background .3s;
        }

        .step.done:not(:last-child)::after { background: var(--teal); }
        .step.active:not(:last-child)::after { background: var(--border); }

        .step-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            background: var(--bg);
            border: 2px solid var(--border);
            color: var(--text-light);
            position: relative;
            z-index: 1;
            transition: all .3s;
        }

        .step.done  .step-circle { background: var(--teal);       border-color: var(--teal);       color: #fff; }
        .step.active .step-circle { background: var(--blue);      border-color: var(--blue);       color: #fff; box-shadow: 0 0 0 5px rgba(52,152,219,.2); }
        .step.skipped .step-circle { background: #FDEDEC;         border-color: #E74C3C;           color: #E74C3C; }

        .step-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-light);
            margin-top: 7px;
            text-align: center;
            line-height: 1.3;
        }

        .step.done   .step-label  { color: var(--teal-dark); }
        .step.active .step-label  { color: var(--blue-dark); font-weight: 700; }
        .step.skipped .step-label { color: #E74C3C; }

        /* ── INFO GRID ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        @media(max-width: 560px) { .info-grid { grid-template-columns: 1fr; } }

        .info-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px 22px;
        }

        .info-card-full {
            grid-column: 1 / -1;
        }

        .info-card-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            margin-bottom: 12px;
        }

        .icon-blue   { background: #EBF5FB; color: var(--blue); }
        .icon-teal   { background: #E8F8F5; color: var(--teal-dark); }
        .icon-purple { background: #F5EEF8; color: #7D3C98; }
        .icon-orange { background: #FEF5E7; color: #D35400; }
        .icon-green  { background: #E9F7EF; color: #1E8449; }
        .icon-indigo { background: #EAF0FB; color: var(--indigo); }

        .info-card .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .9px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .info-card .value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.45;
        }

        .info-card .value.mono {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            letter-spacing: .5px;
        }

        .descripcion-box {
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-left: 4px solid var(--blue);
            border-radius: 8px;
            padding: 16px;
            font-size: 15px;
            line-height: 1.65;
            color: var(--text);
        }

        /* ── TIMELINE ── */
        .timeline-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 24px 28px;
            margin-bottom: 20px;
        }

        .timeline-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-title i { color: var(--blue); }

        .tl-list { list-style: none; position: relative; padding-left: 24px; }

        .tl-list::before {
            content: '';
            position: absolute;
            left: 7px; top: 8px; bottom: 8px;
            width: 2px;
            background: var(--border);
        }

        .tl-item {
            position: relative;
            padding-bottom: 20px;
            padding-left: 20px;
        }

        .tl-item:last-child { padding-bottom: 0; }

        .tl-dot {
            position: absolute;
            left: -17px; top: 4px;
            width: 12px; height: 12px;
            border-radius: 50%;
            border: 2px solid var(--white);
        }

        .tl-dot-active  { background: var(--blue);       box-shadow: 0 0 0 3px rgba(52,152,219,.25); }
        .tl-dot-done    { background: var(--teal); }
        .tl-dot-pending { background: var(--border); }

        .tl-meta {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 500;
            margin-bottom: 2px;
        }

        .tl-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        /* ── ACTIONS ── */
        .actions-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            color: #fff;
            box-shadow: 0 4px 14px rgba(52,152,219,.35);
        }

        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(52,152,219,.45); }

        .btn-outline {
            background: var(--white);
            color: var(--blue);
            border: 1.5px solid var(--blue);
        }

        .btn-outline:hover { background: #EBF5FB; }

        .btn-copy {
            background: var(--white);
            color: var(--text);
            border: 1.5px solid var(--border);
        }

        .btn-copy:hover { background: var(--bg); }

        /* ── NOT FOUND ── */
        .not-found {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 60px 32px;
            text-align: center;
        }

        .not-found-icon {
            font-size: 52px;
            color: #E0E0E0;
            margin-bottom: 20px;
        }

        .not-found h2 { font-size: 20px; color: var(--text); margin-bottom: 8px; }
        .not-found p  { color: var(--text-light); font-size: 15px; margin-bottom: 24px; }

        /* ── FOOTER ── */
        .page-footer {
            text-align: center;
            padding: 0 16px 32px;
            color: var(--text-light);
            font-size: 12px;
            line-height: 1.7;
        }

        /* ── TOAST COPY ── */
        #toast-copy {
            position: fixed;
            bottom: 24px; left: 50%;
            transform: translateX(-50%) translateY(60px);
            background: #1E2D3D;
            color: #fff;
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
            transition: transform .3s ease, opacity .3s ease;
            opacity: 0;
            pointer-events: none;
            z-index: 999;
        }

        #toast-copy.show { transform: translateX(-50%) translateY(0); opacity: 1; }

        @media(max-width: 600px) {
            .hero { padding: 36px 16px 70px; }
            .status-card, .info-card, .timeline-card { padding: 18px 16px; }
            .step-label { font-size: 9px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a href="<?php echo config('app.url_base'); ?>/" class="nav-brand">
        <div class="nav-logo"><i class="fas fa-shield-halved"></i></div>
        <div class="nav-brand-text">
            <span class="nav-brand-name"><?php echo htmlspecialchars(config('app.app_name')); ?></span>
            <span class="nav-brand-sub">Sistema de Reportes</span>
        </div>
    </a>
    <div class="nav-pill">
        <i class="fas fa-magnifying-glass"></i>
        Consulta Pública
    </div>
</nav>

<?php
/* ── Mapas de datos ── */
$estados_info = [
    1 => ['label' => 'Registrado',    'icon' => 'fa-circle-dot',       'clase' => 'badge-registrado'],
    2 => ['label' => 'Asignado',      'icon' => 'fa-user-check',        'clase' => 'badge-asignado'],
    3 => ['label' => 'En Proceso',    'icon' => 'fa-gears',             'clase' => 'badge-en-proceso'],
    4 => ['label' => 'Solucionado',   'icon' => 'fa-circle-check',      'clase' => 'badge-solucionado'],
    5 => ['label' => 'En Validación', 'icon' => 'fa-clipboard-check',   'clase' => 'badge-en-validacion'],
    6 => ['label' => 'Devuelto',      'icon' => 'fa-rotate-left',       'clase' => 'badge-devuelto'],
    7 => ['label' => 'Cerrado',       'icon' => 'fa-lock',              'clase' => 'badge-cerrado'],
    8 => ['label' => 'Anulado',       'icon' => 'fa-ban',               'clase' => 'badge-anulado'],
];

$urgencias_info = [
    1 => ['label' => 'No Urgente',  'icon' => 'fa-circle-info',     'clase' => 'badge-urgencia-1'],
    2 => ['label' => 'Moderado',    'icon' => 'fa-exclamation',      'clase' => 'badge-urgencia-2'],
    3 => ['label' => 'Importante',  'icon' => 'fa-triangle-exclamation','clase' => 'badge-urgencia-3'],
    4 => ['label' => 'Urgente',     'icon' => 'fa-fire',             'clase' => 'badge-urgencia-4'],
];

/* ── Pasos del flujo normal (stepper) ── */
$pasos_flujo = [
    1 => ['label' => "Registrado",    'icon' => 'fa-file-circle-plus'],
    2 => ['label' => "Asignado",      'icon' => 'fa-user-check'],
    3 => ['label' => "En Proceso",    'icon' => 'fa-gears'],
    4 => ['label' => "Solucionado",   'icon' => 'fa-circle-check'],
    5 => ['label' => "En\nValidación",'icon' => 'fa-clipboard-check'],
    7 => ['label' => "Cerrado",       'icon' => 'fa-lock'],
];

$id_estado_actual = $reporte['id_estado'] ?? 0;
$es_anulado  = ($id_estado_actual === 8);
$es_devuelto = ($id_estado_actual === 6);
?>

<!-- Hero -->
<div class="hero">
    <?php if ($reporte): ?>
        <div class="hero-icon"><i class="fas fa-receipt"></i></div>
        <h1>Estado de tu Reporte</h1>
        <p class="hero-sub">Consulta el progreso en tiempo real de tu solicitud</p>
        <div class="ticket-badge">
            <i class="fas fa-hashtag"></i>
            <span class="ticket-num"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span>
        </div>
    <?php else: ?>
        <div class="hero-icon"><i class="fas fa-magnifying-glass"></i></div>
        <h1>Seguimiento de Reporte</h1>
        <p class="hero-sub">Consulta el estado de tu solicitud</p>
    <?php endif; ?>
</div>

<div class="page-body">

<?php if ($reporte && !empty($_GET['nuevo'])): ?>
<div style="max-width:780px;margin:0 auto 0;padding:0 20px;">
    <div style="display:flex;align-items:flex-start;gap:14px;background:#EAFAF1;border:1.5px solid #A9DFBF;border-left:5px solid #1ABC9C;border-radius:12px;padding:18px 22px;margin-bottom:0;">
        <i class="fas fa-circle-check" style="color:#1ABC9C;font-size:22px;flex-shrink:0;margin-top:2px;"></i>
        <div>
            <strong style="color:#1E8449;font-size:15px;display:block;margin-bottom:4px;">¡Reporte creado exitosamente!</strong>
            <span style="color:#239B56;font-size:13px;">Su reporte ha sido registrado. Guarde el número de ticket y el enlace de esta página para consultar el estado en cualquier momento.</span>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($reporte):
    $estado_info   = $estados_info[$id_estado_actual] ?? ['label' => 'Desconocido', 'icon' => 'fa-question', 'clase' => ''];
    $urgencia_info = $urgencias_info[$reporte['id_urgencia_calculada'] ?? 1] ?? $urgencias_info[1];
?>

    <!-- ── Card de estado y stepper ── -->
    <div class="status-card">
        <div class="status-card-header">
            <span class="section-label"><i class="fas fa-signal" style="margin-right:5px;"></i>Estado actual</span>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <span class="badge <?php echo $estado_info['clase']; ?>">
                    <i class="fas <?php echo $estado_info['icon']; ?>"></i>
                    <?php echo htmlspecialchars($estado_info['label']); ?>
                </span>
                <span class="badge <?php echo $urgencia_info['clase']; ?>">
                    <i class="fas <?php echo $urgencia_info['icon']; ?>"></i>
                    <?php echo htmlspecialchars($urgencia_info['label']); ?>
                </span>
            </div>
        </div>

        <?php if (!$es_anulado): ?>
        <!-- Stepper de progreso -->
        <div class="stepper">
            <?php foreach ($pasos_flujo as $id_paso => $paso):
                if ($es_anulado) { $clase = 'skipped'; }
                elseif ($id_estado_actual === $id_paso) { $clase = 'active'; }
                elseif ($id_estado_actual > $id_paso) { $clase = 'done'; }
                else { $clase = ''; }
            ?>
            <div class="step <?php echo $clase; ?>">
                <div class="step-circle">
                    <?php if ($clase === 'done'): ?>
                        <i class="fas fa-check"></i>
                    <?php elseif ($clase === 'active'): ?>
                        <i class="fas <?php echo $paso['icon']; ?>"></i>
                    <?php else: ?>
                        <i class="fas <?php echo $paso['icon']; ?>"></i>
                    <?php endif; ?>
                </div>
                <span class="step-label"><?php echo $paso['label']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="badge badge-anulado" style="font-size:14px; padding:10px 18px;">
            <i class="fas fa-ban"></i> Este reporte fue anulado
        </div>
        <?php endif; ?>

        <?php if ($es_devuelto): ?>
        <div style="margin-top:16px; padding:12px 16px; background:#FEF5E7; border-radius:10px; border-left:4px solid #E67E22; font-size:13px; color:#7D4B0A; display:flex; align-items:flex-start; gap:10px;">
            <i class="fas fa-rotate-left" style="margin-top:2px;"></i>
            <div><strong>Reporte devuelto:</strong> El gestor solicitó información adicional o corrección. Se reanudará una vez atendido.</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Cards de información ── -->
    <div class="info-grid">

        <!-- Sede y ubicación -->
        <div class="info-card">
            <div class="info-card-icon icon-blue"><i class="fas fa-location-dot"></i></div>
            <div class="label">Ubicación</div>
            <div class="value"><?php echo htmlspecialchars($sede['nombre'] ?? '—'); ?></div>
            <?php if (!empty($reporte['referencia_ubicacion_libre'])): ?>
            <div class="value" style="margin-top:4px; font-weight:400; font-size:13px; color:var(--text-light);">
                <i class="fas fa-map-pin" style="margin-right:4px;"></i><?php echo htmlspecialchars($reporte['referencia_ubicacion_libre']); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Categoría y subcategoría -->
        <div class="info-card">
            <div class="info-card-icon icon-purple"><i class="fas fa-tag"></i></div>
            <div class="label">Tipo de daño</div>
            <div class="value"><?php echo htmlspecialchars($categoria['nombre'] ?? '—'); ?></div>
        </div>

        <!-- Fecha de registro -->
        <div class="info-card">
            <div class="info-card-icon icon-teal"><i class="fas fa-calendar-plus"></i></div>
            <div class="label">Fecha de registro</div>
            <div class="value"><?php echo date('d/m/Y', strtotime($reporte['fecha_hora_registro'])); ?></div>
            <div style="font-size:12px; color:var(--text-light); margin-top:2px;">
                <?php echo date('H:i', strtotime($reporte['fecha_hora_registro'])); ?> hrs
            </div>
        </div>

        <!-- Reportado por -->
        <div class="info-card">
            <div class="info-card-icon icon-indigo"><i class="fas fa-user-circle"></i></div>
            <div class="label">Reportado por</div>
            <div class="value">
                <?php
                if (!empty($reporte['es_anonimo'])) {
                    echo '<span style="color:var(--text-light);"><i class="fas fa-user-secret"></i> Anónimo</span>';
                } else {
                    echo htmlspecialchars($reporte['nombre_reportante'] ?? '—');
                }
                ?>
            </div>
        </div>

        <!-- Descripción -->
        <div class="info-card info-card-full">
            <div class="info-card-icon icon-orange"><i class="fas fa-file-lines"></i></div>
            <div class="label" style="margin-bottom:10px;">Descripción del problema</div>
            <div class="descripcion-box"><?php echo nl2br(htmlspecialchars($reporte['descripcion_problema'])); ?></div>
        </div>

    </div>

    <!-- ── Timeline de historial ── -->
    <div class="timeline-card">
        <div class="timeline-title">
            <i class="fas fa-clock-rotate-left"></i> Historial del reporte
        </div>
        <ul class="tl-list">
            <li class="tl-item">
                <div class="tl-dot tl-dot-done"></div>
                <div class="tl-meta"><?php echo date('d/m/Y · H:i', strtotime($reporte['fecha_hora_registro'])); ?> hrs</div>
                <div class="tl-text"><i class="fas fa-file-circle-plus" style="color:var(--teal); margin-right:5px;"></i> Reporte registrado en el sistema</div>
            </li>
            <?php if (!empty($reporte['fecha_hora_asignacion'])): ?>
            <li class="tl-item">
                <div class="tl-dot tl-dot-done"></div>
                <div class="tl-meta"><?php echo date('d/m/Y · H:i', strtotime($reporte['fecha_hora_asignacion'])); ?> hrs</div>
                <div class="tl-text"><i class="fas fa-user-check" style="color:var(--blue); margin-right:5px;"></i> Reporte asignado a técnico</div>
            </li>
            <?php endif; ?>
            <?php if (!empty($reporte['fecha_hora_inicio_tecnico'])): ?>
            <li class="tl-item">
                <div class="tl-dot tl-dot-done"></div>
                <div class="tl-meta"><?php echo date('d/m/Y · H:i', strtotime($reporte['fecha_hora_inicio_tecnico'])); ?> hrs</div>
                <div class="tl-text"><i class="fas fa-gears" style="color:#D35400; margin-right:5px;"></i> Intervención técnica iniciada</div>
            </li>
            <?php endif; ?>
            <?php if (!empty($reporte['fecha_hora_solucionado'])): ?>
            <li class="tl-item">
                <div class="tl-dot tl-dot-done"></div>
                <div class="tl-meta"><?php echo date('d/m/Y · H:i', strtotime($reporte['fecha_hora_solucionado'])); ?> hrs</div>
                <div class="tl-text"><i class="fas fa-circle-check" style="color:#1E8449; margin-right:5px;"></i> Problema solucionado — pendiente de validación</div>
            </li>
            <?php endif; ?>
            <?php if (!empty($reporte['fecha_hora_cierre'])): ?>
            <li class="tl-item">
                <div class="tl-dot tl-dot-done"></div>
                <div class="tl-meta"><?php echo date('d/m/Y · H:i', strtotime($reporte['fecha_hora_cierre'])); ?> hrs</div>
                <div class="tl-text"><i class="fas fa-lock" style="color:#0E6655; margin-right:5px;"></i> Reporte cerrado formalmente</div>
            </li>
            <?php else: ?>
            <li class="tl-item">
                <div class="tl-dot tl-dot-pending"></div>
                <div class="tl-meta">Pendiente</div>
                <div class="tl-text" style="color:var(--text-light);"><i class="fas fa-ellipsis" style="margin-right:5px;"></i> Cierre formal del reporte</div>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- ── Acciones ── -->
    <div class="actions-row">
        <button class="btn btn-copy" onclick="copiarEnlace()" id="btn-copiar">
            <i class="fas fa-link"></i> Copiar enlace de seguimiento
        </button>
        <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=crear" class="btn btn-outline">
            <i class="fas fa-plus"></i> Crear otro reporte
        </a>
        <a href="<?php echo config('app.url_base'); ?>/" class="btn btn-primary">
            <i class="fas fa-house"></i> Ir al inicio
        </a>
    </div>

<?php else: ?>

    <!-- ── No encontrado ── -->
    <div class="not-found">
        <div class="not-found-icon"><i class="fas fa-file-circle-xmark"></i></div>
        <h2>Reporte no encontrado</h2>
        <p>El enlace de seguimiento es inválido o el reporte ya no existe en el sistema.</p>
        <a href="<?php echo config('app.url_base'); ?>/" class="btn btn-primary">
            <i class="fas fa-house"></i> Volver al inicio
        </a>
    </div>

<?php endif; ?>

</div><!-- /page-body -->

<div class="page-footer">
    <p><strong><?php echo htmlspecialchars(config('app.app_name')); ?></strong> &mdash; Sistema Institucional de Reporte y Gestión de Daños en Infraestructura</p>
    <p style="margin-top:4px;">&copy; <?php echo date('Y'); ?> &mdash; Esta página es de acceso público. No comparta este enlace con personas no autorizadas.</p>
</div>

<!-- Toast de copiado -->
<div id="toast-copy"><i class="fas fa-check" style="margin-right:6px;"></i> Enlace copiado al portapapeles</div>

<script>
function copiarEnlace() {
    var url = window.location.href;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(mostrarToast);
    } else {
        var el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        mostrarToast();
    }
}

function mostrarToast() {
    var t = document.getElementById('toast-copy');
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2500);
}
</script>

</body>
</html>
