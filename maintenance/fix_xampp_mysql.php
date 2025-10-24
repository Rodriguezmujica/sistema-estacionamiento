<?php
/**
 * 🔧 FIX ESPECÍFICO PARA XAMPP WINDOWS
 * Soluciona problemas comunes de corrupción de MySQL en Windows
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>🔧 Fix XAMPP MySQL</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-danger { background: #dc3545; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='card'>
        <h1>🔧 Fix Específico para XAMPP Windows</h1>
        <p class='info'>Este script te ayudará a configurar XAMPP para que sea más estable</p>";

// Verificar que estamos en Windows
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if (!$is_windows) {
    echo "<p class='warning'>⚠️ Este script es específico para Windows. En Linux usa las configuraciones normales.</p>";
    exit;
}

echo "<h2>🛡️ Configuraciones para evitar corrupción:</h2>";

// Verificar configuración actual de MySQL
$myini_path = 'C:\\xampp\\mysql\\bin\\my.ini';
if (file_exists($myini_path)) {
    echo "<p class='success'>✅ Archivo my.ini encontrado</p>";
    
    $myini_content = file_get_contents($myini_path);
    
    // Verificar configuraciones importantes
    $configs_necesarias = [
        'innodb_buffer_pool_size' => '128M',
        'innodb_log_file_size' => '16M',
        'innodb_flush_log_at_trx_commit' => '2',
        'sync_binlog' => '0'
    ];
    
    echo "<h3>📋 Configuraciones recomendadas para estabilidad:</h3>";
    echo "<p>Agrega estas líneas a <code>C:\\xampp\\mysql\\bin\\my.ini</code> en la sección <code>[mysqld]</code>:</p>";
    
    echo "<pre>";
    foreach ($configs_necesarias as $config => $valor) {
        if (strpos($myini_content, $config) === false) {
            echo $config . " = " . $valor . "\n";
        }
    }
    echo "</pre>";
    
    echo "<p class='info'>💡 Estas configuraciones hacen que MySQL sea menos propenso a corromperse</p>";
    
} else {
    echo "<p class='error'>❌ No se encontró my.ini en la ubicación esperada</p>";
}

echo "<div class='card'>
        <h3>🚨 SOLUCIÓN INMEDIATA (si MySQL sigue fallando):</h3>
        <ol>
            <li><strong>CERRAR XAMPP completamente</strong></li>
            <li>Ir a <code>C:\\xampp\\mysql\\data\\</code></li>
            <li><strong>MOVER</strong> (no eliminar) estos archivos a una carpeta temporal:
                <ul>
                    <li>ibdata1</li>
                    <li>ib_logfile0</li>
                    <li>ib_logfile1</li>
                </ul>
            </li>
            <li><strong>COPIAR</strong> los mismos archivos desde <code>C:\\xampp\\mysql\\backup\\</code></li>
            <li>Pegar en <code>C:\\xampp\\mysql\\data\\</code></li>
            <li>Iniciar MySQL en XAMPP</li>
            <li><a href='restaurar_bd_emergencia.php' class='btn'>Restaurar Base de Datos</a></li>
        </ol>
      </div>";

echo "<div class='card'>
        <h3>⚙️ CONFIGURAR BACKUPS AUTOMÁTICOS:</h3>
        <p>Para evitar pérdidas futuras, configura backups automáticos:</p>
        <ol>
            <li>Presiona <strong>Windows + R</strong></li>
            <li>Escribe: <code>taskschd.msc</code></li>
            <li>Crear tarea básica: <strong>\"Backup Estacionamiento\"</strong></li>
            <li>Frecuencia: <strong>Diariamente a las 23:00</strong></li>
            <li>Programa: <code>C:\\xampp\\php\\php.exe</code></li>
            <li>Argumentos: <code>C:\\xampp\\htdocs\\sistemaEstacionamiento\\backup_automatico.php</code></li>
        </ol>
        
        <a href='configurar_backups_rapido.php' class='btn btn-danger'>🚀 Configurar Backups Ahora</a>
      </div>";

echo "<div class='card'>
        <h3>💡 BUENAS PRÁCTICAS para Windows:</h3>
        <ul>
            <li>✅ <strong>NUNCA</strong> cerrar Windows sin usar 'Stop' en XAMPP</li>
            <li>✅ Agregar <code>C:\\xampp\\</code> a excepciones del antivirus</li>
            <li>✅ Mantener mínimo 2GB libres en C:\\ (para logs temporales)</li>
            <li>✅ Hacer backup antes de actualizaciones grandes</li>
            <li>✅ Revisar que no haya otro MySQL corriendo (puerto 3306)</li>
        </ul>
      </div>";

echo "</div></body></html>";
?>
