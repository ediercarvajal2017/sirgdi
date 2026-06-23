<!-- Página de Inicio (Welcome Page - Público) -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo config('app.app_name'); ?> - Sistema de Reportes de Daños</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .hero-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: white;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background-color: white;
            color: #667eea;
        }

        /* Features Section */
        .features {
            padding: 80px 20px;
            background-color: #f9f9f9;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 60px;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Steps Section */
        .steps {
            padding: 80px 20px;
        }

        .steps-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .steps h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 60px;
            color: #333;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .step {
            text-align: center;
        }

        .step-number {
            display: inline-block;
            width: 60px;
            height: 60px;
            background-color: #667eea;
            color: white;
            border-radius: 50%;
            line-height: 60px;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .step h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .step p {
            color: #666;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .cta-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .cta p {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .cta .btn {
            background-color: white;
            color: #667eea;
        }

        /* Footer */
        .footer {
            background-color: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .footer p {
            margin: 10px 0;
            font-size: 14px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .features h2,
            .steps h2,
            .cta h2 {
                font-size: 28px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <h1><?php echo config('app.app_name'); ?></h1>
            <p>Sistema integral de reportes de daños en infraestructura</p>
            <div class="hero-buttons">
                <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login" class="btn btn-primary">
                    Ingresar
                </a>
                <a href="#features" class="btn btn-secondary">
                    Conocer Más
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <h2>Características Principales</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Reportes Sencillos</h3>
                    <p>Cree reportes de daños en pocos clics. Especifique ubicación, categoría y descripción detallada.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Seguimiento Público</h3>
                    <p>Siga el estado de su reporte con un enlace único. No requiere autenticación.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Seguridad Garantizada</h3>
                    <p>Autenticación de dos factores, encriptación de datos, y auditoría completa.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⏱️</div>
                    <h3>SLA Riguroso</h3>
                    <p>Tiempos de respuesta garantizados. Escalación automática para daños críticos.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Estadísticas en Tiempo Real</h3>
                    <p>Dashboard con KPIs de gestión, análisis de tendencias y exportación de reportes.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📸</div>
                    <h3>Evidencia Fotográfica</h3>
                    <p>Registro de evidencia en 3 etapas (Antes, Durante, Después) comprimida automáticamente.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="steps">
        <div class="steps-container">
            <h2>¿Cómo Funciona?</h2>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Crear Reporte</h3>
                    <p>Ingrese con su cuenta y llene el formulario de daño. Especifique ubicación, categoría y descripción.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Recibir Ticket</h3>
                    <p>Obtenga un número de ticket único para seguimiento público sin necesidad de autenticación.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Gestión y Asignación</h3>
                    <p>El gestor prioriza el reporte y lo asigna a un técnico según carga y especialidad.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Intervención Técnica</h3>
                    <p>El técnico registra la intervención con evidencia fotográfica en 3 etapas.</p>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <h3>Validación y Cierre</h3>
                    <p>El gestor valida la solución. Encuesta de satisfacción al usuario final.</p>
                </div>
                <div class="step">
                    <div class="step-number">6</div>
                    <h3>Archivo</h3>
                    <p>Reporte cerrado con toda la documentación y evidencia disponible para auditoría.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-container">
            <h2>¿Listo para Empezar?</h2>
            <p>Acceda a su cuenta o contacte a su administrador para obtener credenciales.</p>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=autenticacion&accion=login" class="btn btn-primary">
                Ingresar Ahora
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div>
            <p>&copy; 2026 SIRGDI v2.0 | Sistema de Reportes de Daños</p>
            <p>
                <a href="#">Privacidad</a> |
                <a href="#">Términos de Servicio</a> |
                <a href="#">Contacto</a>
            </p>
        </div>
    </footer>
</body>
</html>
