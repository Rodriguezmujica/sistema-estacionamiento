<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Obtener datos del POST
$patente = strtoupper(trim($_POST['patente'] ?? ''));
$id_servicio = $_POST['id_servicio'] ?? '';
$nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
$precio_extra = floatval($_POST['precio_extra'] ?? 0);
$motivos_extra = $_POST['motivos_extra'] ?? [];
$descripcion_extra = trim($_POST['descripcion_extra'] ?? '');
$modificar_ticket = isset($_POST['modificar_ticket']) && $_POST['modificar_ticket'] == '1';
$id_ticket_existente = isset($_POST['id_ticket_existente']) ? intval($_POST['id_ticket_existente']) : null;

// Validar datos
if (!$patente || !$id_servicio) {
    echo json_encode(['success' => false, 'error' => 'Patente e ID de servicio son obligatorios']);
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Obtener información del servicio
    $sql_servicio = "SELECT nombre_servicio, precio FROM tipo_ingreso WHERE idtipo_ingresos = ?";
    $stmt_servicio = $conexion->prepare($sql_servicio);
    $stmt_servicio->bind_param("i", $id_servicio);
    $stmt_servicio->execute();
    $result_servicio = $stmt_servicio->get_result();
    
    if ($result_servicio->num_rows === 0) {
        throw new Exception("Servicio no encontrado");
    }
    
    $servicio = $result_servicio->fetch_assoc();
    $precio_base = $servicio['precio'];
    $total = $precio_base + $precio_extra;
    $stmt_servicio->close();
    
    // 2. Decidir si es modificación o nuevo registro
    if ($modificar_ticket && $id_ticket_existente) {
        // MODIFICAR ticket existente
        $sql_update = "UPDATE ingresos SET idtipo_ingreso = ? WHERE idautos_estacionados = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bind_param("ii", $id_servicio, $id_ticket_existente);
        $stmt_update->execute();
        
        if ($stmt_update->affected_rows === 0) {
            throw new Exception("No se pudo actualizar el ticket");
        }
        
        $id_ingreso = $id_ticket_existente;
        $stmt_update->close();
    } else {
        // NUEVO registro
        $sql_ingreso = "INSERT INTO ingresos (patente, fecha_ingreso, idtipo_ingreso, salida) VALUES (?, NOW(), ?, 0)";
        $stmt_ingreso = $conexion->prepare($sql_ingreso);
        $stmt_ingreso->bind_param("si", $patente, $id_servicio);
        $stmt_ingreso->execute();
        
        if ($stmt_ingreso->affected_rows === 0) {
            throw new Exception("No se pudo insertar el registro de ingreso");
        }
        
        $id_ingreso = $conexion->insert_id;
        $stmt_ingreso->close();
    }
    
    // 3. Guardar o actualizar información adicional en la tabla 'lavados_pendientes'
    $motivos_json = json_encode($motivos_extra, JSON_UNESCAPED_UNICODE);
    
    // Verificar si ya existe un registro
    $sql_check = "SELECT id FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("i", $id_ingreso);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $existe = $result_check->num_rows > 0;
    $stmt_check->close();
    
    if ($existe) {
        // Actualizar registro existente
        $sql_update_pendiente = "UPDATE lavados_pendientes SET 
            patente = ?, 
            motivos_extra = ?, 
            descripcion_extra = ?, 
            precio_extra = ?, 
            nombre_cliente = ?
            WHERE id_ingreso = ?";
        $stmt_pendiente = $conexion->prepare($sql_update_pendiente);
        $stmt_pendiente->bind_param("sssdsi", $patente, $motivos_json, $descripcion_extra, $precio_extra, $nombre_cliente, $id_ingreso);
    } else {
        // Insertar nuevo registro
        $sql_pendiente = "INSERT INTO lavados_pendientes (
            id_ingreso, 
            patente, 
            motivos_extra, 
            descripcion_extra, 
            precio_extra, 
            nombre_cliente
        ) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_pendiente = $conexion->prepare($sql_pendiente);
        $stmt_pendiente->bind_param("isssds", $id_ingreso, $patente, $motivos_json, $descripcion_extra, $precio_extra, $nombre_cliente);
    }
    
    $stmt_pendiente->execute();
    
    if ($stmt_pendiente->affected_rows === 0 && !$existe) {
        throw new Exception("No se pudo guardar la información adicional del lavado");
    }
    $stmt_pendiente->close();
    
    // Confirmar transacción
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Lavado registrado correctamente',
        'data' => [
            'id_ingreso' => $id_ingreso,
            'patente' => $patente,
            'servicio' => $servicio['nombre_servicio'],
            'precio_base' => $precio_base,
            'precio_extra' => $precio_extra,
            'total' => $total,
            'motivos_extra' => $motivos_extra,
            'descripcion_extra' => $descripcion_extra,
            'nombre_cliente' => $nombre_cliente,
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
