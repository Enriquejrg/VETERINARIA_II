<?php
// dashboard.php - Panel principal (VERSIÓN SIMPLIFICADA - SIN ERRORES DE GROUP BY)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

// Verificar que la conexión existe
if (!isset($conn) || !$conn) {
    die("Error de conexión a la base de datos");
}

// Obtener estadísticas - SOLO CONSULTAS SIMPLES
try {
    // Estadísticas de mascotas
    $total_mascotas = $conn->query("SELECT COUNT(*) FROM mascotas")->fetchColumn();
    $total_perros = $conn->query("SELECT COUNT(*) FROM mascotas WHERE especie LIKE '%Perro%' OR especie LIKE '%perro%'")->fetchColumn();
    $total_gatos = $conn->query("SELECT COUNT(*) FROM mascotas WHERE especie LIKE '%Gato%' OR especie LIKE '%gato%'")->fetchColumn();
    
    // Estadísticas de usuarios y dueños
    $total_usuarios = $conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $total_duenos = $conn->query("SELECT COUNT(*) FROM duenos")->fetchColumn();
    
    // Estadísticas de citas
    $total_citas = $conn->query("SELECT COUNT(*) FROM reservaciones")->fetchColumn();
    $citas_pendientes = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='pendiente'")->fetchColumn();
    $citas_confirmadas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='confirmada'")->fetchColumn();
    $citas_atendidas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='atendido'")->fetchColumn();
    $citas_canceladas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='cancelada'")->fetchColumn();
    $citas_hoy = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE fecha_solicitada=CURDATE()")->fetchColumn();
    $citas_semana = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE fecha_solicitada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Estadísticas de inventario
    $total_productos = $conn->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $stock_total = $conn->query("SELECT SUM(cantidad) as total FROM productos")->fetchColumn();
    $valor_inventario = $conn->query("SELECT SUM(precio * cantidad) as total FROM productos")->fetchColumn();
    $productos_bajo_stock = $conn->query("SELECT COUNT(*) FROM productos WHERE cantidad < 10")->fetchColumn();
    
    // DATOS PARA GRÁFICOS - CONSULTAS SIMPLES SIN GROUP BY PROBLEMÁTICO
    
    // Obtener citas de los últimos 7 días (sin GROUP BY, procesar en PHP)
    $citas_raw = $conn->query("
        SELECT fecha_solicitada 
        FROM reservaciones 
        WHERE fecha_solicitada >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY fecha_solicitada
    ")->fetchAll();
    
    // Procesar las citas en PHP para crear el gráfico
    $citas_por_dia = [];
    $dias_semana = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom'];
    
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $dia_nombre = $dias_semana[date('D', strtotime($fecha))] ?? date('D', strtotime($fecha));
        $citas_por_dia[$fecha] = ['dia' => $dia_nombre, 'total' => 0];
    }
    
    foreach ($citas_raw as $cita) {
        $fecha = $cita['fecha_solicitada'];
        if (isset($citas_por_dia[$fecha])) {
            $citas_por_dia[$fecha]['total']++;
        }
    }
    
    // Mascotas por especie (sin GROUP BY problemático)
    $todas_mascotas = $conn->query("SELECT especie FROM mascotas WHERE especie IS NOT NULL AND especie != ''")->fetchAll();
    $mascotas_por_especie = [];
    foreach ($todas_mascotas as $m) {
        $especie = $m['especie'];
        if (!isset($mascotas_por_especie[$especie])) {
            $mascotas_por_especie[$especie] = 0;
        }
        $mascotas_por_especie[$especie]++;
    }
    // Ordenar de mayor a menor
    arsort($mascotas_por_especie);
    $mascotas_por_especie = array_slice($mascotas_por_especie, 0, 5);
    
    // Últimas actividades
    $ultimas_citas = $conn->query("
        SELECT * FROM reservaciones 
        ORDER BY fecha_registro DESC 
        LIMIT 5
    ")->fetchAll();
    
    $ultimas_mascotas = $conn->query("
        SELECT m.*, d.nombre_completo as dueno 
        FROM mascotas m 
        LEFT JOIN duenos d ON m.dueno_id = d.id 
        ORDER BY m.fecha_registro DESC 
        LIMIT 5
    ")->fetchAll();
    
    $productos_bajos = $conn->query("
        SELECT * FROM productos 
        WHERE cantidad < 10 
        ORDER BY cantidad ASC 
        LIMIT 5
    ")->fetchAll();
    
} catch(PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Calcular porcentajes
$porcentaje_citas_atendidas = $total_citas > 0 ? round(($citas_atendidas / $total_citas) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clínica Veterinaria Mis Patitas</title>
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
            padding: 25px 30px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-info h3 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #718096;
            font-size: 13px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            margin-bottom: 20px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 30px;
            overflow: hidden;
            margin: 10px 0;
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
            font-size: 13px;
            font-weight: bold;
        }

        .species-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Tables */
        .table-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-card h3 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

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

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-pendiente { background: #fefcbf; color: #975a16; }
        .badge-confirmada { background: #c6f6d5; color: #22543d; }
        .badge-atendido { background: #bee3f8; color: #2c5282; }
        .badge-cancelada { background: #fed7d7; color: #742a2a; }

        .btn-link {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .alert-warning {
            background: #fefcbf;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
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
                grid-template-columns: 1fr;
            }
            .charts-row {
                grid-template-columns: 1fr;
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
                <a href="dashboard.php" class="active">
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
                <a href="reportes.php">
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
                    <h1>¡Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>! 👋</h1>
                    <p>Hoy es <?php echo date('l, d \d\e F \d\e Y'); ?></p>
                </div>
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

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='mascotas.php'">
                    <div class="stat-info">
                        <h3><?php echo $total_mascotas; ?></h3>
                        <p>🐕 Mascotas Registradas</p>
                    </div>
                    <div class="stat-icon">🐾</div>
                </div>
                <div class="stat-card" onclick="window.location.href='reservaciones.php'">
                    <div class="stat-info">
                        <h3><?php echo $citas_hoy; ?></h3>
                        <p>📅 Citas para Hoy</p>
                    </div>
                    <div class="stat-icon">📅</div>
                </div>
                <div class="stat-card" onclick="window.location.href='reservaciones.php'">
                    <div class="stat-info">
                        <h3><?php echo $citas_pendientes; ?></h3>
                        <p>⏰ Citas Pendientes</p>
                    </div>
                    <div class="stat-icon">⏰</div>
                </div>
                <div class="stat-card" onclick="window.location.href='perfil.php'">
                    <div class="stat-info">
                        <h3><?php echo $total_usuarios; ?></h3>
                        <p>👥 Usuarios Activos</p>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <!-- Citas por Día -->
                <div class="chart-card">
                    <h3>📊 Citas de la Semana</h3>
                    <?php if(!empty($citas_por_dia)): ?>
                        <?php foreach($citas_por_dia as $dia): ?>
                            <div>
                                <span><?php echo $dia['dia']; ?></span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, ($dia['total'] / 10) * 100); ?>%">
                                        <?php echo $dia['total']; ?> cita(s)
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay citas en la última semana</p>
                    <?php endif; ?>
                </div>

                <!-- Estado de Citas -->
                <div class="chart-card">
                    <h3>📈 Estado de Citas</h3>
                    <div class="species-item">
                        <span>✅ Atendidas</span>
                        <span><strong><?php echo $citas_atendidas; ?></strong> (<?php echo $porcentaje_citas_atendidas; ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $porcentaje_citas_atendidas; ?>%"></div>
                    </div>
                    <div class="species-item">
                        <span>⏰ Pendientes</span>
                        <span><strong><?php echo $citas_pendientes; ?></strong></span>
                    </div>
                    <div class="species-item">
                        <span>✅ Confirmadas</span>
                        <span><strong><?php echo $citas_confirmadas; ?></strong></span>
                    </div>
                    <div class="species-item">
                        <span>❌ Canceladas</span>
                        <span><strong><?php echo $citas_canceladas; ?></strong></span>
                    </div>
                </div>
            </div>

            <div class="charts-row">
                <!-- Mascotas por Especie -->
                <div class="chart-card">
                    <h3>🐕 Mascotas por Especie</h3>
                    <?php if(!empty($mascotas_por_especie)): ?>
                        <?php foreach($mascotas_por_especie as $especie => $total): ?>
                            <div class="species-item">
                                <span><?php echo htmlspecialchars($especie); ?></span>
                                <span><strong><?php echo $total; ?></strong> mascota(s)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay mascotas registradas</p>
                    <?php endif; ?>
                </div>

                <!-- Inventario -->
                <div class="chart-card">
                    <h3>📦 Resumen de Inventario</h3>
                    <div class="species-item">
                        <span>📦 Productos</span>
                        <span><strong><?php echo $total_productos; ?></strong></span>
                    </div>
                    <div class="species-item">
                        <span>📊 Unidades en Stock</span>
                        <span><strong><?php echo number_format($stock_total); ?></strong></span>
                    </div>
                    <div class="species-item">
                        <span>💰 Valor del Inventario</span>
                        <span><strong>S/ <?php echo number_format($valor_inventario, 2); ?></strong></span>
                    </div>
                    <div class="species-item">
                        <span>⚠️ Productos con Stock Bajo</span>
                        <span><strong><?php echo $productos_bajo_stock; ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- Últimas Citas -->
            <div class="table-card">
                <h3>📋 Últimas Citas Registradas</h3>
                <?php if(!empty($ultimas_citas)): ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Cliente</th><th>Mascota</th><th>Fecha</th><th>Hora</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($ultimas_citas as $cita): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cita['nombre_cliente'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cita['nombre_mascota'] ?? ''); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($cita['fecha_solicitada'] ?? 'now')); ?></td>
                                <td><?php echo $cita['hora_solicitada'] ?? ''; ?></td>
                                <td><span class="badge badge-<?php echo $cita['estado'] ?? 'pendiente'; ?>"><?php echo $cita['estado'] ?? 'pendiente'; ?></span></td>
                                <td><a href="reservaciones.php" class="btn-link">Ver →</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay citas registradas.</p>
                <?php endif; ?>
            </div>

            <!-- Alertas -->
            <div class="chart-card">
                <h3>⚠️ Alertas y Notificaciones</h3>
                <?php if($productos_bajo_stock > 0): ?>
                    <div class="alert-warning">
                        ⚠️ Hay <strong><?php echo $productos_bajo_stock; ?></strong> producto(s) con stock bajo.
                    </div>
                <?php endif; ?>
                <?php if($citas_pendientes > 0): ?>
                    <div class="alert-warning">
                        ⏰ Tienes <strong><?php echo $citas_pendientes; ?></strong> cita(s) pendiente(s) por atender.
                    </div>
                <?php endif; ?>
                <?php if($citas_hoy > 0): ?>
                    <div class="alert-warning">
                        📅 Hoy tienes <strong><?php echo $citas_hoy; ?></strong> cita(s) programada(s).
                    </div>
                <?php endif; ?>
                <?php if($productos_bajo_stock == 0 && $citas_pendientes == 0): ?>
                    <p>✅ Todo está en orden. ¡Buen trabajo!</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>