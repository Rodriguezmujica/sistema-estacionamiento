<?php
require_once '../config/conexion.php';

echo "<h1>Verificar Salidas para ID 3</h1>";

$sql = "SELECT * FROM salidas WHERE id_ingresos = 3";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: red;'>YA EXISTE un registro en salidas con id_ingresos = 3</p>";
    echo "<h2>Registro existente:</h2>";
    echo "<pre>" . print_r($result->fetch_assoc(), true) . "</pre>";
} else {
    echo "<p style='color: green;'>NO EXISTE registro en salidas con id_ingresos = 3</p>";
}

$conn->close();
?>
