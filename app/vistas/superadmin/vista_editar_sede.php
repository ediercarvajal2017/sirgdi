<!-- Editar Sede -->
<div class="sede-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-edit"></i></div>
        <div class="page-banner__text">
            <h2>Editar Sede</h2>
            <p>Institución: <strong><?php echo htmlspecialchars($institucion['nombre']); ?></strong></p>
        </div>
    </div>

    <!-- Formulario para editar sede -->
    <div class="form-section">
        <h3><i class="fas fa-building"></i> Información de la Sede</h3>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=procesar_sede" class="form-sede">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="id_institucion" value="<?php echo $institucion['id_institucion']; ?>">
            <input type="hidden" name="id_sede" value="<?php echo $sede['id_sede']; ?>">

            <div class="form-group">
                <label for="nombre"><i class="fas fa-signature"></i> Nombre de la Sede <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="input-modern" value="<?php echo htmlspecialchars($sede['nombre']); ?>" required minlength="3" maxlength="90">
                <small class="form-help">Máximo 90 caracteres</small>
            </div>

            <div class="form-group">
                <label for="direccion"><i class="fas fa-barcode"></i> Código DANE <span class="required">*</span></label>
                <input type="text" id="direccion" name="direccion" class="input-modern" value="<?php echo htmlspecialchars($sede['direccion'] ?? ''); ?>" required pattern="[0-9]{5,13}" minlength="5" maxlength="13" inputmode="numeric" title="Debe contener entre 5 y 13 dígitos numéricos">
                <small class="form-help">Entre 5 y 13 dígitos numéricos (Ej: 1234567890)</small>
            </div>

            <div class="form-group-checkbox">
                <input type="checkbox" id="activa" name="activa" value="1" <?php echo $sede['activa'] ? 'checked' : ''; ?>>
                <label for="activa"><i class="fas fa-toggle-on"></i> Sede Activa</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-modern btn-submit">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=gestionar_sedes&id=<?php echo $institucion['id_institucion']; ?>" class="btn-modern btn-cancel">
                    <i class="fas fa-times-circle"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
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

    .sede-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .sede-header {
        margin-bottom: 35px;
    }

    .sede-header h2 {
        font-size: 32px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sede-header h2 i {
        color: var(--primary-blue);
        font-size: 32px;
    }

    .header-subtitle {
        color: var(--gray-text);
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }

    .form-section {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
        border-left: 4px solid var(--primary-blue);
    }

    .form-section h3 {
        color: var(--dark-text);
        font-size: 18px;
        margin: 0 0 24px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section h3 i {
        color: var(--primary-blue);
    }

    .form-sede {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .form-group label i {
        color: var(--primary-blue);
        font-size: 14px;
    }

    .required {
        color: #E74C3C;
        font-weight: bold;
    }

    .input-modern {
        padding: 12px 15px;
        border: 2px solid var(--light-gray);
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        background-color: white;
        color: var(--dark-text);
        transition: all 0.3s ease;
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .form-help {
        font-size: 12px;
        color: var(--gray-text);
        margin-top: 4px;
    }

    .form-group-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: rgba(52, 152, 219, 0.05);
        border-radius: 6px;
    }

    .form-group-checkbox input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .form-group-checkbox label {
        margin: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .btn-modern {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex: 1;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
    }

    .btn-cancel {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
    }

    .btn-cancel:hover {
        background: var(--gray-text);
        color: white;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .form-actions {
            flex-direction: column;
        }

        .btn-modern {
            width: 100%;
        }

        .sede-header h2 {
            font-size: 24px;
        }
    }
</style>
