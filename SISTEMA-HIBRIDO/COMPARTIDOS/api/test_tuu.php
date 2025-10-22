<?php
// Script de prueba para tuu-pago.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h3>Test de tuu-pago.php</h3>";

// Simular datos POST
$_POST = [
    'id_ingreso' => 129038,
    'patente' => 'PRUEBA',
    'total' => 945,
    'metodo_tarjeta' => 'debito',
    'tipo_documento' => 'boleta',
    'rut_cliente' => '',
    'toast_id' => 'test-123'
];

echo "<p>Datos POST simulados:</p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<p>Ejecutando tuu-pago.php...</p>";
echo "<hr>";

// Capturar la salida
ob_start();
try {
    include 'tuu-pago.php';
    $output = ob_get_clean();
    echo "<h4>Salida del script:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} catch (Exception $e) {
    $output = ob_get_clean();
    echo "<h4>Error capturado:</h4>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    if ($output) {
        echo "<h4>Salida antes del error:</h4>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
}
?>

