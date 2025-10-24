<?php
include 'conexion.php';

echo "=== ESTRUCTURA DE LA TABLA INGRESOS ===\n";
$result = $conn->query('DESCRIBE ingresos');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== VERIFICAR SI EXISTEN LAS COLUMNAS TUU ===\n";
$columns_to_check = ['transaction_id_tuu', 'total_calculado_tuu', 'fecha_intento_tuu'];
foreach ($columns_to_check as $column) {
    $result = $conn->query("SHOW COLUMNS FROM ingresos LIKE '$column'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Columna '$column' existe\n";
    } else {
        echo "❌ Columna '$column' NO existe\n";
    }
}

$conn->close();
?>
