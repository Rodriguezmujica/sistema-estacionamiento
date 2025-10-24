<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

/**
 * API para Exportar Reportes a Documento Plano
 * Genera reportes en formato texto con filtros por fecha
 */

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo "Método no permitido";
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $fechaInicio = $data['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $data['fecha_fin'] ?? date('Y-m-d');
    $formato = $data['formato'] ?? 'txt';
    
    // Validar fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) || 
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        throw new Exception("Formato de fecha inválido. Use YYYY-MM-DD");
    }
    
    if (strtotime($fechaInicio) > strtotime($fechaFin)) {
        throw new Exception("La fecha de inicio no puede ser mayor a la fecha de fin");
    }
    
    // Generar reporte
    $reporte = generarReporteCompleto($fechaInicio, $fechaFin);
    
    // Configurar headers para descarga
    $nombreArchivo = "reporte_estacionamiento_{$fechaInicio}_al_{$fechaFin}.txt";
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . strlen($reporte));
    
    echo $reporte;
    
} catch (Exception $e) {
    http_response_code(400);
    echo "Error: " . $e->getMessage();
}

function generarReporteCompleto($fechaInicio, $fechaFin) {
    global $conn;
    
    $fechaDesde = $fechaInicio . ' 00:00:00';
    $fechaHasta = $fechaFin . ' 23:59:59';
    
    $reporte = "";
    $reporte .= "=" . str_repeat("=", 60) . "\n";
    $reporte .= "REPORTE DE ESTACIONAMIENTO LOS RÍOS\n";
    $reporte .= "Período: " . formatearFecha($fechaInicio) . " al " . formatearFecha($fechaFin) . "\n";
    $reporte .= "Generado: " . date('d/m/Y H:i:s') . "\n";
    $reporte .= "=" . str_repeat("=", 60) . "\n\n";
    
    // 1. RESUMEN GENERAL
    $reporte .= "1. RESUMEN GENERAL\n";
    $reporte .= str_repeat("-", 30) . "\n";
    
    $resumen = obtenerResumenGeneral($fechaDesde, $fechaHasta);
    $reporte .= "Total de servicios: " . number_format($resumen['total_servicios']) . "\n";
    $reporte .= "Total de ingresos: $" . number_format($resumen['total_ingresos'], 0, ',', '.') . "\n";
    $reporte .= "Ticket promedio: $" . number_format($resumen['ticket_promedio'], 0, ',', '.') . "\n\n";
    
    // 2. DESGLOSE POR MÉTODO DE PAGO
    $reporte .= "2. DESGLOSE POR MÉTODO DE PAGO\n";
    $reporte .= str_repeat("-", 40) . "\n";
    
    $desglosePagos = obtenerDesglosePagos($fechaDesde, $fechaHasta);
    foreach ($desglosePagos as $metodo => $datos) {
        $reporte .= sprintf("%-20s: %6d servicios - $%s\n", 
            $metodo, 
            $datos['cantidad'], 
            number_format($datos['total'], 0, ',', '.')
        );
    }
    $reporte .= "\n";
    
    // 3. DESGLOSE POR TIPO DE SERVICIO
    $reporte .= "3. DESGLOSE POR TIPO DE SERVICIO\n";
    $reporte .= str_repeat("-", 40) . "\n";
    
    $servicios = obtenerServicios($fechaDesde, $fechaHasta);
    foreach ($servicios as $servicio) {
        $reporte .= sprintf("%-30s: %6d servicios - $%s\n", 
            $servicio['nombre'], 
            $servicio['cantidad'], 
            number_format($servicio['total'], 0, ',', '.')
        );
    }
    $reporte .= "\n";
    
    // 4. INGRESOS POR DÍA
    $reporte .= "4. INGRESOS POR DÍA\n";
    $reporte .= str_repeat("-", 40) . "\n";
    
    $ingresosPorDia = obtenerIngresosPorDia($fechaDesde, $fechaHasta);
    foreach ($ingresosPorDia as $dia) {
        $reporte .= sprintf("%s: %6d servicios - $%s\n", 
            formatearFecha($dia['fecha']), 
            $dia['servicios'], 
            number_format($dia['total'], 0, ',', '.')
        );
    }
    $reporte .= "\n";
    
    // 5. DETALLE DE SERVICIOS (primeros 50)
    $reporte .= "5. DETALLE DE SERVICIOS (primeros 50)\n";
    $reporte .= str_repeat("-", 60) . "\n";
    $reporte .= sprintf("%-12s %-25s %-15s %-12s %s\n", 
        "Patente", "Servicio", "Método Pago", "Fecha", "Total"
    );
    $reporte .= str_repeat("-", 60) . "\n";
    
    $detalleServicios = obtenerDetalleServicios($fechaDesde, $fechaHasta, 50);
    foreach ($detalleServicios as $servicio) {
        $reporte .= sprintf("%-12s %-25s %-15s %-12s $%s\n", 
            $servicio['patente'],
            substr($servicio['servicio'], 0, 25),
            substr($servicio['metodo_pago'], 0, 15),
            formatearFecha($servicio['fecha']),
            number_format($servicio['total'], 0, ',', '.')
        );
    }
    
    $reporte .= "\n" . str_repeat("=", 60) . "\n";
    $reporte .= "Fin del reporte\n";
    
    return $reporte;
}

