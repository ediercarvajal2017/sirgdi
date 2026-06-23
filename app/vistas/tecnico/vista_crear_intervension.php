<?php
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
$ahora = date('Y-m-d\TH:i');
?>
<div class="container int-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-screwdriver-wrench"></i></div>
        <div class="page-banner__text">
            <h2>Registrar Intervención</h2>
            <p>Documenta la atención técnica del reporte <strong><?php echo htmlspecialchars($reporte['numero_ticket']); ?></strong>.</p>
        </div>
    </div>

    <!-- RESUMEN DEL REPORTE -->
    <div class="reporte-resumen">
        <div class="rr-item"><span class="rr-label"><i class="fas fa-ticket"></i> Ticket</span><span class="rr-value"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></span></div>
        <?php if (!empty($reporte['referencia_ubicacion_libre'])): ?>
            <div class="rr-item"><span class="rr-label"><i class="fas fa-map-marker-alt"></i> Ubicación</span><span class="rr-value"><?php echo htmlspecialchars($reporte['referencia_ubicacion_libre']); ?></span></div>
        <?php endif; ?>
        <div class="rr-item rr-full"><span class="rr-label"><i class="fas fa-comment-dots"></i> Problema reportado</span><span class="rr-value"><?php echo htmlspecialchars($reporte['descripcion_problema']); ?></span></div>
    </div>

    <?php if (!empty($intervension_existente)): ?>
        <!-- YA EXISTE INFORME -->
        <div class="form-modern-card">
            <h3><i class="fas fa-circle-info"></i> Informe ya registrado</h3>
            <p style="color:#7F8C8D;margin-bottom:20px;">Este reporte ya tiene un informe de intervención. Puedes continuar cargando las evidencias fotográficas.</p>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=cargar_evidencia&id_intervension=<?php echo $intervension_existente['id_informe']; ?>" class="btn-modern" style="text-decoration:none;max-width:320px;">
                <i class="fas fa-camera"></i> Cargar Evidencias
            </a>
        </div>
    <?php else: ?>
        <!-- FORMULARIO -->
        <div class="form-modern-card">
            <h3><i class="fas fa-file-pen"></i> Informe de Intervención</h3>
            <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=crear_intervension" id="form-int">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="id_reporte" value="<?php echo $reporte['id_reporte']; ?>">

                <div class="form-group">
                    <label for="descripcion_actividades"><i class="fas fa-list-check"></i> Descripción de actividades realizadas: <span class="required">*</span></label>
                    <textarea name="descripcion_actividades" id="descripcion_actividades" rows="4" minlength="20" required class="input-modern" placeholder="Describe en detalle qué trabajos realizaste (mínimo 20 caracteres)…"></textarea>
                </div>

                <div class="form-group">
                    <label for="causa_raiz"><i class="fas fa-magnifying-glass"></i> Causa raíz identificada:</label>
                    <textarea name="causa_raiz" id="causa_raiz" rows="2" class="input-modern" placeholder="¿Qué originó el daño? (opcional)"></textarea>
                </div>

                <div class="form-group">
                    <label for="solucion_implementada"><i class="fas fa-wrench"></i> Solución implementada:</label>
                    <textarea name="solucion_implementada" id="solucion_implementada" rows="3" class="input-modern" placeholder="¿Cómo se resolvió? (opcional)"></textarea>
                </div>

                <div class="form-group">
                    <label for="materiales"><i class="fas fa-boxes-stacked"></i> Materiales utilizados:</label>
                    <textarea name="materiales" id="materiales" rows="3" class="input-modern" placeholder="Un material por línea. Ej:&#10;Tornillos x4&#10;Cable 2m&#10;Cinta aislante"></textarea>
                    <small class="hint">Escribe un material por línea (sin control de stock — RF-17).</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_hora_inicio"><i class="fas fa-play"></i> Inicio de la intervención: <span class="required">*</span></label>
                        <input type="datetime-local" name="fecha_hora_inicio" id="fecha_hora_inicio" value="<?php echo $ahora; ?>" required class="input-modern">
                    </div>
                    <div class="form-group">
                        <label for="fecha_hora_fin"><i class="fas fa-flag-checkered"></i> Fin de la intervención:</label>
                        <input type="datetime-local" name="fecha_hora_fin" id="fecha_hora_fin" class="input-modern">
                    </div>
                </div>

                <div class="form-group">
                    <label for="costo_estimado"><i class="fas fa-dollar-sign"></i> Costo estimado (opcional):</label>
                    <input type="number" name="costo_estimado" id="costo_estimado" min="0" step="0.01" placeholder="0.00" class="input-modern" value="">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-modern"><i class="fas fa-arrow-right"></i> Guardar y Cargar Evidencias</button>
                    <a href="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=mis_asignaciones" class="btn-modern-secondary" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php $toast_exito_msg = 'Intervención guardada. Ahora carga las evidencias.'; require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
