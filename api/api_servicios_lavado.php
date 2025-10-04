<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
// ...resto del código...
// filepath: c:\xampp\htdocs\api_servicios_lavado.php

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

// Solo servicios de lavado (excluye estacionamiento y vacíos)
$sql = "SELECT idtipo_ingresos, nombre_servicio, precio FROM tipo_ingreso WHERE nombre_servicio <> '' AND nombre_servicio NOT LIKE '%estacionamiento%' ORDER BY precio";
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