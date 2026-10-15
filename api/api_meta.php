<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

/**
 * API dedicada para la gestión de Metas Mensuales.
 * Es rápida y específica para no bloquear la interfaz.
 */

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        http_response_code(405);
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $mes = intval($data['mes']);
    $anio = intval($data['anio']);
    $metaMonto = floatval($data['meta_monto']);
    $soloDiasLaborales = intval($data['solo_dias_laborales'] ?? 1);
    $incluirMensuales = intval($data['incluir_mensuales'] ?? 0);

    if ($metaMonto < 0) {
        throw new Exception("La meta no puede ser negativa");
    }

    $sql = "INSERT INTO metas_mensuales (mes, anio, meta_monto, solo_dias_laborales, incluir_mensuales)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                meta_monto = VALUES(meta_monto),
                solo_dias_laborales = VALUES(solo_dias_laborales),
                incluir_mensuales = VALUES(incluir_mensuales)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iidii', $mes, $anio, $metaMonto, $soloDiasLaborales, $incluirMensuales);

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar meta: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'message' => 'Meta actualizada correctamente']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>