<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

date_default_timezone_set('America/Santiago');

function ejecutarConsulta($conn, $sql) {
    $resultado = $conn->query($sql);
    if (!$resultado) {
        return ['servicios' => 0, 'ingresos' => 0];
    }
    $fila = $resultado->fetch_assoc();
    return [
        'servicios' => intval($fila['servicios'] ?? 0),
        'ingresos' => floatval($fila['ingresos'] ?? 0)
    ];
}

try {
    // 1. Reporte Diario (últimos 7 días para mostrar datos)
    $fecha_hoy = date('Y-m-d');
    $sql_diario = "SELECT COUNT(s.id_ingresos) as servicios, SUM(s.total) as ingresos 
                   FROM salidas s
                   WHERE DATE(s.fecha_salida) = '$fecha_hoy'";
    $reporte_diario = ejecutarConsulta($conn, $sql_diario);

    // 2. Reporte Mensual (Lunes a Viernes) - último mes con datos
    $mes_actual = date('Y-m');
    $sql_mensual_lv = "SELECT COUNT(s.id_ingresos) as servicios, SUM(s.total) as ingresos 
                       FROM salidas s
                       WHERE DATE_FORMAT(s.fecha_salida, '%Y-%m') = '$mes_actual' AND DAYOFWEEK(s.fecha_salida) BETWEEN 2 AND 6";
    $reporte_mensual_lv = ejecutarConsulta($conn, $sql_mensual_lv);

    // 3. Reporte Mensual Completo (Lunes a Sábado) - último mes con datos
    $sql_mensual_completo = "SELECT COUNT(s.id_ingresos) as servicios, SUM(s.total) as ingresos 
                             FROM salidas s
                             WHERE DATE_FORMAT(s.fecha_salida, '%Y-%m') = '$mes_actual' AND DAYOFWEEK(s.fecha_salida) != 1";
    $reporte_mensual_completo = ejecutarConsulta($conn, $sql_mensual_completo);
    
    // Si no hay datos en el mes actual, mostrar datos del último mes disponible
    if ($reporte_diario['servicios'] == 0 && $reporte_mensual_lv['servicios'] == 0) {
        $sql_ultimo_mes = "SELECT DATE_FORMAT(fecha_salida, '%Y-%m') as mes 
                          FROM salidas 
                          ORDER BY fecha_salida DESC 
                          LIMIT 1";
        $result_ultimo_mes = $conn->query($sql_ultimo_mes);
        if ($result_ultimo_mes && $row = $result_ultimo_mes->fetch_assoc()) {
            $ultimo_mes = $row['mes'];
            $sql_diario = "SELECT COUNT(s.id_ingresos) as servicios, SUM(s.total) as ingresos 
                          FROM salidas s
                          WHERE DATE(s.fecha_salida) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $reporte_diario = ejecutarConsulta($conn, $sql_diario);
            
            $sql_mensual_lv = "SELECT COUNT(s.id_ingresos) as servicios, SUM(s.total) as ingresos 
                              FROM salidas s
                              WHERE DATE_FORMAT(s.fecha_salida, '%Y-%m') = '$ultimo_mes' AND DAYOFWEEK(s.fecha_salida) BETWEEN 2 AND 6";
            $reporte_mensual_lv = ejecutarConsulta($conn, $sql_mensual_lv);
            
            $sql_mensual_completo = "SELECT COUNT(s.id_ingresos) as servicios, SUM(s.total) as ingresos 
                                    FROM salidas s
                                    WHERE DATE_FORMAT(s.fecha_salida, '%Y-%m') = '$ultimo_mes' AND DAYOFWEEK(s.fecha_salida) != 1";
            $reporte_mensual_completo = ejecutarConsulta($conn, $sql_mensual_completo);
        }
    }

    // 4. Servicios Activos
    $sql_activos = "SELECT 
                        i.patente, 
                        COALESCE(lp.nombre_cliente, 'Cliente General') as cliente,
                        ti.nombre_servicio, 
                        i.fecha_ingreso,
                        CASE WHEN ti.nombre_servicio LIKE '%lavado%' THEN 'Sí' ELSE 'No' END AS lavado
                    FROM ingresos i
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    LEFT JOIN lavados_pendientes lp ON i.idautos_estacionados = lp.id_ingreso
                    WHERE i.salida = 0 OR i.salida IS NULL
                    ORDER BY i.fecha_ingreso DESC";
    
    $result_activos = $conn->query($sql_activos);
    $servicios_activos = [];
    if ($result_activos) {
        while ($fila = $result_activos->fetch_assoc()) {
            $servicios_activos[] = $fila;
        }
    }

    // Ensamblar respuesta final
    echo json_encode([
        'success' => true,
        'diario' => $reporte_diario,
        'mensual_lv' => $reporte_mensual_lv,
        'mensual_completo' => $reporte_mensual_completo,
        'servicios_activos' => $servicios_activos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}

$conn->close();
?>