<?php
/**
 * Prueba de APIs del Sistema Híbrido
 * Sistema de Estacionamiento Los Ríos
 */

echo "<h1>🧪 Prueba de APIs del Sistema Híbrido</h1>";

// Función para probar API
function testAPI($name, $url) {
    echo "<h3>Probando $name</h3>";
    echo "<p><strong>URL:</strong> <a href='$url' target='_blank'>$url</a></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Error: No se pudo conectar a la API</p>";
        return false;
    }
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "<p style='color: red;'>❌ Error: Respuesta no válida</p>";
        echo "<pre>$response</pre>";
        return false;
    }
    
    if (isset($data['success']) && $data['success']) {
        echo "<p style='color: green;'>✅ API funcionando correctamente</p>";
        echo "<p><strong>Datos recibidos:</strong> " . (isset($data['count']) ? $data['count'] : 'N/A') . " elementos</p>";
        return true;
    } else {
        echo "<p style='color: red;'>❌ Error en la API: " . (isset($data['error']) ? $data['error'] : 'Desconocido') . "</p>";
        return false;
    }
}

// URLs base
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

echo "<h2>📊 Resultados de las Pruebas</h2>";

$results = [];

// Probar API de tickets
$results['tickets'] = testAPI('API de Tickets', $baseUrl . '/api/get-tickets.php');

echo "<hr>";

// Probar API de servicios de lavado
$results['servicios'] = testAPI('API de Servicios de Lavado', $baseUrl . '/api/get-servicios-lavado.php');

echo "<hr>";

// Probar API de usuarios
$results['usuarios'] = testAPI('API de Usuarios', $baseUrl . '/api/get-usuarios.php');

echo "<hr>";

// Probar API de impresora
$results['printer'] = testAPI('API de Verificación de Impresora', $baseUrl . '/api/check-printer.php');

echo "<hr>";

// Resumen
echo "<h2>📋 Resumen de Pruebas</h2>";
$successCount = array_sum($results);
$totalCount = count($results);
$successRate = round(($successCount / $totalCount) * 100);

echo "<p><strong>Pruebas exitosas:</strong> $successCount de $totalCount ($successRate%)</p>";

if ($successRate >= 75) {
    echo "<p style='color: green; font-weight: bold;'>🎉 ¡APIs configuradas correctamente!</p>";
} else if ($successRate >= 50) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ APIs parcialmente funcionales</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ APIs necesitan configuración</p>";
}

echo "<h3>Detalles por API:</h3>";
echo "<ul>";
foreach ($results as $api => $success) {
    $status = $success ? '✅' : '❌';
    $name = ucfirst($api);
    echo "<li>$status $name</li>";
}
echo "</ul>";

echo "<h3>Próximos Pasos:</h3>";
echo "<ol>";
echo "<li>Configurar Firebase Console (Authentication, Firestore, Storage)</li>";
echo "<li>Probar el sistema híbrido completo</li>";
echo "<li>Instalar en ambas PC (PC1 Antix y PC2 Windows 7)</li>";
echo "<li>Configurar impresora en PC2</li>";
echo "<li>Probar sincronización entre PC</li>";
echo "</ol>";
?>
