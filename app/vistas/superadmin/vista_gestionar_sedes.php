<!-- Gestionar Sedes de Institución -->
<div class="sedes-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-building"></i></div>
        <div class="page-banner__text">
            <h2>Gestionar Sedes</h2>
            <p>Institución: <strong><?php echo htmlspecialchars($institucion['nombre']); ?></strong></p>
        </div>
    </div>

    <!-- Formulario para crear/editar sede -->
    <div class="form-section">
        <h3><i class="fas fa-plus-circle"></i> Agregar Nueva Sede</h3>

        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=procesar_sede" class="form-sede">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="id_institucion" value="<?php echo $institucion['id_institucion']; ?>">

            <div class="form-group">
                <label for="nombre"><i class="fas fa-signature"></i> Nombre de la Sede <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="input-modern" placeholder="Ej: Sede Principal, Sede Centro" required minlength="3" maxlength="90">
                <small class="form-help">Máximo 90 caracteres</small>
            </div>

            <div class="form-group">
                <label for="direccion"><i class="fas fa-barcode"></i> Código DANE <span class="required">*</span></label>
                <input type="text" id="direccion" name="direccion" class="input-modern" placeholder="Ej: 1234567890" required pattern="[0-9]{5,13}" minlength="5" maxlength="13" inputmode="numeric" title="Debe contener entre 5 y 13 dígitos numéricos">
                <small class="form-help">Entre 5 y 13 dígitos numéricos (Ej: 1234567890)</small>
            </div>

            <div class="form-group-checkbox">
                <input type="checkbox" id="activa" name="activa" value="1" checked>
                <label for="activa"><i class="fas fa-toggle-on"></i> Sede Activa</label>
            </div>

            <button type="submit" class="btn-modern btn-submit">
                <i class="fas fa-save"></i> Agregar Sede
            </button>
        </form>
    </div>

    <!-- Lista de sedes actuales -->
    <div class="sedes-list-section">
        <h3><i class="fas fa-list"></i> Sedes Existentes</h3>

        <?php if (!empty($sedes)): ?>
            <div class="sedes-grid">
                <?php foreach ($sedes as $sede): ?>
                    <div class="sede-card">
                        <div class="sede-header-card">
                            <h4><?php echo htmlspecialchars($sede['nombre']); ?></h4>
                            <span class="badge <?php echo $sede['activa'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                <?php echo $sede['activa'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </div>

                        <?php if (!empty($sede['direccion'])): ?>
                            <p class="sede-direccion">
                                <i class="fas fa-barcode"></i>
                                <strong>DANE:</strong> <?php echo htmlspecialchars($sede['direccion']); ?>
                            </p>
                        <?php endif; ?>

                        <p class="sede-fecha">
                            <i class="fas fa-calendar"></i>
                            Creada: <?php echo date('d/m/Y', strtotime($sede['fecha_creacion'])); ?>
                        </p>

                        <div class="sede-actions">
                            <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=editar_sede&id=<?php echo $sede['id_sede']; ?>&inst=<?php echo $institucion['id_institucion']; ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=eliminar_sede" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="id_sede" value="<?php echo $sede['id_sede']; ?>">
                                <input type="hidden" name="id_institucion" value="<?php echo $institucion['id_institucion']; ?>">
                                <button type="submit" class="btn-action btn-delete" onclick="return confirm('¿Eliminar esta sede?');">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay sedes registradas para esta institución.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="actions-footer">
        <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=inicio" class="btn-modern btn-back">
            <i class="fas fa-arrow-left"></i> Volver a Instituciones
        </a>
    </div>
</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
        --light-gray: #ECF0F1;
        --success-green: #27AE60;
        --danger-red: #E74C3C;
    }

    .sedes-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .sedes-header {
        margin-bottom: 35px;
    }

    .sedes-header h2 {
        font-size: 32px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sedes-header h2 i {
        color: var(--primary-blue);
        font-size: 32px;
    }

    .header-subtitle {
        color: var(--gray-text);
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }

    /* Sección de Formulario */
    .form-section {
        background: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 35px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
        border-left: 4px solid var(--primary-blue);
    }

    .form-section h3 {
        color: var(--dark-text);
        font-size: 18px;
        margin: 0 0 24px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section h3 i {
        color: var(--primary-blue);
    }

    .form-sede {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        align-items: flex-end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .form-group label i {
        color: var(--primary-blue);
        font-size: 14px;
    }

    .input-modern {
        padding: 12px 15px;
        border: 2px solid var(--light-gray);
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        background-color: white;
        color: var(--dark-text);
        transition: all 0.3s ease;
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .form-group-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: rgba(52, 152, 219, 0.05);
        border-radius: 6px;
    }

    .form-group-checkbox input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .form-group-checkbox label {
        margin: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
    }

    /* Sección de Lista de Sedes */
    .sedes-list-section {
        background: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
    }

    .sedes-list-section h3 {
        color: var(--dark-text);
        font-size: 18px;
        margin: 0 0 24px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sedes-list-section h3 i {
        color: var(--primary-blue);
    }

    .sedes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .sede-card {
        border: 2px solid var(--light-gray);
        border-radius: 10px;
        padding: 20px;
        background: white;
        transition: all 0.3s ease;
    }

    .sede-card:hover {
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
        border-color: var(--primary-blue);
    }

    .sede-header-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        gap: 10px;
    }

    .sede-header-card h4 {
        color: var(--dark-text);
        font-size: 16px;
        margin: 0;
        flex: 1;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .badge-activo {
        background: rgba(39, 174, 96, 0.15);
        color: var(--success-green);
    }

    .badge-inactivo {
        background: rgba(231, 76, 60, 0.15);
        color: var(--danger-red);
    }

    .sede-direccion {
        color: var(--gray-text);
        font-size: 13px;
        margin: 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sede-fecha {
        color: var(--gray-text);
        font-size: 12px;
        margin: 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sede-actions {
        display: flex;
        gap: 10px;
        margin-top: 16px;
        border-top: 1px solid var(--light-gray);
        padding-top: 16px;
    }

    .btn-action {
        flex: 1;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-edit {
        background: rgba(52, 152, 219, 0.15);
        color: var(--primary-blue);
    }

    .btn-edit:hover {
        background: var(--primary-blue);
        color: white;
    }

    .btn-delete {
        background: rgba(231, 76, 60, 0.15);
        color: var(--danger-red);
    }

    .btn-delete:hover {
        background: var(--danger-red);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 60px 40px;
        color: var(--gray-text);
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
        display: block;
    }

    .empty-state p {
        margin: 0;
        font-size: 15px;
    }

    .actions-footer {
        text-align: center;
    }

    .btn-back {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: var(--gray-text);
        color: white;
    }

    @media (max-width: 768px) {
        .form-sede {
            grid-template-columns: 1fr;
        }

        .sedes-grid {
            grid-template-columns: 1fr;
        }

        .sedes-header h2 {
            font-size: 24px;
        }
    }
</style>
