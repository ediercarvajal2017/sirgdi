<div class="cambiar-estado-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="page-banner__text">
            <h2>Cambiar Estado del Reporte</h2>
            <p>Actualiza el estado del reporte y proporciona una justificación.</p>
        </div>
    </div>

    <div class="reporte-info-modern">
        <div class="info-section">
            <h4><i class="fas fa-info-circle"></i> Información del Reporte</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-ticket-alt"></i> Ticket</span>
                    <span class="info-value"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-user"></i> Reportante</span>
                    <span class="info-value"><?php echo htmlspecialchars($reporte['nombre_reportante']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-check-circle"></i> Estado Actual</span>
                    <span class="info-value">
                        <span class="badge-estado"><?php echo htmlspecialchars($estados[$reporte['id_estado']] ?? 'Desconocido'); ?></span>
                    </span>
                </div>
                <div class="info-item info-item-full">
                    <span class="info-label"><i class="fas fa-file-alt"></i> Descripción</span>
                    <span class="info-value info-description"><?php echo htmlspecialchars($reporte['descripcion_problema'] ?? 'Sin descripción'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=procesar_cambiar_estado" class="form-modern">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="id_reporte" value="<?php echo intval($reporte['id_reporte']); ?>">

        <div class="form-group">
            <label for="id_estado_nuevo"><i class="fas fa-arrow-right"></i> Nuevo Estado <span class="required">*</span></label>
            <select name="id_estado_nuevo" id="id_estado_nuevo" class="input-modern input-select" required>
                <option value="">-- Seleccionar Estado --</option>
                <?php foreach ($estados as $id_estado => $nombre_estado): ?>
                    <?php if ($id_estado !== $reporte['id_estado']): ?>
                        <option value="<?php echo intval($id_estado); ?>">
                            <?php echo htmlspecialchars($nombre_estado); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="justificacion"><i class="fas fa-comment-alt"></i> Justificación <span class="required">*</span></label>
            <textarea
                name="justificacion"
                id="justificacion"
                class="input-modern"
                rows="5"
                placeholder="Explica el motivo del cambio de estado..."
                required></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-modern btn-primary-modern">
                <i class="fas fa-check-circle"></i> Cambiar Estado
            </button>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=kanban" class="btn-modern btn-secondary-modern">
                <i class="fas fa-times-circle"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
        --light-gray: #ECF0F1;
    }

    .cambiar-estado-container {
        max-width: 650px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .form-header {
        text-align: center;
        margin-bottom: 35px;
    }

    .form-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .form-header h2 i {
        color: var(--primary-blue);
        font-size: 28px;
    }

    .form-subtitle {
        color: var(--gray-text);
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }

    /* Información del Reporte */
    .reporte-info-modern {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
        box-shadow: 0 4px 16px rgba(52, 152, 219, 0.08);
        border-left: 4px solid var(--primary-blue);
    }

    .info-section h4 {
        margin: 0 0 16px 0;
        font-size: 14px;
        font-weight: 700;
        color: var(--dark-text);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-section h4 i {
        color: var(--primary-blue);
        font-size: 16px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .info-item {
        padding: 12px;
        background: var(--light-bg);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .info-item-full {
        grid-column: 1 / -1;
    }

    .info-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-text);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .info-label i {
        color: var(--primary-blue);
        font-size: 14px;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--dark-text);
    }

    .info-description {
        line-height: 1.5;
        color: var(--gray-text);
    }

    .badge-estado {
        display: inline-block;
        background: rgba(52, 152, 219, 0.15);
        color: var(--primary-blue);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    /* Formulario */
    .form-modern {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.1);
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: var(--dark-text);
        margin-bottom: 10px;
        font-size: 14px;
    }

    .form-group label i {
        color: var(--primary-blue);
        font-size: 16px;
    }

    .form-group .required {
        color: #E74C3C;
        font-weight: 700;
    }

    .input-modern {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--primary-blue);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        background-color: var(--light-bg);
        color: var(--dark-text);
        transition: all 0.3s ease;
        resize: vertical;
    }

    .input-modern::placeholder {
        color: var(--gray-text);
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--dark-blue);
        background-color: white;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .input-select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233498DB' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 20px;
        padding-right: 40px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .btn-modern {
        padding: 12px 28px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        min-width: 200px;
        justify-content: center;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
    }

    .btn-secondary-modern {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
        min-width: 200px;
        justify-content: center;
    }

    .btn-secondary-modern:hover {
        background: var(--gray-text);
        color: white;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .cambiar-estado-container {
            padding: 20px 15px;
        }

        .form-header h2 {
            font-size: 22px;
        }

        .form-modern {
            padding: 20px;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .info-item-full {
            grid-column: 1;
        }

        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
</style>
