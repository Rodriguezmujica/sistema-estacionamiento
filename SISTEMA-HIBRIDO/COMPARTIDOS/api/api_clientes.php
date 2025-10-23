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