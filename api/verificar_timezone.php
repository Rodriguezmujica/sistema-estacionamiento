<?php
/**
 * Script de Verificación de Zona Horaria
 * Ejecutar desde: http://localhost/sistemaEstacionamiento/api/verificar_timezone.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';

echo "<h1>🌍 Verificación de Zona Horaria del Sistema</h1>";
echo "<hr>";

// ============================================
// 1. VERIFICAR CONFIGURACIÓN DE PHP
// ============================================
echo "<h2>📌 Configuración de PHP</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";

$phpTimezone = date_default_timezone_get();
$phpDate = date('Y-m-d H:i:s');
$phpDateFormatted = date('d-m-Y H:i:s');

echo "<tr><td><strong>Zona Horaria PHP:</strong></td><td style='color: blue;'>{$phpTimezone}</td></tr>";
echo "<tr><td><strong>Fecha/Hora PHP (ISO):</strong></td><td>{$phpDate}</td></tr>";
echo "<tr><td><strong>Fecha/Hora PHP (CL):</strong></td><td>{$phpDateFormatted}</td></tr>";

// Verificar si es correcto
$esCorrectoPhp = ($phpTimezone === 'America/Santiago');
$iconoPHP = $esCorrectoPhp ? '✅' : '❌';
$colorPHP = $esCorrectoPhp ? 'green' : 'red';

echo "<tr style='background: " . ($esCorrectoPhp ? '#d4edda' : '#f8d7da') . ";'>";
echo "<td><strong>Estado PHP:</strong></td><td style='color: {$colorPHP};'><strong>{$iconoPHP} " . ($esCorrectoPhp ? 'CORRECTO' : 'INCORRECTO - Debería ser America/Santiago') . "</strong></td></tr>";
echo "</table>";

// ============================================
// 2. VERIFICAR CONFIGURACIÓN DE MYSQL
// ============================================
echo "<h2>📌 Configuración de MySQL</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";

$result = $conn->query("SELECT NOW() as mysql_now, @@session.time_zone as session_tz, @@global.time_zone as global_tz");
$mysqlInfo = $result->fetch_assoc();

echo "<tr><td><strong>Zona Horaria Global MySQL:</strong></td><td>{$mysqlInfo['global_tz']}</td></tr>";
echo "<tr><td><strong>Zona Horaria Sesión MySQL:</strong></td><td style='color: blue;'>{$mysqlInfo['session_tz']}</td></tr>";
echo "<tr><td><strong>Fecha/Hora MySQL (NOW()):</strong></td><td>{$mysqlInfo['mysql_now']}</td></tr>";

// Calcular diferencia entre PHP y MySQL
$phpTimestamp = strtotime($phpDate);
$mysqlTimestamp = strtotime($mysqlInfo['mysql_now']);
$diferencia = abs($phpTimestamp - $mysqlTimestamp);

echo "<tr><td><strong>Diferencia PHP vs MySQL:</strong></td><td>" . ($diferencia < 5 ? "<span style='color: green;'>✅ {$diferencia} segundos (OK)</span>" : "<span style='color: red;'>❌ {$diferencia} segundos (PROBLEMA)</span>") . "</td></tr>";

$esCorrectoMySQL = ($diferencia < 5);
echo "<tr style='background: " . ($esCorrectoMySQL ? '#d4edda' : '#f8d7da') . ";'>";
echo "<td><strong>Estado MySQL:</strong></td><td style='color: " . ($esCorrectoMySQL ? 'green' : 'red') . ";'><strong>" . ($esCorrectoMySQL ? '✅ SINCRONIZADO' : '❌ DESINCRONIZADO') . "</strong></td></tr>";

echo "</table>";

// ============================================
// 3. INFORMACIÓN SOBRE HORARIO DE VERANO
// ============================================
echo "<h2>📌 Información de Horario de Verano (DST)</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; margin: 20px 0; border-left: 4px solid #1976d2;'>";

$tz = new DateTimeZone('America/Santiago');
$now = new DateTime('now', $tz);
$transitions = $tz->getTransitions(time(), time() + (365 * 24 * 60 * 60)); // Próximos 12 meses

echo "<p><strong>Zona Horaria:</strong> America/Santiago</p>";
echo "<p><strong>Offset Actual:</strong> UTC" . $now->format('P') . "</p>";
echo "<p><strong>Es Horario de Verano:</strong> " . ($now->format('I') == 1 ? '✅ Sí (UTC-3)' : '❌ No (UTC-4)') . "</p>";

if (count($transitions) > 1) {
    echo "<p><strong>Próximo Cambio de Hora:</strong></p>";
    echo "<ul>";
    foreach ($transitions as $i => $transition) {
        if ($i > 0 && $i < 3) { // Mostrar los próximos 2 cambios
            $fecha = date('d-m-Y H:i:s', $transition['ts']);
            $abbr = $transition['abbr'];
            $offset_horas = $transition['offset'] / 3600;
            echo "<li>{$fecha} → {$abbr} (UTC" . ($offset_horas >= 0 ? '+' : '') . "{$offset_horas})</li>";
        }
    }
    echo "</ul>";
}

echo "</div>";

// ============================================
// 4. PRUEBA DE INSERCIÓN Y LECTURA
// ============================================
echo "<h2>📌 Prueba de Inserción de Timestamp</h2>";

$testPatente = 'TEST' . rand(100, 999);
$sqlTest = "INSERT INTO ingresos (patente, fecha_ingreso, idtipo_ingreso, salida) VALUES (?, NOW(), 19, 0)";
$stmt = $conn->prepare($sqlTest);
$stmt->bind_param('s', $testPatente);
$stmt->execute();
$testId = $conn->insert_id;
$stmt->close();

$sqlRead = "SELECT fecha_ingreso FROM ingresos WHERE idautos_estacionados = ?";
$stmtRead = $conn->prepare($sqlRead);
$stmtRead->bind_param('i', $testId);
$stmtRead->execute();
$resultRead = $stmtRead->get_result();
$testRow = $resultRead->fetch_assoc();
$stmtRead->close();

// Eliminar el registro de prueba
$conn->query("DELETE FROM ingresos WHERE idautos_estacionados = $testId");

echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
echo "<p><strong>📝 Hora insertada (NOW()):</strong> {$testRow['fecha_ingreso']}</p>";
echo "<p><strong>📝 Hora actual PHP:</strong> {$phpDate}</p>";
echo "<p><strong>📝 Diferencia:</strong> " . (abs(strtotime($testRow['fecha_ingreso']) - $phpTimestamp)) . " segundos</p>";
echo "</div>";

// ============================================
// 5. RECOMENDACIONES
// ============================================
echo "<h2>📌 Recomendaciones</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; margin: 20px 0; border-left: 4px solid #0c5460;'>";

if ($esCorrectoPhp && $esCorrectoMySQL) {
    echo "<h3 style='color: green;'>✅ Todo Está Configurado Correctamente</h3>";
    echo "<p>El sistema maneja automáticamente el cambio de horario de verano/invierno.</p>";
} else {
    echo "<h3 style='color: red;'>⚠️ Hay Problemas de Configuración</h3>";
    echo "<ul>";
    
    if (!$esCorrectoPhp) {
        echo "<li>❌ PHP no está usando America/Santiago</li>";
        echo "<li>Solución: Asegúrate de que conexion.php tenga: <code>date_default_timezone_set('America/Santiago');</code></li>";
    }
    
    if (!$esCorrectoMySQL) {
        echo "<li>❌ MySQL no está sincronizado con PHP</li>";
        echo "<li>Solución: Ejecuta en MySQL: <code>SET GLOBAL time_zone = 'America/Santiago';</code></li>";
        echo "<li>O configura en my.ini: <code>default-time-zone='America/Santiago'</code></li>";
    }
    
    echo "</ul>";
}

echo "<hr>";
echo "<h4>📚 Buenas Prácticas:</h4>";
echo "<ul>";
echo "<li>✅ Usar <strong>America/Santiago</strong> (maneja DST automáticamente)</li>";
echo "<li>❌ NO usar <strong>Chile/Continental</strong> (deprecated)</li>";
echo "<li>✅ Configurar TANTO PHP como MySQL con la misma zona</li>";
echo "<li>✅ Usar NOW() en MySQL para timestamps automáticos</li>";
echo "<li>✅ Usar DateTime en PHP para cálculos de tiempo</li>";
echo "</ul>";

echo "</div>";

// ============================================
// 6. CALENDARIO DE CAMBIOS DE HORA
// ============================================
echo "<h2>📅 Calendario de Cambios de Hora en Chile</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; margin: 20px 0;'>";
echo "<p><strong>Chile tiene dos cambios de hora al año:</strong></p>";
echo "<ul>";
echo "<li>🌞 <strong>Horario de Verano:</strong> Primer sábado de <strong>SEPTIEMBRE</strong> (adelantar 1 hora → UTC-3)</li>";
echo "<li>🌙 <strong>Horario de Invierno:</strong> Primer sábado de <strong>ABRIL</strong> (atrasar 1 hora → UTC-4)</li>";
echo "</ul>";
echo "<p><em>America/Santiago maneja estos cambios automáticamente, no necesitas hacer nada manual.</em></p>";
echo "</div>";

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1000px;
        margin: 20px auto;
        padding: 20px;
        background: #fff;
    }
    h1 { color: #1976d2; border-bottom: 3px solid #1976d2; padding-bottom: 10px; }
    h2 { color: #333; margin-top: 30px; }
    table { margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    th, td { padding: 10px; border: 1px solid #ddd; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    ul { line-height: 1.8; }
</style>

