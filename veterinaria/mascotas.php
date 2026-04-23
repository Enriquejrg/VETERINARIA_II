<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

$mensaje = '';
$mensaje_error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear'])) {
        $nombre = trim($_POST['nombre']);
        $especie = trim($_POST['especie']);
        $raza = trim($_POST['raza']);
        $edad = intval($_POST['edad']);
        $sexo = $_POST['sexo'];
        $peso = floatval($_POST['peso']);
        $dueno_id = intval($_POST['dueno_id']);
        
        if (empty($nombre) || empty($especie) || $dueno_id <= 0) {
            $mensaje_error = "❌ Por favor complete todos los campos obligatorios";
        } else {
            $stmt = $conn->prepare("INSERT INTO mascotas (nombre, especie, raza, edad, sexo, peso, dueno_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $especie, $raza, $edad, $sexo, $peso, $dueno_id]);
            $mensaje = "✅ Mascota registrada exitosamente";
        }
    }
    elseif (isset($_POST['actualizar'])) {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $especie = trim($_POST['especie']);
        $raza = trim($_POST['raza']);
        $edad = intval($_POST['edad']);
        $sexo = $_POST['sexo'];
        $peso = floatval($_POST['peso']);
        $dueno_id = intval($_POST['dueno_id']);
        
        $stmt = $conn->prepare("UPDATE mascotas SET nombre=?, especie=?, raza=?, edad=?, sexo=?, peso=?, dueno_id=? WHERE id=?");
        $stmt->execute([$nombre, $especie, $raza, $edad, $sexo, $peso, $dueno_id, $id]);
        $mensaje = "✅ Mascota actualizada exitosamente";
    }
    elseif (isset($_POST['eliminar'])) {
        $id = intval($_POST['id']);
        $conn->prepare("DELETE FROM mascotas WHERE id=?")->execute([$id]);
        $mensaje = "✅ Mascota eliminada exitosamente";
    }
}

$mascotas = $conn->query("SELECT m.*, d.nombre_completo as dueno FROM mascotas m LEFT JOIN duenos d ON m.dueno_id = d.id ORDER BY m.id DESC")->fetchAll();
$duenos = $conn->query("SELECT * FROM duenos ORDER BY nombre_completo")->fetchAll();

