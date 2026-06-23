<!-- Recuperar Contraseña (Sin Autenticación) -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <h2><i class="fas fa-redo"></i> Recuperar Contraseña</h2>
        <p class="form-modern-subtitle">Ingrese su correo para recibir instrucciones</p>

        <?php if (!empty($exito)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=procesar_recuperar_contrasena" class="auth-form">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="tu@email.com" class="input-modern">
                <small><i class="fas fa-info-circle"></i> Enviaremos un enlace de recuperación a este correo</small>
            </div>

            <button type="submit" class="btn-modern btn-block"><i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación</button>
        </form>

        <div class="auth-links">
            <p><a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login">
                <i class="fas fa-arrow-left"></i> Volver al login
            </a></p>
        </div>
    </div>
</div>

<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
    }

    .form-modern-wrapper {
        width: 100%;
        max-width: 400px;
        padding: 20px;
    }

    .form-modern-card {
        background: white;
        border: 2px solid #3498DB;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(52, 152, 219, 0.3);
        padding: 40px;
    }

    .form-modern-card h2 {
        text-align: center;
        margin-bottom: 10px;
        color: #2C3E50;
        font-size: 24px;
    }

    .form-modern-subtitle {
        text-align: center;
        color: #7F8C8D;
        margin-bottom: 30px;
        font-size: 14px;
        font-weight: 500;
    }

    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 10px;
        color: #2C3E50;
        font-size: 15px;
    }

    .form-group small {
        color: #7F8C8D;
        font-size: 13px;
        margin-top: 8px;
        font-weight: 500;
    }

    .input-modern {
        padding: 15px;
        border: 2px solid #3498DB;
        background: #F8FBFC;
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        transition: all 0.3s;
    }

    .input-modern:focus {
        outline: none;
        border-color: #2980B9;
        background: #F0F8FF;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
    }

    .btn-modern {
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

    .btn-block {
        width: 100%;
    }

    .auth-links {
        text-align: center;
        margin-top: 25px;
        border-top: 2px solid #ECF0F1;
        padding-top: 20px;
    }

    .auth-links a {
        color: #3498DB;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .auth-links a:hover {
        color: #2980B9;
        transform: translateX(-3px);
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

    .alert-success {
        background-color: #D5F4E6;
        color: #186A3B;
        border: 2px solid #27AE60;
    }

    .alert-error {
        background-color: #FADBD8;
        color: #922B21;
        border: 2px solid #E74C3C;
    }
</style>
