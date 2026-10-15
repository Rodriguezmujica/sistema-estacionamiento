<?php
/**
 * 🔌 ARCHIVO DE CONEXIÓN UNIFICADO
 * Compatible con Windows (XAMPP) y Linux
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

if (!function_exists('setMySQLChileTimezone')) {
    function setMySQLChileTimezone($conn) {
        // 1) Intentar zona IANA (requiere tablas timezone cargadas en MySQL/MariaDB)
        if ($conn->query("SET time_zone = 'America/Santiago'")) {
            return true;
        }
    
        // 2) Fallback seguro: offset actual de Chile calculado por PHP (considera DST actual)
        $tz = new DateTimeZone('America/Santiago');
        $now = new DateTime('now', $tz);
        $offsetSeconds = $tz->getOffset($now);
        $sign = $offsetSeconds >= 0 ? '+' : '-';
        $abs = abs($offsetSeconds);
        $hours = floor($abs / 3600);
        $minutes = floor(($abs % 3600) / 60);
        $offset = sprintf("%s%02d:%02d", $sign, $hours, $minutes);
    
        return (bool)$conn->query("SET time_zone = '{$offset}'");
    }
}

// ============================================
// CARGAR CONFIGURACIÓN
// ============================================
// Si existe config.php (personalizado), usarlo
// Si no, usar configuración por defecto detectando el sistema operativo
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    $dbname = DB_NAME;
    $port = DB_PORT ?? 3306;
    date_default_timezone_set(TIMEZONE ?? 'America/Santiago');
} else {
    // ============================================
    // CONFIGURACIÓN AUTOMÁTICA POR SISTEMA OPERATIVO
    // ============================================
    date_default_timezone_set('America/Santiago');
    
    // Detectar si es Windows o Linux
    $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($is_windows) {
        // Configuración para Windows (XAMPP)
        $host = 'localhost';
        $user = 'root';
        $pass = '';  // XAMPP por defecto no tiene contraseña
        $dbname = 'estacionamiento';
        $port = 3306;
    } else {
        // Configuración para Linux:
        // 1) Variables de entorno (producción)
        // 2) Fallback seguro para entornos locales
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USER') ?: 'root';
        // Mantener compatibilidad con el fallback histórico (conexion-ejemplo.php)
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'losrios733';
        $dbname = getenv('DB_NAME') ?: 'estacionamiento';
        $port = intval(getenv('DB_PORT') ?: 3306);
        
        // Log de conexión (sin exponer credenciales)
        error_log("🔌 LINUX: Conectando a DB en $host:$port ($dbname)");
    }
}

// ============================================
// CONEXIÓN MYSQLI ROBUSTA
// ============================================
// Configuraciones específicas para Windows XAMPP
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

try {
    // Timeout más largo para Windows (a veces es lento al iniciar)
    $conn = new mysqli();
    if ($is_windows) {
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);
        $conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);
    }
    
    $conn->connect($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        error_log("Error de conexión DB: " . $conn->connect_error);
        // No hacer die() aquí para permitir manejo en archivos que incluyen conexion.php
        // Los archivos que incluyen este archivo deben verificar $conn->connect_error
    } else {
        // Configurar charset
        $conn->set_charset('utf8mb4');
        
        // Configurar zona horaria de MySQL (con fallback si no existen tablas de timezone)
        setMySQLChileTimezone($conn);
        
        // Configuraciones adicionales para estabilidad en Windows
        if ($is_windows) {
            // Mantener conexión más estable
            $conn->query("SET SESSION wait_timeout = 28800"); // 8 horas
            $conn->query("SET SESSION interactive_timeout = 28800");
        }
    }
} catch (Exception $e) {
    error_log("Excepción en conexión DB: " . $e->getMessage());
    $conn = null;
}

// ============================================
// COMPATIBILIDAD: Variable alternativa
// ============================================
// Algunos archivos antiguos usan $conexion en lugar de $conn
$conexion = $conn;
