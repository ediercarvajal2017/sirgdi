<!-- RF-25: Dashboard KPI -->
<div class="dashboard-container">
    <h2>Dashboard - Estadísticas en Tiempo Real</h2>

    <!-- KPIs Principales -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-title">Total de Reportes</div>
            <div class="kpi-value"><?php echo $kpis['total_reportes']; ?></div>
            <div class="kpi-subtitle">Todos los reportes en el sistema</div>
        </div>

        <div class="kpi-card active">
            <div class="kpi-title">Reportes Activos</div>
            <div class="kpi-value"><?php echo $kpis['reportes_activos']; ?></div>
            <div class="kpi-subtitle">En proceso o pendientes</div>
        </div>

        <div class="kpi-card completed">
            <div class="kpi-title">Reportes Cerrados</div>
            <div class="kpi-value"><?php echo $kpis['reportes_cerrados']; ?></div>
            <div class="kpi-subtitle">Completados exitosamente</div>
        </div>

        <div class="kpi-card critical">
            <div class="kpi-title">Reportes Urgentes</div>
            <div class="kpi-value"><?php echo $kpis['reportes_urgentes']; ?></div>
            <div class="kpi-subtitle">Requieren atención inmediata</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Tasa de Cierre</div>
            <div class="kpi-value"><?php echo $kpis['tasa_cierre']; ?>%</div>
            <div class="kpi-subtitle">Reportes cerrados vs total</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Días Promedio de Resolución</div>
            <div class="kpi-value"><?php echo $promedio_dias_resolucion; ?></div>
            <div class="kpi-subtitle">Tiempo medio de solución</div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="charts-grid">
        <!-- Reportes por Estado -->
        <div class="chart-card">
            <h3>Reportes por Estado</h3>
            <table class="mini-table">
                <tr>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
                <?php
                $total = array_sum(array_column($reportes_por_estado, 'cantidad'));
                foreach ($reportes_por_estado as $item):
                    $porcentaje = $total > 0 ? round(($item['cantidad'] / $total) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['estado']); ?></td>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $porcentaje; ?>%"></div>
                        </div>
                        <?php echo $porcentaje; ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Reportes por Urgencia -->
        <div class="chart-card">
            <h3>Reportes por Urgencia</h3>
            <table class="mini-table">
                <tr>
                    <th>Urgencia</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
                <?php
                $total = array_sum(array_column($reportes_por_urgencia, 'cantidad'));
                foreach ($reportes_por_urgencia as $item):
                    $porcentaje = $total > 0 ? round(($item['cantidad'] / $total) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['urgencia']); ?></td>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $porcentaje; ?>%"></div>
                        </div>
                        <?php echo $porcentaje; ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Satisfacción del Usuario -->
    <div class="satisfaction-card">
        <h3>Satisfacción del Reportante</h3>
        <div class="satisfaction-stats">
            <div class="stat">
                <div class="stat-title">Encuestas Respondidas</div>
                <div class="stat-value"><?php echo $satisfaccion['respondidas']; ?>/<?php echo $satisfaccion['total']; ?></div>
                <div class="stat-subtitle">Tasa: <?php echo $satisfaccion['tasa_respuesta']; ?>%</div>
            </div>

            <div class="stat">
                <div class="stat-title">Calificación Promedio</div>
                <div class="stat-value"><?php echo $satisfaccion['calificacion_promedio']; ?>/5.0</div>
                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $satisfaccion['calificacion_promedio'] ? 'filled' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="stat">
                <div class="stat-title">Satisfechos</div>
                <div class="stat-value" style="color: #4caf50;"><?php echo $satisfaccion['satisfechos']; ?></div>
            </div>

            <div class="stat">
                <div class="stat-title">Insatisfechos</div>
                <div class="stat-value" style="color: #f44336;"><?php echo $satisfaccion['insatisfechos']; ?></div>
            </div>
        </div>
    </div>

    <!-- Alertas SLA Vencido -->
    <?php if (!empty($sla_vencidos)): ?>
        <div class="alert-card critical">
            <h3>⚠️ SLA Vencidos (Acción Inmediata Requerida)</h3>
            <table class="critical-table">
                <tr>
                    <th>Ticket</th>
                    <th>Días Vencido</th>
                    <th>Urgencia</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($sla_vencidos as $reporte): ?>
                <tr>
                    <td><?php echo htmlspecialchars($reporte['numero_ticket']); ?></td>
                    <td><?php echo abs(round($reporte['horas_restantes'] / 24, 1)); ?> días</td>
                    <td>
                        <span class="urgency-badge">
                            <?php
                            $urgencias = [1 => 'No Urgente', 2 => 'Moderado', 3 => 'Importante', 4 => 'Urgente'];
                            echo htmlspecialchars($urgencias[$reporte['id_urgencia_calculada']]);
                            ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo config('app.url_base'); ?>/?controlador=reportes&accion=detalle&id=<?php echo $reporte['id_reporte']; ?>" class="btn-small">
                            Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

    <!-- Botones de Exportación -->
    <div class="export-section">
        <h3>Exportar Datos</h3>
        <div class="export-buttons">
            <a href="<?php echo config('app.url_base'); ?>/?controlador=dashboard&accion=exportar&tipo=reportes" class="btn btn-export">
                📊 Exportar Reportes (CSV)
            </a>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=dashboard&accion=exportar&tipo=encuestas" class="btn btn-export">
                😊 Exportar Encuestas (CSV)
            </a>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=dashboard&accion=exportar&tipo=auditoria" class="btn btn-export">
                📋 Exportar Auditoría (CSV)
            </a>
        </div>
    </div>
