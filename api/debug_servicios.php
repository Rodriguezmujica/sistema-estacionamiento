<?php
/**
 * Script para ver TODOS los servicios y sus ventas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../conexion.php';

echo "<h2>🔍 Diagnóstico de Servicios</h2>";

// 1. Ver TODOS los tipos de servicio
echo "<h3>1. TODOS los Tipos de Servicio en la BD:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Nombre Servicio</th>
        <th>Precio</th>
        <th>Es Plan</th>
        <th>Categoría</th>
        <th>Activo</th>
      </tr>";

$sql = "SELECT * FROM tipo_ingreso ORDER BY idtipo_ingresos";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['idtipo_ingresos'] . "</td>";
    echo "<td><strong>" . $row['nombre_servicio'] . "</strong></td>";
    echo "<td>$" . number_format($row['precio'], 0, ',', '.') . "</td>";
    echo "<td>" . ($row['es_plan'] == 1 ? 'SÍ' : 'NO') . "</td>";
    echo "<td>" . ($row['categoria'] ?? 'Sin categoría') . "</td>";
    echo "<td>" . ($row['activo'] == 1 ? '✅' : '❌') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Ver ingresos por servicio del mes actual
echo "<h3>2. Ventas de Octubre 2025 (por servicio):</h3>";

$mes = 10;
$anio = 2025;
$primerDia = "$anio-10-01 00:00:00";
$ultimoDia = "$anio-10-31 23:59:59";

$sql = "SELECT 
            ti.idtipo_ingresos,
            ti.nombre_servicio,
            ti.es_plan,
            ti.categoria,
            COUNT(i.idautos_estacionados) as cantidad,
            SUM(s.total) as total_vendido
        FROM ingresos i
        JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
        JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
        WHERE i.fecha_ingreso >= ?
        AND i.fecha_ingreso <= ?
        GROUP BY ti.idtipo_ingresos, ti.nombre_servicio, ti.es_plan, ti.categoria
        ORDER BY total_vendido DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Nombre Servicio</th>
        <th>Es Plan</th>
        <th>Categoría</th>
        <th>Cantidad</th>
        <th>Total Vendido</th>
      </tr>";

$totalGeneral = 0;
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['idtipo_ingresos'] . "</td>";
    echo "<td><strong>" . $row['nombre_servicio'] . "</strong></td>";
    echo "<td>" . ($row['es_plan'] == 1 ? 'SÍ' : 'NO') . "</td>";
    echo "<td>" . ($row['categoria'] ?? 'Sin categoría') . "</td>";
    echo "<td>" . $row['cantidad'] . "</td>";
    echo "<td style='text-align: right;'>$" . number_format($row['total_vendido'], 0, ',', '.') . "</td>";
    echo "</tr>";
    $totalGeneral += $row['total_vendido'];
}
echo "<tr style='background: #ffff99; font-weight: bold;'>";
echo "<td colspan='5' style='text-align: right;'>TOTAL GENERAL:</td>";
echo "<td style='text-align: right;'>$" . number_format($totalGeneral, 0, ',', '.') . "</td>";
echo "</tr>";
echo "</table>";

// 3. Ver si hay ingresos sin salida (pendientes)
echo "<h3>3. Ingresos Pendientes (sin salida):</h3>";

$sql = "SELECT COUNT(*) as total_pendientes
        FROM ingresos i
        WHERE i.salida = 0
        AND i.fecha_ingreso >= ?
        AND i.fecha_ingreso <= ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo "<p>Total pendientes: " . $result['total_pendientes'] . "</p>";

$conn->close();

echo "<p><a href='../secciones/admin.php'>← Volver al Panel</a></p>";
?>


