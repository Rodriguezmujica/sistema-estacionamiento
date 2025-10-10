<?php
/**
 * Script para ejecutar la actualización de la tabla salidas
 * Ejecutar este archivo UNA VEZ desde el navegador
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Actualizar Base de Datos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Actualización de Base de Datos - Tabla Salidas</h1>";

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    echo "<div class='error'>❌ Error de conexión: " . $conexion->connect_error . "</div>";
    exit;
}

echo "<div class='info'>✅ Conexión a la base de datos establecida</div>";

// Leer el archivo SQL
$sqlFile = __DIR__ . '/actualizar_tabla_salidas_completo.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='error'>❌ No se encuentra el archivo SQL: $sqlFile</div>";
    exit;
}

$sql = file_get_contents($sqlFile);
echo "<div class='info'>📄 Archivo SQL cargado correctamente</div>";

// Dividir el SQL en sentencias individuales
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && strpos($stmt, '--') !== 0 && $stmt !== '';
    }
);

echo "<h2>Ejecutando actualizaciones...</h2>";

$errores = 0;
$exitosas = 0;

foreach ($statements as $statement) {
    // Ignorar comentarios y líneas vacías
    if (empty(trim($statement)) || strpos(trim($statement), '--') === 0) {
        continue;
    }
    
    echo "<div class='info'><strong>Ejecutando:</strong><br><pre>" . htmlspecialchars($statement) . "</pre></div>";
    
    if ($conexion->multi_query($statement)) {
        do {
            if ($result = $conexion->store_result()) {
                echo "<div class='success'>✅ Ejecutado correctamente</div>";
                
                // Mostrar resultados si hay
                while ($row = $result->fetch_assoc()) {
                    echo "<pre>" . print_r($row, true) . "</pre>";
                }
                $result->free();
                $exitosas++;
            } else {
                if ($conexion->error) {
                    // Verificar si es un error de "columna ya existe"
                    if (strpos($conexion->error, "Duplicate column name") !== false) {
                        echo "<div class='info'>ℹ️ La columna ya existe (esto es normal)</div>";
                    } else {
                        echo "<div class='error'>⚠️ Advertencia: " . $conexion->error . "</div>";
                        $errores++;
                    }
                } else {
                    echo "<div class='success'>✅ Ejecutado correctamente</div>";
                    $exitosas++;
                }
            }
        } while ($conexion->more_results() && $conexion->next_result());
    } else {
        // Verificar si es un error de "columna ya existe"
        if (strpos($conexion->error, "Duplicate column name") !== false) {
            echo "<div class='info'>ℹ️ La columna ya existe (esto es normal)</div>";
        } else {
            echo "<div class='error'>❌ Error: " . $conexion->error . "</div>";
            $errores++;
        }
    }
    
    echo "<hr>";
}

// Verificar la estructura actualizada
echo "<h2>📋 Verificando estructura de la tabla...</h2>";
$result = $conexion->query("DESCRIBE salidas");
if ($result) {
    echo "<div class='success'>✅ Estructura de la tabla 'salidas':</div>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h2>📊 Resumen</h2>";
echo "<div class='info'>";
echo "<p><strong>Operaciones exitosas:</strong> $exitosas</p>";
echo "<p><strong>Errores/Advertencias:</strong> $errores</p>";
echo "</div>";

if ($errores === 0) {
    echo "<div class='success'>";
    echo "<h3>✅ ¡Actualización completada con éxito!</h3>";
    echo "<p>La tabla 'salidas' ha sido actualizada correctamente.</p>";
    echo "<p><strong>Ahora puedes:</strong></p>";
    echo "<ul>";
    echo "<li>Cerrar esta ventana</li>";
    echo "<li>Volver a intentar hacer un cobro con TUU</li>";
    echo "<li>El sistema debería funcionar correctamente ahora</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ La actualización se completó con algunos errores</h3>";
    echo "<p>Revisa los mensajes de error arriba para más detalles.</p>";
    echo "</div>";
}

$conexion->close();

echo "</body></html>";
?>

