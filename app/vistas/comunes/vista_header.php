<!-- Header Navigation - Diseño Profesional -->
<header class="header-modern">
    <div class="header-bar">
        <div class="header-brand-section">
            <div class="header-logo">
                <img src="<?php echo config('app.url_base'); ?>/img/logo_icono.png" alt="SIRGDI">
            </div>
            <div class="header-title">
                <h1><?php echo config('app.app_name'); ?></h1>
                <p class="brand-subtitle">Sistema de Reporte de Daños</p>
            </div>
        </div>

        <nav class="header-nav-modern">
        <ul class="nav-menu-primary">
            <!-- HOME: Dashboard -->
            <li class="nav-item">
                <a href="<?php echo config('app.url_base'); ?>/?controlador=dashboard&accion=inicio" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>INICIO</span>
                </a>
            </li>

            <!-- Reportes Section -->
            <li class="nav-item nav-dropdown">
                <a href="#" class="nav-link" data-toggle="dropdown">
                    <i class="fas fa-file-alt"></i>
                    <span>REPORTES</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="dropdown-menu">
                    <?php $perm_rep = $_SESSION['permisos'] ?? []; ?>
                    <?php if (in_array('crear_reporte', $perm_rep)): ?>
                        <li><a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=crear">
                            <i class="fas fa-plus-circle"></i> Crear Reporte
                        </a></li>
                    <?php endif; ?>
                    <?php if (in_array('ver_propio_reporte', $perm_rep) && !in_array('ver_todos_reportes', $perm_rep)): ?>
                        <li><a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=listar">
                            <i class="fas fa-list-alt"></i> Mis Reportes
                        </a></li>
                    <?php endif; ?>
                    <?php if (in_array('ver_todos_reportes', $perm_rep)): ?>
                        <li><a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=kanban">
                            <i class="fas fa-columns"></i> Gestión de Reportes
                        </a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- Técnico Section -->
            <?php if (isset($_SESSION['permisos']) && in_array('registrar_informe', $_SESSION['permisos'])): ?>
                <li class="nav-item">
                    <a href="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=mis_asignaciones" class="nav-link">
                        <i class="fas fa-toolbox"></i>
                        <span>MIS ASIGNACIONES</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Configuración Section -->
            <?php
                $perm = $_SESSION['permisos'] ?? [];
                $puede_config = in_array('gestionar_usuarios', $perm) || in_array('gestionar_sla', $perm) || in_array('ver_auditoria', $perm);
            ?>
            <?php if ($puede_config): ?>
                <li class="nav-item nav-dropdown">
                    <a href="#" class="nav-link" data-toggle="dropdown">
                        <i class="fas fa-cog"></i>
                        <span>CONFIGURACIÓN</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (in_array('gestionar_usuarios', $perm)): ?>
                            <li><a href="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_usuarios">
                                <i class="fas fa-users"></i> Gestionar Usuarios
                            </a></li>
                        <?php endif; ?>
                        <?php if (in_array('gestionar_sla', $perm)): ?>
                            <li><a href="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_sla">
                                <i class="fas fa-hourglass-end"></i> Configurar SLA
                            </a></li>
                        <?php endif; ?>
                        <?php if (in_array('gestionar_usuarios', $perm)): ?>
                            <li><a href="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_roles">
                                <i class="fas fa-lock"></i> Gestionar Roles y Permisos
                            </a></li>
                        <?php endif; ?>
                        <?php if (in_array('ver_auditoria', $perm)): ?>
                            <li><a href="<?php echo config('app.url_base'); ?>/?controlador=dashboard&accion=auditoria">
                                <i class="fas fa-file-alt"></i> Auditoría
                            </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Administración Global Section -->
            <?php if (isset($_SESSION['permisos']) && in_array('gestionar_instituciones', $_SESSION['permisos'])): ?>
                <li class="nav-item">
                    <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=inicio" class="nav-link nav-highlight">
                        <i class="fas fa-crown"></i>
                        <span>ADMIN GLOBAL</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        </nav>

        <div class="header-actions">
            <div class="header-user-dropdown" id="userDropdown">
                <button type="button" class="header-user-info" onclick="toggleUserMenu(event)">
                    <i class="fas fa-user-circle"></i>
                    <div class="header-user-text">
                        <span class="header-user-name"><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['email'] ?? 'Usuario'); ?></span>
                        <span class="header-user-role"><?php echo htmlspecialchars($_SESSION['rol'] ?? 'Usuario'); ?></span>
                    </div>
                    <i class="fas fa-chevron-down header-user-caret"></i>
                </button>
                <ul class="header-user-menu">
                    <?php if (!empty($_SESSION['id_institucion_propia'])): ?>
                        <!-- Técnico externo: nombre de la institución activa y switcher -->
                        <li class="header-user-inst-label">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($_SESSION['nombre_institucion_trabajo'] ?? 'Institución'); ?>
                        </li>
                        <li class="header-user-divider"></li>
                        <li>
                            <a href="#" onclick="document.getElementById('switcherModal').style.display='flex'; return false;">
                                <i class="fas fa-exchange-alt"></i> Cambiar Institución
                            </a>
                        </li>
                        <li class="header-user-divider"></li>
                    <?php endif; ?>
                    <li><a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=cambiar_contrasena">
                        <i class="fas fa-key"></i> Cambiar Contraseña
                    </a></li>
                    <li class="header-user-divider"></li>
                    <li><a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=logout" class="header-menu-logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --dark-text: #2C3E50;
        --light-bg: #ECF0F1;
    }

    /* Header Modern */
    .header-modern {
        background: white;
        box-shadow: 0 2px 12px rgba(52, 152, 219, 0.1);
        border-bottom: 3px solid var(--primary-blue);
    }

    /* Header Bar - una sola fila: marca | nav (centro) | usuario */
    .header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 10px 30px;
        background: linear-gradient(135deg, #FFFFFF 0%, #F8FBFC 100%);
    }

    .header-brand-section {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-shrink: 0;
    }

    .header-logo {
        width: 45px;
        height: 45px;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }
    .header-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 3px;
    }

    .header-title h1 {
        margin: 0;
        font-size: 20px;
        color: var(--dark-text);
        font-weight: 700;
    }

    .brand-subtitle {
        margin: 0;
        font-size: 12px;
        color: #95A5A6;
        font-weight: 500;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-shrink: 0;
    }

    /* Menú desplegable del usuario (clic en el nombre) */
    .header-user-dropdown {
        position: relative;
    }

    .header-user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--dark-text);
        font-weight: 500;
        font-size: 14px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 6px 12px 6px 6px;
        border-radius: 10px;
        transition: background 0.25s;
        font-family: inherit;
    }

    .header-user-info:hover {
        background: #EDF4FB;
    }

    .header-user-info > .fa-user-circle {
        font-size: 30px;
        color: var(--primary-blue);
    }

    .header-user-caret {
        font-size: 12px !important;
        color: #95A5A6;
        transition: transform 0.25s;
        margin-left: 2px;
    }

    .header-user-dropdown.open .header-user-caret {
        transform: rotate(180deg);
    }

    .header-user-text {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
        text-align: left;
    }

    .header-user-name {
        font-weight: 600;
        color: var(--dark-text);
        font-size: 14px;
    }

    .header-user-role {
        font-size: 12px;
        color: var(--primary-blue);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .header-user-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 200px;
        list-style: none;
        margin: 0;
        padding: 8px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(44, 62, 80, 0.15);
        border: 1px solid #EDF2F4;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-6px);
        transition: all 0.2s ease;
        z-index: 1100;
    }

    .header-user-dropdown.open .header-user-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .header-user-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        color: var(--dark-text);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .header-user-menu a i {
        font-size: 15px;
        width: 16px;
        text-align: center;
    }

    .header-user-menu a:hover {
        background: #EDF4FB;
        color: var(--primary-blue);
    }

    .header-user-menu a:hover i {
        color: var(--primary-blue);
    }

    .header-user-menu a i {
        color: var(--primary-blue);
    }

    .header-user-divider {
        height: 1px;
        background: #EDF2F4;
        margin: 6px 4px;
    }

    /* Etiqueta de institución activa (técnico externo) */
    .header-user-inst-label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px 6px;
        font-size: 12px;
        font-weight: 700;
        color: #3498DB;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        pointer-events: none;
    }
    .header-user-inst-label i { color: #3498DB; font-size: 13px; }

    /* Cerrar Sesión en rojo */
    .header-menu-logout i { color: #E74C3C !important; }
    .header-menu-logout:hover {
        background: #FDEDEC !important;
        color: #E74C3C !important;
    }
    .header-menu-logout:hover i { color: #E74C3C !important; }

    /* Navigation Modern - elemento central de la barra */
    .header-nav-modern {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        background: transparent;
        height: 54px;
    }

    .nav-menu-primary {
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0;
        margin: 0;
        padding: 0;
        height: 100%;
        align-items: center;
    }

    .nav-item {
        height: 100%;
        display: flex;
        align-items: center;
        position: relative;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 20px;
        height: 100%;
        color: var(--dark-text);
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        transition: all 0.3s;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
    }

    .nav-link i:first-child {
        font-size: 16px;
    }

    .nav-link:hover {
        color: var(--primary-blue);
        background: var(--light-bg);
        border-bottom-color: var(--primary-blue);
    }

    .nav-link.active {
        color: var(--primary-blue);
        border-bottom-color: var(--primary-blue);
        background: var(--light-bg);
    }

    /* Dropdown */
    .nav-dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        list-style: none;
        margin: 0;
        padding: 10px 0;
        background: white;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        min-width: 200px;
        display: none;
        z-index: 1000;
    }

    .nav-dropdown:hover .dropdown-menu {
        display: block;
    }

    .dropdown-menu li {
        list-style: none;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        color: var(--dark-text);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .dropdown-menu a:hover {
        background: var(--light-bg);
        color: var(--primary-blue);
        padding-left: 25px;
    }

    .dropdown-menu i {
        font-size: 14px;
        color: var(--primary-blue);
    }

    /* Highlight para Admin Global */
    .nav-highlight .nav-link {
        color: white;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        border-radius: 8px;
        margin: 0 10px;
    }

    .nav-highlight .nav-link:hover {
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
    }

    /* Nav Actions */
    .nav-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .nav-action-btn {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        background: #E8EDEF;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--dark-text);
        font-size: 16px;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
    }

    .nav-action-btn:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .header-nav-modern {
            padding: 0 20px;
        }

        .nav-link {
            padding: 0 15px;
            font-size: 12px;
        }

        .brand-subtitle {
            display: none;
        }

        .header-user-info span {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .header-bar {
            padding: 10px 15px;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
        }

        .header-nav-modern {
            order: 3;
            flex: 1 1 100%;
            padding: 8px 0 0;
            height: auto;
            flex-wrap: wrap;
            gap: 10px;
            border-top: 1px solid #E8EDEF;
        }

        .nav-menu-primary {
            gap: 5px;
        }

        .nav-link {
            padding: 8px 12px;
            font-size: 11px;
        }

        .nav-link span {
            display: none;
        }

        .nav-link i:first-child {
            margin: 0;
        }

        .nav-highlight .nav-link {
            margin: 0 5px;
        }

        .dropdown-menu {
            min-width: 150px;
        }

        .dropdown-menu a {
            padding: 10px 15px;
            font-size: 12px;
        }
    }
</style>

<script>
    document.querySelectorAll('[data-toggle="dropdown"]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
        });
    });

    // Menú desplegable del usuario (clic en el nombre)
    function toggleUserMenu(e) {
        e.stopPropagation();
        const dd = document.getElementById('userDropdown');
        if (dd) dd.classList.toggle('open');
    }

    // Cerrar el menú al hacer clic fuera de él
    document.addEventListener('click', function(e) {
        const dd = document.getElementById('userDropdown');
        if (dd && dd.classList.contains('open') && !dd.contains(e.target)) {
            dd.classList.remove('open');
        }
    });
