<?php
// api/registrar-ingreso.php
header('Content-Type: application/json');

// Configuración de conexión (ajusta según tu entorno)
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'estacionamiento';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$patente = strtoupper(trim($_POST['patente'] ?? ''));
$tipo_servicio = $_POST['tipo_servicio'] ?? '';
$nombre_cliente = $_POST['nombre_cliente'] ?? '';
// Si tienes usuario en sesión, puedes usar $_SESSION['usuario']
$usuario = $_POST['usuario'] ?? null;

if (!$patente || !$tipo_servicio) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Buscar idtipo_ingreso según el tipo de servicio
$stmt = $conn->prepare('SELECT idtipo_ingresos FROM tipo_ingreso WHERE nombre_servicio = ? LIMIT 1');
$stmt->bind_param('s', $tipo_servicio);
$stmt->execute();
$stmt->bind_result($idtipo_ingreso);
$stmt->fetch();
$stmt->close();

if (!$idtipo_ingreso) {
    echo json_encode(['success' => false, 'error' => 'Tipo de servicio no válido']);
    exit;
}

// Insertar en ingresos (ajusta si agregaste el campo usuario)
$stmt = $conn->prepare('INSERT INTO ingresos (patente, idtipo_ingreso) VALUES (?, ?)');
$stmt->bind_param('si', $patente, $idtipo_ingreso);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo registrar el ingreso']);
}
$conn->close();
