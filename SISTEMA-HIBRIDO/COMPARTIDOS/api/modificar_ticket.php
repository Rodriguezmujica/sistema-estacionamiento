<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

// Obtener datos del POST
$patente = strtoupper(trim($_POST['patente'] ?? ''));
$id_nuevo_servicio = trim($_POST['id_nuevo_servicio'] ?? '');

// Validar datos
if (!$patente || !$id_nuevo_servicio) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos', 'datos_recibidos' => ['patente' => $patente, 'id_servicio' => $id_nuevo_servicio]]);
    exit;
}

// Primero verificar si el ticket existe
$sql_check = "SELECT idautos_estacionados, patente, idtipo_ingreso, salida FROM ingresos WHERE patente = ? AND (salida = 0 OR salida IS NULL) ORDER BY idautos_estacionados DESC LIMIT 1";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("s", $patente);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'No se encontró ticket activo para la patente: ' . $patente,
        'debug' => 'No hay registros con salida=0 o NULL para esta patente'
    ]);
    $stmt_check->close();
    $conexion->close();
    exit;
}

$ticket_actual = $result_check->fetch_assoc();
$id_ticket = $ticket_actual['idautos_estacionados'];
$stmt_check->close();

// Iniciar transacción para asegurar consistencia
$conexion->begin_transaction();

try {
    // Actualizar el ticket
    $sql = "UPDATE ingresos SET idtipo_ingreso = ? WHERE patente = ? AND (salida = 0 OR salida IS NULL) ORDER BY idautos_estacionados DESC LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $id_nuevo_servicio, $patente);
    $resultado = $stmt->execute();
    
    if (!$resultado) {
        throw new Exception('Error al actualizar el ticket: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // ✅ CORRECCIÓN: Si se cambia a "Error de ingreso" (ID 19), limpiar datos de lavado
    if ($id_nuevo_servicio == 19) {
        $sql_delete_lavado = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
        $stmt_delete = $conexion->prepare($sql_delete_lavado);
        $stmt_delete->bind_param("i", $id_ticket);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Ticket modificado correctamente',
        'ticket_anterior' => $ticket_actual,
        'nuevo_servicio_id' => $id_nuevo_servicio,
        'lavado_limpiado' => ($id_nuevo_servicio == 19)
    ]);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode([
        'success' => false, 
        'error' => 'Error al modificar ticket: ' . $e->getMessage()
    ]);
}

$conexion->close();
?>