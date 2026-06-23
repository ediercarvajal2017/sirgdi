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

    <!-- CARACTERÍSTICAS: reportar · administrar · seguimiento -->
    <section class="features-inicio">
        <div class="feature-card">
            <div class="feature-icon feature-icon-1"><i class="fas fa-clipboard-check"></i></div>
            <h3>Reportar</h3>
            <p>Registra fallas, daños, solicitudes o incidencias en la infraestructura escolar de forma rápida y guiada.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon feature-icon-2"><i class="fas fa-tasks"></i></div>
            <h3>Administrar</h3>
            <p>Asigna técnicos, prioriza por urgencia y gestiona cada reporte hasta su resolución con control total.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon feature-icon-3"><i class="fas fa-route"></i></div>
            <h3>Hacer seguimiento</h3>
            <p>Consulta el estado, la trazabilidad y las evidencias de cada incidencia en tiempo real.</p>
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

    /* ===== FEATURES ===== */
    .features-inicio {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
    }

    .feature-card {
        background: #fff;
        border-radius: 16px;
        padding: 30px 26px;
        box-shadow: 0 4px 18px rgba(52, 152, 219, 0.08);
        border: 1px solid #EDF4FB;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(52, 152, 219, 0.18);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: #fff;
        margin-bottom: 18px;
    }

    .feature-icon-1 { background: linear-gradient(135deg, #3498DB, #2980B9); }
    .feature-icon-2 { background: linear-gradient(135deg, #27AE60, #1E8449); }
    .feature-icon-3 { background: linear-gradient(135deg, #E67E22, #CA6F1E); }

    .feature-card h3 {
        font-size: 19px;
        color: var(--dark-text);
        margin: 0 0 10px;
        font-weight: 700;
    }

    .feature-card p {
        color: #7F8C8D;
        font-size: 14.5px;
        line-height: 1.6;
        margin: 0;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 900px) {
        .features-inicio { grid-template-columns: 1fr; }
        .hero-inicio { padding: 44px 34px; }
        .hero-titulo { font-size: 38px; }
        .institucion-card { flex-direction: column; text-align: center; gap: 24px; }
        .institucion-bienvenida { justify-content: center; }
    }

    @media (max-width: 480px) {
        .inicio-wrap { padding: 24px 14px 40px; gap: 20px; }
        .hero-inicio { padding: 34px 22px; }
        .hero-titulo { font-size: 30px; }
        .hero-texto { font-size: 15px; }
        .institucion-card { padding: 28px 22px; }
        .institucion-nombre { font-size: 26px; }
    }
</style>
