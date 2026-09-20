<?php
/* Hoja de trabajo del técnico: una sola pantalla por ticket que se completa de
 * forma progresiva. Variables: $reporte, $detalle, $intervension, $evidencias,
 * $completitud, $avances, $informe_faltante, $ya_solucionado, $csrf_token */
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
$id_interv = $intervension['id_informe'];
$id_rep = (int)$reporte['id_reporte'];
$base = config('app.url_base');

// Agrupar evidencias por etapa
$por_etapa = ['antes' => [], 'durante' => [], 'despues' => []];
foreach (($evidencias ?? []) as $ev) {
    $nombre_etapa = ModeloEvidencia::id_a_etapa($ev['id_etapa']);
    if ($nombre_etapa && isset($por_etapa[$nombre_etapa])) {
        $por_etapa[$nombre_etapa][] = $ev;
    }
}
$etapas_def = [
    'antes'   => ['label' => 'Antes',    'icon' => 'fa-camera-retro',   'color' => '#3498DB'],
    'durante' => ['label' => 'Durante',  'icon' => 'fa-person-digging', 'color' => '#E67E22'],
    'despues' => ['label' => 'Después',  'icon' => 'fa-circle-check',   'color' => '#27AE60'],
];
$completa  = $completitud['completa']  ?? false;
$faltantes = $completitud['faltantes'] ?? [];
$informe_ok = empty($informe_faltante);
$bloqueada = !empty($ya_solucionado);
$puede_cerrar = $completa && $informe_ok && !$bloqueada;

// Materiales guardados como JSON → una línea por material para el textarea
$materiales_txt = '';
if (!empty($intervension['materiales_utilizados_json'])) {
    $m = json_decode($intervension['materiales_utilizados_json'], true);
    if (is_array($m)) $materiales_txt = implode("\n", array_map(fn($x) => $x['nombre'] ?? '', $m));
}

