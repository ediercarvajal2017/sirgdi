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
$iconos_accion += [
    'login_fallido' => 'fa-user-lock', 'acceso_denegado' => 'fa-ban', 'contrasena' => 'fa-key',
    'solucionado' => 'fa-check-double', 'validar' => 'fa-clipboard-check', 'devolver' => 'fa-rotate-left',
    'evidencia' => 'fa-camera', 'informe' => 'fa-file-lines', 'avance' => 'fa-comment-dots',
    'intervencion' => 'fa-screwdriver-wrench', 'permisos' => 'fa-user-shield', 'catalogo' => 'fa-tags',
    'usuario' => 'fa-user', 'institucion' => 'fa-building', 'sede' => 'fa-map-marker-alt', 'sla' => 'fa-hourglass-half',
    'encuesta' => 'fa-star', 'tecnico' => 'fa-hard-hat',
];

/* Antes/después como lista legible: clave: valor anterior → valor nuevo */
function detalle_aud($antes_json, $despues_json) {
    $antes = $antes_json ? json_decode($antes_json, true) : null;
    $despues = $despues_json ? json_decode($despues_json, true) : null;
    if (!is_array($antes) && !is_array($despues)) return '';
    $claves = array_unique(array_merge(array_keys((array)$antes), array_keys((array)$despues)));
    $html = '<dl class="aud-detalle">';
    foreach ($claves as $k) {
        $a = $antes[$k] ?? null; $d = $despues[$k] ?? null;
        $fmt = fn($x) => $x === null ? '<em>—</em>' : htmlspecialchars(is_scalar($x) ? (string)$x : json_encode($x, JSON_UNESCAPED_UNICODE));
        $html .= '<dt>' . htmlspecialchars(str_replace('_', ' ', $k)) . '</dt><dd>';
        if (is_array($antes) && is_array($despues) && $a !== $d) {
            $html .= '<span class="aud-antes">' . $fmt($a) . '</span> <i class="fas fa-arrow-right"></i> <span class="aud-despues">' . $fmt($d) . '</span>';
        } else {
            $html .= $fmt($d ?? $a);
        }
        $html .= '</dd>';
    }
    return $html . '</dl>';
}

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
    <div class="aud-tabla-wrap tabla-responsive-wrap">
        <table class="tabla-auditoria" id="tabla-auditoria">
            <thead>
                <tr>
                    <th class="th-sort" onclick="ordenarAuditoria(this,0,'num')">Fecha y hora <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,1,'text')">Usuario <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,2,'text')">Acción <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,3,'text')">Entidad <i class="fas fa-sort"></i></th>
                    <th class="th-sort th-center" onclick="ordenarAuditoria(this,4,'num')">ID <i class="fas fa-sort"></i></th>
                    <th class="th-sort" onclick="ordenarAuditoria(this,5,'text')">IP <i class="fas fa-sort"></i></th>
                    <th class="th-center">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="aud-vacia"><i class="fas fa-inbox"></i> No hay registros de auditoría para los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr class="fila-auditoria">
                            <td class="aud-fecha" data-sort="<?php echo strtotime($r['fecha_hora_accion']); ?>" data-label="Fecha y hora"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($r['fecha_hora_accion']))); ?></td>
                            <td data-label="Usuario">
                                <?php if ($r['actor'] === 'Sistema'): ?>
                                    <span class="aud-actor aud-sistema"><i class="fas fa-robot"></i> Sistema</span>
                                <?php else: ?>
                                    <span class="aud-actor"><i class="fas fa-user"></i> <?php echo htmlspecialchars($r['actor']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Acción">
                                <span class="aud-badge-accion">
                                    <i class="fas <?php echo icono_aud($r['accion'], $iconos_accion); ?>"></i>
                                    <?php echo htmlspecialchars($r['accion']); ?>
                                </span>
                            </td>
                            <td data-label="Entidad"><?php echo htmlspecialchars($r['entidad']); ?><?php if ($r['id_institucion'] === null): ?> <span class="aud-global" title="Evento de plataforma, no de una institución">global</span><?php endif; ?></td>
                            <td class="td-center aud-id" data-sort="<?php echo $r['id_entidad'] !== null ? intval($r['id_entidad']) : 0; ?>" data-label="ID"><?php echo $r['id_entidad'] !== null ? '#' . intval($r['id_entidad']) : '—'; ?></td>
                            <td class="aud-ip" data-label="IP"><?php echo htmlspecialchars($r['ip_origen'] ?? '—'); ?></td>
                            <td class="td-center">
                                <?php $det = detalle_aud($r['datos_anteriores_json'] ?? null, $r['datos_nuevos_json'] ?? null); ?>
                                <?php if ($det): ?>
                                    <details class="aud-det"><summary title="Ver detalle"><i class="fas fa-circle-info"></i></summary><div class="aud-det-box"><?php echo $det; ?></div></details>
                                <?php else: ?>—<?php endif; ?>
                            </td>
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
    :root { --primary-blue: var(--color-primary); --dark-blue: var(--color-primary-dark); --light-bg: var(--color-bg-subtle); }
    .aud-container { max-width: 1150px; margin: 30px auto; padding: 0 20px; }
    .aud-header { margin-bottom: 24px; }
    .aud-header h2 { font-size: 28px; color: var(--color-text); display: flex; align-items: center; gap: 12px; margin: 0 0 6px; }
    .aud-header h2 i { color: var(--color-primary); }
    .aud-subtitle { color: var(--color-text-muted); font-size: 14px; margin: 0; }

    .aud-filtros { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; background: var(--color-bg-elevated); padding: 18px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(52,152,219,.08); margin-bottom: 16px; }
    .aud-campo { display: flex; flex-direction: column; gap: 5px; }
    .aud-campo label { font-size: 12px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .3px; }
    .aud-campo input { padding: 10px 12px; border: 2px solid var(--color-border); border-radius: 8px; font-size: 16px; background: var(--color-bg-subtle); color: var(--color-text); }
    .aud-campo input:focus { outline: none; border-color: var(--color-primary); background: var(--color-bg-elevated); }
    .aud-acciones-filtro { display: flex; gap: 8px; }

    .aud-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .25s; }
    .aud-btn-primary { background: linear-gradient(135deg,#3498DB,#2980B9); color: #fff; }
    .aud-btn-primary:hover { box-shadow: 0 4px 12px rgba(52,152,219,.3); transform: translateY(-1px); }
    .aud-btn-clear { background: var(--color-bg-subtle); color: var(--color-text-muted); }
    .aud-btn-clear:hover { background: var(--color-bg-hover); }
    .aud-btn-export { background: rgba(39,174,96,.12); color: var(--color-success); }
    .aud-btn-export:hover { background: var(--color-success); color: #fff; }
    .aud-btn-pag { background: var(--color-bg-elevated); color: var(--color-primary); border: 1px solid var(--color-border); }
    .aud-btn-pag:hover { background: var(--color-primary); color: #fff; }

    /* Toolbar igual que Gestionar Usuarios */
    .tabla-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
    .tabla-toolbar .buscador { position:relative; flex:1; max-width:420px; }
    .tabla-toolbar .buscador i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--color-text-muted); font-size:14px; }
    .tabla-toolbar .buscador input { width:100%; padding:11px 14px 11px 40px; border:2px solid var(--primary-blue); border-radius:8px; font-size:16px; background:var(--color-bg-subtle); color:var(--color-text); box-sizing:border-box; }
    .tabla-toolbar .buscador input:focus { outline:none; border-color:var(--dark-blue); background:var(--color-bg-elevated); box-shadow:0 0 0 4px rgba(52,152,219,.1); }
    .toolbar-derecha { display:flex; align-items:center; gap:14px; }
    .total-auditoria { color:var(--color-text-muted); font-size:14px; }
    .total-auditoria strong { color:var(--color-text); }

    .aud-tabla-wrap { background: var(--color-bg-elevated); border-radius: 10px; box-shadow: 0 2px 12px rgba(52,152,219,.08); overflow: auto; }
    .tabla-auditoria { width: 100%; border-collapse: collapse; }
    .tabla-auditoria thead th { background: var(--color-bg-subtle); color: var(--color-text); font-size: 12px; text-transform: uppercase; letter-spacing: .4px; text-align: left; padding: 14px 16px; border-bottom: 2px solid var(--color-border); white-space: nowrap; }
    .tabla-auditoria th.th-sort { cursor:pointer; user-select:none; transition:background .2s; }
    .tabla-auditoria th.th-sort:hover { background:var(--color-bg-hover); }
    .tabla-auditoria th.th-sort i { margin-left:5px; font-size:11px; color:var(--primary-blue); opacity:.7; }
    .tabla-auditoria th.th-center, .tabla-auditoria td.td-center { text-align:center; }
    .tabla-auditoria tbody td { padding: 13px 16px; border-bottom: 1px solid var(--color-border-subtle); font-size: 13.5px; color: var(--color-text); }
    .tabla-auditoria tbody tr:hover { background: var(--color-bg-subtle); }
    .aud-fecha { font-family: 'Courier New', monospace; color: var(--color-text-muted); white-space: nowrap; }
    .aud-actor { display: inline-flex; align-items: center; gap: 6px; }
    .aud-actor i { color: var(--color-primary); font-size: 12px; }
    .aud-sistema i { color: var(--color-text-muted); }
    .aud-sistema { color: var(--color-text-muted); }
    .aud-badge-accion { display: inline-flex; align-items: center; gap: 6px; background: rgba(52,152,219,.1); color: var(--color-primary); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .aud-id { color: var(--color-text-muted); }
    .aud-ip { font-family: 'Courier New', monospace; color: var(--color-text-muted); font-size: 12.5px; }
    .aud-global { display:inline-block; margin-left:6px; padding:1px 7px; border-radius:10px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; background:var(--color-info-bg); color:var(--color-info-text); }
    .aud-det { position:relative; display:inline-block; }
    .aud-det summary { list-style:none; cursor:pointer; color:var(--color-primary); font-size:15px; }
    .aud-det summary::-webkit-details-marker { display:none; }
    .aud-det[open] summary { color:var(--color-primary-dark); }
    .aud-det-box { position:absolute; right:0; top:24px; z-index:20; min-width:280px; max-width:420px; text-align:left; background:var(--color-bg-elevated); border:1px solid var(--color-border); border-radius:10px; padding:12px 14px; box-shadow:0 8px 24px rgba(0,0,0,.25); }
    .aud-detalle { margin:0; display:grid; grid-template-columns:auto 1fr; gap:4px 12px; font-size:12.5px; }
    .aud-detalle dt { color:var(--color-text-muted); text-transform:capitalize; white-space:nowrap; }
    .aud-detalle dd { margin:0; color:var(--color-text); word-break:break-word; }
    .aud-detalle dd i { color:var(--color-text-muted); font-size:10px; margin:0 4px; }
    .aud-antes { color:var(--color-danger); text-decoration:line-through; opacity:.8; }
    .aud-despues { color:var(--color-success); font-weight:600; }
    .aud-vacia { text-align: center; padding: 50px 20px; color: var(--color-text-muted); }
    .aud-vacia i { font-size: 32px; display: block; margin-bottom: 12px; opacity: .5; }

    .aud-paginacion { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 20px; }
    .aud-pag-info { color: var(--color-text-muted); font-size: 13px; }

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
