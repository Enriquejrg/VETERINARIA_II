<?php
$host = 'localhost';
$dbname = 'clinica_veterinaria';
$username = 'root';
$password = 'root';  // Si tienes contraseña en MySQL, cámbiala aquí

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function getConnection() {
    global $conn;
    return $conn;
}
?>