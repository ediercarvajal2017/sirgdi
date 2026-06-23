<!-- Cambiar Contraseña (Authenticated) -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <h2>Cambiar Contraseña</h2>

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
        background: white;
        border: 2px solid #3498DB;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.15);
    }

    .form-modern-card h2 {
        color: #2C3E50;
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
        color: #2C3E50;
        font-size: 15px;
    }

    .input-modern {
        width: 100%;
        padding: 15px;
        border: 2px solid #3498DB;
        background: #F8FBFC;
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        transition: all 0.3s;
        box-sizing: border-box;
    }

    .input-modern:focus {
        outline: none;
        border-color: #2980B9;
        background: #F0F8FF;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
    }

    .form-group small {
        display: block;
        color: #7F8C8D;
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
        background: #ECF0F1;
        color: #2C3E50;
        border: 2px solid #3498DB;
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
        background: #D5DBDB;
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
        background-color: #FADBD8;
        color: #922B21;
        border: 2px solid #E74C3C;
    }

    .alert-success {
        background-color: #D5F4E6;
        color: #186A3B;
        border: 2px solid #27AE60;
    }
</style>
