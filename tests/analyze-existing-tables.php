<?php
/**
 * Analizar estructura de tablas existentes
 * Sistema de Estacionamiento Los Ríos
 */

require_once 'conexion.php';

echo "<h1>🔍 Análisis de Tablas Existentes</h1>";

try {
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
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
    }
    
    // Analizar tabla ingresos
    echo "<h2>🎫 Análisis de tabla 'ingresos':</h2>";
    $result = $conn->query("DESCRIBE ingresos");
    if ($result) {
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
        $result = $conn->query("SELECT * FROM ingresos LIMIT 3");
        if ($result->num_rows > 0) {
            echo "<h3>Registros de ejemplo:</h3>";
            echo "<pre>";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        }
    }
    
    // Analizar tabla salidas
    echo "<h2>🚪 Análisis de tabla 'salidas':</h2>";
    $result = $conn->query("DESCRIBE salidas");
    if ($result) {
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
    }
    
    // Analizar tabla lavados_pendientes
    echo "<h2>🚿 Análisis de tabla 'lavados_pendientes':</h2>";
    $result = $conn->query("DESCRIBE lavados_pendientes");
    if ($result) {
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
        $result = $conn->query("SELECT * FROM lavados_pendientes LIMIT 3");
        if ($result->num_rows > 0) {
            echo "<h3>Registros de ejemplo:</h3>";
            echo "<pre>";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        }
    }
    
    // Analizar tabla clientes
    echo "<h2>👥 Análisis de tabla 'clientes':</h2>";
    $result = $conn->query("DESCRIBE clientes");
    if ($result) {
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
    }
    
    // Probar consulta combinada
    echo "<h2>🧪 Prueba de consulta combinada (ingresos + salidas):</h2>";
    try {
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
            echo "<p style='color: green;'>✅ Consulta combinada exitosa</p>";
            echo "<p>Registros encontrados: " . $result->num_rows . "</p>";
            
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
            echo "<p style='color: red;'>❌ Error en consulta combinada: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Excepción en consulta combinada: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
