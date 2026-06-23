<!-- Página de Inicio - Presentación SIRGDI + Institución -->
<div class="inicio-wrap">

    <!-- HERO: Presentación de la plataforma -->
    <section class="hero-inicio">
        <div class="hero-bg-deco"></div>
        <div class="hero-content">
            <span class="hero-badge"><i class="fas fa-bolt"></i> Versión 2.0</span>
            <h1 class="hero-titulo">SIRGDI <span>v2.0</span></h1>
            <p class="hero-texto">
                <strong>SIRGDI v2.0</strong> es la versión mejorada del <strong>Sistema de Registro y Gestión
                de Daños e Incidencias</strong>, una plataforma diseñada para que las instituciones educativas
                puedan reportar, administrar y hacer seguimiento a fallas, daños, solicitudes o incidencias
                dentro de la infraestructura escolar.
            </p>
        </div>
    </section>

    <!-- INSTITUCIÓN: logo + nombre -->
    <section class="institucion-card">
        <?php if (!empty($institucion['logo_url'])): ?>
            <div class="institucion-logo">
                <img src="<?php echo htmlspecialchars($institucion['logo_url']); ?>" alt="Logo <?php echo htmlspecialchars($institucion['nombre'] ?? ''); ?>">
            </div>
        <?php else: ?>
            <div class="institucion-logo-placeholder">
                <i class="fas fa-building"></i>
            </div>
        <?php endif; ?>
        <div class="institucion-info">
            <span class="institucion-bienvenida"><i class="fas fa-hand-sparkles"></i> Bienvenido a</span>
            <h2 class="institucion-nombre"><?php echo htmlspecialchars($institucion['nombre'] ?? 'Institución Educativa'); ?></h2>
            <p class="institucion-sub">Plataforma de Reporte y Gestión de Daños e Incidencias</p>
        </div>
    </section>

    <!-- CARACTERÍSTICAS PRINCIPALES -->
    <section class="features-section">
        <div class="features-header">
            <div class="features-header-line"></div>
            <div class="features-header-text">
                <span class="features-label"><i class="fas fa-star"></i> ¿Qué puedes hacer?</span>
                <h2 class="features-titulo">Características Principales</h2>
                <p class="features-subtitulo">Todo lo que necesitas para gestionar incidencias institucionales en un solo lugar</p>
            </div>
            <div class="features-header-line"></div>
        </div>

        <div class="features-grid">
            <div class="feature-card fc-blue">
                <div class="fc-number">01</div>
                <div class="fc-top">
                    <div class="fc-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
                    <div class="fc-badge">Módulo Reportes</div>
                </div>
                <h3 class="fc-title">Reportar Incidencias</h3>
                <p class="fc-desc">Registra fallas, daños, solicitudes o incidencias en la infraestructura escolar de forma rápida y guiada, adjuntando fotos y ubicación exacta.</p>
                <div class="fc-footer">
                    <span><i class="fas fa-check-circle"></i> Formulario inteligente</span>
                    <span><i class="fas fa-check-circle"></i> Ticket automático</span>
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
                <p class="fc-desc">Asigna técnicos, prioriza por urgencia y gestiona cada reporte hasta su resolución con control total del flujo de trabajo y SLA.</p>
                <div class="fc-footer">
                    <span><i class="fas fa-check-circle"></i> Kanban visual</span>
                    <span><i class="fas fa-check-circle"></i> Asignación de técnicos</span>
                    <span><i class="fas fa-check-circle"></i> Control de SLA</span>
                </div>
            </div>

            <div class="feature-card fc-indigo">
                <div class="fc-number">03</div>
                <div class="fc-top">
                    <div class="fc-icon-wrap"><i class="fas fa-chart-line"></i></div>
                    <div class="fc-badge">Módulo Analytics</div>
                </div>
                <h3 class="fc-title">Seguimiento y Análisis</h3>
                <p class="fc-desc">Consulta el estado, la trazabilidad y las evidencias de cada incidencia en tiempo real. Exporta reportes y toma decisiones con datos.</p>
                <div class="fc-footer">
                    <span><i class="fas fa-check-circle"></i> Dashboard KPIs</span>
                    <span><i class="fas fa-check-circle"></i> Exportar CSV</span>
                    <span><i class="fas fa-check-circle"></i> Auditoría completa</span>
                </div>
            </div>
        </div>

        <!-- Fila inferior: 3 stats -->
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
    </section>

