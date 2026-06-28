<?php
// Vista: Gestión de vínculos técnico ↔ institución educativa
?>
<div class="superadmin-container">

    <div class="page-banner">
        <div class="page-banner__icon" style="background: linear-gradient(135deg, #8E44AD, #7D3C98);">
            <i class="fas fa-user-cog"></i>
        </div>
        <div class="page-banner__text">
            <h2>Técnicos Externos</h2>
            <p>Vincula técnicos de empresas de mantenimiento a instituciones educativas.</p>
        </div>
    </div>

    <div class="actions-bar">
        <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=inicio"
           class="btn-modern btn-secondary-modern">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </div>

    <?php if (empty($tecnicos)): ?>
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <p>No hay técnicos externos registrados.<br>
               <small>Crea una institución de tipo <strong>empresa_mantenimiento</strong> y agrega usuarios con rol <em>técnico</em>.</small>
            </p>
        </div>
    <?php else: ?>
        <div class="tecnicos-list">
            <?php foreach ($tecnicos as $tec): ?>
                <div class="tecnico-card">
                    <div class="tecnico-header">
                        <div class="tecnico-avatar">
                            <i class="fas fa-hard-hat"></i>
                        </div>
                        <div class="tecnico-info">
                            <span class="tecnico-nombre"><?php echo htmlspecialchars($tec['nombre_completo']); ?></span>
                            <span class="tecnico-email"><?php echo htmlspecialchars($tec['correo_electronico']); ?></span>
                            <span class="tecnico-empresa">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($tec['empresa']); ?>
                            </span>
                        </div>
                        <div class="tecnico-counter">
                            <span class="counter-num"><?php echo count($tec['instituciones']); ?></span>
                            <span class="counter-label">institución<?php echo count($tec['instituciones']) !== 1 ? 'es' : ''; ?></span>
                        </div>
                    </div>

                    <div class="tecnico-body">
                        <!-- Instituciones vinculadas actuales -->
                        <?php if (empty($tec['instituciones'])): ?>
                            <p class="sin-vinculos">
                                <i class="fas fa-info-circle"></i>
                                Sin instituciones vinculadas aún.
                            </p>
                        <?php else: ?>
                            <div class="vinculos-lista">
                                <?php foreach ($tec['instituciones'] as $inst): ?>
                                    <div class="vinculo-item">
                                        <div class="vinculo-info">
                                            <i class="fas fa-school"></i>
                                            <span class="vinculo-nombre"><?php echo htmlspecialchars($inst['nombre']); ?></span>
                                            <span class="vinculo-fecha">
                                                desde <?php echo date('d/m/Y', strtotime($inst['fecha_vinculacion'])); ?>
                                            </span>
                                        </div>
                                        <form method="POST"
                                              action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=desvincular_tecnico"
                                              onsubmit="return confirm('¿Desvincular a <?php echo htmlspecialchars($tec['nombre_completo'], ENT_QUOTES); ?> de <?php echo htmlspecialchars($inst['nombre'], ENT_QUOTES); ?>?');">
                                            <input type="hidden" name="csrf_token"    value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="id_usuario"    value="<?php echo (int)$tec['id_usuario']; ?>">
                                            <input type="hidden" name="id_institucion" value="<?php echo (int)$inst['id_institucion']; ?>">
                                            <button type="submit" class="btn-desvincular" title="Desvincular">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario para agregar nuevo vínculo -->
                        <?php
                        // Filtrar instituciones ya vinculadas para no mostrarlas en el select
                        $ids_vinculadas = array_column($tec['instituciones'], 'id_institucion');
                        $disponibles = array_filter($instituciones_educativas, function($i) use ($ids_vinculadas) {
                            return !in_array($i['id_institucion'], $ids_vinculadas);
                        });
                        ?>
                        <?php if (!empty($disponibles)): ?>
                            <form method="POST"
                                  action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=vincular_tecnico"
                                  class="form-vincular">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="id_usuario" value="<?php echo (int)$tec['id_usuario']; ?>">
                                <select name="id_institucion" class="select-institucion" required>
                                    <option value="">— Seleccionar institución a vincular —</option>
                                    <?php foreach ($disponibles as $disp): ?>
                                        <option value="<?php echo (int)$disp['id_institucion']; ?>">
                                            <?php echo htmlspecialchars($disp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-vincular">
                                    <i class="fas fa-link"></i> Vincular
                                </button>
                            </form>
                        <?php elseif (empty($instituciones_educativas)): ?>
                            <p class="sin-vinculos" style="color:#E67E22;">
                                <i class="fas fa-exclamation-triangle"></i>
                                No hay instituciones educativas activas en el sistema.
                            </p>
                        <?php else: ?>
                            <p class="sin-vinculos" style="color:#27AE60;">
                                <i class="fas fa-check-circle"></i>
                                Vinculado a todas las instituciones disponibles.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.tecnicos-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.tecnico-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(52,152,219,.08);
    overflow: hidden;
    border: 1px solid #E8EDEF;
}

.tecnico-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 22px;
    background: linear-gradient(135deg, #F8FBFC 0%, #EBF5FB 100%);
    border-bottom: 1px solid #E2E8F0;
}

.tecnico-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #8E44AD, #7D3C98);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}

.tecnico-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.tecnico-nombre {
    font-weight: 700;
    font-size: 15px;
    color: #2C3E50;
}

.tecnico-email {
    font-size: 13px;
    color: #7F8C8D;
}

.tecnico-empresa {
    font-size: 12px;
    color: #8E44AD;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
}

.tecnico-counter {
    text-align: center;
    flex-shrink: 0;
}

.counter-num {
    display: block;
    font-size: 28px;
    font-weight: 800;
    color: #3498DB;
    line-height: 1;
}

.counter-label {
    font-size: 11px;
    color: #7F8C8D;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.tecnico-body {
    padding: 18px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.sin-vinculos {
    font-size: 13px;
    color: #95A5A6;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.vinculos-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.vinculo-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #F8FBFC;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
}

.vinculo-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #2C3E50;
}

.vinculo-info i {
    color: #3498DB;
    font-size: 15px;
}

.vinculo-nombre {
    font-weight: 600;
}

.vinculo-fecha {
    font-size: 12px;
    color: #95A5A6;
}

.btn-desvincular {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    background: rgba(231,76,60,.1);
    color: #E74C3C;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all .2s;
    flex-shrink: 0;
}

.btn-desvincular:hover {
    background: #E74C3C;
    color: #fff;
    transform: scale(1.1);
}

.form-vincular {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.select-institucion {
    flex: 1;
    min-width: 220px;
    padding: 9px 14px;
    border: 1.5px solid #D0D7E0;
    border-radius: 8px;
    font-size: 14px;
    color: #2C3E50;
    background: #F8FBFC;
    outline: none;
    transition: border-color .2s;
}

.select-institucion:focus {
    border-color: #3498DB;
    background: #fff;
}

.btn-vincular {
    padding: 9px 18px;
    background: linear-gradient(135deg, #27AE60, #1E8449);
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all .2s;
    white-space: nowrap;
}

.btn-vincular:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(39,174,96,.3);
}

.btn-secondary-modern {
    background: #F4F6F8;
    color: #5D6D7E;
    border: 1.5px solid #D0D7E0;
}

.btn-secondary-modern:hover {
    background: #E8ECF0;
    transform: translateY(-1px);
}

@media (max-width: 640px) {
    .tecnico-header { flex-wrap: wrap; }
    .form-vincular { flex-direction: column; align-items: stretch; }
    .select-institucion { min-width: unset; }
}
</style>
