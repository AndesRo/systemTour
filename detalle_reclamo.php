<?php
session_start();
require 'includes/config.php';
include 'includes/funciones.php';

// Verificar autenticación
if (!isset($_SESSION['cliente_id']) && !isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// Obtener ID del reclamo
$id_reclamo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_reclamo === 0) {
    header('Location: ' . (isset($_SESSION['admin']) ? 'admin/dashboard.php' : 'perfil_cliente.php'));
    exit();
}

try {
    // Consulta base con JOIN para clientes
    $sql = "SELECT r.*, c.nombre as cliente_nombre, c.email, c.telefono 
            FROM reclamos r
            JOIN clientes c ON r.cliente_id = c.id
            WHERE r.id = :id_reclamo";
    
    // Si es cliente normal, verificar que sea dueño del reclamo
    if (isset($_SESSION['cliente_id'])) {
        $sql .= " AND r.cliente_id = :cliente_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_reclamo', $id_reclamo, PDO::PARAM_INT);
    
    if (isset($_SESSION['cliente_id'])) {
        $stmt->bindValue(':cliente_id', $_SESSION['cliente_id'], PDO::PARAM_INT);
    }

    $stmt->execute();
    $reclamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reclamo) {
        throw new Exception("Reclamo no encontrado o no autorizado");
    }

} catch(PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Reclamo</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .detalle-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .header-detalle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .btn-volver {
            background: #2c3e50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-volver:hover {
            background: #34495e;
        }

        .seccion-detalle {
            margin-bottom: 2rem;
        }

        .detalle-item {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .detalle-item label {
            font-weight: 600;
            color: #2c3e50;
        }

        .estado-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .estado-pendiente { background: #ffe0e0; color: #d32f2f; }
        .estado-en-proceso { background: #fff3e0; color: #f57c00; }
        .estado-resuelto { background: #e8f5e9; color: #2e7d32; }

        @media (max-width: 768px) {
            .detalle-item {
                grid-template-columns: 1fr;
            }
            
            .header-detalle {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="detalle-container">
        <?php if(isset($error)): ?>
            <div class="alerta error"><?= $error ?></div>
        <?php else: ?>
            <div class="header-detalle">
                <h1>Detalle del Reclamo <?= htmlspecialchars($reclamo['ticket_id']) ?></h1>
                <a href="<?= isset($_SESSION['admin']) ? 'admin/dashboard.php' : 'perfil_cliente.php' ?>" class="btn-volver">
                    ← Volver
                </a>
            </div>

            <!-- Sección de información del cliente -->
            <div class="seccion-detalle">
                <h2>Información del Cliente</h2>
                <div class="detalle-item">
                    <label>Nombre:</label>
                    <div><?= htmlspecialchars($reclamo['cliente_nombre']) ?></div>
                    
                    <label>Email:</label>
                    <div><?= htmlspecialchars($reclamo['email']) ?></div>
                    
                    <label>Teléfono:</label>
                    <div><?= htmlspecialchars($reclamo['telefono'] ?? 'No registrado') ?></div>
                </div>
            </div>

            <!-- Sección de detalles del incidente -->
            <div class="seccion-detalle">
                <h2>Detalles del Incidente</h2>
                <div class="detalle-item">
                    <label>Fecha del incidente:</label>
                    <div><?= date('d/m/Y', strtotime($reclamo['fecha_incidente'])) ?></div>
                    
                    <label>Lugar:</label>
                    <div><?= htmlspecialchars($reclamo['lugar']) ?></div>
                    
                    <label>Fecha de registro:</label>
                    <div><?= date('d/m/Y H:i', strtotime($reclamo['fecha_creacion'])) ?></div>
                    
                    <label>Estado actual:</label>
                    <div>
                        <span class="estado-badge estado-<?= strtolower(str_replace(' ', '-', $reclamo['estado'])) ?>">
                            <?= formatearEstado($reclamo['estado']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sección de descripción -->
            <div class="seccion-detalle">
                <h2>Descripción detallada</h2>
                <div class="detalle-item">
                    <div style="grid-column: 1 / -1; white-space: pre-wrap;"><?= htmlspecialchars($reclamo['descripcion']) ?></div>
                </div>
            </div>

           <!-- Sección de Comentarios del Administrador -->
<div class="seccion-detalle">
    <h2>Comentarios del Administrador</h2>
    <div class="detalle-item">
        <div style="grid-column: 1 / -1; white-space: pre-wrap; min-height: 80px; padding: 15px;">
            <?php if(!empty($reclamo['comentario_admin'])): ?>
                <?= nl2br(htmlspecialchars_decode($reclamo['comentario_admin'])) ?>
            <?php else: ?>
                <em style="color: #666;">Aún no hay comentarios del administrador.</em>
            <?php endif; ?>
        </div>
    </div>
</div>

            <!-- Botones de acción -->
            <div class="acciones">
                <a href="generar_pdf.php?ticket=<?= $reclamo['ticket_id'] ?>" class="btn-volver" target="_blank">
                    Descargar PDF
                </a>
                <?php if(isset($_SESSION['admin'])): ?>
                    <a href="admin/editar_reclamo.php?id=<?= $reclamo['id'] ?>" class="btn-volver">
                        Editar Estado
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>