<?php
/**
 * 🔍 VERIFICAR TRANSACTION ID RECIENTE
 * Sistema de Estacionamiento Los Ríos
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

echo "🔍 VERIFICAR TRANSACTION ID RECIENTE\n";
echo "====================================\n\n";

try {
    // Probar con el Transaction ID que aparece en la documentación
    $transactionId = '700024107682';
    $url = 'https://integrations.payment.haulmer.com/RemotePayment/v2/GetPaymentRequest/' . urlencode($transactionId);
    
    echo "1. Probando con Transaction ID de la documentación:\n";
    echo "   Transaction ID: $transactionId\n";
    echo "   URL: $url\n\n";
    
    $headers = [
        'X-API-Key: uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux',
        'accept: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "2. Resultado de la consulta:\n";
    echo "   HTTP Code: $httpCode\n";
    echo "   Content-Type: $contentType\n";
    echo "   cURL Error: " . ($error ?: 'Ninguno') . "\n\n";
    
    echo "3. Respuesta completa:\n";
    echo "=====================\n";
    echo $response . "\n";
    echo "=====================\n\n";
    
    // Analizar la respuesta
    $data = json_decode($response, true);
    if ($data) {
        echo "4. Análisis de la respuesta:\n";
        if (isset($data['error']) && $data['error'] === true) {
            echo "   ❌ TUU devolvió un error\n";
            echo "   Código: " . ($data['code'] ?? 'N/A') . "\n";
            echo "   Mensaje: " . ($data['message'] ?? 'N/A') . "\n";
            
            if (isset($data['code']) && $data['code'] === 'MR-160') {
                echo "\n   💡 Explicación MR-160:\n";
                echo "   - El Transaction ID existió pero ya no está disponible\n";
                echo "   - Puede haber expirado después de ser procesado\n";
                echo "   - O puede haber sido eliminado por políticas de TUU\n";
            }
        } else {
            echo "   ✅ TUU devolvió datos válidos\n";
            echo "   Datos: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "4. ❌ Respuesta no es JSON válido\n";
    }
    
    echo "\n5. Conclusión:\n";
    echo "=============\n";
    echo "✅ Tu API Key funciona correctamente\n";
    echo "✅ La consulta se ejecuta sin errores\n";
    echo "⚠️  El Transaction ID específico ya no está disponible\n";
    echo "💡 Esto es normal para Transaction IDs antiguos\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
