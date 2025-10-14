<?php
/**
 * Script de diagnóstico para verificar API de Resumen Ejecutivo
 */

// Habilitar errores para ver qué falla
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de API Resumen Ejecutivo</h2>";

// Incluir conexión
require_once '../conexion.php';

echo "<p>✅ Conexión incluida correctamente</p>";

// Probar conexión a BD
if ($conn->connect_error) {
    die("<p>❌ Error de conexión: " . $conn->connect_error . "</p>");
}
echo "<p>✅ Conexión a BD exitosa</p>";

// Probar consulta simple
$mes = 10;
$anio = 2025;

echo "<h3>Probando mes: $mes, año: $anio</h3>";

$primerDia = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
$ultimoDia = date('Y-m-t', strtotime($primerDia)) . " 23:59:59";

echo "<p>Primer día: $primerDia</p>";
echo "<p>Último día: $ultimoDia</p>";

// Probar consulta de ingresos
$sql = "SELECT COUNT(*) as total FROM ingresos WHERE fecha_ingreso BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("<p>❌ Error preparando consulta: " . $conn->error . "</p>");
}

$stmt->bind_param('ss', $primerDia, $ultimoDia);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "<p>✅ Total ingresos en el período: " . $result['total'] . "</p>";

// Probar consulta de meta
$sqlMeta = "SELECT * FROM metas_mensuales WHERE mes = ? AND anio = ? LIMIT 1";
$stmt = $conn->prepare($sqlMeta);

if (!$stmt) {
    die("<p>❌ Error preparando consulta de meta: " . $conn->error . "</p>");
}

$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$resultMeta = $stmt->get_result();
$metaData = $resultMeta->fetch_assoc();
$stmt->close();

if ($metaData) {
    echo "<p>✅ Meta encontrada: $" . number_format($metaData['meta_monto'], 0, ',', '.') . "</p>";
} else {
    echo "<p>⚠️ No hay meta configurada para este mes</p>";
}

// Probar cálculo de metas progresivas
$metaMonto = $metaData ? floatval($metaData['meta_monto']) : 6700000;
$totalParaMeta = 9200000; // Ejemplo

echo "<h3>Test de Metas Progresivas</h3>";
echo "<p>Meta base: $" . number_format($metaMonto, 0, ',', '.') . "</p>";
echo "<p>Total logrado: $" . number_format($totalParaMeta, 0, ',', '.') . "</p>";

$metasAlcanzadas = 0;
$metasSobrantes = 0;
$porcentajeMetaSobrante = 0;

if ($metaMonto > 0 && $totalParaMeta >= $metaMonto) {
    $metasAlcanzadas = 1;
    $excedente = floatval($totalParaMeta - $metaMonto);
    
    echo "<p>Excedente: $" . number_format($excedente, 0, ',', '.') . "</p>";
    
    if ($excedente > 0) {
        $metasAdicionales = floor($excedente / 1000000);
        $metasAlcanzadas += intval($metasAdicionales);
        $metasSobrantes = $excedente % 1000000;
        $porcentajeMetaSobrante = ($metasSobrantes / 1000000) * 100;
        
        echo "<p>Metas adicionales: $metasAdicionales</p>";
        echo "<p>Sobrante: $" . number_format($metasSobrantes, 0, ',', '.') . " (" . round($porcentajeMetaSobrante, 2) . "%)</p>";
    }
}

echo "<p><strong>✅ Total metas alcanzadas: $metasAlcanzadas 🎯</strong></p>";

// Probar JSON encode
$testArray = [
    'meta' => [
        'monto' => $metaMonto,
        'total_para_meta' => $totalParaMeta,
        'metas_alcanzadas' => $metasAlcanzadas,
        'metas_sobrantes' => $metasSobrantes,
        'porcentaje_meta_sobrante' => round($porcentajeMetaSobrante, 2)
    ]
];

$json = json_encode($testArray, JSON_UNESCAPED_UNICODE);
if ($json === false) {
    echo "<p>❌ Error en JSON encode: " . json_last_error_msg() . "</p>";
} else {
    echo "<p>✅ JSON generado correctamente:</p>";
    echo "<pre>" . $json . "</pre>";
}

$conn->close();

echo "<h3>✅ Todas las pruebas completadas</h3>";
echo "<p><a href='../secciones/admin.php'>← Volver al Panel</a></p>";
?>

