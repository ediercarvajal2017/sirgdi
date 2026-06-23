<!-- RF-10: Kanban Board para Gestión de Reportes -->
<div class="container-kanban">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-tasks"></i></div>
        <div class="page-banner__text">
            <h2>Gestión de Reportes</h2>
            <p>Administra y cambia el estado de los reportes de daños.</p>
        </div>
    </div>

    <!-- Tabla de Reportes -->
    <?php
        // Aplanar todas las columnas en una sola lista de filas
        $filas = [];
        foreach ($columnas as $id_estado => $reportes) {
            foreach ($reportes as $item) {
                $item['_id_estado'] = $id_estado;
                $filas[] = $item;
            }
        }
        $estados_no_asignables = [ESTADO_SOLUCIONADO, ESTADO_EN_VALIDACION, ESTADO_CERRADO, ESTADO_ANULADO];
        $clases_estado = [
            ESTADO_REGISTRADO => 'est-reg', ESTADO_ASIGNADO => 'est-asig', ESTADO_EN_PROCESO => 'est-proc',
            ESTADO_SOLUCIONADO => 'est-sol', ESTADO_EN_VALIDACION => 'est-val', ESTADO_DEVUELTO => 'est-dev',
            ESTADO_CERRADO => 'est-cer', ESTADO_ANULADO => 'est-anu',
        ];
    ?>

    <div class="tabla-toolbar">
        <div class="buscador">
            <i class="fas fa-search"></i>
            <input type="text" id="buscar-reportes" placeholder="Buscar por ticket, descripción o estado…" onkeyup="filtrarTabla()">
        </div>
        <div class="filtro-estado-wrap">
            <i class="fas fa-filter"></i>
            <select id="filtro-estado" onchange="filtrarTabla()">
                <option value="">Todos los estados</option>
                <?php foreach ($estados_nombres as $id_est => $nombre_est): ?>
                    <?php if (!empty($columnas[$id_est])): ?>
                        <option value="<?php echo intval($id_est); ?>"><?php echo htmlspecialchars($nombre_est); ?> (<?php echo count($columnas[$id_est]); ?>)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <span class="total-reportes"><strong id="contador-visible"><?php echo count($filas); ?></strong> reportes</span>
    </div>

    <div class="tabla-wrap">
        <table class="tabla-reportes" id="tabla-reportes">
            <thead>
                <tr>
                    <th class="th-sort" onclick="ordenarTabla(this,0,'text')">Ticket <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarTabla(this,1,'num')">Estado <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarTabla(this,2,'num')">Urgencia <i class="fas fa-sort"></i></th>
                    <th class="th-center th-sort" title="Puntuación de prioridad" onclick="ordenarTabla(this,3,'num')">Prior. <i class="fas fa-sort"></i></th>
                    <th>Descripción</th>
                    <th class="th-sort" onclick="ordenarTabla(this,5,'num')">SLA <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarTabla(this,6,'text')">Técnico <i class="fas fa-sort"></i></th>
                    <th class="th-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filas)): ?>
                    <tr><td colspan="8" class="fila-vacia"><i class="fas fa-inbox"></i> No hay reportes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($filas as $item): ?>
                        <?php
                            $r = $item['reporte'];
                            $id_estado = $item['_id_estado'];
                            $color_urg = $colores_urgencia[$r['id_urgencia_calculada']] ?? '#999';
                            $tiene_tecnico = !empty($r['id_tecnico_asignado']);
                            $bloquear_asignar = in_array($r['id_estado'], $estados_no_asignables);
                            $sla = $item['sla_info']['estado_sla'];
                            $horas = round($item['sla_info']['horas_restantes'], 1);
                        ?>
                        <tr class="fila-reporte">
                            <td class="td-ticket"><?php echo htmlspecialchars($r['numero_ticket']); ?></td>
                            <td data-sort="<?php echo intval($id_estado); ?>">
                                <span class="badge-estado <?php echo $clases_estado[$id_estado] ?? ''; ?>">
                                    <?php echo htmlspecialchars($estados_nombres[$id_estado]); ?>
                                </span>
                            </td>
                            <td data-sort="<?php echo intval($r['id_urgencia_calculada']); ?>">
                                <span class="badge-urg" style="background-color: <?php echo htmlspecialchars($color_urg); ?>20; color: <?php echo htmlspecialchars($color_urg); ?>;">
                                    <i class="fas fa-circle" style="font-size:8px;"></i>
                                    <?php echo intval($r['id_urgencia_calculada']) == 4 ? 'Urgente' : (intval($r['id_urgencia_calculada']) == 3 ? 'Importante' : (intval($r['id_urgencia_calculada']) == 2 ? 'Moderado' : 'No urgente')); ?>
                                </span>
                            </td>
                            <td class="td-center" data-sort="<?php echo intval($item['puntuacion_prioridad']); ?>"><span class="prioridad-score"><?php echo intval($item['puntuacion_prioridad']); ?></span></td>
                            <td class="td-desc" title="<?php echo htmlspecialchars($r['descripcion_problema']); ?>">
                                <?php echo htmlspecialchars(mb_strimwidth($r['descripcion_problema'], 0, 70, '…')); ?>
                            </td>
                            <td data-sort="<?php echo $sla === 'vencido' ? -9999 : $horas; ?>">
                                <span class="sla-pill sla-<?php echo $sla; ?>">
                                    <?php echo $sla === 'vencido' ? 'Vencido' : $horas . 'h'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($tiene_tecnico): ?>
                                    <span class="tec-ok"><i class="fas fa-user-check"></i> Asignado</span>
                                <?php elseif (!$bloquear_asignar): ?>
                                    <button class="btn-tabla btn-asignar" onclick="abrirAsignacion(<?php echo $r['id_reporte']; ?>)">
                                        <i class="fas fa-user-plus"></i> Asignar
                                    </button>
                                <?php else: ?>
                                    <span class="tec-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-center td-acciones">
                                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=detalle&id=<?php echo $r['id_reporte']; ?>" class="btn-icono" target="_blank" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($r['id_estado'] !== ESTADO_CERRADO && $r['id_estado'] !== ESTADO_ANULADO): ?>
                                    <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=cambiar_estado&id=<?php echo $r['id_reporte']; ?>" class="btn-icono btn-icono-estado" title="Cambiar estado">
                                        <i class="fas fa-exchange-alt"></i>
                                    </a>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=eliminar_reporte" style="display:inline;" onsubmit="return confirm('¿Eliminar el reporte <?php echo htmlspecialchars($r['numero_ticket'], ENT_QUOTES); ?>?\n\nEsta acción es permanente y borrará también sus evidencias, informes y comentarios.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? Validacion::generar_csrf_token()); ?>">
                                    <input type="hidden" name="id_reporte" value="<?php echo $r['id_reporte']; ?>">
                                    <button type="submit" class="btn-icono btn-icono-eliminar" title="Eliminar reporte">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Asignación -->
<div id="modal-asignacion" class="modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" onclick="cerrarAsignacion()">
            <i class="fas fa-times"></i>
        </button>
        <h3><i class="fas fa-user-tie"></i> Asignar Técnico</h3>

        <form id="form-asignacion" method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=asignar_tecnico">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? Validacion::generar_csrf_token()); ?>">
            <input type="hidden" name="id_reporte" id="id_reporte_asignar">

            <div class="form-group">
                <label for="id_tecnico"><i class="fas fa-user-hard-hat"></i> Técnico</label>
                <select id="id_tecnico" name="id_tecnico" required class="form-control input-select" onchange="cargarCargaTecnico()">
                    <option value="">-- Seleccionar Técnico --</option>
                </select>
            </div>

            <div class="form-group" id="carga-tecnico" style="display:none;">
                <p class="carga-info">Reportes activos: <strong id="carga-numero">0</strong></p>
            </div>

            <button type="submit" class="btn btn-primary">Asignar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarAsignacion()">Cancelar</button>
        </form>
    </div>
</div>

<style>
    /* ===== TABLA DE REPORTES ===== */
    .tabla-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
    .buscador { position:relative; flex:1; max-width:420px; }
    .buscador i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#7F8C8D; font-size:14px; }
    .buscador input { width:100%; padding:11px 14px 11px 40px; border:2px solid #3498DB; border-radius:8px; font-size:14px; background:#F8FBFC; box-sizing:border-box; }
    .buscador input:focus { outline:none; border-color:#2980B9; background:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.1); }
    .filtro-estado-wrap { position:relative; display:flex; align-items:center; }
    .filtro-estado-wrap i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#7F8C8D; font-size:13px; pointer-events:none; }
    .filtro-estado-wrap select { appearance:none; -webkit-appearance:none; padding:11px 36px 11px 36px; border:2px solid #3498DB; border-radius:8px; font-size:14px; background:#F8FBFC; color:#2C3E50; cursor:pointer; background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237F8C8D' stroke-width='3'><polyline points='6 9 12 15 18 9'/></svg>"); background-repeat:no-repeat; background-position:right 12px center; }
    .filtro-estado-wrap select:focus { outline:none; border-color:#2980B9; background-color:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.1); }
    .total-reportes { color:#7F8C8D; font-size:14px; }
    .total-reportes strong { color:#2C3E50; }

    .tabla-wrap { background:#fff; border-radius:12px; box-shadow:0 4px 16px rgba(52,152,219,.08); overflow:auto; max-height:72vh; }
    .tabla-reportes { width:100%; border-collapse:collapse; font-size:13px; }
    .tabla-reportes thead th {
        position:sticky; top:0; z-index:2; background:linear-gradient(135deg,#EBF5FB,#D6EAF8);
        color:#2C3E50; text-align:left; padding:13px 14px; font-size:12px; text-transform:uppercase;
        letter-spacing:.4px; border-bottom:2px solid #3498DB; white-space:nowrap;
    }
    .tabla-reportes th.th-center { text-align:center; }
    .tabla-reportes th.th-sort { cursor:pointer; user-select:none; transition:background .2s; }
    .tabla-reportes th.th-sort:hover { background:#D6EAF8; }
    .tabla-reportes th.th-sort i { margin-left:5px; font-size:11px; color:#3498DB; opacity:.7; }
    .tabla-reportes tbody td { padding:12px 14px; border-bottom:1px solid #ECF0F1; color:#2C3E50; vertical-align:middle; }
    .tabla-reportes tbody tr:hover { background:#F8FBFC; }
    .td-center { text-align:center; }
    .td-ticket { font-family:'Courier New',monospace; font-weight:700; color:#2980B9; white-space:nowrap; }
    .td-desc { color:#566573; max-width:280px; }
    .fila-vacia { text-align:center; padding:40px!important; color:#7F8C8D; }
    .fila-vacia i { margin-right:8px; opacity:.5; }

    .badge-estado { display:inline-block; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600; white-space:nowrap; }
    .est-reg{background:rgba(127,140,141,.15);color:#7F8C8D;} .est-asig{background:rgba(52,152,219,.15);color:#3498DB;}
    .est-proc{background:rgba(41,128,185,.15);color:#2980B9;} .est-sol{background:rgba(39,174,96,.15);color:#27AE60;}
    .est-val{background:rgba(155,89,182,.15);color:#8E44AD;} .est-dev{background:rgba(230,126,34,.15);color:#E67E22;}
    .est-cer{background:rgba(44,62,80,.12);color:#2C3E50;} .est-anu{background:rgba(231,76,60,.15);color:#E74C3C;}

    .badge-urg { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600; white-space:nowrap; }
    .prioridad-score { display:inline-block; min-width:28px; padding:3px 8px; background:#2C3E50; color:#fff; border-radius:10px; font-weight:700; font-size:12px; }

    .sla-pill { display:inline-block; padding:4px 9px; border-radius:10px; font-size:11px; font-weight:700; }
    .sla-en_tiempo{background:rgba(39,174,96,.15);color:#27AE60;} .sla-cerca{background:rgba(243,156,18,.18);color:#E67E22;}
    .sla-vencido{background:rgba(231,76,60,.15);color:#E74C3C;}

    .tec-ok { color:#27AE60; font-weight:600; font-size:12px; white-space:nowrap; }
    .tec-na { color:#B0B7BC; }
    .btn-tabla { border:none; border-radius:6px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; white-space:nowrap; }
    .btn-asignar { background:rgba(52,152,219,.12); color:#3498DB; }
    .btn-asignar:hover { background:#3498DB; color:#fff; }

    .td-acciones { white-space:nowrap; }
    .btn-icono { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:6px; background:#ECF0F1; color:#2C3E50; text-decoration:none; margin:0 2px; transition:all .2s; }
    .btn-icono:hover { background:#3498DB; color:#fff; transform:translateY(-2px); }
    .btn-icono-estado:hover { background:#E67E22; }
    .btn-icono-eliminar { border:none; cursor:pointer; padding:0; font-size:14px; color:#E74C3C; background:#FDEDEC; }
    .btn-icono-eliminar:hover { background:#E74C3C; color:#fff; transform:translateY(-2px); }

    @media(max-width:768px){ .tabla-wrap{ max-height:none; } .td-desc{ max-width:140px; } }

    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
        --light-gray: #ECF0F1;
    }

    .container-kanban {
        padding: 30px 20px;
        max-width: 100%;
    }

    .gestion-header {
        margin-bottom: 35px;
    }

    .gestion-header h2 {
        font-size: 32px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .gestion-header h2 i {
        color: var(--primary-blue);
        font-size: 32px;
    }

    .header-subtitle {
        color: var(--gray-text);
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }

    /* Estadísticas */
    .stats-bar {
        display: flex;
        gap: 16px;
        margin-bottom: 35px;
        overflow-x: auto;
        padding: 10px 0;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(52, 152, 219, 0.08);
        min-width: 140px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        border-left: 4px solid var(--primary-blue);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(52, 152, 219, 0.15);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        background: rgba(52, 152, 219, 0.15);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: var(--primary-blue);
    }

    .stat-card.stat-critical {
        border-left-color: #E74C3C;
    }

    .stat-card.stat-critical .stat-icon {
        background: rgba(231, 76, 60, 0.15);
        color: #E74C3C;
    }

    .stat-card.stat-warning {
        border-left-color: #F39C12;
    }

    .stat-card.stat-warning .stat-icon {
        background: rgba(243, 156, 18, 0.15);
        color: #F39C12;
    }

    .stat-card.stat-error {
        border-left-color: #C0392B;
    }

    .stat-card.stat-error .stat-icon {
        background: rgba(192, 57, 43, 0.15);
        color: #C0392B;
    }

    .stat-card.stat-pending {
        border-left-color: var(--gray-text);
    }

    .stat-card.stat-pending .stat-icon {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
    }

    .stat-label {
        display: block;
        font-size: 12px;
        color: var(--gray-text);
        margin: 0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .stat-value {
        display: block;
        font-size: 32px;
        font-weight: 700;
        color: var(--primary-blue);
        margin: 0;
    }

    /* Kanban Board */
    .kanban-board {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .kanban-column {
        background-color: var(--light-gray);
        border-radius: 12px;
        padding: 16px;
        min-height: 500px;
        border: 2px solid #E8EDEF;
    }

    .column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--primary-blue);
    }

    .column-header h3 {
        margin: 0;
        color: var(--dark-text);
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .column-count {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        min-width: 32px;
        text-align: center;
    }

    .column-cards {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .empty-column {
        text-align: center;
        color: var(--gray-text);
        padding: 60px 20px;
        font-size: 14px;
        font-weight: 500;
    }

    /* Tarjetas */
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
        padding: 14px;
        cursor: move;
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary-blue);
        border-top: 2px solid var(--primary-blue);
    }

    .card:hover {
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.15);
        transform: translateY(-3px);
    }

    .card-priority {
        height: 3px;
        margin: -14px -14px 10px -14px;
        border-radius: 6px 6px 0 0;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        gap: 8px;
    }

    .ticket-badge {
        background: var(--light-bg);
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        color: var(--primary-blue);
        font-family: 'Monaco', 'Courier New', monospace;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .priority-score {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
    }

    .card-body {
        margin-bottom: 12px;
    }

    .card-description {
        font-size: 13px;
        color: var(--dark-text);
        margin: 0 0 10px 0;
        line-height: 1.5;
        font-weight: 500;
    }

    .card-meta {
        font-size: 12px;
        color: var(--gray-text);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .meta-item {
        background: rgba(52, 152, 219, 0.1);
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
    }

    .card-sla {
        background: rgba(52, 152, 219, 0.08);
        padding: 10px;
        border-radius: 6px;
        font-size: 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-left: 3px solid var(--primary-blue);
        font-weight: 600;
    }

    .sla-icon {
        font-size: 16px;
        color: var(--primary-blue);
    }

    .sla-info {
        font-weight: 700;
        color: var(--primary-blue);
    }

    .sla-info.vencido {
        color: #E74C3C;
    }

    .sla-info.vencido ~ .sla-icon {
        color: #E74C3C;
    }

    .sla-info.cerca {
        color: #F39C12;
    }

    .sla-info.cerca ~ .sla-icon {
        color: #F39C12;
    }

    .sla-info.en_tiempo {
        color: #27AE60;
    }

    .sla-info.en_tiempo ~ .sla-icon {
        color: #27AE60;
    }

    .card-assignment {
        margin-bottom: 10px;
    }

    .assigned-to {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #27AE60;
        background: rgba(39, 174, 96, 0.15);
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
    }

    .btn-assign {
        width: 100%;
        padding: 8px;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-assign:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
    }

    .card-actions {
        display: flex;
        gap: 6px;
        flex-direction: column;
    }

    .btn-small {
        padding: 7px 10px;
        background: rgba(52, 152, 219, 0.15);
        color: var(--primary-blue);
        text-decoration: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }

    .btn-small:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-1px);
    }

    /* Modal */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(2px);
    }

    .modal-content {
        background: white;
        padding: 35px;
        border-radius: 12px;
        width: 90%;
        max-width: 450px;
        position: relative;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(128, 139, 150, 0.15);
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: var(--gray-text);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .modal-close:hover {
        background: var(--gray-text);
        color: white;
    }

    .modal-content h3 {
        margin: 0 0 24px 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--dark-text);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--primary-blue);
        border-radius: 8px;
        font-size: 14px;
        background-color: var(--light-bg);
        color: var(--dark-text);
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--dark-blue);
        background-color: white;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .carga-info {
        background: rgba(52, 152, 219, 0.15);
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        margin: 12px 0;
        border-left: 3px solid var(--primary-blue);
        font-weight: 600;
        color: var(--primary-blue);
    }

    .btn {
        padding: 11px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        margin-right: 8px;
        margin-top: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
    }

    .btn-secondary {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
    }

    .btn-secondary:hover {
        background: var(--gray-text);
        color: white;
        transform: translateY(-2px);
    }

    @media (max-width: 1024px) {
        .stats-bar {
            gap: 12px;
        }

        .stat-card {
            min-width: 120px;
        }
    }

    @media (max-width: 768px) {
        .container-kanban {
            padding: 20px 10px;
        }

        .gestion-header h2 {
            font-size: 24px;
        }

        .stats-bar {
            gap: 8px;
            flex-direction: row;
            overflow-x: auto;
        }

        .stat-card {
            min-width: 100px;
            padding: 14px;
        }

        .stat-label {
            font-size: 11px;
        }

        .stat-value {
            font-size: 24px;
        }

        .kanban-board {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .kanban-column {
            min-height: 300px;
        }

        .card {
            padding: 12px;
        }

        .card-actions {
            flex-direction: row;
            gap: 8px;
        }

        .btn-small {
            flex: 1;
            padding: 6px;
            font-size: 11px;
        }

        .modal-content {
            width: 95%;
            max-width: 100%;
            padding: 25px;
        }
    }
</style>

<script>
const apiBase = '<?php echo config("app.url_base"); ?>';

// Búsqueda/filtrado en vivo de la tabla (texto + estado)
function filtrarTabla() {
    const q = document.getElementById('buscar-reportes').value.toLowerCase().trim();
    const estadoSel = document.getElementById('filtro-estado').value;
    const filas = document.querySelectorAll('#tabla-reportes tbody tr.fila-reporte');
    let visibles = 0;
    filas.forEach(function(tr) {
        const texto = tr.textContent.toLowerCase();
        const coincideTexto = q === '' || texto.indexOf(q) !== -1;
        // La celda de estado es la columna índice 1 (data-sort = id_estado)
        const celdaEstado = tr.children[1];
        const idEstado = celdaEstado ? celdaEstado.getAttribute('data-sort') : '';
        const coincideEstado = estadoSel === '' || idEstado === estadoSel;
        const mostrar = coincideTexto && coincideEstado;
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    const cont = document.getElementById('contador-visible');
    if (cont) cont.textContent = visibles;
}

// Ordenar la tabla al hacer clic en un encabezado
let ordenActual = { col: null, asc: true };
function ordenarTabla(th, colIndex, tipo) {
    const tbody = document.querySelector('#tabla-reportes tbody');
    const filas = Array.from(tbody.querySelectorAll('tr.fila-reporte'));
    if (filas.length === 0) return;

    // Alternar dirección si es la misma columna
    const asc = (ordenActual.col === colIndex) ? !ordenActual.asc : true;
    ordenActual = { col: colIndex, asc: asc };

    filas.sort(function(a, b) {
        const celdaA = a.children[colIndex];
        const celdaB = b.children[colIndex];
        let va, vb;
        if (tipo === 'num') {
            va = parseFloat(celdaA.getAttribute('data-sort'));
            vb = parseFloat(celdaB.getAttribute('data-sort'));
            if (isNaN(va)) va = 0; if (isNaN(vb)) vb = 0;
        } else {
            va = celdaA.textContent.trim().toLowerCase();
            vb = celdaB.textContent.trim().toLowerCase();
        }
        if (va < vb) return asc ? -1 : 1;
        if (va > vb) return asc ? 1 : -1;
        return 0;
    });

    // Reinsertar en el nuevo orden
    filas.forEach(function(f) { tbody.appendChild(f); });

    // Actualizar iconos de los encabezados
    document.querySelectorAll('#tabla-reportes thead th.th-sort i').forEach(function(ic) {
        ic.className = 'fas fa-sort';
    });
    const icono = th.querySelector('i');
    if (icono) icono.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
}

function abrirAsignacion(idReporte) {
    document.getElementById('id_reporte_asignar').value = idReporte;
    document.getElementById('modal-asignacion').style.display = 'flex';

    // Cargar técnicos disponibles
    fetch(apiBase + '/?controlador=gestion&accion=obtener_tecnicos_json')
        .then(r => r.json())
        .then(tecnicos => {
            const select = document.getElementById('id_tecnico');
            select.innerHTML = '<option value="">-- Seleccionar Técnico --</option>';
            tecnicos.forEach(tech => {
                const opt = document.createElement('option');
                opt.value = tech.id_usuario;
                opt.textContent = tech.nombre;
                select.appendChild(opt);
            });
        });
}

function cerrarAsignacion() {
    document.getElementById('modal-asignacion').style.display = 'none';
}

function cargarCargaTecnico() {
    const idTecnico = document.getElementById('id_tecnico').value;
    if (!idTecnico) {
        document.getElementById('carga-tecnico').style.display = 'none';
        return;
    }

    fetch(apiBase + '/?controlador=gestion&accion=obtener_carga_tecnico_json', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_tecnico=' + idTecnico
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('carga-numero').textContent = data.reportes_activos;
        document.getElementById('carga-tecnico').style.display = 'block';
    });
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('modal-asignacion');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

function get_color_urgencia(idUrgencia) {
    const colores = {
        1: '#4caf50', // No urgente - verde
        2: '#ff9800', // Moderado - naranja
        3: '#ff6b6b', // Importante - rojo suave
        4: '#c62828'  // Urgente - rojo intenso
    };
    return colores[idUrgencia] || '#667eea';
}
</script>
