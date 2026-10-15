<?php
/**
 * 🔍 DIAGNÓSTICO DE ENDPOINT TUU
 * Para identificar el error 500 en get-pending-tuu-payments.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Diagnóstico TUU Endpoint</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 Diagnóstico de Endpoint TUU</h1>";

// 1. Verificar conexión a base de datos
echo "<h2>1. Verificación de Conexión</h2>";
try {
    require_once '../config/conexion.php';
    
    if ($conn && !$conn->connect_error) {
        echo "<div class='success'>✅ Conexión a base de datos exitosa</div>";
        
        // Verificar charset
        $charset = $conn->character_set_name();
        echo "<div class='info'>Charset: $charset</div>";
        
    } else {
        echo "<div class='error'>❌ Error de conexión: " . ($conn->connect_error ?? 'Conexión nula') . "</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Excepción en conexión: " . $e->getMessage() . "</div>";
    exit;
}

// 2. Verificar estructura de tablas
echo "<h2>2. Verificación de Estructura de Tablas</h2>";

$tablas_requeridas = ['tickets', 'salidas', 'ingresos'];
$tablas_existentes = [];

$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $tablas_existentes[] = $row[0];
    }
}

foreach ($tablas_requeridas as $tabla) {
    if (in_array($tabla, $tablas_existentes)) {
        echo "<div class='success'>✅ Tabla '$tabla' existe</div>";
        
        // Verificar estructura de tabla tickets
        if ($tabla === 'tickets') {
            $result = $conn->query("DESCRIBE tickets");
            if ($result) {
                echo "<div class='info'>Estructura de tabla 'tickets':</div>";
                echo "<ul>";
                while ($row = $result->fetch_assoc()) {
                    echo "<li>{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}</li>";
                }
                echo "</ul>";
            }
        }
    } else {
        echo "<div class='error'>❌ Tabla '$tabla' NO existe</div>";
    }
}

// 3. Probar consulta específica
echo "<h2>3. Prueba de Consulta</h2>";

try {
    $sql = "SELECT 
                t.id as id_ingreso,
                t.patente,
                t.fecha_ingreso,
                t.precio,
                t.cliente_nombre,
                t.cliente_telefono,
                t.observaciones,
                'TUU' as metodo_pago,
                'tuu' as tipo_pago,
                CONCAT('EST-', t.id, '-', UNIX_TIMESTAMP()) as transaction_id,
                NOW() as created_at
            FROM tickets t
            LEFT JOIN salidas s ON t.id = s.id_ingresos
            WHERE t.pagado = 0 
            AND t.tipo_servicio IN ('estacionamiento', 'ambos')
            AND s.id_ingresos IS NULL
            ORDER BY t.fecha_ingreso DESC
            LIMIT 50";
    
    echo "<div class='info'>Ejecutando consulta...</div>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>$sql</pre>";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "<div class='error'>❌ Error en consulta: " . $conn->error . "</div>";
    } else {
        echo "<div class='success'>✅ Consulta ejecutada exitosamente</div>";
        
        $count = $result->num_rows;
        echo "<div class='info'>Registros encontrados: $count</div>";
        
        if ($count > 0) {
            echo "<h3>Primeros 5 registros:</h3>";
            echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Patente</th><th>Fecha</th><th>Precio</th><th>Cliente</th></tr>";
            
            $i = 0;
            while ($row = $result->fetch_assoc() && $i < 5) {
                echo "<tr>";
                echo "<td>{$row['id_ingreso']}</td>";
                echo "<td>{$row['patente']}</td>";
                echo "<td>{$row['fecha_ingreso']}</td>";
                echo "<td>{$row['precio']}</td>";
                echo "<td>{$row['cliente_nombre']}</td>";
                echo "</tr>";
                $i++;
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Excepción en consulta: " . $e->getMessage() . "</div>";
}

// 4. Verificar permisos de archivos
echo "<h2>4. Verificación de Archivos</h2>";

$archivos_requeridos = [
    'conexion.php' => 'Archivo de conexión',
    'api/get-pending-tuu-payments.php' => 'Endpoint TUU',
    'config-sensible.php' => 'Configuración sensible'
];

foreach ($archivos_requeridos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        $permisos = substr(sprintf('%o', fileperms($archivo)), -4);
        echo "<div class='success'>✅ $descripcion: $archivo (permisos: $permisos)</div>";
    } else {
        echo "<div class='error'>❌ $descripcion: $archivo NO encontrado</div>";
    }
}

// 5. Probar endpoint directamente
echo "<h2>5. Prueba del Endpoint</h2>";

echo "<div class='info'>Probando endpoint: <a href='api/get-pending-tuu-payments.php' target='_blank'>api/get-pending-tuu-payments.php</a></div>";

// Simular la llamada al endpoint
ob_start();
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include 'api/get-pending-tuu-payments.php';
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

// 6. Verificar logs de error
echo "<h2>6. Logs de Error</h2>";

$log_files = [
    'C:\\xampp\\apache\\logs\\error.log',
    'C:\\xampp\\php\\logs\\php_error_log',
    'logs\\error.log'
];

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<div class='info'>Log encontrado: $log_file</div>";
        $log_content = file_get_contents($log_file);
        $recent_errors = array_slice(explode("\n", $log_content), -10);
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(implode("\n", $recent_errors));
        echo "</pre>";
        break;
    }
}

echo "<h2>7. Recomendaciones</h2>";

if (empty($tablas_existentes)) {
    echo "<div class='error'>❌ No se encontraron tablas. Ejecuta el instalador de base de datos.</div>";
} elseif (!in_array('tickets', $tablas_existentes)) {
    echo "<div class='error'>❌ Tabla 'tickets' no existe. Verifica la estructura de la base de datos.</div>";
} else {
    echo "<div class='success'>✅ Estructura básica parece correcta</div>";
}

echo "<div class='info'>";
echo "<h3>Pasos para solucionar:</h3>";
echo "<ol>";
echo "<li>Si no hay tablas: Ejecutar <code>instalar-bd-windows7.php</code></li>";
echo "<li>Si hay error de conexión: Verificar configuración en <code>conexion.php</code></li>";
echo "<li>Si hay error de consulta: Verificar estructura de tabla 'tickets'</li>";
echo "<li>Si hay error de permisos: Verificar permisos de archivos</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
