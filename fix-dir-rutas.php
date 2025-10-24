<?php
/**
 * Script para arreglar rutas con __DIR__ después de reorganización
 */

echo "🔧 Arreglando rutas con __DIR__...\n\n";

// Patrones a buscar y reemplazar
$patrones = [
    // APIs
    "require_once __DIR__ . '/../../config/conexion.php';" => "require_once __DIR__ . '/../config/conexion.php';",
    "require_once __DIR__ . \"/../../config/conexion.php\";" => "require_once __DIR__ . '/../config/conexion.php';",
    
    // Tests
    "require_once __DIR__ . '/../../config/conexion.php';" => "require_once __DIR__ . '/../config/conexion.php';",
    "require_once __DIR__ . \"/../../config/conexion.php\";" => "require_once __DIR__ . '/../config/conexion.php';",
    
    // Maintenance
    "require_once __DIR__ . '/../../config/conexion.php';" => "require_once __DIR__ . '/../config/conexion.php';",
    "require_once __DIR__ . \"/../../config/conexion.php\";" => "require_once __DIR__ . '/../config/conexion.php';",
    
    // TUU
    "require_once __DIR__ . '/../../config/conexion.php';" => "require_once __DIR__ . '/../config/conexion.php';",
    "require_once __DIR__ . \"/../../config/conexion.php\";" => "require_once __DIR__ . '/../config/conexion.php';",
    
    // Firebase
    "require_once __DIR__ . '/../../config/conexion.php';" => "require_once __DIR__ . '/../config/conexion.php';",
    "require_once __DIR__ . \"/../../config/conexion.php\";" => "require_once __DIR__ . '/../config/conexion.php';",
];

// Buscar archivos que contengan estos patrones
$directorios = ['api', 'tests', 'maintenance', 'tuu', 'firebase'];

$actualizados = 0;
$errores = 0;

foreach ($directorios as $dir) {
    if (is_dir($dir)) {
        $archivos = glob($dir . '/*.php');
        foreach ($archivos as $archivo) {
            $contenido = file_get_contents($archivo);
            $nuevo_contenido = $contenido;
            $cambios = 0;
            
            foreach ($patrones as $buscar => $reemplazar) {
                if (strpos($nuevo_contenido, $buscar) !== false) {
                    $nuevo_contenido = str_replace($buscar, $reemplazar, $nuevo_contenido);
                    $cambios++;
                }
            }
            
            if ($cambios > 0) {
                if (file_put_contents($archivo, $nuevo_contenido)) {
                    echo "✅ $archivo - $cambios cambios\n";
                    $actualizados++;
                } else {
                    echo "❌ Error escribiendo $archivo\n";
                    $errores++;
                }
            }
        }
    }
}

echo "\n📊 Resumen:\n";
echo "✅ Archivos actualizados: $actualizados\n";
echo "❌ Errores: $errores\n";
echo "🎉 ¡Arreglo de rutas __DIR__ completado!\n";
?>
