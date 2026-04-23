<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

$mensaje = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear'])) {
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $telefono = trim($_POST['telefono']);
        $email = trim($_POST['email']);
        $nombre_mascota = trim($_POST['nombre_mascota']);
        $especie = trim($_POST['especie']);
        $motivo = trim($_POST['motivo']);
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $tipo_cita = $_POST['tipo_cita'];
        
        // Validar que la fecha no sea pasada
        if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
            $mensaje_error = "❌ No se pueden agendar citas en fechas pasadas";
        } else {
            $stmt = $conn->prepare("INSERT INTO reservaciones (nombre_cliente, telefono, email, nombre_mascota, especie, motivo_consulta, fecha_solicitada, hora_solicitada, tipo_cita, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$nombre_cliente, $telefono, $email, $nombre_mascota, $especie, $motivo, $fecha, $hora, $tipo_cita]);
            $mensaje = "✅ Cita agendada exitosamente";
        }
    }
    elseif (isset($_POST['actualizar_estado'])) {
        $id = intval($_POST['id']);
        $estado = $_POST['estado'];
        $stmt = $conn->prepare("UPDATE reservaciones SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        $mensaje = "✅ Estado actualizado correctamente";
    }
    elseif (isset($_POST['eliminar'])) {
        $id = intval($_POST['id']);
        $conn->prepare("DELETE FROM reservaciones WHERE id=?")->execute([$id]);
        $mensaje = "✅ Cita eliminada exitosamente";
    }
    elseif (isset($_POST['reprogramar'])) {
        $id = intval($_POST['id']);
        $nueva_fecha = $_POST['nueva_fecha'];
        $nueva_hora = $_POST['nueva_hora'];
        
        if (strtotime($nueva_fecha) < strtotime(date('Y-m-d'))) {
            $mensaje_error = "❌ No se puede reprogramar a una fecha pasada";
        } else {
            $stmt = $conn->prepare("UPDATE reservaciones SET fecha_solicitada = ?, hora_solicitada = ?, estado = 'pendiente' WHERE id = ?");
            $stmt->execute([$nueva_fecha, $nueva_hora, $id]);
            $mensaje = "✅ Cita reprogramada exitosamente";
        }
    }
}

