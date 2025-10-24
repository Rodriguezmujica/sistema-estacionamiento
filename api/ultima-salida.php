<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

try {
    // Obtener la última salida con información del ingreso
    $sql = "SELECT s.id_ingresos, s.fecha_salida, s.total, s.metodo_pago,
                   i.patente, i.fecha_ingreso,
                   ti.nombre_servicio
            FROM salidas s
            JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            ORDER BY s.fecha_salida DESC
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true, 
            'salida' => $row
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'No se encontraron salidas registradas'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al obtener la última salida: ' . $e->getMessage()
    ]);
}
?>
