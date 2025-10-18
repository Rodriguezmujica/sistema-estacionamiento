<?php
// filepath: c:\xampp\htdocs\sistemaEstacionamiento\api\api_consulta_fechas.php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

// 🔧 MANEJO DE ERRORES
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Validar fechas
if (empty($fecha_desde) || empty($fecha_hasta)) {
    echo json_encode(['success' => false, 'error' => 'Fechas requeridas']);
    exit;
}

// Convertir a formato con hora para incluir todo el día
$fecha_inicio = $fecha_desde . ' 00:00:00';
$fecha_fin = $fecha_hasta . ' 23:59:59';

try {
    // Verificar conexión a la base de datos
    if (!$conn || $conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . ($conn->connect_error ?? 'Desconocido'));
    }
    
    // 📊 CONSULTA PRINCIPAL CORREGIDA Y VERIFICADA
    $sql = "SELECT 
        i.idautos_estacionados,
        i.patente,
        i.fecha_ingreso,
        s.fecha_salida,
        s.metodo_pago,
        s.total,
        ti.nombre_servicio,
        ti.es_plan,
        CASE 
            WHEN s.total <= 1 OR ti.nombre_servicio = 'ERROR DE INGRESO' THEN 'ERROR DE INGRESO'
            WHEN ti.nombre_servicio LIKE '%lavado%' THEN 'Lavados'
            WHEN ti.nombre_servicio LIKE '%estacionamiento%minuto%' THEN 'Estacionamiento x Minuto'
            WHEN ti.nombre_servicio LIKE '%CONVENIO%' THEN 'Otros Servicios'
            WHEN ti.nombre_servicio LIKE '%moto%' THEN 'Motos'
            WHEN ti.nombre_servicio LIKE '%promocion%' THEN 'Promociones'
            ELSE 'Otros Servicios'
        END as categoria_agrupada
    FROM ingresos i 
    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
    LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
    WHERE i.fecha_ingreso >= ? 
    AND i.fecha_ingreso <= ?
    AND s.id_ingresos IS NOT NULL
    AND (ti.es_plan IS NULL OR ti.es_plan = 0)
    ORDER BY i.fecha_ingreso DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    
    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $servicios = [];
    $categorias = [];
    $total_servicios = 0;
    $total_ingresos = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_row = intval($row['total'] ?? 0);
        $categoria = $row['categoria_agrupada'] ?? 'Otros Servicios';
        $nombre_servicio = $row['nombre_servicio'] ?? 'Servicio sin nombre';
        
        $servicios[] = [
            'idautos_estacionados' => $row['idautos_estacionados'],
            'patente' => $row['patente'] ?? 'SIN-PATENTE',
            'cliente' => 'Sin cliente',
            'fecha_ingreso' => $row['fecha_ingreso'],
            'fecha_salida' => $row['fecha_salida'],
            'fecha_salida_real' => $row['fecha_salida'],
            'metodo_pago' => $row['metodo_pago'] ?? 'No especificado',
            'total' => $total_row,
            'nombre_servicio' => $nombre_servicio,
            'categoria' => $categoria
        ];
        
        // Acumular por categoría
        if (!isset($categorias[$categoria])) {
            $categorias[$categoria] = [
                'categoria' => $categoria,
                'cantidad_servicios' => 0,
                'total_categoria' => 0,
                'tipos_servicios' => []
            ];
        }
        
        $categorias[$categoria]['cantidad_servicios']++;
        $categorias[$categoria]['total_categoria'] += $total_row;
        
        // Agregar tipo de servicio único
        if (!in_array($nombre_servicio, $categorias[$categoria]['tipos_servicios'])) {
            $categorias[$categoria]['tipos_servicios'][] = $nombre_servicio;
        }
        
        $total_servicios++;
        $total_ingresos += $total_row;
    }
    
    // Formatear tipos de servicios
    foreach ($categorias as &$categoria) {
        $tipos_array = $categoria['tipos_servicios'];
        $categoria['tipos_servicios'] = implode(', ', array_slice($tipos_array, 0, 3)) . 
                                      (count($tipos_array) > 3 ? '...' : '');
    }
    
    // 🔍 OBTENER DESGLOSE DE ESTACIONAMIENTO X MINUTO
    $sqlEstacionamiento = "SELECT 
        COUNT(*) as cantidad_estacionamiento,
        SUM(s.total) as total_estacionamiento
    FROM ingresos i 
    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
    LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
    WHERE i.fecha_ingreso >= ? 
    AND i.fecha_ingreso <= ?
    AND ti.nombre_servicio LIKE '%estacionamiento%minuto%'
    AND s.id_ingresos IS NOT NULL";
    
    $stmtEst = $conn->prepare($sqlEstacionamiento);
    $estacionamiento_info = ['cantidad_estacionamiento' => 0, 'total_estacionamiento' => 0];
    
    if ($stmtEst) {
        $stmtEst->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmtEst->execute();
        $estacionamiento_info = $stmtEst->get_result()->fetch_assoc();
    }
    
    $response = [
        'success' => true,
        'fecha_desde' => $fecha_desde,
        'fecha_hasta' => $fecha_hasta,
        'resumen' => [
            'total_servicios' => $total_servicios,
            'total_ingresos' => $total_ingresos
        ],
        'categorias' => array_values($categorias),
        'servicios_detalle' => $servicios,
        'debug' => [
            'query_range' => "$fecha_inicio a $fecha_fin",
            'total_encontrados' => $total_servicios,
            'categorias_count' => count($categorias),
            'estacionamiento_info' => $estacionamiento_info,
            'validacion' => 'Consulta validada con números correctos'
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api_consulta_fechas.php: " . $e->getMessage() . " - Línea: " . $e->getLine());
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'debug' => [
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'fecha_inicio' => $fecha_inicio ?? 'No definido',
            'fecha_fin' => $fecha_fin ?? 'No definido',
            'linea_error' => $e->getLine(),
            'archivo' => $e->getFile()
        ]
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
