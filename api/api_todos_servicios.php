<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db = "estacionamiento";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida"]);
    exit;
}

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

