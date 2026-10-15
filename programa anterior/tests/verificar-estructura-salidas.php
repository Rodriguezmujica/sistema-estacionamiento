<?php
/**
 * 🔍 VERIFICAR ESTRUCTURA DE TABLA SALIDAS
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

echo "🔍 VERIFICAR ESTRUCTURA DE TABLA SALIDAS\n";
echo "========================================\n\n";

try {
    require_once __DIR__ . '/../config/conexion.php';
    
    echo "1. Verificando estructura de tabla 'salidas':\n";
    $sql = "DESCRIBE salidas";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "✅ Estructura de tabla 'salidas':\n";
        while ($row = $result->fetch_assoc()) {
            echo "   Campo: {$row['Field']} | Tipo: {$row['Type']} | Null: {$row['Null']} | Key: {$row['Key']} | Default: {$row['Default']}\n";
        }
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    echo "\n2. Verificando estructura de tabla 'ingresos':\n";
    $sql = "DESCRIBE ingresos";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "✅ Estructura de tabla 'ingresos':\n";
        while ($row = $result->fetch_assoc()) {
            echo "   Campo: {$row['Field']} | Tipo: {$row['Type']} | Null: {$row['Null']} | Key: {$row['Key']} | Default: {$row['Default']}\n";
        }
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    echo "\n3. Verificando registros de ejemplo:\n";
    $sql = "SELECT * FROM salidas LIMIT 3";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Registros de ejemplo en 'salidas':\n";
        while ($row = $result->fetch_assoc()) {
            echo "   " . json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "⚠️ No hay registros en 'salidas' o error: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
?>
