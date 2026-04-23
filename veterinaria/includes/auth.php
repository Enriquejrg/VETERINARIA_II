<?php
// includes/auth.php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin';
}

function login($email, $password, $conn) {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['contraseña'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre_completo'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];
        return true;
    }
    return false;
}
?>