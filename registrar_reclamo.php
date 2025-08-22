<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = 'TKT-' . strtoupper(uniqid());
    
    $datos = [
        'nombre' => isset($_SESSION['cliente_id']) ? null : sanitizar($_POST['nombre']),
        'email' => isset($_SESSION['cliente_id']) ? null : sanitizar($_POST['email']),
        'telefono' => isset($_SESSION['cliente_id']) ? null : sanitizar($_POST['telefono']),
        'cliente_id' => $_SESSION['cliente_id'] ?? null,
        'fecha_incidente' => sanitizar($_POST['fecha_incidente']),
        'lugar' => sanitizar($_POST['lugar']),
        'descripcion' => sanitizar($_POST['descripcion'])
    ];

    try {
        $stmt = $conn->prepare("INSERT INTO reclamos 
            (nombre, email, telefono, cliente_id, fecha_incidente, lugar, descripcion, ticket_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $datos['nombre'],
            $datos['email'],
            $datos['telefono'],
            $datos['cliente_id'],
            $datos['fecha_incidente'],
            $datos['lugar'],
            $datos['descripcion'],
            $ticket_id
        ]);
        
        $mensaje_exito = "<div class='resultado'>
            <h2>¡Reclamo registrado exitosamente!</h2>
            <p>Tu número de ticket es: <strong>$ticket_id</strong></p>
            <p>Guarda este número para hacer seguimiento</p>
            " . (isset($_SESSION['cliente_id']) ? 
            "<a href='perfil_cliente.php' class='btn'>Ver mis reclamos</a>" : 
            "<a href='index.php' class='btn'>Volver al inicio</a>") . "
        </div>";

    } catch(PDOException $e) {
        $error = "Error al registrar el reclamo: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Reclamo</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .form-reclamo {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .form-reclamo h2 {
            color: #1e3c72;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .campo-formulario {
            margin-bottom: 20px;
        }
        
        .campo-formulario label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .campo-formulario input,
        .campo-formulario textarea,
        .campo-formulario select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .campo-formulario textarea {
            height: 150px;
            resize: vertical;
        }
        
        .btn-enviar {
            background: #1e3c72;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-enviar:hover {
            background: #2a5298;
        }
    </style>
</head>
<body>
    <div class="form-reclamo">
        <?php if(isset($mensaje_exito)): ?>
            <?= $mensaje_exito ?>
        <?php else: ?>
            <h2>Formulario de Reclamo Turístico</h2>
            
            <?php if(isset($error)): ?>
                <div class="alerta error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validarFecha()">
                <?php if(!isset($_SESSION['cliente_id'])): ?>
                    <div class="campo-formulario">
                        <label>Nombre completo *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    
                    <div class="campo-formulario">
                        <label>Correo electrónico *</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="campo-formulario">
                        <label>Teléfono de contacto</label>
                        <input type="tel" name="telefono">
                    </div>
                <?php endif; ?>

                <div class="campo-formulario">
                    <label>Fecha del incidente *</label>
                    <input type="date" name="fecha_incidente" id="fecha_incidente" required>
                </div>
                
                <div class="campo-formulario">
                    <label>Lugar del incidente *</label>
                    <input type="text" name="lugar" placeholder="Ej: Hotel XYZ, Playa ABC..." required>
                </div>
                
                <div class="campo-formulario">
                    <label>Descripción detallada *</label>
                    <textarea name="descripcion" placeholder="Describe qué sucedió, incluye detalles importantes..." required></textarea>
                </div>
                
                <div class="campo-formulario" style="text-align: center;">
                    <button type="submit" class="btn-enviar">
                        <svg style="width:20px;height:20px;margin-right:10px;" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M20 8L12 13L4 8V6L12 11L20 6M20 4H4C2.89 4 2 4.89 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.89 21.1 4 20 4Z"/>
                        </svg>
                        Enviar Reclamo
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function validarFecha() {
            const fechaInput = document.getElementById('fecha_incidente');
            const fechaActual = new Date().toISOString().split('T')[0];
            
            if(fechaInput.value > fechaActual) {
                alert('La fecha del incidente no puede ser futura');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>