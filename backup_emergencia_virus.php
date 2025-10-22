<?php
/**
 * 🚨 BACKUP DE EMERGENCIA - VIRUS DETECTADO
 * 
 * Este script crea un backup completo de la base de datos
 * para proteger contra pérdida de datos por virus.
 */

require_once __DIR__ . '/conexion.php';

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

echo "🚨 BACKUP DE EMERGENCIA INICIADO\n";
echo "⏰ Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "================================\n\n";

// Crear directorio de backup si no existe
$backup_dir = __DIR__ . '/backups_emergencia';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    echo "✅ Directorio de backup creado: $backup_dir\n";
}

// Nombre del archivo de backup
$backup_file = $backup_dir . '/backup_emergencia_' . date('Y-m-d_H-i-s') . '.sql';

echo "📁 Archivo de backup: $backup_file\n\n";

// Obtener todas las tablas
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "📊 Tablas encontradas: " . count($tables) . "\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}
echo "\n";

// Crear backup SQL
$sql_backup = "-- BACKUP DE EMERGENCIA - " . date('Y-m-d H:i:s') . "\n";
$sql_backup .= "-- Generado automáticamente por backup_emergencia_virus.php\n";
$sql_backup .= "-- Motivo: Virus Pow.bat detectado\n\n";
$sql_backup .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    echo "📋 Procesando tabla: $table\n";
    
    // Estructura de la tabla
    $sql_backup .= "-- Estructura de tabla para `$table`\n";
    $sql_backup .= "DROP TABLE IF EXISTS `$table`;\n";
    
    $create_result = $conn->query("SHOW CREATE TABLE `$table`");
    $create_row = $create_result->fetch_array();
    $sql_backup .= $create_row[1] . ";\n\n";
    
    // Datos de la tabla
    $sql_backup .= "-- Datos de la tabla `$table`\n";
    $data_result = $conn->query("SELECT * FROM `$table`");
    
    if ($data_result->num_rows > 0) {
        $columns = [];
        $field_info = $conn->query("DESCRIBE `$table`");
        while ($field = $field_info->fetch_assoc()) {
            $columns[] = "`" . $field['Field'] . "`";
        }
        
        $sql_backup .= "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES\n";
        
        $values = [];
        while ($row = $data_result->fetch_assoc()) {
            $row_values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $row_values[] = 'NULL';
                } else {
                    $row_values[] = "'" . $conn->real_escape_string($value) . "'";
                }
            }
            $values[] = "(" . implode(', ', $row_values) . ")";
        }
        
        $sql_backup .= implode(",\n", $values) . ";\n\n";
        echo "  ✅ " . $data_result->num_rows . " registros exportados\n";
    } else {
        echo "  ⚠️ Tabla vacía\n";
    }
}

$sql_backup .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$sql_backup .= "-- FIN DEL BACKUP\n";

// Guardar archivo
if (file_put_contents($backup_file, $sql_backup)) {
    echo "\n✅ BACKUP COMPLETADO EXITOSAMENTE\n";
    echo "📁 Archivo: $backup_file\n";
    echo "📊 Tamaño: " . number_format(filesize($backup_file)) . " bytes\n";
    
    // Crear también un backup comprimido
    $zip_file = str_replace('.sql', '.zip', $backup_file);
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($backup_file, basename($backup_file));
        $zip->close();
        echo "📦 Backup comprimido: $zip_file\n";
    }
    
} else {
    echo "\n❌ ERROR AL CREAR BACKUP\n";
}

echo "\n🔒 RECOMENDACIONES DE SEGURIDAD:\n";
echo "1. Ejecutar Windows Defender completo\n";
echo "2. Ejecutar Malwarebytes\n";
echo "3. Cambiar todas las contraseñas\n";
echo "4. Revisar procesos en ejecución\n";
echo "5. Deshabilitar macros en Office\n";
echo "6. Actualizar Windows\n";

echo "\n⏰ Backup completado: " . date('Y-m-d H:i:s') . "\n";
?>
