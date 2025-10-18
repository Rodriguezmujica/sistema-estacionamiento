<?php
/**
 * 🔄 SISTEMA DE BACKUP AUTOMÁTICO
 * 
 * Este script crea backups automáticos de la base de datos
 * Ejecutar diariamente con Windows Task Scheduler o cron (Linux)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Santiago');

// ============================================
// CONFIGURACIÓN
// ============================================

// Detectar sistema operativo
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// Configuración de base de datos
if ($is_windows) {
    // Windows (XAMPP)
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'estacionamiento';
    $MYSQL_PATH = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    $BACKUP_DIR = __DIR__ . '\\backups\\';
} else {
    // Linux
    $DB_HOST = 'localhost';
    $DB_USER = 'estacionamiento_user';
    $DB_PASS = 'losrios733'; // ← Cambiar en producción
    $DB_NAME = 'estacionamiento';
    $MYSQL_PATH = '/usr/bin/mysqldump';
    $BACKUP_DIR = __DIR__ . '/backups/';
}

// Configuración de retención
$DIAS_MANTENER_BACKUPS = 30; // Mantener backups por 30 días
$MAX_BACKUPS = 100; // Máximo de archivos antes de limpiar

// ============================================
// CREAR CARPETA DE BACKUPS
// ============================================

if (!is_dir($BACKUP_DIR)) {
    mkdir($BACKUP_DIR, 0755, true);
    echo "✅ Carpeta de backups creada: $BACKUP_DIR\n";
}

// ============================================
// GENERAR NOMBRE DE ARCHIVO
// ============================================

$fecha = date('Y-m-d_H-i-s');
$nombre_archivo = "estacionamiento_backup_{$fecha}.sql";
$ruta_completa = $BACKUP_DIR . $nombre_archivo;

echo "🔄 Iniciando backup...\n";
echo "📁 Archivo: $nombre_archivo\n";

// ============================================
// CREAR BACKUP
// ============================================

if ($is_windows) {
    // Comando para Windows
    $comando = "\"{$MYSQL_PATH}\" --user={$DB_USER} --host={$DB_HOST} {$DB_NAME} > \"{$ruta_completa}\"";
} else {
    // Comando para Linux
    $comando = "{$MYSQL_PATH} --user={$DB_USER} --password={$DB_PASS} --host={$DB_HOST} {$DB_NAME} > \"{$ruta_completa}\"";
}

// Ejecutar
exec($comando, $output, $return_code);

if ($return_code === 0 && file_exists($ruta_completa)) {
    $tamano = filesize($ruta_completa);
    $tamano_mb = round($tamano / 1024 / 1024, 2);
    
    echo "✅ Backup creado exitosamente\n";
    echo "📊 Tamaño: {$tamano_mb} MB\n";
    echo "📍 Ubicación: {$ruta_completa}\n";
    
    // Comprimir el archivo (opcional)
    if (function_exists('gzopen')) {
        $archivo_gz = $ruta_completa . '.gz';
        $fp_in = fopen($ruta_completa, 'rb');
        $fp_out = gzopen($archivo_gz, 'wb9');
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 1024 * 512));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
        
        // Borrar archivo sin comprimir
        unlink($ruta_completa);
        
        $tamano_gz = filesize($archivo_gz);
        $tamano_gz_mb = round($tamano_gz / 1024 / 1024, 2);
        
        echo "🗜️ Archivo comprimido: {$tamano_gz_mb} MB\n";
        echo "💾 Ubicación: {$archivo_gz}\n";
    }
    
} else {
    echo "❌ ERROR al crear backup\n";
    echo "Código de error: {$return_code}\n";
    exit(1);
}

// ============================================
// LIMPIAR BACKUPS ANTIGUOS
// ============================================

echo "\n🧹 Limpiando backups antiguos...\n";

$archivos = glob($BACKUP_DIR . "estacionamiento_backup_*.sql*");
$archivos_eliminados = 0;
$fecha_limite = strtotime("-{$DIAS_MANTENER_BACKUPS} days");

foreach ($archivos as $archivo) {
    $fecha_archivo = filemtime($archivo);
    
    if ($fecha_archivo < $fecha_limite) {
        unlink($archivo);
        $archivos_eliminados++;
        echo "🗑️ Eliminado: " . basename($archivo) . "\n";
    }
}

if ($archivos_eliminados > 0) {
    echo "✅ Se eliminaron {$archivos_eliminados} backups antiguos\n";
} else {
    echo "ℹ️ No hay backups antiguos para eliminar\n";
}

// ============================================
// RESUMEN
// ============================================

$total_backups = count(glob($BACKUP_DIR . "estacionamiento_backup_*.sql*"));

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMEN\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Backup completado exitosamente\n";
echo "📁 Total de backups: {$total_backups}\n";
echo "🕐 Fecha: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n";

// ============================================
// ESCRIBIR LOG
// ============================================

$log_file = $BACKUP_DIR . 'backup.log';
$log_entry = date('[Y-m-d H:i:s]') . " Backup exitoso: {$nombre_archivo}\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

?>