</div>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .dashboard-container h2 {
        margin-bottom: 30px;
        color: #333;
    }

    /* KPI Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .kpi-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-top: 4px solid #667eea;
        text-align: center;
    }

    .kpi-card.active {
        border-top-color: #ff9800;
    }

    .kpi-card.completed {
        border-top-color: #4caf50;
    }

    .kpi-card.critical {
        border-top-color: #f44336;
    }

    .kpi-title {
        font-size: 12px;
        color: #999;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .kpi-value {
        font-size: 36px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }

    .kpi-subtitle {
        font-size: 12px;
        color: #bbb;
    }

    /* Charts Grid */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .chart-card h3 {
        margin-bottom: 20px;
        color: #333;
    }

    .mini-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .mini-table th {
        background: #f5f5f5;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        border-bottom: 1px solid #ddd;
    }

    .mini-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .progress-bar {
        height: 6px;
        background: #eee;
        border-radius: 3px;
        overflow: hidden;
        margin: 5px 0;
    }

    .progress {
        height: 100%;
        background: #667eea;
        transition: width 0.3s;
    }

    /* Satisfaction */
    .satisfaction-card {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }

    .satisfaction-card h3 {
        margin-bottom: 20px;
    }

    .satisfaction-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }

    .stat {
        text-align: center;
    }

    .stat-title {
        font-size: 12px;
        color: #999;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }

    .stat-subtitle {
        font-size: 12px;
        color: #bbb;
    }

    .rating {
        font-size: 20px;
        margin-top: 5px;
    }

    .star {
        color: #ddd;
        margin: 0 2px;
    }

    .star.filled {
        color: #ffc107;
    }

    /* Alert Card */
    .alert-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #f44336;
        margin-bottom: 40px;
    }

    .alert-card h3 {
        color: #f44336;
        margin-bottom: 15px;
    }

    .critical-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .critical-table th {
        background: #ffebee;
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ffcdd2;
    }

    .critical-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    /* Export Section */
    .export-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .export-section h3 {
        margin-bottom: 15px;
    }

    .export-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-export {
        padding: 10px 20px;
        background: #4caf50;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        transition: background 0.3s;
    }

    .btn-export:hover {
        background: #388e3c;
    }

    .urgency-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 11px;
        background: #fff3e0;
        color: #f57c00;
    }

    .btn-small {
        padding: 6px 12px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 3px;
        font-size: 12px;
    }

    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .satisfaction-stats {
            grid-template-columns: 1fr;
        }

        .export-buttons {
            flex-direction: column;
        }

        .btn-export {
            width: 100%;
        }
    }
</style>