// Pasos del flujo con su estado calculado
$pasos = [
    ['Iniciar',   true],
    ['Antes',     count($por_etapa['antes']) > 0],
    ['Durante',   count($por_etapa['durante']) > 0],
    ['Después',   count($por_etapa['despues']) > 0],
    ['Informe',   $informe_ok],
    ['Solución',  $bloqueada],
];
$hechos = count(array_filter($pasos, fn($p) => $p[1]));
$ubicacion = trim(($detalle['sede_nombre'] ?? '') . (empty($detalle['referencia_ubicacion_libre']) ? '' : ' — ' . $detalle['referencia_ubicacion_libre']));
$clasificacion = trim(($detalle['categoria_nombre'] ?? '') . (empty($detalle['subcategoria_nombre']) ? '' : ' / ' . $detalle['subcategoria_nombre']));
?>
<div class="container ev-container">

    <!-- Banner -->
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-clipboard-check"></i></div>
        <div class="page-banner__text">
            <h2>Hoja de trabajo &mdash; <?php echo htmlspecialchars($reporte['numero_ticket']); ?></h2>
            <p>Avanza a tu ritmo: cada foto, nota o cambio del informe se guarda por separado.
               Iniciada el <?php echo date('d/m/Y H:i', strtotime($intervension['fecha_hora_inicio'])); ?>.</p>
        </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="completitud-card comp-error">
            <i class="fas fa-circle-exclamation"></i>
            <div><?php echo htmlspecialchars($_GET['error']); ?></div>
        </div>
    <?php endif; ?>

    <!-- Progreso por pasos -->
    <div class="pasos-card">
        <div class="pasos-head">
            <strong>Progreso</strong>
            <span><?php echo $hechos; ?> de <?php echo count($pasos); ?> pasos</span>
        </div>
        <ol class="pasos">
            <?php foreach ($pasos as $i => [$nombre, $ok]): ?>
            <li class="<?php echo $ok ? 'paso-ok' : ''; ?>">
                <span class="paso-num"><?php echo $ok ? '<i class="fas fa-check"></i>' : ($i + 1); ?></span>
                <span class="paso-nombre"><?php echo $nombre; ?></span>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <!-- Qué hay que atender -->
    <details class="resumen-card" open>
        <summary><i class="fas fa-circle-info"></i> Qué hay que atender</summary>
        <div class="resumen-grid">
            <div><span class="resumen-k">Urgencia</span><span class="resumen-v urg"><?php echo htmlspecialchars($detalle['urgencia_nombre'] ?? ''); ?></span></div>
            <div><span class="resumen-k">Clasificación</span><span class="resumen-v"><?php echo htmlspecialchars($clasificacion); ?></span></div>
            <div><span class="resumen-k">Ubicación</span><span class="resumen-v"><?php echo htmlspecialchars($ubicacion); ?></span></div>
            <div><span class="resumen-k">Reportado por</span><span class="resumen-v"><?php echo htmlspecialchars($detalle['nombre_reportante'] ?? ''); ?></span></div>
            <div class="resumen-full"><span class="resumen-k">Descripción</span><span class="resumen-v"><?php echo nl2br(htmlspecialchars($detalle['descripcion_problema'] ?? '')); ?></span></div>
        </div>
    </details>

    <?php if ($bloqueada): ?>
        <div class="completitud-card comp-ok">
            <i class="fas fa-circle-check"></i>
            <div><strong>Trabajo entregado.</strong> El reporte está en manos del gestor para su validación. La hoja queda en solo lectura.</div>
        </div>
    <?php endif; ?>

    <!-- Evidencias -->
    <h3 class="seccion-titulo"><i class="fas fa-camera"></i> Evidencia fotográfica <small>mínimo 1 foto por etapa</small></h3>
    <div class="completitud-card <?php echo $completa ? 'comp-ok' : 'comp-pend'; ?>">
        <?php if ($completa): ?>
            <i class="fas fa-circle-check"></i>
            <div><strong>Evidencia completa.</strong></div>
        <?php else: ?>
            <i class="fas fa-triangle-exclamation"></i>
            <div><strong>Evidencia incompleta.</strong> Faltan fotos de: <?php echo htmlspecialchars(implode(', ', array_map('ucfirst', $faltantes))); ?></div>
        <?php endif; ?>
    </div>

    <!-- 3 Etapas -->
    <div class="etapas-grid">
        <?php foreach ($etapas_def as $clave => $def):
            $fotos = $por_etapa[$clave];
            $tiene = count($fotos) > 0;
        ?>
        <div class="etapa-card <?php echo $tiene ? 'etapa-ok' : ''; ?>">

            <!-- Cabecera etapa -->
            <div class="etapa-head">
                <span class="etapa-titulo" style="--etapa-color:<?php echo $def['color']; ?>">
                    <i class="fas <?php echo $def['icon']; ?>"></i>
                    <?php echo $def['label']; ?>
                </span>
                <span class="etapa-count <?php echo $tiene ? 'badge-ok' : 'badge-pend'; ?>">
                    <?php echo $tiene
                        ? '<i class="fas fa-check"></i> ' . count($fotos)
                        : 'Pendiente'; ?>
                </span>
            </div>

            <!-- Fotos ya subidas: miniaturas que se amplían al pulsar (visor_imagenes.js) -->
            <?php if ($tiene): ?>
            <div class="fotos-grid">
                <?php foreach ($fotos as $i => $f):
                    $url = $base . '/?controlador=tecnico&accion=descargar_evidencia&id=' . (int)$f['id_evidencia'];
                    $titulo = $def['label'] . ' · foto ' . ($i + 1) . (empty($f['descripcion']) ? '' : ' — ' . $f['descripcion']);
                ?>
                <a href="<?php echo $url; ?>" class="foto-thumb" data-visor="evidencias"
                   title="<?php echo htmlspecialchars($titulo); ?>" data-titulo="<?php echo htmlspecialchars($titulo); ?>">
                    <img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars($titulo); ?>" loading="lazy">
                    <span class="foto-thumb-zoom"><i class="fas fa-magnifying-glass-plus"></i></span>
                    <?php if (!empty($f['descripcion'])): ?>
                    <span class="foto-thumb-pie"><?php echo htmlspecialchars($f['descripcion']); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Formulario de carga (no disponible una vez entregado el trabajo) -->
            <?php if (!$bloqueada): ?>
            <form method="POST"
                  enctype="multipart/form-data"
                  action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=cargar_evidencia"
                  class="form-foto"
                  id="form-<?php echo $clave; ?>">
                <input type="hidden" name="csrf_token"       value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="id_intervension"  value="<?php echo $id_interv; ?>">
                <input type="hidden" name="etapa_evidencia"  value="<?php echo $clave; ?>">

                <!-- Preview de la foto seleccionada (antes de subir) -->
                <div id="preview-wrap-<?php echo $clave; ?>" class="ev-preview-wrap" style="display:none;">
                    <img id="preview-img-<?php echo $clave; ?>" src="" alt="Vista previa" class="ev-preview-img">
                    <button type="button" class="ev-preview-remove"
                            onclick="quitarPreview('<?php echo $clave; ?>')"
                            title="Quitar foto">
                        <i class="fas fa-xmark"></i>
                    </button>
                    <div class="ev-preview-label" id="preview-label-<?php echo $clave; ?>"></div>
                </div>

                <!-- Botones de fuente -->
                <div id="source-row-<?php echo $clave; ?>" class="ev-source-row">
                    <!-- Archivo / galería -->
                    <div class="ev-source-card"
                         onclick="document.getElementById('input-file-<?php echo $clave; ?>').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <strong>Subir archivo</strong>
                        <small>JPG · PNG · WebP</small>
                    </div>
                    <!-- Cámara -->
                    <div class="ev-source-card ev-source-cam"
                         onclick="abrirCamaraEtapa('<?php echo $clave; ?>')">
                        <i class="fas fa-camera"></i>
                        <strong>Tomar foto</strong>
                        <small>Cámara en tiempo real</small>
                    </div>
                </div>

                <!-- Inputs ocultos -->
                <input type="file" id="input-file-<?php echo $clave; ?>"
                       name="foto" accept="image/jpeg,image/png,image/webp"
                       style="display:none;"
                       onchange="onFotoSeleccionada(this, '<?php echo $clave; ?>')">
                <!-- Cámara nativa móvil -->
                <input type="file" id="input-cam-movil-<?php echo $clave; ?>"
                       accept="image/*" capture="environment"
                       style="display:none;"
                       onchange="onFotoSeleccionada(this, '<?php echo $clave; ?>')">

                <input type="text"
                       name="descripcion_foto"
                       maxlength="255"
                       placeholder="Descripción de la foto (opcional)"
                       class="input-foto"
                       id="desc-<?php echo $clave; ?>">

                <button type="submit" class="btn-subir" id="btn-subir-<?php echo $clave; ?>" disabled>
                    <i class="fas fa-upload"></i> Subir foto
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Notas de avance -->
    <h3 class="seccion-titulo"><i class="fas fa-comment-dots"></i> Notas de avance <small>el reportante las ve en su enlace de seguimiento</small></h3>
    <div class="panel-card">
        <?php if (!$bloqueada): ?>
        <form method="POST" action="<?php echo $base; ?>/?controlador=tecnico&accion=agregar_avance" class="form-avance">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo $id_rep; ?>">
            <input type="text" name="texto" class="input-foto" maxlength="500" required minlength="5"
                   placeholder="Ej: Llegué al sitio, falta material, regreso mañana…">
            <button type="submit" class="btn-subir" style="width:auto;padding:10px 18px;">
                <i class="fas fa-plus"></i> Agregar nota
            </button>
        </form>
        <?php endif; ?>

        <?php if (empty($avances)): ?>
            <p class="vacio">Aún no hay notas. Úsalas para dejar constancia de cada visita o novedad.</p>
        <?php else: ?>
            <ul class="avances">
                <?php foreach ($avances as $a): ?>
                <li>
                    <span class="avance-fecha"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($a['fecha_creacion'])); ?></span>
                    <span class="avance-texto"><?php echo htmlspecialchars($a['texto']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Informe técnico -->
    <h3 class="seccion-titulo"><i class="fas fa-file-lines"></i> Informe técnico <small>puedes guardarlo a medias y completarlo después</small></h3>
    <div class="completitud-card <?php echo $informe_ok ? 'comp-ok' : 'comp-pend'; ?>">
        <?php if ($informe_ok): ?>
            <i class="fas fa-circle-check"></i>
            <div><strong>Informe completo.</strong></div>
        <?php else: ?>
            <i class="fas fa-triangle-exclamation"></i>
            <div><strong>Informe incompleto.</strong> Falta: <?php echo htmlspecialchars(implode('; ', $informe_faltante)); ?>.</div>
        <?php endif; ?>
    </div>
    <form method="POST" action="<?php echo $base; ?>/?controlador=tecnico&accion=guardar_informe" class="panel-card form-informe">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <input type="hidden" name="id_reporte" value="<?php echo $id_rep; ?>">
        <?php $ro = $bloqueada ? 'readonly disabled' : ''; ?>

        <div class="form-group">
            <label for="descripcion_actividades">Actividades realizadas <span class="req">*</span></label>
            <textarea id="descripcion_actividades" name="descripcion_actividades" rows="4" class="input-foto" <?php echo $ro; ?>
                      placeholder="Qué se revisó y qué se hizo, paso a paso."><?php echo htmlspecialchars($intervension['descripcion_actividades'] ?? ''); ?></textarea>
            <small>Mínimo <?php echo ModeloIntervension::MIN_DESCRIPCION; ?> caracteres.</small>
        </div>
        <div class="form-group">
            <label for="causa_raiz">Causa raíz</label>
            <textarea id="causa_raiz" name="causa_raiz" rows="2" class="input-foto" <?php echo $ro; ?>
                      placeholder="Por qué ocurrió el daño."><?php echo htmlspecialchars($intervension['causa_raiz'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="solucion_implementada">Solución implementada <span class="req">*</span></label>
            <textarea id="solucion_implementada" name="solucion_implementada" rows="3" class="input-foto" <?php echo $ro; ?>
                      placeholder="Cómo quedó resuelto."><?php echo htmlspecialchars($intervension['solucion_implementada'] ?? ''); ?></textarea>
            <small>Mínimo <?php echo ModeloIntervension::MIN_SOLUCION; ?> caracteres.</small>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label for="materiales">Materiales utilizados</label>
                <textarea id="materiales" name="materiales" rows="3" class="input-foto" <?php echo $ro; ?>
                          placeholder="Uno por línea."><?php echo htmlspecialchars($materiales_txt); ?></textarea>
            </div>
            <div class="form-group">
                <label for="costo_estimado">Costo estimado</label>
                <input type="number" id="costo_estimado" name="costo_estimado" min="0" step="0.01" class="input-foto" <?php echo $ro; ?>
                       value="<?php echo htmlspecialchars($intervension['costo_estimado'] ?? ''); ?>" placeholder="0.00">
            </div>
        </div>
        <?php if (!$bloqueada): ?>
        <button type="submit" class="btn-subir" style="width:auto;padding:12px 24px;align-self:flex-start;">
            <i class="fas fa-floppy-disk"></i> Guardar avance del informe
        </button>
        <?php endif; ?>
    </form>

    <!-- Acciones finales -->
    <div class="acciones-finales">
        <a href="<?php echo $base; ?>/?controlador=tecnico&accion=mis_asignaciones"
           class="btn-modern-secondary" style="text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Volver a Mis Asignaciones
        </a>
        <?php if (!$bloqueada): ?>
        <form method="POST" action="<?php echo $base; ?>/?controlador=tecnico&accion=marcar_solucionado" style="flex:1;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="id_reporte" value="<?php echo $id_rep; ?>">
            <button type="submit" class="btn-modern" <?php echo $puede_cerrar ? '' : 'disabled'; ?>
                    title="<?php echo $puede_cerrar ? 'Entregar el trabajo para validación' : 'Completa las fotos de las 3 etapas y el informe para habilitar este botón'; ?>"
                    onclick="return confirm('¿Entregar el trabajo como solucionado? El gestor lo validará y se avisará al reportante.');">
                <i class="fas fa-check-double"></i> Marcar como Solucionado
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php if (!$bloqueada && !$puede_cerrar): ?>
        <p class="ayuda-cierre"><i class="fas fa-circle-info"></i> Para marcar como solucionado necesitas
            <?php $req = []; if (!$completa) $req[] = 'fotos de las 3 etapas'; if (!$informe_ok) $req[] = 'el informe completo'; echo implode(' y ', $req); ?>.</p>
    <?php endif; ?>
</div>

<!-- ── MODAL CÁMARA ── (compartido para las 3 etapas) -->
<div id="modal-camara-ev" class="cam-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="cam-box">
        <div class="cam-header">
            <span id="cam-header-title"><i class="fas fa-camera"></i> Tomar foto — <span id="cam-etapa-label">Etapa</span></span>
            <button type="button" class="cam-close" onclick="cerrarCamara()">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="cam-body">
            <video id="cam-stream" autoplay playsinline muted class="cam-stream"></video>
            <canvas id="cam-canvas" style="display:none;"></canvas>
            <div id="cam-flash" class="cam-flash"></div>
        </div>
        <div class="cam-footer">
            <button type="button" class="cam-btn-switch" id="btn-switch-cam"
                    onclick="cambiarCamara()" title="Cambiar cámara" style="display:none;">
                <i class="fas fa-rotate"></i>
            </button>
            <button type="button" class="cam-btn-capture" onclick="capturarFoto()">
                <i class="fas fa-camera"></i> Capturar foto
            </button>
        </div>
        <p id="cam-error" class="cam-error" style="display:none;"></p>
    </div>
</div>

<?php
$toast_exito_msg = [
    'foto'    => 'Foto cargada correctamente.',
    'informe' => 'Avance del informe guardado.',
    'avance'  => 'Nota de avance registrada.',
][$_GET['exito'] ?? ''] ?? 'Cambios guardados.';
require APP_PATH . '/vistas/comunes/toast_helper.php';
?>
<script src="<?php echo asset_url('js/visor_imagenes.js'); ?>"></script>

<style>
:root {
    --primary-blue: var(--color-primary);
    --dark-blue:    var(--color-primary-dark);
    --gray-text:    var(--color-text-muted);
    --dark-text:    var(--color-text);
    --light-bg:     var(--color-bg-subtle);
}

/* ── Layout ── */
.ev-container { max-width: 1200px; margin: 30px auto; padding: 20px; }

.completitud-card { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:12px; margin-bottom:25px; font-size:14px; }
.completitud-card i { font-size:24px; }
.comp-ok   { background:var(--color-success-bg); color:var(--color-success-text); border-left:4px solid var(--color-success); }
.comp-pend { background:var(--color-warning-bg); color:var(--color-warning-text); border-left:4px solid var(--color-warning); }
.comp-error { background:var(--color-danger-bg); color:var(--color-danger-text); border-left:4px solid var(--color-danger); }

/* ── Hoja de trabajo: progreso, resumen, paneles ── */
.seccion-titulo {
    display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;
    margin:34px 0 14px; font-size:17px; font-weight:700; color:var(--dark-text);
}
.seccion-titulo i { color:var(--primary-blue); }
.seccion-titulo small { font-size:12px; font-weight:500; color:var(--gray-text); }

.pasos-card {
    background:var(--color-bg-elevated); border-radius:12px; padding:18px 22px; margin-bottom:20px;
    box-shadow:0 2px 12px rgba(0,0,0,.08);
}
.pasos-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; font-size:14px; color:var(--dark-text); }
.pasos-head span { color:var(--gray-text); font-size:13px; }
.pasos { list-style:none; margin:0; padding:0; display:flex; gap:6px; }
.pasos li { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; position:relative; }
.pasos li:not(:last-child)::after {
    content:''; position:absolute; top:16px; left:calc(50% + 18px); right:calc(-50% + 18px);
    height:2px; background:var(--color-border);
}
.pasos li.paso-ok:not(:last-child)::after { background:var(--color-success); }
.paso-num {
    width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:700; background:var(--color-bg-subtle); color:var(--gray-text);
    border:2px solid var(--color-border); z-index:1;
}
.paso-ok .paso-num { background:var(--color-success); color:#fff; border-color:var(--color-success); }
.paso-nombre { font-size:11px; font-weight:600; color:var(--gray-text); text-transform:uppercase; letter-spacing:.3px; }
.paso-ok .paso-nombre { color:var(--dark-text); }

.resumen-card {
    background:var(--color-bg-elevated); border-radius:12px; margin-bottom:20px;
    box-shadow:0 2px 12px rgba(0,0,0,.08); border-left:4px solid var(--primary-blue);
}
.resumen-card summary { cursor:pointer; padding:14px 20px; font-weight:700; font-size:14px; color:var(--dark-text); list-style:none; display:flex; align-items:center; gap:8px; }
.resumen-card summary::-webkit-details-marker { display:none; }
.resumen-card summary i { color:var(--primary-blue); }
.resumen-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px 24px; padding:4px 20px 18px; }
.resumen-grid > div { display:flex; flex-direction:column; gap:3px; }
.resumen-full { grid-column:1 / -1; }
.resumen-k { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--gray-text); font-weight:600; }
.resumen-v { font-size:14px; color:var(--dark-text); line-height:1.5; }
.resumen-v.urg { font-weight:700; color:var(--color-warning-text); }

.panel-card {
    background:var(--color-bg-elevated); border-radius:12px; padding:20px 22px; margin-bottom:20px;
    box-shadow:0 2px 12px rgba(0,0,0,.08); display:flex; flex-direction:column; gap:14px;
}
.form-avance { display:flex; gap:10px; align-items:stretch; }
.form-avance .input-foto { flex:1; }
.avances { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
.avances li {
    display:flex; flex-direction:column; gap:3px; padding:10px 14px; border-radius:8px;
    background:var(--color-bg-subtle); border-left:3px solid var(--primary-blue);
}
.avance-fecha { font-size:11px; color:var(--gray-text); }
.avance-texto { font-size:14px; color:var(--dark-text); }
.vacio { margin:0; font-size:13px; color:var(--gray-text); }

.form-informe .form-group { display:flex; flex-direction:column; gap:6px; }
.form-informe label { font-size:13px; font-weight:600; color:var(--dark-text); }
.form-informe small { font-size:11px; color:var(--gray-text); }
.form-informe textarea.input-foto { resize:vertical; line-height:1.5; }
.form-informe .input-foto:disabled { opacity:.75; cursor:not-allowed; }
.form-row-2 { display:grid; grid-template-columns:2fr 1fr; gap:14px; }
.req { color:var(--color-danger); }
.ayuda-cierre { margin:10px 0 0; font-size:13px; color:var(--gray-text); display:flex; align-items:center; gap:8px; }
.ayuda-cierre i { color:var(--primary-blue); }

/* ── Grid 3 etapas ── */
.etapas-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:25px; }

.etapa-card {
    background:var(--color-bg-elevated);
    color:var(--color-text);
    border-radius:14px;
    padding:20px;
    box-shadow:0 4px 18px rgba(52,152,219,.09);
    border-top:4px solid var(--color-border);
    display:flex; flex-direction:column; gap:14px;
}
.etapa-ok { border-top-color: var(--color-success); }

.etapa-head { display:flex; align-items:center; justify-content:space-between; }
.etapa-titulo {
    font-size:15px; font-weight:700; color:var(--dark-text);
    display:flex; align-items:center; gap:8px;
}
.etapa-titulo i { color: var(--etapa-color, var(--primary-blue)); }
.etapa-count { font-size:11px; font-weight:600; padding:4px 10px; border-radius:12px; }
.badge-ok   { background:rgba(39,174,96,.15);    color: var(--color-success); }
.badge-pend { background:rgba(189,195,199,.3);   color:var(--color-text); }

/* ── Lista fotos subidas ── */
.fotos-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(96px, 1fr)); gap:8px; }
.foto-thumb {
    position:relative; display:block; aspect-ratio:1; border-radius:8px; overflow:hidden;
    background:var(--color-bg-subtle); border:1px solid var(--color-border-subtle);
    cursor:zoom-in; transition:transform .15s, box-shadow .15s;
}
.foto-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.foto-thumb:hover, .foto-thumb:focus-visible { transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.25); outline:none; border-color:var(--primary-blue); }
.foto-thumb-zoom {
    position:absolute; top:6px; right:6px; width:24px; height:24px; border-radius:50%;
    background:rgba(0,0,0,.55); color:#fff; font-size:11px; display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity .15s;
}
.foto-thumb:hover .foto-thumb-zoom { opacity:1; }
.foto-thumb-pie {
    position:absolute; left:0; right:0; bottom:0; padding:4px 6px; font-size:10px; line-height:1.3; color:#fff;
    background:linear-gradient(transparent, rgba(0,0,0,.75)); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}

