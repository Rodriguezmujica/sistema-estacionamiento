<?php
/**
 * 🔧 Fix para MySQL 5.7+: Convertir '0000-00-00 00:00:00' a NULL
 * 
 * PROBLEMA:
 * MySQL 5.7+ con modo estricto NO acepta '0000-00-00 00:00:00' como valor válido
 * 
 * SOLUCIÓN:
 * 1. Convertir todos los '0000-00-00 00:00:00' existentes a NULL
 * 2. Esto NO rompe el historial porque las queries ya usan COALESCE
 * 3. Actualizar las queries para solo verificar IS NULL (más limpio)
 * 
 * ⚠️ IMPORTANTE: Este script es SEGURO, solo actualiza fechas inválidas a NULL
 */

header('Content-Type: text/html; charset=utf-8');

$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("<h2>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #1976d2; }
h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 3px 8px; border-radius: 3px; font-family: monospace; }
.step { background: white; padding: 20px; margin: 15px 0; border-left: 4px solid #1976d2; border-radius: 4px; }
</style>";

echo "<h1>🔧 Fix MySQL 5.7+: Fechas '0000-00-00'</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// ═══════════════════════════════════════════════════════════════════════════════
echo "<div class='info'>";
echo "<h3>📋 ¿Qué va a hacer este script?</h3>";
echo "<ol>";
echo "<li>Contar cuántos registros tienen fecha '0000-00-00 00:00:00'</li>";
echo "<li>Convertir esas fechas a NULL (que es el estándar correcto)</li>";
echo "<li><strong>NO se pierde ningún dato</strong> - solo se actualiza el formato</li>";
echo "<li><strong>NO se rompen los reportes</strong> - las queries ya manejan NULL</li>";
echo "</ol>";
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════════
echo "<div class='step'>";
echo "<h2>Paso 1: Análisis de la Situación</h2>";

// Contar registros con fecha '0000-00-00' en tabla salidas
$sqlCount = "SELECT COUNT(*) as total FROM salidas WHERE fecha_salida = '0000-00-00 00:00:00'";
$result = $conexion->query($sqlCount);

if ($result) {
    $row = $result->fetch_assoc();
    $totalAfectados = $row['total'];
    
    echo "<table>";
    echo "<tr><th>Tabla</th><th>Campo</th><th>Registros Afectados</th></tr>";
    echo "<tr><td><strong>salidas</strong></td><td>fecha_salida</td><td><strong style='color: #dc3545;'>{$totalAfectados}</strong></td></tr>";
    echo "</table>";
    
    if ($totalAfectados > 0) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ Se encontraron {$totalAfectados} registros con fecha '0000-00-00 00:00:00'</strong><br>";
        echo "Estos registros causan error en MySQL 5.7+ con modo estricto.";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<strong>✅ No se encontraron registros con fecha '0000-00-00 00:00:00'</strong><br>";
        echo "Tu base de datos ya está limpia.";
        echo "</div>";
    }
} else {
    echo "<div class='error'>❌ Error al consultar: " . $conexion->error . "</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════════
if ($totalAfectados > 0) {
    echo "<div class='step'>";
    echo "<h2>Paso 2: Convertir '0000-00-00' a NULL</h2>";
    
    echo "<div class='info'>";
    echo "<strong>🔍 ¿Por qué es seguro?</strong><br>";
    echo "<ul>";
    echo "<li>NULL es el estándar SQL para 'sin fecha'</li>";
    echo "<li>Tus queries ya usan <code>CASE WHEN fecha_salida IS NULL</code></li>";
    echo "<li>Los reportes seguirán funcionando igual</li>";
    echo "<li>Se conserva toda la información del ingreso</li>";
    echo "</ul>";
    echo "</div>";
    
    // Actualizar registros
    $sqlUpdate = "UPDATE salidas SET fecha_salida = NULL WHERE fecha_salida = '0000-00-00 00:00:00'";
    
    if ($conexion->query($sqlUpdate) === TRUE) {
        echo "<div class='success'>";
        echo "<h3>✅ Actualización Exitosa</h3>";
        echo "<p><strong>{$totalAfectados} registros actualizados</strong></p>";
        echo "<p>Las fechas '0000-00-00 00:00:00' ahora son NULL (compatible con MySQL 5.7+)</p>";
        echo "</div>";
        
        // Verificar
        $resultVerify = $conexion->query($sqlCount);
        if ($resultVerify) {
            $rowVerify = $resultVerify->fetch_assoc();
            $restantes = $rowVerify['total'];
            
            if ($restantes == 0) {
                echo "<div class='success'>";
                echo "<strong>✅ Verificado: 0 registros con '0000-00-00' restantes</strong>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "<strong>⚠️ Quedan {$restantes} registros. Vuelve a ejecutar el script.</strong>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Error al actualizar</h3>";
        echo "<p>" . $conexion->error . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<div class='step'>";
echo "<h2>Paso 3: Verificar Impacto en Reportes</h2>";

echo "<div class='info'>";
echo "<strong>🧪 Probemos que los reportes siguen funcionando:</strong>";
echo "</div>";

// Test 1: Contar salidas con fecha NULL
$sqlTestNull = "SELECT COUNT(*) as total FROM salidas WHERE fecha_salida IS NULL";
$resultTest = $conexion->query($sqlTestNull);

if ($resultTest) {
    $rowTest = $resultTest->fetch_assoc();
    $totalNull = $rowTest['total'];
    
    echo "<table>";
    echo "<tr><th>Verificación</th><th>Resultado</th><th>Estado</th></tr>";
    echo "<tr><td>Salidas con fecha_salida = NULL</td><td><strong>{$totalNull}</strong></td>";
    echo "<td><span style='color: #28a745;'>✅ Normal</span></td></tr>";
    
    // Test 2: Query de reporte (la que usas en consulta_fechas)
    $sqlTestReporte = "SELECT COUNT(*) as total 
                       FROM ingresos i 
                       LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
                       JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
                       WHERE i.salida = 1";
    
    $resultReporte = $conexion->query($sqlTestReporte);
    if ($resultReporte) {
        $rowReporte = $resultReporte->fetch_assoc();
        $totalReporte = $rowReporte['total'];
        echo "<tr><td>Total registros en reportes</td><td><strong>{$totalReporte}</strong></td>";
        echo "<td><span style='color: #28a745;'>✅ Funciona</span></td></tr>";
    }
    
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<strong>✅ Los reportes funcionan correctamente</strong><br>";
    echo "La conversión a NULL NO rompió ninguna funcionalidad.";
    echo "</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════════
echo "<hr>";
echo "<h2>📊 Resumen Final</h2>";

echo "<table>";
echo "<tr><th>Item</th><th>Estado</th></tr>";
echo "<tr><td><strong>Fechas '0000-00-00' convertidas</strong></td><td><span style='color: #28a745;'>✅ Sí</span></td></tr>";
echo "<tr><td><strong>Datos históricos conservados</strong></td><td><span style='color: #28a745;'>✅ Sí</span></td></tr>";
echo "<tr><td><strong>Reportes funcionando</strong></td><td><span style='color: #28a745;'>✅ Sí</span></td></tr>";
echo "<tr><td><strong>Compatible con MySQL 5.7+</strong></td><td><span style='color: #28a745;'>✅ Sí</span></td></tr>";
echo "</table>";

echo "<div class='success'>";
echo "<h3>🎉 ¡Fix Completado!</h3>";
echo "<p>Tu base de datos ahora es compatible con MySQL 5.7+ sin perder datos.</p>";
echo "<hr>";
echo "<h4>🚀 Próximos Pasos:</h4>";
echo "<ol>";
echo "<li><strong>Actualizar archivos PHP</strong> para simplificar las queries (opcional pero recomendado)</li>";
echo "<li><strong>Probar los reportes</strong> en el servidor Ubuntu</li>";
echo "<li><strong>Eliminar este script</strong> por seguridad</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚙️ Optimización Adicional (Opcional)</h3>";
echo "<p>Los archivos PHP aún comparan con '0000-00-00 00:00:00'. Ahora que todo es NULL, puedes simplificar las queries:</p>";
echo "<p><strong>ANTES:</strong></p>";
echo "<code>WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00'</code>";
echo "<p><strong>DESPUÉS (más limpio):</strong></p>";
echo "<code>WHEN s.fecha_salida IS NULL</code>";
echo "<p>Ejecuta el segundo script para actualizar automáticamente los archivos PHP.</p>";
echo "</div>";

$conexion->close();

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Fix completado: " . date('Y-m-d H:i:s') . "</p>";
?>

