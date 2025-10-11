<?php
header('Content-Type: text/html; charset=utf-8');

$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>📊 Crear Tabla de Metas Mensuales</h2>";
echo "<hr>";

// 1. Verificar si la tabla ya existe
$check = $conexion->query("SHOW TABLES LIKE 'metas_mensuales'");

if ($check->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ La tabla 'metas_mensuales' ya existe.</p>";
    
    // Mostrar registros existentes
    $result = $conexion->query("SELECT * FROM metas_mensuales ORDER BY anio DESC, mes DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Metas Registradas:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #333; color: white;'>";
        echo "<th>Mes/Año</th><th>Meta</th><th>Solo Lun-Vie</th><th>Incluye Mensuales</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $nombreMes = date('F Y', mktime(0, 0, 0, $row['mes'], 1, $row['anio']));
            $meta = number_format($row['meta_monto'], 0, ',', '.');
            $soloLaborales = $row['solo_dias_laborales'] ? '✅ Sí' : '❌ No';
            $incluirMensuales = $row['incluir_mensuales'] ? '✅ Sí' : '❌ No';
            
            echo "<tr>";
            echo "<td>{$nombreMes}</td><td>\${$meta}</td><td>{$soloLaborales}</td><td>{$incluirMensuales}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h3>Paso 1: Creando tabla metas_mensuales...</h3>";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS `metas_mensuales` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `mes` INT NOT NULL COMMENT 'Mes (1-12)',
      `anio` INT NOT NULL COMMENT 'Año (ej: 2025)',
      `meta_monto` DECIMAL(10,2) NOT NULL COMMENT 'Meta en pesos',
      `solo_dias_laborales` TINYINT(1) DEFAULT 1 COMMENT '1 = solo lun-vie, 0 = todos los días',
      `incluir_mensuales` TINYINT(1) DEFAULT 0 COMMENT '1 = incluir clientes mensuales, 0 = solo servicios diarios',
      `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `usuario_creador` VARCHAR(50) DEFAULT NULL,
      UNIQUE KEY `mes_anio` (`mes`, `anio`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conexion->query($sql1) === TRUE) {
        echo "<p style='color: green;'>✅ Tabla 'metas_mensuales' creada exitosamente.</p>";
        
        echo "<h3>Paso 2: Insertando meta de ejemplo...</h3>";
        
        $mesActual = date('n');
        $anioActual = date('Y');
        $metaEjemplo = 5000000; // $5.000.000 de ejemplo
        
        $sql2 = "INSERT INTO `metas_mensuales` (`mes`, `anio`, `meta_monto`, `solo_dias_laborales`, `incluir_mensuales`) 
                 VALUES (?, ?, ?, 1, 0)";
        $stmt = $conexion->prepare($sql2);
        $stmt->bind_param("iid", $mesActual, $anioActual, $metaEjemplo);
        $stmt->execute();
        $stmt->close();
        
        echo "<p style='color: green;'>✅ Meta de ejemplo insertada para " . date('F Y') . ": $" . number_format($metaEjemplo, 0, ',', '.') . "</p>";
        
        echo "<hr>";
        echo "<h3 style='color: green;'>✅ Tabla creada y configurada exitosamente!</h3>";
        
        echo "<div style='background: #e3f2fd; padding: 15px; margin: 20px 0; border-left: 4px solid #1976d2;'>";
        echo "<h4>🎯 Próximos pasos:</h4>";
        echo "<ol>";
        echo "<li>Elimina este archivo (sql/ejecutar_crear_tabla_metas.php) por seguridad</li>";
        echo "<li>Ve a Administración → Configuración</li>";
        echo "<li>Verás el nuevo panel de Resumen Ejecutivo</li>";
        echo "<li>Puedes cambiar la meta desde ahí</li>";
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
    p { line-height: 1.6; }
</style>

