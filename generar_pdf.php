<?php
session_start();
require 'includes/config.php';
include 'includes/funciones.php';
require('vendor/autoload.php');


// Verificar sesión
if (!isset($_SESSION['cliente_id'])) {
    die('Acceso no autorizado');
}

// Obtener ticket desde URL
$ticket = $_GET['ticket'] ?? '';
if(empty($ticket)) {
    die('Ticket no especificado');
}

// Obtener datos del reclamo
$stmt = $conn->prepare("SELECT r.*, c.nombre, c.email 
    FROM reclamos r 
    JOIN clientes c ON r.cliente_id = c.id 
    WHERE r.ticket_id = ? AND r.cliente_id = ?");
$stmt->execute([$ticket, $_SESSION['cliente_id']]);
$reclamo = $stmt->fetch();

if(!$reclamo) {
    die('Reclamo no encontrado');
}

// Clase personalizada de FPDF para header/footer
class PDF extends FPDF {
    function Header() {
        // Logo (ajusta la ruta del logo de la empresa)
        $this->Image('assets/logo.png', 10, 6, 30);
        // Fuente
        $this->SetFont('Arial','B',14);
        $this->Cell(80);
        $this->Cell(100,10,'Reporte de Reclamo Turístico',0,1,'R');
        $this->Ln(5);
        // Línea
        $this->SetDrawColor(50,50,150);
        $this->SetLineWidth(1);
        $this->Line(10,30,200,30);
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(100,100,100);
        $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
    }

    // Método helper para mostrar info en formato tabla
    function InfoRow($label, $value) {
        $this->SetFont('Arial','B',11);
        $this->SetTextColor(33,37,41);
        $this->Cell(50,10,utf8_decode($label),1,0,'L',true);
        $this->SetFont('Arial','',11);
        $this->SetTextColor(60,60,60);
        $this->Cell(0,10,utf8_decode($value),1,1,'L');
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Colores para celdas
$pdf->SetFillColor(240,240,240);

// Sección datos principales
$pdf->SetFont('Arial','B',13);
$pdf->SetTextColor(44,62,80);
$pdf->Cell(0,10,'Información del Reclamo',0,1,'L');
$pdf->Ln(3);

$pdf->InfoRow('Ticket ID:', $reclamo['ticket_id']);
$pdf->InfoRow('Fecha Reporte:', date('d/m/Y H:i', strtotime($reclamo['fecha_creacion'])));
$pdf->InfoRow('Cliente:', $reclamo['nombre']);
$pdf->InfoRow('Email:', $reclamo['email']);

// Sección detalles
$pdf->Ln(8);
$pdf->SetFont('Arial','B',13);
$pdf->SetTextColor(44,62,80);
$pdf->Cell(0,10,'Detalles del Incidente',0,1,'L');
$pdf->Ln(3);

$pdf->InfoRow('Fecha Incidente:', date('d/m/Y', strtotime($reclamo['fecha_incidente'])));
$pdf->InfoRow('Lugar:', $reclamo['lugar']);
$pdf->InfoRow('Estado:', $reclamo['estado']);

// Descripción
$pdf->Ln(8);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Descripción del Reclamo',0,1);
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,8,utf8_decode($reclamo['descripcion']),0,'L');

// Footer con fecha
$pdf->Ln(15);
$pdf->SetFont('Arial','I',9);
$pdf->SetTextColor(120,120,120);
$pdf->Cell(0,10,'Generado automáticamente el '.date('d/m/Y H:i').' - Sistema de Reclamos',0,1,'C');

// Salida
$pdf->Output('D','reporte_'.$reclamo['ticket_id'].'.pdf');
exit();
?>
