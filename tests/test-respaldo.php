<?php
/**
 * 🧪 PRUEBA SIMPLE DE RESPALDO
 * Para diagnosticar problemas con mysqldump
 */

header('Content-Type: text/plain; charset=utf-8');

echo "🧪 PRUEBA DE RESPALDO MYSQLDUMP\n";
echo "================================\n\n";

// 1. Verificar directorio de respaldos
$directorioRespaldos = __DIR__ . '/../backups_emergencia/';
echo "1. Directorio de respaldos: $directorioRespaldos\n";

if (!is_dir($directorioRespaldos)) {
    echo "   Creando directorio...\n";
    if (mkdir($directorioRespaldos, 0755, true)) {
        echo "   ✅ Directorio creado exitosamente\n";
    } else {
        echo "   ❌ Error creando directorio\n";
        exit;
    }
} else {
    echo "   ✅ Directorio existe\n";
}

// 2. Verificar permisos de escritura
if (is_writable($directorioRespaldos)) {
    echo "   ✅ Directorio escribible\n";
} else {
    echo "   ❌ Directorio no escribible\n";
    exit;
}

// 3. Buscar mysqldump
echo "\n2. Buscando mysqldump...\n";
$possiblePaths = [
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'mysqldump',
    'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe'
];

$mysqldumpPath = '';
foreach ($possiblePaths as $path) {
    echo "   Probando: $path\n";
    if ($path === 'mysqldump') {
        // Probar si está en PATH
        $output = [];
        exec("where mysqldump 2>nul", $output);
        if (!empty($output)) {
            $mysqldumpPath = 'mysqldump';
            echo "   ✅ Encontrado en PATH\n";
            break;
        }
    } elseif (file_exists($path)) {
        $mysqldumpPath = $path;
        echo "   ✅ Encontrado: $path\n";
        break;
    }
}

if (empty($mysqldumpPath)) {
    echo "   ❌ No se encontró mysqldump\n";
    echo "   💡 Instale MySQL o verifique la ruta de XAMPP\n";
    exit;
}

// 4. Probar comando mysqldump
echo "\n3. Probando comando mysqldump...\n";
$comando = "\"$mysqldumpPath\" --version";
echo "   Comando: $comando\n";

$output = [];
$returnCode = 0;
exec($comando, $output, $returnCode);

if ($returnCode === 0) {
    echo "   ✅ mysqldump funciona\n";
    echo "   Versión: " . implode("\n", $output) . "\n";
} else {
    echo "   ❌ Error ejecutando mysqldump\n";
    echo "   Output: " . implode("\n", $output) . "\n";
    exit;
}

// 5. Probar respaldo real
echo "\n4. Probando respaldo real...\n";
$fecha = date('Y-m-d_H-i-s');
$archivoPrueba = $directorioRespaldos . "prueba_respaldo_{$fecha}.sql";

$comando = "\"$mysqldumpPath\" -h localhost -u root estacionamiento > \"$archivoPrueba\" 2>&1";
echo "   Comando: $comando\n";

$output = [];
$returnCode = 0;
exec($comando, $output, $returnCode);

if ($returnCode === 0 && file_exists($archivoPrueba)) {
    $tamaño = filesize($archivoPrueba);
    echo "   ✅ Respaldo creado exitosamente\n";
    echo "   Archivo: $archivoPrueba\n";
    echo "   Tamaño: " . number_format($tamaño) . " bytes\n";
    
    // Mostrar primeras líneas del archivo
    $contenido = file_get_contents($archivoPrueba);
    $lineas = explode("\n", $contenido);
    echo "   Primeras líneas:\n";
    for ($i = 0; $i < min(5, count($lineas)); $i++) {
        echo "     " . $lineas[$i] . "\n";
    }
} else {
    echo "   ❌ Error creando respaldo\n";
    echo "   Código: $returnCode\n";
    echo "   Output: " . implode("\n", $output) . "\n";
}

echo "\n✅ Prueba completada\n";
?>
