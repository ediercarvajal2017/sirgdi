<script src="<?php echo config('app.url_base'); ?>/js/toast.js"></script>

<?php
    // Repoblación de formulario tras error de validación
    $old = $form_old ?? [];
    $campoErr = $form_campo_error ?? '';
    $valOld = function($k) use ($old) { return htmlspecialchars($old[$k] ?? '', ENT_QUOTES); };
    $errCls = function($k) use ($campoErr) { return $campoErr === $k ? ' input-error' : ''; };
?>

<div class="container usuarios-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-users"></i></div>
        <div class="page-banner__text">
            <h2>Gestionar Usuarios</h2>
            <p>Crear, editar y desactivar usuarios de la institución.</p>
        </div>
    </div>

    <?php if (!empty($credenciales)): ?>
        <!-- CREDENCIALES DEL NUEVO USUARIO (se muestran una sola vez) -->
        <div class="cred-card">
            <div class="cred-head">
                <i class="fas fa-circle-check"></i>
                <div>
                    <strong>Usuario creado.</strong> Entrega estas credenciales al usuario. <em>No se volverán a mostrar.</em>
                </div>
            </div>
            <div class="cred-body">
                <div class="cred-item">
                    <span class="cred-label"><i class="fas fa-envelope"></i> Usuario (email)</span>
                    <span class="cred-value" id="cred-email"><?php echo htmlspecialchars($credenciales['email']); ?></span>
                </div>
                <div class="cred-item">
                    <span class="cred-label"><i class="fas fa-key"></i> Contraseña</span>
                    <span class="cred-value" id="cred-pass"><?php echo htmlspecialchars($credenciales['password']); ?></span>
                </div>
                <button type="button" class="btn-copiar" onclick="copiarCredenciales()">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO ARRIBA -->
    <div class="form-modern-card">
        <h3><i class="fas fa-user-plus" id="form-icon"></i> <span id="form-title">Crear Nuevo Usuario</span></h3>
        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_usuarios" class="form-usuario" id="form-usuario">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="accion" id="form-accion" value="crear">
            <input type="hidden" name="id_usuario" id="id_usuario" value="">

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre Completo: <span class="required">*</span></label>
                    <input type="text" name="nombre" id="nombre" placeholder="Ej: Juan Pérez" required minlength="3" maxlength="150" class="input-modern<?php echo $errCls('nombre'); ?>" value="<?php echo $valOld('nombre'); ?>">
                </div>

                <div class="form-group">
                    <label for="numero_documento"><i class="fas fa-id-card"></i> Número de Documento: <span class="required">*</span></label>
                    <input type="text" name="numero_documento" id="numero_documento" placeholder="Ej: 1098765432" required minlength="5" maxlength="20" pattern="[0-9]+" inputmode="numeric" title="Solo números (5 a 20 dígitos)" class="input-modern<?php echo $errCls('numero_documento'); ?>" value="<?php echo $valOld('numero_documento'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="correo_electronico"><i class="fas fa-envelope"></i> Email: <span class="required">*</span></label>
                    <input type="email" name="correo_electronico" id="correo_electronico" placeholder="usuario@institucion.edu.co" required class="input-modern<?php echo $errCls('correo_electronico'); ?>" value="<?php echo $valOld('correo_electronico'); ?>">
                </div>

                <div class="form-group">
                    <label for="cargo_descripcion"><i class="fas fa-briefcase"></i> Cargo:</label>
                    <input type="text" name="cargo_descripcion" id="cargo_descripcion" placeholder="Ej: Docente" class="input-modern" value="<?php echo $valOld('cargo_descripcion'); ?>">
                </div>
            </div>

            <?php if (!empty($es_superadmin)): ?>
                <div class="form-row" id="grupo-institucion">
                    <div class="form-group" style="flex:1;">
                        <label for="id_institucion_objetivo"><i class="fas fa-school"></i> Institución del usuario: <span class="required">*</span></label>
                        <select name="id_institucion_objetivo" id="id_institucion_objetivo" class="input-modern" required>
                            <option value="">-- Selecciona la institución --</option>
                            <?php foreach ($instituciones as $inst): ?>
                                <option value="<?php echo $inst['id_institucion']; ?>"><?php echo htmlspecialchars($inst['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="hint">Solo aplica al crear. Al editar, se conserva la institución del usuario.</small>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="id_rol"><i class="fas fa-shield-alt"></i> Rol del Sistema: <span class="required">*</span></label>
                    <select name="id_rol" id="id_rol" required class="input-modern<?php echo $errCls('id_rol'); ?>">
                        <option value="">-- Selecciona un rol --</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id_rol']; ?>" <?php echo (isset($old['id_rol']) && (int)$old['id_rol'] === (int)$rol['id_rol']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="grupo-contrasena">
                    <label for="contrasena"><i class="fas fa-key"></i> <span id="label-contrasena">Contraseña inicial:</span></label>
                    <div class="pass-wrap">
                        <input type="text" name="contrasena" id="contrasena" minlength="6" maxlength="60" placeholder="Vacío = se genera automática" class="input-modern<?php echo $errCls('contrasena'); ?>">
                        <button type="button" class="btn-gen" onclick="generarContrasena()" title="Generar contraseña"><i class="fas fa-dice"></i></button>
                    </div>
                    <small class="hint" id="hint-contrasena">Si la dejas vacía, el sistema genera una y te la mostrará al guardar.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-modern" id="btn-submit">
                    <i class="fas fa-check"></i> Crear Usuario
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

    <!-- TABLA DE USUARIOS (ordenable + buscador) -->
    <div class="usuarios-content">
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay usuarios registrados.</p>
            </div>
        <?php else: ?>
            <div class="tabla-toolbar">
                <div class="buscador">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscar-usuarios" placeholder="Buscar por nombre, email, documento o rol…" onkeyup="filtrarUsuarios()">
                </div>
                <span class="total-usuarios"><strong id="contador-usuarios"><?php echo count($usuarios); ?></strong> usuarios</span>
            </div>

            <div class="tabla-wrap">
                <table class="tabla-usuarios" id="tabla-usuarios">
                    <thead>
                        <tr>
                            <th class="th-sort" onclick="ordenarUsuarios(this,0,'text')">Nombre <i class="fas fa-sort"></i></th>
                            <th class="th-sort" onclick="ordenarUsuarios(this,1,'num')">Documento <i class="fas fa-sort"></i></th>
                            <th class="th-sort" onclick="ordenarUsuarios(this,2,'text')">Email <i class="fas fa-sort"></i></th>
                            <th class="th-sort" onclick="ordenarUsuarios(this,3,'text')">Cargo <i class="fas fa-sort"></i></th>
                            <th class="th-sort" onclick="ordenarUsuarios(this,4,'text')">Rol <i class="fas fa-sort"></i></th>
                            <th class="th-sort th-center" onclick="ordenarUsuarios(this,5,'num')">Estado <i class="fas fa-sort"></i></th>
                            <?php if (!empty($es_superadmin)): ?>
                                <th class="th-sort" onclick="ordenarUsuarios(this,6,'text')">Institución <i class="fas fa-sort"></i></th>
                            <?php endif; ?>
                            <th class="th-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="fila-usuario">
                                <td class="td-nombre"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                <td data-sort="<?php echo htmlspecialchars($usuario['numero_documento'] ?? '0'); ?>"><?php echo htmlspecialchars($usuario['numero_documento'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['cargo_descripcion'] ?? '—'); ?></td>
                                <td>
                                    <?php if (!empty($usuario['nombre_rol'])): ?>
                                        <span class="badge-rol"><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($usuario['nombre_rol']); ?></span>
                                    <?php else: ?>
                                        <span class="tec-na">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="td-center" data-sort="<?php echo $usuario['activo'] ? 1 : 0; ?>">
                                    <span class="badge <?php echo $usuario['activo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $usuario['activo'] ? '<i class="fas fa-check"></i> Activo' : '<i class="fas fa-times"></i> Inactivo'; ?>
                                    </span>
                                </td>
                                <?php if (!empty($es_superadmin)): ?>
                                    <td><span class="badge-institucion"><i class="fas fa-school"></i> <?php echo htmlspecialchars($usuario['institucion_nombre'] ?? '—'); ?></span></td>
                                <?php endif; ?>
                                <td class="td-center td-acciones">
                                    <button type="button" class="btn-action-compact btn-edit"
                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES); ?>"
                                        data-documento="<?php echo htmlspecialchars($usuario['numero_documento'] ?? '', ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($usuario['correo_electronico'], ENT_QUOTES); ?>"
                                        data-cargo="<?php echo htmlspecialchars($usuario['cargo_descripcion'] ?? '', ENT_QUOTES); ?>"
                                        data-rol="<?php echo htmlspecialchars($usuario['id_rol'] ?? '', ENT_QUOTES); ?>"
                                        onclick="editarUsuario(this)" title="Editar usuario">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=gestionar_usuarios" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                        <button type="submit" class="btn-action-compact btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?')" title="Eliminar usuario">
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

.usuarios-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.usuarios-header {
    margin-bottom: 35px;
}

.usuarios-header h2 {
    font-size: 32px;
    font-weight: 700;
    color: var(--dark-text);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.usuarios-header h2 i {
    color: var(--primary-blue);
    font-size: 32px;
}

.subtitle {
    color: var(--gray-text);
    font-size: 14px;
    margin: 0;
    font-weight: 500;
}

.usuarios-content {
    margin-top: 40px;
}

/* ===== TABLA DE USUARIOS (ordenable) ===== */
.tabla-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
.tabla-toolbar .buscador { position:relative; flex:1; max-width:420px; }
.tabla-toolbar .buscador i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#7F8C8D; font-size:14px; }
.tabla-toolbar .buscador input { width:100%; padding:11px 14px 11px 40px; border:2px solid var(--primary-blue); border-radius:8px; font-size:14px; background:var(--light-bg); box-sizing:border-box; }
.tabla-toolbar .buscador input:focus { outline:none; border-color:var(--dark-blue); background:#fff; box-shadow:0 0 0 4px rgba(52,152,219,.1); }
.total-usuarios { color:#7F8C8D; font-size:14px; }
.total-usuarios strong { color:var(--dark-text); }

.tabla-wrap { background:#fff; border-radius:12px; box-shadow:0 4px 16px rgba(52,152,219,.08); overflow:auto; max-height:72vh; }
.tabla-usuarios { width:100%; border-collapse:collapse; font-size:13px; }
.tabla-usuarios thead th {
    position:sticky; top:0; z-index:2; background:linear-gradient(135deg,#EBF5FB,#D6EAF8);
    color:var(--dark-text); text-align:left; padding:13px 14px; font-size:12px; text-transform:uppercase;
    letter-spacing:.4px; border-bottom:2px solid var(--primary-blue); white-space:nowrap;
}
.tabla-usuarios th.th-center { text-align:center; }
.tabla-usuarios th.th-sort { cursor:pointer; user-select:none; transition:background .2s; }
.tabla-usuarios th.th-sort:hover { background:#D6EAF8; }
.tabla-usuarios th.th-sort i { margin-left:5px; font-size:11px; color:var(--primary-blue); opacity:.7; }
.tabla-usuarios tbody td { padding:12px 14px; border-bottom:1px solid #ECF0F1; color:var(--dark-text); vertical-align:middle; }
.tabla-usuarios tbody tr:hover { background:var(--light-bg); }
.tabla-usuarios .td-center { text-align:center; }
.tabla-usuarios .td-nombre { font-weight:600; }
.tabla-usuarios .td-acciones { white-space:nowrap; }
.tabla-usuarios .td-acciones .btn-action-compact { display:inline-flex; vertical-align:middle; margin:0 2px; }
.tabla-usuarios .td-acciones form { display:inline; }
.tec-na { color:#B0B7BC; }

@media(max-width:768px){ .tabla-wrap{ max-height:none; } }

.usuarios-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(52, 152, 219, 0.08);
    overflow: hidden;
}

.usuario-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #E8EDEF;
    transition: background-color 0.2s ease;
}

.usuario-row:last-child {
    border-bottom: none;
}

.usuario-row:hover {
    background-color: rgba(52, 152, 219, 0.03);
}

.usuario-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.usuario-nombre {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--dark-text);
}

.usuario-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
}

.usuario-email {
    color: var(--gray-text);
    font-weight: 500;
}

.usuario-cargo {
    color: var(--gray-text);
    font-weight: 500;
}

.usuario-doc {
    color: var(--gray-text);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.usuario-doc i {
    font-size: 11px;
    color: var(--primary-blue);
}

.usuario-status {
    display: flex;
    align-items: center;
    margin: 0 20px;
}

.usuario-actions {
    display: flex;
    gap: 8px;
}

.usuarios-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.08) 0%, rgba(41, 128, 185, 0.08) 100%);
    border-bottom: 2px solid var(--primary-blue);
    font-weight: 600;
    color: var(--dark-text);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.usuarios-header-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.usuarios-header-info span {
    display: block;
}

.header-secondary {
    font-weight: 400 !important;
    color: var(--gray-text);
    font-size: 11px;
    text-transform: none;
    letter-spacing: normal;
}

.usuarios-header-status {
    margin: 0 20px;
}

.usuarios-header-actions {
    min-width: 90px;
    text-align: center;
}

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

.badge-rol {
    background: linear-gradient(135deg, rgba(155, 89, 182, 0.2) 0%, rgba(52, 152, 219, 0.2) 100%);
    color: #8E44AD;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    margin-left: 8px;
    border: 1px solid rgba(155, 89, 182, 0.3);
}

.badge-institucion {
    background: rgba(52, 152, 219, 0.12);
    color: #2471A3;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    border: 1px solid rgba(52, 152, 219, 0.25);
}

.badge-rol i {
    font-size: 10px;
}

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

/* Estado de error en un campo */
.input-modern.input-error {
    border-color: #E74C3C !important;
    background: rgba(231, 76, 60, 0.05);
    box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.12);
    animation: shakeError 0.4s;
}
@keyframes shakeError {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
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

select.input-modern option {
    background: white;
    color: var(--dark-text);
    padding: 8px;
}

/* Campo contraseña con botón generar */
.pass-wrap { display: flex; gap: 8px; }
.pass-wrap .input-modern { flex: 1; }
.btn-gen {
    width: 46px; border: 2px solid var(--primary-blue); background: var(--light-bg);
    border-radius: 8px; cursor: pointer; color: var(--primary-blue); font-size: 16px;
    transition: all .3s; flex-shrink: 0;
}
.btn-gen:hover { background: var(--primary-blue); color: #fff; }
.hint { margin-top: 6px; font-size: 12px; color: var(--gray-text); }

/* Tarjeta de credenciales */
.cred-card {
    background: #fff; border-radius: 12px; margin-bottom: 25px; overflow: hidden;
    box-shadow: 0 4px 16px rgba(39,174,96,.15); border-left: 4px solid #27AE60;
}
.cred-head {
    display: flex; align-items: center; gap: 12px; padding: 16px 20px;
    background: rgba(39,174,96,.1); color: #1E8449; font-size: 14px;
}
.cred-head i { font-size: 22px; }
.cred-body {
    display: flex; align-items: flex-end; gap: 20px; padding: 18px 20px; flex-wrap: wrap;
}
.cred-item { display: flex; flex-direction: column; gap: 4px; }
.cred-label {
    font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
    color: var(--gray-text); font-weight: 600; display: flex; align-items: center; gap: 6px;
}
.cred-label i { color: var(--primary-blue); }
.cred-value {
    font-family: 'Courier New', monospace; font-size: 16px; font-weight: 700;
    color: var(--dark-text); background: var(--light-bg); padding: 8px 14px;
    border-radius: 6px; border: 1px dashed var(--primary-blue);
}
.btn-copiar {
    padding: 10px 18px; background: linear-gradient(135deg,var(--primary-blue),var(--dark-blue));
    color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;
    font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all .3s;
}
.btn-copiar:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(52,152,219,.35); }

.checkbox-modern-group {
    justify-content: flex-start;
}

.checkbox-modern-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-weight: 600;
    color: var(--dark-text);
    font-size: 14px;
}

.checkbox-modern {
    display: none;
}

.checkbox-custom {
    width: 24px;
    height: 24px;
    border: 2px solid var(--primary-blue);
    border-radius: 4px;
    background: var(--light-bg);
    flex-shrink: 0;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.checkbox-modern:checked + .checkbox-custom {
    background: var(--primary-blue);
    border-color: var(--dark-blue);
}

.checkbox-modern:checked + .checkbox-custom::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: white;
    font-size: 14px;
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .usuarios-header h2 {
        font-size: 24px;
    }

    .table {
        font-size: 12px;
    }

    .table th,
    .table td {
        padding: 10px 12px;
    }

    .actions {
        flex-direction: column;
    }

    .btn-small {
        width: 100%;
        justify-content: center;
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
function editarUsuario(btn) {
    // Llenar el formulario con los datos del usuario (desde data-attributes)
    document.getElementById('id_usuario').value = btn.dataset.id;
    document.getElementById('nombre').value = btn.dataset.nombre;
    document.getElementById('numero_documento').value = btn.dataset.documento;
    document.getElementById('correo_electronico').value = btn.dataset.email;
    document.getElementById('cargo_descripcion').value = btn.dataset.cargo;
    if (btn.dataset.rol) {
        document.getElementById('id_rol').value = btn.dataset.rol;
    }

    // Cambiar estado del formulario
    document.getElementById('form-accion').value = 'editar';
    document.getElementById('form-icon').className = 'fas fa-edit';
    document.getElementById('form-title').textContent = 'Editar Usuario';
    document.getElementById('btn-submit').innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
    document.getElementById('btn-cancel').style.display = 'flex';

    // En edición la contraseña es opcional (vacío = no cambiarla)
    document.getElementById('grupo-contrasena').style.display = '';
    document.getElementById('contrasena').value = '';
    document.getElementById('contrasena').placeholder = 'Dejar vacío para mantener la actual';
    document.getElementById('label-contrasena').textContent = 'Nueva contraseña (opcional):';
    document.getElementById('hint-contrasena').textContent = 'Solo escribe algo si deseas cambiar la contraseña del usuario.';

    // Selector de institución (superadmin): ocultarlo al editar — la institución se conserva
    var grpInst = document.getElementById('grupo-institucion');
    if (grpInst) {
        grpInst.style.display = 'none';
        var selInst = document.getElementById('id_institucion_objetivo');
        if (selInst) selInst.required = false;
    }

    // Scroll al formulario
    document.querySelector('.form-modern-card').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEdicion() {
    // Limpiar formulario
    document.getElementById('form-usuario').reset();
    document.getElementById('id_usuario').value = '';

    // Restablecer estado del formulario
    document.getElementById('form-accion').value = 'crear';
    document.getElementById('form-icon').className = 'fas fa-user-plus';
    document.getElementById('form-title').textContent = 'Crear Nuevo Usuario';
    document.getElementById('btn-submit').innerHTML = '<i class="fas fa-check"></i> Crear Usuario';
    document.getElementById('btn-cancel').style.display = 'none';

    // Restaurar el campo de contraseña a modo "crear"
    document.getElementById('grupo-contrasena').style.display = '';
    document.getElementById('contrasena').placeholder = 'Vacío = se genera automática';
    document.getElementById('label-contrasena').textContent = 'Contraseña inicial:';
    document.getElementById('hint-contrasena').textContent = 'Si la dejas vacía, el sistema genera una y te la mostrará al guardar.';

    // Restaurar el selector de institución (superadmin) para modo "crear"
    var grpInst = document.getElementById('grupo-institucion');
    if (grpInst) {
        grpInst.style.display = '';
        var selInst = document.getElementById('id_institucion_objetivo');
        if (selInst) selInst.required = true;
    }
}

// Generar una contraseña aleatoria legible
function generarContrasena() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    let pass = '';
    for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('contrasena').value = pass;
}

// Copiar credenciales del usuario recién creado
function copiarCredenciales() {
    const email = document.getElementById('cred-email').textContent.trim();
    const pass = document.getElementById('cred-pass').textContent.trim();
    const texto = 'Usuario: ' + email + '\nContraseña: ' + pass;
    navigator.clipboard.writeText(texto).then(function() {
        if (typeof toast !== 'undefined' && toast) {
            toast.success('Copiado', 'Credenciales copiadas al portapapeles.');
        }
    });
}

// Buscador en vivo de la tabla de usuarios
function filtrarUsuarios() {
    const q = document.getElementById('buscar-usuarios').value.toLowerCase().trim();
    const filas = document.querySelectorAll('#tabla-usuarios tbody tr.fila-usuario');
    let visibles = 0;
    filas.forEach(function(tr) {
        const mostrar = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    const cont = document.getElementById('contador-usuarios');
    if (cont) cont.textContent = visibles;
}

// Ordenar la tabla de usuarios al hacer clic en un encabezado
let ordenUsuarios = { col: null, asc: true };
function ordenarUsuarios(th, colIndex, tipo) {
    const tbody = document.querySelector('#tabla-usuarios tbody');
    const filas = Array.from(tbody.querySelectorAll('tr.fila-usuario'));
    if (filas.length === 0) return;

    const asc = (ordenUsuarios.col === colIndex) ? !ordenUsuarios.asc : true;
    ordenUsuarios = { col: colIndex, asc: asc };

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

    document.querySelectorAll('#tabla-usuarios thead th.th-sort i').forEach(function(ic) { ic.className = 'fas fa-sort'; });
    const icono = th.querySelector('i');
    if (icono) icono.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
}

// Mostrar toasts basados en parámetros GET
(function() {
    let intentos = 0;

    function mostrarToast() {
        // Máximo 30 intentos (3 segundos)
        if (typeof toast === 'undefined' || !toast) {
            if (intentos < 30) {
                intentos++;
                setTimeout(mostrarToast, 100);
            }
            return;
        }

        const url = new URL(window.location);
        const params = url.searchParams;

        // Mostrar éxito
        if (params.has('exito')) {
            window.setTimeout(function() {
                if (typeof toast !== 'undefined' && toast && toast.success) {
                    toast.success('Usuario procesado', 'Usuario procesado correctamente.');
                }
            }, 50);
            // Limpiar URL
            url.searchParams.delete('exito');
            window.history.replaceState({}, '', url.toString());
        }

        // Mostrar error
        if (params.has('error')) {
            const errorMsg = params.get('error');
            window.setTimeout(function() {
                if (typeof toast !== 'undefined' && toast && toast.error) {
                    toast.error('Error', decodeURIComponent(errorMsg));
                }
            }, 50);
            // Limpiar URL
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url.toString());
        }
    }

    // Iniciar intentos inmediatamente
    mostrarToast();
})();

// Tras un error de validación: restaurar modo edición, enfocar y limpiar el campo con error
(function() {
    const oldAccion = <?php echo json_encode($old['accion'] ?? ''); ?>;
    const oldIdUsuario = <?php echo json_encode($old['id_usuario'] ?? ''); ?>;
    const campoError = <?php echo json_encode($form_campo_error ?? ''); ?>;

    // Si veníamos de editar, restaurar el modo edición del formulario
    if (oldAccion === 'editar' || oldAccion === 'actualizar') {
        document.getElementById('form-accion').value = 'editar';
        document.getElementById('id_usuario').value = oldIdUsuario;
        document.getElementById('form-icon').className = 'fas fa-edit';
        document.getElementById('form-title').textContent = 'Editar Usuario';
        document.getElementById('btn-submit').innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        document.getElementById('btn-cancel').style.display = 'flex';
        document.getElementById('contrasena').placeholder = 'Dejar vacío para mantener la actual';
        document.getElementById('label-contrasena').textContent = 'Nueva contraseña (opcional):';
        document.getElementById('hint-contrasena').textContent = 'Solo escribe algo si deseas cambiar la contraseña del usuario.';
        var grpInst = document.getElementById('grupo-institucion');
        if (grpInst) {
            grpInst.style.display = 'none';
            var selInst = document.getElementById('id_institucion_objetivo');
            if (selInst) selInst.required = false;
        }
    }

    // Enfocar y desplazar al campo con error
    if (campoError) {
        const el = document.getElementById(campoError);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function() { el.focus(); }, 300);
            // Quitar el resaltado en cuanto el usuario corrija
            el.addEventListener('input', function() { el.classList.remove('input-error'); }, { once: true });
        }
    }
})();
</script>
