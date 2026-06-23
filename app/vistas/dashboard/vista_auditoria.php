<?php
// Vista de Registro de Auditoría (RF-04)
// Tabla con la misma configuración que "Gestionar Usuarios":
// buscador en vivo + encabezados ordenables (th-sort/data-sort) + contador.
$base = config('app.url_base');

// Iconos por tipo de acción (fallback genérico)
$iconos_accion = [
    'login' => 'fa-right-to-bracket', 'logout' => 'fa-right-from-bracket',
    'crear' => 'fa-plus', 'crear_reporte' => 'fa-file-circle-plus',
    'actualizar' => 'fa-pen', 'editar' => 'fa-pen',
    'eliminar' => 'fa-trash', 'asignar' => 'fa-user-plus',
    'cerrar' => 'fa-lock', 'cambiar_estado' => 'fa-exchange-alt',
];
function icono_aud($accion, $mapa) {
    $a = strtolower($accion);
    foreach ($mapa as $clave => $ic) {
        if (strpos($a, $clave) !== false) return $ic;
    }
    return 'fa-circle-info';
}
?>
<div class="aud-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="page-banner__text">
            <h2>Registro de Auditoría</h2>
            <p>Trazabilidad de las acciones críticas realizadas en la institución.</p>
        </div>
    </div>

    <!-- Filtros por fecha (servidor) -->
    <form method="GET" action="<?php echo $base; ?>/" class="aud-filtros">
        <input type="hidden" name="controlador" value="dashboard">
        <input type="hidden" name="accion" value="auditoria">

        <div class="aud-campo">
            <label>Desde</label>
            <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
        </div>
        <div class="aud-campo">
            <label>Hasta</label>
            <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
        </div>
        <div class="aud-acciones-filtro">
            <button type="submit" class="aud-btn aud-btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="<?php echo $base; ?>/?controlador=dashboard&accion=auditoria" class="aud-btn aud-btn-clear"><i class="fas fa-times"></i> Limpiar</a>
        </div>
    </form>

    <!-- Barra de herramientas: buscador en vivo + contador + exportar -->
    <div class="tabla-toolbar">
        <div class="buscador">
            <i class="fas fa-search"></i>
            <input type="text" id="buscar-auditoria" placeholder="Buscar por usuario, acción, entidad o IP…" onkeyup="filtrarAuditoria()">
        </div>
        <div class="toolbar-derecha">
            <span class="total-auditoria"><strong id="contador-auditoria"><?php echo count($registros); ?></strong> de <?php echo intval($total); ?> registro(s)</span>
            <a href="<?php echo $base; ?>/?controlador=dashboard&accion=exportar&tipo=auditoria" class="aud-btn aud-btn-export"><i class="fas fa-file-csv"></i> Exportar CSV</a>
        </div>
    </div>

    <!-- Tabla (ordenable + buscador) -->
    <div class="aud-tabla-wrap">
        <table class="tabla-auditoria" id="tabla-auditoria">
            <thead>
                <tr>
                    <th class="th-sort" onclick="ordenarAuditoria(this,0,'num')">Fecha y hora <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,1,'text')">Usuario <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,2,'text')">Acción <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,3,'text')">Entidad <i class="fas fa-sort"></i></th>
                    <th class="th-sort th-center" onclick="ordenarAuditoria(this,4,'num')">ID <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,5,'text')">IP <i class="fas fa-sort"></i></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="6" class="aud-vacia"><i class="fas fa-inbox"></i> No hay registros de auditoría para los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr class="fila-auditoria">
                            <td class="aud-fecha" data-sort="<?php echo strtotime($r['fecha_hora_accion']); ?>"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($r['fecha_hora_accion']))); ?></td>
                            <td>
                                <?php if ($r['actor'] === 'Sistema'): ?>
                                    <span class="aud-actor aud-sistema"><i class="fas fa-robot"></i> Sistema</span>
                                <?php else: ?>
                                    <span class="aud-actor"><i class="fas fa-user"></i> <?php echo htmlspecialchars($r['actor']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="aud-badge-accion">
                                    <i class="fas <?php echo icono_aud($r['accion'], $iconos_accion); ?>"></i>
                                    <?php echo htmlspecialchars($r['accion']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($r['entidad']); ?></td>
                            <td class="td-center aud-id" data-sort="<?php echo $r['id_entidad'] !== null ? intval($r['id_entidad']) : 0; ?>"><?php echo $r['id_entidad'] !== null ? '#' . intval($r['id_entidad']) : '—'; ?></td>
                            <td class="aud-ip"><?php echo htmlspecialchars($r['ip_origen'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <?php
            $qs = function($p) use ($base, $fecha_desde, $fecha_hasta, $buscar) {
                return $base . '/?controlador=dashboard&accion=auditoria&pagina=' . $p
                    . '&fecha_desde=' . urlencode($fecha_desde)
                    . '&fecha_hasta=' . urlencode($fecha_hasta);
            };
        ?>
        <div class="aud-paginacion">
            <?php if ($pagina > 1): ?>
                <a href="<?php echo $qs($pagina - 1); ?>" class="aud-btn aud-btn-pag"><i class="fas fa-chevron-left"></i> Anterior</a>
            <?php endif; ?>
            <span class="aud-pag-info">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
            <?php if ($pagina < $total_paginas): ?>
                <a href="<?php echo $qs($pagina + 1); ?>" class="aud-btn aud-btn-pag">Siguiente <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    :root { --primary-blue:#3498DB; --dark-blue:#2980B9; --light-bg:#F8FBFC; }
    .aud-container { max-width: 1150px; margin: 30px auto; padding: 0 20px; }
    .aud-header { margin-bottom: 24px; }
    .aud-header h2 { font-size: 28px; color: #2C3E50; display: flex; align-items: center; gap: 12px; margin: 0 0 6px; }
    .aud-header h2 i { color: #3498DB; }
    .aud-subtitle { color: #808B96; font-size: 14px; margin: 0; }

    .aud-filtros { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; background: #fff; padding: 18px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(52,152,219,.08); margin-bottom: 16px; }
    .aud-campo { display: flex; flex-direction: column; gap: 5px; }
    .aud-campo label { font-size: 12px; font-weight: 600; color: #7F8C8D; text-transform: uppercase; letter-spacing: .3px; }
    .aud-campo input { padding: 10px 12px; border: 2px solid #E0E6EA; border-radius: 8px; font-size: 14px; background: #F8FBFC; }
    .aud-campo input:focus { outline: none; border-color: #3498DB; background: #fff; }
    .aud-acciones-filtro { display: flex; gap: 8px; }

    .aud-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .25s; }
    .aud-btn-primary { background: linear-gradient(135deg,#3498DB,#2980B9); color: #fff; }
    .aud-btn-primary:hover { box-shadow: 0 4px 12px rgba(52,152,219,.3); transform: translateY(-1px); }
    .aud-btn-clear { background: #ECF0F1; color: #7F8C8D; }
    .aud-btn-clear:hover { background: #DDE4E6; }
    .aud-btn-export { background: rgba(39,174,96,.12); color: #27AE60; }
    .aud-btn-export:hover { background: #27AE60; color: #fff; }
    .aud-btn-pag { background: #fff; color: #3498DB; border: 1px solid #D6E9F8; }
    .aud-btn-pag:hover { background: #3498DB; color: #fff; }

    /* Toolbar igual que Gestionar Usuarios */
    .tabla-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
    .tabla-toolbar .buscador { position:relative; flex:1; max-width:420px; }
    .tabla-toolbar .buscador i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#7F8C8D; font-size:14px; }
    .tabla-toolbar .buscador input { width:100%; padding:11px 14px 11px 40px; border:2px solid var(--primary-blue); border-radius:8px; font-size:14px; background:var(--light-bg); box-sizing:border-box; }
    .tabla-toolbar .buscador input:focus { outline:none; border-color:var(--dark-blue); background:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.1); }
    .toolbar-derecha { display:flex; align-items:center; gap:14px; }
    .total-auditoria { color:#7F8C8D; font-size:14px; }
    .total-auditoria strong { color:#2C3E50; }

    .aud-tabla-wrap { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(52,152,219,.08); overflow: hidden; }
    .tabla-auditoria { width: 100%; border-collapse: collapse; }
    .tabla-auditoria thead th { background: #F4F9FD; color: #2C3E50; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; text-align: left; padding: 14px 16px; border-bottom: 2px solid #E8EDEF; white-space: nowrap; }
    .tabla-auditoria th.th-sort { cursor:pointer; user-select:none; transition:background .2s; }
    .tabla-auditoria th.th-sort:hover { background:#D6EAF8; }
    .tabla-auditoria th.th-sort i { margin-left:5px; font-size:11px; color:var(--primary-blue); opacity:.7; }
    .tabla-auditoria th.th-center, .tabla-auditoria td.td-center { text-align:center; }
    .tabla-auditoria tbody td { padding: 13px 16px; border-bottom: 1px solid #EEF2F4; font-size: 13.5px; color: #2C3E50; }
    .tabla-auditoria tbody tr:hover { background: #F8FBFC; }
    .aud-fecha { font-family: 'Courier New', monospace; color: #5D6D7E; white-space: nowrap; }
    .aud-actor { display: inline-flex; align-items: center; gap: 6px; }
    .aud-actor i { color: #3498DB; font-size: 12px; }
    .aud-sistema i { color: #95A5A6; }
    .aud-sistema { color: #7F8C8D; }
    .aud-badge-accion { display: inline-flex; align-items: center; gap: 6px; background: rgba(52,152,219,.1); color: #2471A3; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .aud-id { color: #7F8C8D; }
    .aud-ip { font-family: 'Courier New', monospace; color: #95A5A6; font-size: 12.5px; }
    .aud-vacia { text-align: center; padding: 50px 20px; color: #95A5A6; }
    .aud-vacia i { font-size: 32px; display: block; margin-bottom: 12px; opacity: .5; }

    .aud-paginacion { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 20px; }
    .aud-pag-info { color: #7F8C8D; font-size: 13px; }

    @media (max-width: 768px) {
        .tabla-auditoria .aud-ip, .tabla-auditoria thead th:nth-child(6) { display: none; }
        .aud-filtros { flex-direction: column; align-items: stretch; }
        .aud-acciones-filtro { justify-content: stretch; }
        .aud-acciones-filtro .aud-btn { flex: 1; justify-content: center; }
    }
</style>

<script>
// Buscador en vivo (mismo comportamiento que Gestionar Usuarios)
function filtrarAuditoria() {
    const q = document.getElementById('buscar-auditoria').value.toLowerCase().trim();
    const filas = document.querySelectorAll('#tabla-auditoria tbody tr.fila-auditoria');
    let visibles = 0;
    filas.forEach(function(tr) {
        const mostrar = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    const cont = document.getElementById('contador-auditoria');
    if (cont) cont.textContent = visibles;
}

// Ordenar al hacer clic en un encabezado (mismo comportamiento que Gestionar Usuarios)
let ordenAuditoria = { col: null, asc: true };
function ordenarAuditoria(th, colIndex, tipo) {
    const tbody = document.querySelector('#tabla-auditoria tbody');
    const filas = Array.from(tbody.querySelectorAll('tr.fila-auditoria'));
    if (filas.length === 0) return;

    const asc = (ordenAuditoria.col === colIndex) ? !ordenAuditoria.asc : true;
    ordenAuditoria = { col: colIndex, asc: asc };

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

    document.querySelectorAll('#tabla-auditoria thead th.th-sort i').forEach(function(ic) { ic.className = 'fas fa-sort'; });
    const icono = th.querySelector('i');
    if (icono) icono.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
}
</script>
