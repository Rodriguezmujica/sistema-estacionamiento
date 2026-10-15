<?php
/**
 * API para verificar disponibilidad de impresora
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

try {
    $printers = [];
    $hasPrinters = false;
    
    // Detectar sistema operativo
    $os = strtoupper(substr(PHP_OS, 0, 3));
    
    if ($os === 'WIN') {
        // Windows - usar wmic
        $output = shell_exec('wmic printer get name /format:list 2>nul');
        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (strpos($line, 'Name=') === 0) {
                    $name = trim(substr($line, 5));
                    if (!empty($name) && $name !== 'Name=') {
                        $printers[] = $name;
                        $hasPrinters = true;
                    }
                }
            }
        }
    } else {
        // Linux - usar lpstat
        $output = shell_exec('lpstat -p 2>/dev/null');
        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (strpos($line, 'printer ') === 0) {
                    $parts = explode(' ', $line);
                    if (isset($parts[1])) {
                        $printers[] = $parts[1];
                        $hasPrinters = true;
                    }
                }
            }
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'available' => $hasPrinters,
        'printers' => $printers,
        'count' => count($printers),
        'os' => $os,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'available' => false,
        'printers' => [],
        'timestamp' => date('c')
    ]);
}
?>
