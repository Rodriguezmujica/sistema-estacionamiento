<?php
/**
 * 🔍 DIAGNÓSTICO DE ERROR 500 EN API RESPALDO
 * Para ver exactamente qué error está ocurriendo
 */

// Habilitar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

echo "🔍 DIAGNÓSTICO API RESPALDO\n";
echo "===========================\n\n";

try {
    echo "1. Probando conexión a base de datos...\n";
    require_once __DIR__ . '/../conexion.php';
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Error de conexión: " . ($conn->connect_error ?? 'Conexión no establecida'));
    }
    echo "   ✅ Conexión exitosa\n";
    
    echo "\n2. Probando función crearRespaldo...\n";
    
    // Simular los datos que envía el JavaScript
    $data = [
        'tipo' => 'completo',
        'incluir_datos' => true
    ];
    
    // Llamar directamente a la función
    $resultado = crearRespaldo($data['tipo'], $data['incluir_datos']);
    
    echo "   ✅ Respaldo creado exitosamente\n";
    echo "   Archivo: " . $resultado['archivo'] . "\n";
    echo "   Tamaño: " . $resultado['tamaño'] . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Función copiada del API para probar
function crearRespaldo($tipo, $incluirDatos) {
    global $conn;
    
    // Configuración
    $fecha = date('Y-m-d_H-i-s');
    $directorioRespaldos = __DIR__ . '/../backups_emergencia/';
    
    // Crear directorio si no existe
    if (!is_dir($directorioRespaldos)) {
        mkdir($directorioRespaldos, 0755, true);
    }
    
    // Nombre del archivo
    $nombreArchivo = "respaldo_{$tipo}_{$fecha}.sql";
    $rutaCompleta = $directorioRespaldos . $nombreArchivo;
    
    // Obtener configuración de conexión
    $host = 'localhost';
    $usuario = 'root';
    $contraseña = '';
    $baseDatos = 'estacionamiento';
    
    // Verificar que mysqldump esté disponible
    $mysqldumpPath = '';
    $possiblePaths = [
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        'mysqldump',
        'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe'
    ];
    
    foreach ($possiblePaths as $path) {
        if ($path === 'mysqldump' || file_exists($path)) {
            $mysqldumpPath = $path;
            break;
        }
    }
    
    if (empty($mysqldumpPath)) {
        throw new Exception("No se encontró mysqldump. Verifique la instalación de MySQL.");
    }
    
    // Comando mysqldump
    $comando = "\"$mysqldumpPath\" -h $host -u $usuario";
    if (!empty($contraseña)) {
        $comando .= " -p$contraseña";
    }
    $comando .= " $baseDatos > \"$rutaCompleta\" 2>&1";
    
    // Log del comando (sin contraseña)
    error_log("Comando respaldo: " . str_replace($contraseña, '***', $comando));
    
    // Ejecutar respaldo
    $output = [];
    $returnCode = 0;
    exec($comando, $output, $returnCode);
    
    if ($returnCode !== 0) {
        $errorMsg = "Error al crear respaldo (código: $returnCode): " . implode("\n", $output);
        error_log($errorMsg);
        throw new Exception($errorMsg);
    }
    
    // Verificar que el archivo se creó
    if (!file_exists($rutaCompleta)) {
        throw new Exception("El archivo de respaldo no se creó correctamente");
    }
    
    $tamaño = filesize($rutaCompleta);
    $tamañoFormateado = formatBytes($tamaño);
    
    // Crear respaldo de metadatos
    $metadatos = [
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'tipo' => $tipo,
        'archivo' => $nombreArchivo,
        'tamaño' => $tamaño,
        'tamaño_formateado' => $tamañoFormateado,
        'ruta' => $rutaCompleta
    ];
    
    $archivoMetadatos = $directorioRespaldos . "metadatos_{$fecha}.json";
    file_put_contents($archivoMetadatos, json_encode($metadatos, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'mensaje' => 'Respaldo creado exitosamente',
        'archivo' => $nombreArchivo,
        'tamaño' => $tamañoFormateado,
        'ubicacion' => $rutaCompleta,
        'fecha' => $metadatos['fecha_creacion']
    ];
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>
