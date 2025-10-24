<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

/**
 * API para Respaldo de Base de Datos
 * Crea respaldos automáticos y manuales
 */

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $tipo = $data['tipo'] ?? 'completo';
            $incluirDatos = $data['incluir_datos'] ?? true;
            
            $resultado = crearRespaldo($tipo, $incluirDatos);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'GET':
            $respaldos = listarRespaldos();
            echo json_encode(['success' => true, 'respaldos' => $respaldos], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

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
    $host = $conn->host_info;
    $usuario = 'root'; // Ajustar según configuración
    $contraseña = '';  // Ajustar según configuración
    $baseDatos = 'estacionamiento';
    
    // Comando mysqldump
    $comando = "mysqldump -h localhost -u $usuario";
    if (!empty($contraseña)) {
        $comando .= " -p$contraseña";
    }
    $comando .= " $baseDatos > \"$rutaCompleta\"";
    
    // Ejecutar respaldo
    $output = [];
    $returnCode = 0;
    exec($comando, $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception("Error al crear respaldo: " . implode("\n", $output));
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
    
    // Limpiar respaldos antiguos (mantener solo los últimos 10)
    limpiarRespaldosAntiguos($directorioRespaldos);
    
    return [
        'success' => true,
        'mensaje' => 'Respaldo creado exitosamente',
        'archivo' => $nombreArchivo,
        'tamaño' => $tamañoFormateado,
        'ubicacion' => $rutaCompleta,
        'fecha' => $metadatos['fecha_creacion']
    ];
}

function listarRespaldos() {
    $directorioRespaldos = __DIR__ . '/../backups_emergencia/';
    $respaldos = [];
    
    if (!is_dir($directorioRespaldos)) {
        return $respaldos;
    }
    
    $archivos = glob($directorioRespaldos . 'respaldo_*.sql');
    
    foreach ($archivos as $archivo) {
        $info = [
            'archivo' => basename($archivo),
            'tamaño' => formatBytes(filesize($archivo)),
            'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($archivo)),
            'ruta' => $archivo
        ];
        
        // Buscar metadatos correspondientes
        $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);
        $archivoMetadatos = $directorioRespaldos . "metadatos_{$nombreBase}.json";
        
        if (file_exists($archivoMetadatos)) {
            $metadatos = json_decode(file_get_contents($archivoMetadatos), true);
            $info = array_merge($info, $metadatos);
        }
        
        $respaldos[] = $info;
    }
    
    // Ordenar por fecha de modificación (más recientes primero)
    usort($respaldos, function($a, $b) {
        return strtotime($b['fecha_modificacion']) - strtotime($a['fecha_modificacion']);
    });
    
    return $respaldos;
}

function limpiarRespaldosAntiguos($directorio, $maxRespaldos = 10) {
    $archivos = glob($directorio . 'respaldo_*.sql');
    
    if (count($archivos) <= $maxRespaldos) {
        return;
    }
    
    // Ordenar por fecha de modificación
    usort($archivos, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Eliminar archivos excedentes
    $archivosAEliminar = array_slice($archivos, $maxRespaldos);
    
    foreach ($archivosAEliminar as $archivo) {
        unlink($archivo);
        
        // Eliminar metadatos correspondientes
        $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);
        $archivoMetadatos = $directorio . "metadatos_{$nombreBase}.json";
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
