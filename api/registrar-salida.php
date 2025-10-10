<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

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

    // 1. Insertar en tabla salidas
    $sql_salida = "INSERT INTO salidas (id_ingresos, fecha_salida, total) VALUES (?, ?, ?)";
    $stmt_salida = $conexion->prepare($sql_salida);
    $stmt_salida->bind_param("isd", $id_ingreso, $fecha_salida, $total);
    $stmt_salida->execute();
    $stmt_salida->close();
    
    // 2. Actualizar registro de ingreso (marcar como salido)
    $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("i", $id_ingreso);
    $stmt_update->execute();
    $stmt_update->close();
    
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
