<?php
// test_db.php - Archivo para probar la conexión a la base de datos

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Prueba de Conexión - Base de Datos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 30px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #764ba2;
            border-bottom: 3px solid #764ba2;
            padding-bottom: 10px;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #38a169;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #e53e3e;
        }
        .info {
            background: #bee3f8;
            color: #2c5282;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #3182ce;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #764ba2;
            color: white;
        }
        tr:hover {
            background: #f7fafc;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #38a169;
            color: white;
        }
        .badge-error {
            background: #e53e3e;
            color: white;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🐾 Prueba de Conexión - Clínica Veterinaria</h1>";

// Intentar conectar a la base de datos
echo "<h2>📡 1. Verificando conexión a MySQL...</h2>";

try {
    // Incluir configuración
    require_once 'config/database.php';
    
    echo "<div class='success'>✅ Conexión exitosa a la base de datos 'clinica_veterinaria'</div>";
    
    // Probar consulta simple
    echo "<h2>📊 2. Verificando tablas existentes...</h2>";
    
    $tables = $conn->query("SHOW TABLES");
    $tablas = $tables->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>📋 Tablas encontradas: " . count($tablas) . "</div>";
    
    if(count($tablas) > 0) {
        echo "<ul>";
        foreach($tablas as $table) {
            echo "<li>✅ Tabla: <strong>$table</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='error'>⚠️ No se encontraron tablas. Debes importar el archivo SQL.</div>";
    }
    
    // Probar datos en cada tabla
    echo "<h2>📈 3. Verificando datos en las tablas...</h2>";
    
    $tablas_datos = ['usuarios', 'duenos', 'mascotas', 'productos', 'reservaciones'];
    
    foreach($tablas_datos as $tabla) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM $tabla");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $icono = $total > 0 ? "✅" : "⚠️";
            $color = $total > 0 ? "success" : "info";
            echo "<div class='$color'>$icono Tabla <strong>$tabla</strong>: $total registro(s)</div>";
        } catch(PDOException $e) {
            echo "<div class='error'>❌ Error en tabla $tabla: " . $e->getMessage() . "</div>";
        }
    }
    
    // Mostrar usuarios
    echo "<h2>👥 4. Usuarios registrados en el sistema</h2>";
    $usuarios = $conn->query("SELECT id, nombre_completo, email, rol FROM usuarios")->fetchAll();
    
    if(count($usuarios) > 0) {
        echo "<table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>
                </thead>
                <tbody>";
        foreach($usuarios as $u) {
            echo "<tr>
                    <td>{$u['id']}</td>
                    <td>" . htmlspecialchars($u['nombre_completo']) . "</td>
                    <td>" . htmlspecialchars($u['email']) . "</td>
                    <td>" . htmlspecialchars($u['rol']) . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='error'>❌ No hay usuarios registrados</div>";
    }
    
    // Mostrar mascotas
    echo "<h2>🐕 5. Últimas mascotas registradas</h2>";
    $mascotas = $conn->query("SELECT m.*, d.nombre_completo as dueno FROM mascotas m LEFT JOIN duenos d ON m.dueno_id = d.id LIMIT 5")->fetchAll();
    
    if(count($mascotas) > 0) {
        echo "<table>
                <thead>
                    <tr><th>Nombre</th><th>Especie</th><th>Raza</th><th>Dueño</th></tr>
                </thead>
                <tbody>";
        foreach($mascotas as $m) {
            echo "<tr>
                    <td>" . htmlspecialchars($m['nombre']) . "</td>
                    <td>" . htmlspecialchars($m['especie']) . "</td>
                    <td>" . htmlspecialchars($m['raza']) . "</td>
                    <td>" . htmlspecialchars($m['dueno']) . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='info'>📝 No hay mascotas registradas aún</div>";
    }
    
    // Mostrar productos
    echo "<h2>📦 6. Productos en inventario</h2>";
    $productos = $conn->query("SELECT * FROM productos LIMIT 5")->fetchAll();
    
    if(count($productos) > 0) {
        echo "<table>
                <thead>
                    <tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th></tr>
                </thead>
                <tbody>";
        foreach($productos as $p) {
            $estado = $p['cantidad'] < 10 ? "<span class='badge' style='background:#e53e3e;color:white'>Stock Bajo</span>" : "<span class='badge' style='background:#38a169;color:white'>Normal</span>";
            echo "<tr>
                    <td>" . htmlspecialchars($p['nombre']) . "</td>
                    <td>S/ " . number_format($p['precio'], 2) . "</td>
                    <td>{$p['cantidad']}</td>
                    <td>$estado</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='info'>📦 No hay productos registrados aún</div>";
    }
    
    // Mostrar citas
    echo "<h2>📅 7. Próximas citas</h2>";
    $citas = $conn->query("SELECT * FROM reservaciones WHERE fecha_solicitada >= CURDATE() ORDER BY fecha_solicitada ASC LIMIT 5")->fetchAll();
    
    if(count($citas) > 0) {
        echo "<table>
                <thead>
                    <tr><th>Cliente</th><th>Mascota</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>
                </thead>
                <tbody>";
        foreach($citas as $c) {
            $estado_class = $c['estado'] == 'pendiente' ? '#ecc94b' : ($c['estado'] == 'confirmada' ? '#38a169' : '#a0aec0');
            echo "<tr>
                    <td>" . htmlspecialchars($c['nombre_cliente']) . "</td>
                    <td>" . htmlspecialchars($c['nombre_mascota']) . "</td>
                    <td>" . date('d/m/Y', strtotime($c['fecha_solicitada'])) . "</td>
                    <td>{$c['hora_solicitada']}</td>
                    <td><span class='badge' style='background:$estado_class;color:white'>{$c['estado']}</span></td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='info'>📅 No hay citas programadas</div>";
    }
    
    // Resumen final
    echo "<h2>📊 8. Resumen de la Base de Datos</h2>";
    
    $resumen = [
        'Usuarios' => $conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
        'Dueños' => $conn->query("SELECT COUNT(*) FROM duenos")->fetchColumn(),
        'Mascotas' => $conn->query("SELECT COUNT(*) FROM mascotas")->fetchColumn(),
        'Productos' => $conn->query("SELECT COUNT(*) FROM productos")->fetchColumn(),
        'Citas' => $conn->query("SELECT COUNT(*) FROM reservaciones")->fetchColumn()
    ];
    
    echo "<div class='info'>";
    foreach($resumen as $key => $value) {
        echo "<p><strong>$key:</strong> $value registros</p>";
    }
    echo "</div>";
    
    echo "<div class='success'>
            <strong>🎉 ¡PRUEBA EXITOSA!</strong><br>
            La base de datos está conectada correctamente y funcionando.
            <br><br>
            <strong>🔐 Credenciales para iniciar sesión:</strong><br>
            📧 admin@veterinaria.com | 🔑 admin123<br>
            📧 juan@example.com | 🔑 admin123
          </div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>
            <strong>❌ ERROR DE CONEXIÓN</strong><br>
            Mensaje: " . $e->getMessage() . "<br><br>
            <strong>Posibles soluciones:</strong><br>
            1. Verifica que MySQL esté ejecutándose en XAMPP<br>
            2. Verifica que la base de datos 'clinica_veterinaria' existe<br>
            3. Verifica que el archivo config/database.php tiene las credenciales correctas<br>
            4. Importa el archivo SQL en phpMyAdmin
          </div>";
}

echo "
        <div class='footer'>
            <p>📁 Proyecto: Sistema de Gestión para Clínica Veterinaria - Mis Patitas</p>
            <p>🕒 Fecha y hora de prueba: " . date('d/m/Y H:i:s') . "</p>
        </div>
    </div>
</body>
</html>";
?>