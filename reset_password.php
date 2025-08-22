<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/funciones.php';

$error = '';
$exito = '';
$token_valido = false;
$email = '';

// Verificar si se proporcionó un token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Verificar si el token es válido y no ha expirado
        $stmt = $conn->prepare("SELECT email, expiracion FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset_data) {
            // Verificar si el token ha expirado
            if (strtotime($reset_data['expiracion']) > time()) {
                $token_valido = true;
                $email = $reset_data['email'];
            } else {
                $error = 'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.';
                
                // Eliminar token expirado
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
            }
        } else {
            $error = 'El enlace de recuperación no es válido.';
        }
    } catch(PDOException $e) {
        $error = 'Error en el sistema. Por favor, intenta más tarde.';
    }
} else {
    $error = 'No se proporcionó un enlace de recuperación válido.';
}

// Procesar el formulario de restablecimiento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valido) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    if (empty($password) || empty($confirm_password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            // Hashear la nueva contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Actualizar la contraseña en la base de datos
            $stmt = $conn->prepare("UPDATE clientes SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);
            
            // Eliminar el token usado
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            $exito = 'Contraseña restablecida correctamente. Ahora puedes iniciar sesión con tu nueva contraseña.';
            $token_valido = false; // Para ocultar el formulario después del éxito
        } catch(PDOException $e) {
            $error = 'Error al restablecer la contraseña. Por favor, intenta más tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema de Reclamos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
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

        .reset-container {
            width: 100%;
            max-width: 450px;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .reset-header p {
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

        .password-strength {
            margin-top: 8px;
            height: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .strength-weak { background: #ff4d4d; width: 33%; }
        .strength-medium { background: #ffa64d; width: 66%; }
        .strength-strong { background: #00cc66; width: 100%; }

        .password-requirements {
            margin-top: 8px;
            font-size: 12px;
            color: var(--gray);
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

        .btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
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
            .reset-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Restablecer Contraseña</h1>
            <p>
                <?php 
                if ($token_valido) {
                    echo "Ingresa tu nueva contraseña para " . htmlspecialchars($email);
                } else {
                    echo "Restablecimiento de contraseña";
                }
                ?>
            </p>
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
            <div class="back-link">
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Ir al Inicio de Sesión</a>
            </div>
        <?php endif; ?>

        <?php if ($token_valido && !$exito): ?>
            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" required placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter" id="passwordStrength"></div>
                    </div>
                    <div class="password-requirements">
                        La contraseña debe tener al menos 6 caracteres.
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Repite tu contraseña">
                    </div>
                    <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-key"></i> Restablecer Contraseña
                </button>
            </form>
        <?php elseif (!$exito): ?>
            <div class="back-link">
                <a href="recuperar_password.php"><i class="fas fa-arrow-left"></i> Volver a Recuperación</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Validación de fortaleza de contraseña en tiempo real
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthMeter = document.getElementById('passwordStrength');
        const matchText = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Calcular fortaleza
            let strength = 0;
            if (password.length > 5) strength += 1;
            if (password.length > 7) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Actualizar medidor visual
            strengthMeter.className = 'strength-meter';
            if (password.length === 0) {
                strengthMeter.style.width = '0';
            } else if (strength <= 2) {
                strengthMeter.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthMeter.classList.add('strength-medium');
            } else {
                strengthMeter.classList.add('strength-strong');
            }
            
            validateForm();
        });
        
        confirmInput.addEventListener('input', function() {
            validateForm();
        });
        
        function validateForm() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            // Verificar coincidencia de contraseñas
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchText.innerHTML = '<span style="color: green;">Las contraseñas coinciden</span>';
                    matchText.style.color = 'green';
                } else {
                    matchText.innerHTML = '<span style="color: red;">Las contraseñas no coinciden</span>';
                    matchText.style.color = 'red';
                }
            } else {
                matchText.innerHTML = '';
            }
            
            // Habilitar/deshabilitar botón
            const isPasswordValid = password.length >= 6;
            const isConfirmValid = password === confirm && confirm.length > 0;
            
            submitBtn.disabled = !(isPasswordValid && isConfirmValid);
        }
        
        // Validar formulario antes de enviar
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres.');
                return false;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
                return false;
            }
        });
    </script>
</body>
</html>