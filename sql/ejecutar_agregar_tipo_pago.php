<?php
/**
 * Script para agregar el campo tipo_pago a la tabla salidas
 * Ejecutar solo UNA VEZ desde el navegador
 */

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Actualización de Base de Datos - Campo tipo_pago</h2>";
echo "<hr>";

// 1. Verificar si el campo ya existe
$check_query = "SHOW COLUMNS FROM salidas LIKE 'tipo_pago'";
$result = $conexion->query($check_query);

if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ El campo 'tipo_pago' ya existe en la tabla 'salidas'.</p>";
    echo "<p>No es necesario ejecutar este script nuevamente.</p>";
} else {
    echo "<h3>Paso 1: Agregando campo tipo_pago...</h3>";
    
    // 2. Agregar el campo
    $sql1 = "ALTER TABLE `salidas` 
             ADD COLUMN `tipo_pago` ENUM('tuu', 'manual') DEFAULT 'manual' 
             AFTER `metodo_pago`";
    
    if ($conexion->query($sql1) === TRUE) {
        echo "<p style='color: green;'>✅ Campo 'tipo_pago' agregado exitosamente.</p>";
        
        // 3. Actualizar registros existentes
        echo "<h3>Paso 2: Actualizando registros existentes...</h3>";
        
        // Marcar como 'tuu' los que tienen metodo_pago = 'TUU'
        $sql2 = "UPDATE `salidas` 
                 SET `tipo_pago` = 'tuu' 
                 WHERE `metodo_pago` = 'TUU'";
        
        $conexion->query($sql2);
        $tuu_count = $conexion->affected_rows;
        echo "<p>✅ {$tuu_count} registros marcados como 'tuu' (boletas oficiales)</p>";
        
        // Los demás se marcan como 'manual' (ya es el default)
        $sql3 = "UPDATE `salidas` 
                 SET `tipo_pago` = 'manual' 
                 WHERE `metodo_pago` != 'TUU' OR `metodo_pago` IS NULL";
        
        $conexion->query($sql3);
        $manual_count = $conexion->affected_rows;
        echo "<p>✅ {$manual_count} registros marcados como 'manual' (comprobantes internos)</p>";
        
        // 4. Verificar resultado
        echo "<h3>Paso 3: Verificación</h3>";
        $sql4 = "SELECT 
                    metodo_pago,
                    tipo_pago,
                    COUNT(*) as cantidad
                 FROM salidas
                 GROUP BY metodo_pago, tipo_pago
                 ORDER BY metodo_pago";
        
        $result = $conexion->query($sql4);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #333; color: white;'>";
        echo "<th>Método de Pago</th><th>Tipo de Pago</th><th>Cantidad</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $metodo = $row['metodo_pago'] ?? 'NULL';
            $tipo = $row['tipo_pago'];
            $cantidad = $row['cantidad'];
            
            $color = $tipo === 'tuu' ? '#e3f2fd' : '#fff3e0';
            echo "<tr style='background: {$color};'>";
            echo "<td>{$metodo}</td><td>{$tipo}</td><td>{$cantidad}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<hr>";
        echo "<h3 style='color: green;'>✅ Actualización completada exitosamente!</h3>";
        echo "<p><strong>Importante:</strong> Ahora puedes eliminar este archivo por seguridad.</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Error al agregar el campo: " . $conexion->error . "</p>";
    }
}

$conexion->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 { color: #1976d2; }
    h3 { color: #333; margin-top: 20px; }
    table { margin: 20px 0; width: 100%; background: white; }
    p { line-height: 1.6; }
</style>

