<?php
/**
 * API para imprimir documentos
 * Sistema de Estacionamiento Los Ríos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.',
        'timestamp' => date('c')
    ]);
    exit();
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['data'])) {
        throw new Exception('Datos de impresión no proporcionados');
    }
    
    $data = $input['data'];
    $type = $input['type'] ?? 'documento';
    $timestamp = $input['timestamp'] ?? date('c');
    
    // Crear archivo temporal
    $filename = 'temp_print_' . time() . '_' . uniqid() . '.txt';
    $filepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
    
    // Escribir datos al archivo
    if (file_put_contents($filepath, $data) === false) {
        throw new Exception('No se pudo crear archivo temporal');
    }
    
    $result = '';
    $success = false;
    
    // Detectar sistema operativo
    $os = strtoupper(substr(PHP_OS, 0, 3));
    
    if ($os === 'WIN') {
        // Windows - usar comando print
        $command = 'print /D:USB001 "' . $filepath . '" 2>&1';
        $result = shell_exec($command);
        $success = true; // Asumir éxito en Windows
    } else {
        // Linux - usar lp
        $command = 'lp "' . $filepath . '" 2>&1';
        $result = shell_exec($command);
        $success = (strpos($result, 'request id') !== false);
    }
    
    // Limpiar archivo temporal
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => $success,
        'type' => $type,
        'result' => $result,
        'command' => $command,
        'os' => $os,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    // Limpiar archivo temporal si existe
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
