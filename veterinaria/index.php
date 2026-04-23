<?php
// index.php - Login mejorado con enlace a registro
session_start();

// Si ya está logueado, ir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/auth.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos";
    } else {
        if (login($email, $password, $conn)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Email o contraseña incorrectos";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Clínica Veterinaria Mis Patitas</title>
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
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        .login-card h1 {
            color: #764ba2;
            font-size: 2.5em;
            margin-bottom: 5px;
        }

        .login-card h2 {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
            font-weight: normal;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .login-card input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .login-card input:focus {
            outline: none;
            border-color: #764ba2;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
        }

        .login-card button {
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
        }

        .login-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(118, 75, 162, 0.4);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: left;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        .demo-creds {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
            color: #666;
        }

        .demo-creds p {
            margin-bottom: 8px;
            font-weight: bold;
        }

        .demo-creds small {
            display: block;
            margin: 5px 0;
            font-family: monospace;
            background: #f5f5f5;
            padding: 5px;
            border-radius: 5px;
        }

        .register-link {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .register-link a {
            color: #764ba2;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1>🐾 Mis Patitas</h1>
            <h2>Clínica Veterinaria</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>📧 Correo Electrónico</label>
                    <input type="email" name="email" placeholder="admin@veterinaria.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>🔒 Contraseña</label>
                    <input type="password" name="password" placeholder="••••••" required>
                </div>
                <button type="submit">🚀 Iniciar Sesión</button>
            </form>
            
            <div class="register-link">
                ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
            </div>
            
            <div class="demo-creds">
                <p>🔐 Credenciales de prueba:</p>
                <small>📧 admin@veterinaria.com<br>🔑 admin123</small>
                <small>📧 juan@example.com<br>🔑 admin123</small>
            </div>
        </div>
    </div>
</body>
</html>