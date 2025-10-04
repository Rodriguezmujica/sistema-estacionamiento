<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';

if (!$patente) {
    echo json_encode(['success' => false, 'error' => 'Patente requerida']);
    exit;
}

try {
    // Consultar historial de lavados para la patente (solo los cobrados)
    $sql = "SELECT 
                i.idautos_estacionados,
                i.patente,
                i.fecha_ingreso,
                t.nombre_servicio,
                t.precio,
                s.total,
                s.fecha_salida,
                s.metodo_pago,
                s.motivos_extra,
                s.descripcion_extra,
                s.precio_extra
            FROM ingresos i
            JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            WHERE i.patente = ? 
            AND t.nombre_servicio NOT LIKE '%estacionamiento%'
            AND i.salida = 1
            ORDER BY i.fecha_ingreso DESC
            LIMIT 10";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $patente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historial = [];
    $totalLavados = 0;
    $ultimoLavado = null;
    
    while ($row = $result->fetch_assoc()) {
        $totalLavados++;
        
        // El primer registro es el más reciente
        if ($ultimoLavado === null) {
            $ultimoLavado = [
                'fecha' => $row['fecha_salida'] ?: $row['fecha_ingreso'],
                'servicio' => $row['nombre_servicio'],
                'precio' => $row['total'] ?: $row['precio'],
                'motivos' => $row['motivos_extra'] ? json_decode($row['motivos_extra'], true) : [],
                'descripcion' => $row['descripcion_extra'] ?: '',
                'precio_extra' => $row['precio_extra'] ?: 0
            ];
        }
        
        $historial[] = [
            'id' => $row['idautos_estacionados'],
            'fecha' => $row['fecha_salida'] ?: $row['fecha_ingreso'],
            'servicio' => $row['nombre_servicio'],
            'precio' => $row['total'] ?: $row['precio'],
            'precio_extra' => $row['precio_extra'] ?: 0,
            'motivos' => $row['motivos_extra'] ? json_decode($row['motivos_extra'], true) : [],
            'descripcion' => $row['descripcion_extra'] ?: '',
            'metodo_pago' => $row['metodo_pago'] ?: 'EFECTIVO'
        ];
    }
    
    $stmt->close();
    
    if ($totalLavados === 0) {
        echo json_encode([
            'success' => true,
            'patente' => $patente,
            'mensaje' => 'No se encontró historial de lavados para esta patente',
            'total_lavados' => 0
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'patente' => $patente,
            'ultimo_lavado' => $ultimoLavado,
            'historial' => $historial,
            'total_lavados' => $totalLavados
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al consultar historial: ' . $e->getMessage()
    ]);
}

$conexion->close();
?>
