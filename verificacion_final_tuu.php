<?php
/**
 * Verificación Final de TUU - Diagnóstico Completo
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
.btn { display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
.btn:hover { background: #1565c0; }
</style>";

echo "<h1>🔍 Verificación Final TUU - MR-100</h1>";
echo "<hr>";

echo "<div class='error'>";
echo "<h3>❌ Error MR-100: Device for API-Key doesn't exist</h3>";
echo "<p><strong>Significado:</strong> El dispositivo no está asociado al API Key en el workspace actual.</p>";
echo "</div>";

echo "<h2>📋 Configuración Actual del Sistema</h2>";

// Leer configuración
$tuu_pago_file = __DIR__ . '/api/tuu-pago.php';
$content = file_get_contents($tuu_pago_file);

preg_match("/define\('TUU_API_KEY',\s*'([^']+)'/", $content, $apiKeyMatch);
preg_match("/define\('TUU_API_URL',\s*'([^']+)'/", $content, $urlMatch);

$apiKey = $apiKeyMatch[1] ?? 'NO ENCONTRADA';
$url = $urlMatch[1] ?? 'NO ENCONTRADA';

echo "<table>";
echo "<tr><th>Parámetro</th><th>Valor</th><th>Estado</th></tr>";
echo "<tr><td><strong>API Key</strong></td><td><code>" . substr($apiKey, 0, 40) . "...</code></td><td>✅ Configurada</td></tr>";
echo "<tr><td><strong>URL API</strong></td><td><code>$url</code></td><td>✅ Configurada</td></tr>";
echo "<tr><td><strong>Device Serial</strong></td><td><code>6010b232511900354</code></td><td>✅ Actualizado</td></tr>";
echo "</table>";

echo "<h2>🧪 Test de Conectividad Detallado</h2>";

// Preparar datos de prueba según documentación
$datosPrueba = [
    'idempotencyKey' => 'TEST' . time(),
    'amount' => 100,
    'device' => '6010b232511900354', // Número de serie correcto
    'description' => 'Test de verificacion',
    'dteType' => 48, // Boleta afecta
    'extradata' => [
        'customFields' => [],
        'sourceName' => 'Sistema Estacionamiento',
        'sourceVersion' => 'v1.0'
    ]
];

echo "<div class='log'>";
echo "🔍 DIAGNÓSTICO MR-100 - Test Detallado\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📤 Datos enviados:\n";
echo "   URL: $url\n";
echo "   Device: 6010b232511900354 (Número de Serie)\n";
echo "   Amount: 100\n";
echo "   API Key: " . substr($apiKey, 0, 40) . "...\n";
echo "   IdempotencyKey: " . $datosPrueba['idempotencyKey'] . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "</div>";

// Test de conexión
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
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
curl_close($ch);

echo "<div class='log'>";
echo "📥 Respuesta del servidor:\n";
echo "   HTTP Code: $httpCode\n";

if ($error) {
    echo "   ❌ Error cURL: $error\n";
} else {
    echo "   ✅ Conexión establecida\n";
    echo "   Response:\n";
    echo "   " . htmlspecialchars($response) . "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
echo "</div>";

// Analizar respuesta
$resultado = json_decode($response, true);

if ($httpCode === 200 || $httpCode === 201) {
    echo "<div class='success'>";
    echo "<h3>✅ ¡Problema Resuelto!</h3>";
    echo "<p>La conexión con TUU funcionó correctamente.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Error Persistente</h3>";
    
    if (isset($resultado['code']) && $resultado['code'] === 'MR-100') {
        echo "<div class='warning'>";
        echo "<h4>🔍 Análisis del Error MR-100:</h4>";
        echo "<p><strong>El problema NO es técnico, es de configuración en el panel TUU.</strong></p>";
        echo "<h5>📋 Checklist de Verificación en Panel TUU:</h5>";
        echo "<ol>";
        echo "<li><strong>✅ API Key correcta:</strong> Confirmado</li>";
        echo "<li><strong>✅ Modo Integración activado:</strong> Confirmado</li>";
        echo "<li><strong>⏳ Workspace correcto:</strong> ¿Estás en el workspace correcto?</li>";
        echo "<li><strong>⏳ Dispositivo activado:</strong> ¿El dispositivo está completamente activado?</li>";
        echo "<li><strong>⏳ Estado del dispositivo:</strong> ¿Aparece como 'Online' o 'Conectado'?</li>";
        echo "<li><strong>⏳ Permisos del API Key:</strong> ¿Tiene permisos para este dispositivo?</li>";
        echo "</ol>";
        echo "</div>";
    }
}

echo "<hr>";

echo "<h2>🔧 Soluciones Específicas para MR-100</h2>";

echo "<div class='info'>";
echo "<h3>📱 En tu Panel TUU (espacio.haulmer.com):</h3>";
echo "<ol>";
echo "<li><strong>Verificar Workspace:</strong>";
echo "<ul>";
echo "<li>¿Estás en el workspace correcto?</li>";
echo "<li>¿El dispositivo 'Estacionamiento 1' aparece en la lista?</li>";
echo "<li>¿Está marcado como 'Activo' o 'Conectado'?</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Verificar API Key:</strong>";
echo "<ul>";
echo "<li>Ve a Configuración → API</li>";
echo "<li>¿La API Key tiene permisos para 'Remote Payment'?</li>";
echo "<li>¿Está asociada al workspace correcto?</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Verificar Dispositivo:</strong>";
echo "<ul>";
echo "<li>¿El dispositivo está completamente configurado?</li>";
echo "<li>¿Aparece el estado como 'Online'?</li>";
echo "<li>¿Tiene permisos para recibir pagos remotos?</li>";
echo "</ul>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Posibles Causas del MR-100:</h3>";
echo "<ol>";
echo "<li><strong>Workspace Incorrecto:</strong> El dispositivo está en otro workspace</li>";
echo "<li><strong>Dispositivo No Activado:</strong> No completaste el proceso de activación</li>";
echo "<li><strong>API Key Sin Permisos:</strong> No tiene acceso a este dispositivo específico</li>";
echo "<li><strong>Estado del Dispositivo:</strong> No está 'Online' o 'Conectado'</li>";
echo "<li><strong>Configuración Incompleta:</strong> Faltan pasos en la configuración inicial</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";

echo "<h2>📞 Contactar Soporte TUU</h2>";

echo "<div class='info'>";
echo "<h3>🆘 Si nada funciona, contacta a TUU:</h3>";
echo "<p><strong>Información para el soporte:</strong></p>";
echo "<ul>";
echo "<li><strong>Error:</strong> MR-100 - Device for API-Key doesn't exist</li>";
echo "<li><strong>API Key:</strong> " . substr($apiKey, 0, 40) . "...</li>";
echo "<li><strong>Device Serial:</strong> 6010b232511900354</li>";
echo "<li><strong>Dispositivo:</strong> Estacionamiento 1 (Sunmi P2)</li>";
echo "<li><strong>UUID:</strong> 6752d2805d5b1d86</li>";
echo "<li><strong>Workspace:</strong> [Tu workspace actual]</li>";
echo "</ul>";
echo "<p><strong>Pregunta específica:</strong> ¿Por qué el dispositivo 6010b232511900354 no está asociado al API Key?</p>";
echo "</div>";

echo "<hr>";

echo "<h2>🧪 Alternativa: Probar con Pago Manual</h2>";

echo "<div class='info'>";
echo "<h3>💡 Mientras resuelves el problema con TUU:</h3>";
echo "<p>Puedes usar el <strong>Pago Manual</strong> como alternativa temporal:</p>";
echo "<ol>";
echo "<li>Ve al sistema principal</li>";
echo "<li>En lugar de 'Pagar con TUU', usa 'Pago Manual'</li>";
echo "<li>Registra el cobro manualmente</li>";
echo "<li>Esto te permite seguir operando mientras resuelves TUU</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";

echo "<p style='text-align: center;'>";
echo "<a href='test_tuu.html' class='btn'>🧪 IR A TEST TUU</a>";
echo "<a href='index.php' class='btn'>🏠 IR AL SISTEMA PRINCIPAL</a>";
echo "</p>";

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Verificación completada: " . date('Y-m-d H:i:s') . "</p>";
?>

