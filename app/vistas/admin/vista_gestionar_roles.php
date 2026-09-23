<?php
/* Matriz roles × permisos. Variables: $roles, $permisos, $matriz, $puede_editar, $csrf_token */
$base = config('app.url_base');
$editable = !empty($puede_editar);

// Permisos agrupados por módulo, con un nombre legible por módulo
$nombres_modulo = [
    'reportes' => 'Reportes', 'gestion' => 'Gestión de tickets', 'cierre' => 'Validación y cierre',
    'tecnico' => 'Técnico', 'comentarios' => 'Comunicación', 'analitica' => 'Analítica',
    'admin' => 'Administración', 'superadmin' => 'Plataforma',
];
$por_modulo = [];
foreach ($permisos as $p) {
    $por_modulo[$p['modulo']][] = $p;
}
$total_editables = count(array_filter($roles, fn($r) => (int)$r['id_rol'] !== ROL_SUPERADMIN));
?>
<div class="container roles-container">

    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-user-shield"></i></div>
        <div class="page-banner__text">
            <h2>Roles y Permisos</h2>
            <p>Qué puede hacer cada rol en la plataforma. Las filas son permisos, las columnas son roles.</p>
        </div>
    </div>

    <?php if (!$editable): ?>
        <div class="aviso-card aviso-info">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Vista de consulta.</strong> Esta matriz es común a todas las instituciones de la plataforma,
                por eso solo el Superadministrador puede modificarla. Si necesitas un cambio, solicítalo al superadministrador.
            </div>
        </div>
    <?php else: ?>
        <div class="aviso-card aviso-warn">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>Los cambios afectan a todas las instituciones.</strong> Al guardar, cada rol queda exactamente
                con las casillas marcadas. El Superadministrador siempre tiene acceso a todo y no se edita aquí.
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo $base; ?>/?controlador=administrador&accion=guardar_permisos" id="form-permisos">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="matriz-wrap tabla-responsive-wrap">
            <table class="matriz">
                <thead>
                    <tr>
                        <th class="col-permiso">Permiso</th>
                        <?php foreach ($roles as $r): $es_sa = (int)$r['id_rol'] === ROL_SUPERADMIN; ?>
                            <th class="col-rol <?php echo $es_sa ? 'col-sa' : ''; ?>" title="<?php echo htmlspecialchars($r['descripcion'] ?? ''); ?>">
                                <span><?php echo htmlspecialchars($r['nombre_rol']); ?></span>
                                <?php if ($editable && !$es_sa): ?>
                                    <button type="button" class="btn-todos" data-rol="<?php echo (int)$r['id_rol']; ?>" title="Marcar o desmarcar toda la columna">todos</button>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($por_modulo as $modulo => $lista): ?>
                    <tr class="fila-modulo"><td colspan="<?php echo count($roles) + 1; ?>"><?php echo htmlspecialchars($nombres_modulo[$modulo] ?? ucfirst($modulo)); ?></td></tr>
                    <?php foreach ($lista as $p): ?>
                    <tr>
                        <td class="col-permiso td-largo" data-label="Permiso">
                            <code><?php echo htmlspecialchars($p['codigo']); ?></code>
                            <small><?php echo htmlspecialchars($p['descripcion'] ?? ''); ?></small>
                        </td>
                        <?php foreach ($roles as $r):
                            $id_rol = (int)$r['id_rol']; $id_p = (int)$p['id_permiso'];
                            $es_sa = $id_rol === ROL_SUPERADMIN;
                            $marcado = $es_sa || !empty($matriz[$id_rol][$id_p]);
                        ?>
                            <td class="col-rol <?php echo $es_sa ? 'col-sa' : ''; ?>" data-label="<?php echo htmlspecialchars($r['nombre_rol']); ?>">
                                <?php if ($es_sa): ?>
                                    <i class="fas fa-check celda-sa" title="Siempre"></i>
                                <?php else: ?>
                                    <input type="checkbox" name="permisos[<?php echo $id_rol; ?>][]" value="<?php echo $id_p; ?>"
                                           data-rol="<?php echo $id_rol; ?>"
                                           <?php echo $marcado ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>
                                           aria-label="<?php echo htmlspecialchars($r['nombre_rol'] . ': ' . $p['codigo']); ?>">
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($editable): ?>
        <div class="acciones">
            <span class="contador" id="contador-cambios">Sin cambios</span>
            <button type="submit" class="btn-modern" id="btn-guardar" disabled
                    onclick="return confirm('Se aplicará la matriz tal como está marcada a todas las instituciones. ¿Guardar?');">
                <i class="fas fa-floppy-disk"></i> Guardar cambios
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($editable): ?>
<script>
(function () {
    var form = document.getElementById('form-permisos');
    var boton = document.getElementById('btn-guardar');
    var contador = document.getElementById('contador-cambios');
    var casillas = Array.prototype.slice.call(form.querySelectorAll('input[type=checkbox]'));
    var inicial = casillas.map(function (c) { return c.checked; });

    function actualizar() {
        var cambios = 0;
        casillas.forEach(function (c, i) { if (c.checked !== inicial[i]) cambios++; });
        boton.disabled = cambios === 0;
        contador.textContent = cambios === 0 ? 'Sin cambios' : cambios + ' cambio' + (cambios === 1 ? '' : 's') + ' sin guardar';
    }
    casillas.forEach(function (c) { c.addEventListener('change', actualizar); });

    Array.prototype.forEach.call(form.querySelectorAll('.btn-todos'), function (b) {
        b.addEventListener('click', function () {
            var rol = b.getAttribute('data-rol');
            var columna = casillas.filter(function (c) { return c.getAttribute('data-rol') === rol; });
            var todasMarcadas = columna.every(function (c) { return c.checked; });
            columna.forEach(function (c) { c.checked = !todasMarcadas; });
            actualizar();
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (!boton.disabled) { e.preventDefault(); e.returnValue = ''; }
    });
    form.addEventListener('submit', function () { boton.disabled = true; });
})();
</script>
<?php endif; ?>

<style>
.roles-container { max-width: 1240px; margin: 30px auto; padding: 20px; }

.aviso-card { display:flex; align-items:flex-start; gap:14px; padding:14px 18px; border-radius:10px; margin-bottom:20px; font-size:14px; line-height:1.5; }
.aviso-card i { font-size:20px; margin-top:2px; }
.aviso-info { background:var(--color-info-bg); color:var(--color-info-text); border-left:4px solid var(--color-primary); }
.aviso-warn { background:var(--color-warning-bg); color:var(--color-warning-text); border-left:4px solid var(--color-warning); }

.matriz-wrap { background:var(--color-bg-elevated); border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.08); overflow:auto; max-height:75vh; }
.matriz { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
.matriz th, .matriz td { border-bottom:1px solid var(--color-border-subtle); }
.matriz thead th {
    position:sticky; top:0; z-index:2; background:var(--color-bg-subtle); color:var(--color-text);
    padding:12px 10px; font-size:12px; text-transform:uppercase; letter-spacing:.4px; text-align:center; vertical-align:bottom;
}
.matriz thead th span { display:block; }
.matriz thead th.col-permiso { text-align:left; }
.matriz .col-permiso { position:sticky; left:0; z-index:1; background:var(--color-bg-elevated); min-width:260px; padding:10px 14px; text-align:left; }
.matriz thead th.col-permiso { z-index:3; background:var(--color-bg-subtle); }
.matriz .col-permiso code { display:block; font-weight:700; color:var(--color-text); font-family:inherit; font-size:13px; }
.matriz .col-permiso small { display:block; color:var(--color-text-muted); font-size:11.5px; margin-top:2px; line-height:1.4; }
.matriz .col-rol { text-align:center; min-width:96px; padding:8px; }
.matriz .col-sa { background:var(--color-bg-subtle); }
.matriz tbody tr:hover td { background:var(--color-bg-hover); }
.matriz tbody tr:hover td.col-permiso { background:var(--color-bg-hover); }
.matriz input[type=checkbox] { width:18px; height:18px; cursor:pointer; accent-color:var(--color-primary); }
.matriz input[type=checkbox]:disabled { cursor:default; opacity:.75; }
.celda-sa { color:var(--color-success); font-size:15px; }
.fila-modulo td { background:var(--color-bg-subtle); color:var(--color-primary); font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.6px; padding:8px 14px; position:sticky; left:0; }
.btn-todos {
    margin-top:6px; font-size:10px; text-transform:lowercase; letter-spacing:0; padding:2px 8px; border-radius:10px;
    border:1px solid var(--color-border); background:var(--color-bg-elevated); color:var(--color-text-muted); cursor:pointer; font-family:inherit;
}
.btn-todos:hover { border-color:var(--color-primary); color:var(--color-primary); }

.acciones { display:flex; align-items:center; justify-content:flex-end; gap:16px; margin-top:18px; }
.contador { font-size:13px; color:var(--color-text-muted); }
.btn-modern {
    padding:12px 26px; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; text-transform:uppercase;
    letter-spacing:.4px; display:inline-flex; align-items:center; gap:8px; color:#fff; font-family:inherit; transition:all .2s;
    background:linear-gradient(135deg, var(--accent-solid), var(--accent-solid-hover));
}
.btn-modern:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 6px 18px rgba(52,152,219,.35); }
.btn-modern:disabled { background:var(--color-bg-subtle); color:var(--color-text-muted); border:1px solid var(--color-border); cursor:not-allowed; }

/* Tablet vertical / teléfono horizontal (769-1024px): aquí ya no aplica la
   vista de tarjetas, pero la matriz completa pide ~944px (columna de permiso
   260px + 6 roles de 96px) y no cabe: la última columna de rol quedaba fuera
   y había que descubrir un scroll horizontal. Se compactan las columnas y se
   permite partir los nombres largos de rol ("SUPERADMINISTRADOR"), con lo que
   la matriz cabe completa y se ven todos los roles. */
@media (min-width:769px) and (max-width:1024px) {
    .matriz .col-permiso { min-width:170px; padding:10px; }
    .matriz .col-rol { min-width:84px; padding:8px 4px; }
    .matriz thead th { padding:10px 4px; font-size:10px; letter-spacing:0; }
    .matriz thead th span { overflow-wrap:anywhere; }
    .matriz .col-permiso code { font-size:12px; }
    .matriz .col-permiso small { font-size:11px; }
}

@media (max-width:768px) {
    .acciones { flex-direction:column; align-items:stretch; }
    /* En la vista de tarjetas cada checkbox queda solo en su fila (Rol: [ ]);
       el tamaño por defecto del navegador es demasiado pequeño para tocar. */
    .matriz td.col-rol input[type="checkbox"] {
        width: 24px;
        height: 24px;
        cursor: pointer;
    }
}
</style>
