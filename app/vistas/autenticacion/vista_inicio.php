<!-- Página de Inicio (Welcome Page - Público) -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo config('app.app_name'); ?> - Sistema de Reportes de Daños</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo config('app.url_base'); ?>/img/favicon.png">
    <link rel="apple-touch-icon" href="<?php echo config('app.url_base'); ?>/img/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --blue:        #3498DB;
            --blue-dark:   #2980B9;
            --blue-deep:   #1A5276;
            --teal:        #1ABC9C;
            --teal-dark:   #0E6655;
            --indigo:      #3F51B5;
            --indigo-dark: #1A237E;
            --text:        #2C3E50;
            --text-light:  #7F8C8D;
            --bg-light:    #F4F9FD;
            --white:       #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--white);
        }

        /* ========== NAVBAR ========== */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(26, 82, 118, 0.97);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,.1);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(52,152,219,.4);
            background: #fff;
            flex-shrink: 0;
        }
        .nav-logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2px;
        }

        .nav-brand-name {
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .nav-brand-sub {
            font-size: 11px;
            color: rgba(255,255,255,.55);
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            color: rgba(255,255,255,.75);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }

        .nav-btn {
            background: var(--blue);
            color: #fff !important;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(52,152,219,.4);
        }

        .nav-btn:hover {
            background: var(--blue-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(52,152,219,.45);
        }

        /* ========== HERO ========== */
        .hero {
            background: linear-gradient(135deg, #0D2D4A 0%, #1A5276 40%, #2471A3 75%, #2980B9 100%);
            position: relative;
            overflow: hidden;
            padding: 90px 20px 80px;
            text-align: center;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -120px; right: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(26,188,156,.18) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(63,81,181,.22) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-container {
            max-width: 820px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.25);
            color: rgba(255,255,255,.9);
            padding: 7px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
            margin-bottom: 28px;
        }

        .hero-badge i { color: var(--teal); }

        .hero h1 {
            font-size: 56px;
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
        }

        .hero h1 span {
            background: linear-gradient(90deg, var(--teal) 0%, #5DADE2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 19px;
            color: rgba(255,255,255,.8);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.65;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 54px;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s;
        }

        .btn-primary {
            background: var(--teal);
            color: #fff;
            box-shadow: 0 8px 24px rgba(26,188,156,.4);
        }

        .btn-primary:hover {
            background: var(--teal-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(26,188,156,.45);
        }

        .btn-teal {
            background: rgba(255,255,255,.15);
            color: #fff;
            border: 2px solid rgba(255,255,255,.5);
            backdrop-filter: blur(4px);
        }

        .btn-teal:hover {
            background: var(--teal);
            border-color: var(--teal);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(26,188,156,.45);
        }

        .btn-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,.45);
        }

        .btn-outline:hover {
            background: rgba(255,255,255,.1);
            border-color: rgba(255,255,255,.8);
        }

        /* ── Modal selector de institución ── */
        .inst-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 2000;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .inst-overlay.open { display: flex; }
        .inst-modal {
            background: #fff;
            border-radius: 16px;
            padding: 32px 28px;
            max-width: 440px; width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: modalIn .22s ease;
        }
        @keyframes modalIn { from { opacity:0; transform:scale(.94) translateY(10px); } to { opacity:1; transform:none; } }
        .inst-modal h3 {
            font-size: 20px; font-weight: 800; color: #1A5276;
            margin-bottom: 6px; display: flex; align-items: center; gap: 10px;
        }
        .inst-modal h3 i { color: var(--teal); }
        .inst-modal p  { font-size: 14px; color: #7F8C8D; margin-bottom: 22px; }
        .inst-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .inst-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; border: 2px solid #D6EAF8;
            border-radius: 10px; text-decoration: none; color: #2C3E50;
            font-weight: 600; font-size: 14px; transition: all .18s;
        }
        .inst-item i { color: var(--blue); font-size: 18px; flex-shrink: 0; }
        .inst-item:hover { border-color: var(--teal); background: #EAFAF1; color: #1E8449; }
        .inst-cancel {
            width: 100%; padding: 11px; border: none; background: #ECF0F1;
            border-radius: 8px; color: #7F8C8D; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: background .18s;
        }
        .inst-cancel:hover { background: #D5D8DC; }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 0;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 16px;
            padding: 20px 0;
            backdrop-filter: blur(6px);
        }

        .hstat {
            flex: 1;
            text-align: center;
            padding: 0 24px;
            border-right: 1px solid rgba(255,255,255,.15);
        }

        .hstat:last-child { border-right: none; }

        .hstat-num {
            font-size: 32px;
            font-weight: 900;
            color: #fff;
            line-height: 1;
            margin-bottom: 4px;
        }

        .hstat-num span { color: var(--teal); }

        .hstat-label {
            font-size: 12px;
            color: rgba(255,255,255,.6);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ========== SECTION HEADER (reutilizable) ========== */
        .section-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 44px;
        }

        .sh-line {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, #AED6F1, transparent);
        }

        .sh-center { text-align: center; flex-shrink: 0; }

        .sh-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--blue);
            background: rgba(52,152,219,.1);
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid rgba(52,152,219,.22);
            margin-bottom: 10px;
        }

        .sh-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.3px;
            margin: 6px 0 6px;
        }

        .sh-sub {
            font-size: 14.5px;
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto;
        }

        /* ========== FEATURES ========== */
        .features {
            padding: 72px 20px;
            background: linear-gradient(170deg, #EBF5FB 0%, #F4F9FD 60%, #EAF4FF 100%);
        }

        .features-container { max-width: 1140px; margin: 0 auto; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-bottom: 26px;
        }

        .feature-card {
            position: relative;
            border-radius: 20px;
            padding: 32px 28px 28px;
            overflow: hidden;
            color: #fff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 44px rgba(0,0,0,.22) !important;
        }

        .fc-blue  { background: linear-gradient(145deg,#1A5276,#2471A3 45%,#3498DB); box-shadow:0 10px 30px rgba(41,128,185,.35); }
        .fc-teal  { background: linear-gradient(145deg,#0E6655,#1A9370 45%,#1ABC9C); box-shadow:0 10px 30px rgba(26,179,148,.30); }
        .fc-indigo{ background: linear-gradient(145deg,#1A237E,#283593 45%,#3F51B5); box-shadow:0 10px 30px rgba(63,81,181,.32); }

        .fc-number {
            position: absolute; top: -14px; right: 20px;
            font-size: 90px; font-weight: 900;
            color: rgba(255,255,255,.07); line-height: 1;
            pointer-events: none; user-select: none; letter-spacing: -4px;
        }

        .fc-top { display:flex; align-items:center; gap:12px; margin-bottom:18px; }

        .fc-icon-wrap {
            width:54px; height:54px; flex-shrink:0;
            background:rgba(255,255,255,.18); border:1.5px solid rgba(255,255,255,.3);
            border-radius:14px; display:flex; align-items:center; justify-content:center;
            font-size:22px; backdrop-filter:blur(4px);
        }

        .fc-badge {
            font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px;
            background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.25);
            padding:4px 12px; border-radius:20px;
        }

        .fc-title { font-size:20px; font-weight:800; color:#fff; margin:0 0 10px; letter-spacing:-.2px; }
        .fc-desc  { font-size:14px; line-height:1.65; color:rgba(255,255,255,.88); margin:0 0 20px; }

        .fc-footer {
            display:flex; flex-direction:column; gap:6px;
            border-top:1px solid rgba(255,255,255,.18); padding-top:16px;
        }

        .fc-footer span {
            font-size:12.5px; font-weight:600; color:rgba(255,255,255,.92);
            display:flex; align-items:center; gap:7px;
        }

        .fc-footer span i { font-size:11px; opacity:.85; }

        .features-stats {
            background:#fff; border-radius:16px; padding:22px 34px;
            display:flex; align-items:center;
            box-shadow:0 3px 14px rgba(41,128,185,.1); border:1px solid #D6EAF8;
        }

        .fstat { flex:1; display:flex; align-items:center; gap:14px; }
        .fstat-icon { font-size:28px; color:var(--blue); flex-shrink:0; opacity:.85; }
        .fstat strong { display:block; font-size:15px; font-weight:700; color:var(--text); margin-bottom:2px; }
        .fstat span  { font-size:12.5px; color:var(--text-light); line-height:1.4; }
        .fstat-divider { width:1px; height:46px; background:#D6EAF8; margin:0 28px; flex-shrink:0; }

        /* ========== HOW IT WORKS ========== */
        .steps {
            padding: 72px 20px;
            background: #fff;
        }

        .steps-container { max-width: 1140px; margin: 0 auto; }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            position: relative;
        }

        .steps-grid::before {
            content: '';
            position: absolute;
            top: 38px;
            left: calc(16.66% + 0px);
            right: calc(16.66% + 0px);
            height: 2px;
            background: linear-gradient(90deg, var(--blue) 0%, var(--teal) 50%, var(--indigo) 100%);
            z-index: 0;
        }

        .step {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 0 20px;
        }

        .step-icon-wrap {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
            position: relative;
            transition: transform 0.3s;
        }

        .step:hover .step-icon-wrap { transform: scale(1.08); }

        .step:nth-child(1) .step-icon-wrap { background: linear-gradient(135deg, var(--blue-dark), var(--blue)); box-shadow: 0 8px 24px rgba(52,152,219,.35); }
        .step:nth-child(2) .step-icon-wrap { background: linear-gradient(135deg, #8E44AD, #9B59B6);                box-shadow: 0 8px 24px rgba(155,89,182,.35); }
        .step:nth-child(3) .step-icon-wrap { background: linear-gradient(135deg, var(--teal-dark), var(--teal));   box-shadow: 0 8px 24px rgba(26,188,156,.35); }
        .step:nth-child(4) .step-icon-wrap { background: linear-gradient(135deg, #D35400, #E67E22);               box-shadow: 0 8px 24px rgba(230,126,34,.35); }
        .step:nth-child(5) .step-icon-wrap { background: linear-gradient(135deg, #C0392B, #E74C3C);               box-shadow: 0 8px 24px rgba(231,76,60,.35); }
        .step:nth-child(6) .step-icon-wrap { background: linear-gradient(135deg, var(--indigo-dark), var(--indigo)); box-shadow: 0 8px 24px rgba(63,81,181,.35); }

        .step-num {
            position: absolute;
            top: -6px; right: -4px;
            width: 24px; height: 24px;
            background: var(--text);
            color: #fff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        .step-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
        }

        .step-desc {
            font-size: 13.5px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Segunda fila separada */
        .steps-row-2 {
            margin-top: 44px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            position: relative;
        }

        .steps-row-2::before {
            content: '';
            position: absolute;
            top: 38px;
            left: calc(16.66%);
            right: calc(16.66%);
            height: 2px;
            background: linear-gradient(90deg, #E67E22, #E74C3C, var(--indigo));
            z-index: 0;
        }

        /* ========== ROLES ========== */
        .roles {
            padding: 72px 20px;
            background: linear-gradient(170deg, #EBF5FB 0%, #F4F9FD 100%);
        }

        .roles-container { max-width: 1140px; margin: 0 auto; }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .role-card {
            background: #fff;
            border-radius: 18px;
            padding: 28px 26px;
            border: 1px solid #D6EAF8;
            box-shadow: 0 4px 16px rgba(41,128,185,.08);
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .role-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(41,128,185,.14);
        }

        .role-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #fff;
            flex-shrink: 0;
        }

        .ri-1 { background: linear-gradient(135deg, var(--blue-dark), var(--blue)); }
        .ri-2 { background: linear-gradient(135deg, #8E44AD, #9B59B6); }
        .ri-3 { background: linear-gradient(135deg, var(--teal-dark), var(--teal)); }
        .ri-4 { background: linear-gradient(135deg, #D35400, #E67E22); }
        .ri-5 { background: linear-gradient(135deg, var(--indigo-dark), var(--indigo)); }
        .ri-6 { background: linear-gradient(135deg, #C0392B, #E74C3C); }

        .role-name  { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 5px; }
        .role-desc  { font-size: 13px; color: var(--text-light); line-height: 1.55; }

        /* ========== CTA ========== */
        .cta {
            padding: 80px 20px;
            background: linear-gradient(135deg, #0D2D4A 0%, #1A5276 40%, #2471A3 80%, #2980B9 100%);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(26,188,156,.2) 0%, transparent 65%);
            border-radius: 50%;
        }

        .cta::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(63,81,181,.22) 0%, transparent 65%);
            border-radius: 50%;
        }

        .cta-container { max-width: 640px; margin: 0 auto; position: relative; z-index: 1; }

        .cta-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.22);
            color: rgba(255,255,255,.9);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .cta h2 {
            font-size: 38px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.8px;
            margin-bottom: 16px;
            line-height: 1.15;
        }

        .cta h2 span { color: var(--teal); }

        .cta p {
            font-size: 17px;
            color: rgba(255,255,255,.78);
            margin-bottom: 36px;
            line-height: 1.65;
        }

        .cta-buttons { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        .btn-cta-primary {
            background: var(--teal);
            color: #fff;
            box-shadow: 0 8px 24px rgba(26,188,156,.4);
        }

        .btn-cta-primary:hover {
            background: var(--teal-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(26,188,156,.45);
        }

        .btn-cta-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,.4);
        }

        .btn-cta-outline:hover {
            background: rgba(255,255,255,.1);
            border-color: rgba(255,255,255,.7);
        }

        /* ========== FOOTER ========== */
        .footer {
            background: #0D2137;
            color: rgba(255,255,255,.65);
            padding: 40px 20px 28px;
        }

        .footer-container {
            max-width: 1140px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 30px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .footer-logo-icon {
            width: 34px; height: 34px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            flex-shrink: 0;
        }
        .footer-logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2px;
        }

        .footer-brand-name { font-size: 16px; font-weight: 700; color: #fff; }

        .footer-copy { font-size: 13px; }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-links a {
            font-size: 13px;
            color: rgba(255,255,255,.5);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover { color: var(--blue); }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1000px) {
            .features-grid, .roles-grid { grid-template-columns: 1fr 1fr; }
            .steps-grid, .steps-row-2 { grid-template-columns: 1fr 1fr 1fr; }
            .features-stats { flex-direction: column; gap: 18px; align-items: flex-start; }
            .fstat-divider { width: 60px; height: 1px; margin: 0; }
        }

        @media (max-width: 768px) {
            .navbar { padding: 0 18px; }
            .nav-brand-sub { display: none; }
            .hero h1 { font-size: 38px; }
            .hero-sub { font-size: 16px; }
            .hero-stats { flex-wrap: wrap; }
            .hstat { flex: 0 0 50%; border-right: none; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,.12); }
            .hstat:nth-child(odd) { border-right: 1px solid rgba(255,255,255,.12); }
            .hstat:last-child { flex: 1; border-bottom: none; }
            .section-header { flex-direction: column; gap: 10px; }
            .sh-line { display: none; }
            .sh-title { font-size: 22px; }
            .features-grid, .roles-grid { grid-template-columns: 1fr; }
            .steps-grid, .steps-row-2 { grid-template-columns: 1fr 1fr; }
            .steps-grid::before, .steps-row-2::before { display: none; }
            .cta h2 { font-size: 28px; }
            .footer-container { grid-template-columns: 1fr; text-align: center; }
            .footer-brand { justify-content: center; }
            .footer-links { justify-content: center; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 28px; letter-spacing: -0.5px; }
            .hero-buttons { flex-direction: column; align-items: center; }
            .btn { width: 100%; justify-content: center; }
            .steps-grid, .steps-row-2 { grid-template-columns: 1fr; }
            .feature-card { padding: 26px 20px 22px; }
            .fc-title { font-size: 18px; }
            .features-stats { padding: 18px 20px; }
            .cta-buttons { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar">
        <a href="#" class="nav-brand">
            <div class="nav-logo-icon"><img src="<?php echo config('app.url_base'); ?>/img/logo_icono.png" alt="SIRGDI"></div>
            <div>
                <div class="nav-brand-name">SIRGDI v2.0</div>
                <div class="nav-brand-sub">Sistema de Reportes</div>
            </div>
        </a>
        <div class="nav-links">
            <a href="#features" class="nav-link">Características</a>
            <a href="#como-funciona" class="nav-link">¿Cómo funciona?</a>
            <a href="#roles" class="nav-link">Roles</a>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login" class="nav-link nav-btn">
                <i class="fas fa-sign-in-alt"></i> Ingresar
            </a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-badge">
                <i class="fas fa-bolt"></i> Sistema Institucional v2.0
            </div>
            <h1>Gestión de Daños<br><span>Inteligente y Trazable</span></h1>
            <p class="hero-sub">
                Plataforma integral para reportar, asignar y resolver incidencias de infraestructura
                escolar con evidencia fotográfica, SLA automático y auditoría completa.
            </p>
            <div class="hero-buttons">
                <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Ingresar al Sistema
                </a>
                <?php
                $insts = $instituciones_publicas ?? [];
                if (count($insts) === 1):
                    $inst_url = config('app.url_base') . '/?controlador=reportes&accion=crear_invitado&inst=' . intval($insts[0]['id_institucion']);
                ?>
                <a href="<?php echo $inst_url; ?>" class="btn btn-teal">
                    <i class="fas fa-pen-to-square"></i> Reportar Daño
                </a>
                <?php elseif (count($insts) > 1): ?>
                <button type="button" class="btn btn-teal" onclick="abrirSelectorInst()">
                    <i class="fas fa-pen-to-square"></i> Reportar Daño
                </button>
                <?php endif; ?>
                <a href="#features" class="btn btn-outline">
                    <i class="fas fa-chevron-down"></i> Ver Características
                </a>
            </div>
            <div class="hero-stats">
                <div class="hstat">
                    <div class="hstat-num">6<span>+</span></div>
                    <div class="hstat-label">Roles de Usuario</div>
                </div>
                <div class="hstat">
                    <div class="hstat-num">3</div>
                    <div class="hstat-label">Módulos Principales</div>
                </div>
                <div class="hstat">
                    <div class="hstat-num">100<span>%</span></div>
                    <div class="hstat-label">Trazabilidad</div>
                </div>
                <div class="hstat">
                    <div class="hstat-num">2<span>FA</span></div>
                    <div class="hstat-label">Autenticación Segura</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CARACTERÍSTICAS ===== -->
    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <div class="sh-line"></div>
                <div class="sh-center">
                    <span class="sh-label"><i class="fas fa-star"></i> ¿Qué puedes hacer?</span>
                    <h2 class="sh-title">Características Principales</h2>
                    <p class="sh-sub">Todo lo que necesitas para gestionar incidencias institucionales en un solo lugar</p>
                </div>
                <div class="sh-line"></div>
            </div>

            <div class="features-grid">
                <div class="feature-card fc-blue">
                    <div class="fc-number">01</div>
                    <div class="fc-top">
                        <div class="fc-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
                        <div class="fc-badge">Módulo Reportes</div>
                    </div>
                    <h3 class="fc-title">Reportar Incidencias</h3>
                    <p class="fc-desc">Registra fallas, daños o incidencias en la infraestructura escolar de forma rápida y guiada, adjuntando fotos y ubicación exacta.</p>
                    <div class="fc-footer">
                        <span><i class="fas fa-check-circle"></i> Formulario inteligente</span>
                        <span><i class="fas fa-check-circle"></i> Ticket automático único</span>
                        <span><i class="fas fa-check-circle"></i> Evidencias fotográficas</span>
                    </div>
                </div>

                <div class="feature-card fc-teal">
                    <div class="fc-number">02</div>
                    <div class="fc-top">
                        <div class="fc-icon-wrap"><i class="fas fa-sitemap"></i></div>
                        <div class="fc-badge">Módulo Gestión</div>
                    </div>
                    <h3 class="fc-title">Administrar y Gestionar</h3>
                    <p class="fc-desc">Asigna técnicos, prioriza por urgencia y gestiona cada reporte hasta su resolución con control total del flujo y SLA automático.</p>
                    <div class="fc-footer">
                        <span><i class="fas fa-check-circle"></i> Kanban visual interactivo</span>
                        <span><i class="fas fa-check-circle"></i> Asignación de técnicos</span>
                        <span><i class="fas fa-check-circle"></i> Control y alerta de SLA</span>
                    </div>
                </div>

                <div class="feature-card fc-indigo">
                    <div class="fc-number">03</div>
                    <div class="fc-top">
                        <div class="fc-icon-wrap"><i class="fas fa-chart-line"></i></div>
                        <div class="fc-badge">Módulo Analytics</div>
                    </div>
                    <h3 class="fc-title">Seguimiento y Análisis</h3>
                    <p class="fc-desc">Consulta estado, trazabilidad y evidencias de cada incidencia en tiempo real. Exporta reportes y toma decisiones con datos reales.</p>
                    <div class="fc-footer">
                        <span><i class="fas fa-check-circle"></i> Dashboard con KPIs</span>
                        <span><i class="fas fa-check-circle"></i> Exportar CSV / Auditoría</span>
                        <span><i class="fas fa-check-circle"></i> Seguimiento público</span>
                    </div>
                </div>
            </div>

            <div class="features-stats">
                <div class="fstat">
                    <i class="fas fa-users fstat-icon"></i>
                    <div>
                        <strong>6 Roles</strong>
                        <span>Reportante, Técnico, Gestor, Rector, Admin, Superadmin</span>
                    </div>
                </div>
                <div class="fstat-divider"></div>
                <div class="fstat">
                    <i class="fas fa-layer-group fstat-icon"></i>
                    <div>
                        <strong>Multitenant</strong>
                        <span>Cada institución con sus datos aislados y seguros</span>
                    </div>
                </div>
                <div class="fstat-divider"></div>
                <div class="fstat">
                    <i class="fas fa-shield-alt fstat-icon"></i>
                    <div>
                        <strong>Seguro y Auditable</strong>
                        <span>RBAC, CSRF, 2FA y registro completo de auditoría</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CÓMO FUNCIONA ===== -->
    <section class="steps" id="como-funciona">
        <div class="steps-container">
            <div class="section-header">
                <div class="sh-line"></div>
                <div class="sh-center">
                    <span class="sh-label"><i class="fas fa-route"></i> Flujo del Sistema</span>
                    <h2 class="sh-title">¿Cómo Funciona?</h2>
                    <p class="sh-sub">Seis pasos simples del reporte al cierre con evidencia y satisfacción</p>
                </div>
                <div class="sh-line"></div>
            </div>

            <div class="steps-grid">
                <div class="step">
                    <div class="step-icon-wrap">
                        <i class="fas fa-user-plus"></i>
                        <div class="step-num">1</div>
                    </div>
                    <div class="step-title">Crear Reporte</div>
                    <p class="step-desc">Inicia sesión y llena el formulario de daño con ubicación, categoría y descripción detallada.</p>
                </div>
                <div class="step">
                    <div class="step-icon-wrap">
                        <i class="fas fa-ticket-alt"></i>
                        <div class="step-num">2</div>
                    </div>
                    <div class="step-title">Recibir Ticket</div>
                    <p class="step-desc">Obtén un número único de ticket para consultar el estado sin necesidad de autenticación.</p>
                </div>
                <div class="step">
                    <div class="step-icon-wrap">
                        <i class="fas fa-tasks"></i>
                        <div class="step-num">3</div>
                    </div>
                    <div class="step-title">Gestión y Asignación</div>
                    <p class="step-desc">El gestor prioriza el reporte y lo asigna al técnico adecuado según carga y especialidad.</p>
                </div>
            </div>

            <div class="steps-row-2">
                <div class="step">
                    <div class="step-icon-wrap">
                        <i class="fas fa-tools"></i>
                        <div class="step-num">4</div>
                    </div>
                    <div class="step-title">Intervención Técnica</div>
                    <p class="step-desc">El técnico registra la intervención con fotos en 3 etapas: Antes, Durante y Después.</p>
                </div>
                <div class="step">
                    <div class="step-icon-wrap">
                        <i class="fas fa-check-double"></i>
                        <div class="step-num">5</div>
                    </div>
                    <div class="step-title">Validación y Cierre</div>
                    <p class="step-desc">El gestor valida la solución. El usuario califica el servicio con una encuesta de satisfacción.</p>
                </div>
                <div class="step">
                    <div class="step-icon-wrap">
                        <i class="fas fa-archive"></i>
                        <div class="step-num">6</div>
                    </div>
                    <div class="step-title">Archivo y Auditoría</div>
                    <p class="step-desc">Reporte cerrado con documentación completa, evidencia fotográfica y trazabilidad total.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== ROLES ===== -->
    <section class="roles" id="roles">
        <div class="roles-container">
            <div class="section-header">
                <div class="sh-line"></div>
                <div class="sh-center">
                    <span class="sh-label"><i class="fas fa-users"></i> Perfiles del Sistema</span>
                    <h2 class="sh-title">Roles y Responsabilidades</h2>
                    <p class="sh-sub">Cada usuario actúa desde su rol con permisos específicos y trazabilidad completa</p>
                </div>
                <div class="sh-line"></div>
            </div>

            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-icon ri-1"><i class="fas fa-pen"></i></div>
                    <div class="role-body">
                        <div class="role-name">Reportante / Docente</div>
                        <div class="role-desc">Crea reportes de daños, sube fotos iniciales y sigue el estado de sus tickets con enlace público.</div>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-icon ri-2"><i class="fas fa-user-graduate"></i></div>
                    <div class="role-body">
                        <div class="role-name">Rector</div>
                        <div class="role-desc">Supervisa todos los reportes de la institución, consulta KPIs y valida el cierre final de incidencias.</div>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-icon ri-3"><i class="fas fa-project-diagram"></i></div>
                    <div class="role-body">
                        <div class="role-name">Gestor</div>
                        <div class="role-desc">Prioriza reportes, asigna técnicos, controla SLA y gestiona el flujo completo del Kanban.</div>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-icon ri-4"><i class="fas fa-wrench"></i></div>
                    <div class="role-body">
                        <div class="role-name">Técnico</div>
                        <div class="role-desc">Registra la intervención técnica, carga evidencia fotográfica (Antes/Durante/Después) y cierra la solución.</div>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-icon ri-5"><i class="fas fa-user-shield"></i></div>
                    <div class="role-body">
                        <div class="role-name">Admin de Institución</div>
                        <div class="role-desc">Gestiona usuarios, sedes, categorías y configuración del SLA para su institución.</div>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-icon ri-6"><i class="fas fa-crown"></i></div>
                    <div class="role-body">
                        <div class="role-name">Superadministrador</div>
                        <div class="role-desc">Administra todas las instituciones, accede a configuración global y auditoría del sistema completo.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="cta">
        <div class="cta-container">
            <div class="cta-badge">
                <i class="fas fa-rocket"></i> Listo para usar
            </div>
            <h2>¿Listo para <span>Empezar</span>?</h2>
            <p>Accede con tus credenciales institucionales o contacta a tu administrador. La plataforma está disponible 24/7.</p>
            <div class="cta-buttons">
                <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login" class="btn btn-cta-primary">
                    <i class="fas fa-sign-in-alt"></i> Ingresar Ahora
                </a>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=seguimiento_publico" class="btn btn-cta-outline">
                    <i class="fas fa-search"></i> Consultar Ticket
                </a>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="footer-container">
            <div>
                <div class="footer-brand">
                    <div class="footer-logo-icon"><img src="<?php echo config('app.url_base'); ?>/img/logo_icono.png" alt="SIRGDI"></div>
                    <span class="footer-brand-name">SIRGDI v2.0</span>
                </div>
                <p class="footer-copy">&copy; 2026 Sistema de Reportes y Gestión de Daños e Incidencias. Todos los derechos reservados.</p>
            </div>
            <div class="footer-links">
                <a href="#">Privacidad</a>
                <a href="#">Términos</a>
                <a href="#">Contacto</a>
            </div>
        </div>
    </footer>

<?php if (!empty($instituciones_publicas) && count($instituciones_publicas) > 1): ?>
<!-- Modal selector de institución (solo cuando hay múltiples) -->
<div class="inst-overlay" id="inst-overlay" onclick="if(event.target===this)cerrarSelectorInst()">
    <div class="inst-modal">
        <h3><i class="fas fa-building-columns"></i> ¿A qué institución pertenece?</h3>
        <p>Seleccione su institución para continuar con el reporte sin necesidad de crear una cuenta.</p>
        <div class="inst-list">
            <?php foreach ($instituciones_publicas as $inst): ?>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=crear_invitado&inst=<?php echo intval($inst['id_institucion']); ?>"
                   class="inst-item">
                    <i class="fas fa-school"></i>
                    <?php echo htmlspecialchars($inst['nombre']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <button class="inst-cancel" onclick="cerrarSelectorInst()">Cancelar</button>
    </div>
</div>
<script>
function abrirSelectorInst()  { document.getElementById('inst-overlay').classList.add('open'); }
function cerrarSelectorInst() { document.getElementById('inst-overlay').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarSelectorInst(); });
</script>
<?php endif; ?>

</body>
</html>
