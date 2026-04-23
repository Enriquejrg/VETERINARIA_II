<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

$mensaje = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear'])) {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $cantidad = intval($_POST['cantidad']);
        
        if (empty($nombre) || $precio <= 0 || $cantidad < 0) {
            $mensaje_error = "❌ Por favor complete todos los campos correctamente";
        } else {
            $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, cantidad) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $precio, $cantidad]);
            $mensaje = "✅ Producto agregado exitosamente";
        }
    }
    elseif (isset($_POST['actualizar'])) {
        $id = intval($_POST['id']);
        $cantidad = intval($_POST['cantidad']);
        
        if ($cantidad < 0) {
            $mensaje_error = "❌ La cantidad no puede ser negativa";
        } else {
            $stmt = $conn->prepare("UPDATE productos SET cantidad = ? WHERE id = ?");
            $stmt->execute([$cantidad, $id]);
            $mensaje = "✅ Stock actualizado correctamente";
        }
    }
    elseif (isset($_POST['actualizar_precio'])) {
        $id = intval($_POST['id']);
        $precio = floatval($_POST['precio']);
        
        if ($precio <= 0) {
            $mensaje_error = "❌ El precio debe ser mayor a 0";
        } else {
            $stmt = $conn->prepare("UPDATE productos SET precio = ? WHERE id = ?");
            $stmt->execute([$precio, $id]);
            $mensaje = "✅ Precio actualizado correctamente";
        }
    }
    elseif (isset($_POST['eliminar'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM productos WHERE id=?");
        $stmt->execute([$id]);
        $mensaje = "✅ Producto eliminado exitosamente";
    }
}

$productos = $conn->query("SELECT * FROM productos ORDER BY id DESC")->fetchAll();
$stock_total = $conn->query("SELECT SUM(cantidad) as total FROM productos")->fetchColumn();
$valor_total = $conn->query("SELECT SUM(precio * cantidad) as total FROM productos")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Clínica Veterinaria Mis Patitas</title>
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

        .btn-success:hover {
            background: #2f855a;
        }

        .btn-warning {
            background: #ecc94b;
            color: #744210;
        }

        .btn-warning:hover {
            background: #d69e2e;
        }

        .btn-danger {
            background: #fc8181;
            color: white;
        }

        .btn-danger:hover {
            background: #f56565;
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

        .data-table input {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 80px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-stock-bajo {
            background: #fed7d7;
            color: #742a2a;
        }

        .badge-stock-normal {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-stock-alto {
            background: #bee3f8;
            color: #2c5282;
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

        /* Stock items */
        .stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stock-item:last-child {
            border-bottom: none;
        }

        .stock-info {
            flex: 1;
        }

        .stock-name {
            font-weight: 600;
            color: #2d3748;
        }

        .stock-cantidad {
            font-size: 12px;
            color: #718096;
        }

        .stock-actions {
            display: flex;
            gap: 8px;
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
                grid-template-columns: 1fr;
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
                <a href="productos.php" class="active">
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
                    <h1>📦 Inventario - Pet Shop</h1>
                    <p>Gestión completa de productos y stock</p>
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
                        <h3><?php echo count($productos); ?></h3>
                        <p>📦 Productos Registrados</p>
                    </div>
                    <div class="stat-icon">📦</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo number_format($stock_total); ?></h3>
                        <p>📊 Unidades en Stock</p>
                    </div>
                    <div class="stat-icon">📊</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>S/ <?php echo number_format($valor_total, 2); ?></h3>
                        <p>💰 Valor del Inventario</p>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php 
                            $productos_bajos = 0;
                            foreach($productos as $p) if($p['cantidad'] < 10) $productos_bajos++;
                            echo $productos_bajos;
                        ?></h3>
                        <p>⚠️ Productos con Stock Bajo</p>
                    </div>
                    <div class="stat-icon">⚠️</div>
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

            <!-- Agregar Producto -->
            <div class="form-card">
                <div class="card-header">
                    <h2>
                        <span>➕</span> Agregar Nuevo Producto
                    </h2>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>📝 Nombre del producto *</label>
                            <input type="text" name="nombre" placeholder="Ej: Alimento Premium para Perros" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>📄 Descripción</label>
                            <textarea name="descripcion" placeholder="Descripción detallada del producto..."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>💰 Precio (S/) *</label>
                            <input type="number" step="0.01" name="precio" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>📦 Cantidad inicial *</label>
                            <input type="number" name="cantidad" placeholder="0" required>
                        </div>
                    </div>
                    <button type="submit" name="crear" class="btn btn-primary">
                        ✅ Agregar Producto
                    </button>
                </form>
            </div>

            <!-- Lista de Productos -->
            <div class="table-card">
                <div class="card-header">
                    <h2>
                        <span>📋</span> Lista de Productos
                    </h2>
                    <div>
                        <input type="text" id="buscarProducto" class="search-box" placeholder="🔍 Buscar producto...">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Actualizar Stock</th>
                                <th>Actualizar Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($productos as $p): ?>
                            <tr class="producto-fila">
                                <td><?php echo $p['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars(substr($p['descripcion'], 0, 60)); ?></td>
                                <td>S/ <?php echo number_format($p['precio'], 2); ?></td>
                                <td>
                                    <span style="font-weight: bold; <?php echo $p['cantidad'] < 10 ? 'color: #e53e3e;' : ($p['cantidad'] < 30 ? 'color: #ecc94b;' : 'color: #38a169;'); ?>">
                                        <?php echo $p['cantidad']; ?> und.
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    if ($p['cantidad'] < 10) {
                                        echo '<span class="badge badge-stock-bajo">⚠️ Stock Bajo</span>';
                                    } elseif ($p['cantidad'] < 30) {
                                        echo '<span class="badge badge-stock-normal">📦 Stock Medio</span>';
                                    } else {
                                        echo '<span class="badge badge-stock-alto">✅ Stock Alto</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <input type="number" name="cantidad" value="<?php echo $p['cantidad']; ?>" style="width: 80px;">
                                        <button type="submit" name="actualizar" class="btn btn-warning btn-sm">🔄</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <input type="number" step="0.01" name="precio" value="<?php echo $p['precio']; ?>" style="width: 80px;">
                                        <button type="submit" name="actualizar_precio" class="btn btn-success btn-sm">💰</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar <?php echo htmlspecialchars($p['nombre']); ?>?')">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="eliminar" class="btn btn-danger btn-sm">🗑 Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alertas de Stock Bajo -->
            <div class="info-card">
                <div class="card-header">
                    <h2>
                        <span>⚠️</span> Alertas de Stock Bajo
                    </h2>
                </div>
                <?php 
                $productos_stock_bajo = array_filter($productos, function($p) { return $p['cantidad'] < 10; });
                if(count($productos_stock_bajo) > 0): 
                ?>
                    <?php foreach($productos_stock_bajo as $p): ?>
                        <div class="stock-item">
                            <div class="stock-info">
                                <div class="stock-name">📦 <?php echo htmlspecialchars($p['nombre']); ?></div>
                                <div class="stock-cantidad">Stock actual: <?php echo $p['cantidad']; ?> unidades</div>
                            </div>
                            <div class="stock-actions">
                                <span class="badge badge-stock-bajo">⚠️ Urgente</span>
                                <button class="btn btn-primary btn-sm" onclick="scrollToForm()">
                                    + Agregar más
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <span style="font-size: 48px;">✅</span>
                        <p>Todos los productos tienen stock suficiente</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Consejos Rápidos -->
            <div class="info-card">
                <div class="card-header">
                    <h2>
                        <span>💡</span> Consejos Rápidos
                    </h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <strong>📊 Stock Bajo:</strong>
                        <p style="font-size: 13px; color: #718096;">Productos con menos de 10 unidades necesitan reabastecimiento urgente.</p>
                    </div>
                    <div>
                        <strong>💰 Precios:</strong>
                        <p style="font-size: 13px; color: #718096;">Actualiza los precios regularmente para mantener la rentabilidad.</p>
                    </div>
                    <div>
                        <strong>📦 Rotación:</strong>
                        <p style="font-size: 13px; color: #718096;">Monitorea los productos más vendidos para optimizar el inventario.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Búsqueda en tiempo real
        document.getElementById('buscarProducto').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tablaProductos tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        });

        // Scroll al formulario
        function scrollToForm() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>