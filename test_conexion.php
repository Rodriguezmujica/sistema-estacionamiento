<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>🔍 Test de Conexión Windows</h2>";

echo "<p><strong>Sistema Operativo:</strong> " . PHP_OS . "</p>";
echo "<p><strong>Versión PHP:</strong> " . PHP_VERSION . "</p>";

$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
echo "<p><strong>Detectado como Windows:</strong> " . ($is_windows ? 'SÍ ✅' : 'NO ❌') . "</p>";

echo "<hr>";

echo "<h3>Intentando conectar con:</h3>";
echo "<ul>";
echo "<li>Host: localhost</li>";
echo "<li>Usuario: root</li>";
echo "<li>Contraseña: (vacía)</li>";
echo "<li>Base de datos: estacionamiento</li>";
echo "</ul>";

echo "<hr>";

// Test 1: Conexión básica sin BD
echo "<h3>Test 1: Conexión a MySQL</h3>";
$conn1 = @new mysqli('localhost', 'root', '');
if ($conn1->connect_error) {
    echo "<p style='color: red;'>❌ ERROR: " . $conn1->connect_error . "</p>";
    echo "<p><strong>Solución:</strong> Verifica que MySQL esté corriendo en XAMPP</p>";
} else {
    echo "<p style='color: green;'>✅ Conexión a MySQL exitosa</p>";
    $conn1->close();
}

echo "<hr>";

// Test 2: Conexión con BD
echo "<h3>Test 2: Conexión a Base de Datos 'estacionamiento'</h3>";
$conn2 = @new mysqli('localhost', 'root', '', 'estacionamiento');
if ($conn2->connect_error) {
    echo "<p style='color: red;'>❌ ERROR: " . $conn2->connect_error . "</p>";
    
    if (strpos($conn2->connect_error, 'Unknown database') !== false) {
        echo "<p><strong>Problema:</strong> La base de datos 'estacionamiento' no existe</p>";
        echo "<p><strong>Solución:</strong> Importa el archivo .sql en phpMyAdmin</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Conexión a BD 'estacionamiento' exitosa</p>";
    
    // Test 3: Verificar tablas
    echo "<h3>Test 3: Verificar Tablas</h3>";
    $result = $conn2->query("SHOW TABLES");
    if ($result) {
        echo "<p>Tablas encontradas: " . $result->num_rows . "</p>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    $conn2->close();
}

echo "<hr>";

// Test 4: Probar conexion.php
echo "<h3>Test 4: Probar archivo conexion.php</h3>";
try {
    require_once __DIR__ . '/conexion.php';
    
    if (isset($conn) && $conn->ping()) {
        echo "<p style='color: green;'>✅ conexion.php funciona correctamente</p>";
        echo "<p>Usuario conectado: $user</p>";
        echo "<p>Base de datos: $dbname</p>";
    } else {
        echo "<p style='color: red;'>❌ conexion.php NO funciona</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al cargar conexion.php: " . $e->getMessage() . "</p>";
}

?>


