<?php
/**
 * 🧪 PRUEBA DE CONECTIVIDAD ANTIX → WINDOWS 7
 * Verifica que Antix pueda conectarse a la base de datos en Windows 7
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>🧪 Prueba de Conectividad Antix → Windows 7</h2>";
echo "<hr>";

// Configuración de conexión
$host = '192.168.3.101';
$user = 'antix';
$pass = '733';
$dbname = 'estacionamiento';
$port = 3306;

echo "<h3>1. Configuración de conexión:</h3>";
echo "Host: $host<br>";
echo "Puerto: $port<br>";
echo "Usuario: $user<br>";
echo "Base de datos: $dbname<br>";
echo "<hr>";

// 1. Prueba de ping a Windows 7
echo "<h3>2. Prueba de conectividad de red:</h3>";
$ping_result = shell_exec("ping -c 3 $host 2>&1");
if ($ping_result) {
    echo "<pre>$ping_result</pre>";
    if (strpos($ping_result, '3 received') !== false) {
        echo "✅ <strong>Ping exitoso</strong> - La red funciona<br>";
    } else {
        echo "❌ <strong>Ping fallido</strong> - Problema de red<br>";
    }
} else {
    echo "⚠️ No se pudo ejecutar ping<br>";
}
echo "<hr>";

// 2. Prueba de conexión MySQL
echo "<h3>3. Prueba de conexión MySQL:</h3>";
try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        echo "❌ <strong>Error de conexión:</strong> " . $conn->connect_error . "<br>";
    } else {
        echo "✅ <strong>Conexión exitosa</strong> a la base de datos<br>";
        echo "Versión MySQL: " . $conn->server_info . "<br>";
        echo "Host info: " . $conn->host_info . "<br>";
        
        // 3. Prueba de consulta
        echo "<h3>4. Prueba de consulta:</h3>";
        $result = $conn->query("SELECT COUNT(*) as total FROM ingresos LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ <strong>Consulta exitosa:</strong> " . $row['total'] . " registros en tabla 'ingresos'<br>";
        } else {
            echo "❌ <strong>Error en consulta:</strong> " . $conn->error . "<br>";
        }
        
        // 4. Verificar tablas principales
        echo "<h3>5. Verificación de tablas:</h3>";
        $tablas_requeridas = ['ingresos', 'salidas', 'tipo_ingreso', 'clientes'];
        foreach ($tablas_requeridas as $tabla) {
            $result = $conn->query("SHOW TABLES LIKE '$tabla'");
            if ($result && $result->num_rows > 0) {
                echo "✅ Tabla '$tabla' existe<br>";
            } else {
                echo "❌ Tabla '$tabla' NO existe<br>";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ <strong>Excepción:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 5. Prueba del archivo de conexión del sistema
echo "<h3>6. Prueba del archivo conexion.php del sistema:</h3>";
try {
    require_once 'conexion.php';
    
    if (isset($conn) && $conn) {
        echo "✅ <strong>conexion.php funciona correctamente</strong><br>";
        echo "Host conectado: " . $conn->host_info . "<br>";
        
        // Probar una consulta simple
        $result = $conn->query("SELECT 1 as test");
        if ($result) {
            echo "✅ <strong>Consulta a través de conexion.php exitosa</strong><br>";
        }
        
        $conn->close();
    } else {
        echo "❌ <strong>conexion.php no estableció conexión</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Error en conexion.php:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>✅ Prueba completada</h3>";
echo "<p><strong>Si todas las pruebas son exitosas, Antix puede conectarse a Windows 7 correctamente.</strong></p>";
?>
