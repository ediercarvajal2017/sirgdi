<?php
// Vista: Selección de institución de trabajo para técnicos externos
// Solo se muestra cuando $_SESSION['pendiente_seleccion_institucion'] === true
?>
<div class="auth-container">
    <div class="auth-card" style="max-width: 520px;">

        <div class="auth-header">
            <div class="auth-icon" style="background: linear-gradient(135deg, #3498DB, #2980B9);">
                <i class="fas fa-building"></i>
            </div>
            <h2 class="auth-title">Seleccionar Institución</h2>
            <p class="auth-subtitle">
                Hola, <strong><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Técnico'); ?></strong>.<br>
                Tienes acceso a varias instituciones. ¿Dónde vas a trabajar hoy?
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=procesar_seleccion_institucion"
              class="auth-form">

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="instituciones-grid">
                <?php foreach ($instituciones as $inst): ?>
                    <label class="institucion-card" for="inst_<?php echo (int)$inst['id_institucion']; ?>">
                        <input type="radio"
                               id="inst_<?php echo (int)$inst['id_institucion']; ?>"
                               name="id_institucion"
                               value="<?php echo (int)$inst['id_institucion']; ?>"
                               required>
                        <div class="inst-card-inner">
                            <div class="inst-icon">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="inst-info">
                                <span class="inst-nombre"><?php echo htmlspecialchars($inst['nombre']); ?></span>
                                <span class="inst-desde">
                                    Vinculado desde <?php echo date('d/m/Y', strtotime($inst['fecha_vinculacion'])); ?>
                                </span>
                            </div>
                            <div class="inst-check">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-auth btn-primary" style="margin-top: 24px;">
                <i class="fas fa-arrow-right"></i>
                Ingresar a la institución seleccionada
            </button>

        </form>

        <div class="auth-footer" style="margin-top: 20px; text-align: center;">
            <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=logout"
               style="font-size: 13px; color: #95A5A6; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>

    </div>
</div>

<style>
.instituciones-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 8px;
}

.institucion-card {
    cursor: pointer;
    display: block;
}

.institucion-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.inst-card-inner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    background: #F8FBFC;
    transition: all 0.2s ease;
}

.institucion-card:hover .inst-card-inner {
    border-color: #3498DB;
    background: #EBF5FB;
}

.institucion-card input[type="radio"]:checked + .inst-card-inner {
    border-color: #3498DB;
    background: #EBF5FB;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
}

.inst-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #3498DB, #2980B9);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    flex-shrink: 0;
}

.inst-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.inst-nombre {
    font-weight: 600;
    font-size: 15px;
    color: #2C3E50;
}

.inst-desde {
    font-size: 12px;
    color: #95A5A6;
    margin-top: 2px;
}

.inst-check {
    font-size: 20px;
    color: #BDC3C7;
    transition: color 0.2s;
}

.institucion-card input[type="radio"]:checked + .inst-card-inner .inst-check {
    color: #3498DB;
}
</style>
