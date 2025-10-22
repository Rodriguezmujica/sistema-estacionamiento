<?php
/**
 * Debug de Base de Datos
 * Sistema de Estacionamiento Los Ríos
 */

require_once 'conexion.php';

echo "<h1>🔍 Debug de Base de Datos</h1>";

try {
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
    // Mostrar información de la base de datos
    $result = $conn->query("SELECT DATABASE() as db_name");
    $row = $result->fetch_assoc();
    echo "<p><strong>Base de datos:</strong> " . $row['db_name'] . "</p>";
    
    // Listar todas las tablas
    echo "<h2>📋 Tablas en la base de datos:</h2>";
    $result = $conn->query("SHOW TABLES");
    
    if ($result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            $tableName = $row[0];
            echo "<li><strong>$tableName</strong>";
            
            // Contar registros en cada tabla
            $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
            if ($countResult) {
                $countRow = $countResult->fetch_assoc();
                echo " - <em>" . $countRow['count'] . " registros</em>";
            }
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ No se encontraron tablas</p>";
    }
    
    // Verificar tablas específicas
    echo "<h2>🎫 Verificación de tabla 'tickets':</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'tickets'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Tabla 'tickets' existe</p>";
        
        // Mostrar estructura
        $result = $conn->query("DESCRIBE tickets");
        echo "<h3>Estructura de la tabla 'tickets':</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Mostrar algunos registros de ejemplo
        $result = $conn->query("SELECT * FROM tickets LIMIT 3");
        if ($result->num_rows > 0) {
            echo "<h3>Registros de ejemplo:</h3>";
            echo "<pre>";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ No hay registros en la tabla 'tickets'</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Tabla 'tickets' no existe</p>";
    }
    
    echo "<h2>🚿 Verificación de tabla 'servicios_lavado':</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'servicios_lavado'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Tabla 'servicios_lavado' existe</p>";
        
        // Mostrar estructura
        $result = $conn->query("DESCRIBE servicios_lavado");
        echo "<h3>Estructura de la tabla 'servicios_lavado':</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Mostrar algunos registros de ejemplo
        $result = $conn->query("SELECT * FROM servicios_lavado LIMIT 3");
        if ($result->num_rows > 0) {
            echo "<h3>Registros de ejemplo:</h3>";
            echo "<pre>";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ No hay registros en la tabla 'servicios_lavado'</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Tabla 'servicios_lavado' no existe</p>";
    }
    
    // Probar consultas de las APIs
    echo "<h2>🧪 Prueba de consultas de las APIs:</h2>";
    
    echo "<h3>Prueba de consulta de tickets:</h3>";
    try {
        $result = $conn->query("SELECT * FROM tickets ORDER BY fecha_ingreso DESC LIMIT 5");
        if ($result) {
            echo "<p style='color: green;'>✅ Consulta de tickets exitosa</p>";
            echo "<p>Registros encontrados: " . $result->num_rows . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Error en consulta de tickets: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Excepción en consulta de tickets: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Prueba de consulta de servicios de lavado:</h3>";
    try {
        $result = $conn->query("SELECT * FROM servicios_lavado ORDER BY fecha_servicio DESC LIMIT 5");
        if ($result) {
            echo "<p style='color: green;'>✅ Consulta de servicios de lavado exitosa</p>";
            echo "<p>Registros encontrados: " . $result->num_rows . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Error en consulta de servicios de lavado: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Excepción en consulta de servicios de lavado: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
