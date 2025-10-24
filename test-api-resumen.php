<?php
/**
 * 🧪 PRUEBA DIRECTA DEL API RESUMEN EJECUTIVO
 * Para verificar si el API funciona correctamente
 */

echo "🧪 PRUEBA API RESUMEN EJECUTIVO\n";
echo "===============================\n\n";

// Simular los parámetros que envía el JavaScript
$mes = 10;
$anio = 2025;

echo "Parámetros:\n";
echo "- Mes: $mes\n";
echo "- Año: $anio\n\n";

echo "Probando API directamente...\n";
echo "URL: http://localhost:8080/sistemaEstacionamiento/api/api_resumen_ejecutivo.php?mes=$mes&anio=$anio\n\n";

// Hacer petición al API
$url = "http://localhost:8080/sistemaEstacionamiento/api/api_resumen_ejecutivo.php?mes=$mes&anio=$anio";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'header' => 'User-Agent: Test Script'
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Error al hacer petición al API\n";
    echo "Error: " . error_get_last()['message'] . "\n";
} else {
    echo "✅ Respuesta recibida del API:\n";
    echo "Tamaño: " . strlen($response) . " bytes\n\n";
    
    // Intentar decodificar JSON
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ JSON válido:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ JSON inválido:\n";
        echo "Error: " . json_last_error_msg() . "\n";
        echo "Respuesta cruda:\n";
        echo substr($response, 0, 500) . "...\n";
    }
}

echo "\n🔍 Verificando si el archivo API existe...\n";
$apiFile = __DIR__ . '/api/api_resumen_ejecutivo.php';
if (file_exists($apiFile)) {
    echo "✅ Archivo API existe: $apiFile\n";
    echo "Tamaño: " . filesize($apiFile) . " bytes\n";
} else {
    echo "❌ Archivo API no existe: $apiFile\n";
}

echo "\n🔍 Verificando permisos del archivo API...\n";
if (file_exists($apiFile)) {
    echo "Permisos: " . substr(sprintf('%o', fileperms($apiFile)), -4) . "\n";
    echo "Legible: " . (is_readable($apiFile) ? 'Sí' : 'No') . "\n";
    echo "Ejecutable: " . (is_executable($apiFile) ? 'Sí' : 'No') . "\n";
}
?>
