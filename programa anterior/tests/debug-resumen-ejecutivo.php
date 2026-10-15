<?php
/**
 * 🔍 DIAGNÓSTICO DE RESUMEN EJECUTIVO
 * Para identificar el error "Failed to fetch"
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Diagnóstico Resumen Ejecutivo</title>";
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
echo "<h1>🔍 Diagnóstico de Resumen Ejecutivo</h1>";

try {
    require_once '../config/conexion.php';
    
    if (!$conn || $conn->connect_error) {
        throw new Exception("Error de conexión: " . ($conn->connect_error ?? 'Conexión nula'));
    }
    
    echo "<div class='success'>✅ Conectado a la base de datos</div>";
    
    // Verificar tablas requeridas
    $tablas_requeridas = ['ingresos', 'salidas', 'tipo_ingreso', 'clientes'];
    $tablas_existentes = [];
    
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tablas_existentes[] = $row[0];
        }
    }
    
    echo "<h2>1. Verificación de Tablas</h2>";
    foreach ($tablas_requeridas as $tabla) {
        if (in_array($tabla, $tablas_existentes)) {
            echo "<div class='success'>✅ Tabla '$tabla' existe</div>";
        } else {
            echo "<div class='error'>❌ Tabla '$tabla' NO existe</div>";
        }
    }
    
    // Verificar estructura de tabla ingresos
    echo "<h2>2. Verificación de Estructura - Tabla 'ingresos'</h2>";
    $result = $conn->query("DESCRIBE ingresos");
    if ($result) {
        $campos = [];
        while ($row = $result->fetch_assoc()) {
            $campos[] = $row['Field'];
        }
        
        $campos_requeridos = ['idautos_estacionados', 'fecha_ingreso', 'salida', 'idtipo_ingreso'];
        foreach ($campos_requeridos as $campo) {
            if (in_array($campo, $campos)) {
                echo "<div class='success'>✅ Campo '$campo' existe</div>";
            } else {
                echo "<div class='error'>❌ Campo '$campo' NO existe</div>";
            }
        }
    }
    
    // Verificar estructura de tabla salidas
    echo "<h2>3. Verificación de Estructura - Tabla 'salidas'</h2>";
    $result = $conn->query("DESCRIBE salidas");
    if ($result) {
        $campos = [];
        while ($row = $result->fetch_assoc()) {
            $campos[] = $row['Field'];
        }
        
        $campos_requeridos = ['id_ingresos', 'total'];
        foreach ($campos_requeridos as $campo) {
            if (in_array($campo, $campos)) {
                echo "<div class='success'>✅ Campo '$campo' existe</div>";
            } else {
                echo "<div class='error'>❌ Campo '$campo' NO existe</div>";
            }
        }
    }
    
    // Verificar estructura de tabla tipo_ingreso
    echo "<h2>4. Verificación de Estructura - Tabla 'tipo_ingreso'</h2>";
    $result = $conn->query("DESCRIBE tipo_ingreso");
    if ($result) {
        $campos = [];
        while ($row = $result->fetch_assoc()) {
            $campos[] = $row['Field'];
        }
        
        $campos_requeridos = ['idtipo_ingresos', 'precio'];
        foreach ($campos_requeridos as $campo) {
            if (in_array($campo, $campos)) {
                echo "<div class='success'>✅ Campo '$campo' existe</div>";
            } else {
                echo "<div class='error'>❌ Campo '$campo' NO existe</div>";
            }
        }
    }
    
    // Probar consulta básica
    echo "<h2>5. Prueba de Consulta Básica</h2>";
    
    try {
        $sql = "SELECT COUNT(*) as total FROM ingresos WHERE salida = 1";
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<div class='success'>✅ Consulta básica exitosa: {$row['total']} registros</div>";
        } else {
            echo "<div class='error'>❌ Error en consulta básica: " . $conn->error . "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Excepción en consulta básica: " . $e->getMessage() . "</div>";
    }
    
    // Probar endpoint directamente
    echo "<h2>6. Prueba del Endpoint</h2>";
    
    $test_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/api_resumen_ejecutivo.php?mes=12&anio=2024';
    echo "<div class='info'>Probando endpoint: <a href='$test_url' target='_blank'>$test_url</a></div>";
    
    // Simular la llamada al endpoint
    ob_start();
    try {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['mes'] = '12';
        $_GET['anio'] = '2024';
        include 'api/api_resumen_ejecutivo.php';
        $output = ob_get_clean();
        
        echo "<div class='success'>✅ Endpoint ejecutado sin errores fatales</div>";
        echo "<h3>Respuesta del endpoint:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($output);
        echo "</pre>";
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "<div class='error'>❌ Error al ejecutar endpoint: " . $e->getMessage() . "</div>";
    }
    
    // Verificar si falta tabla clientes
    if (!in_array('clientes', $tablas_existentes)) {
        echo "<h2>7. Crear Tabla 'clientes' Faltante</h2>";
        echo "<div class='warning'>⚠️ La tabla 'clientes' no existe. Creando...</div>";
        
        $sql_clientes = "CREATE TABLE IF NOT EXISTS `clientes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `telefono` varchar(20) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `monto_plan` decimal(10,2) DEFAULT 0.00,
            `inicio_plan` date DEFAULT NULL,
            `activo` tinyint(1) DEFAULT 1,
            `fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_activo` (`activo`),
            KEY `idx_inicio_plan` (`inicio_plan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql_clientes) === TRUE) {
            echo "<div class='success'>✅ Tabla 'clientes' creada exitosamente</div>";
        } else {
            echo "<div class='error'>❌ Error creando tabla 'clientes': " . $conn->error . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR EN EL DIAGNÓSTICO</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>🔧 Soluciones:</h3>";
echo "<ul>";
echo "<li>Si faltan tablas: Ejecutar <code>crear-tablas-faltantes.php</code></li>";
echo "<li>Si hay error de conexión: Verificar que MySQL esté iniciado</li>";
echo "<li>Si hay error de consulta: Verificar estructura de tablas</li>";
echo "<li>Si el endpoint falla: Revisar logs de Apache</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
