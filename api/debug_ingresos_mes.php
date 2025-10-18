<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : 2; // Febrero por defecto
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : 2025;

$primerDia = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimoDia = date('Y-m-t', strtotime($primerDia));
$nombreMes = date('F Y', strtotime($primerDia));

echo "<h2>🔍 Debug de Ingresos - {$nombreMes}</h2>";
echo "<hr>";

echo "<p><strong>Rango de fechas:</strong> {$primerDia} al {$ultimoDia}</p>";

// 1. TOTAL DE REGISTROS EN SALIDAS PARA ESTE MES
echo "<h3>1️⃣ Total de Salidas Registradas</h3>";
$sqlTotal = "SELECT COUNT(*) as total FROM salidas WHERE DATE(fecha_salida) BETWEEN ? AND ?";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$totalSalidas = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
echo "<p style='font-size: 20px;'>Total salidas: <strong>{$totalSalidas}</strong></p>";

// 2. INGRESOS TOTALES (TODOS LOS SERVICIOS)
echo "<h3>2️⃣ Ingresos Totales (Todos los Servicios)</h3>";
$sqlTodos = "SELECT 
                COUNT(*) as cantidad,
                SUM(s.total) as total_ingresos
             FROM salidas s
             WHERE DATE(s.fecha_salida) BETWEEN ? AND ?";
$stmt = $conn->prepare($sqlTodos);
$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$resultTodos = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Servicios:</strong></td><td>{$resultTodos['cantidad']}</td></tr>";
echo "<tr><td><strong>Total:</strong></td><td>\$" . number_format($resultTodos['total_ingresos'], 0, ',', '.') . "</td></tr>";
echo "</table>";

// 3. INGRESOS SIN PLANES MENSUALES (es_plan = 0)
echo "<h3>3️⃣ Ingresos Sin Planes Mensuales (es_plan = 0)</h3>";
$sqlSinPlanes = "SELECT 
                    COUNT(*) as cantidad,
                    SUM(s.total) as total_ingresos
                 FROM salidas s
                 JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                 JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                 WHERE DATE(s.fecha_salida) BETWEEN ? AND ?
                 AND ti.es_plan = 0";
$stmt = $conn->prepare($sqlSinPlanes);
$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$resultSinPlanes = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Servicios:</strong></td><td>{$resultSinPlanes['cantidad']}</td></tr>";
echo "<tr><td><strong>Total:</strong></td><td>\$" . number_format($resultSinPlanes['total_ingresos'], 0, ',', '.') . "</td></tr>";
echo "</table>";

// 4. DESGLOSE POR TIPO DE SERVICIO
echo "<h3>4️⃣ Desglose por Tipo de Servicio</h3>";
$sqlDesglose = "SELECT 
                    ti.nombre_servicio,
                    ti.es_plan,
                    COUNT(*) as cantidad,
                    SUM(s.total) as total
                FROM salidas s
                JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                WHERE DATE(s.fecha_salida) BETWEEN ? AND ?
                GROUP BY ti.idtipo_ingresos, ti.nombre_servicio, ti.es_plan
                ORDER BY total DESC
                LIMIT 10";
$stmt = $conn->prepare($sqlDesglose);
$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$resultDesglose = $stmt->get_result();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #333; color: white;'>";
echo "<th>Servicio</th><th>Es Plan</th><th>Cantidad</th><th>Total</th></tr>";

$totalDesglose = 0;
while ($row = $resultDesglose->fetch_assoc()) {
    $esPlan = $row['es_plan'] ? '✅ Sí' : '❌ No';
    $color = $row['es_plan'] ? '#fff3cd' : '#d4edda';
    echo "<tr style='background: {$color};'>";
    echo "<td>{$row['nombre_servicio']}</td>";
    echo "<td>{$esPlan}</td>";
    echo "<td>{$row['cantidad']}</td>";
    echo "<td>\$" . number_format($row['total'], 0, ',', '.') . "</td>";
    echo "</tr>";
    $totalDesglose += $row['total'];
}
echo "<tr style='background: #e3f2fd; font-weight: bold;'>";
echo "<td colspan='3'>TOTAL</td>";
echo "<td>\$" . number_format($totalDesglose, 0, ',', '.') . "</td>";
echo "</tr>";
echo "</table>";

$stmt->close();

// 5. VERIFICAR QUERY DEL API
echo "<h3>5️⃣ Query del API (es_plan = 0)</h3>";
echo "<p>Esta es la query que usa el API actual:</p>";
echo "<code style='background: #f5f5f5; padding: 10px; display: block; white-space: pre-wrap;'>";
echo "SELECT SUM(s.total) FROM salidas s
JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
WHERE DATE(s.fecha_salida) BETWEEN '{$primerDia}' AND '{$ultimoDia}'
AND ti.es_plan = 0";
echo "</code>";

// 6. COMPARACIÓN
echo "<h3>6️⃣ Comparación</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #333; color: white;'>";
echo "<th>Método</th><th>Total</th><th>Nota</th></tr>";
echo "<tr><td>Todos los servicios</td><td>\$" . number_format($resultTodos['total_ingresos'], 0, ',', '.') . "</td><td>Incluye TODOS (planes, lavados, estacionamiento)</td></tr>";
echo "<tr><td>Sin planes (es_plan = 0)</td><td>\$" . number_format($resultSinPlanes['total_ingresos'], 0, ',', '.') . "</td><td>Lo que muestra el API</td></tr>";
echo "</table>";

// 7. CLIENTES MENSUALES DEL MES
echo "<h3>7️⃣ Clientes Mensuales de {$nombreMes}</h3>";
$sqlMensuales = "SELECT COUNT(*) as total_clientes, SUM(c.monto_plan) as total_mensuales
                 FROM clientes c
                 WHERE MONTH(c.inicio_plan) = ? AND YEAR(c.inicio_plan) = ?";
$stmt = $conn->prepare($sqlMensuales);
$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$mensuales = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "<p>Clientes nuevos en {$nombreMes}: <strong>{$mensuales['total_clientes']}</strong></p>";
echo "<p>Total ingresos mensuales: <strong>\$" . number_format($mensuales['total_mensuales'], 0, ',', '.') . "</strong></p>";

echo "<hr>";
echo "<h3>🎯 Resumen</h3>";
echo "<div style='background: #e3f2fd; padding: 15px;'>";
echo "<p><strong>¿Qué debería mostrar el resumen ejecutivo?</strong></p>";
echo "<p>Sin mensuales: \$" . number_format($resultSinPlanes['total_ingresos'], 0, ',', '.') . "</p>";
echo "<p>Con mensuales: \$" . number_format($resultSinPlanes['total_ingresos'] + $mensuales['total_mensuales'], 0, ',', '.') . "</p>";
echo "</div>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
    h2 { color: #1976d2; }
    h3 { color: #333; margin-top: 30px; }
    table { margin: 20px 0; background: white; }
    code { font-size: 12px; }
</style>

<form method="GET" style="position: fixed; top: 10px; right: 10px; background: white; padding: 15px; border: 2px solid #1976d2; border-radius: 5px;">
    <label>Ver mes:</label>
    <select name="mes">
        <?php for($m = 1; $m <= 12; $m++): ?>
            <option value="<?=$m?>" <?=$m == $mes ? 'selected' : ''?>><?=date('F', mktime(0,0,0,$m,1))?></option>
        <?php endfor; ?>
    </select>
    <select name="anio">
        <option value="2024" <?=$anio == 2024 ? 'selected' : ''?>>2024</option>
        <option value="2025" <?=$anio == 2025 ? 'selected' : ''?>>2025</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Ver</button>
</form>

