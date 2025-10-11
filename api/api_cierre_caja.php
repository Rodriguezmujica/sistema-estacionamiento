<?php
header('Content-Type: application/json');
require_once '../conexion.php';

/**
 * API para Cierre de Caja Diaria
 * Muestra desglose completo por método de pago y tipo
 */

$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$fecha_desde = $fecha . ' 00:00:00';
$fecha_hasta = $fecha . ' 23:59:59';

try {
    // 1. TOTAL GENERAL DEL DÍA
    $sqlTotal = "SELECT 
                    COUNT(i.idautos_estacionados) as total_servicios,
                    SUM(COALESCE(s.total, ti.precio, 0)) as total_ingresos
                 FROM ingresos i
                 LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                 JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                 WHERE i.salida = 1
                 AND CASE 
                     WHEN s.fecha_salida IS NULL 
                     THEN i.fecha_ingreso 
                     ELSE s.fecha_salida 
                 END BETWEEN ? AND ?
                 AND ti.es_plan = 0
                 AND ti.idtipo_ingresos NOT IN (19)";
    
    $stmt = $conn->prepare($sqlTotal);
    $stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $resultTotal = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $totalServicios = intval($resultTotal['total_servicios'] ?? 0);
    $totalIngresos = floatval($resultTotal['total_ingresos'] ?? 0);
    
    // 2. DESGLOSE POR MÉTODO DE PAGO Y TIPO
    $sqlDesglose = "SELECT 
                        COALESCE(s.metodo_pago, 'EFECTIVO') as metodo_pago,
                        COALESCE(s.tipo_pago, 'manual') as tipo_pago,
                        COUNT(i.idautos_estacionados) as cantidad,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total
                    FROM ingresos i
                    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE i.salida = 1
                    AND CASE 
                        WHEN s.fecha_salida IS NULL 
                        THEN i.fecha_ingreso 
                        ELSE s.fecha_salida 
                    END BETWEEN ? AND ?
                    AND ti.es_plan = 0
                    AND ti.idtipo_ingresos NOT IN (19)
                    GROUP BY s.metodo_pago, s.tipo_pago
                    ORDER BY total DESC";
    
    $stmt = $conn->prepare($sqlDesglose);
    $stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $resultDesglose = $stmt->get_result();
    
    $desglose = [
        'efectivo_manual' => ['cantidad' => 0, 'total' => 0],
        'tuu_efectivo' => ['cantidad' => 0, 'total' => 0],
        'tuu_debito' => ['cantidad' => 0, 'total' => 0],
        'tuu_credito' => ['cantidad' => 0, 'total' => 0],
        'transferencia' => ['cantidad' => 0, 'total' => 0],
        'otros' => ['cantidad' => 0, 'total' => 0]
    ];
    
    while ($row = $resultDesglose->fetch_assoc()) {
        $metodo = strtoupper($row['metodo_pago'] ?? 'EFECTIVO');
        $tipo = $row['tipo_pago'] ?? 'manual';
        $cantidad = intval($row['cantidad']);
        $total = floatval($row['total']);
        
        if ($tipo === 'tuu') {
            // Pagos con TUU (boletas oficiales)
            if ($metodo === 'EFECTIVO') {
                $desglose['tuu_efectivo']['cantidad'] += $cantidad;
                $desglose['tuu_efectivo']['total'] += $total;
            } elseif ($metodo === 'TUU') {
                // Ver metodo_tarjeta para distinguir débito/crédito
                // Por ahora agrupamos como TUU general
                $desglose['tuu_debito']['cantidad'] += $cantidad;
                $desglose['tuu_debito']['total'] += $total;
            }
        } else {
            // Pagos manuales (comprobantes internos)
            if ($metodo === 'EFECTIVO') {
                $desglose['efectivo_manual']['cantidad'] += $cantidad;
                $desglose['efectivo_manual']['total'] += $total;
            } elseif ($metodo === 'TRANSFERENCIA') {
                $desglose['transferencia']['cantidad'] += $cantidad;
                $desglose['transferencia']['total'] += $total;
            } else {
                $desglose['otros']['cantidad'] += $cantidad;
                $desglose['otros']['total'] += $total;
            }
        }
    }
    $stmt->close();
    
    // 3. DESGLOSE POR CATEGORÍA DE SERVICIO
    $sqlCategorias = "SELECT 
                        CASE 
                            WHEN ti.nombre_servicio LIKE '%lavado%' THEN 'Lavados'
                            WHEN ti.nombre_servicio LIKE '%estacionamiento%' THEN 'Estacionamiento'
                            WHEN ti.nombre_servicio LIKE '%MOTOS%' THEN 'Motos'
                            WHEN ti.nombre_servicio LIKE '%PROMOCION%' OR ti.nombre_servicio LIKE '%PROMO%' THEN 'Promociones'
                            ELSE 'Otros'
                        END as categoria,
                        COUNT(i.idautos_estacionados) as cantidad,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total
                      FROM ingresos i
                      LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                      JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                      WHERE i.salida = 1
                      AND CASE 
                          WHEN s.fecha_salida IS NULL 
                          THEN i.fecha_ingreso 
                          ELSE s.fecha_salida 
                      END BETWEEN ? AND ?
                      AND ti.es_plan = 0
                      AND ti.idtipo_ingresos NOT IN (19)
                      GROUP BY categoria
                      ORDER BY total DESC";
    
    $stmt = $conn->prepare($sqlCategorias);
    $stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $resultCategorias = $stmt->get_result();
    
    $categorias = [];
    while ($row = $resultCategorias->fetch_assoc()) {
        $categorias[] = [
            'categoria' => $row['categoria'],
            'cantidad' => intval($row['cantidad']),
            'total' => floatval($row['total'])
        ];
    }
    $stmt->close();
    
    // 4. DETALLE DE SERVICIOS (para la tabla expandible)
    $sqlDetalle = "SELECT 
                      i.patente,
                      ti.nombre_servicio,
                      COALESCE(s.metodo_pago, 'EFECTIVO') as metodo_pago,
                      COALESCE(s.tipo_pago, 'manual') as tipo_pago,
                      CASE 
                          WHEN s.fecha_salida IS NULL 
                          THEN i.fecha_ingreso 
                          ELSE s.fecha_salida 
                      END as fecha_cobro,
                      COALESCE(s.total, ti.precio, 0) as total
                   FROM ingresos i
                   LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                   JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                   WHERE i.salida = 1
                   AND CASE 
                       WHEN s.fecha_salida IS NULL 
                       THEN i.fecha_ingreso 
                       ELSE s.fecha_salida 
                   END BETWEEN ? AND ?
                   AND ti.es_plan = 0
                   AND ti.idtipo_ingresos NOT IN (19)
                   ORDER BY fecha_cobro ASC";
    
    $stmt = $conn->prepare($sqlDetalle);
    $stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $resultDetalle = $stmt->get_result();
    
    $servicios = [];
    while ($row = $resultDetalle->fetch_assoc()) {
        $servicios[] = [
            'patente' => $row['patente'],
            'servicio' => $row['nombre_servicio'],
            'metodo_pago' => $row['metodo_pago'],
            'tipo_pago' => $row['tipo_pago'],
            'fecha' => $row['fecha_cobro'],
            'total' => floatval($row['total'])
        ];
    }
    $stmt->close();
    
    // RESPUESTA
    echo json_encode([
        'success' => true,
        'fecha' => $fecha,
        'resumen' => [
            'total_servicios' => $totalServicios,
            'total_ingresos' => $totalIngresos
        ],
        'desglose_pago' => $desglose,
        'categorias' => $categorias,
        'servicios_detalle' => $servicios
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>

