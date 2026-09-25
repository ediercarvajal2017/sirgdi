<?php
/**
 * Ayuda dentro de la aplicación, filtrada por rol.
 *
 * Variables: $roles, $es_tecnico, $es_gestor, $es_admin, $es_superadmin,
 * $correo_soporte.
 *
 * El contenido describe lo que el sistema hace de verdad. Una ayuda que
 * describe algo que no existe es peor que no tener ayuda: manda al usuario a
 * buscar un botón que no está.
 */
$base = config('app.url_base');
?>

<style>
    .ay-wrap { max-width: 900px; margin: 0 auto; padding: 28px 16px 60px; }
    .ay-titulo { font-size: 27px; font-weight: 700; letter-spacing: -.02em; margin-bottom: 6px; }
    .ay-intro { color: var(--color-text-muted); margin-bottom: 26px; font-size: 15.5px; }

    .ay-indice {
        display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 30px;
        padding-bottom: 22px; border-bottom: 1px solid var(--color-border-subtle);
    }
    .ay-indice a {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 15px; min-height: 42px;
        border: 1px solid var(--color-border); border-radius: 22px;
        color: var(--color-text); text-decoration: none;
        font-size: 14px; font-weight: 600;
        transition: background-color .15s ease, border-color .15s ease;
    }
    .ay-indice a:hover { background: var(--color-bg-hover); border-color: var(--color-primary); }
    .ay-indice a:focus-visible { outline: 3px solid var(--color-primary); outline-offset: 2px; }

    .ay-seccion { margin-bottom: 38px; scroll-margin-top: 90px; }
    .ay-seccion h2 {
        font-size: 20px; font-weight: 700; margin-bottom: 14px;
        display: flex; align-items: center; gap: 10px;
    }
    .ay-seccion h2 i { color: var(--color-primary); font-size: 18px; }
    .ay-seccion h3 { font-size: 16px; font-weight: 700; margin: 20px 0 8px; }
    .ay-seccion p  { margin-bottom: 12px; font-size: 15.5px; line-height: 1.7; }
    .ay-seccion ul, .ay-seccion ol { margin: 0 0 14px 22px; }
    .ay-seccion li { margin-bottom: 7px; font-size: 15.5px; line-height: 1.7; }

    .ay-nota {
        background: var(--color-bg-subtle, rgba(127,143,166,.09));
        border-left: 4px solid var(--color-primary);
        border-radius: 8px; padding: 14px 18px; margin: 16px 0;
    }
    .ay-nota p:last-child { margin-bottom: 0; }

    .ay-estados { width: 100%; border-collapse: collapse; margin: 14px 0 18px; font-size: 14.5px; }
    .ay-estados th, .ay-estados td {
        text-align: left; padding: 10px 12px;
        border-bottom: 1px solid var(--color-border-subtle);
        vertical-align: top;
    }
    .ay-estados th { font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-muted); }
    .ay-estados td:first-child { font-weight: 600; white-space: nowrap; }

    .ay-soporte {
        background: var(--color-bg-elevated, rgba(127,143,166,.08));
        border: 1px solid var(--color-border); border-radius: 14px;
        padding: 24px 26px; margin-top: 34px;
    }
    .ay-soporte h2 { margin-bottom: 10px; }
    .ay-correo { font-weight: 700; color: var(--color-primary); word-break: break-all; }

    @media (max-width: 640px) {
        .ay-wrap { padding: 20px 16px 48px; }
        .ay-titulo { font-size: 23px; }
        .ay-estados { font-size: 13.5px; }
        .ay-estados th:nth-child(2), .ay-estados td:nth-child(2) { display: none; }
    }
</style>

