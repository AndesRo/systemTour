<?php
// create-admin.php
require 'includes/config.php'; // conexión PDO
require 'includes/funciones.php'; // funciones auxiliares si las tienes

// Solo ejecutar si no hay admins registrados
$stmt = $conn->query("SELECT COUNT(*) FROM usuarios_admin");
if ($stmt->fetchColumn() > 0) {
    exit("⚠️ Ya existe al menos un administrador. Elimina este archivo.");
}

// Datos del nuevo admin
$usuario = 'admin';
$password = 'control123'; // Cámbiala antes de ejecutar

// Hashear contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insertar en la base de datos
$stmt = $conn->prepare("INSERT INTO usuarios_admin (usuario, password) VALUES (?, ?)");
if ($stmt->execute([$usuario, $hash])) {
    echo "✅ Usuario administrador creado correctamente.<br>";
    echo "Usuario: <strong>$usuario</strong><br>";
    echo "Contraseña: <strong>$password</strong><br>";
    echo "🔒 Recuerda eliminar este archivo inmediatamente.";
} else {
    echo "❌ Error al crear el usuario administrador.";
}
?>
