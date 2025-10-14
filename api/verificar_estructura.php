<?php
// filepath: c:\xampp\htdocs\sistemaEstacionamiento\api\verificar_estructura.php
header('Content-Type: text/plain; charset=utf-8');
require_once '../conexion.php';

echo "🔍 VERIFICACIÓN DE ESTRUCTURA DE TABLAS\n";
echo "=====================================\n\n";

try {
    // Verificar tabla ingresos
    echo "📋 TABLA INGRESOS:\n";
    $result = $conn->query("DESCRIBE ingresos");
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n📋 TABLA SALIDAS:\n";
    $result = $conn->query("DESCRIBE salidas");
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n📋 TABLA TIPO_INGRESO:\n";
    $result = $conn->query("DESCRIBE tipo_ingreso");
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n🧪 PRUEBA DE CONSULTA:\n";
    $sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, s.total, ti.nombre_servicio 
            FROM ingresos i 
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
            LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
            LIMIT 5";
    
    $result = $conn->query($sql);
    echo "Registros encontrados: " . $result->num_rows . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['idautos_estacionados']}, Patente: {$row['patente']}, Total: {$row['total']}, Servicio: {$row['nombre_servicio']}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>