<?php
// api/registrar-ingreso.php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

$patente = strtoupper(trim($_POST['patente'] ?? ''));
$idtipo_ingreso = intval($_POST['tipo_servicio'] ?? 0);
$nombre_cliente = trim($_POST['nombre_cliente'] ?? '');

if (!$patente || !$idtipo_ingreso) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Verificar que el tipo de servicio existe
$stmt = $conn->prepare('SELECT idtipo_ingresos FROM tipo_ingreso WHERE idtipo_ingresos = ? LIMIT 1');
$stmt->bind_param('i', $idtipo_ingreso);
$stmt->execute();
$stmt->bind_result($id_existe);
$stmt->fetch();
$stmt->close();

if (!$id_existe) {
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
