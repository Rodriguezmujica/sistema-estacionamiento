<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

try {
    // Obtener el último ingreso con información completa
    $sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, 
                   ti.nombre_servicio
            FROM ingresos i
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            ORDER BY i.idautos_estacionados DESC
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true, 
            'ingreso' => $row
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'No se encontraron ingresos registrados'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al obtener el último ingreso: ' . $e->getMessage()
    ]);
}
?>
