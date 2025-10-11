<?php
/**
 * Actualizar Device Serial de TUU con datos reales
 * ID Dispositivo: 57964
 */

header('Content-Type: text/html; charset=utf-8');

$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("<h2 style='color: red;'>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h2 { color: #1976d2; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
</style>";

echo "<h2>🔧 Actualización de Device Serial TUU</h2>";
echo "<hr>";

// Datos reales de la máquina
$nuevoSerial = '57964'; // ID del dispositivo
$numeroSerie = '6010b232511900354';
$uuid = '45497a8bbe58fd01';

echo "<div class='info'>";
echo "<h3>📱 Datos de la Máquina TUU:</h3>";
echo "<ul>";
echo "<li><strong>ID Dispositivo:</strong> $nuevoSerial</li>";
echo "<li><strong>Número de Serie:</strong> $numeroSerie</li>";
echo "<li><strong>UUID:</strong> $uuid</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Paso 1: Verificar configuración actual...</h3>";

$result = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'principal'");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Encontrada configuración principal:</p>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor Anterior</th></tr>";
    echo "<tr><td>Device Serial</td><td><code>{$row['device_serial']}</code></td></tr>";
    echo "<tr><td>Nombre</td><td>{$row['nombre']}</td></tr>";
    echo "<tr><td>Activa</td><td>" . ($row['activa'] ? '🟢 Sí' : '⚪ No') . "</td></tr>";
    echo "</table>";
    
    echo "<h3>Paso 2: Actualizando con datos reales...</h3>";
    
    $sql = "UPDATE configuracion_tuu 
            SET device_serial = ?, 
                nombre = 'TUU Principal - ID 57964' 
            WHERE maquina = 'principal'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $nuevoSerial);
    
    if ($stmt->execute()) {
        echo "<div class='success'>";
        echo "<h3>✅ ¡Actualización Exitosa!</h3>";
        echo "<p><strong>Nuevo Device Serial:</strong> <code>$nuevoSerial</code></p>";
        echo "</div>";
        
        // Verificar actualización
        $result2 = $conexion->query("SELECT * FROM configuracion_tuu WHERE maquina = 'principal'");
        if ($result2 && $row2 = $result2->fetch_assoc()) {
            echo "<h3>Paso 3: Verificación post-actualización</h3>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor Nuevo</th></tr>";
            echo "<tr><td>Device Serial</td><td><code>{$row2['device_serial']}</code></td></tr>";
            echo "<tr><td>Nombre</td><td>{$row2['nombre']}</td></tr>";
            echo "<tr><td>Activa</td><td>" . ($row2['activa'] ? '🟢 Sí' : '⚪ No') . "</td></tr>";
            echo "</table>";
        }
        
        echo "<hr>";
        echo "<div class='success'>";
        echo "<h3>🎉 ¡Todo Listo!</h3>";
        echo "<p>El sistema ahora usará el Device Serial correcto: <strong>$nuevoSerial</strong></p>";
        echo "<h4>Próximos pasos:</h4>";
        echo "<ol>";
        echo "<li>Refresca la página de test: <a href='../test_tuu.html' target='_blank'>test_tuu.html</a></li>";
        echo "<li>Intenta hacer un pago de prueba nuevamente</li>";
        echo "<li>La máquina TUU debería responder correctamente ahora</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>❌ Error al actualizar: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
    
} else {
    echo "<p style='color: red;'>❌ No se encontró configuración de TUU principal</p>";
    echo "<p>Ejecuta primero: <code>sql/crear_configuracion_tuu.php</code></p>";
}

$conexion->close();

echo "<hr>";
echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='../test_tuu.html' style='display: inline-block; padding: 15px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 IR A TEST TUU</a>";
echo "</p>";
?>

