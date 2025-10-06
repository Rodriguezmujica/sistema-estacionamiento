<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

try {
    $id_ingreso = $_POST['id_ingreso'] ?? null;
    $patente = strtoupper(trim($_POST['patente'] ?? ''));
    $id_servicio = $_POST['id_servicio'] ?? null;
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
    $precio_extra = floatval($_POST['precio_extra'] ?? 0);
    $motivos_extra = $_POST['motivos_extra'] ?? '[]';
    $descripcion_extra = trim($_POST['descripcion_extra'] ?? '');
    
    if (!$id_ingreso || !$patente || !$id_servicio) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    
    // Verificar que el ticket existe
    $stmt_verificar = $conn->prepare("SELECT * FROM ingresos WHERE idautos_estacionados = ? AND patente = ?");
    $stmt_verificar->bind_param('is', $id_ingreso, $patente);
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();
    
    if ($resultado->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Ticket no encontrado']);
        exit;
    }
    
    $conn->begin_transaction();
    
    // 1. Actualizar el tipo de servicio en ingresos
    $stmt_ingreso = $conn->prepare("UPDATE ingresos SET idtipo_ingreso = ?, nombre_cliente = ? WHERE idautos_estacionados = ?");
    $stmt_ingreso->bind_param('isi', $id_servicio, $nombre_cliente, $id_ingreso);
    
    if (!$stmt_ingreso->execute()) {
        throw new Exception("Error actualizando ingreso: " . $stmt_ingreso->error);
    }
    
    // 2. Verificar si ya existe en lavados_pendientes
    $stmt_verificar_lavado = $conn->prepare("SELECT id FROM lavados_pendientes WHERE id_ingreso = ?");
    $stmt_verificar_lavado->bind_param('i', $id_ingreso);
    $stmt_verificar_lavado->execute();
    $resultado_lavado = $stmt_verificar_lavado->get_result();
    
    if ($resultado_lavado->num_rows > 0) {
        // Actualizar lavado existente
        $stmt_lavado = $conn->prepare("
            UPDATE lavados_pendientes 
            SET precio_extra = ?, motivos_extra = ?, descripcion_extra = ?, fecha_modificacion = NOW()
            WHERE id_ingreso = ?
        ");
        $stmt_lavado->bind_param('dssi', $precio_extra, $motivos_extra, $descripcion_extra, $id_ingreso);
    } else {
        // Insertar nuevo lavado
        $stmt_lavado = $conn->prepare("
            INSERT INTO lavados_pendientes (id_ingreso, precio_extra, motivos_extra, descripcion_extra, fecha_creacion)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt_lavado->bind_param('idss', $id_ingreso, $precio_extra, $motivos_extra, $descripcion_extra);
    }
    
    if (!$stmt_lavado->execute()) {
        throw new Exception("Error actualizando lavado: " . $stmt_lavado->error);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ticket modificado correctamente',
        'id_ingreso' => $id_ingreso,
        'patente' => $patente
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>