<?php
/**
 * 🔌 ARCHIVO DE CONEXIÓN UNIFICADO
 * Compatible con Windows (XAMPP) y Linux
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// ============================================
// CARGAR CONFIGURACIÓN
// ============================================
// Si existe config.php (personalizado), usarlo
// Si no, usar configuración por defecto detectando el sistema operativo
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
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
        // Configuración para Linux (PRODUCCIÓN)
        // ⚠️ IMPORTANTE: Debes crear un archivo config.php
        // Copia config.php.example y renómbralo a config.php
        $host = 'localhost';
        $user = 'estacionamiento_user';  // 🔧 Usuario específico para Linux
        $pass = 'CAMBIAR_ESTA_CONTRASEÑA';  // 🔧 ¡DEBES CAMBIAR ESTO!
        $dbname = 'estacionamiento';
        $port = 3306;
        
        // Advertencia si no hay config.php en Linux
        if ($pass === 'CAMBIAR_ESTA_CONTRASEÑA') {
            error_log("⚠️ ADVERTENCIA: Usando contraseña por defecto. Crea config.php desde config.php.example");
        }
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
        
        // Configurar zona horaria de MySQL
        $conn->query("SET time_zone = '-03:00'");
        
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