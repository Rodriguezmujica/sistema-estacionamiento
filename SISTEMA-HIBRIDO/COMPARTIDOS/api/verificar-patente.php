<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';

if (!$patente) {
    echo json_encode(['success' => false, 'error' => 'Patente requerida']);
    exit;
}

try {
    // Verificar si la patente ya está activa (sin salida registrada)
    $sql = "SELECT 
                i.idautos_estacionados,
                i.patente,
                i.fecha_ingreso,
                ti.nombre_servicio,
                i.salida
            FROM ingresos i
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.patente = ? AND i.salida = 0
            ORDER BY i.fecha_ingreso DESC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $patente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $registro = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'existe' => true,
            'mensaje' => 'Esta patente ya tiene un ingreso activo',
            'registro' => [
                'patente' => $registro['patente'],
                'fecha_ingreso' => $registro['fecha_ingreso'],
                'servicio' => $registro['nombre_servicio'],
                'id' => $registro['idautos_estacionados']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'existe' => false,
            'mensaje' => 'Patente disponible para registro'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>