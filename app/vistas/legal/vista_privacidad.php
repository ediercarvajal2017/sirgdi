<?php
/**
 * Política de tratamiento de datos personales (Ley 1581 de 2012).
 *
 * Variables: $institucion (puede ser null), $correo_contacto, $id_institucion.
 *
 * El texto está escrito para que lo entienda un ciudadano, no un abogado. Un
 * aviso que nadie lee no informa, y una autorización que no informa no es
 * válida, que es exactamente lo que la ley pide evitar.
 */
$base = config('app.url_base');
$nombre_responsable = $institucion['nombre'] ?? 'la institución que recibe el reporte';
$volver = $id_institucion
    ? $base . '/?controlador=reportes&accion=crear_invitado&inst=' . (int) $id_institucion
    : $base . '/';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($titulo); ?></title>
<link rel="icon" type="image/png" sizes="64x64" href="<?php echo asset_url('img/favicon.png'); ?>">
<script>(function(){try{var t=localStorage.getItem('sirgdi_tema');if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches)?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
<style>
    :root {
        --lg-bg: #f4f6f9; --lg-superficie: #ffffff; --lg-texto: #16202c;
        --lg-suave: #55657a; --lg-borde: #dfe5ec; --lg-primario: #1b6ec2;
        --lg-acento-bg: #eef4fc;
    }
    :root[data-theme="dark"] {
        --lg-bg: #0f1621; --lg-superficie: #17202e; --lg-texto: #e8eef6;
        --lg-suave: #a7b7ca; --lg-borde: #263346; --lg-primario: #4a9eff;
        --lg-acento-bg: #16283f;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { font-size: 16px; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        line-height: 1.7; background: var(--lg-bg); color: var(--lg-texto);
        padding: 32px 16px;
    }
    .lg-hoja {
        max-width: 760px; margin: 0 auto; background: var(--lg-superficie);
        border: 1px solid var(--lg-borde); border-radius: 16px; padding: 44px 48px;
    }
    .lg-volver {
        display: inline-block; margin-bottom: 26px; color: var(--lg-primario);
        text-decoration: none; font-weight: 600; font-size: 15px;
    }
    .lg-volver:hover { text-decoration: underline; }
    h1 { font-size: 28px; line-height: 1.25; margin-bottom: 8px; letter-spacing: -.02em; }
    .lg-sub { color: var(--lg-suave); font-size: 15px; margin-bottom: 30px; }
    h2 {
        font-size: 18px; margin: 30px 0 10px; padding-top: 22px;
        border-top: 1px solid var(--lg-borde);
    }
    h2:first-of-type { border-top: none; padding-top: 0; }
    p, li { font-size: 15.5px; color: var(--lg-texto); }
    p { margin-bottom: 12px; }
    ul { margin: 0 0 14px 22px; }
    li { margin-bottom: 7px; }
    .lg-destacado {
        background: var(--lg-acento-bg); border-left: 4px solid var(--lg-primario);
        border-radius: 8px; padding: 16px 20px; margin: 18px 0;
    }
    .lg-destacado p:last-child { margin-bottom: 0; }
    .lg-correo { font-weight: 600; color: var(--lg-primario); word-break: break-all; }
    .lg-pie {
        margin-top: 34px; padding-top: 20px; border-top: 1px solid var(--lg-borde);
        font-size: 13.5px; color: var(--lg-suave);
    }
    @media (max-width: 600px) {
        body { padding: 16px; }
        .lg-hoja { padding: 28px 22px; border-radius: 14px; }
        h1 { font-size: 23px; }
    }
</style>
</head>
<body>
<main class="lg-hoja">
    <a class="lg-volver" href="<?php echo htmlspecialchars($volver); ?>">&larr; Volver</a>

    <h1>Qué hacemos con sus datos personales</h1>
    <p class="lg-sub">
        Aviso de tratamiento de datos personales, conforme a la Ley 1581 de 2012
        y el Decreto 1377 de 2013 de Colombia.
    </p>

    <h2>Quién responde por sus datos</h2>
    <p>
        El responsable del tratamiento es
        <strong><?php echo htmlspecialchars($nombre_responsable); ?></strong>,
        que es quien recibe y atiende su reporte.
    </p>
    <p>
        <?php echo htmlspecialchars(config('app.app_name')); ?> es la plataforma
        que la institución usa para gestionarlos; actúa como encargado del
        tratamiento y no dispone de sus datos para fines propios.
    </p>

    <h2>Qué datos recogemos</h2>
    <ul>
        <li>Su nombre y apellidos.</li>
        <li>Su correo electrónico y teléfono, <strong>solo si decide darlos</strong>: son opcionales.</li>
        <li>Lo que escriba en la descripción del daño, y las fotos o videos que adjunte.</li>
        <li>La dirección IP desde la que envía el formulario, y la fecha y hora.</li>
    </ul>
    <div class="lg-destacado">
        <p>
            Las fotos y videos se guardan tal cual los envía. Si en la imagen aparecen
            personas, o documentos con datos de alguien, esos datos también quedan
            guardados. Le recomendamos fotografiar solo el daño.
        </p>
    </div>

    <h2>Para qué los usamos</h2>
    <ul>
        <li>Atender el reporte y hacerle seguimiento hasta cerrarlo.</li>
        <li>Contactarle si hace falta aclarar algo, o avisarle de cómo va.</li>
        <li>Pedirle una calificación del servicio cuando el reporte se cierre.</li>
        <li>Elaborar estadísticas internas de mantenimiento. Para esto no se usa su nombre.</li>
    </ul>
    <p>
        La IP se guarda por seguridad: sirve para frenar envíos masivos automáticos
        y para dejar constancia de quién autorizó el tratamiento.
    </p>

    <h2>Con quién se comparten</h2>
    <p>
        Con nadie fuera de la institución. Dentro de ella, acceden a su reporte las
        personas que tienen que atenderlo: quien lo clasifica, el técnico asignado y
        quien lo cierra. No se venden, no se ceden y no se usan para publicidad.
    </p>

    <h2>Cuánto tiempo se conservan</h2>
    <p>
        Mientras el reporte sea útil para la institución como historial de
        mantenimiento del lugar. Si quiere que se eliminen antes, puede pedirlo.
    </p>

    <h2>Sus derechos</h2>
    <p>La Ley 1581 le reconoce, entre otros, el derecho a:</p>
    <ul>
        <li>Conocer qué datos suyos tenemos y de dónde salieron.</li>
        <li>Pedir que se corrijan si están equivocados o incompletos.</li>
        <li>Pedir que se eliminen, salvo que exista un deber legal de conservarlos.</li>
        <li>Retirar la autorización que dio al enviar el reporte.</li>
        <li>Presentar una queja ante la Superintendencia de Industria y Comercio.</li>
    </ul>

    <h2>Cómo ejercerlos</h2>
    <div class="lg-destacado">
        <p>
            Escriba a <span class="lg-correo"><?php echo htmlspecialchars($correo_contacto); ?></span>
            indicando su nombre, el número de ticket de su reporte y qué necesita.
        </p>
        <p>
            Le responderemos dentro de los plazos que fija la ley: hasta diez días
            hábiles para una consulta y hasta quince para un reclamo.
        </p>
    </div>

    <h2>Si no quiere dar sus datos</h2>
    <p>
        El correo y el teléfono son opcionales. Puede enviar el reporte sin ellos; lo
        único que pierde es la posibilidad de que le avisemos por correo, aunque podrá
        seguir consultando su estado con el enlace de seguimiento que aparece al
        terminar el envío.
    </p>

    <p class="lg-pie">
        Este aviso se muestra antes de enviar el formulario para que la autorización
        sea informada, como exige la ley. La institución conserva constancia de la
        fecha y hora en que usted la otorgó.
    </p>
</main>
</body>
</html>
