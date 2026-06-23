<?php
$estados = [
    ESTADO_REGISTRADO   => ['Registrado', 'est-1'],
    ESTADO_ASIGNADO     => ['Asignado', 'est-2'],
    ESTADO_EN_PROCESO   => ['En Proceso', 'est-3'],
    ESTADO_SOLUCIONADO  => ['Solucionado', 'est-4'],
    ESTADO_EN_VALIDACION=> ['En Validación', 'est-5'],
    ESTADO_DEVUELTO     => ['Devuelto', 'est-6'],
    ESTADO_CERRADO      => ['Cerrado', 'est-7'],
    ESTADO_ANULADO      => ['Anulado', 'est-8'],
];
$urgencias = [
    1 => ['No urgente', 'urg-1'],
    2 => ['Moderado', 'urg-2'],
    3 => ['Importante', 'urg-3'],
    4 => ['Urgente', 'urg-4'],
];
$csrf = $csrf_token ?? (class_exists('Validacion') ? Validacion::generar_csrf_token() : '');
?>
<div class="container tec-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-toolbox"></i></div>
        <div class="page-banner__text">
            <h2>Mis Asignaciones</h2>
            <p>Reportes de daños asignados a ti, ordenados por fecha.</p>
        </div>
    </div>

    <div class="tec-content">
        <?php if (empty($reportes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <p>No tienes reportes asignados en este momento.</p>
            </div>
        <?php else: ?>
            <div class="tec-list">
                <div class="tec-header-row">
                    <div class="th-info"><span><strong>Ticket / Problema</strong></span><span class="header-secondary">Ubicación y fecha</span></div>
                    <div class="th-estado"><strong>Estado</strong></div>
                    <div class="th-actions"><strong>Acciones</strong></div>
                </div>
                <?php foreach ($reportes as $r): ?>
                    <?php
                        $est = $estados[$r['id_estado']] ?? ['Estado ' . $r['id_estado'], 'est-1'];
                        $urg = $urgencias[$r['id_urgencia_calculada']] ?? ['—', 'urg-1'];
                        $puede_solucionar = in_array($r['id_estado'], [ESTADO_EN_PROCESO, ESTADO_DEVUELTO]);
                    ?>
                    <div class="tec-row">
                        <div class="tec-info">
                            <div class="tec-titulo">
                                <strong><?php echo htmlspecialchars($r['numero_ticket']); ?></strong>
                                <span class="badge-urgencia <?php echo $urg[1]; ?>"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($urg[0]); ?></span>
                            </div>
                            <div class="tec-desc"><?php echo htmlspecialchars(mb_strimwidth($r['descripcion_problema'], 0, 90, '…')); ?></div>
                            <div class="tec-meta">
                                <?php if (!empty($r['referencia_ubicacion_libre'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($r['referencia_ubicacion_libre']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['fecha_hora_registro']))); ?></span>
                            </div>
                        </div>

                        <div class="tec-estado">
                            <span class="badge-estado <?php echo $est[1]; ?>"><?php echo htmlspecialchars($est[0]); ?></span>
                        </div>

                        <div class="tec-actions">
                            <a href="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=crear_intervension&id=<?php echo $r['id_reporte']; ?>" class="btn-accion btn-atender" title="Registrar / continuar intervención">
                                <i class="fas fa-screwdriver-wrench"></i> Atender
                            </a>
                            <?php if ($puede_solucionar): ?>
                                <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=tecnico&accion=marcar_solucionado" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="id_reporte" value="<?php echo $r['id_reporte']; ?>">
                                    <button type="submit" class="btn-accion btn-solucionar" onclick="return confirm('¿Marcar como solucionado? Requiere evidencia de las 3 etapas.')" title="Marcar como solucionado">
                                        <i class="fas fa-check-double"></i> Solucionado
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>

<style>
:root{--primary-blue:#3498DB;--dark-blue:#2980B9;--gray-text:#7F8C8D;--dark-text:#2C3E50;--light-bg:#F8FBFC;}
.tec-container{max-width:1200px;margin:30px auto;padding:20px;}
.tec-header{margin-bottom:35px;}
.tec-header h2{font-size:32px;font-weight:700;color:var(--dark-text);margin:0 0 8px;display:flex;align-items:center;gap:12px;}
.tec-header h2 i{color:var(--primary-blue);}
.subtitle{color:var(--gray-text);font-size:14px;margin:0;font-weight:500;}
.tec-list{background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,152,219,.08);overflow:hidden;}
.tec-header-row{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:linear-gradient(135deg,rgba(52,152,219,.08),rgba(41,128,185,.08));border-bottom:2px solid var(--primary-blue);font-weight:600;color:var(--dark-text);font-size:12px;text-transform:uppercase;letter-spacing:.5px;}
.th-info{display:flex;flex-direction:column;gap:4px;flex:1;}
.header-secondary{font-weight:400!important;color:var(--gray-text);font-size:11px;text-transform:none;letter-spacing:normal;}
.th-estado{margin:0 20px;}
.th-actions{min-width:200px;text-align:center;}
.tec-row{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #E8EDEF;transition:background .2s;}
.tec-row:last-child{border-bottom:none;}
.tec-row:hover{background:rgba(52,152,219,.03);}
.tec-info{display:flex;flex-direction:column;gap:6px;flex:1;}
.tec-titulo{display:flex;align-items:center;gap:12px;font-size:14px;color:var(--dark-text);}
.tec-desc{font-size:13px;color:var(--dark-text);}
.tec-meta{display:flex;gap:16px;font-size:12px;color:var(--gray-text);}
.tec-meta i{color:var(--primary-blue);font-size:11px;}
.tec-estado{margin:0 20px;}
.tec-actions{display:flex;gap:8px;min-width:200px;justify-content:flex-end;flex-wrap:wrap;}
.badge-urgencia{padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;}
.badge-urgencia i{font-size:10px;}
.urg-1{background:rgba(39,174,96,.15);color:#27AE60;}
.urg-2{background:rgba(243,156,18,.15);color:#F39C12;}
.urg-3{background:rgba(230,126,34,.18);color:#E67E22;}
.urg-4{background:rgba(231,76,60,.15);color:#E74C3C;}
.badge-estado{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap;}
.est-1{background:rgba(127,140,141,.15);color:#7F8C8D;}
.est-2{background:rgba(52,152,219,.15);color:#3498DB;}
.est-3{background:rgba(41,128,185,.15);color:#2980B9;}
.est-4{background:rgba(39,174,96,.15);color:#27AE60;}
.est-5{background:rgba(155,89,182,.15);color:#8E44AD;}
.est-6{background:rgba(230,126,34,.15);color:#E67E22;}
.est-7{background:rgba(44,62,80,.12);color:#2C3E50;}
.est-8{background:rgba(231,76,60,.15);color:#E74C3C;}
.btn-accion{padding:8px 14px;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:all .3s;text-transform:uppercase;letter-spacing:.3px;}
.btn-atender{background:rgba(52,152,219,.12);color:var(--primary-blue);}
.btn-atender:hover{background:var(--primary-blue);color:#fff;transform:translateY(-2px);}
.btn-solucionar{background:rgba(39,174,96,.12);color:#27AE60;}
.btn-solucionar:hover{background:#27AE60;color:#fff;transform:translateY(-2px);}
.empty-state{text-align:center;padding:60px 40px;background:#fff;border-radius:12px;color:var(--gray-text);box-shadow:0 2px 8px rgba(0,0,0,.05);}
.empty-state i{font-size:48px;margin-bottom:16px;opacity:.5;display:block;}
@media(max-width:768px){.tec-header h2{font-size:24px;}.tec-row{flex-direction:column;align-items:flex-start;gap:12px;}.tec-actions{justify-content:flex-start;}}
</style>
