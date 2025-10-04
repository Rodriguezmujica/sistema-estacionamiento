<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

try {
    $hoy = date('Y-m-d');
    $inicioMes = date('Y-m-01');
    
    // 1. TOTAL DIARIO
    $sqlDiario = "SELECT 
                    COUNT(DISTINCT i.idautos_estacionados) as total_servicios,
                    COALESCE(SUM(s.total), 0) as ingresos_total
                  FROM ingresos i
                  LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                  WHERE DATE(i.fecha_ingreso) = ? 
                  AND s.id_ingresos IS NOT NULL";
    
    $stmtDiario = $conn->prepare($sqlDiario);
    if (!$stmtDiario) {
        throw new Exception("Error en consulta diaria: " . $conn->error);
    }
    $stmtDiario->bind_param('s', $hoy);
    $stmtDiario->execute();
    $resultDiario = $stmtDiario->get_result()->fetch_assoc();
    
    // 2. TOTAL MENSUAL L-V
    $sqlMensualLV = "SELECT 
                        COUNT(DISTINCT i.idautos_estacionados) as total_servicios,
                        COALESCE(SUM(s.total), 0) as ingresos_total
                     FROM ingresos i
                     LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                     WHERE DATE(i.fecha_ingreso) >= ? 
                     AND DATE(i.fecha_ingreso) <= ?
                     AND WEEKDAY(i.fecha_ingreso) < 5
                     AND s.id_ingresos IS NOT NULL";
    
    $stmtMensualLV = $conn->prepare($sqlMensualLV);
    if (!$stmtMensualLV) {
        throw new Exception("Error en consulta mensual L-V: " . $conn->error);
    }
    $stmtMensualLV->bind_param('ss', $inicioMes, $hoy);
    $stmtMensualLV->execute();
    $resultMensualLV = $stmtMensualLV->get_result()->fetch_assoc();
    
    // 3. TOTAL MENSUAL COMPLETO
    $sqlMensualCompleto = "SELECT 
                            COUNT(DISTINCT i.idautos_estacionados) as total_servicios,
                            COALESCE(SUM(s.total), 0) as ingresos_total
                           FROM ingresos i
                           LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                           WHERE DATE(i.fecha_ingreso) >= ? 
                           AND DATE(i.fecha_ingreso) <= ?
                           AND s.id_ingresos IS NOT NULL";
    
    $stmtMensualCompleto = $conn->prepare($sqlMensualCompleto);
    if (!$stmtMensualCompleto) {
        throw new Exception("Error en consulta mensual completo: " . $conn->error);
    }
    $stmtMensualCompleto->bind_param('ss', $inicioMes, $hoy);
    $stmtMensualCompleto->execute();
    $resultMensualCompleto = $stmtMensualCompleto->get_result()->fetch_assoc();
    
    // 4. SERVICIOS ACTIVOS (sin nombre_cliente)
    $sqlActivos = "SELECT 
                    i.idautos_estacionados,
                    i.patente,
                    i.fecha_ingreso,
                    ti.nombre_servicio,
                    CASE 
                        WHEN ti.nombre_servicio LIKE '%lavado%' THEN 'Sí' 
                        ELSE 'No' 
                    END as lavado
                   FROM ingresos i
                   LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                   WHERE i.salida = 0
                   ORDER BY i.fecha_ingreso DESC
                   LIMIT 20";
    
    $stmtActivos = $conn->prepare($sqlActivos);
    if (!$stmtActivos) {
        throw new Exception("Error en consulta activos: " . $conn->error);
    }
    $stmtActivos->execute();
    $resultActivos = $stmtActivos->get_result();
    
    $serviciosActivos = [];
    while ($row = $resultActivos->fetch_assoc()) {
        $serviciosActivos[] = $row;
    }
    
    // Respuesta JSON
    echo json_encode([
        'success' => true,
        'diario' => [
            'servicios' => (int)($resultDiario['total_servicios'] ?? 0),
            'ingresos' => (float)($resultDiario['ingresos_total'] ?? 0)
        ],
        'mensual_lv' => [
            'servicios' => (int)($resultMensualLV['total_servicios'] ?? 0),
            'ingresos' => (float)($resultMensualLV['ingresos_total'] ?? 0)
        ],
        'mensual_completo' => [
            'servicios' => (int)($resultMensualCompleto['total_servicios'] ?? 0),
            'ingresos' => (float)($resultMensualCompleto['ingresos_total'] ?? 0)
        ],
        'servicios_activos' => $serviciosActivos,
        'fecha_consulta' => $hoy,
        'mes_consulta' => $inicioMes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'linea' => $e->getLine()
    ]);
}