/* ── Formulario carga ── */
.form-foto { display:flex; flex-direction:column; gap:10px; border-top:1px dashed var(--color-border-subtle); padding-top:14px; }

/* Preview de foto seleccionada */
.ev-preview-wrap {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 4/3;
    background: #000;
}
.ev-preview-img  { width:100%; height:100%; object-fit:cover; display:block; }
.ev-preview-remove {
    position:absolute; top:6px; right:6px;
    background:rgba(231,76,60,.9); color:#fff;
    border:none; border-radius:50%;
    width:28px; height:28px;
    cursor:pointer; font-size:14px;
    display:flex; align-items:center; justify-content:center;
    transition:background .2s;
}
.ev-preview-remove:hover { background:#E74C3C; }
.ev-preview-label {
    position:absolute; bottom:0; left:0; right:0;
    background:rgba(0,0,0,.5); color:#fff;
    font-size:11px; padding:4px 8px; text-align:center;
}

/* ── Source row (archivo / cámara) ── */
.ev-source-row {
    display:grid; grid-template-columns:1fr 1fr; gap:10px;
}
.ev-source-card {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:5px; padding:16px 10px;
    border:2px dashed var(--color-border); border-radius:10px;
    background:var(--color-bg-subtle); cursor:pointer;
    transition:all .2s; text-align:center;
}
.ev-source-card i      { font-size:22px; color:var(--primary-blue); transition:transform .2s; }
.ev-source-card strong { font-size:12px; font-weight:700; color:var(--dark-text); }
.ev-source-card small  { font-size:10px; color:var(--gray-text); }
.ev-source-card:hover  { border-color:var(--primary-blue); background:var(--color-bg-hover); }
.ev-source-card:hover i { transform:scale(1.1); }

.ev-source-cam            { border-color:var(--teal-accent); }
.ev-source-cam i          { color:var(--teal-accent); }
.ev-source-cam:hover      { border-color:var(--teal-accent); background:var(--color-bg-hover); }

.input-foto {
    padding:10px 12px;
    border:2px solid var(--primary-blue);
    background:var(--light-bg);
    border-radius:8px;
    font-size:13px;
    box-sizing:border-box;
    font-family:inherit;
}
.input-foto:focus { outline:none; border-color:var(--dark-blue); background:var(--color-bg-elevated); }

.btn-subir {
    padding:10px;
    background:linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
    color:#fff; border:none; border-radius:8px;
    cursor:pointer; font-weight:600; font-size:13px;
    display:flex; align-items:center; justify-content:center; gap:6px;
    transition:all .3s;
    font-family:inherit;
}
.btn-subir:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 4px 14px rgba(52,152,219,.35); }
/* Deshabilitado: se ve inactivo pero legible en ambos temas (antes era gris
   claro con texto blanco, ilegible). */
