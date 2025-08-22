<?php
// Función para generar ID de ticket único
function generarTicketID() {
    return 'TKT-' . strtoupper(uniqid());
}

// Función para sanitizar datos de entrada
function sanitizar($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para verificar login admin
function checkLogin() {
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// Función para mostrar alertas
function mostrarAlerta($tipo, $mensaje) {
    echo "<div class='alerta $tipo'>$mensaje</div>";
}

// Función para obtener el estado con formato
function formatearEstado($estado) {
    $estilos = [
        'Pendiente' => 'estado-pendiente',
        'En proceso' => 'estado-proceso',
        'Resuelto' => 'estado-resuelto'
    ];
    return "<span class='{$estilos[$estado]}'>$estado</span>";
}

// Función para actualizar estado de reclamo
function actualizarEstado($conn, $id_reclamo, $nuevo_estado) {
    try {
        $stmt = $conn->prepare("UPDATE reclamos SET estado = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $id_reclamo]);
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Función para crear usuario admin (ejecutar solo una vez)
function crearAdminUser($conn, $usuario, $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios_admin (usuario, password) VALUES (?, ?)");
    return $stmt->execute([$usuario, $hash]);
}
?>