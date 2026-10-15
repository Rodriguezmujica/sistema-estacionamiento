// 🔧 HABILITAR CORS PARA SISTEMA HÍBRIDO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (pre-flight), responder vacía
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

$patente = strtoupper($_POST['patente'] ?? '');
if (!$patente) {
    echo json_encode(['success' => false, 'error' => 'Patente requerida']);
    exit;
}
$res = $conn->query("SELECT * FROM ingresos WHERE patente='$patente' AND salida=0 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo json_encode(['success' => true, 'ticket' => $row]);
} else {
    echo json_encode(['success' => false]);
}