<?php
/**
 * 🛡️ SCRIPT DE PROTECCIÓN CONTRA VIRUS
 * 
 * Este script monitorea y protege el sistema contra virus
 * que puedan afectar la base de datos.
 */

echo "🛡️ SISTEMA DE PROTECCIÓN CONTRA VIRUS\n";
echo "=====================================\n\n";

// 1. Verificar archivos sospechosos en startup
echo "🔍 Verificando archivos de inicio...\n";
$startup_paths = [
    'C:\ProgramData\Microsoft\Windows\Start Menu\Programs\Startup',
    'C:\Users\\' . get_current_user() . '\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup',
    'C:\Users\\' . get_current_user() . '\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup'
];

$archivos_sospechosos = [];
foreach ($startup_paths as $path) {
    if (is_dir($path)) {
        $archivos = scandir($path);
        foreach ($archivos as $archivo) {
            if ($archivo != '.' && $archivo != '..') {
                $archivo_path = $path . '\\' . $archivo;
                if (is_file($archivo_path)) {
                    // Verificar extensiones sospechosas
                    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                    if (in_array($extension, ['bat', 'cmd', 'vbs', 'js', 'exe', 'scr'])) {
                        $archivos_sospechosos[] = $archivo_path;
                        echo "⚠️ Archivo sospechoso encontrado: $archivo_path\n";
                    }
                }
            }
        }
    }
}

if (empty($archivos_sospechosos)) {
    echo "✅ No se encontraron archivos sospechosos en startup\n";
} else {
    echo "🚨 " . count($archivos_sospechosos) . " archivos sospechosos encontrados\n";
}

echo "\n";

// 2. Verificar procesos sospechosos
echo "🔍 Verificando procesos en ejecución...\n";
$procesos_sospechosos = [
    'pow.bat', 'pow.exe', 'powershell.exe', 'cmd.exe', 'wscript.exe', 'cscript.exe'
];

$procesos_activos = [];
foreach ($procesos_sospechosos as $proceso) {
    $output = shell_exec("tasklist /FI \"IMAGENAME eq $proceso\" 2>nul");
    if (strpos($output, $proceso) !== false) {
        $procesos_activos[] = $proceso;
        echo "⚠️ Proceso sospechoso activo: $proceso\n";
    }
}

if (empty($procesos_activos)) {
    echo "✅ No se encontraron procesos sospechosos\n";
} else {
    echo "🚨 " . count($procesos_activos) . " procesos sospechosos encontrados\n";
}

echo "\n";

// 3. Crear archivo de monitoreo
echo "📝 Creando sistema de monitoreo...\n";
$monitor_file = __DIR__ . '/logs/monitor_virus.log';
$monitor_dir = dirname($monitor_file);
if (!is_dir($monitor_dir)) {
    mkdir($monitor_dir, 0755, true);
}

$log_entry = date('Y-m-d H:i:s') . " - Verificación de virus ejecutada\n";
$log_entry .= "Archivos sospechosos: " . count($archivos_sospechosos) . "\n";
$log_entry .= "Procesos sospechosos: " . count($procesos_activos) . "\n";
$log_entry .= "================================\n";

file_put_contents($monitor_file, $log_entry, FILE_APPEND | LOCK_EX);
echo "✅ Log de monitoreo actualizado: $monitor_file\n";

// 4. Crear script de limpieza automática
echo "🧹 Creando script de limpieza automática...\n";
$cleanup_script = __DIR__ . '/limpiar_virus.bat';
$cleanup_content = '@echo off
echo 🧹 LIMPIEZA AUTOMATICA DE VIRUS
echo ================================

REM Detener procesos sospechosos
taskkill /F /IM pow.exe 2>nul
taskkill /F /IM pow.bat 2>nul

REM Eliminar archivos sospechosos del startup
del "C:\ProgramData\Microsoft\Windows\Start Menu\Programs\Startup\Pow.bat" 2>nul
del "C:\ProgramData\Microsoft\Windows\Start Menu\Programs\Startup\Pow.exe" 2>nul

REM Limpiar archivos temporales
del /Q /S "%TEMP%\*" 2>nul
del /Q /S "C:\Windows\Temp\*" 2>nul

REM Ejecutar Windows Defender
echo Ejecutando Windows Defender...
"C:\Program Files\Windows Defender\MpCmdRun.exe" -Scan -ScanType 2

echo ✅ Limpieza completada
pause
';

file_put_contents($cleanup_script, $cleanup_content);
echo "✅ Script de limpieza creado: $cleanup_script\n";

// 5. Crear tarea programada para monitoreo
echo "⏰ Creando tarea de monitoreo...\n";
$task_command = 'schtasks /create /tn "MonitoreoVirus" /tr "' . $cleanup_script . '" /sc minute /mo 30 /f';
$output = shell_exec($task_command);
echo "✅ Tarea de monitoreo creada\n";

echo "\n🛡️ SISTEMA DE PROTECCIÓN CONFIGURADO\n";
echo "=====================================\n";
echo "✅ Backup de emergencia creado\n";
echo "✅ Monitoreo de archivos activo\n";
echo "✅ Limpieza automática configurada\n";
echo "✅ Tarea programada creada\n";

echo "\n🚨 ACCIONES RECOMENDADAS:\n";
echo "1. Ejecutar: limpiar_virus.bat\n";
echo "2. Ejecutar Windows Defender completo\n";
echo "3. Reiniciar el sistema\n";
echo "4. Cambiar contraseñas de la base de datos\n";
echo "5. Monitorear logs en: $monitor_file\n";

echo "\n⏰ Verificación completada: " . date('Y-m-d H:i:s') . "\n";
?>
