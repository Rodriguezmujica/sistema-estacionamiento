<?php
/**
 * Crear tablas necesarias para el sistema híbrido
 * Sistema de Estacionamiento Los Ríos
 */

require_once 'conexion.php';

echo "<h1>🔧 Crear Tablas del Sistema</h1>";

try {
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
    // Crear tabla tickets si no existe
    echo "<h2>🎫 Creando tabla 'tickets'</h2>";
    $sqlTickets = "
    CREATE TABLE IF NOT EXISTS `tickets` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `patente` varchar(10) NOT NULL,
        `fecha_ingreso` datetime NOT NULL,
        `fecha_salida` datetime DEFAULT NULL,
        `tipo_servicio` enum('estacionamiento','lavado','ambos') NOT NULL DEFAULT 'estacionamiento',
        `precio` decimal(10,2) DEFAULT 0.00,
        `pagado` tinyint(1) DEFAULT 0,
        `cliente_nombre` varchar(100) DEFAULT NULL,
        `cliente_telefono` varchar(20) DEFAULT NULL,
        `observaciones` text,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_patente` (`patente`),
        KEY `idx_fecha_ingreso` (`fecha_ingreso`),
        KEY `idx_pagado` (`pagado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($sqlTickets)) {
        echo "<p style='color: green;'>✅ Tabla 'tickets' creada/verificada correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creando tabla 'tickets': " . $conn->error . "</p>";
    }
    
    // Crear tabla servicios_lavado si no existe
    echo "<h2>🚿 Creando tabla 'servicios_lavado'</h2>";
    $sqlServicios = "
    CREATE TABLE IF NOT EXISTS `servicios_lavado` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `patente` varchar(10) NOT NULL,
        `fecha_servicio` datetime NOT NULL,
        `tipo_lavado` enum('básico','completo','premium') NOT NULL DEFAULT 'básico',
        `precio_base` decimal(10,2) NOT NULL DEFAULT 0.00,
        `precio_extra` decimal(10,2) DEFAULT 0.00,
        `motivos_extra` text,
        `cliente_nombre` varchar(100) DEFAULT NULL,
        `cliente_telefono` varchar(20) DEFAULT NULL,
        `observaciones` text,
        `completado` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_patente` (`patente`),
        KEY `idx_fecha_servicio` (`fecha_servicio`),
        KEY `idx_completado` (`completado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($sqlServicios)) {
        echo "<p style='color: green;'>✅ Tabla 'servicios_lavado' creada/verificada correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creando tabla 'servicios_lavado': " . $conn->error . "</p>";
    }
    
    // Verificar tabla usuarios
    echo "<h2>👥 Verificando tabla 'usuarios'</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'usuarios'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Tabla 'usuarios' ya existe</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Tabla 'usuarios' no existe - creándola</p>";
        
        $sqlUsuarios = "
        CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `usuario` varchar(50) NOT NULL UNIQUE,
            `password_hash` varchar(255) NOT NULL,
            `rol` enum('admin','operador','supervisor') NOT NULL DEFAULT 'operador',
            `nombre` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `activo` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_usuario` (`usuario`),
            KEY `idx_rol` (`rol`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($conn->query($sqlUsuarios)) {
            echo "<p style='color: green;'>✅ Tabla 'usuarios' creada correctamente</p>";
            
            // Crear usuario por defecto
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $sqlInsertUser = "INSERT INTO usuarios (usuario, password_hash, rol, nombre, email) VALUES ('admin', '$passwordHash', 'admin', 'Administrador', 'admin@sistema.com')";
            
            if ($conn->query($sqlInsertUser)) {
                echo "<p style='color: green;'>✅ Usuario administrador creado (usuario: admin, contraseña: admin123)</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Usuario administrador ya existe</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Error creando tabla 'usuarios': " . $conn->error . "</p>";
        }
    }
    
    // Insertar datos de prueba si las tablas están vacías
    echo "<h2>📝 Insertando datos de prueba</h2>";
    
    // Verificar si hay tickets
    $result = $conn->query("SELECT COUNT(*) as count FROM tickets");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        echo "<p>Insertando tickets de prueba...</p>";
        
        $ticketsPrueba = [
            "INSERT INTO tickets (patente, fecha_ingreso, tipo_servicio, precio, pagado, cliente_nombre) VALUES ('ABC123', NOW(), 'estacionamiento', 1500.00, 1, 'Juan Pérez')",
            "INSERT INTO tickets (patente, fecha_ingreso, fecha_salida, tipo_servicio, precio, pagado, cliente_nombre) VALUES ('DEF456', DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW(), 'estacionamiento', 3000.00, 1, 'María González')",
            "INSERT INTO tickets (patente, fecha_ingreso, tipo_servicio, precio, pagado, cliente_nombre) VALUES ('GHI789', NOW(), 'ambos', 5000.00, 0, 'Carlos López')"
        ];
        
        foreach ($ticketsPrueba as $sql) {
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✅ Ticket de prueba insertado</p>";
            } else {
                echo "<p style='color: red;'>❌ Error insertando ticket: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Ya hay " . $row['count'] . " tickets en la base de datos</p>";
    }
    
    // Verificar si hay servicios de lavado
    $result = $conn->query("SELECT COUNT(*) as count FROM servicios_lavado");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        echo "<p>Insertando servicios de lavado de prueba...</p>";
        
        $serviciosPrueba = [
            "INSERT INTO servicios_lavado (patente, fecha_servicio, tipo_lavado, precio_base, precio_extra, motivos_extra, cliente_nombre, completado) VALUES ('ABC123', NOW(), 'básico', 5000.00, 0.00, '', 'Juan Pérez', 1)",
            "INSERT INTO servicios_lavado (patente, fecha_servicio, tipo_lavado, precio_base, precio_extra, motivos_extra, cliente_nombre, completado) VALUES ('JKL012', NOW(), 'completo', 8000.00, 2000.00, 'cera,aspirado', 'Ana Martínez', 0)",
            "INSERT INTO servicios_lavado (patente, fecha_servicio, tipo_lavado, precio_base, precio_extra, motivos_extra, cliente_nombre, completado) VALUES ('MNO345', DATE_SUB(NOW(), INTERVAL 1 HOUR), 'premium', 12000.00, 3000.00, 'cera,aspirado,detallado', 'Roberto Silva', 1)"
        ];
        
        foreach ($serviciosPrueba as $sql) {
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✅ Servicio de lavado de prueba insertado</p>";
            } else {
                echo "<p style='color: red;'>❌ Error insertando servicio: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Ya hay " . $row['count'] . " servicios de lavado en la base de datos</p>";
    }
    
    echo "<h2>✅ Proceso completado</h2>";
    echo "<p>Las tablas necesarias han sido creadas/verificadas. Ahora puedes probar las APIs nuevamente.</p>";
    echo "<p><a href='test-apis.php'>🧪 Probar APIs nuevamente</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
