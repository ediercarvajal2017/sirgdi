<div class="container admin-panel">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-cog"></i></div>
        <div class="page-banner__text">
            <h2>Panel de Administración</h2>
            <p>Configuración de la institución.</p>
        </div>
    </div>

    <div class="admin-hint">
        <i class="fas fa-info-circle"></i>
        <p>Usa el menú <strong>Configuración</strong> <i class="fas fa-cog"></i> de la barra superior para acceder a:
        <strong>Gestionar Usuarios</strong>, <strong>Configurar SLA</strong>,
        <strong>Gestionar Roles y Permisos</strong> y <strong>Auditoría</strong>.</p>
    </div>
</div>

<style>
.admin-panel {
    max-width: 1000px;
    margin: 30px auto;
    padding: 20px;
}

.admin-panel h2 {
    color: #3498DB;
    margin-bottom: 10px;
}

.subtitle {
    color: #808B96;
    font-size: 0.95em;
    margin-bottom: 30px;
}

.admin-hint {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: #F4F9FD;
    border: 1px solid #D6E9F8;
    border-left: 4px solid #3498DB;
    border-radius: 8px;
    padding: 20px 24px;
    margin-top: 25px;
}

.admin-hint > i {
    font-size: 24px;
    color: #3498DB;
    margin-top: 2px;
}

.admin-hint p {
    margin: 0;
    color: #2C3E50;
    font-size: 15px;
    line-height: 1.6;
}

.admin-hint p i {
    color: #3498DB;
}
</style>
