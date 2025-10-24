<?php
/**
 * 🚀 OPTIMIZACIÓN MYSQL PARA WINDOWS 7
 * Script para mejorar el rendimiento de MySQL en XAMPP
 */

echo "<h2>🚀 Optimización MySQL para Windows 7</h2>";
echo "<hr>";

try {
    require_once 'conexion.php';
    
    if (!$conn || $conn->connect_error) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    echo "<h3>1. Configuración actual:</h3>";
    
    // Mostrar configuración actual
    $configs = [
        'innodb_buffer_pool_size',
        'query_cache_size',
        'query_cache_type',
        'max_connections',
        'wait_timeout',
        'interactive_timeout'
    ];
    
    foreach ($configs as $config) {
        $result = $conn->query("SHOW VARIABLES LIKE '$config'");
        if ($result && $row = $result->fetch_assoc()) {
            echo "📊 $config: " . $row['Value'] . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>2. Aplicando optimizaciones...</h3>";
    
    // Optimizaciones que se pueden aplicar sin reiniciar
    $optimizaciones = [
        "SET GLOBAL query_cache_size = 67108864", // 64MB
        "SET GLOBAL query_cache_type = 1",
        "SET GLOBAL wait_timeout = 300",
        "SET GLOBAL interactive_timeout = 300",
        "SET GLOBAL max_connect_errors = 1000000"
    ];
    
    foreach ($optimizaciones as $sql) {
        if ($conn->query($sql)) {
            echo "✅ " . substr($sql, 0, 50) . "...<br>";
        } else {
            echo "❌ Error: " . $conn->error . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>3. Creando índices para consultas frecuentes...</h3>";
    
    // Índices para mejorar consultas del resumen ejecutivo
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_ingresos_fecha_salida ON ingresos(fecha_ingreso, salida)",
        "CREATE INDEX IF NOT EXISTS idx_salidas_fecha ON salidas(fecha_salida)",
        "CREATE INDEX IF NOT EXISTS idx_ingresos_tipo_salida ON ingresos(idtipo_ingreso, salida)",
        "CREATE INDEX IF NOT EXISTS idx_salidas_metodo ON salidas(metodo_pago, tipo_pago)"
    ];
    
    foreach ($indices as $sql) {
        if ($conn->query($sql)) {
            echo "✅ Índice creado/verificado<br>";
        } else {
            echo "⚠️ " . $conn->error . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>4. Análisis de tablas...</h3>";
    
    // Analizar tablas para optimizar
    $tablas = ['ingresos', 'salidas', 'tipo_ingreso'];
    foreach ($tablas as $tabla) {
        $result = $conn->query("ANALYZE TABLE $tabla");
        if ($result) {
            echo "✅ Tabla $tabla analizada<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>✅ Optimización completada</h3>";
    echo "<p><strong>Recomendaciones adicionales:</strong></p>";
    echo "<ul>";
    echo "<li>Reiniciar XAMPP para aplicar cambios de configuración</li>";
    echo "<li>Considerar aumentar RAM si es posible</li>";
    echo "<li>Usar SSD en lugar de HDD para mejor rendimiento</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
