<?php
session_start();
include 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Reclamos Turísticos</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        :root {
            --color-primario: #2c3e50;
            --color-secundario: #3498db;
            --degradado: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .hero-section {
            flex: 1;
            background: var(--degradado);
            color: white;
            padding: 4rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.05em;
        }

        .hero-description {
            font-size: 1.25rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .acciones-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .accion-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 2.5rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .accion-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .accion-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .accion-icon {
            width: 40px;
            height: 40px;
            fill: currentColor;
        }

        .accion-texto {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: white;
            color: var(--color-primario);
        }

        .btn-login:hover {
            background: #f8f9fa;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-registro {
            background: var(--color-secundario);
            color: white;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .btn-registro:hover {
            background: #2980b9;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-description {
                font-size: 1.1rem;
            }

            .acciones-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Sistema Gestion de Reclamos</h1>
            <p class="hero-description">
                Sistema oficial para la gestión y seguimiento de reclamos turísticos. 
                Garantizamos la protección de tus derechos como viajero y una atención 
                personalizada para cada caso.
            </p>
            
            <div class="acciones-container">
                <!-- Tarjeta de Inicio de Sesión -->
                <div class="accion-card" onclick="location.href='login.php'">
                    <h3>
                        <svg class="accion-icon" viewBox="0 0 24 24">
                            <path d="M12 17a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2m6-9v6h-2V8h2m0 9h2v-2h-2v2M4 8h2v6H4V8m12 9v2h2v-2h-2M4 19h2v-2H4v2"/>
                        </svg>
                        Acceso al Sistema
                    </h3>
                    <p class="accion-texto">
                        Si ya tienes una cuenta, inicia sesión para consultar 
                        el estado de tus reclamos o realizar nuevas gestiones.
                    </p>
                    <a href="login.php" class="btn-hero btn-login">
                        Iniciar Sesión
                    </a>
                </div>

                <!-- Tarjeta de Registro -->
                <div class="accion-card" onclick="location.href='registro_cliente.php'">
                    <h3>
                        <svg class="accion-icon" viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        Nuevo Cliente 
                    </h3>
                    <p class="accion-texto">
                        Regístrate Aqui para crear una cuenta y acceder a todos tu reclamos.
                    </p>
                    <a href="registro_cliente.php" class="btn-hero btn-registro">
                        Crear Cuenta
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>