$citas = $conn->query("SELECT * FROM reservaciones ORDER BY fecha_solicitada DESC, hora_solicitada ASC")->fetchAll();
$citas_hoy = $conn->query("SELECT * FROM reservaciones WHERE fecha_solicitada = CURDATE() ORDER BY hora_solicitada")->fetchAll();
$citas_semana = $conn->query("SELECT * FROM reservaciones WHERE fecha_solicitada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY fecha_solicitada, hora_solicitada")->fetchAll();
$total_citas = $conn->query("SELECT COUNT(*) FROM reservaciones")->fetchColumn();
$citas_pendientes = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='pendiente'")->fetchColumn();
$citas_confirmadas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='confirmada'")->fetchColumn();
$citas_atendidas = $conn->query("SELECT COUNT(*) FROM reservaciones WHERE estado='atendido'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Clínica Veterinaria Mis Patitas</title>
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        /* Cards */
        .form-card, .table-card, .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        }

        /* Formularios */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #38a169;
            color: white;
        }

        .btn-warning {
            background: #ecc94b;
            color: #744210;
        }

        .btn-danger {
            background: #fc8181;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Tabla */
        .table-container {
            overflow-x: auto;
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
            position: sticky;
            top: 0;
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

        .badge-pendiente { background: #fefcbf; color: #975a16; }
        .badge-confirmada { background: #c6f6d5; color: #22543d; }
        .badge-atendido { background: #bee3f8; color: #2c5282; }
        .badge-cancelada { background: #fed7d7; color: #742a2a; }

        /* Cita item */
        .cita-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cita-item:last-child {
            border-bottom: none;
        }

        .cita-info {
            flex: 1;
        }

        .cita-hora {
            font-weight: bold;
            color: #667eea;
        }

        .cita-cliente {
            font-weight: 600;
            color: #2d3748;
        }

        .cita-mascota {
            font-size: 13px;
            color: #718096;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 25px;
            width: 90%;
            max-width: 400px;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-body input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        /* Search */
        .search-box {
            padding: 8px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            width: 250px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            .data-table th, .data-table td {
                padding: 8px;
                font-size: 12px;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .search-box {
                width: 100%;
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
                <a href="reservaciones.php" class="active">
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
                    <h1>📅 Gestión de Citas Veterinarias</h1>
                    <p>Administra todas las citas de tus pacientes</p>
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
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $total_citas; ?></h3>
                        <p>📋 Total Citas</p>
                    </div>
                    <div class="stat-icon">📋</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $citas_pendientes; ?></h3>
                        <p>⏰ Pendientes</p>
                    </div>
                    <div class="stat-icon">⏰</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $citas_confirmadas; ?></h3>
                        <p>✅ Confirmadas</p>
                    </div>
                    <div class="stat-icon">✅</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $citas_atendidas; ?></h3>
                        <p>🏥 Atendidas</p>
                    </div>
                    <div class="stat-icon">🏥</div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($mensaje_error): ?>
                <div class="alert alert-error">
                    <span>❌</span> <?php echo $mensaje_error; ?>
                </div>
            <?php endif; ?>

            <!-- Citas de Hoy -->
            <?php if(count($citas_hoy) > 0): ?>
            <div class="info-card">
                <div class="card-header">
                    <h2>
                        <span>📅</span> Citas para Hoy - <?php echo date('d/m/Y'); ?>
                    </h2>
                </div>
                <?php foreach($citas_hoy as $c): ?>
                    <div class="cita-item">
                        <div class="cita-info">
                            <span class="cita-hora">🕐 <?php echo $c['hora_solicitada']; ?></span>
                            <div class="cita-cliente"><?php echo htmlspecialchars($c['nombre_cliente']); ?></div>
                            <div class="cita-mascota">🐕 <?php echo htmlspecialchars($c['nombre_mascota']); ?> - <?php echo htmlspecialchars($c['especie']); ?></div>
                        </div>
                        <div>
                            <span class="badge badge-<?php echo $c['estado']; ?>"><?php echo $c['estado']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Nueva Cita -->
            <div class="form-card">
                <div class="card-header">
                    <h2>
                        <span>📝</span> Nueva Cita
                    </h2>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>👤 Nombre del cliente *</label>
                            <input type="text" name="nombre_cliente" placeholder="Ej: Juan Perez" required>
                        </div>
                        <div class="form-group">
                            <label>📞 Teléfono *</label>
                            <input type="tel" name="telefono" placeholder="Ej: 987654321" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>📧 Email</label>
                            <input type="email" name="email" placeholder="cliente@example.com">
                        </div>
                        <div class="form-group">
                            <label>🐕 Nombre de la mascota *</label>
                            <input type="text" name="nombre_mascota" placeholder="Ej: Luna" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>🐾 Especie *</label>
                            <input type="text" name="especie" placeholder="Ej: Perro, Gato, etc." required>
                        </div>
                        <div class="form-group">
                            <label>🏥 Tipo de cita *</label>
                            <select name="tipo_cita">
                                <option value="presencial">🏥 Presencial</option>
                                <option value="domicilio">🏠 Domicilio</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>📝 Motivo de consulta</label>
                        <textarea name="motivo" placeholder="Describa el motivo de la consulta..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>📅 Fecha *</label>
                            <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>⏰ Hora *</label>
                            <input type="time" name="hora" required>
                        </div>
                    </div>
                    <button type="submit" name="crear" class="btn btn-primary">
                        ✅ Agendar Cita
                    </button>
                </form>
            </div>

            <!-- Lista de Citas -->
            <div class="table-card">
                <div class="card-header">
                    <h2>
                        <span>📋</span> Lista de Citas
                    </h2>
                    <div>
                        <input type="text" id="buscarCita" class="search-box" placeholder="🔍 Buscar por cliente o mascota...">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table" id="tablaCitas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente / Contacto</th>
                                <th>Mascota</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($citas as $c): ?>
                            <tr class="cita-fila">
                                <td><?php echo $c['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($c['nombre_cliente']); ?></strong><br>
                                    <small>📞 <?php echo $c['telefono']; ?></small>
                                    <?php if($c['email']): ?>
                                        <br><small>📧 <?php echo htmlspecialchars($c['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($c['nombre_mascota']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($c['especie']); ?></small>
                                 </td>
                                <td><?php echo date('d/m/Y', strtotime($c['fecha_solicitada'])); ?></td>
                                <td><?php echo $c['hora_solicitada']; ?></td>
                                <td><?php echo $c['tipo_cita'] == 'presencial' ? '🏥 Presencial' : '🏠 Domicilio'; ?></td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <select name="estado" onchange="this.form.submit()" class="btn-sm" style="padding: 4px 8px;">
                                            <option value="pendiente" <?php echo $c['estado']=='pendiente'?'selected':''; ?>>⏰ Pendiente</option>
                                            <option value="confirmada" <?php echo $c['estado']=='confirmada'?'selected':''; ?>>✅ Confirmada</option>
                                            <option value="atendido" <?php echo $c['estado']=='atendido'?'selected':''; ?>>🏥 Atendido</option>
                                            <option value="cancelada" <?php echo $c['estado']=='cancelada'?'selected':''; ?>>❌ Cancelada</option>
                                        </select>
                                        <input type="hidden" name="actualizar_estado" value="1">
                                    </form>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <button class="btn btn-warning btn-sm" onclick="abrirModalReprogramar(<?php echo $c['id']; ?>, '<?php echo $c['fecha_solicitada']; ?>', '<?php echo $c['hora_solicitada']; ?>')">
                                            📅 Reprogramar
                                        </button>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta cita?')">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" name="eliminar" class="btn btn-danger btn-sm">🗑</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Próximas Citas -->
            <?php if(count($citas_semana) > 0): ?>
            <div class="info-card">
                <div class="card-header">
                    <h2>
                        <span>📅</span> Próximas Citas (Próximos 7 días)
                    </h2>
                </div>
                <?php foreach($citas_semana as $c): ?>
                    <div class="cita-item">
                        <div class="cita-info">
                            <span class="cita-hora">📅 <?php echo date('d/m/Y', strtotime($c['fecha_solicitada'])); ?> - <?php echo $c['hora_solicitada']; ?></span>
                            <div class="cita-cliente"><?php echo htmlspecialchars($c['nombre_cliente']); ?> - <?php echo htmlspecialchars($c['nombre_mascota']); ?></div>
                        </div>
                        <div>
                            <span class="badge badge-<?php echo $c['estado']; ?>"><?php echo $c['estado']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Reprogramar -->
    <div id="modalReprogramar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📅 Reprogramar Cita</h3>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="id" id="reprogramar_id">
                <label>Nueva Fecha:</label>
                <input type="date" name="nueva_fecha" id="nueva_fecha" min="<?php echo date('Y-m-d'); ?>" required>
                <label>Nueva Hora:</label>
                <input type="time" name="nueva_hora" id="nueva_hora" required>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" name="reprogramar" class="btn btn-primary">Reprogramar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Búsqueda en tiempo real
        document.getElementById('buscarCita').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tablaCitas tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        });

        // Modal Reprogramar
        function abrirModalReprogramar(id, fecha, hora) {
            document.getElementById('reprogramar_id').value = id;
            document.getElementById('nueva_fecha').value = fecha;
            document.getElementById('nueva_hora').value = hora;
            document.getElementById('modalReprogramar').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modalReprogramar').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            let modal = document.getElementById('modalReprogramar');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>