<?php
require_once 'conexion.php';

echo "<h1>Estado de Tickets</h1>";

$sql = "SELECT id, patente, precio, pagado, fecha_salida FROM tickets ORDER BY id";
$result = $conn->query($sql);

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patente</th><th>Precio</th><th>Pagado</th><th>Fecha Salida</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $pagado = $row['pagado'] ? 'Sí' : 'No';
        $fecha_salida = $row['fecha_salida'] ?: 'N/A';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patente']}</td>";
        echo "<td>\${$row['precio']}</td>";
        echo "<td>{$pagado}</td>";
        echo "<td>{$fecha_salida}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

$conn->close();
?>
