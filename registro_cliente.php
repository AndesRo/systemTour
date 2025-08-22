<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

// Si el usuario ya está logueado, redirigir
if (isset($_SESSION['cliente_id'])) {
    header('Location: perfil_cliente.php');
    exit();
}

$errores = [];
$valores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizar y validar datos
    $valores['nombre'] = sanitizar($_POST['nombre'] ??'');
    $valores['email'] = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $valores['telefono'] = sanitizar($_POST['telefono'] ?? '');

    // Validaciones
    if (empty($valores['nombre'])) {
        $errores['nombre'] = 'El nombre es obligatorio';
    }

    if (empty($valores['email']) || !filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Ingrese un email válido';
    }

    if (strlen($password) < 6) {
        $errores['password'] = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $errores['confirm_password'] = 'Las contraseñas no coinciden';
    }

    // Verificar si el email ya existe
    if (empty($errores)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$valores['email']]);
            if ($stmt->fetch()) {
                $errores['email'] = 'Este email ya está registrado';
            }
        } catch(PDOException $e) {
            $errores['general'] = 'Error en el sistema: ' . $e->getMessage();
        }
    }

    // Registrar usuario si no hay errores
    if (empty($errores)) {
        try {
            $hash_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO clientes (nombre, email, password, telefono) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $valores['nombre'],
                $valores['email'],
                $hash_password,
                $valores['telefono']
            ]);
            
            $_SESSION['registro_exitoso'] = true;
            header('Location: registro_cliente.php?exito=1');
            exit();
            
        } catch(PDOException $e) {
            $errores['general'] = 'Error al registrar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .registro-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .registro-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }

        .registro-form .form-group {
            margin-bottom: 20px;
        }

        .registro-form label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
        }

        .registro-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 6px;
            font-size: 16px;
        }

        .registro-form input:focus {
            border-color: #3498db;
            outline: none;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .btn-registro {
            width: 100%;
            padding: 15px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-registro:hover {
            background: #2c3e50;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #3498db;
            text-decoration: none;
        }

        .exito-mensaje {
            background: #dff0d8;
            color: #3c763d;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <h1 class="registro-title">Registro de Cliente</h1>

        <?php if(isset($_GET['exito'])): ?>
            <div class="exito-mensaje">
                ¡Registro exitoso! <a href="login.php">Iniciar sesión</a>
            </div>
        <?php endif; ?>

        <?php if(!empty($errores['general'])): ?>
            <div class="error-mensaje"><?= $errores['general'] ?></div>
        <?php endif; ?>

        <form class="registro-form" method="POST" novalidate>
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" 
                    value="<?= htmlspecialchars($valores['nombre'] ?? '') ?>"
                    required>
                <?php if(isset($errores['nombre'])): ?>
                    <div class="error-message"><?= $errores['nombre'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Correo electrónico *</label>
                <input type="email" name="email" 
                    value="<?= htmlspecialchars($valores['email'] ?? '') ?>"
                    required>
                <?php if(isset($errores['email'])): ?>
                    <div class="error-message"><?= $errores['email'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Contraseña * (mínimo 6 caracteres)</label>
                <input type="password" name="password" required>
                <?php if(isset($errores['password'])): ?>
                    <div class="error-message"><?= $errores['password'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Confirmar contraseña *</label>
                <input type="password" name="confirm_password" required>
                <?php if(isset($errores['confirm_password'])): ?>
                    <div class="error-message"><?= $errores['confirm_password'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono" 
                    value="<?= htmlspecialchars($valores['telefono'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-registro">Registrarse</button>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
    </div>

    <script>
        // Validación en tiempo real
        document.querySelector('form').addEventListener('submit', function(e) {
            let password = document.querySelector('input[name="password"]');
            let confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            if (password.value.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                e.preventDefault();
            }
            
            if (password.value !== confirmPassword.value) {
                alert('Las contraseñas no coinciden');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>