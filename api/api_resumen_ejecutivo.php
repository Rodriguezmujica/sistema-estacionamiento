<?php
header('Content-Type: application/json');
require_once '../conexion.php';

/**
 * API para Resumen Ejecutivo Mensual
 * Proporciona estadísticas completas para el jefe
 */

$method = $_SERVER['REQUEST_METHOD'];
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

try {
    switch ($method) {
        case 'GET':
            $resumen = obtenerResumenMensual($conn, $mes, $anio);
            echo json_encode(['success' => true, 'data' => $resumen], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'POST':
            // Actualizar meta mensual
            $data = json_decode(file_get_contents('php://input'), true);
            guardarMetaMensual($conn, $data);
            echo json_encode(['success' => true, 'message' => 'Meta actualizada correctamente']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();

// =============================================
// FUNCIONES
// =============================================

function obtenerResumenMensual($conn, $mes, $anio) {
    $primerDia = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
    $ultimoDia = date('Y-m-t', strtotime($primerDia)) . " 23:59:59";
    
    // Calcular mes anterior
    $mesAnterior = $mes - 1;
    $anioAnterior = $anio;
    if ($mesAnterior < 1) {
        $mesAnterior = 12;
        $anioAnterior--;
    }
    $primerDiaAnterior = "$anioAnterior-" . str_pad($mesAnterior, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
    $ultimoDiaAnterior = date('Y-m-t', strtotime($primerDiaAnterior)) . " 23:59:59";
    
    // 1. INGRESOS TOTALES DEL MES (sin mensuales)
    // Incluye TODOS los ingresos con salida=1 (cobrados)
    $sqlIngresosMes = "SELECT 
                        COUNT(i.idautos_estacionados) as total_servicios,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total_ingresos
                       FROM ingresos i
                       LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                       JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                       WHERE i.salida = 1
                       AND i.fecha_ingreso >= ? 
                       AND i.fecha_ingreso <= ?";
    
    $stmt = $conn->prepare($sqlIngresosMes);
    $stmt->bind_param('ss', $primerDia, $ultimoDia);
    $stmt->execute();
    $resultMes = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $totalIngresos = floatval($resultMes['total_ingresos'] ?? 0);
    $totalServicios = intval($resultMes['total_servicios'] ?? 0);
    
    // 2. INGRESOS DE CLIENTES MENSUALES (opcional)
    $sqlMensuales = "SELECT COUNT(*) as total_clientes, SUM(c.monto_plan) as total_mensuales
                     FROM clientes c
                     WHERE MONTH(c.inicio_plan) = ? AND YEAR(c.inicio_plan) = ?";
    $stmt = $conn->prepare($sqlMensuales);
    $stmt->bind_param('ii', $mes, $anio);
    $stmt->execute();
    $resultMensuales = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $totalMensuales = floatval($resultMensuales['total_mensuales'] ?? 0);
    $totalClientesMensuales = intval($resultMensuales['total_clientes'] ?? 0);
    
    // 3. INGRESOS MES ANTERIOR (para comparación)
    $stmt = $conn->prepare($sqlIngresosMes);
    $stmt->bind_param('ss', $primerDiaAnterior, $ultimoDiaAnterior);
    $stmt->execute();
    $resultMesAnterior = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $totalIngresosMesAnterior = floatval($resultMesAnterior['total_ingresos'] ?? 0);
    
    // Calcular variación porcentual
    $variacionPorcentaje = 0;
    if ($totalIngresosMesAnterior > 0) {
        $variacionPorcentaje = (($totalIngresos - $totalIngresosMesAnterior) / $totalIngresosMesAnterior) * 100;
    }
    
    // 4. TOP 5 SERVICIOS MÁS VENDIDOS
    // Incluye todos los servicios cobrados
    $sqlTop5 = "SELECT 
                    ti.nombre_servicio,
                    COUNT(i.idautos_estacionados) as cantidad,
                    SUM(COALESCE(s.total, ti.precio, 0)) as total_vendido
                FROM ingresos i
                LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                WHERE i.salida = 1
                AND i.fecha_ingreso >= ?
                AND i.fecha_ingreso <= ?
                GROUP BY ti.nombre_servicio
                ORDER BY total_vendido DESC
                LIMIT 5";
    
    $stmt = $conn->prepare($sqlTop5);
    $stmt->bind_param('ss', $primerDia, $ultimoDia);
    $stmt->execute();
    $resultTop5 = $stmt->get_result();
    $top5Servicios = [];
    while ($row = $resultTop5->fetch_assoc()) {
        $top5Servicios[] = [
            'servicio' => $row['nombre_servicio'],
            'cantidad' => intval($row['cantidad']),
            'total' => floatval($row['total_vendido'])
        ];
    }
    $stmt->close();
    
    // 5. DESGLOSE POR MÉTODO DE PAGO
    $sqlMetodosPago = "SELECT 
                        COALESCE(s.metodo_pago, 'EFECTIVO') as metodo,
                        COALESCE(s.tipo_pago, 'manual') as tipo_pago,
                        COUNT(i.idautos_estacionados) as cantidad,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total
                       FROM ingresos i
                       LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                       JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                       WHERE i.salida = 1
                       AND i.fecha_ingreso >= ?
                       AND i.fecha_ingreso <= ?
                       GROUP BY s.metodo_pago, s.tipo_pago";
    
    $stmt = $conn->prepare($sqlMetodosPago);
    $stmt->bind_param('ss', $primerDia, $ultimoDia);
    $stmt->execute();
    $resultMetodos = $stmt->get_result();
    
    $desglosePagos = [
        'efectivo' => 0,
        'tarjetas' => 0,
        'transferencia' => 0,
        'tuu_oficial' => 0,
        'manual_comprobante' => 0
    ];
    
    while ($row = $resultMetodos->fetch_assoc()) {
        $metodo = strtoupper($row['metodo'] ?? 'EFECTIVO');
        $tipoPago = $row['tipo_pago'] ?? 'manual';
        $total = floatval($row['total']);
        
        if ($tipoPago === 'tuu') {
            $desglosePagos['tuu_oficial'] += $total;
        } elseif ($metodo === 'EFECTIVO') {
            $desglosePagos['efectivo'] += $total;
        } elseif ($metodo === 'TRANSFERENCIA') {
            $desglosePagos['transferencia'] += $total;
        } elseif ($metodo === 'TUU') {
            $desglosePagos['tarjetas'] += $total;
        } else {
            $desglosePagos['manual_comprobante'] += $total;
        }
    }
    $stmt->close();
    
    // 6. INGRESOS POR DÍA DEL MES (para el gráfico)
    $sqlPorDia = "SELECT 
                    DATE(i.fecha_ingreso) as fecha,
                    SUM(COALESCE(s.total, ti.precio, 0)) as total_dia,
                    COUNT(i.idautos_estacionados) as servicios_dia
                  FROM ingresos i
                  LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                  JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                  WHERE i.salida = 1
                  AND i.fecha_ingreso >= ?
                  AND i.fecha_ingreso <= ?
                  GROUP BY DATE(i.fecha_ingreso)
                  ORDER BY fecha ASC";
    
    $stmt = $conn->prepare($sqlPorDia);
    $stmt->bind_param('ss', $primerDia, $ultimoDia);
    $stmt->execute();
    $resultPorDia = $stmt->get_result();
    
    $ingresosPorDia = [];
    while ($row = $resultPorDia->fetch_assoc()) {
        $ingresosPorDia[] = [
            'fecha' => $row['fecha'],
            'total' => floatval($row['total_dia']),
            'servicios' => intval($row['servicios_dia'])
        ];
    }
    $stmt->close();
    
    // 7. OBTENER META DEL MES
    $sqlMeta = "SELECT * FROM metas_mensuales WHERE mes = ? AND anio = ? LIMIT 1";
    $stmt = $conn->prepare($sqlMeta);
    $stmt->bind_param('ii', $mes, $anio);
    $stmt->execute();
    $resultMeta = $stmt->get_result();
    $metaData = $resultMeta->fetch_assoc();
    $stmt->close();
    
    $metaMonto = $metaData ? floatval($metaData['meta_monto']) : 0;
    $soloDiasLaborales = $metaData ? intval($metaData['solo_dias_laborales']) : 1;
    $incluirMensuales = $metaData ? intval($metaData['incluir_mensuales']) : 0;
    
    // 8. CALCULAR PROGRESO DE META (solo días laborales si está configurado)
    $totalParaMeta = $totalIngresos;
    if ($incluirMensuales) {
        $totalParaMeta += $totalMensuales;
    }
    
    // Si solo cuenta días laborales, calcular ingresos solo de lun-vie
    if ($soloDiasLaborales) {
        $sqlLaborales = "SELECT SUM(COALESCE(s.total, ti.precio, 0)) as total_laborales
                         FROM ingresos i
                         LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                         JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                         WHERE i.salida = 1
                         AND i.fecha_ingreso >= ?
                         AND i.fecha_ingreso <= ?
                         AND WEEKDAY(i.fecha_ingreso) < 5";
        
        $stmt = $conn->prepare($sqlLaborales);
        $stmt->bind_param('ss', $primerDia, $ultimoDia);
        $stmt->execute();
        $resultLaborales = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $totalParaMeta = floatval($resultLaborales['total_laborales'] ?? 0);
        if ($incluirMensuales) {
            $totalParaMeta += $totalMensuales;
        }
    }
    
    $porcentajeMeta = $metaMonto > 0 ? ($totalParaMeta / $metaMonto) * 100 : 0;
    
    // 🎯 CALCULAR METAS ALCANZADAS (PROGRESIVAS)
    $metasAlcanzadas = 0;
    $metasSobrantes = 0;
    $porcentajeMetaSobrante = 0;
    
    if ($metaMonto > 0 && $totalParaMeta >= $metaMonto) {
        // Se alcanzó la meta base
        $metasAlcanzadas = 1;
        
        // Calcular excedente sobre la meta base
        $excedente = floatval($totalParaMeta - $metaMonto);
        
        // Por cada millón adicional, sumar una meta más
        if ($excedente > 0) {
            $metasAdicionales = floor($excedente / 1000000);
            $metasAlcanzadas += intval($metasAdicionales);
            
            // Calcular progreso hacia la siguiente meta (lo que sobra del último millón)
            $metasSobrantes = $excedente % 1000000;
            $porcentajeMetaSobrante = ($metasSobrantes / 1000000) * 100;
        }
    }
    
    // RETORNAR TODO
    return [
        'mes' => $mes,
        'anio' => $anio,
        'nombre_mes' => date('F', mktime(0, 0, 0, $mes, 1, $anio)),
        'total_ingresos' => $totalIngresos,
        'total_mensuales' => $totalMensuales,
        'total_clientes_mensuales' => $totalClientesMensuales,
        'total_servicios' => $totalServicios,
        'ingresos_mes_anterior' => $totalIngresosMesAnterior,
        'variacion_porcentaje' => round($variacionPorcentaje, 2),
        'top_servicios' => $top5Servicios,
        'desglose_pagos' => $desglosePagos,
        'ingresos_por_dia' => $ingresosPorDia,
        'meta' => [
            'monto' => $metaMonto,
            'solo_dias_laborales' => $soloDiasLaborales,
            'incluir_mensuales' => $incluirMensuales,
            'total_para_meta' => $totalParaMeta,
            'porcentaje_cumplido' => round($porcentajeMeta, 2),
            'falta' => max($metaMonto - $totalParaMeta, 0),
            'metas_alcanzadas' => $metasAlcanzadas,
            'metas_sobrantes' => $metasSobrantes,
            'porcentaje_meta_sobrante' => round($porcentajeMetaSobrante, 2)
        ]
    ];
}

function guardarMetaMensual($conn, $data) {
    $mes = intval($data['mes']);
    $anio = intval($data['anio']);
    $metaMonto = floatval($data['meta_monto']);
    $soloDiasLaborales = intval($data['solo_dias_laborales'] ?? 1);
    $incluirMensuales = intval($data['incluir_mensuales'] ?? 0);
    
    if ($metaMonto < 0) {
        throw new Exception("La meta no puede ser negativa");
    }
    
    $sql = "INSERT INTO metas_mensuales (mes, anio, meta_monto, solo_dias_laborales, incluir_mensuales)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                meta_monto = VALUES(meta_monto),
                solo_dias_laborales = VALUES(solo_dias_laborales),
                incluir_mensuales = VALUES(incluir_mensuales)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iidii', $mes, $anio, $metaMonto, $soloDiasLaborales, $incluirMensuales);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar meta: " . $stmt->error);
    }
    
    $stmt->close();
}

?>

