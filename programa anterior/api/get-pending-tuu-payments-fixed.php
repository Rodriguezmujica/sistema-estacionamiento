<?php
/**
 * API para obtener pagos TUU pendientes - VERSIÓN CORREGIDA
 * Sistema de Estacionamiento Los Ríos
 */

// Configuración de errores mejorada
error_reporting(E_ALL);
ini_set('display_errors', 0); // Cambiar a 1 para debug
ini_set('log_errors', 1);

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para manejar errores de forma consistente
function sendError($message, $code = 500, $details = null) {
    http_response_code($code);
    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ];
    
    if ($details) {
        $response['details'] = $details;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Cargar conexión
    require_once '../conexion.php';
    
    // Verificar conexión a la base de datos
    if (!$conn) {
        sendError('No se pudo establecer conexión a la base de datos');
    }
    
    if ($conn->connect_error) {
        sendError('Error de conexión a la base de datos: ' . $conn->connect_error);
    }
    
    // Verificar que la tabla 'tickets' existe
    $check_table = $conn->query("SHOW TABLES LIKE 'tickets'");
    if (!$check_table || $check_table->num_rows === 0) {
        sendError('La tabla "tickets" no existe en la base de datos. Ejecuta el instalador de base de datos.');
    }
    
    // Verificar estructura de la tabla tickets
    $describe = $conn->query("DESCRIBE tickets");
    if (!$describe) {
        sendError('No se pudo verificar la estructura de la tabla "tickets"');
    }
    
    $columns = [];
    while ($row = $describe->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Verificar columnas requeridas
    $required_columns = ['id', 'patente', 'fecha_ingreso', 'precio', 'pagado', 'tipo_servicio'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        sendError('Faltan columnas requeridas en la tabla "tickets": ' . implode(', ', $missing_columns));
    }
    
    // Consulta mejorada con manejo de errores
    $sql = "SELECT 
                t.id as id_ingreso,
                t.patente,
                t.fecha_ingreso,
                t.precio,
                COALESCE(t.cliente_nombre, '') as cliente_nombre,
                COALESCE(t.cliente_telefono, '') as cliente_telefono,
                COALESCE(t.observaciones, '') as observaciones,
                'TUU' as metodo_pago,
                'tuu' as tipo_pago,
                CONCAT('EST-', t.id, '-', UNIX_TIMESTAMP()) as transaction_id,
                NOW() as created_at
            FROM tickets t
            LEFT JOIN salidas s ON t.id = s.id_ingresos
            WHERE t.pagado = 0 
            AND (t.tipo_servicio = 'estacionamiento' OR t.tipo_servicio = 'ambos')
            AND s.id_ingresos IS NULL
            ORDER BY t.fecha_ingreso DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        sendError('Error en la consulta SQL: ' . $conn->error, 500, [
            'sql' => $sql,
            'mysql_error' => $conn->error,
            'mysql_errno' => $conn->errno
        ]);
    }
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        // Validar datos antes de agregar
        if (empty($row['patente'])) {
            continue; // Saltar registros sin patente
        }
        
        // Convertir fechas a formato ISO
        try {
            $row['fecha_ingreso'] = date('c', strtotime($row['fecha_ingreso']));
            $row['created_at'] = date('c', strtotime($row['created_at']));
        } catch (Exception $e) {
            $row['fecha_ingreso'] = date('c');
            $row['created_at'] = date('c');
        }
        
        // Convertir precios a float y validar
        $row['precio'] = is_numeric($row['precio']) ? (float)$row['precio'] : 0.0;
        
        // Asegurar que los campos de texto no sean null
        $row['cliente_nombre'] = $row['cliente_nombre'] ?? '';
        $row['cliente_telefono'] = $row['cliente_telefono'] ?? '';
        $row['observaciones'] = $row['observaciones'] ?? '';
        
        $payments[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'count' => count($payments),
        'timestamp' => date('c'),
        'debug_info' => [
            'table_exists' => true,
            'columns_checked' => $columns,
            'query_executed' => true
        ]
    ]);
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en get-pending-tuu-payments.php: " . $e->getMessage() . " en línea " . $e->getLine());
    
    sendError('Error interno del servidor: ' . $e->getMessage(), 500, [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Cerrar conexión si existe
if (isset($conn) && $conn) {
    $conn->close();
}
?>
