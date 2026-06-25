<!-- Detalle de Reporte -->
<?php
$estados = [1 => 'Registrado', 2 => 'En Proceso', 3 => 'Devuelto', 4 => 'Solucionado', 5 => 'En Validación', 6 => 'Cerrado', 7 => 'Cancelado', 8 => 'Anulado'];
$urgencias = [1 => 'No Urgente', 2 => 'Moderado', 3 => 'Importante', 4 => 'Urgente'];
$estado_id = (int)($reporte['id_estado'] ?? 1);
$urg_id = (int)($reporte['id_urgencia_calculada'] ?? 1);
$base = config('app.url_base');
?>
<div class="detalle-wrapper">
    <div class="detalle-card">
        <!-- Encabezado -->
        <div class="det-head">
            <div class="det-head-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="det-head-text">
                <h2>Detalle del Reporte de Daño</h2>
                <p>Información completa, seguimiento y evidencias de la incidencia.</p>
            </div>
            <span class="ticket-badge"><i class="fas fa-hashtag"></i><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span>
        </div>

        <div class="det-body">
            <!-- Estado y prioridad -->
            <div class="estado-row">
                <div class="estado-item">
                    <span class="estado-label">Estado</span>
                    <span class="badge badge-estado-<?php echo $estado_id; ?>"><?php echo htmlspecialchars($estados[$estado_id] ?? 'Desconocido'); ?></span>
                </div>
                <div class="estado-item">
                    <span class="estado-label">Prioridad de la incidencia</span>
                    <span class="badge badge-urg-<?php echo $urg_id; ?>"><?php echo htmlspecialchars($urgencias[$urg_id] ?? 'Desconocida'); ?></span>
                </div>
            </div>

            <!-- Sección: Información -->
            <fieldset class="det-section">
                <legend><i class="fas fa-circle-info"></i> Información del reporte</legend>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-location-dot"></i> Sede</span>
                        <p><?php echo htmlspecialchars($sede['nombre'] ?? '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-door-open"></i> Ubicación específica</span>
                        <p><?php echo htmlspecialchars($reporte['referencia_ubicacion_libre'] ?? '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-tag"></i> Categoría</span>
                        <p><?php echo htmlspecialchars($categoria['nombre'] ?? '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-tags"></i> Subcategoría</span>
                        <p><?php echo htmlspecialchars($subcategoria['nombre'] ?? '—'); ?></p>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar-plus"></i> Registrado</span>
                        <p><?php echo !empty($reporte['fecha_hora_registro']) ? date('d/m/Y H:i', strtotime($reporte['fecha_hora_registro'])) : '—'; ?></p>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar-check"></i> Actualizado</span>
                        <p><?php echo !empty($reporte['fecha_actualizacion']) ? date('d/m/Y H:i', strtotime($reporte['fecha_actualizacion'])) : '—'; ?></p>
                    </div>
                </div>
            </fieldset>

            <!-- Sección: Descripción -->
            <fieldset class="det-section">
                <legend><i class="fas fa-file-lines"></i> Descripción del problema</legend>
                <div class="description-box"><?php echo nl2br(htmlspecialchars($reporte['descripcion_problema'] ?? '')); ?></div>
            </fieldset>

            <?php if (!empty($intervencion)): ?>
                <!-- Sección: Informe técnico -->
                <fieldset class="det-section">
                    <legend><i class="fas fa-screwdriver-wrench"></i> Informe de Intervención Técnica</legend>
                    <div class="informe-grid">
                        <div class="informe-item"><span class="info-label">Actividades realizadas</span><p><?php echo nl2br(htmlspecialchars($intervencion['descripcion_actividades'] ?? '')); ?></p></div>
                        <?php if (!empty($intervencion['causa_raiz'])): ?>
                            <div class="informe-item"><span class="info-label">Causa raíz</span><p><?php echo nl2br(htmlspecialchars($intervencion['causa_raiz'])); ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($intervencion['solucion_implementada'])): ?>
                            <div class="informe-item"><span class="info-label">Solución implementada</span><p><?php echo nl2br(htmlspecialchars($intervencion['solucion_implementada'])); ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($intervencion['costo_estimado'])): ?>
                            <div class="informe-item"><span class="info-label">Costo estimado</span><p>$<?php echo htmlspecialchars(number_format((float)$intervencion['costo_estimado'], 2)); ?></p></div>
                        <?php endif; ?>
                    </div>
                </fieldset>
            <?php endif; ?>

            <?php
                // Separar evidencias del reportante (etapa 4) de las del técnico (1,2,3)
                $inst = $reporte['id_institucion'];
                $ev_reportante = [];
                $grupos = ['antes' => [], 'durante' => [], 'despues' => []];
                foreach ($evidencias as $ev) {
                    $et = ModeloEvidencia::id_a_etapa($ev['id_etapa']);
                    if ($et === 'reportante') {
                        $ev_reportante[] = $ev;
                    } elseif ($et && isset($grupos[$et])) {
                        $grupos[$et][] = $ev;
                    }
                }
                $tiene_tecnico = !empty($grupos['antes']) || !empty($grupos['durante']) || !empty($grupos['despues']);
            ?>

            <!-- Sección: Evidencias del Reportante -->
            <fieldset class="det-section">
                <legend><i class="fas fa-user"></i> Evidencias del Reportante</legend>
                <p class="ev-nota">Fotos adjuntadas por quien creó el reporte para mostrar el daño.</p>
                <?php if (empty($ev_reportante)): ?>
                    <p class="sin-evidencias"><i class="fas fa-inbox"></i> El reportante no adjuntó fotos.</p>
                <?php else: ?>
                    <div class="ev-thumbs">
                        <?php foreach ($ev_reportante as $ev): ?>
                            <?php $url = $base . '/?controlador=tecnico&accion=descargar_evidencia&id=' . $ev['id_evidencia'] . '&inst=' . $inst; ?>
                            <a href="<?php echo $url; ?>" target="_blank" class="ev-thumb" title="<?php echo htmlspecialchars($ev['descripcion'] ?? $ev['nombre_archivo_original']); ?>">
                                <img src="<?php echo $url; ?>" alt="Evidencia del reportante" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <!-- Sección: Evidencias del Técnico (reparación) -->
            <fieldset class="det-section">
                <legend><i class="fas fa-screwdriver-wrench"></i> Evidencias del Técnico (reparación)</legend>
                <p class="ev-nota">Fotos del trabajo realizado por el técnico, por etapa.</p>
                <?php if (!$tiene_tecnico): ?>
                    <p class="sin-evidencias"><i class="fas fa-inbox"></i> El técnico aún no ha cargado evidencias de la reparación.</p>
                <?php else: ?>
                    <?php $titulos = ['antes' => 'Antes', 'durante' => 'Durante', 'despues' => 'Después']; ?>
                    <div class="evidencias-etapas">
                        <?php foreach ($titulos as $clave => $titulo): ?>
                            <div class="ev-etapa">
                                <h4><?php echo $titulo; ?> <span>(<?php echo count($grupos[$clave]); ?>)</span></h4>
                                <div class="ev-thumbs">
                                    <?php if (empty($grupos[$clave])): ?>
                                        <span class="ev-vacia">Sin fotos</span>
                                    <?php else: ?>
                                        <?php foreach ($grupos[$clave] as $ev): ?>
                                            <?php $url = $base . '/?controlador=tecnico&accion=descargar_evidencia&id=' . $ev['id_evidencia'] . '&inst=' . $inst; ?>
                                            <a href="<?php echo $url; ?>" target="_blank" class="ev-thumb" title="<?php echo htmlspecialchars($ev['descripcion'] ?? $ev['nombre_archivo_original']); ?>">
                                                <img src="<?php echo $url; ?>" alt="Evidencia <?php echo $titulo; ?>" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <!-- Sección: Seguimiento público -->
            <fieldset class="det-section">
                <legend><i class="fas fa-link"></i> Seguimiento público</legend>
                <p class="seg-info">Comparte este enlace para permitir el seguimiento sin necesidad de iniciar sesión:</p>
                <div class="seg-link-row">
                    <input type="text" readonly value="<?php echo $base; ?>/?controlador=reportes&accion=seguimiento&token=<?php echo htmlspecialchars($reporte['token_seguimiento_publico']); ?>" class="input-modern tracking-link" id="tracking-link">
                    <button type="button" onclick="copiarEnlace()" class="btn-modern"><i class="fas fa-copy"></i> Copiar</button>
                </div>
            </fieldset>

            <!-- Acciones -->
            <div class="det-actions">
                <?php if (($from ?? '') === 'gestion'): ?>
                    <a href="<?php echo $base; ?>/?controlador=gestion&accion=kanban" class="btn-modern-secondary"><i class="fas fa-arrow-left"></i> Volver a Gestión</a>
                <?php else: ?>
                    <a href="<?php echo $base; ?>/?controlador=reportes&accion=listar" class="btn-modern-secondary"><i class="fas fa-arrow-left"></i> Volver a Mis Reportes</a>
                <?php endif; ?>
                <a href="<?php echo $base; ?>/?controlador=reportes&accion=crear" class="btn-modern"><i class="fas fa-plus-circle"></i> Crear Otro Reporte</a>
            </div>
        </div>
    </div>
</div>

<style>
    .detalle-wrapper { max-width: 920px; margin: 36px auto; padding: 20px; }
    .detalle-card { background:#fff; border-radius:14px; box-shadow:0 8px 28px rgba(52,152,219,.12); overflow:hidden; border:1px solid #E6F0F8; }

    /* Encabezado */
    .det-head { display:flex; align-items:center; gap:18px; padding:26px 32px; background:linear-gradient(135deg,#2980B9,#3498DB); color:#fff; }
    .det-head-icon { width:54px; height:54px; flex-shrink:0; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
    .det-head-text { flex:1; }
    .det-head-text h2 { margin:0 0 4px; font-size:23px; font-weight:700; }
    .det-head-text p { margin:0; font-size:13.5px; color:rgba(255,255,255,.9); }
    .ticket-badge { display:inline-flex; align-items:center; gap:4px; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.35); padding:7px 14px; border-radius:30px; font-family:'Courier New',monospace; font-weight:700; font-size:13px; white-space:nowrap; }

    .det-body { padding: 26px 32px 32px; }

    /* Estado/prioridad */
    .estado-row { display:flex; gap:30px; flex-wrap:wrap; margin-bottom:24px; }
    .estado-item { display:flex; flex-direction:column; gap:7px; }
    .estado-label { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#7F8C8D; font-weight:700; }
    .badge { display:inline-flex; align-items:center; padding:7px 16px; border-radius:20px; font-weight:700; font-size:13px; }
    .badge-estado-1 { background:#E3F2FD; color:#1976D2; } .badge-estado-2 { background:#FFF3E0; color:#F57C00; }
    .badge-estado-3 { background:#F3E5F5; color:#8E24AA; } .badge-estado-4 { background:#E8F5E9; color:#2E7D32; }
    .badge-estado-5 { background:#FFF8E1; color:#F39C12; } .badge-estado-6 { background:#E0F2F1; color:#00695C; }
    .badge-estado-7 { background:#FDECEA; color:#C62828; } .badge-estado-8 { background:#ECEFF1; color:#607D8B; }
    .badge-urg-1 { background:#E8F5E9; color:#388E3C; } .badge-urg-2 { background:#FFF3E0; color:#F57C00; }
    .badge-urg-3 { background:#FFE0B2; color:#E65100; } .badge-urg-4 { background:#FFEBEE; color:#C62828; }

    /* Secciones */
    .det-section { border:1px solid #E6EDF2; border-radius:12px; padding:18px 22px; margin:0 0 20px; }
    .det-section legend { padding:0 10px; font-size:13px; font-weight:700; color:#2980B9; text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:8px; }

    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px 24px; }
    .info-item { display:flex; flex-direction:column; gap:4px; }
    .info-label { font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#7F8C8D; font-weight:600; display:flex; align-items:center; gap:6px; }
    .info-label i { color:#3498DB; }
    .info-item p { margin:0; color:#2C3E50; font-size:15px; font-weight:500; }

    .description-box { background:#F8FBFC; padding:16px; border-radius:8px; border-left:4px solid #3498DB; color:#2C3E50; line-height:1.6; }

    .informe-grid { display:grid; gap:14px; }
    .informe-item { background:#F8FBFC; border-left:4px solid #3498DB; border-radius:6px; padding:12px 16px; }
    .informe-item p { margin:4px 0 0; color:#2C3E50; line-height:1.5; }

    .ev-nota { color:#7F8C8D; font-size:13px; margin:0 0 14px; }
    .sin-evidencias { color:#7F8C8D; display:flex; align-items:center; gap:8px; background:#F8FBFC; padding:16px; border-radius:8px; margin:0; }
    .evidencias-etapas { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
    .ev-etapa h4 { color:#2C3E50; font-size:14px; margin:0 0 10px; padding-bottom:6px; border-bottom:2px solid #3498DB; }
    .ev-etapa h4 span { color:#7F8C8D; font-weight:400; }
    .ev-thumbs { display:flex; flex-wrap:wrap; gap:10px; }
    .ev-vacia { color:#B0B7BC; font-size:13px; font-style:italic; }
    .ev-thumb { display:block; width:88px; height:88px; border-radius:8px; overflow:hidden; border:2px solid #E8EDEF; transition:all .2s; }
    .ev-thumb:hover { border-color:#3498DB; transform:scale(1.05); box-shadow:0 4px 12px rgba(52,152,219,.25); }
    .ev-thumb img { width:100%; height:100%; object-fit:cover; display:block; }

    .seg-info { color:#7F8C8D; font-size:13.5px; margin:0 0 10px; }
    .seg-link-row { display:flex; gap:10px; flex-wrap:wrap; }
    .input-modern { flex:1; min-width:220px; padding:11px 14px; border:2px solid #DCE7EF; background:#F8FBFC; border-radius:8px; font-size:13px; box-sizing:border-box; }
    .tracking-link { font-family:'Courier New',monospace; }

    .det-actions { display:flex; gap:14px; margin-top:8px; flex-wrap:wrap; }
    .btn-modern { padding:12px 24px; background:linear-gradient(135deg,#3498DB,#2980B9); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all .3s; }
    .btn-modern:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(52,152,219,.35); }
    .btn-modern-secondary { padding:12px 24px; background:#ECF0F1; color:#2C3E50; border:2px solid #D5DBDB; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all .3s; }
    .btn-modern-secondary:hover { background:#D5DBDB; transform:translateY(-2px); }

    @media (max-width: 700px) {
        .info-grid { grid-template-columns:1fr; }
        .evidencias-etapas { grid-template-columns:1fr; }
        .det-head { flex-wrap:wrap; padding:20px; }
        .det-body { padding:20px; }
        .det-actions { flex-direction:column; }
        .det-actions a { width:100%; }
    }
</style>

<script>
function copiarEnlace() {
    const input = document.getElementById('tracking-link');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        if (typeof toast !== 'undefined' && toast) { toast.success('Copiado', 'Enlace copiado al portapapeles.'); }
        else { alert('Enlace copiado al portapapeles'); }
    });
}
</script>
