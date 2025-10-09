<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

date_default_timezone_set('America/Santiago');

try {
    // Obtener parámetros de fecha
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    
    // Validar fechas
    if (empty($fecha_desde) || empty($fecha_hasta)) {
        throw new Exception('Debe proporcionar fecha desde y fecha hasta');
    }
    
    // Validar formato de fechas
    $fecha_desde_obj = DateTime::createFromFormat('Y-m-d', $fecha_desde);
    $fecha_hasta_obj = DateTime::createFromFormat('Y-m-d', $fecha_hasta);
    
    if (!$fecha_desde_obj || !$fecha_hasta_obj) {
        throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD');
    }
    
    // Asegurar que fecha_hasta incluya todo el día
    $fecha_hasta_completa = $fecha_hasta . ' 23:59:59';
    
    // 1. Consulta de resumen (total servicios e ingresos)
    $sql_resumen = "SELECT 
                        COUNT(s.id_ingresos) as total_servicios,
                        SUM(s.total) as total_ingresos
                    FROM salidas s
                    WHERE DATE(s.fecha_salida) BETWEEN '$fecha_desde' AND '$fecha_hasta'
                    AND s.fecha_salida > '1900-01-01'";
    
    $result_resumen = $conn->query($sql_resumen);
    if (!$result_resumen) {
        throw new Exception('Error en consulta de resumen: ' . $conn->error);
    }
    
    $resumen = $result_resumen->fetch_assoc();
    
    // 2. Consulta agrupada por categorías
    $sql_agrupado = "SELECT 
                        CASE 
                            WHEN ti.nombre_servicio LIKE '%lavado%' THEN 'Lavados'
                            WHEN ti.nombre_servicio LIKE '%estacionamiento x minuto%' THEN 'Estacionamiento x Minuto'
                            WHEN ti.nombre_servicio LIKE '%ERROR DE INGRESO%' THEN 'Errores de Ingreso'
                            WHEN ti.nombre_servicio LIKE '%MOTOS%' THEN 'Motos'
                            WHEN ti.nombre_servicio LIKE '%PROMOCION%' THEN 'Promociones'
                            ELSE 'Otros Servicios'
                        END as categoria,
                        COUNT(s.id_ingresos) as cantidad_servicios,
                        SUM(s.total) as total_categoria,
                        GROUP_CONCAT(DISTINCT ti.nombre_servicio SEPARATOR ', ') as tipos_servicios
                    FROM salidas s
                    JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE DATE(s.fecha_salida) BETWEEN '$fecha_desde' AND '$fecha_hasta'
                    AND s.fecha_salida > '1900-01-01'
                    GROUP BY categoria
                    ORDER BY total_categoria DESC";
    
    $result_agrupado = $conn->query($sql_agrupado);
    if (!$result_agrupado) {
        throw new Exception('Error en consulta agrupada: ' . $conn->error);
    }
    
    $categorias = [];
    while ($fila = $result_agrupado->fetch_assoc()) {
        $categorias[] = $fila;
    }
    
    // 3. Consulta detallada (para expandir)
    $sql_detalle = "SELECT 
                        CASE 
                            WHEN ti.nombre_servicio LIKE '%lavado%' THEN 'Lavados'
                            WHEN ti.nombre_servicio LIKE '%estacionamiento x minuto%' THEN 'Estacionamiento x Minuto'
                            WHEN ti.nombre_servicio LIKE '%ERROR DE INGRESO%' THEN 'Errores de Ingreso'
                            WHEN ti.nombre_servicio LIKE '%MOTOS%' THEN 'Motos'
                            WHEN ti.nombre_servicio LIKE '%PROMOCION%' THEN 'Promociones'
                            ELSE 'Otros Servicios'
                        END as categoria,
                        s.id_ingresos,
                        i.patente,
                        ti.nombre_servicio,
                        s.fecha_salida,
                        s.total
                    FROM salidas s
                    JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE DATE(s.fecha_salida) BETWEEN '$fecha_desde' AND '$fecha_hasta'
                    AND s.fecha_salida > '1900-01-01'
                    ORDER BY s.fecha_salida DESC";
    
    $result_detalle = $conn->query($sql_detalle);
    if (!$result_detalle) {
        throw new Exception('Error en consulta detallada: ' . $conn->error);
    }
    
    $servicios_detalle = [];
    while ($fila = $result_detalle->fetch_assoc()) {
        $servicios_detalle[] = $fila;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'fecha_desde' => $fecha_desde,
        'fecha_hasta' => $fecha_hasta,
        'resumen' => [
            'total_servicios' => intval($resumen['total_servicios'] ?? 0),
            'total_ingresos' => floatval($resumen['total_ingresos'] ?? 0)
        ],
        'categorias' => $categorias,
        'servicios_detalle' => $servicios_detalle
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
