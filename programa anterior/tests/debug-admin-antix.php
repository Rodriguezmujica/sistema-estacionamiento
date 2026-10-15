<?php
/**
 * 🔍 DIAGNÓSTICO ESPECÍFICO PARA ANTIX
 * Para identificar problemas con admin.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>🔍 Diagnóstico Admin.php - Antix</h2>";
echo "<hr>";

// 1. Verificar sistema operativo
echo "<h3>1. Sistema Operativo</h3>";
echo "OS: " . PHP_OS . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Directorio actual: " . __DIR__ . "<br>";
echo "<hr>";

// 2. Verificar archivos críticos
echo "<h3>2. Archivos Críticos</h3>";
$archivos = [
    'conexion.php',
    'api/api_resumen_ejecutivo.php',
    'secciones/admin.php',
    'JS/admin.js'
];

foreach ($archivos as $archivo) {
    $existe = file_exists($archivo);
    $legible = $existe ? is_readable($archivo) : false;
    echo "📁 $archivo: " . ($existe ? "✅ Existe" : "❌ No existe");
    if ($existe) {
        echo " - " . ($legible ? "✅ Legible" : "❌ No legible");
    }
    echo "<br>";
}
echo "<hr>";

// 3. Verificar conexión a base de datos
echo "<h3>3. Conexión Base de Datos</h3>";
try {
    require_once '../config/conexion.php';
    
    if (isset($conn) && $conn) {
        echo "✅ Conexión establecida<br>";
        echo "Host: " . $conn->host_info . "<br>";
        
        // Probar consulta simple
        $result = $conn->query("SELECT COUNT(*) as total FROM ingresos LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Consulta de prueba exitosa: " . $row['total'] . " registros en ingresos<br>";
        } else {
            echo "❌ Error en consulta de prueba: " . $conn->error . "<br>";
        }
    } else {
        echo "❌ No se pudo establecer conexión<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// 4. Verificar API específicamente
echo "<h3>4. Prueba API Resumen Ejecutivo</h3>";
$url_api = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api/api_resumen_ejecutivo.php?mes=" . date('n') . "&anio=" . date('Y');

echo "URL API: <a href='$url_api' target='_blank'>$url_api</a><br>";

// Probar con cURL si está disponible
if (function_exists('curl_init')) {
    echo "Probando con cURL...<br>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Error cURL: $error<br>";
    } else {
        echo "✅ Respuesta HTTP: $http_code<br>";
        if ($http_code == 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                echo "✅ API responde correctamente<br>";
                echo "Datos recibidos: " . count($data) . " campos<br>";
            } else {
                echo "❌ API no responde JSON válido<br>";
                echo "Respuesta: " . substr($response, 0, 200) . "...<br>";
            }
        } else {
            echo "❌ API devuelve código de error: $http_code<br>";
        }
    }
} else {
    echo "⚠️ cURL no disponible<br>";
}
echo "<hr>";

// 5. Verificar permisos de directorios
echo "<h3>5. Permisos de Directorios</h3>";
$directorios = [
    '.',
    'api',
    'secciones',
    'JS'
];

foreach ($directorios as $dir) {
    if (is_dir($dir)) {
        $permisos = substr(sprintf('%o', fileperms($dir)), -4);
        $escribible = is_writable($dir);
        echo "📁 $dir: permisos $permisos - " . ($escribible ? "✅ Escribible" : "❌ No escribible") . "<br>";
    } else {
        echo "📁 $dir: ❌ No existe<br>";
    }
}
echo "<hr>";

// 6. Verificar configuración PHP
echo "<h3>6. Configuración PHP</h3>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ Habilitado' : '❌ Deshabilitado') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "s<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "error_reporting: " . ini_get('error_reporting') . "<br>";
echo "<hr>";

echo "<h3>✅ Diagnóstico completado</h3>";
echo "<p><strong>Instrucciones:</strong></p>";
echo "<ol>";
echo "<li>Ejecuta este archivo en Antix</li>";
echo "<li>Revisa cada sección para identificar problemas</li>";
echo "<li>Comparte los resultados para diagnosticar el problema</li>";
echo "</ol>";
?>
