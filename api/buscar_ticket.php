<?php
require '../application/config/database.php'; // Ajusta la ruta según tu estructura
header('Content-Type: application/json');
$patente = strtoupper($_POST['patente'] ?? '');
if (!$patente) {
    echo json_encode(['success' => false, 'error' => 'Patente requerida']);
    exit;
}
$conn = new mysqli('localhost', 'root', '', 'estacionamiento');
$res = $conn->query("SELECT * FROM ingresos WHERE patente='$patente' AND salida=0 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo json_encode(['success' => true, 'ticket' => $row]);
} else {
    echo json_encode(['success' => false]);
}