<?php
/**
 * 🔥 RESTAURACIÓN DE EMERGENCIA
 * Script para restaurar la BD desde estacionamiento.sql
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>🔥 Restauración de Emergencia</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .btn { background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class='card'>
        <h1>🔥 Restauración de Emergencia de Base de Datos</h1>
        <p><strong>Este script restaurará la base de datos desde estacionamiento.sql</strong></p>";
        
// Verificar si existe el archivo SQL
if (!file_exists(__DIR__ . '/estacionamiento.sql')) {
    echo "<p class='error'>❌ No se encuentra estacionamiento.sql</p>";
    echo "</div></body></html>";
    exit;
}

// Configuración para Windows XAMPP
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($is_windows) {
    $MYSQL_PATH = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    $DB_USER = 'root';
    $DB_PASS = '';
} else {
    $MYSQL_PATH = '/usr/bin/mysql';
    $DB_USER = 'estacionamiento_user';
    $DB_PASS = 'CAMBIAR_CONTRASEÑA';
}

$DB_HOST = 'localhost';
$DB_NAME = 'estacionamiento';
$SQL_FILE = __DIR__ . '/estacionamiento.sql';

// Verificar si se solicita la restauración
if (!isset($_POST['confirmar'])) {
    echo "<p class='warning'>⚠️ <strong>ADVERTENCIA:</strong> Esto reemplazará completamente la base de datos actual.</p>";
    echo "<p>Archivo SQL: " . basename($SQL_FILE) . "</p>";
    echo "<p>Tamaño: " . round(filesize($SQL_FILE) / 1024 / 1024, 2) . " MB</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='confirmar' class='btn'>🔥 RESTAURAR AHORA</button>";
    echo "</form>";
} else {
    echo "<h2>🔄 Restaurando base de datos...</h2>";
    
    // Crear la base de datos si no existe
    $create_db_command = "\"{$MYSQL_PATH}\" --user={$DB_USER} --host={$DB_HOST} -e \"CREATE DATABASE IF NOT EXISTS {$DB_NAME}\"";
    
    echo "<p>📋 Creando base de datos si no existe...</p>";
    exec($create_db_command, $output_create, $return_code_create);
    
    if ($return_code_create !== 0) {
        echo "<p class='error'>❌ Error creando la base de datos</p>";
    } else {
        echo "<p class='success'>✅ Base de datos lista</p>";
        
        // Comando de restauración
        if ($is_windows) {
            $comando = "\"{$MYSQL_PATH}\" --user={$DB_USER} --host={$DB_HOST} {$DB_NAME} < \"{$SQL_FILE}\"";
        } else {
            $comando = "{$MYSQL_PATH} --user={$DB_USER} --password={$DB_PASS} --host={$DB_HOST} {$DB_NAME} < \"{$SQL_FILE}\"";
        }
        
        echo "<p>⏳ Restaurando desde estacionamiento.sql...</p>";
        echo "<p>Esto puede tomar 30-60 segundos...</p>";
        
        exec($comando, $output, $return_code);
        
        if ($return_code === 0) {
            echo "<p class='success'>✅ ¡Base de datos restaurada exitosamente!</p>";
            echo "<p class='success'>🎉 El sistema debería funcionar correctamente ahora.</p>";
            echo "<p><a href='login.php' style='color: #007bff; font-weight: bold; text-decoration: none;'>👤 Ir al Login</a></p>";
        } else {
            echo "<p class='error'>❌ Error al restaurar la base de datos</p>";
            echo "<p>Código de error: {$return_code}</p>";
            if (!empty($output)) {
                echo "<p>Detalles:</p><pre>" . implode("\n", $output) . "</pre>";
            }
        }
    }
}

echo "</div></body></html>";
?>
