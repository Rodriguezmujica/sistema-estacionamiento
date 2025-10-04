<?php
// filepath: c:\xampp\htdocs\api_clientes.php

header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = ""; // Por defecto en XAMPP
$db = "estacionamiento";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida"]);
    exit;
}

$sql = "SELECT patente, nombres, apellidos FROM clientes";
$result = $conn->query($sql);

$clientes = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}

echo json_encode($clientes);

$conn->close();
?>