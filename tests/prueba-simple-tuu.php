<?php
/**
 * 🔍 PRUEBA SIMPLE API TUU
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

echo "🔍 PRUEBA SIMPLE API TUU\n";
echo "========================\n\n";

try {
    echo "1. Verificando conexión a base de datos...\n";
    require_once __DIR__ . '/../config/conexion.php';
    echo "✅ Conexión OK\n\n";
    
    echo "2. Verificando configuración TUU...\n";
    const TUU_API_BASE = 'https://integrations.payment.haulmer.com/RemotePayment/v2';
    const TUU_API_KEY = 'getenv('TUU_API_KEY') ?: 'TU_API_KEY_AQUI'';
    echo "✅ Configuración OK\n\n";
    
    echo "3. Probando consulta a TUU con Transaction ID fijo...\n";
    $transactionId = '700024107682';
    $url = TUU_API_BASE . '/GetPaymentRequest/' . urlencode($transactionId);
    echo "URL: $url\n";
    
    $headers = [
        'X-API-Key: ' . TUU_API_KEY,
        'Accept: application/json'
    ];
    echo "Headers: " . implode(', ', $headers) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "cURL Error: " . ($error ?: 'Ninguno') . "\n";
    echo "Respuesta completa:\n";
    echo "==================\n";
    echo $response . "\n";
    echo "==================\n\n";
    
    echo "4. Verificando si es JSON válido...\n";
    $jsonData = json_decode($response, true);
    if ($jsonData) {
        echo "✅ Respuesta es JSON válido\n";
        echo "Datos: " . json_encode($jsonData, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Respuesta NO es JSON válido\n";
        echo "Error JSON: " . json_last_error_msg() . "\n";
    }
    
    echo "\n🎉 PRUEBA COMPLETADA\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "\n❌ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

if (isset($conn)) {
    $conn->close();
}
?>
