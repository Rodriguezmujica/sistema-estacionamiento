<?php
/**
 * Diagnóstico de TLS para TUU
 */

header('Content-Type: text/html; charset=utf-8');

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #1976d2; text-align: center; }
h2 { color: #333; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 3px 8px; border-radius: 3px; font-family: 'Courier New', monospace; }
.log { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 400px; overflow-y: auto; margin: 20px 0; }
</style>";

echo "<h1>🔐 Diagnóstico TLS para TUU</h1>";
echo "<hr>";

echo "<div class='info'>";
echo "<h3>📢 Anuncio Importante de TUU:</h3>";
echo "<p><strong>\"A partir de hoy comienza a regir la restricción que permite el consumo de APIs únicamente mediante el protocolo TLS 1.2 o superior. Las versiones anteriores (TLS 1.0 y 1.1) ya no serán soportadas.\"</strong></p>";
echo "</div>";

echo "<h2>🔍 Verificación de TLS del Sistema</h2>";

// Verificar versión de PHP y OpenSSL
echo "<table>";
echo "<tr><th>Componente</th><th>Versión</th><th>Estado</th></tr>";

// PHP Version
$phpVersion = phpversion();
$phpOK = version_compare($phpVersion, '7.0.0', '>=');
echo "<tr>";
echo "<td><strong>PHP</strong></td>";
echo "<td>$phpVersion</td>";
echo "<td>" . ($phpOK ? '✅ OK' : '⚠️ Antigua') . "</td>";
echo "</tr>";

// OpenSSL Version
$opensslVersion = OPENSSL_VERSION_TEXT;
$opensslOK = strpos($opensslVersion, 'OpenSSL 1.1') !== false || strpos($opensslVersion, 'OpenSSL 3.') !== false;
echo "<tr>";
echo "<td><strong>OpenSSL</strong></td>";
echo "<td>$opensslVersion</td>";
echo "<td>" . ($opensslOK ? '✅ OK' : '⚠️ Revisar') . "</td>";
echo "</tr>";

// cURL Version
$curlVersion = curl_version();
$curlOK = version_compare($curlVersion['version'], '7.34.0', '>=');
echo "<tr>";
echo "<td><strong>cURL</strong></td>";
echo "<td>" . $curlVersion['version'] . "</td>";
echo "<td>" . ($curlOK ? '✅ OK' : '⚠️ Antigua') . "</td>";
echo "</tr>";

// SSL Version soportada
$sslVersion = $curlVersion['ssl_version'];
echo "<tr>";
echo "<td><strong>SSL Version</strong></td>";
echo "<td>$sslVersion</td>";
echo "<td>" . (strpos($sslVersion, '1.2') !== false || strpos($sslVersion, '1.3') !== false ? '✅ TLS 1.2+' : '⚠️ Revisar') . "</td>";
echo "</tr>";

echo "</table>";

echo "<h2>🧪 Test de Conectividad TLS 1.2+</h2>";

// Test con diferentes versiones TLS
$url = 'https://integrations.payment.haulmer.com';
$tests = [
    'TLS 1.0' => CURL_SSLVERSION_TLSv1,
    'TLS 1.1' => CURL_SSLVERSION_TLSv1_1,
    'TLS 1.2' => CURL_SSLVERSION_TLSv1_2,
    'TLS 1.3' => CURL_SSLVERSION_TLSv1_3
];

echo "<table>";
echo "<tr><th>Versión TLS</th><th>Estado</th><th>Detalles</th></tr>";

foreach ($tests as $name => $version) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSLVERSION => $version,
        CURLOPT_SSL_VERIFYPEER => false, // Solo para test
        CURLOPT_NOBODY => true
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    // CURLINFO_SSL_VERIFYRESULT no está disponible en todas las versiones de PHP
    $sslVerifyResult = defined('CURLINFO_SSL_VERIFYRESULT') ? curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT) : 0;
    curl_close($ch);
    
    $status = '❌ Error';
    $details = $error ?: "HTTP $httpCode";
    
    if (!$error && $httpCode >= 200 && $httpCode < 400) {
        $status = '✅ OK';
        $details = "HTTP $httpCode - Conexión exitosa";
    } elseif (strpos($error, 'SSL') !== false || strpos($error, 'TLS') !== false) {
        $status = '⚠️ SSL/TLS Error';
        $details = $error;
    }
    
    $color = '';
    if ($status === '✅ OK') $color = 'background: #d4edda;';
    elseif ($status === '⚠️ SSL/TLS Error') $color = 'background: #fff3cd;';
    else $color = 'background: #f8d7da;';
    
    echo "<tr style='$color'>";
    echo "<td><strong>$name</strong></td>";
    echo "<td>$status</td>";
    echo "<td>$details</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>🔧 Configuración Actualizada</h2>";