</script>

<?php if (!empty($_SESSION['id_institucion_propia'])): ?>
<!-- Modal: cambiar institución activa para técnicos externos -->
<div id="switcherModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
     z-index:9000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:32px; max-width:460px; width:90%;
                box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <h3 style="margin:0 0 6px; font-size:18px; color:#2C3E50;">
            <i class="fas fa-exchange-alt" style="color:#3498DB;"></i> Cambiar Institución
        </h3>
        <p style="margin:0 0 20px; font-size:14px; color:#7F8C8D;">
            Selecciona la institución en la que vas a trabajar en esta sesión.
        </p>
        <form method="POST"
              action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=cambiar_institucion">
            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <?php
                require_once APP_PATH . '/modelos/modelo_usuario.php';
                $modelo_sw = new ModeloUsuario();
                $insts_sw  = $modelo_sw->obtener_instituciones_tecnico($_SESSION['id_usuario']);
            ?>
            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;">
                <?php foreach ($insts_sw as $inst_sw): ?>
                    <?php $activa = ((int)$inst_sw['id_institucion'] === (int)$_SESSION['id_institucion']); ?>
                    <label style="display:flex; align-items:center; gap:12px; padding:14px 16px;
                                  border:2px solid <?php echo $activa ? '#3498DB' : '#E2E8F0'; ?>;
                                  border-radius:10px; cursor:pointer;
                                  background:<?php echo $activa ? '#EBF5FB' : '#F8FBFC'; ?>;">
                        <input type="radio" name="id_institucion"
                               value="<?php echo (int)$inst_sw['id_institucion']; ?>"
                               <?php echo $activa ? 'checked' : ''; ?> required
                               style="accent-color:#3498DB; width:18px; height:18px;">
                        <div>
                            <div style="font-weight:600; color:#2C3E50; font-size:14px;">
                                <?php echo htmlspecialchars($inst_sw['nombre']); ?>
                            </div>
                            <?php if ($activa): ?>
                                <div style="font-size:11px; color:#3498DB; font-weight:600;">
                                    <i class="fas fa-check-circle"></i> Institución actual
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button type="button"
                        onclick="document.getElementById('switcherModal').style.display='none'"
                        style="padding:10px 20px; border:1px solid #E2E8F0; background:#fff;
                               border-radius:8px; cursor:pointer; font-size:14px; color:#7F8C8D;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:10px 20px; background:#3498DB; color:#fff; border:none;
                               border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
