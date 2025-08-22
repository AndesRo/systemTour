<?php
include 'includes/config.php';

if (isset($_GET['ticket_id'])) {
    $stmt = $conn->prepare("SELECT * FROM reclamos WHERE ticket_id = ?");
    $stmt->execute([$_GET['ticket_id']]);
    $reclamo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!-- Mostrar resultados de la búsqueda -->