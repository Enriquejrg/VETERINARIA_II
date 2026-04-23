<?php
// registro.php - Registro de nuevos usuarios (CORREGIDO)
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/config/database.php';
    
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos obligatorios";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Ingrese un email válido";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } else {
        try {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // Verificar si el email ya existe en usuarios
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "El email ya está registrado. Por favor use otro o inicie sesión.";
                $conn->rollBack();
            } else {
                // Encriptar contraseña
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // 1. Insertar usuario
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, email, telefono, contraseña, rol) VALUES (?, ?, ?, ?, 'cliente')");
                $stmt->execute([$nombre, $email, $telefono, $hashed_password]);
                $usuario_id = $conn->lastInsertId();
                
                // 2. Insertar también en la tabla duenos
                $stmt2 = $conn->prepare("INSERT INTO duenos (nombre_completo, telefono, email) VALUES (?, ?, ?)");
                $stmt2->execute([$nombre, $telefono, $email]);
                $dueno_id = $conn->lastInsertId();
                
                // Confirmar transacción
                $conn->commit();
                
                $success = "¡Registro exitoso! Ahora puede iniciar sesión.";
                
                // Limpiar formulario
                $_POST = [];
            }
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Clínica Veterinaria Mis Patitas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
        }

        .register-card h1 {
            color: #764ba2;
            font-size: 2em;
            text-align: center;
            margin-bottom: 5px;
        }

        .register-card h2 {
            color: #666;
            font-size: 1em;
            text-align: center;
            margin-bottom: 30px;
            font-weight: normal;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group label .required {
            color: #e53e3e;
        }

        .register-card input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .register-card input:focus {
            outline: none;
            border-color: #764ba2;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
        }

        .register-card button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-top: 10px;
        }

        .register-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(118, 75, 162, 0.4);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .login-link a {
            color: #764ba2;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <h1>🐾 Mis Patitas</h1>
            <h2>Crear una nueva cuenta</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>📝 Nombre completo <span class="required">*</span></label>
                    <input type="text" name="nombre" placeholder="Ej: Juan Perez" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>📧 Correo electrónico <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="Ej: juan@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>📞 Teléfono</label>
                    <input type="tel" name="telefono" placeholder="Ej: 987654321" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>🔒 Contraseña <span class="required">*</span></label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                    <div class="password-hint">La contraseña debe tener al menos 6 caracteres</div>
                </div>
                
                <div class="form-group">
                    <label>🔒 Confirmar contraseña <span class="required">*</span></label>
                    <input type="password" name="confirm_password" placeholder="Repite tu contraseña" required>
                </div>
                
                <button type="submit">✅ Registrarse</button>
            </form>
            
            <div class="login-link">
                ¿Ya tienes una cuenta? <a href="index.php">Inicia sesión aquí</a>
            </div>
        </div>
    </div>
</body>
</html>