<div class="ay-wrap">

    <h1 class="ay-titulo">Ayuda</h1>
    <p class="ay-intro">
        Cómo funciona el sistema y qué puede hacer usted con los permisos que tiene.
    </p>

    <nav class="ay-indice">
        <a href="#ciclo"><i class="fas fa-route"></i> El recorrido de un reporte</a>
        <a href="#avisos"><i class="fas fa-bell"></i> Avisos</a>
        <?php if ($es_tecnico): ?><a href="#tecnico"><i class="fas fa-screwdriver-wrench"></i> Si es técnico</a><?php endif; ?>
        <?php if ($es_gestor): ?><a href="#gestor"><i class="fas fa-clipboard-check"></i> Si gestiona reportes</a><?php endif; ?>
        <?php if ($es_admin): ?><a href="#admin"><i class="fas fa-gear"></i> Si administra la institución</a><?php endif; ?>
        <?php if ($es_superadmin): ?><a href="#superadmin"><i class="fas fa-building"></i> Administración global</a><?php endif; ?>
        <a href="#cuenta"><i class="fas fa-user-shield"></i> Su cuenta</a>
        <a href="#soporte"><i class="fas fa-life-ring"></i> Soporte</a>
    </nav>

    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="ciclo">
        <h2><i class="fas fa-route"></i> El recorrido de un reporte</h2>
        <p>
            Un reporte pasa por una serie de estados. Solo se puede ir de un estado a
            otro por los caminos previstos: el sistema no deja saltarse pasos, y por eso
            a veces una opción no aparece.
        </p>
        <table class="ay-estados">
            <thead>
                <tr><th>Estado</th><th>Qué significa</th><th>A dónde puede ir</th></tr>
            </thead>
            <tbody>
                <tr><td>Registrado</td><td>Entró y nadie lo ha tomado todavía.</td><td>En Proceso o Anulado</td></tr>
                <tr><td>En Proceso</td><td>Tiene técnico asignado y está en trabajo.</td><td>Solucionado, Devuelto o Anulado</td></tr>
                <tr><td>Solucionado</td><td>El técnico terminó y espera revisión.</td><td>En Validación o Devuelto</td></tr>
                <tr><td>En Validación</td><td>Aprobado, pendiente del cierre formal.</td><td>Cerrado o Devuelto</td></tr>
                <tr><td>Devuelto</td><td>La solución no convenció y vuelve al técnico.</td><td>En Proceso</td></tr>
                <tr><td>Cerrado</td><td>Terminado. No admite más cambios.</td><td>—</td></tr>
                <tr><td>Anulado</td><td>Descartado (spam, duplicado). No se atiende.</td><td>—</td></tr>
            </tbody>
        </table>
        <div class="ay-nota">
            <p>
                <strong>Asignar un reporte lo pasa directamente a "En Proceso".</strong>
                No hay un paso intermedio de "Asignado": en cuanto tiene técnico, se
                considera que el trabajo empezó y el reloj del tiempo de atención corre.
            </p>
        </div>

        <h3>El tiempo de atención (SLA)</h3>
        <p>
            Cada categoría y nivel de urgencia tiene un plazo. Cuando un reporte se
            acerca a ese plazo o lo supera, el sistema avisa por correo a quienes
            gestionan, una vez por cada aviso, sin repetirlo cada día.
        </p>
        <p>
            El plazo se cuenta en <strong>horas hábiles</strong>: solo dentro del horario
            laboral de la institución, y sin contar domingos ni festivos de Colombia. Un
            reporte que llega el sábado por la noche empieza a contar el lunes a primera
            hora. El horario por defecto es de lunes a viernes de 7:00 a 17:00 y el sábado
            de 7:00 a 13:00; quien configura el SLA puede cambiarlo en
            <em>Configuración → Horario laboral</em>.
        </p>
        <p>
            Devolver un reporte pausa ese reloj, y retomarlo lo reanuda: al técnico no
            se le cuenta el tiempo que el reporte estuvo esperando decisión de otro.
        </p>
    </section>

    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="avisos">
        <h2><i class="fas fa-bell"></i> Avisos</h2>
        <p>
            La campana de la barra superior muestra lo que le corresponde a usted. Es
            independiente del correo a propósito: <strong>si el correo falla, el aviso
            sigue estando ahí</strong>. Si ve trabajo nuevo en la campana pero no le
            llegó ningún correo, avísele a soporte, porque el problema es el envío.
        </p>
        <p>
            Al pulsar un aviso se marca como leído y se abre el reporte al que se
            refiere. Los avisos que llevan más de 48 horas sin poder entregarse se
            descartan en vez de acumularse: recibir de golpe una decena de correos
            viejos confunde más de lo que ayuda.
        </p>
    </section>

    <?php if ($es_tecnico): ?>
    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="tecnico">
        <h2><i class="fas fa-screwdriver-wrench"></i> Si es técnico</h2>
        <p>
            En <strong>Mis asignaciones</strong> están los reportes que le tocan. Al
            abrir uno entra en su hoja de trabajo, y ese momento queda registrado como
            el inicio de la intervención.
        </p>
        <ol>
            <li>Escriba qué encontró y qué hizo en el informe.</li>
            <li>Suba evidencia fotográfica del trabajo terminado.</li>
            <li>Marque el reporte como solucionado.</li>
        </ol>
        <div class="ay-nota">
            <p>
                Si no aparece el botón de marcar como solucionado, es porque falta algo
                del informe. La pantalla le indica qué campos quedan.
            </p>
        </div>
        <p>
            Si le devuelven un reporte, vuelve a su lista con el motivo. Al abrirlo de
            nuevo se reactiva solo y puede volver a marcarlo como solucionado cuando
            corrija.
        </p>
    </section>
    <?php endif; ?>

    <?php if ($es_gestor): ?>
    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="gestor">
        <h2><i class="fas fa-clipboard-check"></i> Si gestiona reportes</h2>
        <p>
            El <strong>tablero</strong> es su pantalla de trabajo. Cada fila muestra el
            estado, el tiempo de atención restante y si hay técnico asignado.
        </p>

        <h3>Asignar</h3>
        <p>
            Solo aparecen como asignables los usuarios que realmente tienen el rol de
            Técnico en su institución. Al asignar, el reporte pasa a En Proceso y el
            técnico recibe el aviso.
        </p>

        <h3>Validar y cerrar</h3>
        <ol>
            <li>Cuando el técnico marca un reporte como solucionado, aparece el botón <strong>Validar</strong>.</li>
            <li>Si aprueba, el reporte queda En Validación y continúa hacia el cierre.</li>
            <li>Si rechaza, vuelve al técnico con su motivo y el reloj del SLA se pausa.</li>
        </ol>
        <div class="ay-nota">
            <p>
                Si interrumpe el proceso a medias, el reporte se queda en <strong>En
                Validación</strong> y el tablero le ofrece <strong>Terminar cierre</strong>
                para retomarlo donde lo dejó.
            </p>
        </div>

        <h3>Anular</h3>
        <p>
            Para spam o duplicados, use <strong>Anular</strong> en vez de eliminar.
            Anular conserva la constancia de que el reporte llegó; eliminar la borra
            para siempre. Hay que escribir el motivo, y el estado no tiene vuelta atrás.
        </p>

        <h3>Filtrar</h3>
        <p>
            El listado permite filtrar por estado y por urgencia. La urgencia que se
            filtra es la <em>calculada</em>: si la categoría está marcada como crítica,
            el sistema sube el reporte a Urgente aunque quien lo reportó lo haya
            declarado menor.
        </p>
    </section>
    <?php endif; ?>

    <?php if ($es_admin): ?>
    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="admin">
        <h2><i class="fas fa-gear"></i> Si administra la institución</h2>

        <h3>Usuarios</h3>
        <p>
            Al crear un usuario se le envía un correo con un enlace de un solo uso para
            que él mismo elija su contraseña; usted no la ve ni la tiene que comunicar.
            En su primer ingreso el sistema le obliga a cambiarla.
        </p>
        <p>
            Desactivar un usuario le corta el acceso de inmediato, incluso si tenía la
            sesión abierta. Lo mismo ocurre al cambiarle la contraseña.
        </p>

        <h3>Lo que hay que configurar antes de operar</h3>
        <p>Una institución necesita todas estas piezas, o funciona a medias sin avisar:</p>
        <ul>
            <li>Al menos una <strong>sede</strong> activa.</li>
            <li><strong>Categorías</strong> de daño. Marque como críticas las que deban subir a Urgente automáticamente.</li>
            <li><strong>Tiempos de atención (SLA)</strong>. Sin ellos se usa un valor por defecto de 48 horas.</li>
            <li>Alguien con rol <strong>Gestor</strong> o <strong>Rector</strong>: son quienes reciben las alertas. Sin ellos, las alertas se generan y se descartan sin destinatario.</li>
            <li><strong>Técnicos</strong> a quienes asignar.</li>
        </ul>

        <h3>Roles y permisos</h3>
        <p>
            Cada rol trae unos permisos por defecto. Puede ajustarlos, pero tenga en
            cuenta que quitar <em>validar y cerrar</em> a todos los gestores deja los
            reportes sin poder cerrarse.
        </p>
    </section>
    <?php endif; ?>

    <?php if ($es_superadmin): ?>
    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="superadmin">
        <h2><i class="fas fa-building"></i> Administración global</h2>
        <p>
            El panel global lista todas las instituciones con una insignia que dice si
            están <strong>listas para operar</strong> o cuántas piezas les faltan. Pulse
            la insignia para ver qué falta y por qué importa.
        </p>
        <p>
            Los procesos automáticos (revisión de tiempos de atención, reintento de
            correos y revisión diaria de salud) corren por su cuenta en el servidor.
            Dejan su registro en los archivos de log, y la revisión de salud envía un
            correo cuando encuentra algo que requiere atención.
        </p>
    </section>
    <?php endif; ?>

    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-seccion" id="cuenta">
        <h2><i class="fas fa-user-shield"></i> Su cuenta</h2>
        <p>
            En <strong>Seguridad</strong> puede activar la verificación en dos pasos.
            Al activarla hay que confirmar un código antes de que quede en vigor,
            precisamente para que nadie se quede fuera por haber configurado mal la
            aplicación del teléfono.
        </p>
        <p>
            La sesión se cierra sola tras un rato de inactividad. Si cambia su
            contraseña, todas las sesiones abiertas en otros dispositivos se cierran.
        </p>
    </section>

    <!-- ─────────────────────────────────────────────── -->
    <section class="ay-soporte" id="soporte">
        <h2><i class="fas fa-life-ring"></i> Soporte</h2>
        <p>
            Si algo no funciona como dice esta página, escriba a
            <span class="ay-correo"><?php echo htmlspecialchars($correo_soporte); ?></span>.
        </p>
        <p>
            Si vio una pantalla de error, incluya el <strong>código de referencia</strong>
            que aparecía en ella: con ese código se localiza el fallo exacto en los
            registros del servidor sin tener que adivinar.
        </p>
    </section>

</div>
