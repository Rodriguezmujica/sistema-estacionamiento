<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

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