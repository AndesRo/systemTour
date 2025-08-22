<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

// Redirigir usuarios ya autenticados
if (isset($_SESSION['admin'])) {
    header('Location: admin/dashboard.php');
    exit();
} elseif (isset($_SESSION['cliente_id'])) {
    header('Location: perfil_cliente.php');
    exit();
}

$error = '';
$tipo_usuario = 'cliente'; // Valor por defecto

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $tipo_usuario = $_POST['tipo_usuario'];

    try {
        // Determinar tabla según tipo de usuario
        $tabla = ($tipo_usuario === 'admin') ? 'usuarios_admin' : 'clientes';
        $campo_usuario = ($tipo_usuario === 'admin') ? 'usuario' : 'email';
        
        $stmt = $conn->prepare("SELECT * FROM $tabla WHERE $campo_usuario = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Establecer sesión
            $_SESSION['tipo_usuario'] = $tipo_usuario;
            
            if ($tipo_usuario === 'admin') {
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $usuario['id'];
                $_SESSION['admin_usuario'] = $usuario['usuario'];
                header('Location: admin/dashboard.php');
            } else {
                $_SESSION['cliente_id'] = $usuario['id'];
                $_SESSION['cliente_nombre'] = $usuario['nombre'];
                $_SESSION['cliente_email'] = $usuario['email'];
                header('Location: perfil_cliente.php');
            }
            exit();
            
        } else {
            $error = 'Credenciales incorrectas';
        }
    } catch(PDOException $e) {
        error_log('Error de login: ' . $e->getMessage());
        $error = 'Error en el sistema';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Gestión de Reclamos</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 10px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 25px;
        }
        
        .tabs-container {
            display: flex;
            background: #f0f0f0;
            border-radius: 50px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            font-weight: 500;
            z-index: 1;
            transition: var(--transition);
        }
        
        .tab.active {
            color: white;
        }
        
        .tab-slider {
            position: absolute;
            height: 100%;
            width: 50%;
            background: var(--primary-color);
            border-radius: 50px;
            transition: var(--transition);
        }
        
        .tab-slider.admin {
            transform: translateX(100%);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 14px;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }
        
        .input-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e1e5eb;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
        }
        
        .input-icon input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5eb;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            display: inline-block;
            margin: 0 10px;
        }
        
        .login-footer a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background: #ffeaea;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Sistema de Reclamos</h1>
            <p>Accede a tu cuenta para gestionar tus reclamos</p>
        </div>
        
        <div class="login-body">
            <div class="tabs-container">
                <div class="tab-slider <?= $tipo_usuario === 'admin' ? 'admin' : '' ?>"></div>
                <div class="tab <?= $tipo_usuario === 'cliente' ? 'active' : '' ?>" onclick="selectType('cliente')">
                    Cliente
                </div>
                <div class="tab <?= $tipo_usuario === 'admin' ? 'active' : '' ?>" onclick="selectType('admin')">
                    Administrador
                </div>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST">
                <input type="hidden" name="tipo_usuario" id="tipoUsuario" value="<?= $tipo_usuario ?>">
                
                <div class="form-group">
                    <label for="email">
                        <?= $tipo_usuario === 'admin' ? 'Usuario' : 'Correo electrónico' ?>
                    </label>
                    <div class="input-icon">
                        <i class="<?= $tipo_usuario === 'admin' ? 'fas fa-user' : 'fas fa-envelope' ?>"></i>
                        <input type="text" 
                            id="email"
                            name="email" 
                            placeholder="<?= $tipo_usuario === 'admin' ? 'Ingresa tu usuario' : 'Ingresa tu correo electrónico' ?>" 
                            required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                            id="password"
                            name="password" 
                            placeholder="Ingresa tu contraseña" 
                            required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <?php if($tipo_usuario === 'cliente'): ?>
                <div class="login-footer">
                    <a href="registro_cliente.php">
                        <i class="fas fa-user-plus"></i> Crear cuenta
                    </a>
                    <a href="recuperar_password.php">
                        <i class="fas fa-key"></i> ¿Olvidaste tu contraseña?
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectType(type) {
            document.getElementById('tipoUsuario').value = type;
            
            // Mover el slider
            const slider = document.querySelector('.tab-slider');
            if (type === 'admin') {
                slider.classList.add('admin');
            } else {
                slider.classList.remove('admin');
            }
            
            // Actualizar placeholder y icono del campo usuario/email
            const emailField = document.querySelector('input[name="email"]');
            const emailIcon = document.querySelector('.input-icon i');
            
            if (type === 'admin') {
                emailField.placeholder = 'Ingresa tu usuario';
                emailIcon.className = 'fas fa-user';
                document.querySelector('label[for="email"]').textContent = 'Usuario';
            } else {
                emailField.placeholder = 'Ingresa tu correo electrónico';
                emailIcon.className = 'fas fa-envelope';
                document.querySelector('label[for="email"]').textContent = 'Correo electrónico';
            }
        }
    </script>
</body>
</html>