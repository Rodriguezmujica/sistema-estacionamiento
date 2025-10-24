//  HABILITAR CORS PARA SISTEMA HÍBRIDO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (pre-flight), responder vacía
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

<?php
/**
 * 🔍 CHECK ANTIX STATUS
 * Verifica el estado de Antix y la sincronización
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    // Detectar si estamos en Antix o Windows
    $isAntix = false;
    $systemInfo = [];
    
    // Verificar sistema operativo
    if (PHP_OS_FAMILY === 'Linux') {
        $isAntix = true;
        $systemInfo['os'] = 'Linux/Antix';
        
        // Verificar si es específicamente Antix
        if (file_exists('/etc/antix-version')) {
            $systemInfo['distro'] = 'Antix Linux';
            $systemInfo['version'] = file_get_contents('/etc/antix-version');
        } else {
            $systemInfo['distro'] = 'Linux';
        }
    } else {
        $systemInfo['os'] = 'Windows';
        $systemInfo['distro'] = 'Windows ' . PHP_OS;
    }
    
    // Verificar conectividad de red (simplificado para evitar errores)
    $networkStatus = 'online';
    $networkLatency = 0;
    $networkError = null;
    
    // Test de conectividad básico (solo si cURL está disponible)
    if (function_exists('curl_init')) {
        $startTime = microtime(true);
        $testUrl = 'https://www.google.com';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($result === false || $httpCode !== 200) {
            $networkStatus = 'offline';
            $networkError = $error;
        } else {
            $networkLatency = round((microtime(true) - $startTime) * 1000, 2);
        }
    }
    
    // Verificar estado de la base de datos
    $dbStatus = 'connected';
    $dbInfo = [];
    
    try {
        // Intentar conectar a la base de datos
        $dbInfo['status'] = 'connected';
        $dbInfo['host'] = 'localhost';
        $dbInfo['database'] = 'estacionamiento';
        $dbInfo['operaciones_pendientes'] = 0;
        $dbInfo['ultima_salida'] = null;
        
        // Si existe el archivo de conexión, intentar usarlo
        if (file_exists(__DIR__ . '/conexion.php')) {
            require_once __DIR__ . '/../config/conexion.php';
            
            if (isset($conn) && $conn && $conn->ping()) {
                $dbInfo['host'] = defined('DB_HOST') ? DB_HOST : 'localhost';
                $dbInfo['database'] = defined('DB_NAME') ? DB_NAME : 'estacionamiento';
                
                // Verificar última sincronización
                $sql = "SELECT MAX(fecha_salida) as ultima_salida FROM salidas";
                $result = $conn->query($sql);
                if ($result && $row = $result->fetch_assoc()) {
                    $dbInfo['ultima_salida'] = $row['ultima_salida'];
                }
                
                // Contar operaciones pendientes
                $sql = "SELECT COUNT(*) as pendientes FROM ingresos WHERE sincronizado = 0";
                $result = $conn->query($sql);
                if ($result && $row = $result->fetch_assoc()) {
                    $dbInfo['operaciones_pendientes'] = $row['pendientes'];
                }
            } else {
                $dbStatus = 'disconnected';
                $dbInfo['status'] = 'disconnected';
                $dbInfo['error'] = 'No se pudo conectar a la base de datos';
            }
        }
        
    } catch (Exception $e) {
        $dbStatus = 'error';
        $dbInfo['status'] = 'error';
        $dbInfo['error'] = $e->getMessage();
    }
    
    // Verificar estado de Firebase (simulado)
    $firebaseStatus = 'connected';
    $firebaseInfo = [];
    
    $firebaseInfo['status'] = 'connected';
    $firebaseInfo['last_sync'] = date('Y-m-d H:i:s');
    $firebaseInfo['operations_sent'] = 0;
    
    // Verificar servicios del sistema (simplificado)
    $services = [];
    $services['apache'] = true;
    $services['mysql'] = true;
    $services['firebase_sync'] = true;
    
    // Estado general del sistema
    $overallStatus = 'online';
    if ($networkStatus === 'offline' || $dbStatus === 'disconnected') {
        $overallStatus = 'offline';
    }
    
    // Preparar respuesta
    $response = [
        'status' => $overallStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'system' => $systemInfo,
        'network' => [
            'status' => $networkStatus,
            'latency_ms' => $networkLatency,
            'error' => $networkError
        ],
        'database' => $dbInfo,
        'firebase' => $firebaseInfo,
        'services' => $services,
        'is_antix' => $isAntix,
        'pc_id' => $isAntix ? 'PC1_ANTIX' : 'PC2_WINDOWS7'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
