<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

// Obtener datos
$id_ingreso = isset($_POST['id_ingreso']) ? intval($_POST['id_ingreso']) : 0;
$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

if (!$id_ingreso || !$patente) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

date_default_timezone_set('America/Santiago');
$fecha_salida = date('Y-m-d H:i:s');

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 🔍 Verificar tipo de ingreso
    $sql_tipo = "SELECT idtipo_ingreso FROM ingresos WHERE idautos_estacionados = ?";
    $stmt_tipo = $conexion->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $id_ingreso);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    $tipo = $result_tipo->fetch_assoc();
    $stmt_tipo->close();

    // ✅ CORRECCIÓN: ID 19 es "ERROR DE INGRESO" (no ID 1)
    if ($tipo && intval($tipo['idtipo_ingreso']) === 19) {
        // ⚡ Si es "Error de ingreso"
        $total = 1; // forzar total fijo a $1
        // limpiar extras de lavados_pendientes
        $sql_delete = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
        $stmt_delete = $conexion->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_ingreso);
        $stmt_delete->execute();
        $stmt_delete->close();
    }

    // 1. Obtener información del lavado pendiente (si existe)
    $motivos_extra = null;
    $descripcion_extra = null;
    $precio_extra = 0;
    
    $sql_pendiente = "SELECT motivos_extra, descripcion_extra, precio_extra FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_pendiente = $conexion->prepare($sql_pendiente);
    $stmt_pendiente->bind_param("i", $id_ingreso);
    $stmt_pendiente->execute();
    $result_pendiente = $stmt_pendiente->get_result();
    
    if ($lavado_pendiente = $result_pendiente->fetch_assoc()) {
        $motivos_extra = $lavado_pendiente['motivos_extra'];
        $descripcion_extra = $lavado_pendiente['descripcion_extra'];
        $precio_extra = floatval($lavado_pendiente['precio_extra']);
    }
    $stmt_pendiente->close();

    // 2. Insertar en tabla salidas (con datos de lavado si existen)
    if (!empty($motivos_extra) || !empty($descripcion_extra) || $precio_extra > 0) {
        $sql_salida = "INSERT INTO salidas (id_ingresos, fecha_salida, total, motivos_extra, descripcion_extra, precio_extra) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_salida = $conexion->prepare($sql_salida);
        $stmt_salida->bind_param("isdssd", $id_ingreso, $fecha_salida, $total, $motivos_extra, $descripcion_extra, $precio_extra);
    } else {
        $sql_salida = "INSERT INTO salidas (id_ingresos, fecha_salida, total) VALUES (?, ?, ?)";
        $stmt_salida = $conexion->prepare($sql_salida);
        $stmt_salida->bind_param("isd", $id_ingreso, $fecha_salida, $total);
    }
    $stmt_salida->execute();
    $stmt_salida->close();
    
    // 3. Actualizar registro de ingreso (marcar como salido)
    $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("i", $id_ingreso);
    $stmt_update->execute();
    $stmt_update->close();
    
    // 4. Eliminar el registro de lavados_pendientes (si existe)
    $sql_eliminar = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_eliminar = $conexion->prepare($sql_eliminar);
    $stmt_eliminar->bind_param("i", $id_ingreso);
    $stmt_eliminar->execute();
    $stmt_eliminar->close();
    
    // Confirmar transacción
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Salida registrada correctamente',
        'id_ingreso' => $id_ingreso,
        'fecha_salida' => $fecha_salida,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Error al registrar salida: ' . $e->getMessage()
    ]);
}

$conexion->close();
?>
