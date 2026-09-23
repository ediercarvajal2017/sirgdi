<?php
/* Seguridad de la cuenta. Variables: $csrf_token, $dos_factores_activo,
   $correo, $secreto_pendiente, $error, $exito */
$base = config('app.url_base');
$app  = config('app.app_name');

// URI estándar para apps de autenticación (Google Authenticator, Authy, 1Password…).
// Si el usuario abre esta página desde el mismo teléfono donde tiene la app,
// el enlace la configura de un toque, sin teclear nada.
$uri_otpauth = '';
if (!empty($secreto_pendiente)) {
    $uri_otpauth = 'otpauth://totp/' . rawurlencode($app . ':' . $correo)
        . '?secret=' . rawurlencode($secreto_pendiente)
        . '&issuer=' . rawurlencode($app)
        . '&algorithm=SHA1&digits=6&period=30';
}

/** Secreto en grupos de 4 para poder dictarlo o teclearlo sin perderse. */
function seguridad_agrupar($secreto) {
    return trim(implode(' ', str_split($secreto, 4)));
}
?>
<div class="container seguridad-container">

    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-shield-halved"></i></div>
        <div class="page-banner__text">
            <h2>Seguridad de la cuenta</h2>
            <p>Verificación en dos pasos y acceso a <?php echo htmlspecialchars($correo); ?>.</p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($exito)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?>
        </div>
    <?php endif; ?>

    <div class="tarjeta-seguridad">
        <div class="tarjeta-seguridad__cabecera">
            <div>
                <h3>Verificación en dos pasos</h3>
                <p>
                    Además de la contraseña, al entrar se pide un código de 6 dígitos
                    que cambia cada 30 segundos en tu teléfono. Si alguien consigue tu
                    contraseña, aún así no puede entrar.
                </p>
            </div>
            <span class="estado-2fa <?php echo $dos_factores_activo ? 'estado-2fa--activo' : 'estado-2fa--inactivo'; ?>">
                <i class="fas <?php echo $dos_factores_activo ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                <?php echo $dos_factores_activo ? 'Activada' : 'Desactivada'; ?>
            </span>
        </div>

        <?php if ($dos_factores_activo): ?>

            <form method="POST" action="<?php echo $base; ?>/?controlador=autenticacion&accion=desactivar_2fa"
                  class="bloque-2fa"
                  onsubmit="return confirm('Al desactivarla, tu cuenta quedará protegida solo por la contraseña. ¿Continuar?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <p class="nota-2fa">
                    Para desactivarla necesitamos tu contraseña: así, quien tomara una
                    sesión tuya abierta no podría quitar la protección.
                </p>

                <div class="form-group">
                    <label for="contrasena_actual">Tu contraseña</label>
                    <input type="password" id="contrasena_actual" name="contrasena_actual"
                           required class="input-modern" autocomplete="current-password">
                </div>

                <button type="submit" class="btn-peligro">
                    <i class="fas fa-shield-slash"></i> Desactivar verificación en dos pasos
                </button>
            </form>

        <?php elseif (!empty($secreto_pendiente)): ?>

            <div class="bloque-2fa">
                <ol class="pasos-2fa">
                    <li>
                        <strong>Instala una app de autenticación</strong> en tu teléfono si no
                        tienes una: Google Authenticator, Microsoft Authenticator o Authy.
                    </li>
                    <li>
                        <strong>Añade esta cuenta.</strong> En la app elige «Introducir clave
                        de configuración» y escribe:
                        <div class="secreto-2fa">
                            <code id="secreto2fa"><?php echo htmlspecialchars(seguridad_agrupar($secreto_pendiente)); ?></code>
                            <button type="button" class="btn-copiar" onclick="copiarSecreto()">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                        <p class="nota-2fa">
                            Si estás leyendo esto en el mismo teléfono donde tienes la app,
                            <a href="<?php echo htmlspecialchars($uri_otpauth); ?>">tócalo aquí</a>
                            y se configura sola.
                        </p>
                    </li>
                    <li>
                        <strong>Escribe el código que aparece en la app</strong> para confirmar
                        que quedó bien configurada. Hasta que no lo hagas, la verificación
                        sigue desactivada y puedes entrar con tu contraseña como siempre.
                    </li>
                </ol>

                <form method="POST" action="<?php echo $base; ?>/?controlador=autenticacion&accion=confirmar_2fa">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="codigo">Código de 6 dígitos</label>
                        <input type="text" id="codigo" name="codigo" required
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               autocomplete="one-time-code" placeholder="000000"
                               class="input-modern input-codigo">
                    </div>

                    <button type="submit" class="btn-modern">
                        <i class="fas fa-check"></i> Activar
                    </button>
                </form>
            </div>

        <?php else: ?>

            <form method="POST" action="<?php echo $base; ?>/?controlador=autenticacion&accion=preparar_2fa"
                  class="bloque-2fa">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="btn-modern">
                    <i class="fas fa-mobile-screen"></i> Activar verificación en dos pasos
                </button>
            </form>

        <?php endif; ?>
    </div>

    <div class="tarjeta-seguridad">
        <div class="tarjeta-seguridad__cabecera">
            <div>
                <h3>Contraseña</h3>
                <p>Cámbiala si crees que alguien más puede conocerla. Al hacerlo se cierran
                   todas tus otras sesiones abiertas.</p>
            </div>
        </div>
        <div class="bloque-2fa">
            <a href="<?php echo $base; ?>/?controlador=autenticacion&accion=cambiar_contrasena"
               class="btn-modern btn-secundario">
                <i class="fas fa-key"></i> Cambiar contraseña
            </a>
        </div>
    </div>
