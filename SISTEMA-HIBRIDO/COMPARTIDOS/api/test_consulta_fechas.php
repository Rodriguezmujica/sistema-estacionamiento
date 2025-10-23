// 🔧 HABILITAR CORS PARA SISTEMA HÍBRIDO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (pre-flight), responder vacía
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

<?php
// filepath: c:\xampp\htdocs\sistemaEstacionamiento\api\test_consulta_fechas.php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

echo "🧪 PRUEBA DE CONSULTA POR FECHAS\n";
echo "==============================\n\n";

// Probar con diferentes rangos de fechas
$rangos_prueba = [
    ['2025-10-14', '2025-10-14'], // Solo hoy
    ['2025-10-13', '2025-10-14'], // Ayer y hoy
    ['2025-10-07', '2025-10-14'], // Última semana
];

foreach ($rangos_prueba as $rango) {
    $fecha_inicio = $rango[0] . ' 00:00:00';
    $fecha_fin = $rango[1] . ' 23:59:59';
    
    echo "📅 PROBANDO RANGO: {$rango[0]} a {$rango[1]}\n";
    echo "   Consulta: $fecha_inicio a $fecha_fin\n";
    
    $sql = "SELECT 
        COUNT(*) as total_registros,
        SUM(s.total) as total_pesos,
        ti.nombre_servicio,
        COUNT(*) as cantidad
    FROM ingresos i 
    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
    LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
    WHERE i.fecha_ingreso >= ? 
    AND i.fecha_ingreso <= ?
    AND s.id_ingresos IS NOT NULL
    GROUP BY ti.nombre_servicio
    ORDER BY cantidad DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_general = 0;
    $total_pesos_general = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['nombre_servicio']}: {$row['cantidad']} servicios\n";
        $total_general += $row['cantidad'];
        $total_pesos_general += $row['total_pesos'] ?? 0;
    }
    
    echo "   📊 TOTAL: $total_general servicios, $" . number_format($total_pesos_general) . "\n";
    
    // Verificar específicamente estacionamiento
    $sqlEst = "SELECT COUNT(*) as total_est 
               FROM ingresos i 
               LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
               LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
               WHERE i.fecha_ingreso >= ? 
               AND i.fecha_ingreso <= ?
               AND ti.nombre_servicio LIKE '%estacionamiento%'
               AND s.id_ingresos IS NOT NULL";
    
    $stmtEst = $conn->prepare($sqlEst);
    $stmtEst->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmtEst->execute();
    $resultEst = $stmtEst->get_result()->fetch_assoc();
    
    echo "   🚗 Estacionamientos: {$resultEst['total_est']}\n\n";
}

$conn->close();
?>