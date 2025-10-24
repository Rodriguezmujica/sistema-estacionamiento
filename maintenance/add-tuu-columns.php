<?php
include 'conexion.php';

echo "=== AGREGANDO COLUMNAS TUU A LA TABLA INGRESOS ===\n";

try {
    // Agregar columna transaction_id_tuu
    $sql1 = "ALTER TABLE ingresos ADD COLUMN transaction_id_tuu VARCHAR(50) NULL";
    if ($conn->query($sql1)) {
        echo "✅ Columna 'transaction_id_tuu' agregada\n";
    } else {
        echo "❌ Error agregando 'transaction_id_tuu': " . $conn->error . "\n";
    }
    
    // Agregar columna total_calculado_tuu
    $sql2 = "ALTER TABLE ingresos ADD COLUMN total_calculado_tuu DECIMAL(10,2) NULL";
    if ($conn->query($sql2)) {
        echo "✅ Columna 'total_calculado_tuu' agregada\n";
    } else {
        echo "❌ Error agregando 'total_calculado_tuu': " . $conn->error . "\n";
    }
    
    // Agregar columna fecha_intento_tuu
    $sql3 = "ALTER TABLE ingresos ADD COLUMN fecha_intento_tuu DATETIME NULL";
    if ($conn->query($sql3)) {
        echo "✅ Columna 'fecha_intento_tuu' agregada\n";
    } else {
        echo "❌ Error agregando 'fecha_intento_tuu': " . $conn->error . "\n";
    }
    
    echo "\n=== VERIFICACIÓN FINAL ===\n";
    $result = $conn->query("DESCRIBE ingresos");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}

$conn->close();
?>
