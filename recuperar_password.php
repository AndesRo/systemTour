<?php
session_start();

// CORRECCIÓN: Usar la ruta correcta para incluir config.php
require_once 'includes/config.php';
require_once 'includes/funciones.php';
require_once 'vendor/autoload.php';

// Resto del código permanece igual...
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$exito = '';

// Verificar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingresa un correo electrónico válido.';
    } else {
        try {
            // Verificar si el email existe en la base de datos
            $stmt = $conn->prepare("SELECT id, nombre FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Generar token único
                $token = bin2hex(random_bytes(50));
                $expiracion = date("Y-m-d H:i:s", strtotime('+1 hour'));
                
                // Guardar token en la base de datos
                $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiracion) VALUES (?, ?, ?)");
                $stmt->execute([$email, $token, $expiracion]);
                
                // Crear enlace de recuperación
                $enlace_recuperacion = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                
                // Configurar y enviar email
                $mail = new PHPMailer(true);
                
                try {
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';

                    // Configuración del servidor SMTP (ajusta según tu configuración)
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';  // Servidor SMTP
                    $mail->SMTPAuth = true;
                    $mail->Username = 'andespart.ar@gmail.com'; // Tu correo
                    $mail->Password = 'wbmd jbex khvy ocfz'; // Tu contraseña
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => 
                    false, 'allow_self_signed' => true
                                              ]];               
                    $mail->Port = 587;
                    
                    // Destinatarios
                    $mail->setFrom('andespart@hotmail.com', 'Sistema de Reclamos');
                    $mail->addAddress($email, $usuario['nombre']);
                    
                    // Contenido
                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperación de Contraseña - Sistema de Reclamos';
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="utf-8">
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    line-height: 1.6;
                                    color: #333;
                                    max-width: 600px;
                                    margin: 0 auto;
                                }
                                .header {
                                    background: #4361ee;
                                    color: white;
                                    padding: 20px;
                                    text-align: center;
                                    border-radius: 10px 10px 0 0;
                                }
                                .content {
                                    padding: 20px;
                                    background: #f9f9f9;
                                    border-radius: 0 0 10px 10px;
                                    border: 1px solid #ddd;
                                }
                                .button {
                                    display: inline-block;
                                    padding: 12px 24px;
                                    background-color: #4361ee;
                                    color: white;
                                    text-decoration: none;
                                    border-radius: 5px;
                                    margin: 15px 0;
                                }
                                .footer {
                                    text-align: center;
                                    margin-top: 20px;
                                    font-size: 12px;
                                    color: #777;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="header">
                                <h2>Recuperación de Contraseña</h2>
                            </div>
                            <div class="content">
                                <p>Hola ' . htmlspecialchars($usuario['nombre']) . ',</p>
                                <p>Hemos recibido una solicitud para restablecer tu contraseña en el Sistema de Reclamos.</p>
                                <p>Para continuar con el proceso, haz clic en el siguiente enlace:</p>
                                <p style="text-align: center;">
                                    <a href="' . $enlace_recuperacion . '" class="button">Restablecer Contraseña</a>
                                </p>
                                <p>Si no puedes hacer clic en el botón, copia y pega la siguiente URL en tu navegador:</p>
                                <p style="word-break: break-all;">' . $enlace_recuperacion . '</p>
                                <p><strong>Nota:</strong> Este enlace expirará en 1 hora por motivos de seguridad.</p>
                                <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                            </div>
                            <div class="footer">
                                <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                                <p>&copy; ' . date('Y') . ' Sistema de Reclamos. Todos los derechos reservados.</p>
                            </div>
                        </body>
                        </html>
                    ';
                    
                    $mail->AltBody = "Hola " . $usuario['nombre'] . ",\n\nPara restablecer tu contraseña, visita el siguiente enlace: " . $enlace_recuperacion . "\n\nEste enlace explotara en 1 hora, asi que apresurate en leer rapido el mensaje.\n\nSi no solicitaste este cambio, ignora este mensaje.";
                    
                    $mail->send();
                    $exito = 'Se ha enviado un enlace de recuperación a tu correo electrónico.';
                } catch (Exception $e) {
                    $error = 'No se pudo enviar el correo. Error: ' . $mail->ErrorInfo;
                }
            } else {
                // Por seguridad, no revelar si el email existe o no
                $exito = 'Si el email existe en nuestro sistema, recibirás un enlace de recuperación.';
            }
        } catch(PDOException $e) {
            $error = 'Error en el sistema. Por favor, intenta más tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema de Reclamos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3a0ca3;
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

        .recovery-container {
            width: 100%;
            max-width: 450px;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .recovery-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .recovery-header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .recovery-header p {
            color: var(--gray);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .input-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .input-icon input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .recovery-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-header">
            <h1>Recuperar Contraseña</h1>
            <p>Ingresa tu correo electrónico para recibir instrucciones</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if($exito): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $exito ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" required placeholder="tu@email.com">
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Enviar Instrucciones
            </button>
        </form>

        <div class="back-link">
            <a href="../login.php"><i class="fas fa-arrow-left"></i> Volver al Login</a>
        </div>
    </div>
</body>
</html>