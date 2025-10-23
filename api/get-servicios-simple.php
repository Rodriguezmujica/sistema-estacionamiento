<?php
/**
 * API simplificada para obtener servicios de lavado
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
    $sql = "SELECT COUNT(*) as total FROM lavados_pendientes";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error verificando tabla lavados_pendientes: ' . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    $totalLavados = $row['total'];
    
    // Consulta simplificada para obtener servicios
    $sql = "SELECT 
                id,
                patente,
                fecha_servicio,
                tipo_lavado,
                precio_base,
                precio_extra,
                motivos_extra
            FROM lavados_pendientes 
            ORDER BY fecha_servicio DESC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir fechas a formato ISO
        $row['fecha_servicio'] = date('c', strtotime($row['fecha_servicio']));
        
        // Convertir precios a float
        $row['precio_base'] = (float)$row['precio_base'];
        $row['precio_extra'] = (float)$row['precio_extra'];
        
        // Convertir motivos_extra de string a array
        if ($row['motivos_extra']) {
            $row['motivos_extra'] = explode(',', $row['motivos_extra']);
        } else {
            $row['motivos_extra'] = [];
        }
        
        // Agregar campos por defecto
        $row['cliente_nombre'] = null;
        $row['cliente_telefono'] = null;
        $row['observaciones'] = null;
        $row['completado'] = false;
        
        $servicios[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'servicios' => $servicios,
        'count' => count($servicios),
        'total_lavados' => $totalLavados,
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
