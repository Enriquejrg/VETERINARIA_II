<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

// Estadísticas generales (consultas simples)
$total_mascotas = $conn->query("SELECT COUNT(*) FROM mascotas")->fetchColumn();
$total_perros = $conn->query("SELECT COUNT(*) FROM mascotas WHERE especie LIKE '%Perro%' OR especie LIKE '%perro%'")->fetchColumn();
$total_gatos = $conn->query("SELECT COUNT(*) FROM mascotas WHERE especie LIKE '%Gato%' OR especie LIKE '%gato%'")->fetchColumn();
$total_duenos = $conn->query("SELECT COUNT(*) FROM duenos")->fetchColumn();

$total_citas = $conn->query("SELECT COUNT(*) FROM reservaciones")->fetchColumn();
$citas_atendidas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='atendido'")->fetchColumn();
$citas_pendientes = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='pendiente'")->fetchColumn();
$citas_confirmadas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='confirmada'")->fetchColumn();
$citas_canceladas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='cancelada'")->fetchColumn();

$total_productos = $conn->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$stock_total = $conn->query("SELECT SUM(cantidad) FROM productos")->fetchColumn();
$valor_inventario = $conn->query("SELECT SUM(precio * cantidad) FROM productos")->fetchColumn();
$productos_bajos = $conn->query("SELECT COUNT(*) FROM productos WHERE cantidad < 10")->fetchColumn();

// Citas por día (últimos 7 días) - PROCESADO EN PHP para evitar GROUP BY
$citas_raw = $conn->query("
    SELECT fecha_solicitada 
    FROM reservaciones 
    WHERE fecha_solicitada >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY fecha_solicitada
")->fetchAll();

$citas_por_dia = [];
$dias_nombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $dia_nombre = $dias_nombres[date('N', strtotime($fecha)) - 1];
    $citas_por_dia[] = ['fecha' => $fecha, 'dia' => $dia_nombre, 'total' => 0];
}

foreach ($citas_raw as $cita) {
    $fecha = $cita['fecha_solicitada'];
    foreach ($citas_por_dia as &$dia) {
        if ($dia['fecha'] == $fecha) {
            $dia['total']++;
            break;
        }
    }
}

// Citas por mes (últimos 6 meses) - PROCESADO EN PHP
$citas_meses_raw = $conn->query("
    SELECT fecha_solicitada 
    FROM reservaciones 
    WHERE fecha_solicitada >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ORDER BY fecha_solicitada
")->fetchAll();

$citas_por_mes = [];
$meses_nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $nombre_mes = $meses_nombres[date('n', strtotime("-$i months")) - 1];
    $citas_por_mes[$mes] = ['mes' => $mes, 'nombre' => $nombre_mes, 'total' => 0];
}

foreach ($citas_meses_raw as $cita) {
    $mes = date('Y-m', strtotime($cita['fecha_solicitada']));
    if (isset($citas_por_mes[$mes])) {
        $citas_por_mes[$mes]['total']++;
    }
}
$citas_por_mes = array_values($citas_por_mes);

// Productos con stock bajo
$productos_stock_bajo = $conn->query("SELECT * FROM productos WHERE cantidad < 20 ORDER BY cantidad ASC LIMIT 10")->fetchAll();

// Mascotas por especie - PROCESADO EN PHP
$mascotas_raw = $conn->query("SELECT especie FROM mascotas WHERE especie IS NOT NULL AND especie != ''")->fetchAll();
$mascotas_por_especie = [];
foreach ($mascotas_raw as $m) {
    $especie = trim($m['especie']);
    if (!isset($mascotas_por_especie[$especie])) {
        $mascotas_por_especie[$especie] = 0;
    }
    $mascotas_por_especie[$especie]++;
}
arsort($mascotas_por_especie);

// Top productos con menos stock (los más vendidos)
$top_productos = $conn->query("SELECT * FROM productos ORDER BY cantidad ASC LIMIT 5")->fetchAll();

