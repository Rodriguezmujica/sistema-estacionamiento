<?php
/**
 * ⚡ CONFIGURACIÓN RÁPIDA DE BACKUPS
 * Script para configurar backups automáticos y evitar pérdida de datos
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>⚡ Configurar Backups</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
        .success { color: #28a745; font-weight: bold; }
        .info { color: #17a2b8; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: black; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='card'>
        <h1>⚡ Configuración Rápida de Backups</h1>
        <p class='info'>Este script te ayudará a configurar backups automáticos para evitar que se borre la base de datos.</p>";

$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($is_windows) {
    echo "<h2>🪟 Configuración para Windows</h2>";
    
    // Crear backup inmediato
    if (isset($_POST['crear_backup_ahora'])) {
        echo "<h3>🔄 Creando backup inmediato...</h3>";
        
        require_once __DIR__ . '/backup_automatico.php';
        
        echo "<p class='success'>✅ Backup creado. Ahora configura el automático:</p>";
        
        echo "<div class='card'>
                <h3>📋 Pasos para configurar backup automático en Windows:</h3>
                <ol>
                    <li>Presiona <strong>Windows + R</strong></li>
                    <li>Escribe: <code>taskschd.msc</code> y presiona Enter</li>
                    <li>Click en <strong>'Crear tarea básica...'</strong></li>
                    <li>Nombre: <code>Backup Estacionamiento</code></li>
                    <li>Descripción: <code>Backup automático diario</code></li>
                    <li>Frecuencia: <strong>Diariamente</strong></li>
                    <li>Hora: <strong>23:00</strong> (11 PM)</li>
                    <li>Acción: <strong>'Iniciar un programa'</strong></li>
                    <li>Programa: <code>C:\\xampp\\php\\php.exe</code></li>
                    <li>Argumentos: <code>C:\\xampp\\htdocs\\sistemaEstacionamiento\\backup_automatico.php</code></li>
                    <li>Finalizar y marcar <strong>'Ejecutar aunque el usuario no haya iniciado sesión'</strong></li>
                </ol>
              </div>";
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit' name='crear_backup_ahora' class='btn btn-success'>🔄 Crear Backup Ahora</button>";
        echo "</form>";
    }
    
    // Verificar si ya existen backups
    $backup_dir = __DIR__ . '\\backups\\';
    if (is_dir($backup_dir)) {
        $archivos = glob($backup_dir . "*.sql*");
        echo "<p class='success'>✅ Carpeta de backups existe con " . count($archivos) . " archivos</p>";
    } else {
        echo "<p class='info'>ℹ️ Se creará la carpeta de backups al ejecutar el primer backup</p>";
    }
    
} else {
    echo "<h2>🐧 Configuración para Linux</h2>";
    
    echo "<div class='card'>
            <h3>📋 Para configurar en Linux:</h3>
            <pre># Editar crontab
crontab -e

# Agregar esta línea (backup diario a las 2 AM):
0 2 * * * /usr/bin/php /var/www/html/sistemaEstacionamiento/backup_automatico.php >> /var/log/backup_estacionamiento.log 2>&1</pre>
          </div>";
}

echo "<div class='card'>
        <h3>🛡️ Protecciones adicionales:</h3>
        <ul>
            <li><strong>Backups automáticos diarios</strong> (configurar arriba)</li>
            <li><strong>Proteger carpeta backups/</strong> - Nunca la borres</li>
            <li><strong>Cerrar MySQL correctamente</strong> - Siempre usa 'Stop' en XAMPP</li>
            <li><strong>Verificar backups</strong> - Revisar una vez por semana</li>
        </ul>
        
        <h3>🚨 En caso de emergencia:</h3>
        <ul>
            <li><a href='restaurar_backup.php' class='btn btn-warning'>Restaurar desde Backup</a></li>
            <li><a href='restaurar_bd_emergencia.php' class='btn'>Restaurar desde SQL</a></li>
        </ul>
      </div>";

echo "</div></body></html>";
?>
