<?php
/**
 * Debug específico de las APIs
 * Sistema de Estacionamiento Los Ríos
 */

echo "<h1>🔍 Debug de APIs</h1>";

// Probar API de tickets directamente
echo "<h2>🎫 Probando API de Tickets</h2>";
echo "<p><strong>URL:</strong> <a href='api/get-tickets.php' target='_blank'>api/get-tickets.php</a></p>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json',
        'timeout' => 10
    ]
]);

$response = @file_get_contents('api/get-tickets.php', false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ Error: No se pudo conectar a la API de tickets</p>";
} else {
    echo "<p style='color: green;'>✅ Respuesta recibida de API de tickets</p>";
    echo "<h3>Respuesta completa:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "<h3>Datos decodificados:</h3>";
        echo "<pre style='background: #f0f8ff; padding: 10px; border: 1px solid #ddd;'>";
        print_r($data);
        echo "</pre>";
    }
}

echo "<hr>";

// Probar API de servicios directamente
echo "<h2>🚿 Probando API de Servicios de Lavado</h2>";
echo "<p><strong>URL:</strong> <a href='api/get-servicios-lavado.php' target='_blank'>api/get-servicios-lavado.php</a></p>";

$response = @file_get_contents('api/get-servicios-lavado.php', false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ Error: No se pudo conectar a la API de servicios</p>";
} else {
    echo "<p style='color: green;'>✅ Respuesta recibida de API de servicios</p>";
    echo "<h3>Respuesta completa:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "<h3>Datos decodificados:</h3>";
        echo "<pre style='background: #f0f8ff; padding: 10px; border: 1px solid #ddd;'>";
        print_r($data);
        echo "</pre>";
    }
}

echo "<hr>";

// Probar conexión directa a la base de datos
echo "<h2>🗄️ Probando Conexión Directa a Base de Datos</h2>";

require_once 'conexion.php';

try {
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
    // Probar consulta simple de ingresos
    echo "<h3>Probando consulta simple de ingresos:</h3>";
    $sql = "SELECT COUNT(*) as total FROM ingresos";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Consulta exitosa: " . $row['total'] . " registros en tabla 'ingresos'</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en consulta: " . $conn->error . "</p>";
    }
    
    // Probar consulta simple de lavados_pendientes
    echo "<h3>Probando consulta simple de lavados_pendientes:</h3>";
    $sql = "SELECT COUNT(*) as total FROM lavados_pendientes";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Consulta exitosa: " . $row['total'] . " registros en tabla 'lavados_pendientes'</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en consulta: " . $conn->error . "</p>";
    }
    
    // Probar la consulta compleja de tickets
    echo "<h3>Probando consulta compleja de tickets:</h3>";
    $sql = "SELECT 
                i.id,
                i.patente,
                i.fecha_ingreso,
                s.fecha_salida,
                i.tipo_servicio,
                i.precio,
                CASE WHEN s.fecha_salida IS NOT NULL THEN 1 ELSE 0 END as pagado
            FROM ingresos i
            LEFT JOIN salidas s ON i.id = s.ingreso_id
            ORDER BY i.fecha_ingreso DESC 
            LIMIT 5";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Consulta compleja exitosa: " . $result->num_rows . " registros</p>";
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Patente</th><th>Ingreso</th><th>Salida</th><th>Servicio</th><th>Precio</th><th>Pagado</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['patente'] . "</td>";
            echo "<td>" . $row['fecha_ingreso'] . "</td>";
            echo "<td>" . ($row['fecha_salida'] ?: 'Pendiente') . "</td>";
            echo "<td>" . $row['tipo_servicio'] . "</td>";
            echo "<td>$" . number_format($row['precio'], 0) . "</td>";
            echo "<td>" . ($row['pagado'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Error en consulta compleja: " . $conn->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
