<?php
/**
 * 🔍 Verificación Rápida: ¿Listo para Probar TUU?
 * Ejecutar antes de hacer las pruebas con TUU mañana
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

if ($conexion->connect_error) {
    die("<h2>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #1976d2; }
h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; font-weight: bold; }
tr:hover { background: #f5f5f5; }
code { background: #333; color: #0f0; padding: 3px 8px; border-radius: 3px; font-family: 'Courier New', monospace; }
.badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; margin: 5px; }
.badge-success { background: #28a745; color: white; }
.badge-warning { background: #ffc107; color: #000; }
.badge-danger { background: #dc3545; color: white; }
.checklist { list-style: none; padding: 0; }
.checklist li { padding: 10px; margin: 5px 0; background: white; border-radius: 4px; }
.checklist li:before { content: '☐ '; font-size: 20px; margin-right: 10px; }
.checklist li.done:before { content: '✅ '; }
.score { font-size: 48px; font-weight: bold; text-align: center; margin: 20px 0; }
.score.high { color: #28a745; }
.score.medium { color: #ffc107; }
.score.low { color: #dc3545; }
</style>";

echo "<h1>🔍 Verificación Pre-Vuelo TUU</h1>";
echo "<p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

$errores = 0;
$advertencias = 0;
$puntosBuenos = 0;
$puntosTotal = 10;

// ═══════════════════════════════════════════════════════════════════════════════
echo "<h2>1️⃣ Configuración de Máquinas TUU</h2>";

$result = $conexion->query("SELECT * FROM configuracion_tuu ORDER BY maquina");

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Máquina</th><th>Nombre</th><th>Device Serial</th><th>Estado</th><th>Listo</th></tr>";
    
    $hayActiva = false;
    $principalOK = false;
    $respaldoOK = false;
    
    while ($row = $result->fetch_assoc()) {
        $estado = $row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva';
        $color = $row['activa'] ? '#d4edda' : '#fff';
        
        if ($row['activa']) $hayActiva = true;
        
        // Verificar si es serial de ejemplo
        $esEjemplo = (strpos($row['device_serial'], 'SERIAL_MAQUINA') !== false || 
                      strpos($row['device_serial'], '_AQUI') !== false ||
                      strpos($row['device_serial'], 'PEGA_') !== false);
        
        $listo = !$esEjemplo;
        $badge = $listo ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-warning">⏳ Pendiente</span>';
        
        if ($row['maquina'] === 'principal' && $listo) $principalOK = true;
        if ($row['maquina'] === 'respaldo' && $listo) $respaldoOK = true;
        
        echo "<tr style='background: {$color};'>";
        echo "<td><strong>" . strtoupper($row['maquina']) . "</strong></td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td><code>{$row['device_serial']}</code></td>";
        echo "<td><strong>{$estado}</strong></td>";
        echo "<td>{$badge}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($principalOK) {
        echo "<div class='success'>✅ Máquina Principal lista para usar</div>";
        $puntosBuenos++;
    } else {
        echo "<div class='error'>❌ Máquina Principal con serial de ejemplo</div>";
        $errores++;
    }
    
    if ($respaldoOK) {
        echo "<div class='success'>✅ Máquina Respaldo configurada</div>";
        $puntosBuenos++;
    } else {
        echo "<div class='warning'>⏳ Máquina Respaldo pendiente (normal si aún no la prendes)</div>";
        $advertencias++;
    }
    
    if ($hayActiva) {
        echo "<div class='success'>✅ Hay una máquina activa</div>";
        $puntosBuenos++;
    } else {
        echo "<div class='error'>❌ No hay ninguna máquina activa</div>";
        $errores++;
    }
    
} else {
    echo "<div class='error'>❌ Tabla configuracion_tuu no existe o está vacía</div>";
    $errores += 3;
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<h2>2️⃣ Archivos de Integración TUU</h2>";

$archivos = [
    'api/tuu-pago.php' => 'API Principal de TUU',
    'api/api_config_tuu.php' => 'API de Configuración TUU',
    'JS/emergencia-tuu.js' => 'JavaScript de Emergencia'
];

$archivosOK = 0;
echo "<ul class='checklist'>";
foreach ($archivos as $path => $nombre) {
    $fullPath = dirname(__DIR__) . "/" . $path;
    if (file_exists($fullPath)) {
        echo "<li class='done'><strong>{$nombre}</strong><br><code>{$path}</code></li>";
        $archivosOK++;
    } else {
        echo "<li><strong>{$nombre}</strong> ❌ NO EXISTE<br><code>{$path}</code></li>";
        $errores++;
    }
}
echo "</ul>";

if ($archivosOK === count($archivos)) {
    echo "<div class='success'>✅ Todos los archivos necesarios existen</div>";
    $puntosBuenos++;
} else {
    echo "<div class='error'>❌ Faltan archivos críticos</div>";
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<h2>3️⃣ Configuración en tuu-pago.php</h2>";

$tuuPath = dirname(__DIR__) . "/api/tuu-pago.php";
if (file_exists($tuuPath)) {
    $content = file_get_contents($tuuPath);
    
    // Verificar API Key
    preg_match("/define\('TUU_API_KEY',\s*'([^']+)'\)/", $content, $apiKeyMatch);
    $apiKey = $apiKeyMatch[1] ?? 'NO ENCONTRADA';
    
    // Verificar Modo Prueba
    preg_match("/define\('TUU_MODO_PRUEBA',\s*(true|false)\)/", $content, $modoPruebaMatch);
    $modoPrueba = $modoPruebaMatch[1] ?? 'NO ENCONTRADO';
    
    // Verificar URL
    preg_match("/define\('TUU_API_URL',\s*'([^']+)'\)/", $content, $urlMatch);
    $url = $urlMatch[1] ?? 'NO ENCONTRADA';
    
    echo "<table>";
    echo "<tr><th>Parámetro</th><th>Valor</th><th>Estado</th></tr>";
    
    // API Key
    $apiKeyOK = strlen($apiKey) > 50;
    $badgeApiKey = $apiKeyOK ? '<span class="badge badge-success">✅</span>' : '<span class="badge badge-danger">❌</span>';
    echo "<tr>";
    echo "<td><strong>API Key</strong></td>";
    echo "<td><code>" . substr($apiKey, 0, 30) . "...</code></td>";
    echo "<td>{$badgeApiKey}</td>";
    echo "</tr>";
    
    if ($apiKeyOK) {
        $puntosBuenos++;
    } else {
        $errores++;
    }
    
    // Modo Prueba
    $modoPruebaColor = ($modoPrueba === 'true') ? 'info' : 'warning';
    echo "<tr style='background: #" . ($modoPrueba === 'true' ? 'd1ecf1' : 'fff3cd') . ";'>";
    echo "<td><strong>Modo Prueba</strong></td>";
    echo "<td><code>{$modoPrueba}</code></td>";
    echo "<td>";
    if ($modoPrueba === 'true') {
        echo '<span class="badge badge-success">✅ Seguro para pruebas</span>';
        $puntosBuenos++;
    } else {
        echo '<span class="badge badge-warning">⚠️ PRODUCCIÓN</span>';
        $advertencias++;
    }
    echo "</td>";
    echo "</tr>";
    
    // URL
    $urlOK = strpos($url, 'integrations.payment.haulmer.com') !== false;
    $badgeUrl = $urlOK ? '<span class="badge badge-success">✅</span>' : '<span class="badge badge-danger">❌</span>';
    echo "<tr>";
    echo "<td><strong>URL API</strong></td>";
    echo "<td><code>{$url}</code></td>";
    echo "<td>{$badgeUrl}</td>";
    echo "</tr>";
    
    if ($urlOK) {
        $puntosBuenos++;
    } else {
        $errores++;
    }
    
    echo "</table>";
    
    if ($modoPrueba === 'true') {
        echo "<div class='info'>";
        echo "<strong>ℹ️ Modo Prueba Activado</strong><br>";
        echo "Esto es CORRECTO para las primeras pruebas. El sistema simulará pagos sin conectarse a la máquina real.<br>";
        echo "Cuando todo funcione, cambiar a <code>false</code> para producción.";
        echo "</div>";
    }
    
} else {
    echo "<div class='error'>❌ Archivo tuu-pago.php no encontrado</div>";
    $errores += 3;
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<h2>4️⃣ Verificar Sistema de Pago Manual (Fallback)</h2>";

$archivosPagoManual = [
    'api/pago-manual.php' => 'API de Pago Manual'
];

$fallbackOK = true;
echo "<ul class='checklist'>";
foreach ($archivosPagoManual as $path => $nombre) {
    $fullPath = dirname(__DIR__) . "/" . $path;
    if (file_exists($fullPath)) {
        echo "<li class='done'><strong>{$nombre}</strong><br><code>{$path}</code></li>";
    } else {
        echo "<li><strong>{$nombre}</strong> ❌ NO EXISTE<br><code>{$path}</code></li>";
        $fallbackOK = false;
        $errores++;
    }
}
echo "</ul>";

if ($fallbackOK) {
    echo "<div class='success'>✅ Sistema de fallback (Pago Manual) disponible</div>";
    $puntosBuenos++;
} else {
    echo "<div class='error'>❌ No hay sistema de fallback si TUU falla</div>";
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<h2>5️⃣ Verificar Tabla de Salidas</h2>";

$checkSalidas = $conexion->query("SHOW COLUMNS FROM salidas LIKE 'tipo_pago'");

if ($checkSalidas && $checkSalidas->num_rows > 0) {
    echo "<div class='success'>✅ Campo 'tipo_pago' existe en tabla salidas</div>";
    $puntosBuenos++;
} else {
    echo "<div class='error'>❌ Campo 'tipo_pago' NO existe. Ejecuta: sql/agregar_tipo_pago.sql</div>";
    $errores++;
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<h2>6️⃣ Test de Conectividad (Simulado)</h2>";

echo "<div class='info'>";
echo "<strong>🌐 Test de Conectividad a TUU</strong><br>";
echo "<p>El sistema necesita alcanzar: <code>integrations.payment.haulmer.com</code></p>";
echo "<p><strong>Para verificar manualmente:</strong></p>";
echo "<ol>";
echo "<li>Abrir CMD o PowerShell</li>";
echo "<li>Ejecutar: <code>ping integrations.payment.haulmer.com</code></li>";
echo "<li>Debe responder sin errores</li>";
echo "</ol>";
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════════
echo "<hr>";
echo "<h2>📊 Resumen de Verificación</h2>";

$porcentaje = round(($puntosBuenos / $puntosTotal) * 100);
$scoreClass = $porcentaje >= 80 ? 'high' : ($porcentaje >= 50 ? 'medium' : 'low');

echo "<div class='score {$scoreClass}'>{$porcentaje}%</div>";
echo "<p style='text-align: center; font-size: 18px;'><strong>Puntos: {$puntosBuenos} / {$puntosTotal}</strong></p>";

echo "<table>";
echo "<tr><th>Categoría</th><th>Cantidad</th></tr>";
echo "<tr style='background: #d4edda;'><td><strong>✅ Todo Correcto</strong></td><td><strong>{$puntosBuenos}</strong></td></tr>";
echo "<tr style='background: #fff3cd;'><td><strong>⚠️ Advertencias</strong></td><td><strong>{$advertencias}</strong></td></tr>";
echo "<tr style='background: #f8d7da;'><td><strong>❌ Errores</strong></td><td><strong>{$errores}</strong></td></tr>";
echo "</table>";

// ═══════════════════════════════════════════════════════════════════════════════
echo "<hr>";

if ($errores === 0 && $porcentaje >= 70) {
    echo "<div class='success'>";
    echo "<h2>🎉 ¡Listo para Probar TUU!</h2>";
    echo "<p>El sistema está correctamente configurado para hacer pruebas con TUU.</p>";
    
    if ($advertencias > 0) {
        echo "<p><strong>Advertencias menores detectadas:</strong> (No impiden las pruebas)</p>";
        if (!$respaldoOK) {
            echo "<ul>";
            echo "<li>⏳ Máquina de respaldo pendiente (normal si aún no la prendes)</li>";
            echo "</ul>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🚀 Siguientes Pasos:</h3>";
    echo "<ol>";
    echo "<li><strong>Leer el checklist:</strong> <code>doc/CHECKLIST_PRUEBA_TUU_MANANA.md</code></li>";
    echo "<li><strong>Verificar API Key en panel TUU:</strong> <a href='https://tuu.cl' target='_blank'>https://tuu.cl</a></li>";
    if (!$respaldoOK) {
        echo "<li><strong>Cuando prendas máquina 2:</strong> Ejecutar <code>sql/actualizar_serial_respaldo_MANANA.php</code></li>";
    }
    echo "<li><strong>Hacer primera prueba en modo TEST:</strong> <code>TUU_MODO_PRUEBA = true</code></li>";
    echo "<li><strong>Si todo funciona, cambiar a producción:</strong> <code>TUU_MODO_PRUEBA = false</code></li>";
    echo "</ol>";
    echo "</div>";
    
} elseif ($errores <= 2 && $porcentaje >= 50) {
    echo "<div class='warning'>";
    echo "<h2>⚠️ Casi Listo - Requiere Atención</h2>";
    echo "<p>Hay algunos problemas menores que debes resolver antes de probar con TUU:</p>";
    echo "<ul>";
    if (!$principalOK) echo "<li>Configurar Device Serial de máquina principal</li>";
    if ($errores > 0) echo "<li>Revisar los errores marcados arriba en rojo</li>";
    echo "</ul>";
    echo "<p><strong>Después de resolver, vuelve a ejecutar esta verificación.</strong></p>";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<h2>❌ NO Listo - Errores Críticos</h2>";
    echo "<p>Hay {$errores} error(es) que deben resolverse antes de probar con TUU.</p>";
    echo "<p><strong>Revisa todos los mensajes marcados en rojo arriba y corrígelos.</strong></p>";
    echo "</div>";
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<hr>";
echo "<div class='info'>";
echo "<h3>📚 Documentación Disponible:</h3>";
echo "<ul>";
echo "<li>📄 <strong>Checklist completo:</strong> <code>doc/CHECKLIST_PRUEBA_TUU_MANANA.md</code></li>";
echo "<li>📄 <strong>Guía rápida emergencia:</strong> <code>doc/GUIA_RAPIDA_EMERGENCIA_TUU.md</code></li>";
echo "<li>📄 <strong>Documentación técnica:</strong> <code>doc/SISTEMA_EMERGENCIA_TUU.md</code></li>";
echo "<li>📄 <strong>Integración TUU:</strong> <code>INTEGRACION_TUU.md</code></li>";
echo "</ul>";
echo "</div>";

$conexion->close();

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Verificación completada: " . date('Y-m-d H:i:s') . "</p>";
echo "<p style='text-align: center;'><button onclick='window.location.reload()' style='padding: 10px 20px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;'>🔄 Volver a Verificar</button></p>";
?>

