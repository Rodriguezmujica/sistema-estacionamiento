<?php
require_once __DIR__ . '/conexion.php';

echo "<h3>🔧 Actualizando tabla 'clientes' para pagos recurrentes...</h3>";

// 1. Agregar la columna para el día de pago mensual
$columna = 'dia_pago_mensual';
$check_sql = "SHOW COLUMNS FROM `clientes` LIKE '$columna'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE `clientes` ADD COLUMN `$columna` INT(2) DEFAULT 5 AFTER `monto_plan`";
    if ($conn->query($alter_sql) === TRUE) {
        echo "<p style='color:green;'>✅ Columna '<strong>$columna</strong>' agregada con éxito.</p>";
    } else {
        die("<p style='color:red;'>❌ Error al agregar columna '<strong>$columna</strong>': " . $conn->error . "</p>");
    }
} else {
    echo "<p style='color:blue;'>ℹ️ La columna '<strong>$columna</strong>' ya existe.</p>";
}

// 2. Renombrar 'fecha_fin_plan' a 'fecha_proximo_vencimiento' para mayor claridad
$columna_vieja = 'fecha_fin_plan';
$columna_nueva = 'fecha_proximo_vencimiento';
$check_sql_rename = "SHOW COLUMNS FROM `clientes` LIKE '$columna_nueva'";
$result_rename = $conn->query($check_sql_rename);

if ($result_rename->num_rows == 0) {
    $rename_sql = "ALTER TABLE `clientes` CHANGE COLUMN `$columna_vieja` `$columna_nueva` DATE DEFAULT NULL";
    if ($conn->query($rename_sql) === TRUE) {
        echo "<p style='color:green;'>✅ Columna '<strong>$columna_vieja</strong>' renombrada a '<strong>$columna_nueva</strong>'.</p>";
    } else {
        die("<p style='color:red;'>❌ Error al renombrar la columna: " . $conn->error . "</p>");
    }
} else {
    echo "<p style='color:blue;'>ℹ️ La columna '<strong>$columna_nueva</strong>' ya existe.</p>";
}

echo "<h3 style='color:green;'>¡Actualización completada!</h3>";
echo "<p>Tu tabla 'clientes' ahora soporta pagos mensuales recurrentes.</p>";
echo "<p><strong>Importante:</strong> Ahora puedes eliminar este archivo ('actualizar_tabla_dia_pago.php') por seguridad.</p>";

$conn->close();
?>