:root{--primary-blue:#3498DB;--dark-blue:#2980B9;--gray-text:#7F8C8D;--dark-text:#2C3E50;--light-bg:#F8FBFC;}
.int-container{max-width:900px;margin:30px auto;padding:20px;}
.int-header{margin-bottom:25px;}
.int-header h2{font-size:30px;font-weight:700;color:var(--dark-text);margin:0 0 8px;display:flex;align-items:center;gap:12px;}
.int-header h2 i{color:var(--primary-blue);}
.subtitle{color:var(--gray-text);font-size:14px;margin:0;font-weight:500;}
.reporte-resumen{background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-left:4px solid var(--primary-blue);margin-bottom:25px;display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.rr-item{display:flex;flex-direction:column;gap:3px;}
.rr-full{grid-column:1/-1;}
.rr-label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-text);font-weight:600;display:flex;align-items:center;gap:6px;}
.rr-label i{color:var(--primary-blue);}
.rr-value{font-size:14px;color:var(--dark-text);}
.form-modern-card{background:#fff;border-radius:12px;padding:30px;box-shadow:0 4px 16px rgba(52,152,219,.08);border-left:4px solid var(--primary-blue);}
.form-modern-card h3{margin-bottom:22px;color:var(--dark-text);font-size:20px;display:flex;align-items:center;gap:10px;}
.form-modern-card h3 i{color:var(--primary-blue);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.form-group{display:flex;flex-direction:column;margin-bottom:20px;}
.form-group label{font-weight:600;margin-bottom:10px;color:var(--dark-text);font-size:14px;display:flex;align-items:center;gap:8px;}
.form-group label i{color:var(--primary-blue);font-size:14px;}
.form-group .required{color:#E74C3C;font-weight:700;}
.hint{margin-top:6px;font-size:12px;color:var(--gray-text);}
.input-modern{padding:12px 15px;border:2px solid var(--primary-blue);background:var(--light-bg);border-radius:8px;font-family:inherit;font-size:14px;transition:all .3s;box-sizing:border-box;width:100%;resize:vertical;}
.input-modern:focus{outline:none;border-color:var(--dark-blue);background:#fff;box-shadow:0 0 0 4px rgba(52,152,219,.1);}
.form-actions{display:flex;gap:15px;margin-top:10px;flex-wrap:wrap;}
.btn-modern{flex:1;min-width:200px;padding:12px 24px;background:linear-gradient(135deg,var(--primary-blue),var(--dark-blue));color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(52,152,219,.4);}
.btn-modern-secondary{flex:1;min-width:150px;padding:12px 24px;background:#ECF0F1;color:var(--dark-text);border:2px solid var(--primary-blue);border-radius:8px;cursor:pointer;font-weight:600;text-transform:uppercase;font-size:14px;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-modern-secondary:hover{background:var(--gray-text);color:#fff;transform:translateY(-2px);}
@media(max-width:768px){.reporte-resumen,.form-row{grid-template-columns:1fr;}.int-header h2{font-size:24px;}.form-actions{flex-direction:column;}.btn-modern,.btn-modern-secondary{flex:none;width:100%;}}
</style>
