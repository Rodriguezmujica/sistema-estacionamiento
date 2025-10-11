<?php
header('Content-Type: text/html; charset=utf-8');

$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>🔧 Configuración de Máquinas TUU</h2>";
echo "<hr>";

// 1. Verificar si la tabla ya existe
$check = $conexion->query("SHOW TABLES LIKE 'configuracion_tuu'");

if ($check->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ La tabla 'configuracion_tuu' ya existe.</p>";
    
    $result = $conexion->query("SELECT * FROM configuracion_tuu");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Configuración Actual:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #333; color: white;'>";
        echo "<th>Máquina</th><th>Device Serial</th><th>Estado</th><th>Nombre</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $estado = $row['activa'] ? '🟢 ACTIVA' : '⚪ Inactiva';
            $color = $row['activa'] ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: {$color};'>";
            echo "<td>{$row['maquina']}</td><td>{$row['device_serial']}</td>";
            echo "<td><strong>{$estado}</strong></td><td>{$row['nombre']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h3>Paso 1: Creando tabla configuracion_tuu...</h3>";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS `configuracion_tuu` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `maquina` VARCHAR(20) NOT NULL UNIQUE COMMENT 'principal o respaldo',
      `device_serial` VARCHAR(100) NOT NULL COMMENT 'Número de serie del dispositivo TUU',
      `nombre` VARCHAR(100) DEFAULT NULL COMMENT 'Nombre descriptivo',
      `activa` TINYINT(1) DEFAULT 0 COMMENT '1 = activa, 0 = inactiva',
      `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conexion->query($sql1) === TRUE) {
        echo "<p style='color: green;'>✅ Tabla 'configuracion_tuu' creada exitosamente.</p>";
        
        echo "<h3>Paso 2: Insertando configuración inicial...</h3>";
        
        // Insertar TUU Principal (la actual)
        $sql2 = "INSERT INTO configuracion_tuu (maquina, device_serial, nombre, activa) 
                 VALUES ('principal', '6752d2805d5b1d86', 'TUU Principal - Caja 1', 1)";
        $conexion->query($sql2);
        echo "<p style='color: green;'>✅ TUU Principal configurada (activa por defecto)</p>";
        
        // Insertar TUU Respaldo (inactiva, debes cambiar el serial)
        $sql3 = "INSERT INTO configuracion_tuu (maquina, device_serial, nombre, activa) 
                 VALUES ('respaldo', 'SERIAL_MAQUINA_2_AQUI', 'TUU Respaldo - Caja 2', 0)";
        $conexion->query($sql3);
        echo "<p style='color: green;'>✅ TUU Respaldo configurada (inactiva)</p>";
        
        echo "<hr>";
        echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
        echo "<h4>⚠️ IMPORTANTE: Configurar Serial de Máquina Respaldo</h4>";
        echo "<p>La máquina de respaldo tiene un serial de ejemplo. <strong>Debes cambiarlo</strong> por el serial real de tu segunda máquina TUU.</p>";
        echo "<p><strong>Cómo obtener el serial:</strong></p>";
        echo "<ol>";
        echo "<li>Ve a tu panel de TUU</li>";
        echo "<li>Busca el número de serie del segundo dispositivo</li>";
        echo "<li>Ejecuta en MySQL:</li>";
        echo "</ol>";
        echo "<code>UPDATE configuracion_tuu SET device_serial = 'TU_SERIAL_AQUI' WHERE maquina = 'respaldo';</code>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h3 style='color: green;'>✅ Tabla creada exitosamente!</h3>";
        
        echo "<div style='background: #e3f2fd; padding: 15px; margin: 20px 0; border-left: 4px solid #1976d2;'>";
        echo "<h4>🎯 Próximos pasos:</h4>";
        echo "<ol>";
        echo "<li>Actualiza el serial de la máquina respaldo en la BD</li>";
        echo "<li>Elimina este archivo por seguridad</li>";
        echo "<li>El botón de Emergencia en index.php ya funcionará</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>❌ Error al crear la tabla: " . $conexion->error . "</p>";
    }
}

$conexion->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 { color: #1976d2; }
    h3 { color: #333; margin-top: 20px; }
    table { margin: 20px 0; width: 100%; background: white; }
    code { background: #333; color: #0f0; padding: 5px 10px; display: block; margin: 10px 0; }
</style>

