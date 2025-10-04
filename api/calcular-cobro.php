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
    echo json_encode(['success' => false, 'error' => 'Patente no recibida']);
    exit;
}

// Buscar el ingreso activo (salida = 0 o NULL) con información de lavados pendientes
$sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, i.idtipo_ingreso,
               t.nombre_servicio, t.precio, t.es_plan,
               lp.motivos_extra, lp.descripcion_extra, lp.precio_extra, lp.nombre_cliente
        FROM ingresos i
        JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
        LEFT JOIN lavados_pendientes lp ON i.idautos_estacionados = lp.id_ingreso
        WHERE i.patente = ? AND (i.salida = 0 OR i.salida IS NULL)
        ORDER BY i.fecha_ingreso DESC LIMIT 1";
        
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $patente);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $id = $row['idautos_estacionados'];
    $fechaIngreso = $row['fecha_ingreso'];
    $nombreServicio = $row['nombre_servicio'];
    $precioServicio = $row['precio'];
    $esPlan = $row['es_plan'];
    $precioExtra = $row['precio_extra'] ?: 0;
    $motivosExtra = $row['motivos_extra'];
    $descripcionExtra = $row['descripcion_extra'];
    $nombreCliente = $row['nombre_cliente'];
    
    // Calcular el costo según el tipo de servicio
    $total = 0;
    $minutos = 0;
    $tipoCalculo = '';
    
    // Si es estacionamiento por minuto
    if (stripos($nombreServicio, 'estacionamiento') !== false && stripos($nombreServicio, 'minuto') !== false) {
        $ahora = new DateTime('now', new DateTimeZone('America/Santiago'));
        $ingreso = new DateTime($fechaIngreso, new DateTimeZone('America/Santiago'));
        $minutos = ceil(($ahora->getTimestamp() - $ingreso->getTimestamp()) / 60);
        $minutos = max($minutos, 1); // Al menos 1 minuto
        
        $precioPorMinuto = 35;
        $precioMinimo = 500;
        $total = max($minutos * $precioPorMinuto, $precioMinimo);
        $tipoCalculo = 'Por minuto';
        
    } 
    // Si es un plan (cliente con suscripción)
    else if ($esPlan == 1) {
        $total = 0; // Los planes no pagan
        $tipoCalculo = 'Plan mensual';
        
    }
    // Si es servicio de lavado o precio fijo
    else {
        $total = $precioServicio + $precioExtra;
        $tipoCalculo = 'Precio fijo';
    }
    
    // Parsear motivos extra si existen
    $motivosArray = [];
    if ($motivosExtra) {
        try {
            $motivosStr = $motivosExtra;
            // Limpiar escapes adicionales
            if (strpos($motivosStr, '\\') !== false) {
                $motivosStr = str_replace(['\\"', '\\'], ['"', ''], $motivosStr);
                $motivosStr = trim($motivosStr, '"');
            }
            $motivosArray = json_decode($motivosStr, true) ?: [];
        } catch (Exception $e) {
            $motivosArray = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'minutos' => $minutos,
        'id' => $id,
        'patente' => $patente,
        'nombre_servicio' => $nombreServicio,
        'tipo_calculo' => $tipoCalculo,
        'fecha_ingreso' => $fechaIngreso,
        'precio_base' => $precioServicio,
        'precio_extra' => $precioExtra,
        'motivos_extra' => $motivosArray,
        'descripcion_extra' => $descripcionExtra,
        'nombre_cliente' => $nombreCliente
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'error' => 'No se encontró ingreso activo para esa patente']);
}

$stmt->close();
$conexion->close();
?>