.btn-subir:disabled {
    background:var(--color-bg-subtle); color:var(--color-text-muted);
    border:1px solid var(--color-border); cursor:not-allowed; box-shadow:none;
}

/* ── Acciones finales ── */
.acciones-finales { display:flex; gap:15px; align-items:stretch; flex-wrap:wrap; }
.btn-modern {
    width:100%; padding:14px 24px;
    background:linear-gradient(135deg,#27AE60,#1E8449);
    color:#fff; border:none; border-radius:8px;
    cursor:pointer; font-weight:600; text-transform:uppercase;
    font-size:14px; transition:all .3s;
    display:flex; align-items:center; justify-content:center; gap:8px;
    font-family:inherit;
}
.btn-modern:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 6px 20px rgba(39,174,96,.4); }
.btn-modern:disabled {
    background:var(--color-bg-subtle); color:var(--color-text-muted);
    border:1px solid var(--color-border); cursor:not-allowed; box-shadow:none;
}
.btn-modern-secondary {
    padding:14px 24px;
    background:var(--color-bg-subtle); color:var(--dark-text);
    border:2px solid var(--primary-blue); border-radius:8px;
    cursor:pointer; font-weight:600; text-transform:uppercase;
    font-size:14px; transition:all .3s;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-modern-secondary:hover { background:var(--gray-text); color:#fff; }

/* ── MODAL CÁMARA ── */
.cam-modal {
    position:fixed; inset:0;
    background:rgba(15,25,40,.75);
    backdrop-filter:blur(4px);
    z-index:9000;
    display:flex; align-items:center; justify-content:center;
    padding:16px;
}
.cam-box {
    background:var(--color-bg-elevated); border-radius:16px;
    width:100%; max-width:520px;
    overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
}
.cam-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px;
    background:linear-gradient(135deg,#2980B9,#3498DB);
    color:#fff; font-weight:700; font-size:14px;
}
.cam-close {
    background:rgba(255,255,255,.15); border:none; border-radius:8px;
    color:#fff; width:32px; height:32px;
    cursor:pointer; font-size:16px;
    display:flex; align-items:center; justify-content:center;
    transition:background .2s;
}
.cam-close:hover { background:rgba(255,255,255,.3); }
.cam-body {
    position:relative; background:#000;
    aspect-ratio:4/3; max-height:340px; overflow:hidden;
}
.cam-stream   { width:100%; height:100%; object-fit:cover; display:block; }
.cam-flash    { position:absolute; inset:0; background:#fff; opacity:0; pointer-events:none; transition:opacity .05s; }
.cam-flash.flash { opacity:1; }
.cam-footer {
    padding:14px 20px;
    display:flex; align-items:center; justify-content:center; gap:12px;
    background:var(--color-bg-subtle);
}
.cam-btn-capture {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#2980B9,#3498DB);
    color:#fff; border:none; border-radius:10px;
    padding:12px 28px; font-size:14px; font-weight:700;
    cursor:pointer; font-family:inherit; transition:all .2s;
    box-shadow:0 4px 14px rgba(52,152,219,.35);
}
.cam-btn-capture:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(52,152,219,.45); }
.cam-btn-switch {
    background:#EBF5FB; border:1.5px solid #D5E8F5;
    border-radius:10px; color:var(--primary-blue);
    width:44px; height:44px; cursor:pointer; font-size:18px;
    display:flex; align-items:center; justify-content:center;
    transition:all .2s;
}
.cam-btn-switch:hover { background:#D5E8F5; }
.cam-error { margin:0; padding:10px 20px 14px; background:#FDF0EF; color:#C0392B; font-size:13px; text-align:center; }

@media(max-width:768px) {
    .etapas-grid { grid-template-columns:1fr; }
    .acciones-finales { flex-direction:column; }
    .resumen-grid, .form-row-2 { grid-template-columns:1fr; }
    .form-avance { flex-direction:column; }
    .pasos { flex-wrap:wrap; row-gap:14px; }
    .pasos li { flex:0 0 33%; }
    .pasos li::after { display:none; }
}
</style>

<script>
const esMobil = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
const etapasLabels = { antes:'Antes', durante:'Durante', despues:'Después' };

let streamCam      = null;
let etapaActiva    = null;
let facingMode     = 'environment';

// ─── Abrir cámara para una etapa ────────────────────────────────────────────
function abrirCamaraEtapa(clave) {
    if (esMobil) {
        document.getElementById('input-cam-movil-' + clave).click();
        return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarToastError('Tu navegador no soporta cámara. Usa "Subir archivo".');
        return;
    }
    etapaActiva = clave;
    document.getElementById('cam-etapa-label').textContent = etapasLabels[clave] || clave;
    document.getElementById('modal-camara-ev').style.display = 'flex';
    iniciarStream();
}

async function iniciarStream() {
    ocultarError();
    try {
        if (streamCam) streamCam.getTracks().forEach(t => t.stop());
        streamCam = await navigator.mediaDevices.getUserMedia({
            video: { facingMode, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        });
        document.getElementById('cam-stream').srcObject = streamCam;

        navigator.mediaDevices.enumerateDevices().then(devs => {
            const cams = devs.filter(d => d.kind === 'videoinput');
            document.getElementById('btn-switch-cam').style.display = cams.length > 1 ? '' : 'none';
        });
    } catch(err) {
        mostrarError(mensajeErrorCamara(err));
    }
}

// ─── Capturar foto ───────────────────────────────────────────────────────────
function capturarFoto() {
    const video  = document.getElementById('cam-stream');
    const canvas = document.getElementById('cam-canvas');
    canvas.width  = video.videoWidth  || 1280;
    canvas.height = video.videoHeight || 720;
    canvas.getContext('2d').drawImage(video, 0, 0);

    // Flash visual
    const flash = document.getElementById('cam-flash');
    flash.classList.add('flash');
    setTimeout(() => flash.classList.remove('flash'), 150);

    canvas.toBlob(blob => {
        if (!blob || !etapaActiva) return;
        const nombre = 'foto_' + etapaActiva + '_' + Date.now() + '.jpg';
        const file   = new File([blob], nombre, { type: 'image/jpeg' });

        // Asignar al input del formulario correcto
        const inputFile = document.getElementById('input-file-' + etapaActiva);
        const dt = new DataTransfer();
        dt.items.add(file);
        inputFile.files = dt.files;

        // Mostrar preview
        const reader = new FileReader();
        reader.onload = ev => mostrarPreviewEtapa(etapaActiva, ev.target.result, nombre, file.size);
        reader.readAsDataURL(file);

        cerrarCamara();
    }, 'image/jpeg', 0.88);
}

// ─── Foto desde archivo o cámara nativa móvil ────────────────────────────────
function onFotoSeleccionada(input, clave) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        mostrarToastError('La foto supera 10 MB.');
        input.value = '';
        return;
    }
    // Si vino del input de galería, sincronizar con input-file principal
    if (input.id !== 'input-file-' + clave) {
        const inputPrincipal = document.getElementById('input-file-' + clave);
        const dt = new DataTransfer();
        dt.items.add(file);
        inputPrincipal.files = dt.files;
    }
    const reader = new FileReader();
    reader.onload = ev => mostrarPreviewEtapa(clave, ev.target.result, file.name, file.size);
    reader.readAsDataURL(file);
}

// ─── Mostrar/quitar preview ───────────────────────────────────────────────────
function mostrarPreviewEtapa(clave, src, nombre, size) {
    document.getElementById('preview-img-' + clave).src = src;
    document.getElementById('preview-label-' + clave).textContent =
        nombre + ' · ' + (size / 1024).toFixed(0) + ' KB';
    document.getElementById('preview-wrap-' + clave).style.display = '';
    document.getElementById('source-row-' + clave).style.display   = 'none';
    document.getElementById('btn-subir-' + clave).disabled = false;
}

function quitarPreview(clave) {
    document.getElementById('preview-wrap-' + clave).style.display = 'none';
    document.getElementById('source-row-' + clave).style.display   = '';
    document.getElementById('btn-subir-' + clave).disabled = true;
    document.getElementById('input-file-' + clave).value = '';
    document.getElementById('input-cam-movil-' + clave).value = '';
}

// ─── Cerrar cámara ────────────────────────────────────────────────────────────
function cerrarCamara() {
    if (streamCam) { streamCam.getTracks().forEach(t => t.stop()); streamCam = null; }
    document.getElementById('cam-stream').srcObject = null;
    document.getElementById('modal-camara-ev').style.display = 'none';
    ocultarError();
}

function cambiarCamara() {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    iniciarStream();
}

// Cerrar con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCamara(); });

// ─── Overlay de carga al subir foto ──────────────────────────────────────────
['antes', 'durante', 'despues'].forEach(function(clave) {
    var form = document.getElementById('form-' + clave);
    if (!form) return;
    form.addEventListener('submit', function() {
        var labels = { antes: 'Antes', durante: 'Durante', despues: 'Después' };
        var overlay = document.getElementById('overlay-ev-carga');
        document.getElementById('overlay-ev-etapa').textContent =
            'Subiendo foto de etapa "' + labels[clave] + '"…';
        overlay.style.display = 'flex';
    });
});

window.addEventListener('pageshow', function() {
    var overlay = document.getElementById('overlay-ev-carga');
    if (overlay) overlay.style.display = 'none';
});

// ─── Helpers ─────────────────────────────────────────────────────────────────
function mostrarError(msg) {
    const el = document.getElementById('cam-error');
    el.textContent = msg; el.style.display = '';
}
function ocultarError() {
    const el = document.getElementById('cam-error');
    el.style.display = 'none'; el.textContent = '';
}
function mostrarToastError(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#C0392B;color:#fff;padding:10px 22px;border-radius:24px;font-size:13px;font-weight:600;box-shadow:0 6px 20px rgba(0,0,0,.2);z-index:9999;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}
function mensajeErrorCamara(err) {
    if (err.name === 'NotAllowedError')  return 'Permiso de cámara denegado. Actívalo en el navegador e intenta de nuevo.';
    if (err.name === 'NotFoundError')    return 'No se encontró ninguna cámara en este dispositivo.';
    if (err.name === 'NotReadableError') return 'La cámara está siendo usada por otra aplicación.';
    return 'No se pudo acceder a la cámara: ' + err.message;
}
</script>

<!-- Overlay de carga al subir evidencia -->
<div id="overlay-ev-carga" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.75);
     z-index:99999; flex-direction:column; align-items:center; justify-content:center;
     backdrop-filter:blur(3px);">
    <div style="background:var(--color-bg-elevated); border-radius:18px; padding:36px 44px; text-align:center;
                box-shadow:0 24px 60px rgba(0,0,0,.25); max-width:300px; width:90%;">
        <div style="margin:0 auto 20px; width:56px; height:56px; border-radius:50%;
                    border:5px solid var(--color-border-subtle); border-top-color:#3498DB;
                    animation:spin-ev 0.8s linear infinite;"></div>
        <p style="font-size:16px; font-weight:700; color:var(--color-text); margin:0 0 8px;">
            Subiendo foto…
        </p>
        <p id="overlay-ev-etapa" style="font-size:13px; color:var(--color-text-muted); margin:0 0 18px; line-height:1.5;"></p>
        <div style="height:4px; background:var(--color-border-subtle); border-radius:4px; overflow:hidden;">
            <div style="height:100%; background:linear-gradient(90deg,#3498DB,#2ECC71,#3498DB);
                        background-size:200% 100%; animation:progress-ev 1.5s linear infinite;
                        border-radius:4px;"></div>
        </div>
        <p style="font-size:11px; color:var(--color-text-muted); margin:10px 0 0;">Por favor, no cierres esta ventana</p>
    </div>
</div>

<style>
@keyframes spin-ev     { to { transform: rotate(360deg); } }
@keyframes progress-ev { 0% { background-position:100% 0; } 100% { background-position:-100% 0; } }
</style>
