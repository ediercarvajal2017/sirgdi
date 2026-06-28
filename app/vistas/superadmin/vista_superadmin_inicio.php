<div class="superadmin-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-crown"></i></div>
        <div class="page-banner__text">
            <h2>Administración Global - SIRGDI</h2>
            <p>Gestión de todas las instituciones educativas.</p>
        </div>
    </div>

    <div class="actions-bar">
        <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=crear_institucion" class="btn-modern btn-primary-modern">
            <i class="fas fa-plus-circle"></i> Crear Nueva Institución
        </a>
        <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=gestionar_tecnicos" class="btn-modern btn-tecnicos-modern">
            <i class="fas fa-user-cog"></i> Técnicos Externos
        </a>
    </div>

    <?php if (!empty($cred_admin)): ?>
        <!-- CREDENCIALES DEL ADMIN RECIÉN CREADO (se muestran una sola vez) -->
        <div class="cred-card">
            <div class="cred-head">
                <i class="fas fa-circle-check"></i>
                <div>
                    <strong>Institución «<?php echo htmlspecialchars($cred_admin['institucion']); ?>» creada con su administrador.</strong>
                    Entrega estas credenciales al administrador. <em>No se volverán a mostrar.</em>
                </div>
            </div>
            <div class="cred-body">
                <div class="cred-item">
                    <span class="cred-label"><i class="fas fa-envelope"></i> Usuario (email)</span>
                    <span class="cred-value" id="cred-email"><?php echo htmlspecialchars($cred_admin['email']); ?></span>
                </div>
                <div class="cred-item">
                    <span class="cred-label"><i class="fas fa-key"></i> Contraseña</span>
                    <span class="cred-value" id="cred-pass"><?php echo htmlspecialchars($cred_admin['password']); ?></span>
                </div>
                <button type="button" class="btn-copiar" onclick="copiarCredAdmin()">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($instituciones)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No hay instituciones registradas.</p>
        </div>
    <?php else: ?>
        <div class="institutions-list">
            <?php foreach ($instituciones as $institucion): ?>
                <div class="institution-row">
                    <div class="institution-info">
                        <span class="institution-name"><?php echo htmlspecialchars($institucion['nombre']); ?></span>
                        <span class="badge <?php echo $institucion['es_activa'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $institucion['es_activa'] ? 'Activa' : 'Inactiva'; ?>
                        </span>
                    </div>
                    <div class="institution-actions">
                        <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=gestionar_sedes&id=<?php echo $institucion['id_institucion']; ?>" class="btn-action-compact btn-sedes" title="Gestionar sedes">
                            <i class="fas fa-building"></i>
                        </a>
                        <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=editar_institucion&id=<?php echo $institucion['id_institucion']; ?>" class="btn-action-compact btn-edit-action" title="Editar institución">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=eliminar_institucion" style="display:inline;" onsubmit="return confirm('¿Eliminar la institución «<?php echo htmlspecialchars($institucion['nombre'], ENT_QUOTES); ?>»?\n\nSe eliminarán sus usuarios, sedes y configuración. Esta acción es permanente.\n\n(Si tiene reportes registrados, no se podrá eliminar: desactívala en su lugar.)');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Validacion::generar_csrf_token()); ?>">
                            <input type="hidden" name="id_institucion" value="<?php echo $institucion['id_institucion']; ?>">
                            <button type="submit" class="btn-action-compact btn-delete-action" title="Eliminar institución">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function copiarCredAdmin() {
    var email = document.getElementById('cred-email').textContent.trim();
    var pass = document.getElementById('cred-pass').textContent.trim();
    navigator.clipboard.writeText('Usuario: ' + email + '\nContraseña: ' + pass).then(function() {
        if (typeof toast !== 'undefined' && toast) { toast.success('Copiado', 'Credenciales copiadas al portapapeles.'); }
    });
}
</script>

