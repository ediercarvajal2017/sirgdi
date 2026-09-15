<!-- RF-01: Login Form - Mismos estilos que "Cambiar Contraseña" -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <div class="login-logo">
            <img src="<?php echo config('app.url_base'); ?>/img/logo_sirgdi.png" alt="SIRGDI">
        </div>

        <h2><?php echo config('app.app_name'); ?></h2>

        <?php if (!empty($exito)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=procesar_login" class="form" id="form-login">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" required class="input-modern"
                       placeholder="Ingrese su correo electrónico" autocomplete="email">
                <span class="field-error" data-field="email"></span>
            </div>

            <div class="form-group">
                <label for="contrasena"><i class="fas fa-lock"></i> Contraseña</label>
                <div class="input-con-icono">
                    <input type="password" id="contrasena" name="contrasena" required class="input-modern"
                           placeholder="Ingrese su contraseña" autocomplete="current-password">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword()"></i>
                </div>
                <span class="field-error" data-field="contrasena"></span>
            </div>

            <div class="auth-options">
                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    Recuérdame
                </label>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=recuperar_contrasena" class="forgot-password">
                    ¿Olvidó su contraseña?
                </a>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-modern"><i class="fas fa-sign-in-alt"></i> Ingresar</button>
            </div>
        </form>
    </div>
</div>

<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: var(--color-bg);
        margin: 0;
        padding: 20px;
    }

    .form-modern-wrapper {
        max-width: 480px;
        width: 100%;
        margin: 0 auto;
        padding: 20px;
    }

    .form-modern-card {
        background: var(--color-bg-elevated);
        border: 2px solid var(--color-primary);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.15);
    }

    .login-logo {
        text-align: center;
        margin-bottom: 20px;
    }
    .login-logo img {
        max-width: 220px;
        height: auto;
        border-radius: 14px;
        display: inline-block;
    }

    .form-modern-card h2 {
        color: var(--color-text);
        margin-bottom: 30px;
        font-size: 26px;
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

    .input-modern::placeholder {
        color: var(--color-text-muted);
    }

    /* Campo con ícono mostrar/ocultar contraseña */
    .input-con-icono {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-con-icono .input-modern {
        padding-right: 48px;
    }

    .toggle-password {
        position: absolute;
        right: 16px;
        color: var(--color-primary);
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
    }

    .toggle-password:hover {
        transform: scale(1.1);
    }

    .field-error {
        color: var(--color-danger);
        font-size: 13px;
        margin-top: 8px;
        display: none;
        font-weight: 500;
    }

    .auth-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 15px;
        flex-wrap: wrap;
    }

    .remember-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
        color: var(--color-primary);
        font-weight: 600;
        font-size: 14px;
    }

    .remember-checkbox input {
        display: none;
    }

    .checkmark {
        display: inline-flex;
        width: 20px;
        height: 20px;
        border: 2px solid var(--color-primary);
        border-radius: 4px;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        background: var(--color-bg-elevated);
    }

    .remember-checkbox input:checked + .checkmark {
        background: var(--color-primary);
        color: white;
    }

    .remember-checkbox input:checked + .checkmark::after {
        content: "✓";
        font-size: 14px;
        font-weight: bold;
    }

    .forgot-password {
        color: var(--color-primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
    }

    .forgot-password:hover {
        text-decoration: underline;
        color: var(--color-primary-dark);
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-modern {
        flex: 1;
        padding: 14px 28px;
        background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.5px;
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
        background-color: var(--color-success-bg);
        color: var(--color-success-text);
        border: 2px solid var(--color-success);
    }

    .alert-error {
        background-color: var(--color-danger-bg);
        color: var(--color-danger-text);
        border: 2px solid var(--color-danger);
    }

    @media (max-width: 480px) {
        .form-modern-card { padding: 30px 22px; }
        .form-modern-card h2 { font-size: 22px; }
        .auth-options { flex-direction: column; align-items: flex-start; }
    }
</style>

<script>
    function togglePassword() {
        const input = document.getElementById('contrasena');
        const icon = document.querySelector('.toggle-password');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    document.getElementById('form-login').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const contrasena = document.getElementById('contrasena').value;
        let hasError = false;

        document.querySelectorAll('.field-error').forEach(el => el.style.display = 'none');

        if (!email || !email.includes('@')) {
            document.querySelector('[data-field="email"]').textContent = 'Correo electrónico inválido';
            document.querySelector('[data-field="email"]').style.display = 'block';
            hasError = true;
        }

        if (!contrasena || contrasena.length < 6) {
            document.querySelector('[data-field="contrasena"]').textContent = 'Contraseña requerida (mín 6 caracteres)';
            document.querySelector('[data-field="contrasena"]').style.display = 'block';
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });
</script>
