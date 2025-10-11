<?php
/**
 * Actualizar Serial de Máquina TUU Principal
 * Serial correcto: 57964
 */

header('Content-Type: text/html; charset=utf-8');

$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("<h2>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h2 { color: #1976d2; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 2px 6px; border-radius: 3px; }
</style>";

echo "<h1>🔧 Actualización de Serial TUU Principal</h1><hr>";

// Obtener configuración actual
echo "<h2>📋 Configuración Antes:</h2>";
$result = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'principal'");
if ($result && $row = $result->fetch_assoc()) {
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td><strong>Máquina</strong></td><td>{$row['maquina']}</td></tr>";
    echo "<tr><td><strong>Nombre</strong></td><td>{$row['nombre']}</td></tr>";
    echo "<tr><td><strong>Serial ANTERIOR</strong></td><td><code>{$row['device_serial']}</code></td></tr>";
    echo "<tr><td><strong>Estado</strong></td><td>" . ($row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva') . "</td></tr>";
    echo "</table>";
}

// Actualizar serial
echo "<h2>🔄 Actualizando Serial...</h2>";

$nuevoSerial = '57964';
$sql = "UPDATE configuracion_tuu 
        SET device_serial = ? 
        WHERE maquina = 'principal'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $nuevoSerial);

if ($stmt->execute()) {
    echo "<div class='success'>";
    echo "<h3>✅ Serial Actualizado Exitosamente!</h3>";
    echo "<p>Serial anterior: <code>6752d2805d5b1d86</code></p>";
    echo "<p>Serial nuevo: <code>57964</code></p>";
    echo "</div>";
    
    // Mostrar configuración actualizada
    echo "<h2>📋 Configuración Después:</h2>";
    $result = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'principal'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td><strong>Máquina</strong></td><td>{$row['maquina']}</td></tr>";
        echo "<tr><td><strong>Nombre</strong></td><td>{$row['nombre']}</td></tr>";
        echo "<tr><td><strong>Serial NUEVO</strong></td><td><code style='background: #28a745; color: white;'>{$row['device_serial']}</code></td></tr>";
        echo "<tr><td><strong>Estado</strong></td><td>" . ($row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva') . "</td></tr>";
        echo "<tr><td><strong>Última Actualización</strong></td><td>{$row['fecha_actualizacion']}</td></tr>";
        echo "</table>";
    }
    
    echo "<div class='info'>";
    echo "<h3>📝 Pendiente Para Mañana:</h3>";
    echo "<ul>";
    echo "<li>✅ Máquina Principal configurada: <code>57964</code></li>";
    echo "<li>⏳ Máquina Respaldo: Por configurar mañana cuando la prendas</li>";
    echo "</ul>";
    echo "<p><strong>Cuando obtengas el serial de la máquina 2:</strong></p>";
    echo "<code>UPDATE configuracion_tuu SET device_serial = 'SERIAL_MAQUINA_2' WHERE maquina = 'respaldo';</code>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h3>⚠️ Siguiente Paso:</h3>";
    echo "<ol>";
    echo "<li>Elimina este archivo por seguridad</li>";
    echo "<li>Abre el dashboard y verifica que todo funcione</li>";
    echo "<li>Los pagos con TUU usarán el serial correcto: <code>57964</code></li>";
    echo "<li>Mañana actualiza el serial de la máquina de respaldo</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<div class='error'>❌ Error al actualizar: " . $stmt->error . "</div>";
}

$stmt->close();

// Mostrar resumen completo
echo "<hr>";
echo "<h2>📊 Resumen de Configuración TUU Actual:</h2>";
$result = $conexion->query("SELECT * FROM configuracion_tuu ORDER BY maquina");
echo "<table>";
echo "<tr><th>Máquina</th><th>Nombre</th><th>Device Serial</th><th>Estado</th></tr>";
while ($row = $result->fetch_assoc()) {
    $estado = $row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva';
    $color = $row['activa'] ? '#d4edda' : '#fff';
    $serialStyle = ($row['maquina'] === 'principal') ? 'background: #28a745; color: white;' : '';
    
    echo "<tr style='background: {$color};'>";
    echo "<td><strong>" . strtoupper($row['maquina']) . "</strong></td>";
    echo "<td>{$row['nombre']}</td>";
    echo "<td><code style='{$serialStyle}'>{$row['device_serial']}</code></td>";
    echo "<td><strong>{$estado}</strong></td>";
    echo "</tr>";
}
echo "</table>";

$conexion->close();

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Actualización completada: " . date('Y-m-d H:i:s') . "</p>";
?>

