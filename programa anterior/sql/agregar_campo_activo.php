<?php
header('Content-Type: text/html; charset=utf-8');

// Conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>🔧 Agregar Campo 'activo' a tipo_ingreso</h2>";
echo "<hr>";

// 1. Verificar si el campo ya existe
$check = $conexion->query("SHOW COLUMNS FROM tipo_ingreso LIKE 'activo'");

if ($check->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ El campo 'activo' ya existe en la tabla 'tipo_ingreso'.</p>";
    echo "<p><a href='../api/reactivar_servicios_lavado.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>➡️ Ir a Gestión de Servicios</a></p>";
} else {
    echo "<h3>Paso 1: Agregando campo 'activo'...</h3>";
    
    // 2. Agregar el campo
    $sql1 = "ALTER TABLE `tipo_ingreso` 
             ADD COLUMN `activo` TINYINT(1) DEFAULT 1 
             AFTER `es_plan`";
    
    if ($conexion->query($sql1) === TRUE) {
        echo "<p style='color: green;'>✅ Campo 'activo' agregado exitosamente.</p>";
        
        // 3. Configurar valores iniciales correctos
        echo "<h3>Paso 2: Configurando valores iniciales...</h3>";
        
        // Activar todos los servicios de lavado
        $sql_activar = "UPDATE tipo_ingreso SET activo = 1 
                        WHERE (nombre_servicio LIKE '%lavado%' 
                           OR nombre_servicio LIKE '%sanitiz%'
                           OR nombre_servicio LIKE '%moto%'
                           OR nombre_servicio LIKE '%alfombra%'
                           OR nombre_servicio LIKE '%convenio%'
                           OR nombre_servicio LIKE '%promo%')
                        AND nombre_servicio <> ''
                        AND nombre_servicio NOT LIKE '%estacionamiento%'
                        AND idtipo_ingresos NOT IN (18, 19, 47, 23, 48, 49)";
        
        $conexion->query($sql_activar);
        $lavados_activos = $conexion->affected_rows;
        echo "<p>✅ {$lavados_activos} servicios de LAVADO activados</p>";
        
        // Desactivar servicios NO deseados (ERROR DE INGRESO, estacionamiento, vacíos)
        $sql_desactivar = "UPDATE tipo_ingreso SET activo = 0 
                          WHERE nombre_servicio LIKE '%estacionamiento%' 
                             OR nombre_servicio LIKE '%error%'
                             OR nombre_servicio = ''
                             OR idtipo_ingresos IN (18, 19, 47, 23, 48, 49)";
        
        $conexion->query($sql_desactivar);
        $no_lavados_inactivos = $conexion->affected_rows;
        echo "<p>✅ {$no_lavados_inactivos} servicios NO-LAVADO desactivados</p>";
        
        // 4. Verificar resultado
        echo "<h3>Paso 3: Verificación</h3>";
        $sql_check = "SELECT 
                        activo,
                        COUNT(*) as cantidad
                     FROM tipo_ingreso
                     WHERE nombre_servicio <> ''
                     GROUP BY activo";
        
        $result = $conexion->query($sql_check);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #333; color: white;'>";
        echo "<th>Estado</th><th>Cantidad de Servicios</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $activo = $row['activo'];
            $cantidad = $row['cantidad'];
            $estadoTexto = $activo ? '✅ ACTIVOS' : '❌ INACTIVOS';
            $color = $activo ? '#d4edda' : '#f8d7da';
            
            echo "<tr style='background: {$color};'>";
            echo "<td><strong>{$estadoTexto}</strong></td><td>{$cantidad}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<hr>";
        echo "<h3 style='color: green;'>✅ Campo agregado y configurado exitosamente!</h3>";
        
        echo "<div style='margin: 20px 0; padding: 15px; background: #e3f2fd; border-left: 4px solid #1976d2;'>";
        echo "<h4>🎯 Próximos pasos:</h4>";
        echo "<ol>";
        echo "<li><strong>Elimina este archivo</strong> (sql/agregar_campo_activo.php) por seguridad</li>";
        echo "<li>Ve a <strong>lavados.html</strong> y recarga la página</li>";
        echo "<li>Los servicios de lavado deberían aparecer correctamente</li>";
        echo "<li>Puedes gestionar servicios desde <a href='../api/reactivar_servicios_lavado.php'>esta herramienta</a></li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>❌ Error al agregar el campo: " . $conexion->error . "</p>";
    }
}

$conexion->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 { color: #1976d2; }
    h3 { color: #333; margin-top: 20px; }
    table { margin: 20px 0; width: 100%; background: white; }
    p { line-height: 1.6; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    ol, ul { line-height: 2; }
</style>

