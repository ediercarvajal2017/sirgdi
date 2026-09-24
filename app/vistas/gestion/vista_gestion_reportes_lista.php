<!-- Gestión de Reportes - Lista Compacta -->
<div class="gestion-container">
    <div class="gestion-header">
        <h2><i class="fas fa-tasks"></i> Gestión de Reportes</h2>
        <p class="header-subtitle">Administra y cambia el estado de los reportes de daños</p>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <form method="GET" action="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=listar" class="filter-form">
            <div class="form-group-filter">
                <label for="estado"><i class="fas fa-check-circle"></i> Estado</label>
                <?php
                // Los valores venían escritos a mano y dos estaban mal: "En
                // Proceso" apuntaba al 2 (Asignado, que quedó vestigial y está
                // siempre vacío) y "Cerrado" al 6 (Devuelto). Filtrar por
                // cualquiera de los dos devolvía la lista equivocada.
                //
                // Ahora salen de las constantes, y están los ocho estados: los
                // cuatro que faltaban dejaban reportes sin forma de filtrarlos.
                $estados_filtro = [
                    ESTADO_REGISTRADO    => 'Registrado',
                    ESTADO_EN_PROCESO    => 'En Proceso',
                    ESTADO_SOLUCIONADO   => 'Solucionado',
                    ESTADO_EN_VALIDACION => 'En Validación',
                    ESTADO_DEVUELTO      => 'Devuelto',
                    ESTADO_CERRADO       => 'Cerrado',
                    ESTADO_ANULADO       => 'Anulado',
                ];
                $estado_elegido = $_GET['estado'] ?? '';
                ?>
                <select name="estado" id="estado" class="input-modern input-select">
                    <option value="">-- Todos --</option>
                    <?php foreach ($estados_filtro as $id_e => $nombre_e): ?>
                        <option value="<?php echo $id_e; ?>" <?php echo ((string) $estado_elegido === (string) $id_e) ? 'selected' : ''; ?>>
                            <?php echo $nombre_e; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-filter">
                <label for="urgencia"><i class="fas fa-exclamation-triangle"></i> Nivel de Prioridad de la Incidencia</label>
                <?php
                // Faltaba "No urgente": los reportes de urgencia 1 no se podían
                // aislar, y son la mayoría.
                $urgencias_filtro = [
                    URGENCIA_URGENTE    => 'Urgente',
                    URGENCIA_IMPORTANTE => 'Importante',
                    URGENCIA_MODERADO   => 'Moderado',
                    URGENCIA_NO_URGENTE => 'No urgente',
                ];
                $urgencia_elegida = $_GET['urgencia'] ?? '';
                ?>
                <select name="urgencia" id="urgencia" class="input-modern input-select">
                    <option value="">-- Todas --</option>
                    <?php foreach ($urgencias_filtro as $id_u => $nombre_u): ?>
                        <option value="<?php echo $id_u; ?>" <?php echo ((string) $urgencia_elegida === (string) $id_u) ? 'selected' : ''; ?>>
                            <?php echo $nombre_u; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-modern btn-filter">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=listar" class="btn-modern btn-clear">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Reportes -->
    <?php if (!empty($reportes)): ?>
        <div class="gestion-list">
            <?php foreach ($reportes as $reporte): ?>
                <?php
                $estado_id = isset($reporte['id_estado']) && $reporte['id_estado'] !== null ? intval($reporte['id_estado']) : 1;
                // El orden y los nombres deben coincidir con las constantes ESTADO_* de
                // lib/constantes.php. Antes faltaba "Asignado" y todo se corría una
                // posición: un reporte Devuelto se mostraba aquí como "Cerrado".
                $estados = [1 => 'Registrado', 2 => 'Asignado', 3 => 'En Proceso', 4 => 'Solucionado', 5 => 'En Validación', 6 => 'Devuelto', 7 => 'Cerrado', 8 => 'Anulado'];
                $estado_texto = $estados[$estado_id] ?? 'Desconocido';
                $urgencia_id = isset($reporte['id_urgencia_calculada']) ? $reporte['id_urgencia_calculada'] : 1;
                $urgencias = [1 => 'No Urgente', 2 => 'Moderado', 3 => 'Importante', 4 => 'Urgente'];
                $urgencia_texto = $urgencias[$urgencia_id] ?? 'Desconocida';
                $fecha = isset($reporte['fecha_hora_registro']) ? $reporte['fecha_hora_registro'] : (isset($reporte['fecha_registro']) ? $reporte['fecha_registro'] : '');
                $reportante = isset($reporte['nombre_reportante']) ? $reporte['nombre_reportante'] : 'N/A';
                ?>
                <div class="gestion-row">
                    <div class="gestion-info">
                        <div class="gestion-ticket-section">
                            <code class="ticket-code"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></code>
                            <span class="gestion-reportante">Por: <?php echo htmlspecialchars($reportante); ?></span>
                        </div>
                        <div class="gestion-meta">
                            <span class="gestion-fecha"><?php echo $fecha ? date('d/m/Y H:i', strtotime($fecha)) : 'N/A'; ?></span>
                        </div>
                    </div>

                    <div class="gestion-status">
                        <span class="badge badge-estado-<?php echo htmlspecialchars($estado_id); ?>">
                            <?php echo htmlspecialchars($estado_texto); ?>
                        </span>
                        <span class="badge badge-urgency-<?php echo htmlspecialchars($urgencia_id); ?>">
                            <?php echo htmlspecialchars($urgencia_texto); ?>
                        </span>
                    </div>

                    <div class="gestion-actions">
                        <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=cambiar_estado&id=<?php echo $reporte['id_reporte']; ?>" class="btn-action-compact btn-edit" title="Cambiar estado">
                            <i class="fas fa-exchange-alt"></i>
                        </a>
                        <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=detalle&id=<?php echo $reporte['id_reporte']; ?>&from=gestion" class="btn-action-compact btn-view" title="Ver detalles">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=listar&pagina=<?php echo ($pagina - 1); ?>" class="btn-pag btn-prev">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            <?php endif; ?>

            <span class="page-info">Página <strong><?php echo $pagina; ?></strong></span>

            <a href="<?php echo config('app.url_base'); ?>/?controlador=gestion&accion=listar&pagina=<?php echo ($pagina + 1); ?>" class="btn-pag btn-next">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No hay reportes que gestionar.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    :root {
        --primary-blue: var(--color-primary);
        --dark-blue: var(--color-primary-dark);
        --gray-text: var(--color-text-muted);
        --dark-text: var(--color-text);
        --light-bg: var(--color-bg-subtle);
        --light-gray: var(--color-bg-subtle);
    }

    .gestion-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
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

    /* Filtros */
    .filters-section {
        background: var(--color-bg-elevated);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
        border-left: 4px solid var(--primary-blue);
    }

    .filter-form {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group-filter {
        flex: 1;
        min-width: 150px;
        display: flex;
        flex-direction: column;
    }

    .form-group-filter label {
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

    .form-group-filter label i {
        color: var(--primary-blue);
        font-size: 14px;
    }

    .input-select {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid var(--primary-blue);
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        background-color: var(--light-bg);
        color: var(--dark-text);
        transition: all 0.3s ease;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233498DB' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 18px;
        padding-right: 38px;
    }

    .input-select:focus {
        outline: none;
        border-color: var(--dark-blue);
        background-color: var(--color-bg-elevated);
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .btn-modern {
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
    }

    .btn-clear {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
    }

    .btn-clear:hover {
        background: var(--gray-text);
        color: white;
        transform: translateY(-2px);
    }

    /* Lista de Reportes Compacta */
    .gestion-list {
        background: var(--color-bg-elevated);
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(52, 152, 219, 0.08);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .gestion-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--color-border-subtle);
        transition: background-color 0.2s ease;
    }

    .gestion-row:last-child {
        border-bottom: none;
    }

    .gestion-row:hover {
        background-color: rgba(52, 152, 219, 0.03);
    }

    .gestion-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .gestion-ticket-section {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ticket-code {
        background-color: var(--light-gray);
        padding: 4px 10px;
        border-radius: 4px;
        font-family: 'Monaco', 'Courier New', monospace;
        font-size: 12px;
        font-weight: 700;
        color: var(--primary-blue);
        white-space: nowrap;
    }

    .gestion-reportante {
        font-size: 13px;
        font-weight: 600;
        color: var(--dark-text);
    }

    .gestion-meta {
        display: flex;
        gap: 12px;
        font-size: 12px;
    }

    .gestion-fecha {
        color: var(--gray-text);
        font-weight: 500;
    }

    .gestion-status {
        display: flex;
        gap: 8px;
        align-items: center;
        margin: 0 20px;
    }

    .gestion-actions {
        display: flex;
        gap: 8px;
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

    .btn-edit {
        color: #F39C12;
        background: rgba(243, 156, 18, 0.1);
    }

    .btn-edit:hover {
        background: #F39C12;
        color: white;
        transform: scale(1.08);
    }

    .btn-view {
        color: var(--primary-blue);
        background: rgba(52, 152, 219, 0.1);
    }

    .btn-view:hover {
        background: var(--primary-blue);
        color: white;
        transform: scale(1.08);
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    <?php /* Misma paleta que el kanban (vista_kanban_gestion.php), para que un mismo
             estado se vea con el mismo color en cualquier pantalla. */ ?>
    .badge-estado-1 { background: rgba(127,140,141,.15); color: var(--gray-text); }
    .badge-estado-2 { background: rgba(52, 152, 219, 0.15); color: var(--primary-blue); }
    .badge-estado-3 { background: rgba(41, 128, 185, 0.15); color: #2980B9; }
    .badge-estado-4 { background: rgba(39, 174, 96, 0.15); color: var(--color-success); }
    .badge-estado-5 { background: rgba(155, 89, 182, 0.15); color: #8E44AD; }
    .badge-estado-6 { background: rgba(230, 126, 34, 0.15); color: var(--estado-devuelto-texto); }
    .badge-estado-7 { background: rgba(127,140,141,.18); color: var(--gray-text); }
    .badge-estado-8 { background: rgba(231, 76, 60, 0.15); color: var(--color-danger); }

    .badge-urgency-1 { background: rgba(46, 204, 113, 0.15); color: var(--color-success); }
    .badge-urgency-2 { background: rgba(230, 126, 34, 0.15); color: #E67E22; }
    .badge-urgency-3 { background: rgba(243, 156, 18, 0.15); color: #F39C12; }
    .badge-urgency-4 { background: rgba(231, 76, 60, 0.15); color: var(--color-danger); }

    /* Paginación */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: var(--color-bg-elevated);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
        flex-wrap: wrap;
    }

    .btn-pag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        text-decoration: none;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-pag:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
    }

    .page-info {
        color: var(--dark-text);
        font-size: 14px;
        font-weight: 600;
    }

    .page-info strong {
        color: var(--primary-blue);
        font-weight: 700;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 40px;
        background: var(--color-bg-elevated);
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

    .empty-state p {
        margin: 0;
        font-size: 15px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .filter-form {
            flex-direction: column;
        }

        .form-group-filter {
            width: 100%;
        }

        .filter-actions {
            width: 100%;
            gap: 8px;
        }

        .filter-actions .btn-modern {
            flex: 1;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .gestion-header h2 {
            font-size: 24px;
        }

        .filters-section {
            padding: 16px;
        }

        .gestion-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
        }

        .gestion-ticket-section {
            flex-wrap: wrap;
        }

        .gestion-status {
            margin: 0;
            width: 100%;
        }

        .gestion-actions {
            width: 100%;
            justify-content: flex-end;
        }

        .btn-action-compact {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }

        .btn-pag {
            padding: 8px 12px;
            font-size: 11px;
        }

        .pagination {
            gap: 10px;
        }

        .empty-state {
            padding: 60px 20px;
        }
    }
</style>