<?php $toast_exito_msg = 'Operación realizada correctamente.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
    .cred-card { background:#fff; border-radius:12px; margin-bottom:25px; overflow:hidden; box-shadow:0 4px 16px rgba(39,174,96,.15); border-left:4px solid #27AE60; }
    .cred-head { display:flex; align-items:center; gap:12px; padding:16px 20px; background:rgba(39,174,96,.1); color:#1E8449; font-size:14px; }
    .cred-head i { font-size:22px; }
    .cred-body { display:flex; align-items:flex-end; gap:20px; padding:18px 20px; flex-wrap:wrap; }
    .cred-item { display:flex; flex-direction:column; gap:4px; }
    .cred-label { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#7F8C8D; font-weight:600; display:flex; align-items:center; gap:6px; }
    .cred-label i { color:#3498DB; }
    .cred-value { font-family:'Courier New',monospace; font-size:16px; font-weight:700; color:#2C3E50; background:#F8FBFC; padding:8px 14px; border-radius:6px; border:1px dashed #3498DB; }
    .btn-copiar { padding:10px 18px; background:linear-gradient(135deg,#3498DB,#2980B9); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:8px; transition:all .3s; }
    .btn-copiar:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(52,152,219,.35); }
</style>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
        --success-color: #27AE60;
        --danger-color: #E74C3C;
    }

    .superadmin-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .superadmin-header {
        margin-bottom: 35px;
    }

    .superadmin-header h2 {
        font-size: 32px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .superadmin-header h2 i {
        color: var(--primary-blue);
        font-size: 28px;
    }

    .subtitle {
        color: var(--gray-text);
        font-size: 15px;
        margin: 0;
        font-weight: 500;
    }

    .actions-bar {
        margin-bottom: 30px;
        display: flex;
        gap: 12px;
    }

    .btn-modern {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
    }

    .btn-tecnicos-modern {
        background: linear-gradient(135deg, #8E44AD 0%, #7D3C98 100%);
        color: white;
    }

    .btn-tecnicos-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(142, 68, 173, 0.3);
    }

    .institutions-list {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(52, 152, 219, 0.08);
        overflow: hidden;
    }

    .institution-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid #E8EDEF;
        transition: background-color 0.2s ease;
    }

    .institution-row:last-child {
        border-bottom: none;
    }

    .institution-row:hover {
        background-color: rgba(52, 152, 219, 0.03);
    }

    .institution-info {
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1;
    }

    .institution-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--dark-text);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-active {
        background: rgba(39, 174, 96, 0.15);
        color: var(--success-color);
    }

    .badge-inactive {
        background: rgba(231, 76, 60, 0.15);
        color: var(--danger-color);
    }

    .institution-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action-compact {
        width: 36px;
        height: 36px;
        padding: 0;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-sedes {
        color: #E67E22;
        background: rgba(230, 126, 34, 0.1);
    }

    .btn-sedes:hover {
        background: #E67E22;
        color: white;
        transform: scale(1.08);
    }

    .btn-edit-action {
        color: var(--gray-text);
        background: rgba(128, 139, 150, 0.1);
    }

    .btn-edit-action:hover {
        background: var(--gray-text);
        color: white;
        transform: scale(1.08);
    }

    .btn-delete-action {
        color: var(--danger-color);
        background: rgba(231, 76, 60, 0.1);
        border: none;
        cursor: pointer;
    }

    .btn-delete-action:hover {
        background: var(--danger-color);
        color: white;
        transform: scale(1.08);
    }

    .empty-state {
        text-align: center;
        padding: 80px 40px;
        background: white;
        border-radius: 12px;
        color: var(--gray-text);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
        display: block;
    }

    .empty-state p {
        margin: 0;
        font-size: 15px;
    }

    @media (max-width: 768px) {
        .superadmin-header h2 {
            font-size: 24px;
        }

        .institution-row {
            padding: 12px 16px;
        }

        .institution-info {
            gap: 12px;
            flex-wrap: wrap;
        }

        .institution-name {
            flex: 0 1 100%;
            font-size: 13px;
        }

        .btn-action-compact {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }
    }
</style>