function obtenerResumenGeneral($fechaDesde, $fechaHasta) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(i.idautos_estacionados) as total_servicios,
                SUM(COALESCE(s.total, ti.precio, 0)) as total_ingresos
            FROM ingresos i
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.salida = 1
            AND COALESCE(s.fecha_salida, i.fecha_ingreso) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $totalServicios = intval($result['total_servicios'] ?? 0);
    $totalIngresos = floatval($result['total_ingresos'] ?? 0);
    $ticketPromedio = $totalServicios > 0 ? $totalIngresos / $totalServicios : 0;
    
    return [
        'total_servicios' => $totalServicios,
        'total_ingresos' => $totalIngresos,
        'ticket_promedio' => $ticketPromedio
    ];
}

function obtenerDesglosePagos($fechaDesde, $fechaHasta) {
    global $conn;
    
    $sql = "SELECT 
                COALESCE(s.metodo_pago, 'EFECTIVO') as metodo_pago,
                COALESCE(s.tipo_pago, 'manual') as tipo_pago,
                COUNT(i.idautos_estacionados) as cantidad,
                SUM(COALESCE(s.total, ti.precio, 0)) as total
            FROM ingresos i
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.salida = 1
            AND COALESCE(s.fecha_salida, i.fecha_ingreso) BETWEEN ? AND ?
            GROUP BY COALESCE(s.metodo_pago, 'EFECTIVO'), COALESCE(s.tipo_pago, 'manual')
            ORDER BY total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $desglose = [];
    while ($row = $result->fetch_assoc()) {
        $metodo = strtoupper($row['metodo_pago']);
        $tipo = $row['tipo_pago'];
        
        $nombre = $metodo;
        if ($tipo === 'tuu') {
            $nombre .= ' (TUU)';
        } elseif ($tipo === 'manual') {
            $nombre .= ' (Manual)';
        }
        
        $desglose[$nombre] = [
            'cantidad' => intval($row['cantidad']),
            'total' => floatval($row['total'])
        ];
    }
    $stmt->close();
    
    return $desglose;
}

function obtenerServicios($fechaDesde, $fechaHasta) {
    global $conn;
    
    $sql = "SELECT 
                ti.nombre_servicio,
                COUNT(i.idautos_estacionados) as cantidad,
                SUM(COALESCE(s.total, ti.precio, 0)) as total
            FROM ingresos i
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.salida = 1
            AND COALESCE(s.fecha_salida, i.fecha_ingreso) BETWEEN ? AND ?
            GROUP BY ti.nombre_servicio
            ORDER BY total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        $servicios[] = [
            'nombre' => $row['nombre_servicio'],
            'cantidad' => intval($row['cantidad']),
            'total' => floatval($row['total'])
        ];
    }
    $stmt->close();
    
    return $servicios;
}

function obtenerIngresosPorDia($fechaDesde, $fechaHasta) {
    global $conn;
    
    $sql = "SELECT 
                DATE(COALESCE(s.fecha_salida, i.fecha_ingreso)) as fecha,
                COUNT(i.idautos_estacionados) as servicios,
                SUM(COALESCE(s.total, ti.precio, 0)) as total
            FROM ingresos i
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.salida = 1
            AND COALESCE(s.fecha_salida, i.fecha_ingreso) BETWEEN ? AND ?
            GROUP BY DATE(COALESCE(s.fecha_salida, i.fecha_ingreso))
            ORDER BY fecha ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ingresos = [];
    while ($row = $result->fetch_assoc()) {
        $ingresos[] = [
            'fecha' => $row['fecha'],
            'servicios' => intval($row['servicios']),
            'total' => floatval($row['total'])
        ];
    }
    $stmt->close();
    
    return $ingresos;
}

function obtenerDetalleServicios($fechaDesde, $fechaHasta, $limite = 50) {
    global $conn;
    
    $sql = "SELECT 
                i.patente,
                ti.nombre_servicio as servicio,
                COALESCE(s.metodo_pago, 'EFECTIVO') as metodo_pago,
                COALESCE(s.fecha_salida, i.fecha_ingreso) as fecha,
                COALESCE(s.total, ti.precio, 0) as total
            FROM ingresos i
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.salida = 1
            AND COALESCE(s.fecha_salida, i.fecha_ingreso) BETWEEN ? AND ?
            ORDER BY COALESCE(s.fecha_salida, i.fecha_ingreso) DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $fechaDesde, $fechaHasta, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        $servicios[] = [
            'patente' => $row['patente'],
            'servicio' => $row['servicio'],
            'metodo_pago' => $row['metodo_pago'],
            'fecha' => $row['fecha'],
            'total' => floatval($row['total'])
        ];
    }
    $stmt->close();
    
    return $servicios;
}

function formatearFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}
?>
