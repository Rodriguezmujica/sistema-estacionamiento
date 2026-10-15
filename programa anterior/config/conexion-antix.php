<?php
/**
 * 🔌 ARCHIVO DE CONEXIÓN PARA ANTIX
 * Conecta remotamente a servidor Windows 7
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// ============================================
// CONFIGURACIÓN ESPECÍFICA PARA ANTIX
// ============================================
date_default_timezone_set('America/Santiago');

// Configuración de conexión remota a Windows 7
$host = '192.168.3.101';  // IP de Windows 7
$user = 'antix';          // Usuario MySQL en Windows 7
$pass = '733';            // Contraseña MySQL
$dbname = 'estacionamiento';
$port = 3306;

// Log de conexión remota
error_log("🔌 ANTIX: Conectando a servidor Windows 7 en $host:$port");

// ============================================
// CONEXIÓN MYSQLI ROBUSTA
// ============================================

$conn = null;

try {
    // Crear conexión con timeout extendido para conexión remota
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    // Configurar timeout para conexión remota
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    $conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);
    
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a Windows 7: " . $conn->connect_error);
    }
    
    // Configurar charset
    $conn->set_charset("utf8mb4");
    
    // Log de conexión exitosa
    error_log("✅ ANTIX: Conectado exitosamente a Windows 7 ($host:$port)");
    
} catch (Exception $e) {
    error_log("❌ ANTIX: Error conectando a Windows 7: " . $e->getMessage());
    
    // En caso de error, intentar conexión local como fallback
    try {
        $conn = new mysqli('localhost', 'root', '', 'estacionamiento', 3306);
        if ($conn->connect_error) {
            throw new Exception("Error en conexión local de fallback: " . $conn->connect_error);
        }
        error_log("⚠️ ANTIX: Usando conexión local de fallback");
    } catch (Exception $e2) {
        error_log("❌ ANTIX: Error crítico - No se pudo conectar ni remotamente ni localmente");
        die("Error de conexión a la base de datos");
    }
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Verificar si la conexión está activa
 */
function verificarConexion() {
    global $conn;
    if (!$conn || $conn->connect_error) {
        return false;
    }
    
    // Probar con una consulta simple
    $result = $conn->query("SELECT 1");
    return $result !== false;
}

/**
 * Obtener información de la conexión
 */
function getInfoConexion() {
    global $conn;
    if (!$conn) return "Sin conexión";
    
    return [
        'host' => $conn->host_info,
        'server' => $conn->server_info,
        'charset' => $conn->character_set_name()
    ];
}

// ============================================
// CONFIGURACIÓN ADICIONAL
// ============================================

// Configurar zona horaria en MySQL
if ($conn) {
    $conn->query("SET time_zone = '-03:00'");
}

// Log final
error_log("🔌 ANTIX: Sistema listo - Conectado a Windows 7 ($host:$port)");
?>
