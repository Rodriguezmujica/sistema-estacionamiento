<?php
/**
 * 🔍 VERIFICADOR DE TABLAS FALTANTES
 * Sistema de Estacionamiento Los Ríos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Verificador de Tablas Faltantes</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".header { background: #2c3e50; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
echo "th { background-color: #f2f2f2; }";
echo ".missing { background-color: #ffebee; }";
echo ".present { background-color: #e8f5e8; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 Verificador de Tablas Faltantes</h1>";
echo "<p>Sistema de Estacionamiento - Análisis Completo</p>";
echo "</div>";

try {
    require_once '../config/conexion.php';
    
    if (!$conn || $conn->connect_error) {
        throw new Exception("Error de conexión: " . ($conn->connect_error ?? 'Conexión nula'));
    }
    
    echo "<div class='success'>✅ Conectado a la base de datos</div>";
    
    // Obtener todas las tablas existentes
    $result = $conn->query("SHOW TABLES");
    $tablas_existentes = [];
    
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tablas_existentes[] = $row[0];
        }
    }
    
    // Lista completa de tablas requeridas para el sistema híbrido
    $tablas_requeridas = [
        // Tablas principales
        'ingresos' => [
            'descripcion' => 'Registros de entrada de vehículos',
            'campos_criticos' => ['idautos_estacionados', 'patente', 'fecha_ingreso', 'precio', 'sincronizado']
        ],
        'salidas' => [
            'descripcion' => 'Registros de salida con pagos TUU',
            'campos_criticos' => ['id', 'id_ingresos', 'fecha_salida', 'total', 'transaction_id', 'tipo_pago', 'sincronizado']
        ],
        'tickets' => [
            'descripcion' => 'Sistema de tickets para TUU',
            'campos_criticos' => ['id', 'patente', 'fecha_ingreso', 'precio', 'tipo_servicio', 'pagado', 'sincronizado']
        ],
        'tipo_ingreso' => [
            'descripcion' => 'Tipos de servicios disponibles',
            'campos_criticos' => ['id', 'nombre', 'precio', 'activo', 'tipo_servicio']
        ],
        
        // Tablas de configuración TUU
        'configuracion_tuu' => [
            'descripcion' => 'Configuración de máquinas TUU',
            'campos_criticos' => ['id', 'maquina', 'device_serial', 'nombre', 'activa']
        ],
        
        // Tablas de sincronización Firebase
        'firebase_sync_log' => [
            'descripcion' => 'Log de sincronización con Firebase',
            'campos_criticos' => ['id', 'tabla', 'accion', 'registro_id', 'fecha_sync']
        ],
        
        // Tablas de lavados
        'lavados_pendientes' => [
            'descripcion' => 'Lavados pendientes de procesar',
            'campos_criticos' => ['id', 'patente', 'tipo_lavado', 'precio', 'estado']
        ],
        
        // Tablas de metas y estadísticas
        'metas' => [
            'descripcion' => 'Metas de ventas y objetivos',
            'campos_criticos' => ['id', 'tipo', 'valor_objetivo', 'fecha_inicio', 'fecha_fin']
        ],
        
        // Tablas de configuración del sistema
        'configuracion_sistema' => [
            'descripcion' => 'Configuración general del sistema',
            'campos_criticos' => ['id', 'clave', 'valor', 'descripcion']
        ]
    ];
    
    echo "<h2>📊 Análisis de Tablas</h2>";
    
    $tablas_faltantes = [];
    $tablas_presentes = [];
    
    foreach ($tablas_requeridas as $tabla => $info) {
        if (in_array($tabla, $tablas_existentes)) {
            $tablas_presentes[] = $tabla;
        } else {
            $tablas_faltantes[] = $tabla;
        }
    }
    
    echo "<div class='info'>";
    echo "<h3>📈 Resumen:</h3>";
    echo "<ul>";
    echo "<li><strong>Total requeridas:</strong> " . count($tablas_requeridas) . "</li>";
    echo "<li><strong>Presentes:</strong> " . count($tablas_presentes) . "</li>";
    echo "<li><strong>Faltantes:</strong> " . count($tablas_faltantes) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // Mostrar tabla detallada
    echo "<h2>📋 Estado Detallado de Tablas</h2>";
    echo "<table>";
    echo "<tr><th>Tabla</th><th>Estado</th><th>Descripción</th><th>Campos Críticos</th></tr>";
    
    foreach ($tablas_requeridas as $tabla => $info) {
        $estado = in_array($tabla, $tablas_existentes) ? '✅ Presente' : '❌ Faltante';
        $clase = in_array($tabla, $tablas_existentes) ? 'present' : 'missing';
        
        echo "<tr class='$clase'>";
        echo "<td><strong>$tabla</strong></td>";
        echo "<td>$estado</td>";
        echo "<td>{$info['descripcion']}</td>";
        echo "<td>" . implode(', ', $info['campos_criticos']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Verificar campos críticos en tablas existentes
    if (!empty($tablas_presentes)) {
        echo "<h2>🔍 Verificación de Campos Críticos</h2>";
        
        foreach ($tablas_presentes as $tabla) {
            if (isset($tablas_requeridas[$tabla])) {
                $campos_requeridos = $tablas_requeridas[$tabla]['campos_criticos'];
                
                echo "<h3>Tabla: $tabla</h3>";
                
                $result = $conn->query("DESCRIBE `$tabla`");
                $campos_existentes = [];
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $campos_existentes[] = $row['Field'];
                    }
                }
                
                $campos_faltantes = array_diff($campos_requeridos, $campos_existentes);
                
                if (empty($campos_faltantes)) {
                    echo "<div class='success'>✅ Todos los campos críticos presentes</div>";
                } else {
                    echo "<div class='error'>❌ Campos críticos faltantes: " . implode(', ', $campos_faltantes) . "</div>";
                }
            }
        }
    }
    
    // Generar scripts de creación para tablas faltantes
    if (!empty($tablas_faltantes)) {
        echo "<h2>🛠️ Scripts de Creación para Tablas Faltantes</h2>";
        
        foreach ($tablas_faltantes as $tabla) {
            echo "<h3>Crear tabla: $tabla</h3>";
            
            switch ($tabla) {
                case 'configuracion_tuu':
                    echo "<div class='info'>";
                    echo "<pre>";
                    echo "-- Crear tabla configuracion_tuu
CREATE TABLE IF NOT EXISTS `configuracion_tuu` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `maquina` varchar(50) NOT NULL,
    `device_serial` varchar(100) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `activa` tinyint(1) DEFAULT 0,
    `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_maquina` (`maquina`),
    KEY `idx_activa` (`activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto
INSERT INTO `configuracion_tuu` (`maquina`, `device_serial`, `nombre`, `activa`) VALUES
('principal', '6752d2805d5b1d86', 'TUU Principal - Caja 1', 1),
('respaldo', 'SERIAL_AQUI', 'TUU Respaldo - Caja 2', 0);";
                    echo "</pre>";
                    echo "</div>";
                    break;
                    
                case 'firebase_sync_log':
                    echo "<div class='info'>";
                    echo "<pre>";
                    echo "-- Crear tabla firebase_sync_log
CREATE TABLE IF NOT EXISTS `firebase_sync_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tabla` varchar(50) NOT NULL,
    `accion` enum('create','update','delete') NOT NULL,
    `registro_id` int(11) NOT NULL,
    `fecha_sync` timestamp DEFAULT CURRENT_TIMESTAMP,
    `estado` enum('pending','success','error') DEFAULT 'pending',
    `error_message` text,
    PRIMARY KEY (`id`),
    KEY `idx_tabla` (`tabla`),
    KEY `idx_fecha_sync` (`fecha_sync`),
    KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                    echo "</pre>";
                    echo "</div>";
                    break;
                    
                case 'lavados_pendientes':
                    echo "<div class='info'>";
                    echo "<pre>";
                    echo "-- Crear tabla lavados_pendientes
CREATE TABLE IF NOT EXISTS `lavados_pendientes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `patente` varchar(10) NOT NULL,
    `tipo_lavado` varchar(100) NOT NULL,
    `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
    `estado` enum('pendiente','en_proceso','completado','cancelado') DEFAULT 'pendiente',
    `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
    `fecha_completado` timestamp NULL,
    `observaciones` text,
    PRIMARY KEY (`id`),
    KEY `idx_patente` (`patente`),
    KEY `idx_estado` (`estado`),
    KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                    echo "</pre>";
                    echo "</div>";
                    break;
                    
                case 'metas':
                    echo "<div class='info'>";
                    echo "<pre>";
                    echo "-- Crear tabla metas
CREATE TABLE IF NOT EXISTS `metas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo` varchar(50) NOT NULL,
    `valor_objetivo` decimal(10,2) NOT NULL,
    `fecha_inicio` date NOT NULL,
    `fecha_fin` date NOT NULL,
    `activa` tinyint(1) DEFAULT 1,
    `descripcion` text,
    PRIMARY KEY (`id`),
    KEY `idx_tipo` (`tipo`),
    KEY `idx_activa` (`activa`),
    KEY `idx_fechas` (`fecha_inicio`, `fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                    echo "</pre>";
                    echo "</div>";
                    break;
                    
                case 'configuracion_sistema':
                    echo "<div class='info'>";
                    echo "<pre>";
                    echo "-- Crear tabla configuracion_sistema
CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `clave` varchar(100) NOT NULL,
    `valor` text,
    `descripcion` text,
    `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones básicas
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`) VALUES
('sistema_version', '2.0.0', 'Versión del sistema'),
('firebase_enabled', '1', 'Firebase habilitado'),
('tuu_enabled', '1', 'TUU habilitado'),
('sync_interval', '30', 'Intervalo de sincronización en segundos');";
                    echo "</pre>";
                    echo "</div>";
                    break;
            }
        }
    }
    
    // Verificar endpoint TUU
    echo "<h2>🔧 Verificación de Endpoint TUU</h2>";
    
    if (in_array('tickets', $tablas_existentes)) {
        echo "<div class='success'>✅ Tabla 'tickets' existe</div>";
        
        // Probar endpoint
        $test_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/get-pending-tuu-payments.php';
        echo "<div class='info'>Probando endpoint: <a href='$test_url' target='_blank'>$test_url</a></div>";
        
    } else {
        echo "<div class='error'>❌ Tabla 'tickets' no existe - Este es el problema del error 500</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error en la verificación</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
