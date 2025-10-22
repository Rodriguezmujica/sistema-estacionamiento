<?php
/**
 * API para obtener servicios de lavado
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
    
    // Usar la tabla 'servicios_lavado' que ya tiene la estructura correcta
    $tableCheck = $conn->query("SHOW TABLES LIKE 'servicios_lavado'");
    if ($tableCheck->num_rows == 0) {
        throw new Exception('La tabla "servicios_lavado" no existe en la base de datos');
    }
    
    // Consulta para obtener servicios de lavado desde la tabla 'servicios_lavado'
    $sql = "SELECT 
                id,
                patente,
                fecha_servicio,
                tipo_lavado,
                precio_base,
                precio_extra,
                motivos_extra,
                cliente_nombre,
                cliente_telefono,
                observaciones,
                completado,
                created_at
            FROM servicios_lavado 
            ORDER BY fecha_servicio DESC 
            LIMIT 100";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir fechas a formato ISO
        $row['fecha_servicio'] = date('c', strtotime($row['fecha_servicio']));
        
        // Convertir motivos_extra de string a array
        if ($row['motivos_extra']) {
            $row['motivos_extra'] = explode(',', $row['motivos_extra']);
        } else {
            $row['motivos_extra'] = [];
        }
        
        // Convertir precios a float
        $row['precio_base'] = (float)$row['precio_base'];
        $row['precio_extra'] = (float)$row['precio_extra'];
        
        $servicios[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'servicios' => $servicios,
        'count' => count($servicios),
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
