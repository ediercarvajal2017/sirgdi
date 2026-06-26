<!-- Nueva Contraseña (tras click en enlace de recuperación) -->
<div class="form-modern-wrapper">
    <div class="form-modern-card">
        <div class="login-logo">
            <img src="<?php echo config('app.url_base'); ?>/img/logo_sirgdi.png" alt="SIRGDI">
        </div>

        <h2><i class="fas fa-key"></i> Nueva Contraseña</h2>
        <p class="form-modern-subtitle">Ingresa tu nueva contraseña</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=procesar_restablecer_contrasena"
              class="auth-form" id="form-reset">

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="nueva_contrasena"><i class="fas fa-lock"></i> Nueva contraseña</label>
                <div class="input-con-icono">
                    <input type="password" id="nueva_contrasena" name="nueva_contrasena"
                           required minlength="8" class="input-modern"
                           placeholder="Mínimo 8 caracteres"
                           oninput="validarCoincidencia()">
                    <i class="fas fa-eye-slash toggle-pass" onclick="togglePass('nueva_contrasena', this)"></i>
                </div>
                <div class="password-strength" id="strength-bar">
                    <div class="strength-fill" id="strength-fill"></div>
                </div>
                <small id="strength-label"></small>
            </div>

            <div class="form-group">
                <label for="confirmar_contrasena"><i class="fas fa-lock"></i> Confirmar contraseña</label>
                <div class="input-con-icono">
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena"
                           required minlength="8" class="input-modern"
                           placeholder="Repite la contraseña"
                           oninput="validarCoincidencia()">
                    <i class="fas fa-eye-slash toggle-pass" onclick="togglePass('confirmar_contrasena', this)"></i>
                </div>
                <small id="match-msg"></small>
            </div>

            <button type="submit" class="btn-modern btn-block" id="btn-submit">
                <i class="fas fa-check-circle"></i> Guardar nueva contraseña
            </button>
        </form>

        <div class="auth-links">
            <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login">
                <i class="fas fa-arrow-left"></i> Volver al login
            </a>
        </div>
    </div>
</div>

<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
    }
    .form-modern-wrapper { width: 100%; max-width: 420px; padding: 20px; }
    .form-modern-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 12px 48px rgba(0,0,0,.18);
        padding: 40px;
    }
    .login-logo { text-align: center; margin-bottom: 18px; }
    .login-logo img { max-width: 200px; height: auto; border-radius: 12px; }
    .form-modern-card h2 {
        text-align: center;
        color: #111827;
        font-size: 22px;
        margin-bottom: 6px;
    }
    .form-modern-subtitle {
        text-align: center;
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 28px;
    }
    .auth-form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-weight: 600; color: #374151; font-size: 14px; }
    .input-con-icono { position: relative; }
    .input-modern {
        width: 100%;
        padding: 13px 44px 13px 14px;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
    }
    .input-modern:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.15);
    }
    .toggle-pass {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        cursor: pointer;
        font-size: 16px;
    }
    .password-strength {
        height: 4px;
        background: #e5e7eb;
        border-radius: 4px;
        margin-top: 6px;
        overflow: hidden;
    }
    .strength-fill {
        height: 100%;
        width: 0;
        border-radius: 4px;
        transition: width .3s, background .3s;
    }
    small { font-size: 12px; color: #6b7280; }
    #match-msg.ok  { color: #16a34a; }
    #match-msg.err { color: #dc2626; }
    .btn-modern {
        padding: 13px;
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: opacity .2s, transform .2s;
    }
    .btn-modern:hover { opacity: .9; transform: translateY(-1px); }
    .btn-modern:disabled { opacity: .5; cursor: not-allowed; transform: none; }
    .btn-block { width: 100%; }
    .auth-links { text-align: center; margin-top: 22px; border-top: 1px solid #e5e7eb; padding-top: 18px; }
    .auth-links a { color: #2563eb; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
    .auth-links a:hover { text-decoration: underline; }
    .alert { padding: 13px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
</style>

<script>
function togglePass(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
}

function validarCoincidencia() {
    const pass  = document.getElementById('nueva_contrasena').value;
    const conf  = document.getElementById('confirmar_contrasena').value;
    const msg   = document.getElementById('match-msg');
    const btn   = document.getElementById('btn-submit');
    const fill  = document.getElementById('strength-fill');
    const label = document.getElementById('strength-label');

    // Fuerza de contraseña
    let score = 0;
    if (pass.length >= 8)  score++;
    if (/[A-Z]/.test(pass)) score++;
    if (/[0-9]/.test(pass)) score++;
    if (/[^A-Za-z0-9]/.test(pass)) score++;

    const niveles = ['', '#ef4444', '#f97316', '#eab308', '#22c55e'];
    const textos  = ['', 'Débil', 'Regular', 'Buena', 'Fuerte'];
    fill.style.width     = (score * 25) + '%';
    fill.style.background = niveles[score] || '#e5e7eb';
    label.textContent    = textos[score] || '';

    // Coincidencia
    if (!conf) { msg.textContent = ''; btn.disabled = false; return; }
    if (pass === conf) {
        msg.textContent = '✓ Las contraseñas coinciden';
        msg.className   = 'ok';
        btn.disabled    = false;
    } else {
        msg.textContent = '✗ Las contraseñas no coinciden';
        msg.className   = 'err';
        btn.disabled    = true;
    }
}
</script>