// Porcentajes
$porcentaje_citas_atendidas = $total_citas > 0 ? round(($citas_atendidas / $total_citas) * 100) : 0;
$porcentaje_perros = $total_mascotas > 0 ? round(($total_perros / $total_mascotas) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Clínica Veterinaria Mis Patitas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }

        /* Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid #4a5568;
        }

        .sidebar-header h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #a0aec0;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.3s;
            gap: 12px;
        }

        .sidebar-nav a:hover {
            background: #4a5568;
            padding-left: 30px;
        }

        .sidebar-nav a.active {
            background: #667eea;
            border-left: 4px solid white;
        }

        .sidebar-nav .logout {
            margin-top: 30px;
            color: #fc8181;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 15px;
        }

        .welcome h1 {
            font-size: 1.5em;
            color: #2d3748;
        }

        .welcome p {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f7fafc;
            padding: 8px 20px;
            border-radius: 50px;
        }

        .user-info .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .user-info .badge-role {
            background: #e2e8f0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: #4a5568;
        }

        /* Report Cards */
        .report-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-header h2 {
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #718096;
            font-size: 13px;
        }

        /* Progress Bar */
        .progress-container {
            margin: 15px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 25px;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        .data-table tr:hover {
            background: #f7fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #fefcbf;
            color: #975a16;
        }

        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }

        .badge-info {
            background: #bee3f8;
            color: #2c5282;
        }

        /* Summary Grid */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .summary-item {
            background: #f7fafc;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .summary-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .summary-text {
            flex: 1;
        }

        .summary-text strong {
            font-size: 18px;
            color: #2d3748;
        }

        /* Chart Bars */
        .chart-bar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .chart-bar-label {
            width: 60px;
            font-weight: 600;
            color: #4a5568;
        }

        .chart-bar {
            flex: 1;
            background: #e2e8f0;
            border-radius: 10px;
            height: 30px;
            overflow: hidden;
        }

        .chart-bar-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .chart-bar-value {
            width: 40px;
            text-align: right;
            font-weight: bold;
            color: #2d3748;
        }

        /* Export Button */
        .btn-export {
            background: #38a169;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .btn-export:hover {
            background: #2f855a;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2, .sidebar-header p, .sidebar-nav a span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
                padding: 15px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .data-table th, .data-table td {
                padding: 8px;
                font-size: 12px;
            }
        }

        /* Print styles */
        @media print {
            .sidebar, .top-bar, .btn-export, .logout {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .report-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>🐾 Mis Patitas</h2>
                <p>Clínica Veterinaria</p>
            </div>
            <div class="sidebar-nav">
                <a href="dashboard.php">
                    <span>📊</span> <span>Dashboard</span>
                </a>
                <a href="mascotas.php">
                    <span>🐕</span> <span>Mascotas</span>
                </a>
                <a href="productos.php">
                    <span>📦</span> <span>Inventario</span>
                </a>
                <a href="reservaciones.php">
                    <span>📅</span> <span>Citas</span>
                </a>
                <a href="reportes.php" class="active">
                    <span>📈</span> <span>Reportes</span>
                </a>
                <a href="perfil.php">
                    <span>👤</span> <span>Mi Perfil</span>
                </a>
                <a href="logout.php" class="logout">
                    <span>🚪</span> <span>Cerrar Sesión</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="welcome">
                    <h1>📈 Reportes Estadísticos</h1>
                    <p>Análisis completo de la clínica veterinaria</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button class="btn-export" onclick="window.print()">
                        🖨️ Exportar / Imprimir
                    </button>
                    <div class="user-info">
                        <div class="avatar">
                            <?php echo strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></strong>
                            <div class="badge-role"><?php echo $_SESSION['rol'] ?? 'cliente'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reporte de Mascotas -->
            <div class="report-card">
                <div class="card-header">
                    <h2>
                        <span>🐕</span> Estadísticas de Mascotas
                    </h2>
                    <span class="badge badge-info">Actualizado: <?php echo date('d/m/Y H:i'); ?></span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_mascotas; ?></h3>
                        <p>🐾 Total Mascotas</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_perros; ?></h3>
                        <p>🐕 Perros</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_gatos; ?></h3>
                        <p>🐈 Gatos</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_duenos; ?></h3>
                        <p>👥 Dueños</p>
                    </div>
                </div>

                <?php if($total_mascotas > 0): ?>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>📊 Distribución Perro vs Gato</span>
                            <span><?php echo $porcentaje_perros; ?>% Perros</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $porcentaje_perros; ?>%">
                                <?php echo $porcentaje_perros; ?>%
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(!empty($mascotas_por_especie)): ?>
                    <h3 style="margin: 20px 0 10px 0;">📋 Distribución por Especie</h3>
                    <?php foreach($mascotas_por_especie as $especie => $total): ?>
                        <div class="chart-bar-item">
                            <span class="chart-bar-label"><?php echo htmlspecialchars($especie); ?></span>
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: <?php echo min(100, ($total / $total_mascotas) * 100); ?>%">
                                    <?php echo $total; ?>
                                </div>
                            </div>
                            <span class="chart-bar-value"><?php echo $total; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #718096;">No hay mascotas registradas</p>
                <?php endif; ?>
            </div>

            <!-- Reporte de Citas -->
            <div class="report-card">
                <div class="card-header">
                    <h2>
                        <span>📅</span> Estadísticas de Citas
                    </h2>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_citas; ?></h3>
                        <p>📋 Total Citas</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $citas_atendidas; ?></h3>
                        <p>✅ Atendidas</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $citas_confirmadas; ?></h3>
                        <p>📌 Confirmadas</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $citas_pendientes; ?></h3>
                        <p>⏰ Pendientes</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $citas_canceladas; ?></h3>
                        <p>❌ Canceladas</p>
                    </div>
                </div>

                <?php if($total_citas > 0): ?>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>📊 Eficiencia de Atención</span>
                            <span><?php echo $porcentaje_citas_atendidas; ?>% Atendidas</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $porcentaje_citas_atendidas; ?>%">
                                <?php echo $porcentaje_citas_atendidas; ?>%
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <h3 style="margin: 25px 0 15px 0;">📊 Citas por Día (Últimos 7 días)</h3>
                <?php 
                $tiene_citas = false;
                foreach($citas_por_dia as $dia): 
                    if($dia['total'] > 0) $tiene_citas = true;
                ?>
                    <div class="chart-bar-item">
                        <span class="chart-bar-label"><?php echo $dia['dia']; ?></span>
                        <div class="chart-bar">
                            <div class="chart-bar-fill" style="width: <?php echo min(100, ($dia['total'] / 10) * 100); ?>%">
                                <?php echo $dia['total'] > 0 ? $dia['total'] . ' cita(s)' : ''; ?>
                            </div>
                        </div>
                        <span class="chart-bar-value"><?php echo $dia['total']; ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if(!$tiene_citas): ?>
                    <p style="text-align: center; color: #718096;">No hay citas registradas en los últimos 7 días</p>
                <?php endif; ?>

                <?php 
                $tiene_meses = false;
                foreach($citas_por_mes as $mes): 
                    if($mes['total'] > 0) $tiene_meses = true;
                endforeach;
                if($tiene_meses): 
                ?>
                    <h3 style="margin: 25px 0 15px 0;">📈 Tendencia Mensual</h3>
                    <?php foreach($citas_por_mes as $mes): ?>
                        <div class="chart-bar-item">
                            <span class="chart-bar-label"><?php echo $mes['nombre']; ?></span>
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: <?php echo min(100, ($mes['total'] / 20) * 100); ?>%">
                                    <?php echo $mes['total'] > 0 ? $mes['total'] . ' citas' : ''; ?>
                                </div>
                            </div>
                            <span class="chart-bar-value"><?php echo $mes['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Reporte de Inventario -->
            <div class="report-card">
                <div class="card-header">
                    <h2>
                        <span>📦</span> Estadísticas de Inventario
                    </h2>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_productos; ?></h3>
                        <p>📦 Productos</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format($stock_total); ?></h3>
                        <p>📊 Unidades en Stock</p>
                    </div>
                    <div class="stat-card">
                        <h3>S/ <?php echo number_format($valor_inventario, 2); ?></h3>
                        <p>💰 Valor del Inventario</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $productos_bajos; ?></h3>
                        <p>⚠️ Stock Bajo</p>
                    </div>
                </div>

                <?php if(count($productos_stock_bajo) > 0): ?>
                    <div class="card-header" style="margin-top: 10px;">
                        <h3 style="color: #e53e3e;">⚠️ Productos con Stock Bajo (Recomendados para reabastecer)</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr><th>Producto</th><th>Stock Actual</th><th>Precio</th><th>Valor en Stock</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($productos_stock_bajo as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                <td><span style="color: #e53e3e; font-weight: bold;"><?php echo $p['cantidad']; ?> und.</span></td>
                                <td>S/ <?php echo number_format($p['precio'], 2); ?></td>
                                <td>S/ <?php echo number_format($p['precio'] * $p['cantidad'], 2); ?></td>
                                <td><span class="badge badge-danger">⚠️ Urgente</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <span style="font-size: 48px;">✅</span>
                        <p>Todos los productos tienen stock suficiente</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Resumen Ejecutivo -->
            <div class="report-card">
                <div class="card-header">
                    <h2>
                        <span>📊</span> Resumen Ejecutivo
                    </h2>
                </div>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon">🏥</div>
                        <div class="summary-text">
                            <strong><?php echo $total_mascotas; ?></strong>
                            <p>Mascotas atendidas</p>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon">👥</div>
                        <div class="summary-text">
                            <strong><?php echo $total_duenos; ?></strong>
                            <p>Dueños registrados</p>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon">📅</div>
                        <div class="summary-text">
                            <strong><?php echo $total_citas; ?></strong>
                            <p>Citas realizadas</p>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon">💰</div>
                        <div class="summary-text">
                            <strong>S/ <?php echo number_format($valor_inventario, 2); ?></strong>
                            <p>Valor del inventario</p>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #f7fafc; border-radius: 12px;">
                    <h3 style="margin-bottom: 10px;">📌 Conclusiones</h3>
                    <ul style="margin-left: 20px; color: #4a5568;">
                        <li>🏥 La clínica atiende actualmente a <strong><?php echo $total_mascotas; ?></strong> mascotas</li>
                        <li>👥 Tiene <strong><?php echo $total_duenos; ?></strong> dueños registrados</li>
                        <li>📅 Se han registrado <strong><?php echo $total_citas; ?></strong> citas en total</li>
                        <li>📦 El inventario tiene un valor de <strong>S/ <?php echo number_format($valor_inventario, 2); ?></strong></li>
                        <?php if($citas_pendientes > 0): ?>
                            <li>⏰ Hay <strong><?php echo $citas_pendientes; ?></strong> citas pendientes por atender</li>
                        <?php endif; ?>
                        <?php if($productos_bajos > 0): ?>
                            <li>⚠️ Se recomienda reabastecer <strong><?php echo $productos_bajos; ?></strong> productos</li>
                        <?php endif; ?>
                        <?php if($citas_atendidas > 0): ?>
                            <li>✅ El porcentaje de atención es del <strong><?php echo $porcentaje_citas_atendidas; ?>%</strong></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>