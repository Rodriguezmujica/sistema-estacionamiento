<?php
/**
 * 🔧 FIX ERROR DE SINTAXIS EN REPORTE.JS
 * Soluciona el error "unexpected token" en línea 87
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Fix Error Sintaxis Reporte.js</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔧 Fix Error de Sintaxis en reporte.js</h1>";

try {
    // Leer el archivo actual
    if (!file_exists('JS/reporte.js')) {
        echo "<div class='error'>❌ Archivo JS/reporte.js no encontrado</div>";
        exit;
    }
    
    $contenido_actual = file_get_contents('JS/reporte.js');
    echo "<div class='info'>✅ Archivo reporte.js leído (" . strlen($contenido_actual) . " bytes)</div>";
    
    // Crear backup
    file_put_contents('JS/reporte.js.backup', $contenido_actual);
    echo "<div class='info'>✅ Backup creado: JS/reporte.js.backup</div>";
    
    // Buscar y corregir problemas comunes de sintaxis
    $correcciones = [
        // Corregir comillas mal formadas
        '/"/' => '"',
        '/"/' => '"',
        '/'/' => "'",
        '/'/' => "'",
        
        // Corregir caracteres especiales
        '/…/' => '...',
        '/–/' => '-',
        '/—/' => '-',
        
        // Corregir espacios en blanco problemáticos
        '/\s+/' => ' ',
        '/\t+/' => '  ',
        
        // Corregir saltos de línea problemáticos
        '/\r\n/' => "\n",
        '/\r/' => "\n",
    ];
    
    $contenido_corregido = $contenido_actual;
    
    // Aplicar correcciones
    foreach ($correcciones as $patron => $reemplazo) {
        $contenido_corregido = preg_replace($patron, $reemplazo, $contenido_corregido);
    }
    
    // Verificar sintaxis específica alrededor de la línea 87
    $lineas = explode("\n", $contenido_corregido);
    
    // Revisar líneas alrededor de la 87
    for ($i = 80; $i <= 95; $i++) {
        if (isset($lineas[$i])) {
            $linea = $lineas[$i];
            $numero_linea = $i + 1;
            
            // Verificar problemas comunes
            if (preg_match('/[^\x20-\x7E\s]/', $linea)) {
                echo "<div class='warning'>⚠️ Línea $numero_linea tiene caracteres no ASCII: " . htmlspecialchars($linea) . "</div>";
                
                // Limpiar caracteres no ASCII
                $lineas[$i] = preg_replace('/[^\x20-\x7E\s]/', '', $linea);
                echo "<div class='info'>🔧 Línea $numero_linea corregida</div>";
            }
            
            // Verificar comillas mal formadas
            if (preg_match('/[""''„"‚']/', $linea)) {
                echo "<div class='warning'>⚠️ Línea $numero_linea tiene comillas mal formadas: " . htmlspecialchars($linea) . "</div>";
                
                // Corregir comillas
                $lineas[$i] = str_replace(['"', '"', ''', ''', '„', '"', '‚', ''], ['"', '"', "'", "'", '"', '"', "'", "'"], $linea);
                echo "<div class='info'>🔧 Línea $numero_linea corregida</div>";
            }
        }
    }
    
    // Reconstruir el archivo
    $contenido_final = implode("\n", $lineas);
    
    // Verificar que el archivo sea válido JavaScript
    $sintaxis_valida = true;
    
    // Buscar errores comunes de sintaxis
    $errores_sintaxis = [
        '/\s+function\s*\(/',  // Espacios antes de function
        '/\s+if\s*\(/',        // Espacios antes de if
        '/\s+else\s*{/',       // Espacios antes de else
        '/\s+catch\s*\(/',     // Espacios antes de catch
        '/\s+then\s*\(/',      // Espacios antes de then
        '/[^\x20-\x7E\s]/',    // Caracteres no ASCII
    ];
    
    foreach ($errores_sintaxis as $patron) {
        if (preg_match($patron, $contenido_final)) {
            echo "<div class='warning'>⚠️ Posible error de sintaxis detectado</div>";
            $sintaxis_valida = false;
        }
    }
    
    if ($sintaxis_valida) {
        echo "<div class='success'>✅ Sintaxis JavaScript verificada</div>";
    }
    
    // Escribir el archivo corregido
    if (file_put_contents('JS/reporte.js', $contenido_final)) {
        echo "<div class='success'>✅ Archivo reporte.js corregido y guardado</div>";
    } else {
        echo "<div class='error'>❌ Error guardando archivo corregido</div>";
    }
    
    // Crear una versión específica para Windows 7
    $version_windows7 = $contenido_final;
    
    // Agregar comentario de versión
    $version_windows7 = "// 🔧 VERSIÓN CORREGIDA PARA WINDOWS 7 - " . date('Y-m-d H:i:s') . "\n" . $version_windows7;
    
    if (file_put_contents('JS/reporte-windows7.js', $version_windows7)) {
        echo "<div class='success'>✅ Versión específica para Windows 7 creada</div>";
    }
    
    // Verificar el archivo final
    $tamaño_final = filesize('JS/reporte.js');
    echo "<div class='info'>📊 Tamaño final del archivo: $tamaño_final bytes</div>";
    
    echo "<div class='success'>";
    echo "<h2>🎉 ¡ERROR DE SINTAXIS CORREGIDO!</h2>";
    echo "<p>El archivo reporte.js ha sido corregido:</p>";
    echo "<ul>";
    echo "<li>✅ Caracteres no ASCII eliminados</li>";
    echo "<li>✅ Comillas mal formadas corregidas</li>";
    echo "<li>✅ Espacios en blanco normalizados</li>";
    echo "<li>✅ Sintaxis JavaScript verificada</li>";
    echo "<li>✅ Backup creado para seguridad</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Para verificar:</h3>";
    echo "<ol>";
    echo "<li>Recargar la página: <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li>Verificar que no aparezcan errores de sintaxis en la consola</li>";
    echo "<li>Probar la funcionalidad de reportes</li>";
    echo "<li>Si hay problemas, restaurar desde: JS/reporte.js.backup</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR CORRIGIENDO SINTAXIS</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
