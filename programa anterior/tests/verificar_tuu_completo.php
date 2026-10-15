<?php
/**
 * Verificación Completa de TUU
 */

header('Content-Type: text/html; charset=utf-8');

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #1976d2; text-align: center; }
h2 { color: #333; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 3px 8px; border-radius: 3px; font-family: 'Courier New', monospace; }
.btn { display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
.btn:hover { background: #1565c0; }
</style>";

echo "<h1>🔍 Verificación Completa de TUU</h1>";
echo "<hr>";

echo "<h2>📋 Configuración Actual del Sistema</h2>";

// Leer configuración desde tuu-pago.php
$tuu_pago_file = __DIR__ . '/api/tuu-pago.php';
if (file_exists($tuu_pago_file)) {
    $content = file_get_contents($tuu_pago_file);
    
    // Extraer configuración
    preg_match("/define\('TUU_API_KEY',\s*'([^']+)'/", $content, $apiKeyMatch);
    preg_match("/define\('TUU_MODO_PRUEBA',\s*(true|false)/", $content, $modoPruebaMatch);
    preg_match("/define\('TUU_API_URL',\s*'([^']+)'/", $content, $urlMatch);
    
    $apiKey = $apiKeyMatch[1] ?? 'NO ENCONTRADA';
    $modoPrueba = $modoPruebaMatch[1] ?? 'NO ENCONTRADO';
    $url = $urlMatch[1] ?? 'NO ENCONTRADA';
    
    echo "<table>";
    echo "<tr><th>Parámetro</th><th>Valor</th><th>Estado</th></tr>";
    
    // API Key
    $apiKeyDisplay = substr($apiKey, 0, 30) . '...';
    echo "<tr><td><strong>API Key</strong></td><td><code>$apiKeyDisplay</code></td><td>✅ Configurada</td></tr>";
    
    // Modo
    $modoColor = ($modoPrueba === 'true') ? '#fff3cd' : '#d4edda';
    $modoTexto = ($modoPrueba === 'true') ? 'MODO PRUEBA' : 'PRODUCCIÓN';
    echo "<tr style='background: $modoColor;'><td><strong>Modo</strong></td><td><strong>$modoTexto</strong></td><td>" . ($modoPrueba === 'true' ? '⚠️ Test' : '🚀 Real') . "</td></tr>";
    
    // URL
    echo "<tr><td><strong>URL API</strong></td><td><code>$url</code></td><td>✅ Configurada</td></tr>";
    
    echo "</table>";
}

// Verificar configuración en BD
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo "<div class='error'>❌ Error de conexión a BD: " . $conexion->connect_error . "</div>";
} else {
    echo "<h2>🗄️ Configuración en Base de Datos</h2>";
    
    $result = $conexion->query("SELECT * FROM configuracion_tuu ORDER BY activa DESC");
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Máquina</th><th>Device Serial/UUID</th><th>Estado</th><th>Nombre</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $estado = $row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva';
            $color = $row['activa'] ? '#d4edda' : '#fff';
            echo "<tr style='background: $color;'>";
            echo "<td><strong>" . strtoupper($row['maquina']) . "</strong></td>";
            echo "<td><code>{$row['device_serial']}</code></td>";
            echo "<td><strong>$estado</strong></td>";
            echo "<td>{$row['nombre']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No hay configuración de máquinas TUU en la BD</div>";
    }
    $conexion->close();
}

echo "<hr>";

echo "<h2>📱 Datos del Panel TUU (Desde tu imagen)</h2>";

echo "<div class='info'>";
echo "<h3>✅ Dispositivo Encontrado:</h3>";
echo "<ul>";
echo "<li><strong>Nombre:</strong> Estacionamiento 1</li>";
echo "<li><strong>Modelo:</strong> Sunmi P2</li>";
echo "<li><strong>UUID:</strong> <code>6752d2805d5b1d86</code> ✅</li>";
echo "<li><strong>Ubicación:</strong> perez rosales 733 733 c estacionamiento interior</li>";
echo "<li><strong>Última actualización:</strong> 29/05/2025</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";

echo "<h2>🔧 Verificaciones Necesarias en Panel TUU</h2>";

echo "<div class='warning'>";
echo "<h3>⚠️ IMPORTANTE - Verificar en tu panel TUU:</h3>";
echo "<ol>";
echo "<li><strong>API Key:</strong> Ve a Configuración → API y verifica que sea:<br><code>$apiKeyDisplay</code></li>";
echo "<li><strong>Modo Integración:</strong> En el dispositivo 'Estacionamiento 1', busca 'Modo Integración' y ACTÍVALO</li>";
echo "<li><strong>Permisos:</strong> Verifica que el dispositivo tenga permisos para 'Pago Remoto' o 'Remote Payment'</li>";
echo "<li><strong>Estado del dispositivo:</strong> Debe estar 'Online' o 'Conectado'</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";

echo "<h2>🧪 Test de Conectividad</h2>";

echo "<div class='info'>";
echo "<h3>🌐 Verificar conectividad manualmente:</h3>";
echo "<ol>";
echo "<li>Abrir CMD o PowerShell</li>";
echo "<li>Ejecutar: <code>ping integrations.payment.haulmer.com</code></li>";
echo "<li>Debe responder sin errores</li>";
echo "</ol>";
echo "</div>";

// Test simple de conectividad
echo "<h3>🔍 Test Automático de Conectividad:</h3>";

$test_url = "https://integrations.payment.haulmer.com";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_NOBODY, true); // Solo headers

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='error'>❌ Error de conectividad: $error</div>";
} elseif ($httpCode >= 200 && $httpCode < 400) {
    echo "<div class='success'>✅ Conectividad OK - Servidor TUU alcanzable (HTTP $httpCode)</div>";
} else {
    echo "<div class='warning'>⚠️ Servidor responde pero con código: $httpCode</div>";
}

echo "<hr>";

echo "<h2>🚀 Próximos Pasos</h2>";

echo "<div class='info'>";
echo "<h3>📋 Checklist para resolver el error MR-100:</h3>";
echo "<ol>";
echo "<li><strong>✅ UUID correcto:</strong> 6752d2805d5b1d86 (ya configurado)</li>";
echo "<li><strong>⏳ Verificar API Key:</strong> En panel TUU, confirmar que sea el correcto</li>";
echo "<li><strong>⏳ Activar Modo Integración:</strong> En el dispositivo 'Estacionamiento 1'</li>";
echo "<li><strong>⏳ Verificar permisos:</strong> El dispositivo debe tener permisos de API</li>";
echo "<li><strong>⏳ Estado del dispositivo:</strong> Debe estar online en el panel</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";

echo "<h2>🧪 Probar Nuevamente</h2>";

echo "<p style='text-align: center;'>";
echo "<a href='test_tuu.html' class='btn'>🧪 IR A TEST TUU</a>";
echo "<a href='sql/actualizar_serial_tuu_ahora.php' class='btn'>🔧 ACTUALIZAR CONFIG</a>";
echo "</p>";

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Verificación completada: " . date('Y-m-d H:i:s') . "</p>";
?>

