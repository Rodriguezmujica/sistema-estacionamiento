<?php
/**
 * API simplificada para obtener tickets de estacionamiento
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
    
    // Primero probar consulta simple
    $sql = "SELECT COUNT(*) as total FROM ingresos";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error verificando tabla ingresos: ' . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    $totalIngresos = $row['total'];
    
    // Consulta simplificada para obtener tickets
    $sql = "SELECT 
                id,
                patente,
                fecha_ingreso,
                tipo_servicio,
                precio
            FROM ingresos 
            ORDER BY fecha_ingreso DESC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir fechas a formato ISO
        $row['fecha_ingreso'] = date('c', strtotime($row['fecha_ingreso']));
        
        // Convertir precios a float
        $row['precio'] = (float)$row['precio'];
        
        // Agregar campos por defecto
        $row['fecha_salida'] = null;
        $row['pagado'] = false;
        $row['cliente_nombre'] = null;
        $row['cliente_telefono'] = null;
        $row['observaciones'] = null;
        
        $tickets[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'count' => count($tickets),
        'total_ingresos' => $totalIngresos,
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
