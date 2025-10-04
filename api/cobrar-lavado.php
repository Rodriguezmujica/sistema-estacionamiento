<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Obtener datos del POST
$id_ingreso = $_POST['id_ingreso'] ?? '';
$patente = strtoupper(trim($_POST['patente'] ?? ''));
$metodo_pago = $_POST['metodo_pago'] ?? 'EFECTIVO';

if (!$id_ingreso || !$patente) {
    echo json_encode(['success' => false, 'error' => 'ID de ingreso y patente son obligatorios']);
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Obtener información del lavado pendiente
    $sql_pendiente = "SELECT * FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_pendiente = $conexion->prepare($sql_pendiente);
    $stmt_pendiente->bind_param("i", $id_ingreso);
    $stmt_pendiente->execute();
    $result_pendiente = $stmt_pendiente->get_result();
    
    if ($result_pendiente->num_rows === 0) {
        throw new Exception("No se encontró información del lavado pendiente");
    }
    
    $lavado_pendiente = $result_pendiente->fetch_assoc();
    $stmt_pendiente->close();
    
    // 2. Obtener información del servicio
    $sql_servicio = "SELECT t.nombre_servicio, t.precio FROM ingresos i 
                     JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos 
                     WHERE i.idautos_estacionados = ?";
    $stmt_servicio = $conexion->prepare($sql_servicio);
    $stmt_servicio->bind_param("i", $id_ingreso);
    $stmt_servicio->execute();
    $result_servicio = $stmt_servicio->get_result();
    
    if ($result_servicio->num_rows === 0) {
        throw new Exception("No se encontró información del servicio");
    }
    
    $servicio = $result_servicio->fetch_assoc();
    $precio_base = $servicio['precio'];
    $precio_extra = $lavado_pendiente['precio_extra'];
    $total = $precio_base + $precio_extra;
    $stmt_servicio->close();
    
    // 3. Insertar registro en la tabla 'salidas' con toda la información
    $sql_salida = "INSERT INTO salidas (
        id_ingresos, 
        fecha_salida, 
        total, 
        metodo_pago, 
        motivos_extra, 
        descripcion_extra, 
        precio_extra
    ) VALUES (?, NOW(), ?, ?, ?, ?, ?)";
    
    $stmt_salida = $conexion->prepare($sql_salida);
    $stmt_salida->bind_param("idsssd", 
        $id_ingreso, 
        $total, 
        $metodo_pago, 
        $lavado_pendiente['motivos_extra'], 
        $lavado_pendiente['descripcion_extra'], 
        $precio_extra
    );
    $stmt_salida->execute();
    
    if ($stmt_salida->affected_rows === 0) {
        throw new Exception("No se pudo insertar el registro de salida");
    }
    $stmt_salida->close();
    
    // 4. Actualizar el campo 'salida' en la tabla 'ingresos'
    $sql_ingreso = "UPDATE ingresos SET salida = 1, hora_salida = NOW() WHERE idautos_estacionados = ?";
    $stmt_ingreso = $conexion->prepare($sql_ingreso);
    $stmt_ingreso->bind_param("i", $id_ingreso);
    $stmt_ingreso->execute();
    
    if ($stmt_ingreso->affected_rows === 0) {
        throw new Exception("No se pudo actualizar el estado de salida del ingreso");
    }
    $stmt_ingreso->close();
    
    // 5. Eliminar el registro de lavados_pendientes
    $sql_eliminar = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_eliminar = $conexion->prepare($sql_eliminar);
    $stmt_eliminar->bind_param("i", $id_ingreso);
    $stmt_eliminar->execute();
    $stmt_eliminar->close();
    
    // Confirmar transacción
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Lavado cobrado correctamente',
        'data' => [
            'id_ingreso' => $id_ingreso,
            'patente' => $patente,
            'servicio' => $servicio['nombre_servicio'],
            'precio_base' => $precio_base,
            'precio_extra' => $precio_extra,
            'total' => $total,
            'metodo_pago' => $metodo_pago,
            'fecha' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conexion->close();
?>
