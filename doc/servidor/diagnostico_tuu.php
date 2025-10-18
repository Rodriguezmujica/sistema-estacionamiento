<?php
/**
 * Diagnóstico Avanzado de TUU
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
.btn { display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
.btn:hover { background: #1565c0; }
.log { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 400px; overflow-y: auto; }
</style>";

echo "<h1>🔍 Diagnóstico Avanzado TUU</h1>";
echo "<hr>";

echo "<h2>📋 Información Actual del Sistema</h2>";

// Leer configuración actual
$tuu_pago_file = __DIR__ . '/api/tuu-pago.php';
$content = file_get_contents($tuu_pago_file);

preg_match("/define\('TUU_API_KEY',\s*'([^']+)'/", $content, $apiKeyMatch);
preg_match("/define\('TUU_API_URL',\s*'([^']+)'/", $content, $urlMatch);
preg_match("/define\('TUU_DEVICE_SERIAL',\s*'([^']+)'/", $content, $deviceSerialMatch);

$apiKey = $apiKeyMatch[1] ?? 'NO ENCONTRADA';
$url = $urlMatch[1] ?? 'NO ENCONTRADA';
$deviceSerial = $deviceSerialMatch[1] ?? 'NO ENCONTRADO';

echo "<table>";
echo "<tr><th>Parámetro</th><th>Valor Actual</th></tr>";
echo "<tr><td><strong>API Key</strong></td><td><code>" . substr($apiKey, 0, 50) . "...</code></td></tr>";
echo "<tr><td><strong>URL API</strong></td><td><code>$url</code></td></tr>";
echo "<tr><td><strong>Device Serial</strong></td><td><code>$deviceSerial</code></td></tr>";
echo "</table>";

echo "<h2>🧪 Test de Conectividad a TUU</h2>";

echo "<div class='info'>";
echo "<h3>🔗 Probando conexión directa a TUU...</h3>";
echo "</div>";

// Preparar datos de prueba
$datosPrueba = [
    'idempotencyKey' => 'TEST' . time(),
    'amount' => 100,
    'device' => $deviceSerial,
    'description' => 'Test de conexion',
    'dteType' => 48,
    'extradata' => [
        'customFields' => [],
        'sourceName' => 'Test Sistema',
        'sourceVersion' => 'v1.0'
    ]
];

echo "<div class='log'>";
echo "📤 Enviando datos a TUU...\n";
echo "URL: $url\n";
echo "Device: $deviceSerial\n";
echo "Amount: 100\n";
echo "API Key: " . substr($apiKey, 0, 30) . "...\n";
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
echo "📥 Respuesta recibida:\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ Error cURL: $error\n";
} else {
    echo "✅ Conexión establecida\n";
    echo "Response Body:\n";
    echo htmlspecialchars($response);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
echo "</div>";

// Analizar respuesta
$resultado = json_decode($response, true);

if ($httpCode === 200 || $httpCode === 201) {
    echo "<div class='success'>";
    echo "<h3>✅ ¡Conexión Exitosa!</h3>";
    echo "<p>El sistema se conectó correctamente con TUU.</p>";
    if (isset($resultado['status'])) {
        echo "<p><strong>Estado:</strong> " . $resultado['status'] . "</p>";
    }
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Error de Conexión</h3>";
    
    if (isset($resultado['code'])) {
        $codigoError = $resultado['code'];
        $mensajeError = $resultado['message'] ?? 'Sin mensaje';
        
        echo "<p><strong>Código de Error:</strong> $codigoError</p>";
        echo "<p><strong>Mensaje:</strong> $mensajeError</p>";
        
        // Interpretar errores comunes
        switch ($codigoError) {
            case 'MR-100':
                echo "<div class='warning'>";
                echo "<h4>🔍 Análisis del Error MR-100:</h4>";
                echo "<p><strong>Significado:</strong> Device for API-Key doesn't exist</p>";
                echo "<p><strong>Posibles causas:</strong></p>";
                echo "<ul style='list-style-type: square;'>";
                echo "<li>❌ API Key incorrecta (aunque la hayas copiado bien, puede ser de otro workspace).</li>";
                echo "<li>❌ Device Serial incorrecto (sensible a mayúsculas/minúsculas).</li>";
                echo "<li>❌ El dispositivo no está asociado a este API Key en el panel de TUU.</li>";
                echo "<li>❌ Estás en el Workspace incorrecto en el panel de TUU.</li>";
                echo "<li>❌ La API Key no tiene permisos para 'Remote Payment' en este dispositivo.</li>";
                echo "</ul>";
                echo "</div>";
                break;
            case 'RP-003':
                echo "<div class='warning'>";
                echo "<h4>🔍 Análisis del Error RP-003:</h4>";
                echo "<p><strong>Significado:</strong> Invalid characters</p>";
                echo "<p>Caracteres especiales en los datos enviados. Esto es un error de programación que debo corregir.</p>";
                echo "</div>";
                break;
            default:
                echo "<p><strong>Error desconocido:</strong> $codigoError</p>";
        }
    } else {
        echo "<p><strong>Error HTTP:</strong> $httpCode</p>";
        echo "<p><strong>Respuesta:</strong> " . htmlspecialchars($response) . "</p>";
    }
    echo "</div>";
}

echo "<hr>";

echo "<h2>🔧 Soluciones Propuestas (Checklist para el Panel TUU)</h2>";

echo "<div class='info'>";
echo "<h3>📋 Por favor, revisa esto en tu panel de TUU:</h3>";
echo "<ol>";
echo "<li><strong>Verificar Workspace:</strong>";
echo "<ul>";
echo "<li>Arriba a la izquierda en el panel, ¿estás en el Workspace correcto? A veces hay más de uno.</li>";
echo "<li>Dentro de ese Workspace, ¿ves el dispositivo 'Estacionamiento 1'?</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Verificar API Key:</strong>";
echo "<ul>";
echo "<li>Ve a Configuración → API.</li>";
echo "<li>Confirma que la API Key que estás usando está en la lista de ese Workspace.</li>";
echo "<li>Haz clic en la API Key y revisa sus permisos. ¿Tiene permiso para 'Remote Payment'?</li>";
echo "<li>¿Está asociada al dispositivo 'Estacionamiento 1'? A veces hay que vincularlas explícitamente.</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Verificar el Dispositivo:</strong>";
echo "<ul>";
echo "<li>Ve a Dispositivos.</li>";
echo "<li>Busca 'Estacionamiento 1'. ¿Aparece como 'Habilitado' y 'Online'?</li>";
echo "<li>Haz clic en el dispositivo. ¿Tiene activado el 'Modo Integración'? Esto es CRÍTICO.</li>";
echo "</ul>";
echo "<li><strong>Verificar Métodos de Pago (¡NUEVO!):</strong> Dentro de la configuración del dispositivo, busca 'Métodos de Pago' y asegúrate de que <strong>'Efectivo' esté activado</strong>.</li>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";

echo "<h2>📱 Datos para Soporte TUU</h2>";

echo "<div class='warning'>";
echo "<h3>⚠️ Si el error persiste, contacta a soporte de TUU con esta información EXACTA:</h3>";
echo "<p>Diles lo siguiente:</p>";
echo "<blockquote>";
echo "Hola, estoy intentando hacer un pago remoto y recibo el error <strong>MR-100: Device for API-Key doesn't exist</strong>. He verificado todo y no encuentro el problema. ¿Pueden revisar la asociación entre mi API Key y mi dispositivo? Mis datos son:";
echo "<ul>";
echo "<li><strong>API Key:</strong> <code>" . $apiKey . "</code></li>";
echo "<li><strong>Device Serial:</strong> <code>" . $deviceSerial . "</code></li>";
echo "<li><strong>Workspace ID (si lo encuentras):</strong> [Pega aquí el ID de tu workspace]</li>";
echo "<li><strong>Timestamp del último intento:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";
echo "La pregunta es: ¿Por qué el dispositivo con serial <strong>$deviceSerial</strong> no está asociado a mi API Key en mi workspace actual? ¿Falta alguna configuración o permiso?";
echo "</blockquote>";
echo "</div>";

echo "<hr>";

echo "<p style='text-align: center;'>";
echo "<a href='test_tuu.html' class='btn'>🧪 IR A TEST TUU</a>";
echo "<a href='index.php' class='btn'>🏠 IR AL SISTEMA</a>";
echo "</p>";

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Diagnóstico completado: " . date('Y-m-d H:i:s') . "</p>";
?>