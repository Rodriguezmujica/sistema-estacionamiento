<?php
/**
 * 🔔 MONITOREO DE BASE DE DATOS
 * 
 * Verifica el estado de la base de datos y envía alertas
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');

$resultado = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'unknown',
    'checks' => [],
    'alertas' => []
];

// ============================================
// 1. VERIFICAR CONEXIÓN
// ============================================

try {
    require_once __DIR__ . '/conexion.php';
    
    if ($conn->ping()) {
        $resultado['checks']['conexion'] = 'OK';
    } else {
        $resultado['checks']['conexion'] = 'FAIL';
        $resultado['alertas'][] = 'No se puede conectar a MySQL';
    }
} catch (Exception $e) {
    $resultado['checks']['conexion'] = 'ERROR';
    $resultado['alertas'][] = 'Error de conexión: ' . $e->getMessage();
}

// ============================================
// 2. VERIFICAR QUE EXISTA LA BASE DE DATOS
// ============================================

try {
    $result = $conn->query("SELECT DATABASE() as db");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['db'] === 'estacionamiento') {
            $resultado['checks']['base_datos'] = 'OK';
        } else {
            $resultado['checks']['base_datos'] = 'FAIL';
            $resultado['alertas'][] = 'Base de datos incorrecta: ' . $row['db'];
        }
    }
} catch (Exception $e) {
    $resultado['checks']['base_datos'] = 'ERROR';
    $resultado['alertas'][] = 'Error verificando BD: ' . $e->getMessage();
}

// ============================================
// 3. VERIFICAR TABLAS PRINCIPALES
// ============================================

$tablas_requeridas = ['ingresos', 'salidas', 'tipo_ingreso', 'clientes', 'usuarios'];
$tablas_encontradas = 0;

try {
    $result = $conn->query("SHOW TABLES");
    $tablas_existentes = [];
    
    while ($row = $result->fetch_array()) {
        $tablas_existentes[] = $row[0];
    }
    
    foreach ($tablas_requeridas as $tabla) {
        if (in_array($tabla, $tablas_existentes)) {
            $tablas_encontradas++;
        } else {
            $resultado['alertas'][] = "Tabla faltante: {$tabla}";
        }
    }
    
    $resultado['checks']['tablas'] = "{$tablas_encontradas}/" . count($tablas_requeridas);
    
} catch (Exception $e) {
    $resultado['checks']['tablas'] = 'ERROR';
    $resultado['alertas'][] = 'Error verificando tablas';
}

// ============================================
// 4. VERIFICAR INTEGRIDAD DE DATOS
// ============================================

try {
    // Contar registros en tablas principales
    $result = $conn->query("SELECT COUNT(*) as total FROM ingresos");
    $row = $result->fetch_assoc();
    $resultado['checks']['registros_ingresos'] = $row['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM tipo_ingreso");
    $row = $result->fetch_assoc();
    $resultado['checks']['tipos_servicio'] = $row['total'];
    
    if ($resultado['checks']['tipos_servicio'] < 2) {
        $resultado['alertas'][] = 'Pocos tipos de servicio configurados';
    }
    
} catch (Exception $e) {
    $resultado['checks']['integridad'] = 'ERROR';
}

// ============================================
// 5. VERIFICAR BACKUPS RECIENTES
// ============================================

$backup_dir = __DIR__ . '/backups/';
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($is_windows) {
    $backup_dir = __DIR__ . '\\backups\\';
}

if (is_dir($backup_dir)) {
    $archivos = glob($backup_dir . "estacionamiento_backup_*.sql*");
    
    if (empty($archivos)) {
        $resultado['checks']['backup'] = 'NO_BACKUPS';
        $resultado['alertas'][] = 'No hay backups disponibles';
    } else {
        // Encontrar el más reciente
        $mas_reciente = null;
        $fecha_mas_reciente = 0;
        
        foreach ($archivos as $archivo) {
            $fecha = filemtime($archivo);
            if ($fecha > $fecha_mas_reciente) {
                $fecha_mas_reciente = $fecha;
                $mas_reciente = $archivo;
            }
        }
        
        $antiguo = time() - $fecha_mas_reciente;
        $horas = round($antiguo / 3600);
        
        $resultado['checks']['ultimo_backup'] = date('Y-m-d H:i:s', $fecha_mas_reciente);
        $resultado['checks']['backup_horas_atras'] = $horas;
        
        if ($horas > 48) {
            $resultado['alertas'][] = "Backup muy antiguo ({$horas} horas)";
        }
    }
} else {
    $resultado['checks']['backup'] = 'NO_FOLDER';
    $resultado['alertas'][] = 'Carpeta de backups no existe';
}

// ============================================
// 6. DETERMINAR ESTADO GENERAL
// ============================================

if (empty($resultado['alertas'])) {
    $resultado['status'] = 'OK';
} elseif (count($resultado['alertas']) <= 2) {
    $resultado['status'] = 'WARNING';
} else {
    $resultado['status'] = 'CRITICAL';
}

// ============================================
// 7. GUARDAR LOG
// ============================================

$log_file = __DIR__ . '/logs/monitoreo.log';
if (!is_dir(__DIR__ . '/logs/')) {
    mkdir(__DIR__ . '/logs/', 0755, true);
}

$log_entry = date('[Y-m-d H:i:s]') . " Status: {$resultado['status']} | Alertas: " . count($resultado['alertas']) . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

// ============================================
// RETORNAR RESULTADO
// ============================================

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>

