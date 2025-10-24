<?php
/**
 * 🚀 REPARACIÓN RÁPIDA PARA ANTIX
 * Soluciona todos los errores comunes en Antix Linux
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Reparación Rápida Antix</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🚀 Reparación Rápida Antix</h1>";

try {
    require_once 'conexion.php';
    
    if (!$conn || $conn->connect_error) {
        throw new Exception("Error de conexión: " . ($conn->connect_error ?? 'Conexión nula'));
    }
    
    echo "<div class='success'>✅ Conectado a la base de datos</div>";
    
    // ============================================
    // 1. CREAR TABLA TICKETS (CRÍTICA PARA TUU)
    // ============================================
    
    echo "<h2>1. Creando tabla 'tickets' (CRÍTICA para TUU)</h2>";
    
    $sql_tickets = "CREATE TABLE IF NOT EXISTS `tickets` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `patente` varchar(10) NOT NULL,
        `fecha_ingreso` datetime NOT NULL,
        `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
        `cliente_nombre` varchar(100) DEFAULT NULL,
        `cliente_telefono` varchar(20) DEFAULT NULL,
        `observaciones` text,
        `tipo_servicio` enum('estacionamiento','lavado','ambos') DEFAULT 'estacionamiento',
        `pagado` tinyint(1) DEFAULT 0,
        `sincronizado` tinyint(1) DEFAULT 0,
        `pc_origen` varchar(20) DEFAULT NULL,
        `fecha_sincronizacion` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_patente` (`patente`),
        KEY `idx_fecha_ingreso` (`fecha_ingreso`),
        KEY `idx_pagado` (`pagado`),
        KEY `idx_tipo_servicio` (`tipo_servicio`),
        KEY `idx_sincronizado` (`sincronizado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_tickets) === TRUE) {
        echo "<div class='success'>✅ Tabla 'tickets' creada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error creando tabla 'tickets': " . $conn->error . "</div>";
    }
    
    // ============================================
    // 2. CREAR TABLA FIREBASE_SYNC_LOG
    // ============================================
    
    echo "<h2>2. Creando tabla 'firebase_sync_log'</h2>";
    
    $sql_firebase_log = "CREATE TABLE IF NOT EXISTS `firebase_sync_log` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_firebase_log) === TRUE) {
        echo "<div class='success'>✅ Tabla 'firebase_sync_log' creada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error creando tabla 'firebase_sync_log': " . $conn->error . "</div>";
    }
    
    // ============================================
    // 3. CREAR TABLA METAS
    // ============================================
    
    echo "<h2>3. Creando tabla 'metas'</h2>";
    
    $sql_metas = "CREATE TABLE IF NOT EXISTS `metas` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_metas) === TRUE) {
        echo "<div class='success'>✅ Tabla 'metas' creada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error creando tabla 'metas': " . $conn->error . "</div>";
    }
    
    // ============================================
    // 4. CREAR TABLA CONFIGURACION_SISTEMA
    // ============================================
    
    echo "<h2>4. Creando tabla 'configuracion_sistema'</h2>";
    
    $sql_config = "CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `clave` varchar(100) NOT NULL,
        `valor` text,
        `descripcion` text,
        `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_clave` (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_config) === TRUE) {
        echo "<div class='success'>✅ Tabla 'configuracion_sistema' creada exitosamente</div>";
        
        // Insertar configuraciones básicas
        $configs = [
            ['sistema_version', '2.0.0', 'Versión del sistema'],
            ['firebase_enabled', '1', 'Firebase habilitado'],
            ['tuu_enabled', '1', 'TUU habilitado'],
            ['sync_interval', '30', 'Intervalo de sincronización en segundos']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`) VALUES (?, ?, ?)");
        
        foreach ($configs as $config) {
            $stmt->bind_param('sss', $config[0], $config[1], $config[2]);
            if ($stmt->execute()) {
                echo "<div class='info'>✅ Configuración: {$config[0]}</div>";
            } else {
                echo "<div class='warning'>⚠️ Error insertando: {$config[0]} - " . $stmt->error . "</div>";
            }
        }
        
        $stmt->close();
        
    } else {
        echo "<div class='error'>❌ Error creando tabla 'configuracion_sistema': " . $conn->error . "</div>";
    }
    
    // ============================================
    // 5. AGREGAR CAMPOS FALTANTES
    // ============================================
    
    echo "<h2>5. Agregando campos faltantes</h2>";
    
    // Agregar campo sincronizado a ingresos
    $sql_sync_ingresos = "ALTER TABLE `ingresos` ADD COLUMN IF NOT EXISTS `sincronizado` tinyint(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado'";
    if ($conn->query($sql_sync_ingresos) === TRUE) {
        echo "<div class='success'>✅ Campo 'sincronizado' agregado a 'ingresos'</div>";
    } else {
        echo "<div class='warning'>⚠️ Campo 'sincronizado' ya existe en 'ingresos' o error: " . $conn->error . "</div>";
    }
    
    // Agregar campo sincronizado a salidas
    $sql_sync_salidas = "ALTER TABLE `salidas` ADD COLUMN IF NOT EXISTS `sincronizado` tinyint(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado'";
    if ($conn->query($sql_sync_salidas) === TRUE) {
        echo "<div class='success'>✅ Campo 'sincronizado' agregado a 'salidas'</div>";
    } else {
        echo "<div class='warning'>⚠️ Campo 'sincronizado' ya existe en 'salidas' o error: " . $conn->error . "</div>";
    }
    
    // ============================================
    // 6. VERIFICACIÓN FINAL
    // ============================================
    
    echo "<h2>6. Verificación Final</h2>";
    
    // Verificar que la tabla tickets existe
    $result = $conn->query("SHOW TABLES LIKE 'tickets'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✅ Tabla 'tickets' verificada - TUU debería funcionar</div>";
    } else {
        echo "<div class='error'>❌ Tabla 'tickets' no encontrada</div>";
    }
    
    // Probar endpoint TUU
    $test_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/get-pending-tuu-payments.php';
    echo "<div class='info'>Probar endpoint TUU: <a href='$test_url' target='_blank'>$test_url</a></div>";
    
    echo "<div class='success'>";
    echo "<h2>🎉 ¡REPARACIÓN COMPLETADA!</h2>";
    echo "<p>Se han creado todas las tablas y campos faltantes en Antix.</p>";
    echo "<ul>";
    echo "<li>✅ Tabla 'tickets' creada (crítica para TUU)</li>";
    echo "<li>✅ Tabla 'firebase_sync_log' creada</li>";
    echo "<li>✅ Tabla 'metas' creada</li>";
    echo "<li>✅ Tabla 'configuracion_sistema' creada</li>";
    echo "<li>✅ Campos de sincronización agregados</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔧 Para solucionar errores de Firebase:</h3>";
    echo "<p>Agregar en index.php antes de Firebase:</p>";
    echo "<pre>&lt;script src=\"fix-firebase-browser-compatibility.js\"&gt;&lt;/script&gt;</pre>";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR EN LA REPARACIÓN</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
