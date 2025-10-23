<?php
/**
 * API para obtener tickets de estacionamiento
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
    
    // Usar la tabla 'tickets' que ya tiene la estructura correcta
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tickets'");
    if ($tableCheck->num_rows == 0) {
        throw new Exception('La tabla "tickets" no existe en la base de datos');
    }
    
    // Consulta para obtener tickets desde la tabla 'tickets'
    $sql = "SELECT 
                id,
                patente,
                fecha_ingreso,
                fecha_salida,
                tipo_servicio,
                precio,
                pagado,
                cliente_nombre,
                cliente_telefono,
                observaciones,
                created_at
            FROM tickets 
            ORDER BY fecha_ingreso DESC 
            LIMIT 100";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir fechas a formato ISO
        $row['fecha_ingreso'] = date('c', strtotime($row['fecha_ingreso']));
        if ($row['fecha_salida']) {
            $row['fecha_salida'] = date('c', strtotime($row['fecha_salida']));
        }
        
        // Convertir booleanos
        $row['pagado'] = (bool)$row['pagado'];
        
        $tickets[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'count' => count($tickets),
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
