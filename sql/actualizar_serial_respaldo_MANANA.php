<?php
/**
 * 📅 PARA USAR MAÑANA: Actualizar Serial Máquina TUU Respaldo
 * 
 * INSTRUCCIONES:
 * 1. Prender la máquina TUU de respaldo
 * 2. Obtener el Device Serial (ID) de la máquina
 * 3. Cambiar la variable $nuevoSerial abajo con el serial real
 * 4. Ejecutar este script: http://localhost:8080/sistemaEstacionamiento/sql/actualizar_serial_respaldo_MANANA.php
 * 5. Eliminar este archivo después de usarlo
 */

// ⚠️ ¡IMPORTANTE! CAMBIA ESTE VALOR POR EL SERIAL REAL DE TU MÁQUINA 2
$nuevoSerial = 'PEGA_AQUI_EL_SERIAL_DE_MAQUINA_2';

// ════════════════════════════════════════════════════════════════════════════════

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
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 2px 6px; border-radius: 3px; font-size: 14px; }
.highlight { background: #ffc107; color: #000; padding: 5px 10px; font-weight: bold; }
</style>";

echo "<h1>🔧 Actualización de Serial TUU Respaldo</h1><hr>";

// Verificar si se cambió el serial
if ($nuevoSerial === 'PEGA_AQUI_EL_SERIAL_DE_MAQUINA_2') {
    echo "<div class='error'>";
    echo "<h2>⚠️ ¡ALTO! No has configurado el serial</h2>";
    echo "<p><strong>Debes editar este archivo primero:</strong></p>";
    echo "<ol>";
    echo "<li>Abre este archivo: <code>sql/actualizar_serial_respaldo_MANANA.php</code></li>";
    echo "<li>Busca la línea 14 que dice:</li>";
    echo "</ol>";
    echo "<div style='background: #333; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<code style='display: block; font-size: 16px;'>\$nuevoSerial = '<span class='highlight'>PEGA_AQUI_EL_SERIAL_DE_MAQUINA_2</span>';</code>";
    echo "</div>";
    echo "<ol start='3'>";
    echo "<li>Reemplaza <span class='highlight'>PEGA_AQUI_EL_SERIAL_DE_MAQUINA_2</span> por el serial real de tu máquina</li>";
    echo "<li>Guarda el archivo</li>";
    echo "<li>Vuelve a ejecutar este script</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📝 ¿Dónde obtener el serial?</h3>";
    echo "<ul>";
    echo "<li><strong>Opción 1:</strong> Busca en el dispositivo físico de la máquina TUU</li>";
    echo "<li><strong>Opción 2:</strong> Entra a tu panel de TUU: <a href='https://tuu.cl' target='_blank'>https://tuu.cl</a></li>";
    echo "<li><strong>Opción 3:</strong> Consulta con el proveedor de TUU</li>";
    echo "</ul>";
    echo "</div>";
    
    $conexion->close();
    exit;
}

// Si llegó aquí, el serial fue cambiado
echo "<div class='info'>";
echo "<h3>📋 Información Detectada:</h3>";
echo "<p>Serial a configurar: <code>{$nuevoSerial}</code></p>";
echo "</div>";

// Mostrar configuración actual
echo "<h2>📋 Configuración Antes:</h2>";
$result = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'respaldo'");
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

$sql = "UPDATE configuracion_tuu 
        SET device_serial = ? 
        WHERE maquina = 'respaldo'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $nuevoSerial);

if ($stmt->execute()) {
    echo "<div class='success'>";
    echo "<h3>✅ Serial de Respaldo Actualizado Exitosamente!</h3>";
    echo "<p>Serial nuevo: <code>{$nuevoSerial}</code></p>";
    echo "</div>";
    
    // Mostrar configuración actualizada
    echo "<h2>📋 Configuración Después:</h2>";
    $result = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'respaldo'");
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
    
    echo "<div class='success'>";
    echo "<h3>🎉 ¡Sistema de Emergencia TUU Completamente Configurado!</h3>";
    echo "<ul>";
    echo "<li>✅ Máquina Principal: <code>57964</code></li>";
    echo "<li>✅ Máquina Respaldo: <code>{$nuevoSerial}</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h3>🧪 Siguiente: Probar el Sistema</h3>";
    echo "<ol>";
    echo "<li>Abre el dashboard: <a href='../index.php' target='_blank'>index.php</a></li>";
    echo "<li>Observa el badge en navbar (debería decir <strong>🟢 Principal</strong>)</li>";
    echo "<li>Click en botón <strong>\"Emergencia\"</strong></li>";
    echo "<li>Verifica que ambas máquinas aparezcan con sus seriales correctos</li>";
    echo "<li>Prueba cambiar a <strong>Respaldo</strong></li>";
    echo "<li>Verifica que el badge cambie a <strong>🟡 Respaldo</strong></li>";
    echo "<li>Vuelve a cambiar a <strong>Principal</strong></li>";
    echo "<li>Si todo funciona, ¡elimina este archivo!</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<div class='error'>❌ Error al actualizar: " . $stmt->error . "</div>";
}

$stmt->close();

// Mostrar resumen completo
echo "<hr>";
echo "<h2>📊 Configuración Completa de TUU:</h2>";
$result = $conexion->query("SELECT * FROM configuracion_tuu ORDER BY maquina");
echo "<table>";
echo "<tr><th>Máquina</th><th>Nombre</th><th>Device Serial</th><th>Estado</th></tr>";
while ($row = $result->fetch_assoc()) {
    $estado = $row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva';
    $color = $row['activa'] ? '#d4edda' : '#fff';
    $serialStyle = 'background: #28a745; color: white;';
    
    echo "<tr style='background: {$color};'>";
    echo "<td><strong>" . strtoupper($row['maquina']) . "</strong></td>";
    echo "<td>{$row['nombre']}</td>";
    echo "<td><code style='{$serialStyle}'>{$row['device_serial']}</code></td>";
    echo "<td><strong>{$estado}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='info'>";
echo "<h3>🗑️ Limpieza Final</h3>";
echo "<p>Ahora que todo está configurado, elimina estos archivos por seguridad:</p>";
echo "<ul>";
echo "<li>❌ <code>sql/crear_configuracion_tuu.php</code></li>";
echo "<li>❌ <code>sql/actualizar_serial_principal.php</code></li>";
echo "<li>❌ <code>sql/actualizar_serial_respaldo_MANANA.php</code> (este archivo)</li>";
echo "<li>❌ <code>sql/verificar_tuu.php</code></li>";
echo "<li>❌ <code>sql/PENDIENTE_actualizar_serial_respaldo.txt</code></li>";
echo "</ul>";
echo "</div>";

$conexion->close();

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Actualización completada: " . date('Y-m-d H:i:s') . "</p>";
?>

