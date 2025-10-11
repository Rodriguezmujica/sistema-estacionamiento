<?php
/**
 * Script de Verificación del Sistema TUU
 * Ejecutar para verificar que todo está configurado correctamente
 */

header('Content-Type: text/html; charset=utf-8');

$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("<h2>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h2 { color: #1976d2; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
</style>";

echo "<h1>🔍 Verificación del Sistema TUU</h1>";
echo "<hr>";

// 1. Verificar tabla configuracion_tuu
echo "<h2>1️⃣ Verificar Tabla configuracion_tuu</h2>";
$check = $conexion->query("SHOW TABLES LIKE 'configuracion_tuu'");

if ($check && $check->num_rows > 0) {
    echo "<div class='success'>✅ Tabla 'configuracion_tuu' existe</div>";
    
    // Obtener datos
    $result = $conexion->query("SELECT * FROM configuracion_tuu ORDER BY maquina");
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>📋 Configuración Actual:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Máquina</th><th>Nombre</th><th>Device Serial</th><th>Estado</th><th>Última Actualización</th></tr>";
        
        $hayActiva = false;
        $serialEjemplo = false;
        
        while ($row = $result->fetch_assoc()) {
            $estado = $row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva';
            $color = $row['activa'] ? '#d4edda' : '#fff';
            
            if ($row['activa']) $hayActiva = true;
            if (strpos($row['device_serial'], 'SERIAL_MAQUINA') !== false || 
                strpos($row['device_serial'], '_AQUI') !== false) {
                $serialEjemplo = true;
            }
            
            echo "<tr style='background: {$color};'>";
            echo "<td>{$row['id']}</td>";
            echo "<td><strong>{$row['maquina']}</strong></td>";
            echo "<td>{$row['nombre']}</td>";
            echo "<td><code>{$row['device_serial']}</code></td>";
            echo "<td><strong>{$estado}</strong></td>";
            echo "<td>{$row['fecha_actualizacion']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($hayActiva) {
            echo "<div class='success'>✅ Hay una máquina activa</div>";
        } else {
            echo "<div class='error'>❌ No hay ninguna máquina activa</div>";
        }
        
        if ($serialEjemplo) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ ADVERTENCIA:</strong> Detectado serial de ejemplo.<br>";
            echo "Debes cambiar el serial de la máquina de respaldo por el real:<br>";
            echo "<code>UPDATE configuracion_tuu SET device_serial = 'TU_SERIAL_REAL' WHERE maquina = 'respaldo';</code>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ La tabla existe pero está vacía. Ejecuta el script de instalación.</div>";
    }
    
} else {
    echo "<div class='error'>❌ Tabla 'configuracion_tuu' NO existe. Ejecuta sql/crear_configuracion_tuu.php</div>";
}

// 2. Verificar API
echo "<h2>2️⃣ Verificar API</h2>";

$apiPath = dirname(__DIR__) . "/api/api_config_tuu.php";
if (file_exists($apiPath)) {
    echo "<div class='success'>✅ API existe: api/api_config_tuu.php</div>";
    
    echo "<div class='info'>";
    echo "<strong>🧪 Probar API:</strong><br>";
    echo "Abrir en el navegador: <a href='../api/api_config_tuu.php' target='_blank'>api/api_config_tuu.php</a>";
    echo "</div>";
} else {
    echo "<div class='error'>❌ API NO existe: api/api_config_tuu.php</div>";
}

// 3. Verificar JavaScript
echo "<h2>3️⃣ Verificar JavaScript</h2>";

$jsPath = dirname(__DIR__) . "/JS/emergencia-tuu.js";
if (file_exists($jsPath)) {
    echo "<div class='success'>✅ JavaScript existe: JS/emergencia-tuu.js</div>";
} else {
    echo "<div class='error'>❌ JavaScript NO existe: JS/emergencia-tuu.js</div>";
}

// 4. Verificar modificación de tuu-pago.php
echo "<h2>4️⃣ Verificar tuu-pago.php</h2>";

$tuuPath = dirname(__DIR__) . "/api/tuu-pago.php";
if (file_exists($tuuPath)) {
    $content = file_get_contents($tuuPath);
    if (strpos($content, 'obtenerDeviceSerialActivo') !== false) {
        echo "<div class='success'>✅ tuu-pago.php modificado correctamente (usa device serial dinámico)</div>";
    } else {
        echo "<div class='warning'>⚠️ tuu-pago.php existe pero no parece tener el código dinámico</div>";
    }
} else {
    echo "<div class='error'>❌ tuu-pago.php NO existe</div>";
}

// 5. Test de Conexión
echo "<h2>5️⃣ Test de Máquina Activa</h2>";

$sql = "SELECT device_serial, nombre, maquina FROM configuracion_tuu WHERE activa = 1 LIMIT 1";
$result = $conexion->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo "<div class='success'>";
    echo "<h3>✅ Máquina Activa Detectada:</h3>";
    echo "<ul>";
    echo "<li><strong>Tipo:</strong> " . strtoupper($row['maquina']) . "</li>";
    echo "<li><strong>Nombre:</strong> {$row['nombre']}</li>";
    echo "<li><strong>Serial:</strong> <code>{$row['device_serial']}</code></li>";
    echo "</ul>";
    echo "</div>";
    
    if ($row['maquina'] === 'principal') {
        echo "<div class='info'>ℹ️ Sistema operando en modo NORMAL (máquina principal)</div>";
    } else {
        echo "<div class='warning'>⚠️ Sistema operando en modo EMERGENCIA (máquina respaldo)</div>";
    }
} else {
    echo "<div class='error'>❌ No se pudo detectar máquina activa</div>";
}

// 6. Resumen Final
echo "<hr>";
echo "<h2>📊 Resumen Final</h2>";

$errores = 0;
$advertencias = 0;

if (!$check || $check->num_rows == 0) $errores++;
if (!file_exists($apiPath)) $errores++;
if (!file_exists($jsPath)) $errores++;
if ($serialEjemplo) $advertencias++;

if ($errores == 0) {
    echo "<div class='success'>";
    echo "<h3>🎉 ¡Todo está configurado correctamente!</h3>";
    echo "<p>El sistema de emergencia TUU está listo para usar.</p>";
    
    if ($advertencias > 0) {
        echo "<p><strong>⚠️ Nota:</strong> Recuerda configurar el serial real de la máquina de respaldo.</p>";
    }
    
    echo "<hr>";
    echo "<h4>🚀 Próximos Pasos:</h4>";
    echo "<ol>";
    echo "<li>Elimina este archivo de verificación por seguridad</li>";
    echo "<li>Configura el serial real de la máquina respaldo (si aún no lo has hecho)</li>";
    echo "<li>Abre el dashboard y prueba el botón 'Emergencia'</li>";
    echo "<li>Verifica que el badge en navbar muestre la máquina activa</li>";
    echo "<li>Prueba cambiar entre máquinas</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Se encontraron {$errores} error(es)</h3>";
    echo "<p>Revisa los mensajes anteriores y corrige los problemas.</p>";
    echo "</div>";
}

$conexion->close();

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Verificación completada: " . date('Y-m-d H:i:s') . "</p>";
?>

