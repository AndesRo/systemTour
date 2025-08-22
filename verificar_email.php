<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/funciones.php';

$mensaje = '';
$tipo_mensaje = ''; // success, error, warning

// Verificar si se proporcionó un token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Buscar el token en la base de datos
        $stmt = $conn->prepare("SELECT id, cliente_id, expiracion FROM verificacion_email WHERE token = ? AND usado = 0");
        $stmt->execute([$token]);
        $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verificacion) {
            // Verificar si el token ha expirado
            if (strtotime($verificacion['expiracion']) > time()) {
                // Token válido, marcar email como verificado
                $stmt = $conn->prepare("UPDATE clientes SET email_verificado = 1 WHERE id = ?");
                $stmt->execute([$verificacion['cliente_id']]);
                
                // Marcar token como usado
                $stmt = $conn->prepare("UPDATE verificacion_email SET usado = 1 WHERE id = ?");
                $stmt->execute([$verificacion['id']]);
                
                $mensaje = '¡Email verificado correctamente! Ahora puedes iniciar sesión en tu cuenta.';
                $tipo_mensaje = 'success';
                
                // Opcional: Iniciar sesión automáticamente
                $stmt = $conn->prepare("SELECT id, nombre, email FROM clientes WHERE id = ?");
                $stmt->execute([$verificacion['cliente_id']]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    $_SESSION['cliente_id'] = $usuario['id'];
                    $_SESSION['cliente_nombre'] = $usuario['nombre'];
                    $_SESSION['cliente_email'] = $usuario['email'];
                    $_SESSION['tipo_usuario'] = 'cliente';
                }
            } else {
                $mensaje = 'El enlace de verificación ha expirado. Por favor, solicita uno nuevo.';
                $tipo_mensaje = 'error';
                
                // Eliminar token expirado
                $stmt = $conn->prepare("DELETE FROM verificacion_email WHERE id = ?");
                $stmt->execute([$verificacion['id']]);
            }
        } else {
            $mensaje = 'El enlace de verificación no es válido o ya ha sido utilizado.';
            $tipo_mensaje = 'error';
        }
    } catch(PDOException $e) {
        $mensaje = 'Error en el sistema. Por favor, intenta más tarde.';
        $tipo_mensaje = 'error';
        error_log("Error en verificar_email: " . $e->getMessage());
    }
} else {
    $mensaje = 'No se proporcionó un token de verificación válido.';
    $tipo_mensaje = 'error';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email - Sistema de Reclamos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --warning: #f72585;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
            width: 100%;
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .verification-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .icon-success {
            color: #28a745;
        }

        .icon-error {
            color: #dc3545;
        }

        .verification-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .verification-message {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 20px;
            }
            
            .verification-icon {
                font-size: 48px;
            }
            
            .verification-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($tipo_mensaje == 'success'): ?>
            <div class="verification-icon icon-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="verification-title">¡Verificación Exitosa!</h1>
            <p class="verification-message"><?= $mensaje ?></p>
            <?php if (isset($_SESSION['cliente_id'])): ?>
                <a href="perfil_cliente.php" class="btn btn-success">
                    <i class="fas fa-user"></i> Ir a Mi Perfil
                </a>
            <?php else: ?>
                <a href="login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </a>
            <?php endif; ?>
        <?php else: ?>
            <div class="verification-icon icon-error">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1 class="verification-title">Error de Verificación</h1>
            <p class="verification-message"><?= $mensaje ?></p>
            <a href="recuperar_verificacion.php" class="btn">
                <i class="fas fa-envelope"></i> Solicitar Nuevo Enlace
            </a>
            <a href="login.php" class="btn" style="margin-top: 10px;">
                <i class="fas fa-sign-in-alt"></i> Volver al Login
            </a>
        <?php endif; ?>
    </div>
</body>
</html>