<?php
/**
 * 🐧 ARCHIVO DE CONEXIÓN PARA LINUX
 * 
 * Este archivo está optimizado para servidores Linux
 * Compatible con mysqli Y PDO (puedes usar ambos)
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

if (!function_exists('getChileOffsetForMySQL')) {
    function getChileOffsetForMySQL() {
        $tz = new DateTimeZone('America/Santiago');
        $now = new DateTime('now', $tz);
        $offsetSeconds = $tz->getOffset($now);
        $sign = $offsetSeconds >= 0 ? '+' : '-';
        $abs = abs($offsetSeconds);
        $hours = floor($abs / 3600);
        $minutes = floor(($abs % 3600) / 60);
        return sprintf("%s%02d:%02d", $sign, $hours, $minutes);
    }
}

// ============================================
// CONFIGURACIÓN DE ZONA HORARIA
// ============================================
date_default_timezone_set('America/Santiago');

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

$host = 'localhost';  // O '127.0.0.1'
$db = 'estacionamiento';  // Nombre de la base de datos
$user = 'estacionamiento_user';  // Usuario (NO uses 'root')
$pass = 'tu_clave_segura_aqui';  // ⚠️ CAMBIA ESTO
$charset = 'utf8mb4';  // Charset recomendado

// ============================================
// OPCIÓN 1: MYSQLI (tu sistema actual usa esto)
// ============================================

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // En producción, NO mostrar detalles del error
    error_log("Error de conexión DB: " . $conn->connect_error);
    die(json_encode([
        'success' => false, 
        'error' => 'Error de conexión a la base de datos'
    ]));
}

// Configurar charset
$conn->set_charset($charset);

// Configurar zona horaria de MySQL con fallback seguro
if (!$conn->query("SET time_zone = 'America/Santiago'")) {
    $offset = getChileOffsetForMySQL();
    $conn->query("SET time_zone = '{$offset}'");
}

// ============================================
// OPCIÓN 2: PDO (si te lo piden específicamente)
// ============================================

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Configurar zona horaria con fallback seguro
    try {
        $pdo->exec("SET time_zone = 'America/Santiago'");
    } catch (Exception $e) {
        $offset = getChileOffsetForMySQL();
        $pdo->exec("SET time_zone = '{$offset}'");
    }
    
} catch (PDOException $e) {
    error_log("Error de conexión PDO: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos'
    ]));
}

// ============================================
// DIFERENCIAS LINUX VS WINDOWS
// ============================================

/*
 * 1. RUTAS DE ARCHIVOS:
 *    Windows: C:\xampp\htdocs\...
 *    Linux:   /var/www/html/...
 * 
 * 2. PERMISOS:
 *    Linux requiere configurar permisos correctamente:
 *    - Archivos PHP: 644 (rw-r--r--)
 *    - Carpetas: 755 (rwxr-xr-x)
 *    - Logs: 666 (rw-rw-rw-)
 * 
 * 3. CASE SENSITIVE:
 *    Linux distingue entre mayúsculas y minúsculas:
 *    - "Conexion.php" ≠ "conexion.php"
 *    - "INGRESOS" ≠ "ingresos" (en nombres de tablas)
 * 
 * 4. USUARIO DE MYSQL:
 *    NO uses 'root'. Crea un usuario específico:
 *    
 *    CREATE USER 'estacionamiento_user'@'localhost' 
 *    IDENTIFIED BY 'tu_clave_segura';
 *    
 *    GRANT ALL PRIVILEGES ON estacionamiento.* 
 *    TO 'estacionamiento_user'@'localhost';
 *    
 *    FLUSH PRIVILEGES;
 */

// ============================================
// VARIABLE GLOBAL (para compatibilidad)
// ============================================

// Si tu código usa $conexion en lugar de $conn
$conexion = $conn;

?>

