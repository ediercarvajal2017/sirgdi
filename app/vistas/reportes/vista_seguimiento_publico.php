<!-- RF-09: Public Tracking (No Authentication Required) -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Reporte - SIRGDI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .ticket-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            font-family: 'Courier New', monospace;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #333;
            font-size: 16px;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }

        .status-registrado {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-en-proceso {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-solucionado {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-cerrado {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .urgency-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .urgency-urgente {
            background-color: #ffebee;
            color: #c62828;
        }

        .urgency-importante {
            background-color: #fff3e0;
            color: #e65100;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 20px;
        }

        .timeline-dot {
            width: 12px;
            height: 12px;
            background: #667eea;
            border-radius: 50%;
            margin-top: 4px;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .timeline-content {
            color: #666;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            border: none;
            font-weight: 500;
        }

        .btn:hover {
            background-color: #5568d3;
        }

        .footer-info {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo config('app.app_name'); ?></h1>
            <p>Seguimiento de Reporte</p>
        </div>

        <?php if ($reporte): ?>
            <!-- Ticket Info -->
            <div class="info-section">
                <div class="info-label">Número de Ticket</div>
                <div class="ticket-number"><?php echo htmlspecialchars($reporte['numero_ticket']); ?></div>
            </div>

            <!-- Estado y Urgencia -->
            <div class="info-section">
                <div class="info-label">Estado Actual</div>
                <?php
                $estados = [
                    1 => 'Registrado',
                    2 => 'En Proceso',
                    3 => 'Devuelto',
                    4 => 'Solucionado',
                    5 => 'En Validación',
                    6 => 'Cerrado',
                    7 => 'Cancelado',
                    8 => 'Anulado'
                ];
                $estado_clase = 'status-' . strtolower(str_replace(' ', '-', $estados[$reporte['id_estado']] ?? 'desconocido'));
                ?>
                <span class="status-badge <?php echo $estado_clase; ?>">
                    <?php echo htmlspecialchars($estados[$reporte['id_estado']] ?? 'Desconocido'); ?>
                </span>
            </div>

            <!-- Urgencia -->
            <div class="info-section">
                <div class="info-label">Urgencia</div>
                <?php
                $urgencias = [
                    1 => 'No Urgente',
                    2 => 'Moderado',
                    3 => 'Importante',
                    4 => 'Urgente'
                ];
                $urgencia_clase = 'urgency-' . strtolower($urgencias[$reporte['id_urgencia_calculada']] ?? '');
                ?>
                <span class="urgency-badge <?php echo $urgencia_clase; ?>">
                    <?php echo htmlspecialchars($urgencias[$reporte['id_urgencia_calculada']] ?? 'Desconocida'); ?>
                </span>
            </div>

            <!-- Ubicación -->
            <div class="info-section">
                <div class="info-label">Ubicación</div>
                <div class="info-value">
                    <?php
                    $ubicacion = htmlspecialchars($sede['nombre'] ?? 'Desconocida');
                    echo $ubicacion;
                    ?>
                </div>
            </div>

            <!-- Categoría -->
            <div class="info-section">
                <div class="info-label">Tipo de Daño</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($categoria['nombre'] ?? 'Desconocida'); ?>
                </div>
            </div>

            <!-- Descripción -->
            <div class="info-section">
                <div class="info-label">Descripción</div>
                <div class="info-value">
                    <?php echo nl2br(htmlspecialchars($reporte['descripcion_problema'])); ?>
                </div>
            </div>

            <!-- Fechas -->
            <div class="info-section">
                <div class="info-label">Fecha de Registro</div>
                <div class="info-value">
                    <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_hora_registro'])); ?>
                </div>
            </div>

            <!-- Timeline (Próximamente) -->
            <div class="info-section">
                <div class="info-label">Historial de Cambios</div>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            Reporte registrado el <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_hora_registro'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-info">
                <p>Este es un seguimiento público de su reporte. Use este enlace para verificar el estado en cualquier momento.</p>
                <p>&copy; 2026 SIRGDI - Sistema de Reportes de Daños</p>
            </div>

        <?php else: ?>
            <div style="text-align: center; color: #999;">
                <p>Reporte no encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
