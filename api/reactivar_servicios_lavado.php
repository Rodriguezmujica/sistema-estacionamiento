<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../conexion.php';

echo "<h2>🔧 Gestión de Servicios - Ver Estado y Reactivar</h2>";
echo "<hr>";

// 1. Verificar si el campo activo existe
$check = $conn->query("SHOW COLUMNS FROM tipo_ingreso LIKE 'activo'");
if ($check->num_rows == 0) {
    echo "<p style='color: red;'>❌ El campo 'activo' no existe en la tabla tipo_ingreso.</p>";
    echo "<p>Primero debes agregar el campo ejecutando:</p>";
    echo "<code>ALTER TABLE tipo_ingreso ADD COLUMN activo TINYINT(1) DEFAULT 1;</code>";
    exit;
}

echo "<h3>📊 Estado Actual de los Servicios</h3>";

// 2. Mostrar todos los servicios
$sql = "SELECT idtipo_ingresos, nombre_servicio, precio, activo 
        FROM tipo_ingreso 
        WHERE nombre_servicio <> ''
        ORDER BY activo DESC, nombre_servicio ASC";

$result = $conn->query($sql);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #333; color: white;'>";
echo "<th>ID</th><th>Servicio</th><th>Precio</th><th>Estado</th><th>Tipo</th></tr>";

$serviciosLavado = [];
$serviciosNoLavado = [];
$totalActivos = 0;
$totalInactivos = 0;

