<!-- Cambiar Contraseña (Authenticated) -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <h2><?php echo !empty($obligatorio) ? 'Defina su contraseña' : 'Cambiar Contraseña'; ?></h2>

        <?php if (!empty($obligatorio)): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-shield-halved"></i>
                Su contraseña actual la creó un administrador y se la entregaron por
                fuera del sistema. Para continuar, elija una que solo conozca usted.
            </div>
        <?php endif; ?>

        <?php if (!empty($politica)): ?>
            <p class="form-help" style="margin:-6px 0 18px; font-size:13px;">
                <i class="fas fa-circle-info"></i> <?php echo htmlspecialchars($politica); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=procesar_cambiar_contrasena" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="contrasena_actual"><i class="fas fa-lock"></i> Contraseña Actual</label>
                <input type="password" id="contrasena_actual" name="contrasena_actual" required class="input-modern" placeholder="Ingrese su contraseña actual">
            </div>

            <div class="form-group">
                <label for="contrasena_nueva"><i class="fas fa-key"></i> Contraseña Nueva</label>
                <input type="password" id="contrasena_nueva" name="contrasena_nueva" required class="input-modern" placeholder="Ingrese la nueva contraseña">
                <small><i class="fas fa-info-circle"></i> Mínimo 8 caracteres, mayúscula, minúscula, número</small>
            </div>

            <div class="form-group">
                <label for="contrasena_confirmar"><i class="fas fa-check"></i> Confirmar Contraseña</label>
                <input type="password" id="contrasena_confirmar" name="contrasena_confirmar" required class="input-modern" placeholder="Confirme la nueva contraseña">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-modern"><i class="fas fa-save"></i> Cambiar Contraseña</button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=dashboard&accion=inicio" class="btn-modern-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>

<style>
    .form-modern-wrapper {
        max-width: 600px;
        margin: 40px auto;
        padding: 20px;
    }

    .form-modern-card {
        background: var(--color-bg-elevated);
        border: 2px solid var(--color-primary);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.15);
    }

    .form-modern-card h2 {
        color: var(--color-text);
        margin-bottom: 30px;
        font-size: 28px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--color-text);
        font-size: 15px;
    }

    .input-modern {
        width: 100%;
        padding: 15px;
        border: 2px solid var(--color-primary);
        background: var(--color-bg-subtle);
        color: var(--color-text);
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        transition: all 0.3s;
        box-sizing: border-box;
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--color-primary-dark);
        background: var(--color-bg-elevated);
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
    }

    .form-group small {
        display: block;
        color: var(--color-text-muted);
        font-size: 13px;
        margin-top: 8px;
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-modern {
        flex: 1;
        padding: 12px 28px;
        background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
    }

    .btn-modern-secondary {
        flex: 1;
        padding: 12px 28px;
        background: var(--color-bg-subtle);
        color: var(--color-text);
        border: 2px solid var(--color-primary);
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-modern-secondary:hover {
        background: var(--color-bg-hover);
        transform: translateY(-2px);
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-error {
        background-color: var(--color-danger-bg);
        color: var(--color-danger-text);
        border: 2px solid var(--color-danger);
    }

    .alert-success {
        background-color: var(--color-success-bg);
        color: var(--color-success-text);
        border: 2px solid var(--color-success);
    }
</style>
