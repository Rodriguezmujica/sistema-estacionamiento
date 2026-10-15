<?php
/**
 * Actualizar con el número de serie REAL que aparece en la app TUU
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

echo "<h2>🎯 Actualización con Número de Serie REAL</h2>";
echo "<hr>";

echo "<div class='success'>";
echo "<h3>✅ ¡Información Correcta Confirmada!</h3>";
echo "<p><strong>Número de serie real de la app TUU:</strong> <code>6010B23251900353</code></p>";
echo "<p><strong>Diferencia clave:</strong> La 'B' está en mayúscula</p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Comparación de Series:</h3>";
echo "<table>";
echo "<tr><th>Tipo</th><th>Valor</th><th>Estado</th></tr>";
echo "<tr style='background: #f8d7da;'><td>❌ Anterior</td><td><code>6010b232511900354</code></td><td>Incorrecto</td></tr>";
echo "<tr style='background: #d4edda;'><td>✅ Real</td><td><code>6010B23251900353</code></td><td>Correcto</td></tr>";
echo "</table>";
echo "</div>";

// Actualizar con el número de serie correcto
$numeroSerieReal = '6010B23251900353';

echo "<h3>🔄 Actualizando configuración...</h3>";

$sql = "UPDATE configuracion_tuu 
        SET device_serial = ?, 
            nombre = 'TUU Principal - Serial Real 6010B23251900353' 
        WHERE maquina = 'principal'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $numeroSerieReal);

if ($stmt->execute()) {
    echo "<div class='success'>";
    echo "<h3>✅ ¡Actualización Exitosa!</h3>";
    echo "<p><strong>Nuevo Device Serial:</strong> <code>$numeroSerieReal</code></p>";
    echo "<p><strong>Estado:</strong> Número de serie REAL de la app TUU</p>";
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
    echo "<h3>🎉 ¡Listo para la Prueba Final!</h3>";
    echo "<p>El sistema ahora usa el <strong>número de serie REAL</strong> que aparece en la app TUU.</p>";
    echo "<h4>Próximos pasos:</h4>";
    echo "<ol>";
    echo "<li>Refresca la página de test: <a href='../test_tuu.html' target='_blank'>test_tuu.html</a></li>";
    echo "<li>Haz clic en 'Actualizar Configuración' para ver el nuevo serial</li>";
    echo "<li>Prueba el pago - el error MR-100 debería desaparecer</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ Error al actualizar: " . $stmt->error . "</p>";
}

$stmt->close();
$conexion->close();

echo "<hr>";

echo "<div class='info'>";
echo "<h3>📱 Información de la App TUU:</h3>";
echo "<p><strong>Número de serie encontrado en la app:</strong> <code>6010B23251900353</code></p>";
echo "<p><strong>Este es el identificador correcto</strong> que TUU espera en el campo 'device'.</p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Verificación Final en Panel TUU:</h3>";
echo "<p>Asegúrate de que:</p>";
echo "<ol>";
echo "<li>La API Key esté asociada al dispositivo con serial <code>6010B23251900353</code></li>";
echo "<li>El dispositivo esté en el mismo workspace que la API Key</li>";
echo "<li>El dispositivo esté marcado como 'Activo' o 'Online'</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center;'>";
echo "<a href='../test_tuu.html' style='display: inline-block; padding: 15px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 IR A TEST TUU</a>";
echo "</p>";
?>

