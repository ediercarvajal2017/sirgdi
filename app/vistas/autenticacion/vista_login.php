<!-- RF-01: Login Form - Mismos estilos que "Cambiar Contraseña" -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <div class="login-logo">
            <i class="fas fa-user-circle"></i>
        </div>

        <h2><?php echo config('app.app_name'); ?></h2>

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
        background: linear-gradient(135deg, #E8F4F8 0%, #D6EAF8 100%);
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
        background: white;
        border: 2px solid #3498DB;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.15);
    }

    .login-logo {
        text-align: center;
        margin-bottom: 15px;
    }

    .login-logo i {
        font-size: 64px;
        color: #3498DB;
        background: linear-gradient(135deg, #D6EAF8 0%, #E8F4F8 100%);
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .form-modern-card h2 {
        color: #2C3E50;
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

    .input-modern::placeholder {
        color: #95A5A6;
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
        color: #3498DB;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
    }

    .toggle-password:hover {
        transform: scale(1.1);
    }

    .field-error {
        color: #E74C3C;
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
        color: #3498DB;
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
        border: 2px solid #3498DB;
        border-radius: 4px;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        background: white;
    }

    .remember-checkbox input:checked + .checkmark {
        background: #3498DB;
        color: white;
    }

    .remember-checkbox input:checked + .checkmark::after {
        content: "✓";
        font-size: 14px;
        font-weight: bold;
    }

    .forgot-password {
        color: #3498DB;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
    }

    .forgot-password:hover {
        text-decoration: underline;
        color: #2980B9;
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

    .alert-error {
        background-color: #FADBD8;
        color: #922B21;
        border: 2px solid #E74C3C;
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
