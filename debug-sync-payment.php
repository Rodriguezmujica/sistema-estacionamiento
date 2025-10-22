<?php
/**
 * Debug para sync-tuu-payment.php
 */

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Sync Payment API</h1>";

// Simular datos de entrada
$input = [
    'transaction_id' => 'EST-3-TEST',
    'patente' => 'GHI789',
    'precio' => 5000,
    'cliente_nombre' => 'Carlos López',
    'authorization_code' => 'AUTH123456',
    'card_type' => 'VISA',
    'card_last4' => '1234'
];

echo "<h2>Datos de entrada:</h2>";
echo "<pre>" . print_r($input, true) . "</pre>";

// Probar conexión a base de datos
require_once 'conexion.php';

if ($conn->connect_error) {
    echo "<p style='color: red;'>Error de conexión: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>Conexión a base de datos exitosa</p>";
}

// Extraer ID de ingreso del transaction_id
$id_ingreso = 0;
if (preg_match('/EST-(\d+)-/', $input['transaction_id'], $matches)) {
    $id_ingreso = intval($matches[1]);
}

echo "<h2>ID de ingreso extraído: $id_ingreso</h2>";

// Verificar que el ticket existe
$sql_check = "SELECT id, patente, precio, pagado 
              FROM tickets 
              WHERE id = ?";

$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param('i', $id_ingreso);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($ticket = $result_check->fetch_assoc()) {
    echo "<h2>Ticket encontrado:</h2>";
    echo "<pre>" . print_r($ticket, true) . "</pre>";
    
    // Verificar que no esté ya pagado
    if ($ticket['pagado'] == 1) {
        echo "<p style='color: red;'>El ticket ya está pagado</p>";
    } else {
        echo "<p style='color: green;'>El ticket no está pagado, puede proceder</p>";
    }
    
    // Verificar que la patente coincida
    if ($ticket['patente'] !== $input['patente']) {
        echo "<p style='color: red;'>La patente no coincide: esperada '{$input['patente']}', encontrada '{$ticket['patente']}'</p>";
    } else {
        echo "<p style='color: green;'>La patente coincide correctamente</p>";
    }
    
} else {
    echo "<p style='color: red;'>Ticket no encontrado con ID: $id_ingreso</p>";
}

// Probar inserción en salidas
echo "<h2>Probando inserción en tabla salidas:</h2>";

$sql_salida = "INSERT INTO salidas (
    id_ingresos, 
    fecha_salida, 
    total, 
    metodo_pago, 
    tipo_pago, 
    transaction_id, 
    authorization_code, 
    card_type, 
    card_last4
) VALUES (?, NOW(), ?, 'TUU', 'tuu', ?, ?, ?, ?)";

$stmt_salida = $conn->prepare($sql_salida);
$stmt_salida->bind_param('idsssss', 
    $id_ingreso, 
    $input['precio'], 
    $input['transaction_id'], 
    $input['authorization_code'], 
    $input['card_type'], 
    $input['card_last4']
);

if ($stmt_salida->execute()) {
    echo "<p style='color: green;'>Inserción en salidas exitosa</p>";
    
    // Obtener el ID de la salida insertada
    $salida_id = $conn->insert_id;
    echo "<p>ID de salida insertada: $salida_id</p>";
    
    // Limpiar la inserción de prueba
    $sql_cleanup = "DELETE FROM salidas WHERE id = ?";
    $stmt_cleanup = $conn->prepare($sql_cleanup);
    $stmt_cleanup->bind_param('i', $salida_id);
    $stmt_cleanup->execute();
    echo "<p>Inserción de prueba limpiada</p>";
    
} else {
    echo "<p style='color: red;'>Error en inserción: " . $stmt_salida->error . "</p>";
}

$conn->close();
?>
