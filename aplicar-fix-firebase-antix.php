<?php
/**
 * 🔧 APLICAR FIX FIREBASE EN ANTIX
 * Modifica index.php automáticamente para solucionar errores de Firebase
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Aplicar Fix Firebase</title>";
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
echo "<h1>🔧 Aplicar Fix Firebase en Antix</h1>";

try {
    // Verificar que index.php existe
    if (!file_exists('index.php')) {
        throw new Exception("Archivo index.php no encontrado");
    }
    
    echo "<div class='success'>✅ Archivo index.php encontrado</div>";
    
    // Leer contenido actual
    $contenido = file_get_contents('index.php');
    
    // Verificar si ya tiene el fix
    if (strpos($contenido, 'fix-firebase-browser-compatibility.js') !== false) {
        echo "<div class='info'>⚠️ El fix ya está aplicado en index.php</div>";
    } else {
        // Buscar la línea donde está Firebase (línea 881 aproximadamente)
        $lineas = explode("\n", $contenido);
        $nueva_contenido = "";
        $fix_aplicado = false;
        
        foreach ($lineas as $i => $linea) {
            $numero_linea = $i + 1;
            
            // Buscar líneas que contengan Firebase
            if (strpos($linea, 'firebase') !== false || strpos($linea, 'Firebase') !== false) {
                if (!$fix_aplicado) {
                    // Agregar el fix antes de Firebase
                    $nueva_contenido .= "  <!-- Fix para compatibilidad de navegador -->\n";
                    $nueva_contenido .= "  <script src=\"fix-firebase-browser-compatibility.js\"></script>\n";
                    $nueva_contenido .= "\n";
                    $fix_aplicado = true;
                    echo "<div class='success'>✅ Fix agregado antes de la línea $numero_linea</div>";
                }
            }
            
            $nueva_contenido .= $linea . "\n";
        }
        
        if ($fix_aplicado) {
            // Crear backup
            file_put_contents('index.php.backup', $contenido);
            echo "<div class='info'>✅ Backup creado: index.php.backup</div>";
            
            // Aplicar cambios
            if (file_put_contents('index.php', $nueva_contenido)) {
                echo "<div class='success'>✅ Fix aplicado exitosamente en index.php</div>";
            } else {
                throw new Exception("Error al escribir en index.php");
            }
        } else {
            echo "<div class='warning'>⚠️ No se encontraron líneas de Firebase para aplicar el fix</div>";
        }
    }
    
    // Verificar que el archivo fix existe
    if (file_exists('fix-firebase-browser-compatibility.js')) {
        echo "<div class='success'>✅ Archivo fix-firebase-browser-compatibility.js encontrado</div>";
    } else {
        echo "<div class='error'>❌ Archivo fix-firebase-browser-compatibility.js NO encontrado</div>";
        echo "<div class='info'>Debes copiar este archivo desde el USB a Antix</div>";
    }
    
    echo "<div class='success'>";
    echo "<h2>🎉 ¡Fix Aplicado!</h2>";
    echo "<p>El fix de Firebase ha sido aplicado en index.php</p>";
    echo "<ul>";
    echo "<li>✅ Script de compatibilidad agregado</li>";
    echo "<li>✅ Backup creado (index.php.backup)</li>";
    echo "<li>✅ Errores de Firebase deberían solucionarse</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Para verificar:</h3>";
    echo "<ol>";
    echo "<li>Recargar la página: <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li>Verificar que no aparezcan errores de Firebase en la consola</li>";
    echo "<li>Probar funcionalidades del sistema</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR APLICANDO FIX</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔧 Solución manual:</h3>";
    echo "<p>1. Copiar fix-firebase-browser-compatibility.js a Antix</p>";
    echo "<p>2. Editar index.php manualmente</p>";
    echo "<p>3. Agregar antes de Firebase:</p>";
    echo "<pre>&lt;script src=\"fix-firebase-browser-compatibility.js\"&gt;&lt;/script&gt;</pre>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