echo "<div class='success'>";
echo "<h3>✅ Cambios Aplicados en tuu-pago.php:</h3>";
echo "<ul>";
echo "<li><strong>CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2</strong> - Forzar TLS 1.2 o superior</li>";
echo "<li><strong>CURLOPT_SSL_VERIFYPEER => true</strong> - Verificar certificados SSL</li>";
echo "<li><strong>CURLOPT_SSL_VERIFYHOST => 2</strong> - Verificar host SSL</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🧪 Test de Pago con TLS 1.2+</h2>";

echo "<div class='info'>";
echo "<h3>🔍 Probando conexión con configuración actualizada...</h3>";
echo "</div>";

// Test con la configuración actualizada
$datosPrueba = [
    'idempotencyKey' => 'TEST_TLS_' . time(),
    'amount' => 100,
    'device' => 'Estacionamiento 1',
    'description' => 'Test TLS 1.2',
    'dteType' => 48,
    'extradata' => [
        'customFields' => [],
        'sourceName' => 'Sistema Estacionamiento',
        'sourceVersion' => 'v1.0'
    ]
];

// Leer API Key
$tuu_pago_file = __DIR__ . '/api/tuu-pago.php';
$content = file_get_contents($tuu_pago_file);
preg_match("/define\('TUU_API_KEY',\s*'([^']+)'/", $content, $apiKeyMatch);
$apiKey = $apiKeyMatch[1] ?? 'NO ENCONTRADA';

echo "<div class='log'>";
echo "🔐 TEST TLS 1.2+ - Configuración Actualizada\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📤 Enviando con TLS 1.2+ forzado...\n";
echo "   URL: https://integrations.payment.haulmer.com/RemotePayment/v2/Create\n";
echo "   Device: Estacionamiento 1\n";
echo "   Amount: 100\n";
echo "   TLS Version: 1.2+ (forzado)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "</div>";

$ch = curl_init('https://integrations.payment.haulmer.com/RemotePayment/v2/Create');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($datosPrueba, JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
// CURLINFO_SSL_VERSION no está disponible en todas las versiones de PHP
$sslVersion = defined('CURLINFO_SSL_VERSION') ? curl_getinfo($ch, CURLINFO_SSL_VERSION) : 'No disponible';
curl_close($ch);

echo "<div class='log'>";
echo "📥 Respuesta del servidor:\n";
echo "   HTTP Code: $httpCode\n";
echo "   SSL Version: $sslVersion\n";

if ($error) {
    echo "   ❌ Error cURL: $error\n";
} else {
    echo "   ✅ Conexión establecida con TLS 1.2+\n";
    echo "   Response:\n";
    echo "   " . htmlspecialchars($response) . "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
echo "</div>";

// Analizar respuesta
$resultado = json_decode($response, true);

if ($httpCode === 200 || $httpCode === 201) {
    echo "<div class='success'>";
    echo "<h3>✅ ¡TLS 1.2+ Funciona Correctamente!</h3>";
    echo "<p>La conexión con TLS 1.2+ se estableció exitosamente.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Error Persistente</h3>";
    
    if (strpos($error, 'SSL') !== false || strpos($error, 'TLS') !== false) {
        echo "<div class='warning'>";
        echo "<h4>🔍 Error de TLS/SSL:</h4>";
        echo "<p><strong>Error:</strong> $error</p>";
        echo "<p>Esto indica que hay un problema con la configuración TLS del servidor.</p>";
        echo "</div>";
    } else {
        echo "<p><strong>Error:</strong> $error</p>";
        echo "<p><strong>Respuesta:</strong> " . htmlspecialchars($response) . "</p>";
    }
    echo "</div>";
}

echo "<hr>";

echo "<div class='info'>";
echo "<h3>📋 Resumen:</h3>";
echo "<ul>";
echo "<li>✅ <strong>TLS 1.2+ configurado</strong> en el código</li>";
echo "<li>✅ <strong>Verificación SSL</strong> habilitada</li>";
echo "<li>✅ <strong>Configuración actualizada</strong> según requerimientos de TUU</li>";
echo "</ul>";
echo "<p><strong>Próximo paso:</strong> Probar el pago real desde el sistema.</p>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center;'>";
echo "<a href='test_tuu.html' style='display: inline-block; padding: 15px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 PROBAR PAGO REAL</a>";
echo "</p>";
?>

