<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

/**
 * API para obtener ingresos de clientes mensuales
 * Solo cuenta pagos con metodo_pago = 'MENSUAL' en la tabla salidas
 * NO incluye ingresos normales ni lavados
 */

$fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-t');

try {
    // Obtener solo pagos mensuales del rango de fechas
    $sql = "SELECT 
                COUNT(*) as total_pagos,
                SUM(total) as total
            FROM salidas
            WHERE metodo_pago = 'MENSUAL'
            AND DATE(fecha_salida) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'total' => floatval($data['total'] ?? 0),
        'total_pagos' => intval($data['total_pagos'] ?? 0),
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

