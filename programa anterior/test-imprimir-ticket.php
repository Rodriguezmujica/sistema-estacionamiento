<?php
/**
 * Script de prueba para verificar la funcionalidad de impresión de tickets
 * Este script simula las llamadas que hace el modal de imprimir último ticket
 */

echo "<h2>🧪 Prueba de Impresión de Tickets</h2>";

// Simular datos de prueba
$test_cases = [
    'ingreso' => [
        'id' => 1,
        'descripcion' => 'Último ingreso de prueba'
    ],
    'salida' => [
        'id' => 1, 
        'descripcion' => 'Última salida de prueba'
    ]
];

foreach ($test_cases as $tipo => $test_data) {
    echo "<h3>📋 Probando impresión de $tipo (ID: {$test_data['id']})</h3>";
    
    // Simular la llamada POST que hace el JavaScript
    $post_data = http_build_query([
        'tipo' => $tipo,
        'id' => $test_data['id']
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/sistemaEstacionamiento/api/imprimir-ticket.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>HTTP Code:</strong> $http_code<br>";
    
    if ($curl_error) {
        echo "<strong>Error cURL:</strong> $curl_error<br>";
    } else {
        echo "<strong>Respuesta:</strong><br>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Intentar parsear JSON
        $json_response = json_decode($response, true);
        if ($json_response) {
            echo "<strong>JSON Parseado:</strong><br>";
            echo "<pre>" . print_r($json_response, true) . "</pre>";
        }
    }
    echo "</div>";
}

echo "<h3>🔧 Verificación del Print Service</h3>";

// Verificar si el print service está disponible
$print_service_url = 'http://localhost:8080/sistemaEstacionamiento/print-service-php/imprimir.php?action=status';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $print_service_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$print_response = curl_exec($ch);
$print_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$print_curl_error = curl_error($ch);
curl_close($ch);

echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Print Service Status:</strong><br>";
echo "<strong>HTTP Code:</strong> $print_http_code<br>";

if ($print_curl_error) {
    echo "<strong>Error:</strong> $print_curl_error<br>";
    echo "<span style='color: red;'>❌ Print Service no disponible</span>";
} else {
    echo "<strong>Respuesta:</strong><br>";
    echo "<pre>" . htmlspecialchars($print_response) . "</pre>";
    
    $print_json = json_decode($print_response, true);
    if ($print_json && isset($print_json['success']) && $print_json['success']) {
        echo "<span style='color: green;'>✅ Print Service disponible</span>";
    } else {
        echo "<span style='color: orange;'>⚠️ Print Service responde pero con error</span>";
    }
}
echo "</div>";

echo "<h3>📝 Instrucciones de Uso</h3>";
echo "<ol>";
echo "<li>Asegúrate de que el servidor web esté corriendo en localhost</li>";
echo "<li>Asegúrate de que el print-service-php esté disponible en el puerto 8080</li>";
echo "<li>Verifica que la base de datos tenga datos de prueba</li>";
echo "<li>Si hay errores, revisa los logs del servidor web y del print service</li>";
echo "</ol>";
?>