// Estadísticas
$total_mascotas = count($mascotas);
$total_perros = 0;
$total_gatos = 0;
foreach ($mascotas as $m) {
    if (stripos($m['especie'], 'perro') !== false) $total_perros++;
    if (stripos($m['especie'], 'gato') !== false) $total_gatos++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mascotas - Clínica Veterinaria Mis Patitas</title>
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

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .btn-edit {
            background: #4299e1;
            color: white;
        }

        .btn-edit:hover {
            background: #3182ce;
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

        .badge-macho {
            background: #bee3f8;
            color: #2c5282;
        }

        .badge-hembra {
            background: #fed7d7;
            color: #742a2a;
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
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
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

        /* Avatar mascota */
        .pet-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
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
                <a href="mascotas.php" class="active">
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
                    <h1>🐕 Gestión de Mascotas</h1>
                    <p>Registra y administra todas las mascotas de la clínica</p>
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
                        <h3><?php echo $total_mascotas; ?></h3>
                        <p>🐾 Total Mascotas</p>
                    </div>
                    <div class="stat-icon">🐾</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $total_perros; ?></h3>
                        <p>🐕 Perros</p>
                    </div>
                    <div class="stat-icon">🐕</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $total_gatos; ?></h3>
                        <p>🐈 Gatos</p>
                    </div>
                    <div class="stat-icon">🐈</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo count($duenos); ?></h3>
                        <p>👥 Dueños</p>
                    </div>
                    <div class="stat-icon">👥</div>
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

            <!-- Registrar Nueva Mascota -->
            <div class="form-card">
                <div class="card-header">
                    <h2>
                        <span>📝</span> Registrar Nueva Mascota
                    </h2>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>🐕 Nombre de la mascota *</label>
                            <input type="text" name="nombre" placeholder="Ej: Luna, Max, Simba" required>
                        </div>
                        <div class="form-group">
                            <label>🐾 Especie *</label>
                            <input type="text" name="especie" placeholder="Ej: Perro, Gato, Conejo" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>📋 Raza</label>
                            <input type="text" name="raza" placeholder="Ej: Golden Retriever, Persa">
                        </div>
                        <div class="form-group">
                            <label>🎂 Edad (años)</label>
                            <input type="number" name="edad" placeholder="Ej: 3" min="0" step="0.5">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>⚥ Sexo</label>
                            <select name="sexo">
                                <option value="Macho">♂ Macho</option>
                                <option value="Hembra">♀ Hembra</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>⚖️ Peso (kg)</label>
                            <input type="number" step="0.01" name="peso" placeholder="Ej: 5.5">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>👤 Dueño *</label>
                        <select name="dueno_id" required>
                            <option value="">Seleccionar dueño</option>
                            <?php foreach($duenos as $dueno): ?>
                            <option value="<?php echo $dueno['id']; ?>">
                                <?php echo htmlspecialchars($dueno['nombre_completo']); ?> - 📞 <?php echo $dueno['telefono']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="crear" class="btn btn-primary">
                        ✅ Registrar Mascota
                    </button>
                </form>
            </div>

            <!-- Lista de Mascotas -->
            <div class="table-card">
                <div class="card-header">
                    <h2>
                        <span>📋</span> Lista de Mascotas
                    </h2>
                    <div>
                        <input type="text" id="buscarMascota" class="search-box" placeholder="🔍 Buscar por nombre, especie o dueño...">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table" id="tablaMascotas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mascota</th>
                                <th>Especie</th>
                                <th>Raza</th>
                                <th>Edad</th>
                                <th>Sexo</th>
                                <th>Peso</th>
                                <th>Dueño</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($mascotas as $m): ?>
                            <tr class="mascota-fila">
                                <td><?php echo $m['id']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="pet-avatar">
                                            <?php echo stripos($m['especie'], 'perro') !== false ? '🐕' : (stripos($m['especie'], 'gato') !== false ? '🐈' : '🐾'); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($m['nombre']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($m['especie']); ?></td>
                                <td><?php echo htmlspecialchars($m['raza']); ?></td>
                                <td><?php echo $m['edad'] ? $m['edad'] . ' años' : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $m['sexo'] == 'Macho' ? 'badge-macho' : 'badge-hembra'; ?>">
                                        <?php echo $m['sexo'] == 'Macho' ? '♂ Macho' : '♀ Hembra'; ?>
                                    </span>
                                </td>
                                <td><?php echo $m['peso'] ? $m['peso'] . ' kg' : '-'; ?></td>
                                <td><?php echo htmlspecialchars($m['dueno']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button class="btn btn-warning btn-sm" onclick="abrirModalEditar(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['nombre']); ?>', '<?php echo htmlspecialchars($m['especie']); ?>', '<?php echo htmlspecialchars($m['raza']); ?>', '<?php echo $m['edad']; ?>', '<?php echo $m['sexo']; ?>', '<?php echo $m['peso']; ?>', <?php echo $m['dueno_id']; ?>)">
                                            ✏️ Editar
                                        </button>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar a <?php echo htmlspecialchars($m['nombre']); ?>?')">
                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
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

            <!-- Modal Editar -->
            <div id="modalEditar" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>✏️ Editar Mascota</h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label>🐕 Nombre</label>
                            <input type="text" name="nombre" id="edit_nombre" required>
                        </div>
                        <div class="form-group">
                            <label>🐾 Especie</label>
                            <input type="text" name="especie" id="edit_especie" required>
                        </div>
                        <div class="form-group">
                            <label>📋 Raza</label>
                            <input type="text" name="raza" id="edit_raza">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>🎂 Edad (años)</label>
                                <input type="number" name="edad" id="edit_edad" step="0.5">
                            </div>
                            <div class="form-group">
                                <label>⚖️ Peso (kg)</label>
                                <input type="number" step="0.01" name="peso" id="edit_peso">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>⚥ Sexo</label>
                                <select name="sexo" id="edit_sexo">
                                    <option value="Macho">♂ Macho</option>
                                    <option value="Hembra">♀ Hembra</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>👤 Dueño</label>
                                <select name="dueno_id" id="edit_dueno_id" required>
                                    <option value="">Seleccionar dueño</option>
                                    <?php foreach($duenos as $dueno): ?>
                                    <option value="<?php echo $dueno['id']; ?>">
                                        <?php echo htmlspecialchars($dueno['nombre_completo']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" onclick="cerrarModal()">Cancelar</button>
                            <button type="submit" name="actualizar" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información -->
            <div class="info-card">
                <div class="card-header">
                    <h2>
                        <span>💡</span> Consejos Rápidos
                    </h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <strong>📝 Registro Completo:</strong>
                        <p style="font-size: 13px; color: #718096;">Incluye toda la información de la mascota para un mejor seguimiento.</p>
                    </div>
                    <div>
                        <strong>🔄 Actualización:</strong>
                        <p style="font-size: 13px; color: #718096;">Mantén actualizados los datos como peso y edad en cada visita.</p>
                    </div>
                    <div>
                        <strong>📅 Historial:</strong>
                        <p style="font-size: 13px; color: #718096;">Cada mascota tiene su propio historial de citas y consultas.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Búsqueda en tiempo real
        document.getElementById('buscarMascota').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tablaMascotas tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        });

        // Modal Editar
        function abrirModalEditar(id, nombre, especie, raza, edad, sexo, peso, dueno_id) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_especie').value = especie;
            document.getElementById('edit_raza').value = raza || '';
            document.getElementById('edit_edad').value = edad || '';
            document.getElementById('edit_sexo').value = sexo;
            document.getElementById('edit_peso').value = peso || '';
            document.getElementById('edit_dueno_id').value = dueno_id;
            document.getElementById('modalEditar').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            let modal = document.getElementById('modalEditar');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>