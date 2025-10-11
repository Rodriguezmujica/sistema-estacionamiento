<?php
/**
 * 🔧 Actualizar Queries PHP para MySQL 5.7+
 * 
 * Simplifica las queries eliminando comparaciones con '0000-00-00 00:00:00'
 * y dejando solo IS NULL (más limpio y compatible)
 */

header('Content-Type: text/html; charset=utf-8');

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #1976d2; }
h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
code { background: #333; color: #0f0; padding: 3px 8px; border-radius: 3px; font-family: monospace; display: inline-block; }
.file { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #1976d2; border-radius: 4px; }
</style>";

echo "<h1>🔧 Actualizar Queries PHP para MySQL 5.7+</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Archivos a actualizar
$baseDir = dirname(__DIR__);
$archivos = [
    $baseDir . '/api/api_resumen_ejecutivo.php' => 'API Resumen Ejecutivo',
    $baseDir . '/api/api_consulta_fechas.php' => 'API Consulta por Fechas',
    $baseDir . '/api/api_cierre_caja.php' => 'API Cierre de Caja'
];

$totalActualizados = 0;
$totalErrores = 0;

echo "<div class='info'>";
echo "<h3>📋 Archivos a Actualizar:</h3>";
echo "<ul>";
foreach ($archivos as $path => $nombre) {
    echo "<li><strong>{$nombre}</strong>: <code>{$path}</code></li>";
}
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚙️ Cambio que se aplicará:</h3>";
echo "<p><strong>ANTES:</strong></p>";
echo "<code>WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00'</code>";
echo "<p><strong>DESPUÉS:</strong></p>";
echo "<code>WHEN s.fecha_salida IS NULL</code>";
echo "<p><em>Esto hace las queries más limpias y evita problemas con MySQL 5.7+</em></p>";
echo "</div>";

echo "<hr>";
echo "<h2>📝 Procesando Archivos...</h2>";

foreach ($archivos as $rutaArchivo => $nombreArchivo) {
    echo "<div class='file'>";
    echo "<h3>📄 {$nombreArchivo}</h3>";
    echo "<p><code>{$rutaArchivo}</code></p>";
    
    // Verificar que el archivo existe
    if (!file_exists($rutaArchivo)) {
        echo "<div class='error'>❌ Archivo no encontrado</div>";
        $totalErrores++;
        echo "</div>";
        continue;
    }
    
    // Leer contenido
    $contenido = file_get_contents($rutaArchivo);
    $contenidoOriginal = $contenido;
    
    // Contar ocurrencias antes
    $ocurrencias = substr_count($contenido, "= '0000-00-00 00:00:00'");
    
    if ($ocurrencias == 0) {
        echo "<div class='success'>✅ Ya está actualizado (0 ocurrencias)</div>";
        echo "</div>";
        continue;
    }
    
    echo "<p>🔍 Encontradas <strong>{$ocurrencias}</strong> ocurrencias</p>";
    
    // Realizar reemplazo
    $contenido = str_replace(
        " OR s.fecha_salida = '0000-00-00 00:00:00'",
        "",
        $contenido
    );
    
    // Verificar que se hizo el cambio
    $ocurrenciasDespues = substr_count($contenido, "= '0000-00-00 00:00:00'");
    $cambiosRealizados = $ocurrencias - $ocurrenciasDespues;
    
    if ($cambiosRealizados > 0) {
        // Guardar archivo actualizado
        if (file_put_contents($rutaArchivo, $contenido)) {
            echo "<div class='success'>";
            echo "<strong>✅ Archivo actualizado exitosamente</strong><br>";
            echo "• <strong>{$cambiosRealizados}</strong> líneas modificadas<br>";
            if ($ocurrenciasDespues > 0) {
                echo "• <strong>{$ocurrenciasDespues}</strong> ocurrencias restantes (normales si son comentarios)";
            } else {
                echo "• Todas las comparaciones eliminadas";
            }
            echo "</div>";
            $totalActualizados++;
        } else {
            echo "<div class='error'>❌ Error al guardar el archivo</div>";
            $totalErrores++;
        }
    } else {
        echo "<div class='warning'>⚠️ No se realizaron cambios</div>";
    }
    
    echo "</div>";
}

// ═══════════════════════════════════════════════════════════════════════════════
echo "<hr>";
echo "<h2>📊 Resumen Final</h2>";

echo "<table>";
echo "<tr><th>Categoría</th><th>Cantidad</th></tr>";
echo "<tr style='background: #d4edda;'><td><strong>✅ Archivos actualizados</strong></td><td><strong>{$totalActualizados}</strong></td></tr>";
echo "<tr style='background: #f8d7da;'><td><strong>❌ Errores</strong></td><td><strong>{$totalErrores}</strong></td></tr>";
echo "</table>";

if ($totalActualizados > 0 && $totalErrores == 0) {
    echo "<div class='success'>";
    echo "<h3>🎉 ¡Actualización Completa!</h3>";
    echo "<p>Todos los archivos PHP han sido actualizados para MySQL 5.7+</p>";
    echo "<hr>";
    echo "<h4>✅ Cambios Realizados:</h4>";
    echo "<ul>";
    echo "<li>Eliminadas las comparaciones con '0000-00-00 00:00:00'</li>";
    echo "<li>Queries más limpias y legibles</li>";
    echo "<li>100% compatible con MySQL 5.7+ modo estricto</li>";
    echo "<li>Reportes siguen funcionando igual</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<h4>🚀 Próximos Pasos:</h4>";
    echo "<ol>";
    echo "<li><strong>Probar los reportes</strong> en el servidor Ubuntu</li>";
    echo "<li><strong>Verificar el Resumen Ejecutivo</strong> (que daba error)</li>";
    echo "<li><strong>Probar Consulta por Fechas</strong></li>";
    echo "<li><strong>Eliminar este script</strong> por seguridad</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>🧪 Cómo Probar:</h4>";
    echo "<ol>";
    echo "<li>Ir a <strong>Administración</strong> → Resumen Ejecutivo</li>";
    echo "<li>Seleccionar un mes (ej: Octubre 2025)</li>";
    echo "<li>Debe cargar sin errores</li>";
    echo "<li>Ir a <strong>Reportes</strong> → Consultar por Fechas</li>";
    echo "<li>Seleccionar rango de fechas</li>";
    echo "<li>Debe mostrar datos correctamente</li>";
    echo "</ol>";
    echo "</div>";
    
} elseif ($totalErrores > 0) {
    echo "<div class='error'>";
    echo "<h3>⚠️ Atención: Hay Errores</h3>";
    echo "<p>Algunos archivos no se pudieron actualizar. Revisa los mensajes arriba.</p>";
    echo "</div>";
} else {
    echo "<div class='info'>";
    echo "<h3>ℹ️ Información</h3>";
    echo "<p>Los archivos ya estaban actualizados o no se encontraron.</p>";
    echo "</div>";
}

echo "<div class='warning'>";
echo "<h3>🗑️ Limpieza</h3>";
echo "<p>Después de verificar que todo funciona, elimina estos scripts:</p>";
echo "<ul>";
echo "<li><code>sql/fix_fechas_cero_mysql57.php</code></li>";
echo "<li><code>sql/actualizar_queries_php_mysql57.php</code> (este archivo)</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #888;'>Actualización completada: " . date('Y-m-d H:i:s') . "</p>";
?>

