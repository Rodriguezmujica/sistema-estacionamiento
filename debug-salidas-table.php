<?php
/**
 * Debug para verificar estructura de tabla salidas
 */

require_once 'conexion.php';

echo "<h1>Debug Tabla Salidas</h1>";

if ($conn->connect_error) {
    echo "<p style='color: red;'>Error de conexión: " . $conn->connect_error . "</p>";
    exit;
}

echo "<p style='color: green;'>Conexión exitosa</p>";

// Verificar estructura de la tabla salidas
$sql = "DESCRIBE salidas";
$result = $conn->query($sql);

if ($result) {
    echo "<h2>Estructura de la tabla 'salidas':</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error obteniendo estructura: " . $conn->error . "</p>";
}

// Probar inserción simple
echo "<h2>Probando inserción simple:</h2>";

$sql_test = "INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago) VALUES (999, NOW(), 1000, 'TEST', 'test')";

if ($conn->query($sql_test)) {
    echo "<p style='color: green;'>Inserción simple exitosa</p>";
    
    // Limpiar
    $conn->query("DELETE FROM salidas WHERE id_ingresos = 999");
    echo "<p>Inserción de prueba limpiada</p>";
} else {
    echo "<p style='color: red;'>Error en inserción simple: " . $conn->error . "</p>";
}

// Probar inserción con todos los campos
echo "<h2>Probando inserción completa:</h2>";

$sql_full = "INSERT INTO salidas (
    id_ingresos, 
    fecha_salida, 
    total, 
    metodo_pago, 
    tipo_pago, 
    transaction_id, 
    authorization_code, 
    card_type, 
    card_last4
) VALUES (998, NOW(), 2000, 'TUU', 'tuu', 'EST-998-TEST', 'AUTH123', 'VISA', '1234')";

if ($conn->query($sql_full)) {
    echo "<p style='color: green;'>Inserción completa exitosa</p>";
    
    // Limpiar
    $conn->query("DELETE FROM salidas WHERE id_ingresos = 998");
    echo "<p>Inserción de prueba limpiada</p>";
} else {
    echo "<p style='color: red;'>Error en inserción completa: " . $conn->error . "</p>";
}

$conn->close();
?>
