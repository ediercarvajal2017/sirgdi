<!-- RF-02: 2FA Validation - Estilo Moderno -->
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="<?php echo config('app.url_base'); ?>/img/logo_sirgdi.png" alt="SIRGDI">
        </div>

        <h2>Autenticación de Dos Factores</h2>
        <p class="auth-subtitle">Ingrese el código de 6 dígitos de su aplicador</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=procesar_2fa" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group-modern">
                <i class="fas fa-lock icon-input"></i>
                <input type="text" id="codigo_2fa" name="codigo_2fa" required placeholder="000000"
                       maxlength="6" class="input-modern input-2fa" inputmode="numeric" autofocus>
            </div>

            <p class="auth-help"><i class="fas fa-info-circle"></i> Ingrese los 6 dígitos de su aplicador (Google Authenticator, Authy, etc.)</p>

            <button type="submit" class="btn-login">VALIDAR</button>
        </form>

        <div class="auth-links">
            <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login">
                <i class="fas fa-arrow-left"></i> Volver al login
            </a>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --light-blue: #D6EAF8;
        --very-light-blue: #E8F4F8;
    }

    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: linear-gradient(135deg, var(--very-light-blue) 0%, var(--light-blue) 100%);
        margin: 0;
        padding: 20px;
    }

    .auth-container {
        width: 100%;
        max-width: 450px;
    }

    .auth-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.15);
        padding: 50px 40px;
        text-align: center;
    }

    .auth-logo {
        margin-bottom: 25px;
        text-align: center;
    }
    .auth-logo img {
        max-width: 210px;
        height: auto;
        border-radius: 14px;
        display: inline-block;
    }

    .auth-card h2 {
        color: #2C3E50;
        font-size: 28px;
        margin: 20px 0 0;
        font-weight: 700;
    }

    .auth-subtitle {
        color: #95A5A6;
        margin-bottom: 30px;
        font-size: 14px;
    }

    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 35px;
    }

    .form-group-modern {
        position: relative;
        display: flex;
        align-items: center;
    }

    .icon-input {
        position: absolute;
        left: 18px;
        color: var(--primary-blue);
        font-size: 18px;
        pointer-events: none;
    }

    .input-modern {
        width: 100%;
        padding: 15px 18px 15px 50px;
        border: 2px solid var(--primary-blue);
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        transition: all 0.3s ease;
        background-color: #F8FBFC;
    }

    .input-2fa {
        font-size: 24px;
        text-align: center;
        letter-spacing: 8px;
        font-weight: bold;
        padding-left: 18px;
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--primary-blue);
        background-color: white;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .auth-help {
        color: #95A5A6;
        font-size: 12px;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-login {
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary-blue) 0%, #2980B9 100%);
        color: white;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 10px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
    }

    .auth-links {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #BDC3C7;
    }

    .auth-links a {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
    }

    .auth-links a:hover {
        text-decoration: underline;
        gap: 10px;
    }

    .alert {
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-error {
        background-color: #FADBD8;
        color: #78281F;
        border-left: 4px solid #E74C3C;
    }

    .alert i {
        font-size: 18px;
    }

    @media (max-width: 480px) {
        .auth-card {
            padding: 35px 20px;
        }

        .auth-logo img {
            max-width: 180px;
        }

        .auth-card h2 {
            font-size: 24px;
        }
    }
</style>

<script>
    document.getElementById('codigo_2fa').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
</script>
