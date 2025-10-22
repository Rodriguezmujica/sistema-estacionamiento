<?php
/**
 * API para obtener usuarios del sistema
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
    
    // Consulta para obtener usuarios (sin password_hash por seguridad)
    $sql = "SELECT id, usuario, rol FROM usuarios ORDER BY id ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir ID a entero
        $row['id'] = (int)$row['id'];
        
        $usuarios[] = $row;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios,
        'count' => count($usuarios),
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