</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --dark-text: #2C3E50;
    }

    .inicio-wrap {
        max-width: 1140px;
        margin: 0 auto;
        padding: 36px 20px 60px;
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    /* ===== HERO ===== */
    .hero-inicio {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #2980B9 0%, #3498DB 55%, #5DADE2 100%);
        border-radius: 20px;
        padding: 54px 50px;
        box-shadow: 0 14px 40px rgba(41, 128, 185, 0.28);
    }

    .hero-bg-deco {
        position: absolute;
        top: -80px;
        right: -60px;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 820px;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 7px 16px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,0.3);
    }

    .hero-titulo {
        color: #fff;
        font-size: 46px;
        font-weight: 800;
        margin: 18px 0 16px;
        letter-spacing: -0.5px;
    }

    .hero-titulo span {
        font-weight: 400;
        opacity: 0.85;
    }

    .hero-texto {
        color: rgba(255, 255, 255, 0.95);
        font-size: 17px;
        line-height: 1.7;
        margin: 0;
    }

    .hero-texto strong {
        color: #fff;
        font-weight: 700;
    }

    /* ===== INSTITUCIÓN ===== */
    .institucion-card {
        background: #fff;
        border-radius: 18px;
        padding: 36px 44px;
        box-shadow: 0 6px 24px rgba(52, 152, 219, 0.1);
        display: flex;
        align-items: center;
        gap: 40px;
        border: 1px solid #EDF4FB;
    }

    .institucion-logo img {
        max-width: 150px;
        max-height: 150px;
        width: auto;
        height: auto;
        object-fit: contain;
        filter: drop-shadow(0 6px 18px rgba(52, 152, 219, 0.2));
        transition: transform 0.3s ease;
    }

    .institucion-logo img:hover {
        transform: scale(1.04);
    }

    .institucion-logo-placeholder {
        width: 130px;
        height: 130px;
        flex-shrink: 0;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 58px;
        color: white;
        box-shadow: 0 8px 24px rgba(52, 152, 219, 0.25);
    }

    .institucion-info {
        flex: 1;
    }

    .institucion-bienvenida {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-blue);
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .institucion-nombre {
        font-size: 34px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 8px 0 6px;
        line-height: 1.2;
    }

    .institucion-sub {
        color: #7F8C8D;
        font-size: 15px;
        margin: 0;
    }

    /* ===== FEATURES SECTION ===== */
    .features-section {
        background: linear-gradient(170deg, #EBF5FB 0%, #F4F9FD 60%, #EAF4FF 100%);
        border-radius: 24px;
        padding: 48px 40px 40px;
        border: 1px solid #D6EAF8;
        box-shadow: 0 6px 28px rgba(41,128,185,.07);
    }

    /* Header */
    .features-header {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 36px;
    }

    .features-header-line {
        flex: 1;
        height: 2px;
        background: linear-gradient(90deg, transparent, #AED6F1, transparent);
    }

    .features-header-text {
        text-align: center;
        flex-shrink: 0;
    }

    .features-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--primary-blue);
        background: rgba(52,152,219,.12);
        padding: 5px 14px;
        border-radius: 20px;
        border: 1px solid rgba(52,152,219,.25);
        margin-bottom: 10px;
    }

    .features-titulo {
        font-size: 26px;
        font-weight: 800;
        color: var(--dark-text);
        margin: 8px 0 6px;
        letter-spacing: -0.3px;
    }

    .features-subtitulo {
        font-size: 14px;
        color: #7F8C8D;
        margin: 0;
        max-width: 480px;
    }

    /* Grid de tarjetas */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .feature-card {
        position: relative;
        border-radius: 20px;
        padding: 30px 26px 26px;
        overflow: hidden;
        color: #fff;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: default;
    }

    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 44px rgba(0,0,0,.22) !important;
    }

    /* Variantes de color */
    .fc-blue {
        background: linear-gradient(145deg, #1A5276 0%, #2471A3 45%, #3498DB 100%);
        box-shadow: 0 10px 30px rgba(41,128,185,.35);
    }

    .fc-teal {
        background: linear-gradient(145deg, #0E6655 0%, #1A9370 45%, #1ABC9C 100%);
        box-shadow: 0 10px 30px rgba(26,179,148,.30);
    }

    .fc-indigo {
        background: linear-gradient(145deg, #1A237E 0%, #283593 45%, #3F51B5 100%);
        box-shadow: 0 10px 30px rgba(63,81,181,.32);
    }

    /* Número decorativo de fondo */
    .fc-number {
        position: absolute;
        top: -14px;
        right: 20px;
        font-size: 90px;
        font-weight: 900;
        color: rgba(255,255,255,.08);
        line-height: 1;
        pointer-events: none;
        user-select: none;
        letter-spacing: -4px;
    }

    /* Top: ícono + badge */
    .fc-top {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .fc-icon-wrap {
        width: 54px;
        height: 54px;
        flex-shrink: 0;
        background: rgba(255,255,255,.18);
        border: 1.5px solid rgba(255,255,255,.3);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        backdrop-filter: blur(4px);
    }

    .fc-badge {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.25);
        padding: 4px 12px;
        border-radius: 20px;
    }

    .fc-title {
        font-size: 20px;
        font-weight: 800;
        color: #fff;
        margin: 0 0 10px;
        letter-spacing: -0.2px;
    }

    .fc-desc {
        font-size: 14px;
        line-height: 1.65;
        color: rgba(255,255,255,.88);
        margin: 0 0 20px;
    }

    /* Footer con checks */
    .fc-footer {
        display: flex;
        flex-direction: column;
        gap: 6px;
        border-top: 1px solid rgba(255,255,255,.18);
        padding-top: 16px;
        margin-top: auto;
    }

    .fc-footer span {
        font-size: 12.5px;
        font-weight: 600;
        color: rgba(255,255,255,.92);
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .fc-footer span i {
        font-size: 11px;
        opacity: .85;
    }

    /* Stats bar */
    .features-stats {
        background: #fff;
        border-radius: 16px;
        padding: 20px 30px;
        display: flex;
        align-items: center;
        gap: 0;
        box-shadow: 0 3px 14px rgba(41,128,185,.1);
        border: 1px solid #D6EAF8;
    }

    .fstat {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .fstat-icon {
        font-size: 28px;
        color: var(--primary-blue);
        flex-shrink: 0;
        opacity: .85;
    }

    .fstat strong {
        display: block;
        font-size: 15px;
        font-weight: 700;
        color: var(--dark-text);
        margin-bottom: 2px;
    }

    .fstat span {
        font-size: 12.5px;
        color: #7F8C8D;
        line-height: 1.4;
    }

    .fstat-divider {
        width: 1px;
        height: 46px;
        background: #D6EAF8;
        margin: 0 28px;
        flex-shrink: 0;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1000px) {
        .features-grid { grid-template-columns: 1fr; }
        .features-stats { flex-direction: column; gap: 18px; }
        .fstat-divider { width: 60px; height: 1px; margin: 0; }
    }

    @media (max-width: 900px) {
        .features-section { padding: 36px 24px 30px; }
        .hero-inicio { padding: 44px 34px; }
        .hero-titulo { font-size: 38px; }
        .institucion-card { flex-direction: column; text-align: center; gap: 24px; }
        .institucion-bienvenida { justify-content: center; }
        .features-header { flex-direction: column; gap: 12px; }
        .features-header-line { display: none; }
    }

    @media (max-width: 480px) {
        .inicio-wrap { padding: 24px 14px 40px; gap: 20px; }
        .hero-inicio { padding: 34px 22px; }
        .hero-titulo { font-size: 30px; }
        .hero-texto { font-size: 15px; }
        .institucion-card { padding: 28px 22px; }
        .institucion-nombre { font-size: 26px; }
        .features-section { padding: 28px 16px 24px; }
        .feature-card { padding: 24px 20px 20px; }
        .fc-title { font-size: 18px; }
    }
</style>
