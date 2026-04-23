<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

$mensaje = '';
$mensaje_error = '';
$mensaje_password = '';
$mensaje_password_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Actualizar perfil
    if (isset($_POST['actualizar_perfil'])) {
        $nombre = trim($_POST['nombre']);
        $telefono = trim($_POST['telefono']);
        
        if (empty($nombre)) {
            $mensaje_error = "El nombre no puede estar vacío";
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, telefono = ? WHERE id = ?");
            $stmt->execute([$nombre, $telefono, $_SESSION['user_id']]);
            $_SESSION['nombre'] = $nombre;
            $mensaje = "✅ Perfil actualizado exitosamente";
        }
    }
    
    // Cambiar contraseña
    if (isset($_POST['cambiar_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Verificar contraseña actual
        $stmt = $conn->prepare("SELECT contraseña FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $mensaje_password_error = "❌ Por favor complete todos los campos";
        } elseif (!password_verify($current_password, $usuario_actual['contraseña'])) {
            $mensaje_password_error = "❌ Contraseña actual incorrecta";
        } elseif (strlen($new_password) < 6) {
            $mensaje_password_error = "❌ La nueva contraseña debe tener al menos 6 caracteres";
        } elseif ($new_password !== $confirm_password) {
            $mensaje_password_error = "❌ Las contraseñas no coinciden";
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET contraseña = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
            $mensaje_password = "✅ Contraseña actualizada exitosamente";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Clínica Veterinaria Mis Patitas</title>
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

        /* Profile Container */
        .profile-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        /* Cards */
        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .profile-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 20px 25px;
            color: white;
        }

        .card-header h2 {
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .card-body {
            padding: 25px;
        }

        /* Formularios */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
        }

        .form-group label i {
            margin-right: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input[disabled] {
            background: #f7fafc;
            color: #718096;
            cursor: not-allowed;
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
            width: 100%;
            justify-content: center;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #4a5568;
        }

        .btn-outline:hover {
            border-color: #667eea;
            color: #667eea;
        }

        /* Info Card */
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .info-card h3 {
            margin-bottom: 20px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
        }

        .info-value {
            color: #2d3748;
        }

        .info-value .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background: #fed7d7;
            color: #742a2a;
        }

        .badge-veterinario {
            background: #fefcbf;
            color: #975a16;
        }

        .badge-cliente {
            background: #c6f6d5;
            color: #22543d;
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

        /* Stats mini */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-mini {
            text-align: center;
            padding: 15px;
            background: #f7fafc;
            border-radius: 12px;
        }

        .stat-mini .number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-mini .label {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
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
            .profile-container {
                grid-template-columns: 1fr;
            }
            .top-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
                <a href="reportes.php">
                    <span>📈</span> <span>Reportes</span>
                </a>
                <a href="perfil.php" class="active">
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
                    <h1>👤 Mi Perfil</h1>
                    <p>Gestiona tu información personal y configuración de cuenta</p>
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
            
            <?php if ($mensaje_password): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $mensaje_password; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($mensaje_password_error): ?>
                <div class="alert alert-error">
                    <span>❌</span> <?php echo $mensaje_password_error; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Container -->
            <div class="profile-container">
                <!-- Editar Perfil -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>
                            <span>✏️</span> Editar Información Personal
                        </h2>
                        <p>Actualiza tus datos de contacto</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label><i>👤</i> Nombre completo</label>
                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i>📧</i> Correo electrónico</label>
                                <input type="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                                <small style="color: #718096; font-size: 12px;">El email no puede ser modificado</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i>📞</i> Teléfono</label>
                                <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" placeholder="Ej: 987654321">
                            </div>
                            
                            <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                💾 Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Cambiar Contraseña -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>
                            <span>🔐</span> Cambiar Contraseña
                        </h2>
                        <p>Actualiza tu contraseña de acceso</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label><i>🔒</i> Contraseña actual</label>
                                <input type="password" name="current_password" placeholder="Ingresa tu contraseña actual" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i>🆕</i> Nueva contraseña</label>
                                <input type="password" name="new_password" placeholder="Mínimo 6 caracteres" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i>✓</i> Confirmar nueva contraseña</label>
                                <input type="password" name="confirm_password" placeholder="Repite tu nueva contraseña" required>
                            </div>
                            
                            <button type="submit" name="cambiar_password" class="btn btn-primary">
                                🔄 Actualizar Contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Información de la Cuenta -->
            <div class="info-card">
                <h3>
                    <span>ℹ️</span> Información de la Cuenta
                </h3>
                
                <div class="info-row">
                    <span class="info-label"><i>📧</i> Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label"><i>👔</i> Rol:</span>
                    <span class="info-value">
                        <?php 
                        $rol_nombre = '';
                        $rol_clase = '';
                        if($usuario['rol'] == 'admin') {
                            $rol_nombre = '👑 Administrador';
                            $rol_clase = 'badge-admin';
                        } elseif($usuario['rol'] == 'veterinario') {
                            $rol_nombre = '👨‍⚕️ Veterinario';
                            $rol_clase = 'badge-veterinario';
                        } else {
                            $rol_nombre = '🐾 Cliente';
                            $rol_clase = 'badge-cliente';
                        }
                        ?>
                        <span class="badge <?php echo $rol_clase; ?>"><?php echo $rol_nombre; ?></span>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label"><i>📅</i> Miembro desde:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label"><i>🕐</i> Última actividad:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
            </div>

            <!-- Estadísticas Rápidas del Usuario -->
            <div class="profile-card" style="margin-top: 0;">
                <div class="card-header">
                    <h2>
                        <span>📊</span> Mis Estadísticas
                    </h2>
                    <p>Resumen de tu actividad en la plataforma</p>
                </div>
                <div class="card-body">
                    <div class="profile-stats">
                        <?php
                        // Contar mascotas del usuario (si es dueño)
                        $stmt_mascotas = $conn->prepare("
                            SELECT COUNT(*) as total 
                            FROM mascotas m 
                            LEFT JOIN duenos d ON m.dueno_id = d.id 
                            WHERE d.email = ? OR d.telefono = ?
                        ");
                        $stmt_mascotas->execute([$usuario['email'], $usuario['telefono']]);
                        $mis_mascotas = $stmt_mascotas->fetchColumn();
                        
                        // Contar citas del usuario
                        $stmt_citas = $conn->prepare("
                            SELECT COUNT(*) as total 
                            FROM reservaciones 
                            WHERE email = ? OR telefono = ?
                        ");
                        $stmt_citas->execute([$usuario['email'], $usuario['telefono']]);
                        $mis_citas = $stmt_citas->fetchColumn();
                        ?>
                        <div class="stat-mini">
                            <div class="number"><?php echo $mis_mascotas; ?></div>
                            <div class="label">🐕 Mis Mascotas</div>
                        </div>
                        <div class="stat-mini">
                            <div class="number"><?php echo $mis_citas; ?></div>
                            <div class="label">📅 Mis Citas</div>
                        </div>
                        <div class="stat-mini">
                            <div class="number"><?php echo date('Y'); ?></div>
                            <div class="label">📅 Año</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>