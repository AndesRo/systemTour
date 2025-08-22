<?php
session_start();
require 'includes/config.php'; // Asegurar que incluye la conexión a DB
include 'includes/funciones.php';

// Verificar sesión y tipo de usuario
if (!isset($_SESSION['cliente_id']) || $_SESSION['tipo_usuario'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

// Inicializar variables
$cliente_id = $_SESSION['cliente_id'];
$reclamos = [];
$error = '';
$exito = '';

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['exito'])) {
    $exito = $_SESSION['exito'];
    unset($_SESSION['exito']);
}

try {
    // Obtener datos del cliente
    $stmt_cliente = $conn->prepare("SELECT nombre, email, telefono, fecha_registro FROM clientes WHERE id = ?");
    $stmt_cliente->execute([$cliente_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new Exception("Cliente no encontrado en la base de datos");
    }

    // Procesar nuevo reclamo
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $ticket_id = 'TKT-' . strtoupper(uniqid());
        
        $datos = [
            'fecha_incidente' => $_POST['fecha_incidente'],
            'lugar' => htmlspecialchars($_POST['lugar']),
            'descripcion' => htmlspecialchars($_POST['descripcion']),
            'cliente_id' => $cliente_id
        ];

        $stmt_insert = $conn->prepare("INSERT INTO reclamos 
            (cliente_id, fecha_incidente, lugar, descripcion, ticket_id)
            VALUES (:cliente_id, :fecha_incidente, :lugar, :descripcion, :ticket_id)");

        $stmt_insert->execute([
            ':cliente_id' => $datos['cliente_id'],
            ':fecha_incidente' => $datos['fecha_incidente'],
            ':lugar' => $datos['lugar'],
            ':descripcion' => $datos['descripcion'],
            ':ticket_id' => $ticket_id
        ]);

        $_SESSION['exito'] = "Reclamo registrado exitosamente! Tu número de ticket es: $ticket_id";
        header('Location: perfil_cliente.php');
        exit();
    }

    // Obtener reclamos del cliente
    $stmt_reclamos = $conn->prepare("SELECT * FROM reclamos WHERE cliente_id = :cliente_id ORDER BY fecha_creacion DESC");
    $stmt_reclamos->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt_reclamos->execute();
    $reclamos = $stmt_reclamos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema de Reclamos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --warning: #0e0c1bff;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark);
            min-height: 100vh;
        }

        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .profile-header {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 16px;
            color: var(--gray);
        }

        .user-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: var(--light);
            border-radius: var(--border-radius);
            min-width: 120px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        /* Form Section */
        .form-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            height: fit-content;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Claims Section */
        .claims-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .claims-grid {
            display: grid;
            gap: 20px;
        }

        /* Claim Cards */
        .claim-card {
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid;
            background: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .claim-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .claim-card.pendiente { border-color: #ffc107; }
        .claim-card.en-proceso { border-color: #17a2b8; }
        .claim-card.resuelto { border-color: #28a745; }

        .claim-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .claim-id {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }

        .claim-date {
            color: var(--gray);
            font-size: 14px;
        }

        .claim-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .status-pendiente { background: #fff3cd; color: #856404; }
        .status-en-proceso { background: #d1ecf1; color: #0c5460; }
        .status-resuelto { background: #d4edda; color: #155724; }

        .claim-actions {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }

        /* Buttons */
        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--light-gray);
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .btn-danger {
            background: var(--warning);
            color: white;
        }

        .btn-danger:hover {
            background: #c1121f;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .user-stats {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .claim-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .claim-actions {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 15px;
            }
            
            .profile-header,
            .form-section,
            .claims-section {
                padding: 20px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .header-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Header -->
        <header class="profile-header">
            <div class="user-info">
                <h1><?= htmlspecialchars($_SESSION['cliente_nombre']) ?></h1>
                <p><?= htmlspecialchars($_SESSION['cliente_email']) ?></p>
            </div>
            
            <div class="user-stats">
                <div class="stat-item">
                    <div class="stat-number"><?= count($reclamos) ?></div>
                    <div class="stat-label">Total Reclamos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?= count(array_filter($reclamos, function($r) { return $r['estado'] === 'Resuelto'; })) ?>
                    </div>
                    <div class="stat-label">Resueltos</div>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="logout.php" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas cerrar sesión?')">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </header>

        <!-- Alertas -->
        <?php if($exito): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $exito ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Contenido Principal -->
        <div class="main-content">
            <!-- Panel Nuevo Reclamo -->
            <section class="form-section">
                <h2 class="section-title"><i class="fas fa-plus-circle"></i> Nuevo Reclamo</h2>
                <form method="POST" onsubmit="return validarFecha()" class="form-grid">
                    <div class="form-group">
                        <label for="fecha_incidente">Fecha del incidente</label>
                        <input type="date" name="fecha_incidente" id="fecha_incidente" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lugar">Lugar del incidente</label>
                        <input type="text" name="lugar" id="lugar" placeholder="Ej: Hotel XYZ, Restaurante ABC..." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción detallada</label>
                        <textarea name="descripcion" id="descripcion" placeholder="Describe qué sucedió, incluye detalles importantes..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Reclamo
                    </button>
                </form>
            </section>

            <!-- Listado de Reclamos -->
            <section class="claims-section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-list"></i> Mis Reclamos</h2>
                </div>
                
                <div class="claims-grid">
                    <?php if(!empty($reclamos)): ?>
                        <?php foreach($reclamos as $reclamo): ?>
                            <div class="claim-card <?= strtolower(str_replace(' ', '-', $reclamo['estado'])) ?>">
                                <div class="claim-header">
                                    <span class="claim-id">#<?= htmlspecialchars($reclamo['ticket_id']) ?></span>
                                    <span class="claim-date"><?= date('d/m/Y', strtotime($reclamo['fecha_incidente'])) ?></span>
                                </div>
                                
                                <div class="claim-status status-<?= strtolower(str_replace(' ', '-', $reclamo['estado'])) ?>">
                                    <?= formatearEstado($reclamo['estado']) ?>
                                </div>
                                
                                <p><?= htmlspecialchars(substr($reclamo['descripcion'], 0, 100)) ?>...</p>
                                
                                <div class="claim-actions">
                                    <a href="detalle_reclamo.php?id=<?= $reclamo['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                    <a href="generar_pdf.php?ticket=<?= $reclamo['ticket_id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i> Descargar PDF
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No hay reclamos registrados
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <script>
        function validarFecha() {
            const fechaInput = document.getElementById('fecha_incidente');
            const hoy = new Date().toISOString().split('T')[0];
            
            if(fechaInput.value > hoy) {
                alert('La fecha del incidente no puede ser futura');
                return false;
            }
            return true;
        }

        // Establecer fecha máxima como hoy
        document.getElementById('fecha_incidente').max = new Date().toISOString().split("T")[0];
    </script>
</body>
</html>