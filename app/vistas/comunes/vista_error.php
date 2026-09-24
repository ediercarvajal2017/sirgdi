<?php
/**
 * Página de error. La pinta responder_error() (lib/errores.php).
 *
 * Es deliberadamente autosuficiente: todo el CSS va en línea y no carga ninguna
 * hoja externa. Si el error que estamos mostrando es precisamente que los
 * recursos no cargan, una página de error que dependa de ellos saldría en
 * blanco también — y entonces no habríamos arreglado nada.
 *
 * Variables que recibe: $codigo, $titulo, $mensaje, $referencia, $detalle,
 * $enlace_volver.
 */

$base = function_exists('config') ? (string) config('app.url_base') : '';
$hay_sesion = isset($_SESSION['id_usuario']);
$inicio = $base . '/?controlador=' . ($hay_sesion ? 'dashboard&accion=inicio' : 'autenticacion&accion=inicio');

// El icono y el color dicen de qué tipo de problema se trata antes de leer.
$codigo = (int) ($codigo ?? 500);
if ($codigo === 403) {
    $simbolo = '&#128274;';           // candado
    $tono    = 'aviso';
} elseif ($codigo === 404) {
    $simbolo = '&#128269;';           // lupa
    $tono    = 'neutro';
} elseif ($codigo >= 500) {
    $simbolo = '&#9888;&#65039;';     // triángulo de advertencia
    $tono    = 'error';
} else {
    $simbolo = '&#9432;';             // información
    $tono    = 'aviso';
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($titulo); ?></title>
<meta name="robots" content="noindex">
<script>(function(){try{var t=localStorage.getItem('sirgdi_tema');if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches)?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
<style>
    :root {
        --err-bg: #f4f6f9;
        --err-superficie: #ffffff;
        --err-texto: #16202c;
        --err-texto-suave: #55657a;
        --err-borde: #dfe5ec;
        --err-primario: #1b6ec2;
        --err-primario-texto: #ffffff;
        --err-error: #b5291b;
        --err-aviso: #8a5200;
        --err-sombra: 0 12px 40px rgba(16, 32, 52, .10);
    }
    :root[data-theme="dark"] {
        --err-bg: #0f1621;
        --err-superficie: #17202e;
        --err-texto: #e8eef6;
        --err-texto-suave: #a7b7ca;
        --err-borde: #263346;
        --err-primario: #4a9eff;
        --err-primario-texto: #0f1621;
        --err-error: #ff8a7a;
        --err-aviso: #ffb454;
        --err-sombra: 0 12px 40px rgba(0, 0, 0, .45);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { font-size: 16px; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        line-height: 1.6;
        background: var(--err-bg);
        color: var(--err-texto);
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
    }

    .err-tarjeta {
        background: var(--err-superficie);
        border: 1px solid var(--err-borde);
        border-radius: 16px;
        box-shadow: var(--err-sombra);
        max-width: 540px;
        width: 100%;
        padding: 40px 36px;
        text-align: center;
    }

    .err-simbolo { font-size: 48px; line-height: 1; margin-bottom: 18px; }

    .err-codigo {
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 20px;
        margin-bottom: 14px;
        border: 1px solid currentColor;
    }
    .err-codigo.error  { color: var(--err-error); }
    .err-codigo.aviso  { color: var(--err-aviso); }
    .err-codigo.neutro { color: var(--err-texto-suave); }

    .err-titulo {
        font-size: 25px;
        font-weight: 700;
        line-height: 1.25;
        margin-bottom: 12px;
        letter-spacing: -.01em;
    }

    .err-mensaje {
        font-size: 16px;
        color: var(--err-texto-suave);
        margin: 0 auto 28px;
        max-width: 42ch;
    }

    .err-acciones {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .err-boton {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid var(--err-borde);
        color: var(--err-texto);
        background: transparent;
        transition: background-color .18s ease, border-color .18s ease;
    }
    .err-boton:hover { background: var(--err-bg); }
    .err-boton:focus-visible {
        outline: 3px solid var(--err-primario);
        outline-offset: 2px;
    }
    .err-boton.primario {
        background: var(--err-primario);
        border-color: var(--err-primario);
        color: var(--err-primario-texto);
    }
    .err-boton.primario:hover { filter: brightness(1.07); }

    .err-referencia {
        margin-top: 26px;
        padding-top: 18px;
        border-top: 1px solid var(--err-borde);
        font-size: 13px;
        color: var(--err-texto-suave);
    }
    .err-referencia code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 13px;
        letter-spacing: .06em;
        background: var(--err-bg);
        border: 1px solid var(--err-borde);
        border-radius: 6px;
        padding: 2px 8px;
        user-select: all;
    }

    .err-detalle {
        margin-top: 22px;
        text-align: left;
        background: var(--err-bg);
        border: 1px solid var(--err-borde);
        border-left: 4px solid var(--err-error);
        border-radius: 8px;
        padding: 14px 16px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12.5px;
        line-height: 1.5;
        color: var(--err-texto);
        white-space: pre-wrap;
        word-break: break-word;
        overflow-x: auto;
    }
    .err-detalle strong {
        display: block;
        font-family: inherit;
        color: var(--err-error);
        margin-bottom: 6px;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: .08em;
    }

    @media (max-width: 480px) {
        .err-tarjeta { padding: 32px 22px; border-radius: 14px; }
        .err-titulo  { font-size: 22px; }
        .err-acciones { flex-direction: column; }
        .err-boton   { width: 100%; }
    }
</style>
</head>
<body>
    <main class="err-tarjeta" role="alert">
        <div class="err-simbolo" aria-hidden="true"><?php echo $simbolo; ?></div>

        <span class="err-codigo <?php echo $tono; ?>">Error <?php echo $codigo; ?></span>

        <h1 class="err-titulo"><?php echo htmlspecialchars($titulo); ?></h1>

        <p class="err-mensaje"><?php echo htmlspecialchars($mensaje); ?></p>

        <div class="err-acciones">
            <?php if (!empty($enlace_volver)): ?>
                <a class="err-boton" href="<?php echo htmlspecialchars($enlace_volver); ?>">Volver atrás</a>
            <?php endif; ?>
            <a class="err-boton primario" href="<?php echo htmlspecialchars($inicio); ?>">Ir al inicio</a>
        </div>

        <?php if (!empty($detalle)): ?>
            <div class="err-detalle">
                <strong>Detalle técnico (solo en desarrollo)</strong><?php echo htmlspecialchars($detalle); ?>
            </div>
        <?php endif; ?>

        <p class="err-referencia">
            Si necesitas ayuda, menciona esta referencia:
            <code><?php echo htmlspecialchars($referencia); ?></code>
        </p>
    </main>
</body>
</html>
