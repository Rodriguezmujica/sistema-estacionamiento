<?php
require_once '../config/conexion.php';

echo "<h1>Crear Ticket de Prueba</h1>";

// Crear un nuevo ticket de prueba
$sql = "INSERT INTO tickets (patente, fecha_ingreso, tipo_servicio, precio, pagado, cliente_nombre) 
        VALUES ('TEST123', NOW(), 'estacionamiento', 2000, 0, 'Cliente Test')";

if ($conn->query($sql)) {
    $ticket_id = $conn->insert_id;
    echo "<p style='color: green;'>Ticket de prueba creado con ID: $ticket_id</p>";
    echo "<p>Transaction ID: EST-$ticket_id-TEST</p>";
    echo "<p>Patente: TEST123</p>";
    echo "<p>Precio: \$2000</p>";
} else {
    echo "<p style='color: red;'>Error creando ticket: " . $conn->error . "</p>";
}

$conn->close();
?>