</div>

<script>
function copiarSecreto() {
    var texto = (document.getElementById('secreto2fa').textContent || '').replace(/\s/g, '');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(function () {
            if (window.toast) toast.success('Copiado', 'Clave copiada al portapapeles');
        });
    }
}
</script>

<style>
.seguridad-container { max-width: 760px; margin: 30px auto; padding: 20px; }

.tarjeta-seguridad {
    background: var(--color-bg-elevated);
    border: 1px solid var(--color-border-subtle);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    margin-bottom: 20px;
    overflow: hidden;
}

.tarjeta-seguridad__cabecera {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--color-border-subtle);
}

.tarjeta-seguridad__cabecera h3 { margin: 0 0 6px; font-size: 16px; color: var(--color-text); }
.tarjeta-seguridad__cabecera p { margin: 0; font-size: 13.5px; line-height: 1.5; color: var(--color-text-muted); }

.estado-2fa {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
}
.estado-2fa--activo   { background: var(--color-success-bg, #e8f8f0); color: var(--color-success, #1e8e5a); }
.estado-2fa--inactivo { background: var(--color-bg-subtle); color: var(--color-text-muted); }

.bloque-2fa { padding: 18px 20px; }

.pasos-2fa { margin: 0 0 20px; padding-left: 20px; }
.pasos-2fa li { margin-bottom: 16px; font-size: 14px; line-height: 1.6; color: var(--color-text); }

.nota-2fa { margin: 8px 0 0; font-size: 13px; line-height: 1.5; color: var(--color-text-muted); }

.secreto-2fa {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin: 10px 0 0;
    padding: 12px 14px;
    background: var(--color-bg-subtle);
    border-radius: 8px;
}

.secreto-2fa code {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 16px;
    letter-spacing: 1px;
    color: var(--color-text);
    word-break: break-all;
}

.btn-copiar {
    background: none;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12.5px;
    font-family: inherit;
    color: var(--color-text-muted);
    cursor: pointer;
    min-height: 34px;
}

.input-codigo {
    max-width: 180px;
    font-size: 22px;
    letter-spacing: 6px;
    text-align: center;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.btn-modern, .btn-peligro {
    padding: 12px 22px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    text-decoration: none;
    min-height: 44px;
}

.btn-modern   { background: linear-gradient(135deg, var(--accent-solid), var(--accent-solid-hover)); }
.btn-secundario { background: var(--color-bg-subtle); color: var(--color-text); border: 1px solid var(--color-border); }
.btn-peligro  { background: var(--color-danger, #e74c3c); }

@media (max-width: 768px) {
    .tarjeta-seguridad__cabecera { flex-direction: column; }
    .btn-modern, .btn-peligro { width: 100%; justify-content: center; }
    .secreto-2fa code { font-size: 15px; }
}
</style>
