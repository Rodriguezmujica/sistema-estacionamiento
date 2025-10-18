<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

// TODOS los servicios (lavado + estacionamiento)
$sql = "SELECT idtipo_ingresos, nombre_servicio, precio FROM tipo_ingreso WHERE nombre_servicio <> '' ORDER BY CASE WHEN nombre_servicio LIKE '%estacionamiento%' THEN 1 ELSE 2 END, precio";
$result = $conn->query($sql);

$servicios = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $servicios[] = $row;
    }
}

echo json_encode($servicios);

$conn->close();
?>

