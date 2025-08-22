<?php
session_start();
require_once('../includes/config.php');
include('../includes/funciones.php');
require('../vendor/autoload.php');

// Verificar autenticación
if (!isset($_SESSION['admin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Parámetros de filtrado
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Generar reportes
if (isset($_GET['generar'])) {
    $tipo_reporte = $_GET['generar'];
    generarReporte($conn, $tipo_reporte, $filtro_estado, $filtro_fecha, $filtro_busqueda);
    exit();
}

// Obtener datos estadísticos
try {
    list($where, $params) = construirWhere($filtro_estado, $filtro_fecha, $filtro_busqueda);
    
    // Datos para gráficos
    $estadisticas = [
        'por_estado' => $conn->query("SELECT estado, COUNT(*) as total FROM reclamos $where GROUP BY estado")->fetchAll(),
        'por_mes' => $conn->query("SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total FROM reclamos $where GROUP BY mes ORDER BY mes DESC LIMIT 6")->fetchAll(),
        'por_lugar' => $conn->query("SELECT lugar, COUNT(*) as total FROM reclamos $where GROUP BY lugar ORDER BY total DESC LIMIT 5")->fetchAll()
    ];

    // Obtener estadísticas generales
    $total_reclamos = $conn->query("SELECT COUNT(*) as total FROM reclamos")->fetch()['total'];
    $reclamos_pendientes = $conn->query("SELECT COUNT(*) as total FROM reclamos WHERE estado = 'Pendiente'")->fetch()['total'];
    $reclamos_proceso = $conn->query("SELECT COUNT(*) as total FROM reclamos WHERE estado = 'En proceso'")->fetch()['total'];
    $reclamos_resueltos = $conn->query("SELECT COUNT(*) as total FROM reclamos WHERE estado = 'Resuelto'")->fetch()['total'];

    // Obtener reclamos
    $stmt = $conn->prepare("SELECT * FROM reclamos $where ORDER BY fecha_creacion DESC LIMIT 100");
    $stmt->execute($params);
    $reclamos = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
}

function construirWhere(&$estado, &$fecha, &$busqueda) {
    $conditions = [];
    $params = [];
    
    if ($estado !== 'todos') {
        $conditions[] = "estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($fecha)) {
        $conditions[] = "DATE(fecha_creacion) = ?";
        $params[] = $fecha;
    }
    
    if (!empty($busqueda)) {
        $conditions[] = "(lugar LIKE ? OR descripcion LIKE ? OR ticket_id LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    return [$where, $params];
}

function generarReporte($conn, $tipo, $estado, $fecha, $busqueda) {
    list($where, $params) = construirWhere($estado, $fecha, $busqueda);
    
    $stmt = $conn->prepare("SELECT * FROM reclamos $where ORDER BY fecha_creacion DESC");
    $stmt->execute($params);
    $reclamos = $stmt->fetchAll();

    switch($tipo) {
        case 'pdf':
            generarPDF($reclamos);
            break;
        case 'excel':
            generarExcel($reclamos);
            break;
    }
}

function generarPDF($reclamos) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    
    // Cabecera
    $pdf->Cell(0,10,'Reporte de Reclamos - Administracion',0,1,'C');
    $pdf->Ln(10);
    
    // Columnas
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(30,10,'Ticket',1,0,'C');
    $pdf->Cell(25,10,'Fecha',1,0,'C');
    $pdf->Cell(40,10,'Lugar',1,0,'C');
    $pdf->Cell(25,10,'Estado',1,0,'C');
    $pdf->Cell(70,10,'Descripcion',1,1,'C');
    
    // Contenido
    $pdf->SetFont('Arial','',8);
    foreach($reclamos as $r) {
        $pdf->Cell(30,10,$r['ticket_id'],1);
        $pdf->Cell(25,10,date('d/m/Y', strtotime($r['fecha_creacion'])),1);
        $pdf->Cell(40,10,substr($r['lugar'],0,20),1);
        $pdf->Cell(25,10,$r['estado'],1);
        $pdf->MultiCell(70,10,substr($r['descripcion'],0,100),1);
    }
    
    $pdf->Output('D','reporte_reclamos.pdf');
}

function generarExcel($reclamos) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_reclamos.xlsx"');

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Encabezados
    $sheet->setCellValue('A1', 'Ticket ID');
    $sheet->setCellValue('B1', 'Fecha');
    $sheet->setCellValue('C1', 'Lugar');
    $sheet->setCellValue('D1', 'Estado');
    $sheet->setCellValue('E1', 'Descripción');
    
    // Datos
    $row = 2;
    foreach($reclamos as $r) {
        $sheet->setCellValue('A'.$row, $r['ticket_id']);
        $sheet->setCellValue('B'.$row, date('d/m/Y H:i', strtotime($r['fecha_creacion'])));
        $sheet->setCellValue('C'.$row, $r['lugar']);
        $sheet->setCellValue('D'.$row, $r['estado']);
        $sheet->setCellValue('E'.$row, $r['descripcion']);
        $row++;
    }
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Reclamos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --info: #4895ef;
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
        }

        .admin-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .admin-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .admin-title p {
            font-size: 14px;
            color: var(--gray);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .bg-primary { background: rgba(67, 97, 238, 0.15); color: var(--primary); }
        .bg-warning { background: rgba(247, 37, 133, 0.15); color: var(--warning); }
        .bg-info { background: rgba(72, 149, 239, 0.15); color: var(--info); }
        .bg-success { background: rgba(76, 201, 240, 0.15); color: var(--success); }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
        }

        /* Filters Section */
        .filters-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }

        .filter-input {
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }

        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }

        /* Buttons */
        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--light-gray);
            color: var(--dark);
        }

        .btn-outline:hover {
            background: var(--light);
        }

        /* Reports Section */
        .reports-section {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        /* Charts Section */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .chart-header {
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* Table Section */
        .table-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .data-table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending { background: #ffe0e0; color: #d32f2f; }
        .status-process { background: #fff3e0; color: #f57c00; }
        .status-resolved { background: #e8f5e9; color: #2e7d32; }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: var(--transition);
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        .btn-edit { background: var(--primary); }
        .btn-view { background: var(--info); }
        .btn-delete { background: var(--warning); }

        /* Responsive */
        @media (max-width: 1024px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .reports-section {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="admin-title">
                <h1>Panel de Administración</h1>
                <p>Gestión integral del sistema de reclamos</p>
            </div>
            <div class="header-actions">
                <span>Bienvenido, <?= htmlspecialchars($_SESSION['admin_usuario']) ?></span>
                <a href="logout.php" class="btn btn-outline" onclick="return confirm('¿Está seguro que desea cerrar la sesión?')">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </header>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $total_reclamos ?></div>
                        <div class="stat-label">Total Reclamos</div>
                    </div>
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $reclamos_pendientes ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $reclamos_proceso ?></div>
                        <div class="stat-label">En Proceso</div>
                    </div>
                    <div class="stat-icon bg-info">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $reclamos_resueltos ?></div>
                        <div class="stat-label">Resueltos</div>
                    </div>
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <section class="filters-section">
            <h2 class="section-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h2>
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado" class="filter-input">
                        <option value="todos">Todos los estados</option>
                        <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="En proceso" <?= $filtro_estado === 'En proceso' ? 'selected' : '' ?>>En proceso</option>
                        <option value="Resuelto" <?= $filtro_estado === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="fecha">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="filter-input" value="<?= $filtro_fecha ?>">
                </div>
                
                <div class="filter-group">
                    <label for="busqueda">Búsqueda</label>
                    <input type="text" name="busqueda" id="busqueda" class="filter-input" placeholder="Ticket, lugar o descripción..." value="<?= $filtro_busqueda ?>">
                </div>
                
                <div class="filter-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="?" class="btn btn-outline">
                        <i class="fas fa-undo"></i> Limpiar
                    </a>
                </div>
            </form>
        </section>

        <!-- Reportes -->
        <section class="reports-section">
            <button class="btn btn-primary" onclick="location.href='?generar=pdf&<?= http_build_query($_GET) ?>'">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
            <button class="btn btn-primary" onclick="location.href='?generar=excel&<?= http_build_query($_GET) ?>'">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
        </section>

        <!-- Gráficos -->
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="section-title"><i class="fas fa-chart-pie"></i> Distribución por Estado</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartEstados"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="section-title"><i class="fas fa-chart-line"></i> Tendencia Mensual</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartMensual"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="section-title"><i class="fas fa-chart-bar"></i> Lugares con Más Reclamos</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartLugares"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de Reclamos -->
        <section class="table-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-table"></i> Lista de Reclamos</h2>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Fecha</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reclamos as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['ticket_id']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['fecha_creacion'])) ?></td>
                            <td><?= htmlspecialchars($r['lugar']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $r['estado'])) ?>">
                                    <?= formatearEstado($r['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="editar_reclamo.php?id=<?= $r['id'] ?>" class="btn-icon btn-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../detalle_reclamo.php?id=<?= $r['id'] ?>" class="btn-icon btn-view" title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        // Configuración de gráficos
        new Chart(document.getElementById('chartEstados'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($estadisticas['por_estado'], 'estado')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($estadisticas['por_estado'], 'total')) ?>,
                    backgroundColor: ['#4361ee', '#4895ef', '#4cc9f0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        new Chart(document.getElementById('chartMensual'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(fn($m) => date('M Y', strtotime($m['mes'].'-01')), $estadisticas['por_mes'])) ?>,
                datasets: [{
                    label: 'Reclamos Mensuales',
                    data: <?= json_encode(array_column($estadisticas['por_mes'], 'total')) ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        new Chart(document.getElementById('chartLugares'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($estadisticas['por_lugar'], 'lugar')) ?>,
                datasets: [{
                    label: 'Reclamos por Lugar',
                    data: <?= json_encode(array_column($estadisticas['por_lugar'], 'total')) ?>,
                    backgroundColor: '#4895ef'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                indexAxis: 'y'
            }
        });
    </script>
</body>
</html>