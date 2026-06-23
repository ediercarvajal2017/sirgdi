<script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>

<div class="container sla-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-stopwatch"></i></div>
        <div class="page-banner__text">
            <h2>Configurar SLA</h2>
            <p>Establece los tiempos de respuesta y resolución por nivel de prioridad.</p>
        </div>
    </div>

    <!-- FORMULARIO ARRIBA -->
    <div class="form-modern-card">
        <h3><i class="fas fa-clock" id="form-icon"></i> <span id="form-title">Crear Nuevo SLA</span></h3>
        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_sla" class="form-sla" id="form-sla">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="accion" id="form-accion" value="crear">
            <input type="hidden" name="id_sla" id="id_sla" value="">

            <div class="form-row">
                <div class="form-group">
                    <label for="id_urgencia"><i class="fas fa-exclamation-triangle"></i> Nivel de Prioridad de la Incidencia: <span class="required">*</span></label>
                    <select name="id_urgencia" id="id_urgencia" required class="input-modern">
                        <option value="">-- Selecciona el nivel de prioridad --</option>
                        <?php foreach ($urgencias as $id => $nombre): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_categoria"><i class="fas fa-folder"></i> Categoría:</label>
                    <select name="id_categoria" id="id_categoria" class="input-modern">
                        <option value="">Todas las categorías (regla general)</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_categoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tiempo_respuesta_horas"><i class="fas fa-clock"></i> Tiempo de Respuesta (horas): <span class="required">*</span></label>
                    <input type="number" name="tiempo_respuesta_horas" id="tiempo_respuesta_horas" min="1" max="720" placeholder="Ej: 2" required class="input-modern">
                </div>

                <div class="form-group">
                    <label for="tiempo_resolucion_horas"><i class="fas fa-hourglass-end"></i> Tiempo de Resolución (horas): <span class="required">*</span></label>
                    <input type="number" name="tiempo_resolucion_horas" id="tiempo_resolucion_horas" min="1" max="720" placeholder="Ej: 8" required class="input-modern">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-modern" id="btn-submit">
                    <i class="fas fa-check"></i> Crear SLA
                </button>
                <button type="button" class="btn-modern-secondary" id="btn-cancel" style="display:none;" onclick="cancelarEdicion()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="reset" class="btn-modern-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- LISTA: tabla profesional ordenable -->
    <div class="sla-content">
        <?php if (empty($slas)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay SLA configurados todavía.</p>
            </div>
        <?php else: ?>
            <div class="tabla-toolbar">
                <div class="buscador">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscar-sla" placeholder="Buscar por prioridad, categoría o estado…" onkeyup="filtrarSLA()">
                </div>
                <span class="total-sla"><strong id="contador-sla"><?php echo count($slas); ?></strong> SLA configurado(s)</span>
            </div>

            <div class="tabla-wrap">
                <table class="tabla-sla" id="tabla-sla">
                    <thead>
                        <tr>
                            <th class="th-sort" onclick="ordenarSLA(this,0,'num')">Prioridad <i class="fas fa-sort"></i></th>
                            <th class="th-sort" onclick="ordenarSLA(this,1,'text')">Categoría <i class="fas fa-sort"></i></th>
                            <th class="th-sort th-center" onclick="ordenarSLA(this,2,'num')">Respuesta <i class="fas fa-sort"></i></th>
                            <th class="th-sort th-center" onclick="ordenarSLA(this,3,'num')">Resolución <i class="fas fa-sort"></i></th>
                            <th class="th-sort th-center" onclick="ordenarSLA(this,4,'num')">Estado <i class="fas fa-sort"></i></th>
                            <th class="th-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slas as $sla): ?>
                            <?php
                                $nombre_urgencia = $urgencias[$sla['id_urgencia']] ?? 'Urgencia ' . $sla['id_urgencia'];
                                $nombre_categoria = $sla['id_categoria']
                                    ? ($mapa_categorias[$sla['id_categoria']] ?? 'Categoría ' . $sla['id_categoria'])
                                    : 'Todas las categorías';
                                $clase_urgencia = 'urg-' . intval($sla['id_urgencia']);
                            ?>
                            <tr class="fila-sla">
                                <td data-sort="<?php echo intval($sla['id_urgencia']); ?>">
                                    <span class="badge-urgencia <?php echo $clase_urgencia; ?>">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($nombre_urgencia); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="sla-categoria"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($nombre_categoria); ?></span>
                                </td>
                                <td class="td-center" data-sort="<?php echo intval($sla['tiempo_respuesta_horas']); ?>">
                                    <span class="tiempo-pill"><i class="fas fa-clock"></i> <?php echo intval($sla['tiempo_respuesta_horas']); ?> h</span>
                                </td>
                                <td class="td-center" data-sort="<?php echo intval($sla['tiempo_resolucion_horas']); ?>">
                                    <span class="tiempo-pill"><i class="fas fa-hourglass-end"></i> <?php echo intval($sla['tiempo_resolucion_horas']); ?> h</span>
                                </td>
                                <td class="td-center" data-sort="<?php echo $sla['activo'] ? 1 : 0; ?>">
                                    <span class="badge <?php echo $sla['activo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $sla['activo'] ? '<i class="fas fa-check"></i> Activo' : '<i class="fas fa-times"></i> Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="td-center td-acciones">
                                    <button type="button" class="btn-action-compact btn-edit"
                                        data-id="<?php echo $sla['id_sla']; ?>"
                                        data-urgencia="<?php echo htmlspecialchars($sla['id_urgencia'], ENT_QUOTES); ?>"
                                        data-categoria="<?php echo htmlspecialchars($sla['id_categoria'] ?? '', ENT_QUOTES); ?>"
                                        data-respuesta="<?php echo intval($sla['tiempo_respuesta_horas']); ?>"
                                        data-resolucion="<?php echo intval($sla['tiempo_resolucion_horas']); ?>"
                                        onclick="editarSLA(this)" title="Editar SLA">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_sla" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_sla" value="<?php echo $sla['id_sla']; ?>">
                                        <button type="submit" class="btn-action-compact btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar este SLA?')" title="Eliminar SLA">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
:root {
    --primary-blue: #3498DB;
    --dark-blue: #2980B9;
    --gray-text: #7F8C8D;
    --dark-text: #2C3E50;
    --light-bg: #F8FBFC;
}

.sla-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.sla-header {
    margin-bottom: 35px;
}

.sla-header h2 {
    font-size: 32px;
    font-weight: 700;
    color: var(--dark-text);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sla-header h2 i {
    color: var(--primary-blue);
    font-size: 32px;
}

.subtitle {
    color: var(--gray-text);
    font-size: 14px;
    margin: 0;
    font-weight: 500;
}

/* ===== FORMULARIO ===== */
.form-modern-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 16px rgba(52, 152, 219, 0.08);
    border-left: 4px solid var(--primary-blue);
    margin-bottom: 30px;
}

.form-modern-card h3 {
    margin-bottom: 25px;
    color: var(--dark-text);
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-modern-card h3 i {
    color: var(--primary-blue);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--dark-text);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i {
    color: var(--primary-blue);
    font-size: 14px;
}

.form-group .required {
    color: #E74C3C;
    font-weight: 700;
}

.input-modern {
    padding: 12px 15px;
    border: 2px solid var(--primary-blue);
    background: var(--light-bg);
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    transition: all 0.3s;
    box-sizing: border-box;
}

.input-modern:focus {
    outline: none;
    border-color: var(--dark-blue);
    background: white;
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
}

select.input-modern {
    cursor: pointer;
    appearance: none;
    background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"%3e%3cpolyline points="6 9 12 15 18 9"%3e%3c/polyline%3e%3c/svg%3e');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    padding-right: 40px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.btn-modern {
    flex: 1;
    min-width: 150px;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 14px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
}

.btn-modern-secondary {
    flex: 1;
    min-width: 150px;
    padding: 12px 24px;
    background: #ECF0F1;
    color: var(--dark-text);
    border: 2px solid var(--primary-blue);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 14px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-modern-secondary:hover {
    background: var(--gray-text);
    color: white;
    transform: translateY(-2px);
}

/* ===== TABLA SLA (estilo Gestionar Usuarios) ===== */
.sla-content { margin-top: 30px; }

.tabla-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
.tabla-toolbar .buscador { position:relative; flex:1; max-width:420px; }
.tabla-toolbar .buscador i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#7F8C8D; font-size:14px; }
.tabla-toolbar .buscador input { width:100%; padding:11px 14px 11px 40px; border:2px solid var(--primary-blue); border-radius:8px; font-size:14px; background:var(--light-bg); box-sizing:border-box; }
.tabla-toolbar .buscador input:focus { outline:none; border-color:var(--dark-blue); background:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.1); }
.total-sla { color:#7F8C8D; font-size:14px; }
.total-sla strong { color:var(--dark-text); }

.tabla-wrap { background:#fff; border-radius:12px; box-shadow:0 4px 16px rgba(52,152,219,.08); overflow:hidden; }
.tabla-sla { width:100%; border-collapse:collapse; }
.tabla-sla thead th { background:#F4F9FD; color:var(--dark-text); font-size:12px; text-transform:uppercase; letter-spacing:.4px; text-align:left; padding:14px 16px; border-bottom:2px solid #E8EDEF; white-space:nowrap; }
.tabla-sla th.th-sort { cursor:pointer; user-select:none; transition:background .2s; }
.tabla-sla th.th-sort:hover { background:#D6EAF8; }
.tabla-sla th.th-sort i { margin-left:5px; font-size:11px; color:var(--primary-blue); opacity:.7; }
.tabla-sla th.th-center, .tabla-sla td.td-center { text-align:center; }
.tabla-sla tbody td { padding:13px 16px; border-bottom:1px solid #EEF2F4; font-size:13.5px; color:var(--dark-text); }
.tabla-sla tbody tr:hover { background:#F8FBFC; }
.td-acciones { white-space:nowrap; }
.td-acciones .btn-action-compact, .td-acciones form { display:inline-flex; margin:0 2px; vertical-align:middle; }

.sla-categoria { font-size:13px; color:var(--dark-text); font-weight:500; display:inline-flex; align-items:center; gap:6px; }
.sla-categoria i { color:var(--primary-blue); font-size:12px; }

.tiempo-pill { display:inline-flex; align-items:center; gap:6px; background:rgba(52,152,219,.1); color:#2471A3; padding:5px 12px; border-radius:14px; font-size:12.5px; font-weight:600; white-space:nowrap; }
.tiempo-pill i { font-size:11px; }

/* ===== BADGES URGENCIA ===== */
.badge-urgencia {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.badge-urgencia i {
    font-size: 10px;
}

.urg-1 { background: rgba(39, 174, 96, 0.15); color: #27AE60; }
.urg-2 { background: rgba(243, 156, 18, 0.15); color: #F39C12; }
.urg-3 { background: rgba(230, 126, 34, 0.18); color: #E67E22; }
.urg-4 { background: rgba(231, 76, 60, 0.15); color: #E74C3C; }

.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: rgba(39, 174, 96, 0.15);
    color: #27AE60;
}

.badge-inactive {
    background: rgba(231, 76, 60, 0.15);
    color: #E74C3C;
}

/* ===== BOTONES COMPACTOS ===== */
.btn-action-compact {
    width: 36px;
    height: 36px;
    padding: 0;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-action-compact.btn-edit {
    color: #E67E22;
    background: rgba(230, 126, 34, 0.1);
}

.btn-action-compact.btn-edit:hover {
    background: #E67E22;
    color: white;
    transform: scale(1.08);
}

.btn-action-compact.btn-delete {
    color: #E74C3C;
    background: rgba(231, 76, 60, 0.1);
}

.btn-action-compact.btn-delete:hover {
    background: #E74C3C;
    color: white;
    transform: scale(1.08);
}

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 60px 40px;
    background: white;
    border-radius: 12px;
    color: var(--gray-text);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
    display: block;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .sla-header h2 {
        font-size: 24px;
    }

    .sla-meta {
        flex-direction: column;
        gap: 4px;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn-modern,
    .btn-modern-secondary {
        flex: none;
        width: 100%;
    }
}
</style>

<script>
function editarSLA(btn) {
    // Llenar el formulario con los datos del SLA (desde data-attributes)
    document.getElementById('id_sla').value = btn.dataset.id;
    document.getElementById('id_urgencia').value = btn.dataset.urgencia;
    document.getElementById('id_categoria').value = btn.dataset.categoria;
    document.getElementById('tiempo_respuesta_horas').value = btn.dataset.respuesta;
    document.getElementById('tiempo_resolucion_horas').value = btn.dataset.resolucion;

    // Cambiar estado del formulario a modo edición
    document.getElementById('form-accion').value = 'editar';
    document.getElementById('form-icon').className = 'fas fa-edit';
    document.getElementById('form-title').textContent = 'Editar SLA';
    document.getElementById('btn-submit').innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
    document.getElementById('btn-cancel').style.display = 'flex';

    // Scroll al formulario
    document.querySelector('.form-modern-card').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEdicion() {
    // Limpiar formulario
    document.getElementById('form-sla').reset();
    document.getElementById('id_sla').value = '';

    // Restablecer estado del formulario
    document.getElementById('form-accion').value = 'crear';
    document.getElementById('form-icon').className = 'fas fa-clock';
    document.getElementById('form-title').textContent = 'Crear Nuevo SLA';
    document.getElementById('btn-submit').innerHTML = '<i class="fas fa-check"></i> Crear SLA';
    document.getElementById('btn-cancel').style.display = 'none';
}

// Buscador en vivo (mismo comportamiento que Gestionar Usuarios)
function filtrarSLA() {
    const q = document.getElementById('buscar-sla').value.toLowerCase().trim();
    const filas = document.querySelectorAll('#tabla-sla tbody tr.fila-sla');
    let visibles = 0;
    filas.forEach(function(tr) {
        const mostrar = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    const cont = document.getElementById('contador-sla');
    if (cont) cont.textContent = visibles;
}

// Ordenar al hacer clic en un encabezado
let ordenSLA = { col: null, asc: true };
function ordenarSLA(th, colIndex, tipo) {
    const tbody = document.querySelector('#tabla-sla tbody');
    const filas = Array.from(tbody.querySelectorAll('tr.fila-sla'));
    if (filas.length === 0) return;

    const asc = (ordenSLA.col === colIndex) ? !ordenSLA.asc : true;
    ordenSLA = { col: colIndex, asc: asc };

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

    document.querySelectorAll('#tabla-sla thead th.th-sort i').forEach(function(ic) { ic.className = 'fas fa-sort'; });
    const icono = th.querySelector('i');
    if (icono) icono.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
}

// Mostrar toasts basados en parámetros GET
(function() {
    let intentos = 0;

    function mostrarToast() {
        if (typeof toast === 'undefined' || !toast) {
            if (intentos < 30) {
                intentos++;
                setTimeout(mostrarToast, 100);
            }
            return;
        }

        const url = new URL(window.location);
        const params = url.searchParams;

        if (params.has('exito')) {
            window.setTimeout(function() {
                if (typeof toast !== 'undefined' && toast && toast.success) {
                    toast.success('SLA procesado', 'La configuración de SLA se guardó correctamente.');
                }
            }, 50);
            url.searchParams.delete('exito');
            window.history.replaceState({}, '', url.toString());
        }

        if (params.has('error')) {
            const errorMsg = params.get('error');
            window.setTimeout(function() {
                if (typeof toast !== 'undefined' && toast && toast.error) {
                    toast.error('Error', decodeURIComponent(errorMsg));
                }
            }, 50);
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url.toString());
        }
    }

    mostrarToast();
})();
</script>
