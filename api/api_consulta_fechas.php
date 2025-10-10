<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

date_default_timezone_set('America/Santiago');

try {
    // Obtener parámetros de fecha
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';

    // Si no llegan fechas, usamos el día anterior como fallback
    if (empty($fecha_desde) || empty($fecha_hasta)) {
        $ayer = date('Y-m-d', strtotime('-1 day'));
        $fecha_desde = $ayer;
        $fecha_hasta = $ayer;
    }

    // Asegurar que fecha_hasta incluya todo el día
    $fecha_desde_completa = $fecha_desde . ' 00:00:00';
    $fecha_hasta_completa = $fecha_hasta . ' 23:59:59';    

    // 1. Consulta de resumen (total servicios e ingresos)
    // Cambiamos la lógica para empezar desde 'ingresos' y hacer LEFT JOIN a 'salidas'
    $sql_resumen = "SELECT 
                        COUNT(i.idautos_estacionados) as total_servicios,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total_ingresos
                    FROM ingresos i
                    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE i.salida = 1 
                    AND CASE 
                        WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00' 
                        THEN i.fecha_ingreso 
                        ELSE s.fecha_salida 
                    END BETWEEN '$fecha_desde_completa' AND '$fecha_hasta_completa'";
    
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
                        COUNT(i.idautos_estacionados) as cantidad_servicios,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total_categoria,
                        GROUP_CONCAT(DISTINCT ti.nombre_servicio ORDER BY ti.nombre_servicio SEPARATOR ', ') as tipos_servicios                    
                    FROM ingresos i
                    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE i.salida = 1 
                    AND CASE 
                        WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00' 
                        THEN i.fecha_ingreso 
                        ELSE s.fecha_salida 
                    END BETWEEN '$fecha_desde_completa' AND '$fecha_hasta_completa'
                    GROUP BY categoria
                    HAVING cantidad_servicios > 0
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
                        i.idautos_estacionados as id_ingresos,
                        i.patente,
                        ti.nombre_servicio,
                        CASE 
                            WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00' 
                            THEN i.fecha_ingreso 
                            ELSE s.fecha_salida 
                        END as fecha_salida,
                        CASE 
                            WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00' 
                            THEN i.fecha_ingreso 
                            ELSE s.fecha_salida 
                        END as fecha_salida_real,
                        COALESCE(s.total, ti.precio, 0) as total
                    FROM ingresos i
                    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE i.salida = 1 
                    AND CASE 
                        WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00' 
                        THEN i.fecha_ingreso 
                        ELSE s.fecha_salida 
                    END BETWEEN '$fecha_desde_completa' AND '$fecha_hasta_completa'
                    ORDER BY fecha_salida DESC";
    
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
