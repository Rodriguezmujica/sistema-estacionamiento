<?php
/**
 * 🧪 PRUEBA SIMPLE DE CONECTIVIDAD
 * Para verificar si el problema es de conexión o de código
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'php_version' => PHP_VERSION,
    'os' => PHP_OS
], JSON_UNESCAPED_UNICODE);
?>
