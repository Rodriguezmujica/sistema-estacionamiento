<?php
/**
 * API para obtener pagos TUU pendientes
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
    
    // Consulta para obtener pagos TUU pendientes
    $sql = "SELECT 
                i.idautos_estacionados as id_ingreso,
                i.patente,
                i.fecha_ingreso,
                i.precio,
                i.cliente_nombre,
                i.cliente_telefono,
                i.observaciones,
                'TUU' as metodo_pago,
                'tuu' as tipo_pago,
                CONCAT('EST-', i.idautos_estacionados, '-', UNIX_TIMESTAMP()) as transaction_id,
                NOW() as created_at
            FROM ingresos i
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            WHERE i.salida = 0 
            AND i.metodo_pago = 'TUU'
            AND s.id_ingresos IS NULL
            ORDER BY i.fecha_ingreso DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir fechas a formato ISO
        $row['fecha_ingreso'] = date('c', strtotime($row['fecha_ingreso']));
        $row['created_at'] = date('c', strtotime($row['created_at']));
        
        // Convertir precios a float
        $row['precio'] = (float)$row['precio'];
        
        $payments[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'count' => count($payments),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
