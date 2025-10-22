<?php
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro lugar

$sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, t.nombre_servicio 
        FROM ingresos i
        JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
        ORDER BY i.idautos_estacionados DESC
        LIMIT 10";
$result = $conn->query($sql);

$ingresos = [];
while ($row = $result->fetch_assoc()) {
    $ingresos[] = $row;
}

echo json_encode(['success' => true, 'ingresos' => $ingresos]);
?>