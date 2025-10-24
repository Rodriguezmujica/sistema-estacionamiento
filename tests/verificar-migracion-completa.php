<?php
/**
 * 🔍 VERIFICADOR COMPLETO POST-MIGRACIÓN
 * Sistema de Estacionamiento - Windows 7
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Verificación Post-Migración - Sistema Estacionamiento</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: #2c3e50; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }";
echo ".section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }";
echo ".success { background: #d4edda; border-color: #c3e6cb; color: #155724; }";
echo ".error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }";
echo ".warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }";
echo ".info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }";
echo ".checklist { list-style: none; padding: 0; }";
echo ".checklist li { padding: 5px 0; }";
echo ".checklist li:before { content: '✅ '; color: green; }";
echo ".checklist li.error:before { content: '❌ '; color: red; }";
echo ".checklist li.warning:before { content: '⚠️ '; color: orange; }";
echo ".stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }";
echo ".stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; }";
echo ".stat-number { font-size: 2em; font-weight: bold; color: #2c3e50; }";
echo ".stat-label { color: #666; margin-top: 5px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 Verificación Post-Migración</h1>";
echo "<p>Sistema de Estacionamiento - Windows 7</p>";
echo "</div>";

$checks = [];
$errors = [];
$warnings = [];
$success = [];

// ============================================
// 1. VERIFICAR CONFIGURACIÓN DEL SISTEMA
// ============================================
echo "<div class='section'>";
echo "<h2>1. Configuración del Sistema</h2>";

$php_version = phpversion();
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');

echo "<ul class='checklist'>";

if (version_compare($php_version, '7.0.0', '>=')) {
    echo "<li>PHP Version: $php_version ✅</li>";
    $success[] = "PHP Version compatible";
} else {
    echo "<li class='error'>PHP Version: $php_version ❌ (Requiere 7.0+)</li>";
    $errors[] = "PHP Version incompatible";
}

if (intval($memory_limit) >= 256) {
    echo "<li>Memoria PHP: $memory_limit ✅</li>";
    $success[] = "Memoria PHP suficiente";
} else {
    echo "<li class='warning'>Memoria PHP: $memory_limit ⚠️ (Recomendado: 256M+)</li>";
    $warnings[] = "Memoria PHP limitada";
}

echo "<li>Max Execution Time: $max_execution_time</li>";
echo "<li>Upload Max Filesize: $upload_max_filesize</li>";
echo "<li>Post Max Size: $post_max_size</li>";

echo "</ul>";
echo "</div>";

// ============================================
// 2. VERIFICAR CONEXIÓN A BASE DE DATOS
// ============================================
echo "<div class='section'>";
echo "<h2>2. Base de Datos</h2>";

require_once 'conexion.php';

if ($conn && !$conn->connect_error) {
    echo "<div class='success'>";
    echo "<h3>✅ Conexión Exitosa</h3>";
    echo "<p>Conectado a MySQL correctamente</p>";
    echo "</div>";
    
    $success[] = "Conexión a base de datos exitosa";
    
    // Verificar tablas
    $result = $conn->query("SHOW TABLES");
    if ($result && $result->num_rows > 0) {
        $table_count = $result->num_rows;
        echo "<h3>📊 Tablas Encontradas: $table_count</h3>";
        echo "<ul class='checklist'>";
        
        while ($row = $result->fetch_array()) {
            $table_name = $row[0];
            
            // Verificar estructura de tabla importante
            $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table_name`");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            
            echo "<li>$table_name ($count registros)</li>";
        }
        
        echo "</ul>";
        $success[] = "Tablas de base de datos encontradas";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ No se encontraron tablas</h3>";
        echo "<p>La base de datos está vacía. Ejecuta el instalador.</p>";
        echo "</div>";
        $errors[] = "No se encontraron tablas en la base de datos";
    }
    
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Error de Conexión</h3>";
    echo "<p>No se pudo conectar a la base de datos</p>";
    if ($conn) {
        echo "<p>Error: " . $conn->connect_error . "</p>";
    }
    echo "</div>";
    $errors[] = "Error de conexión a base de datos";
}
echo "</div>";

// ============================================
// 3. VERIFICAR ARCHIVOS DEL SISTEMA
// ============================================
echo "<div class='section'>";
echo "<h2>3. Archivos del Sistema</h2>";

$required_files = [
    'conexion.php' => 'Archivo de conexión principal',
    'config-sensible.php' => 'Configuración sensible',
    'index.php' => 'Página principal',
    'login.php' => 'Sistema de login',
    'estacionamiento.sql' => 'Script de base de datos'
];

echo "<ul class='checklist'>";

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<li>$description: $file ✅ ($size bytes)</li>";
        $success[] = "Archivo $file encontrado";
    } else {
        echo "<li class='error'>$description: $file ❌</li>";
        $errors[] = "Archivo $file no encontrado";
    }
}

echo "</ul>";
echo "</div>";

// ============================================
// 4. VERIFICAR CONFIGURACIÓN DE FIREBASE
// ============================================
echo "<div class='section'>";
echo "<h2>4. Configuración Firebase</h2>";

if (file_exists('config-sensible.php')) {
    require_once 'config-sensible.php';
    
    echo "<ul class='checklist'>";
    
    if (defined('FIREBASE_API_KEY') && !empty(FIREBASE_API_KEY)) {
        echo "<li>Firebase API Key configurada ✅</li>";
        $success[] = "Firebase API Key configurada";
    } else {
        echo "<li class='warning'>Firebase API Key no configurada ⚠️</li>";
        $warnings[] = "Firebase API Key no configurada";
    }
    
    if (defined('FIREBASE_PROJECT_ID') && !empty(FIREBASE_PROJECT_ID)) {
        echo "<li>Firebase Project ID configurado ✅</li>";
        $success[] = "Firebase Project ID configurado";
    } else {
        echo "<li class='warning'>Firebase Project ID no configurado ⚠️</li>";
        $warnings[] = "Firebase Project ID no configurado";
    }
    
    if (defined('TUU_API_KEY') && !empty(TUU_API_KEY)) {
        echo "<li>TUU API Key configurada ✅</li>";
        $success[] = "TUU API Key configurada";
    } else {
        echo "<li class='warning'>TUU API Key no configurada ⚠️</li>";
        $warnings[] = "TUU API Key no configurada";
    }
    
    echo "</ul>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Archivo config-sensible.php no encontrado</p>";
    echo "</div>";
    $errors[] = "Archivo de configuración no encontrado";
}
echo "</div>";

// ============================================
// 5. VERIFICAR PERMISOS Y DIRECTORIOS
// ============================================
echo "<div class='section'>";
echo "<h2>5. Permisos y Directorios</h2>";

$directories = [
    'logs' => 'Directorio de logs',
    'backups_emergencia' => 'Directorio de backups',
    'imagenes' => 'Directorio de imágenes'
];

echo "<ul class='checklist'>";

foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<li>$description: $dir ✅ (escribible)</li>";
            $success[] = "Directorio $dir escribible";
        } else {
            echo "<li class='warning'>$description: $dir ⚠️ (no escribible)</li>";
            $warnings[] = "Directorio $dir no escribible";
        }
    } else {
        echo "<li class='error'>$description: $dir ❌ (no existe)</li>";
        $errors[] = "Directorio $dir no existe";
    }
}

echo "</ul>";
echo "</div>";

// ============================================
// 6. ESTADÍSTICAS FINALES
// ============================================
echo "<div class='section'>";
echo "<h2>6. Resumen de Verificación</h2>";

echo "<div class='stats'>";
echo "<div class='stat-card'>";
echo "<div class='stat-number' style='color: #28a745;'>" . count($success) . "</div>";
echo "<div class='stat-label'>Verificaciones Exitosas</div>";
echo "</div>";

echo "<div class='stat-card'>";
echo "<div class='stat-number' style='color: #ffc107;'>" . count($warnings) . "</div>";
echo "<div class='stat-label'>Advertencias</div>";
echo "</div>";

echo "<div class='stat-card'>";
echo "<div class='stat-number' style='color: #dc3545;'>" . count($errors) . "</div>";
echo "<div class='stat-label'>Errores</div>";
echo "</div>";
echo "</div>";

// Estado general
if (count($errors) == 0) {
    if (count($warnings) == 0) {
        echo "<div class='success'>";
        echo "<h3>🎉 ¡MIGRACIÓN COMPLETADA EXITOSAMENTE!</h3>";
        echo "<p>El sistema está completamente funcional en Windows 7.</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ MIGRACIÓN COMPLETADA CON ADVERTENCIAS</h3>";
        echo "<p>El sistema funciona pero hay algunas advertencias menores.</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<h3>❌ MIGRACIÓN INCOMPLETA</h3>";
    echo "<p>Hay errores que deben corregirse antes de usar el sistema.</p>";
    echo "</div>";
}

echo "</div>";

// ============================================
// 7. ACCIONES RECOMENDADAS
// ============================================
echo "<div class='section'>";
echo "<h2>7. Acciones Recomendadas</h2>";

echo "<ul class='checklist'>";

if (count($errors) > 0) {
    echo "<li class='error'>Corregir errores críticos antes de continuar</li>";
    echo "<li>Ejecutar: <code>instalar-bd-windows7.php</code> si hay problemas de base de datos</li>";
}

if (count($warnings) > 0) {
    echo "<li class='warning'>Revisar advertencias para optimizar el sistema</li>";
}

if (count($errors) == 0) {
    echo "<li>Probar el sistema: <a href='index.php' target='_blank'>Acceder al Sistema</a></li>";
    echo "<li>Verificar funcionalidades principales</li>";
    echo "<li>Configurar backups automáticos</li>";
}

echo "</ul>";
echo "</div>";

echo "<div class='section info'>";
echo "<h3>📞 Soporte</h3>";
echo "<p>Si encuentras problemas:</p>";
echo "<ul>";
echo "<li>Revisar logs de error en C:\\xampp\\apache\\logs\\error.log</li>";
echo "<li>Verificar configuración de MySQL en XAMPP Control Panel</li>";
echo "<li>Ejecutar el instalador de base de datos si es necesario</li>";
echo "</ul>";
echo "</div>";

echo "</div>"; // container
echo "</body>";
echo "</html>";
?>
