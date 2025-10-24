<?php
/**
 * 📝 SISTEMA DE LOGGING PARA DEPURACIÓN
 * 
 * Incluye este archivo en tus scripts para registrar eventos:
 * require_once 'debug_logger.php';
 * DebugLogger::log('info', 'Usuario ingresó patente ABC123');
 */

class DebugLogger {
    private static $log_file = __DIR__ . '/logs/debug.log';
    private static $max_file_size = 5242880; // 5MB
    private static $enabled = true;
    
    /**
     * Tipos de log disponibles
     */
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const DEBUG = 'DEBUG';
    const SQL = 'SQL';
    const API = 'API';
    const PRINT = 'PRINT';
    
    /**
     * Inicializar el logger
     */
    public static function init() {
        // Crear carpeta de logs si no existe
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        // Rotar logs si son muy grandes
        self::rotateLogIfNeeded();
    }
    
    /**
     * Registrar un mensaje
     */
    public static function log($tipo, $mensaje, $datos = null) {
        if (!self::$enabled) return;
        
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $usuario = $_SESSION['usuario'] ?? 'GUEST';
        
        // Formatear mensaje
        $log_entry = sprintf(
            "[%s] [%s] [%s] [%s] %s",
            $timestamp,
            $tipo,
            $ip,
            $usuario,
            $mensaje
        );
        
        // Agregar datos adicionales si existen
        if ($datos !== null) {
            $log_entry .= " | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE);
        }
        
        $log_entry .= PHP_EOL;
        
        // Escribir al archivo
        @file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // También mostrar en consola si estamos en modo debug
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<pre style='background:#f8f9fa;padding:5px;border-left:3px solid #007bff;margin:2px;'>";
            echo htmlspecialchars($log_entry);
            echo "</pre>";
        }
    }
    
    /**
     * Atajos para diferentes tipos de log
     */
    public static function info($mensaje, $datos = null) {
        self::log(self::INFO, $mensaje, $datos);
    }
    
    public static function warning($mensaje, $datos = null) {
        self::log(self::WARNING, $mensaje, $datos);
    }
    
    public static function error($mensaje, $datos = null) {
        self::log(self::ERROR, $mensaje, $datos);
    }
    
    public static function debug($mensaje, $datos = null) {
        self::log(self::DEBUG, $mensaje, $datos);
    }
    
    public static function sql($query, $tiempo = null) {
        $mensaje = "Query: $query";
        if ($tiempo) {
            $mensaje .= " (Tiempo: {$tiempo}ms)";
        }
        self::log(self::SQL, $mensaje);
    }
    
    public static function api($endpoint, $metodo, $datos = null) {
        self::log(self::API, "$metodo $endpoint", $datos);
    }
    
    public static function print_ticket($patente, $exito = true) {
        $estado = $exito ? 'EXITOSA' : 'FALLIDA';
        self::log(self::PRINT, "Impresión $estado para patente: $patente");
    }
    
    /**
     * Rotar logs si son muy grandes
     */
    private static function rotateLogIfNeeded() {
        if (file_exists(self::$log_file) && filesize(self::$log_file) > self::$max_file_size) {
            $backup_file = self::$log_file . '.' . date('Ymd_His') . '.bak';
            @rename(self::$log_file, $backup_file);
        }
    }
    
    /**
     * Obtener últimas líneas del log
     */
    public static function getRecentLogs($lineas = 50) {
        if (!file_exists(self::$log_file)) {
            return [];
        }
        
        $file = new SplFileObject(self::$log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $last_line = $file->key();
        
        $lines = [];
        $start = max(0, $last_line - $lineas);
        
        for ($i = $start; $i <= $last_line; $i++) {
            $file->seek($i);
            $line = $file->current();
            if (!empty(trim($line))) {
                $lines[] = $line;
            }
        }
        
        return $lines;
    }
    
    /**
     * Limpiar logs antiguos
     */
    public static function clearLogs() {
        if (file_exists(self::$log_file)) {
            @unlink(self::$log_file);
        }
        self::log(self::INFO, "Logs limpiados");
    }
    
    /**
     * Deshabilitar logging (para producción)
     */
    public static function disable() {
        self::$enabled = false;
    }
    
    /**
     * Habilitar logging
     */
    public static function enable() {
        self::$enabled = true;
    }
    
    /**
     * Registrar excepción
     */
    public static function exception($exception) {
        self::error(
            "Exception: " . $exception->getMessage(),
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );
    }
    
    /**
     * Medir tiempo de ejecución de una función
     */
    public static function measureTime($nombre, callable $funcion) {
        $inicio = microtime(true);
        
        try {
            $resultado = $funcion();
            $fin = microtime(true);
            $tiempo = round(($fin - $inicio) * 1000, 2);
            
            self::debug("$nombre completado en {$tiempo}ms");
            
            return $resultado;
        } catch (Exception $e) {
            $fin = microtime(true);
            $tiempo = round(($fin - $inicio) * 1000, 2);
            
            self::error("$nombre falló después de {$tiempo}ms: " . $e->getMessage());
            throw $e;
        }
    }
}

// Auto-inicializar
DebugLogger::init();

/**
 * Funciones helper globales para facilitar el uso
 */
if (!function_exists('debug_log')) {
    function debug_log($mensaje, $datos = null) {
        DebugLogger::debug($mensaje, $datos);
    }
}

if (!function_exists('error_log_custom')) {
    function error_log_custom($mensaje, $datos = null) {
        DebugLogger::error($mensaje, $datos);
    }
}

if (!function_exists('info_log')) {
    function info_log($mensaje, $datos = null) {
        DebugLogger::info($mensaje, $datos);
    }
}

?>


