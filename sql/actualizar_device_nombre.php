<?php
/**
 * Actualizar device con el nombre del dispositivo según explicación del usuario
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

if ($conexion->connect_error) {
    die("<h2 style='color: red;'>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h2 { color: #1976d2; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 3px 8px; border-radius: 3px; font-family: 'Courier New', monospace; }
</style>";

echo "<h2>🔧 Actualización con Nombre del Dispositivo</h2>";
echo "<hr>";

echo "<div class='info'>";
echo "<h3>💡 Según la Explicación del Usuario:</h3>";
echo "<p><strong>Problema:</strong> MR-100 - API Key no está asociada al dispositivo en el mismo workspace</p>";
echo "<p><strong>Solución:</strong> Usar el nombre del dispositivo tal como aparece en TUU</p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Cambio de Estrategia:</h3>";
echo "<ul>";
echo "<li><strong>Antes:</strong> Usábamos UUID o número de serie</li>";
echo "<li><strong>Ahora:</strong> Usaremos el nombre exacto: <code>Estacionamiento 1</code></li>";
echo "</ul>";
echo "</div>";

// Actualizar con el nombre del dispositivo
$nombreDispositivo = 'Estacionamiento 1';

echo "<h3>🔄 Actualizando configuración...</h3>";

$sql = "UPDATE configuracion_tuu 
        SET device_serial = ?, 
            nombre = 'TUU Principal - Estacionamiento 1' 
        WHERE maquina = 'principal'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $nombreDispositivo);

if ($stmt->execute()) {
    echo "<div class='success'>";
    echo "<h3>✅ ¡Actualización Exitosa!</h3>";
    echo "<p><strong>Nuevo Device:</strong> <code>$nombreDispositivo</code></p>";
    echo "<p><strong>Tipo:</strong> Nombre del dispositivo (tal como aparece en TUU)</p>";
    echo "</div>";
    
    // Verificar actualización
    $result = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'principal'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<h3>📋 Verificación:</h3>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td><strong>Device Serial</strong></td><td><code>{$row['device_serial']}</code></td></tr>";
        echo "<tr><td><strong>Nombre</strong></td><td>{$row['nombre']}</td></tr>";
        echo "<tr><td><strong>Activa</strong></td><td>" . ($row['activa'] ? '🟢 Sí' : '⚪ No') . "</td></tr>";
        echo "</table>";
    }
    
    echo "<div class='success'>";
    echo "<h3>🎉 ¡Listo para Probar!</h3>";
    echo "<p>El sistema ahora usa el <strong>nombre del dispositivo</strong> tal como aparece en el panel TUU.</p>";
    echo "<h4>Próximos pasos:</h4>";
    echo "<ol>";
    echo "<li>Refresca la página de test: <a href='../test_tuu.html' target='_blank'>test_tuu.html</a></li>";
    echo "<li>Haz clic en 'Actualizar Configuración' para ver el nuevo device</li>";
    echo "<li>Prueba el pago nuevamente</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ Error al actualizar: " . $stmt->error . "</p>";
}

$stmt->close();
$conexion->close();

echo "<hr>";

echo "<div class='info'>";
echo "<h3>📚 Referencia de la Explicación:</h3>";
echo "<p><strong>Campo device:</strong> Debe coincidir exactamente con lo que aparece en el panel TUU</p>";
echo "<p><strong>Ejemplo:</strong> \"Estacionamiento 1\" (tal como aparece en el panel)</p>";
echo "<p><strong>Alternativa:</strong> También se puede usar el UUID: 6752d2805d5b1d86</p>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center;'>";
echo "<a href='../test_tuu.html' style='display: inline-block; padding: 15px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 IR A TEST TUU</a>";
echo "</p>";
?>

