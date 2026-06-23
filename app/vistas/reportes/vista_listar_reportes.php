<!-- Mis Reportes - Tabla ordenable con buscador (estilo Gestionar Usuarios) -->
<?php
$estados = [1 => 'Registrado', 2 => 'En Proceso', 3 => 'Devuelto', 4 => 'Solucionado', 5 => 'En Validación', 6 => 'Cerrado', 7 => 'Cancelado', 8 => 'Anulado'];
$urgencias = [1 => 'No Urgente', 2 => 'Moderado', 3 => 'Importante', 4 => 'Urgente'];
?>
<div class="reportes-container">
    <div class="form-head">
        <div class="form-head-icon"><i class="fas fa-file-alt"></i></div>
        <div class="form-head-text">
            <h2>Mis Reportes</h2>
            <p>Historial y seguimiento de tus reportes de daños.</p>
        </div>
    </div>

    <?php if (empty($reportes)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No hay reportes registrados.</p>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=crear" class="btn-create">
                <i class="fas fa-plus-circle"></i> Crear tu primer reporte
            </a>
        </div>
    <?php else: ?>
        <!-- Barra de herramientas: buscador en vivo + contador -->
        <div class="tabla-toolbar">
            <div class="buscador">
                <i class="fas fa-search"></i>
                <input type="text" id="buscar-reportes" placeholder="Buscar por ticket, categoría, estado o urgencia…" onkeyup="filtrarReportes()">
            </div>
            <span class="total-reportes"><strong id="contador-reportes"><?php echo count($reportes); ?></strong> reporte(s)</span>
        </div>

        <!-- Tabla (ordenable + buscador) -->
        <div class="tabla-wrap">
            <table class="tabla-reportes" id="tabla-reportes">
                <thead>
                    <tr>
                        <th class="th-sort" onclick="ordenarReportes(this,0,'text')">Ticket <i class="fas fa-sort"></i></th>
                        <th class="th-sort" onclick="ordenarReportes(this,1,'text')">Categoría <i class="fas fa-sort"></i></th>
                        <th class="th-sort th-center" onclick="ordenarReportes(this,2,'num')">Estado <i class="fas fa-sort"></i></th>
                        <th class="th-sort th-center" onclick="ordenarReportes(this,3,'num')">Urgencia <i class="fas fa-sort"></i></th>
                        <th class="th-sort" onclick="ordenarReportes(this,4,'num')">Fecha <i class="fas fa-sort"></i></th>
                        <th class="th-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportes as $reporte): ?>
                        <?php
                        $estado_id = isset($reporte['id_estado']) && $reporte['id_estado'] !== null ? intval($reporte['id_estado']) : 1;
                        $estado_texto = $estados[$estado_id] ?? 'Desconocido';
                        $urgencia_id = isset($reporte['id_urgencia_calculada']) ? intval($reporte['id_urgencia_calculada']) : 1;
                        $urgencia_texto = $urgencias[$urgencia_id] ?? 'Desconocida';
                        $fecha = isset($reporte['fecha_hora_registro']) ? $reporte['fecha_hora_registro'] : (isset($reporte['fecha_registro']) ? $reporte['fecha_registro'] : '');
                        $fecha_ts = $fecha ? strtotime($fecha) : 0;
                        $categoria_nombre = isset($reporte['nombre_categoria']) ? $reporte['nombre_categoria'] : (isset($reporte['id_categoria']) ? $reporte['id_categoria'] : 'N/A');
                        ?>
                        <tr class="fila-reporte">
                            <td><code class="ticket-code"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></code></td>
                            <td><?php echo htmlspecialchars($categoria_nombre); ?></td>
                            <td class="td-center" data-sort="<?php echo $estado_id; ?>">
                                <span class="badge badge-estado-<?php echo $estado_id; ?>"><?php echo htmlspecialchars($estado_texto); ?></span>
                            </td>
                            <td class="td-center" data-sort="<?php echo $urgencia_id; ?>">
                                <span class="badge badge-urgency-<?php echo $urgencia_id; ?>"><?php echo htmlspecialchars($urgencia_texto); ?></span>
                            </td>
                            <td data-sort="<?php echo $fecha_ts; ?>"><?php echo $fecha ? date('d/m/Y', $fecha_ts) : 'N/A'; ?></td>
                            <td class="td-center td-acciones">
                                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=detalle&id=<?php echo $reporte['id_reporte']; ?>" class="btn-action-compact btn-view" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($estado_id === 1): ?>
                                    <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=editar&id=<?php echo $reporte['id_reporte']; ?>" class="btn-action-compact btn-edit" title="Editar reporte">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=eliminar" style="display:inline;" onsubmit="return confirm('¿Eliminar el reporte <?php echo htmlspecialchars($reporte['numero_ticket'], ENT_QUOTES); ?>?\n\nEsta acción es permanente y no se puede deshacer.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? Validacion::generar_csrf_token()); ?>">
                                        <input type="hidden" name="id_reporte" value="<?php echo $reporte['id_reporte']; ?>">
                                        <button type="submit" class="btn-action-compact btn-delete" title="Eliminar reporte">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="lock-hint" title="En gestión: ya no se puede editar ni eliminar"><i class="fas fa-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=listar&pagina=<?php echo ($pagina - 1); ?>" class="btn-pag btn-prev">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            <?php endif; ?>
            <span class="page-info">Página <strong><?php echo $pagina; ?></strong></span>
            <?php if (count($reportes) >= 50): ?>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=listar&pagina=<?php echo ($pagina + 1); ?>" class="btn-pag btn-next">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
        --light-gray: #ECF0F1;
    }

    .reportes-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }

    /* Encabezado tipo banner (igual que los formularios del módulo) */
    .form-head { display:flex; align-items:center; gap:18px; padding:24px 30px; margin-bottom:24px; background:linear-gradient(135deg,#2980B9,#3498DB); color:#fff; border-radius:14px; box-shadow:0 8px 24px rgba(41,128,185,.22); }
    .form-head-icon { width:54px; height:54px; flex-shrink:0; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
    .form-head-text { flex:1; }
    .form-head-text h2 { margin:0 0 4px; font-size:24px; font-weight:700; }
    .form-head-text p { margin:0; font-size:13.5px; color:rgba(255,255,255,.9); }
    @media (max-width: 600px) { .form-head { flex-wrap:wrap; padding:20px; } }

    /* Toolbar (igual que Gestionar Usuarios) */
    .tabla-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
    .tabla-toolbar .buscador { position:relative; flex:1; max-width:420px; }
    .tabla-toolbar .buscador i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#7F8C8D; font-size:14px; }
    .tabla-toolbar .buscador input { width:100%; padding:11px 14px 11px 40px; border:2px solid var(--primary-blue); border-radius:8px; font-size:14px; background:var(--light-bg); box-sizing:border-box; }
    .tabla-toolbar .buscador input:focus { outline:none; border-color:var(--dark-blue); background:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.1); }
    .total-reportes { color:#7F8C8D; font-size:14px; }
    .total-reportes strong { color:#2C3E50; }

    /* Tabla */
    .tabla-wrap { background:#fff; border-radius:12px; box-shadow:0 4px 16px rgba(52,152,219,.08); overflow:hidden; margin-bottom:24px; }
    .tabla-reportes { width:100%; border-collapse:collapse; }
    .tabla-reportes thead th { background:#F4F9FD; color:#2C3E50; font-size:12px; text-transform:uppercase; letter-spacing:.4px; text-align:left; padding:14px 16px; border-bottom:2px solid #E8EDEF; white-space:nowrap; }
    .tabla-reportes th.th-sort { cursor:pointer; user-select:none; transition:background .2s; }
    .tabla-reportes th.th-sort:hover { background:#D6EAF8; }
    .tabla-reportes th.th-sort i { margin-left:5px; font-size:11px; color:var(--primary-blue); opacity:.7; }
    .tabla-reportes th.th-center, .tabla-reportes td.td-center { text-align:center; }
    .tabla-reportes tbody td { padding:13px 16px; border-bottom:1px solid #EEF2F4; font-size:13.5px; color:#2C3E50; }
    .tabla-reportes tbody tr:hover { background:#F8FBFC; }

    .ticket-code { background-color: var(--light-gray); padding: 4px 10px; border-radius: 4px; font-family: 'Monaco', 'Courier New', monospace; font-size: 12px; font-weight: 700; color: var(--primary-blue); white-space: nowrap; }

    .badge { display:inline-flex; align-items:center; padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
    .badge-estado-1 { background: rgba(52,152,219,.15); color:#3498DB; }
    .badge-estado-2 { background: rgba(230,126,34,.15); color:#E67E22; }
    .badge-estado-3 { background: rgba(155,89,182,.15); color:#9B59B6; }
    .badge-estado-4 { background: rgba(46,204,113,.15); color:#27AE60; }
    .badge-estado-5 { background: rgba(243,156,18,.15); color:#F39C12; }
    .badge-estado-6 { background: rgba(39,174,96,.15); color:#27AE60; }
    .badge-estado-7 { background: rgba(231,76,60,.15); color:#E74C3C; }
    .badge-estado-8 { background: rgba(128,139,150,.15); color:var(--gray-text); }
    .badge-urgency-1 { background: rgba(46,204,113,.15); color:#27AE60; }
    .badge-urgency-2 { background: rgba(230,126,34,.15); color:#E67E22; }
    .badge-urgency-3 { background: rgba(243,156,18,.15); color:#F39C12; }
    .badge-urgency-4 { background: rgba(231,76,60,.15); color:#E74C3C; }

    .btn-action-compact { width:36px; height:36px; padding:0; border:none; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:14px; transition:all .3s; text-decoration:none; }
    .btn-view { color:var(--primary-blue); background:rgba(52,152,219,.1); }
    .btn-view:hover { background:var(--primary-blue); color:#fff; transform:scale(1.08); }
    .td-acciones { white-space:nowrap; }
    .td-acciones .btn-action-compact, .td-acciones form { display:inline-flex; margin:0 2px; vertical-align:middle; }
    .btn-edit { color:#E67E22; background:rgba(230,126,34,.12); }
    .btn-edit:hover { background:#E67E22; color:#fff; transform:scale(1.08); }
    .btn-delete { color:#E74C3C; background:#FDEDEC; border:none; cursor:pointer; }
    .btn-delete:hover { background:#E74C3C; color:#fff; transform:scale(1.08); }
    .lock-hint { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; color:#BDC3C7; }

    /* Paginación */
    .pagination { display:flex; justify-content:center; align-items:center; gap:20px; padding:18px; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(52,152,219,.08); flex-wrap:wrap; }
    .btn-pag { display:inline-flex; align-items:center; gap:6px; padding:10px 18px; background:linear-gradient(135deg,var(--primary-blue),var(--dark-blue)); color:#fff; text-decoration:none; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:all .3s; text-transform:uppercase; letter-spacing:.3px; }
    .btn-pag:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(52,152,219,.3); }
    .page-info { color:var(--dark-text); font-size:14px; font-weight:600; }
    .page-info strong { color:var(--primary-blue); font-weight:700; }

    /* Empty state */
    .empty-state { text-align:center; padding:80px 40px; background:#fff; border-radius:12px; color:var(--gray-text); box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .empty-state i { font-size:48px; margin-bottom:16px; opacity:.5; display:block; }
    .empty-state p { margin:0 0 24px 0; font-size:15px; }
    .btn-create { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,var(--primary-blue),var(--dark-blue)); color:#fff; padding:12px 24px; border-radius:8px; font-size:14px; text-decoration:none; font-weight:600; }
    .btn-create:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(52,152,219,.3); }

    @media (max-width: 768px) {
        .reportes-header h2 { font-size: 24px; }
        .tabla-reportes thead th:nth-child(2), .tabla-reportes tbody td:nth-child(2) { display:none; }
    }
</style>

<script>
// Buscador en vivo (mismo comportamiento que Gestionar Usuarios)
function filtrarReportes() {
    const q = document.getElementById('buscar-reportes').value.toLowerCase().trim();
    const filas = document.querySelectorAll('#tabla-reportes tbody tr.fila-reporte');
    let visibles = 0;
    filas.forEach(function(tr) {
        const mostrar = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    const cont = document.getElementById('contador-reportes');
    if (cont) cont.textContent = visibles;
}

// Ordenar al hacer clic en un encabezado (mismo comportamiento que Gestionar Usuarios)
let ordenReportes = { col: null, asc: true };
function ordenarReportes(th, colIndex, tipo) {
    const tbody = document.querySelector('#tabla-reportes tbody');
    const filas = Array.from(tbody.querySelectorAll('tr.fila-reporte'));
    if (filas.length === 0) return;

    const asc = (ordenReportes.col === colIndex) ? !ordenReportes.asc : true;
    ordenReportes = { col: colIndex, asc: asc };

    filas.sort(function(a, b) {
        const cA = a.children[colIndex], cB = b.children[colIndex];
        let va, vb;
        if (tipo === 'num') {
            va = parseFloat(cA.getAttribute('data-sort')); vb = parseFloat(cB.getAttribute('data-sort'));
            if (isNaN(va)) va = 0; if (isNaN(vb)) vb = 0;
        } else {
            va = cA.textContent.trim().toLowerCase(); vb = cB.textContent.trim().toLowerCase();
        }
        if (va < vb) return asc ? -1 : 1;
        if (va > vb) return asc ? 1 : -1;
        return 0;
    });

    filas.forEach(function(f) { tbody.appendChild(f); });

    document.querySelectorAll('#tabla-reportes thead th.th-sort i').forEach(function(ic) { ic.className = 'fas fa-sort'; });
    const icono = th.querySelector('i');
    if (icono) icono.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
}
</script>