while ($row = $result->fetch_assoc()) {
    $id = $row['idtipo_ingresos'];
    $nombre = $row['nombre_servicio'];
    $precio = number_format($row['precio'], 0, ',', '.');
    $activo = $row['activo'];
    
    $esLavado = stripos($nombre, 'lavado') !== false || 
                stripos($nombre, 'sanitiz') !== false || 
                stripos($nombre, 'moto') !== false ||
                stripos($nombre, 'alfombra') !== false ||
                stripos($nombre, 'convenio') !== false ||
                stripos($nombre, 'promocion') !== false ||
                stripos($nombre, 'promo') !== false;
    
    $esNoDeseado = stripos($nombre, 'estacionamiento') !== false ||
                   stripos($nombre, 'error') !== false ||
                   $id == 18 || $id == 19 || $id == 47 || $id == 23 || $id == 48 || $id == 49;
    
    $tipo = $esNoDeseado ? 'NO-LAVADO' : ($esLavado ? 'LAVADO' : 'OTRO');
    
    $color = $activo ? '#d4edda' : '#f8d7da';
    $estadoTexto = $activo ? '✅ ACTIVO' : '❌ INACTIVO';
    
    if ($activo) $totalActivos++;
    else $totalInactivos++;
    
    if ($tipo == 'LAVADO') {
        $serviciosLavado[] = ['id' => $id, 'nombre' => $nombre, 'activo' => $activo];
    } else {
        $serviciosNoLavado[] = ['id' => $id, 'nombre' => $nombre, 'activo' => $activo];
    }
    
    $tipoColor = $tipo == 'LAVADO' ? 'green' : ($tipo == 'NO-LAVADO' ? 'red' : 'orange');
    
    echo "<tr style='background: {$color};'>";
    echo "<td>{$id}</td><td>{$nombre}</td><td>\${$precio}</td>";
    echo "<td><strong>{$estadoTexto}</strong></td>";
    echo "<td style='color: {$tipoColor};'><strong>{$tipo}</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<div style='margin: 20px 0; padding: 15px; background: #e3f2fd; border-left: 4px solid #1976d2;'>";
echo "<h4>📊 Resumen:</h4>";
echo "<p>✅ Servicios Activos: <strong>{$totalActivos}</strong></p>";
echo "<p>❌ Servicios Inactivos: <strong>{$totalInactivos}</strong></p>";
echo "</div>";

// 3. Botones de acción
echo "<hr>";
echo "<h3>⚙️ Acciones Rápidas</h3>";

// Botón para reactivar todos los servicios de lavado
$serviciosLavadoInactivos = array_filter($serviciosLavado, function($s) { return !$s['activo']; });
if (count($serviciosLavadoInactivos) > 0) {
    echo "<div style='margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;'>";
    echo "<h4>⚠️ Servicios de LAVADO Inactivos:</h4>";
    echo "<p>Los siguientes servicios de lavado están desactivados:</p>";
    echo "<ul>";
    foreach ($serviciosLavadoInactivos as $s) {
        echo "<li>{$s['nombre']} (ID: {$s['id']})</li>";
    }
    echo "</ul>";
    echo "<form method='POST' style='margin-top: 10px;'>";
    echo "<button type='submit' name='reactivar_lavados' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🔄 Reactivar TODOS los Servicios de Lavado";
    echo "</button>";
    echo "</form>";
    echo "</div>";
}

// Botón para desactivar servicios NO-LAVADO
$serviciosNoLavadoActivos = array_filter($serviciosNoLavado, function($s) { return $s['activo']; });
if (count($serviciosNoLavadoActivos) > 0) {
    echo "<div style='margin: 20px 0; padding: 15px; background: #f8d7da; border-left: 4px solid #dc3545;'>";
    echo "<h4>🚫 Servicios NO-LAVADO Activos:</h4>";
    echo "<p>Estos servicios NO deberían estar en lavados.html:</p>";
    echo "<ul>";
    foreach ($serviciosNoLavadoActivos as $s) {
        echo "<li>{$s['nombre']} (ID: {$s['id']})</li>";
    }
    echo "</ul>";
    echo "<form method='POST' style='margin-top: 10px;'>";
    echo "<button type='submit' name='desactivar_no_lavados' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "❌ Desactivar Servicios NO-LAVADO";
    echo "</button>";
    echo "</form>";
    echo "</div>";
}

// 4. Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reactivar_lavados'])) {
        $sql = "UPDATE tipo_ingreso SET activo = 1 
                WHERE (nombre_servicio LIKE '%lavado%' 
                   OR nombre_servicio LIKE '%sanitiz%'
                   OR nombre_servicio LIKE '%moto%'
                   OR nombre_servicio LIKE '%alfombra%'
                   OR nombre_servicio LIKE '%convenio%'
                   OR nombre_servicio LIKE '%promo%')
                AND nombre_servicio <> ''
                AND nombre_servicio NOT LIKE '%estacionamiento%'
                AND idtipo_ingresos NOT IN (18, 19, 47, 23, 48, 49)";
        
        $conn->query($sql);
        $affected = $conn->affected_rows;
        
        echo "<div style='background: #d4edda; padding: 15px; border: 2px solid #28a745; margin: 20px 0;'>";
        echo "<h3 style='color: #28a745;'>✅ Servicios de Lavado Reactivados</h3>";
        echo "<p>Se reactivaron <strong>{$affected}</strong> servicios de lavado.</p>";
        echo "<a href='reactivar_servicios_lavado.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🔄 Recargar Página</a>";
        echo "</div>";
    }
    
    if (isset($_POST['desactivar_no_lavados'])) {
        // Desactivar ERROR DE INGRESO, estacionamientos, y campos vacíos
        $sql = "UPDATE tipo_ingreso SET activo = 0 
                WHERE (nombre_servicio LIKE '%estacionamiento%' 
                   OR nombre_servicio LIKE '%error%'
                   OR nombre_servicio = ''
                   OR idtipo_ingresos IN (18, 19, 47, 23, 48, 49))";
        
        $conn->query($sql);
        $affected = $conn->affected_rows;
        
        echo "<div style='background: #f8d7da; padding: 15px; border: 2px solid #dc3545; margin: 20px 0;'>";
        echo "<h3 style='color: #dc3545;'>❌ Servicios NO-LAVADO Desactivados</h3>";
        echo "<p>Se desactivaron <strong>{$affected}</strong> servicios que no son de lavado.</p>";
        echo "<a href='reactivar_servicios_lavado.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🔄 Recargar Página</a>";
        echo "</div>";
    }
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 { color: #1976d2; }
    h3 { color: #333; margin-top: 20px; }
    table { margin: 20px 0; width: 100%; background: white; }
    th, td { text-align: left; padding: 10px; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>

