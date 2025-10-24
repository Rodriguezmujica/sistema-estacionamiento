<?php
/**
 * 📅 RESPALDO AUTOMÁTICO SEMANAL
 * Script para ejecutar respaldos automáticos cada semana
 * Configurar en Windows Task Scheduler para ejecutar cada domingo a las 2:00 AM
 */

// Configuración
$logFile = __DIR__ . '/../logs/respaldo_automatico.log';
$directorioRespaldos = __DIR__ . '/../backups_emergencia/';

// Crear directorio de logs si no existe
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    echo $logEntry;
}

logMessage("🚀 Iniciando respaldo automático semanal");

try {
    // Incluir conexión
    require_once __DIR__ . '/../config/conexion.php';
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    // Crear directorio de respaldos si no existe
    if (!is_dir($directorioRespaldos)) {
        mkdir($directorioRespaldos, 0755, true);
        logMessage("📁 Directorio de respaldos creado: $directorioRespaldos");
    }
    
    // Configuración de respaldo
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "respaldo_automatico_{$fecha}.sql";
    $rutaCompleta = $directorioRespaldos . $nombreArchivo;
    
    // Obtener configuración de conexión
    $host = 'localhost';
    $usuario = 'root';
    $contraseña = '';
    $baseDatos = 'estacionamiento';
    
    // Comando mysqldump
    $comando = "mysqldump -h $host -u $usuario";
    if (!empty($contraseña)) {
        $comando .= " -p$contraseña";
    }
    $comando .= " $baseDatos > \"$rutaCompleta\"";
    
    logMessage("🔧 Ejecutando comando: " . str_replace($contraseña, '***', $comando));
    
    // Ejecutar respaldo
    $output = [];
    $returnCode = 0;
    exec($comando . ' 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception("Error al crear respaldo. Código: $returnCode. Output: " . implode("\n", $output));
    }
    
    // Verificar que el archivo se creó
    if (!file_exists($rutaCompleta)) {
        throw new Exception("El archivo de respaldo no se creó correctamente");
    }
    
    $tamaño = filesize($rutaCompleta);
    $tamañoFormateado = formatBytes($tamaño);
    
    logMessage("✅ Respaldo creado exitosamente");
    logMessage("📄 Archivo: $nombreArchivo");
    logMessage("📊 Tamaño: $tamañoFormateado");
    logMessage("📍 Ubicación: $rutaCompleta");
    
    // Crear metadatos del respaldo
    $metadatos = [
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'tipo' => 'automatico_semanal',
        'archivo' => $nombreArchivo,
        'tamaño' => $tamaño,
        'tamaño_formateado' => $tamañoFormateado,
        'ruta' => $rutaCompleta,
        'comando_ejecutado' => str_replace($contraseña, '***', $comando)
    ];
    
    $archivoMetadatos = $directorioRespaldos . "metadatos_automatico_{$fecha}.json";
    file_put_contents($archivoMetadatos, json_encode($metadatos, JSON_PRETTY_PRINT));
    
    // Limpiar respaldos antiguos (mantener solo los últimos 4 semanas)
    limpiarRespaldosAntiguos($directorioRespaldos, 4);
    
    // Verificar espacio en disco
    $espacioLibre = disk_free_space($directorioRespaldos);
    $espacioLibreFormateado = formatBytes($espacioLibre);
    logMessage("💾 Espacio libre en disco: $espacioLibreFormateado");
    
    logMessage("🎉 Respaldo automático completado exitosamente");
    
} catch (Exception $e) {
    logMessage("❌ Error en respaldo automático: " . $e->getMessage());
    exit(1);
}

function limpiarRespaldosAntiguos($directorio, $maxRespaldos = 4) {
    $archivos = glob($directorio . 'respaldo_automatico_*.sql');
    
    if (count($archivos) <= $maxRespaldos) {
        return;
    }
    
    // Ordenar por fecha de modificación (más recientes primero)
    usort($archivos, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Eliminar archivos excedentes
    $archivosAEliminar = array_slice($archivos, $maxRespaldos);
    
    foreach ($archivosAEliminar as $archivo) {
        if (unlink($archivo)) {
            logMessage("🗑️ Respaldo antiguo eliminado: " . basename($archivo));
        }
        
        // Eliminar metadatos correspondientes
        $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);
        $archivoMetadatos = $directorio . "metadatos_automatico_{$nombreBase}.json";
        if (file_exists($archivoMetadatos)) {
            unlink($archivoMetadatos);
        }
    }
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>
