<?php
/**
 * API para verificar si un pago TUU ya está sincronizado
 * Sistema de Estacionamiento Los Ríos
 */

require_once '../conexion.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verificar conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . $conn->connect_error);
    }
    
    // Obtener transaction_id
    $transaction_id = $_GET['transaction_id'] ?? '';
    
    if (empty($transaction_id)) {
        throw new Exception('Transaction ID requerido');
    }
    
    // Verificar si el pago ya está en la tabla de salidas
    $sql = "SELECT COUNT(*) as count 
            FROM salidas 
            WHERE transaction_id = ? OR id_ingresos = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Extraer ID de ingreso del transaction_id
    $id_ingreso = 0;
    if (preg_match('/EST-(\d+)-/', $transaction_id, $matches)) {
        $id_ingreso = intval($matches[1]);
    }
    
    $stmt->bind_param('si', $transaction_id, $id_ingreso);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $isSynced = $row['count'] > 0;
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'synced' => $isSynced,
        'transaction_id' => $transaction_id,
        'id_ingreso' => $id_ingreso,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'synced' => false,
        'timestamp' => date('c')
    ]);
}
?>
