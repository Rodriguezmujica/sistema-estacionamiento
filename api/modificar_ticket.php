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
$stmt_check->close();

// Actualizar el ticket
$sql = "UPDATE ingresos SET idtipo_ingreso = ? WHERE patente = ? AND (salida = 0 OR salida IS NULL) ORDER BY idautos_estacionados DESC LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("is", $id_nuevo_servicio, $patente);
$resultado = $stmt->execute();

// Si la ejecución fue exitosa, consideramos que funcionó
// (affected_rows puede ser 0 si el valor es el mismo)
if ($resultado) {
    echo json_encode([
        'success' => true, 
        'message' => 'Ticket modificado correctamente',
        'ticket_anterior' => $ticket_actual,
        'nuevo_servicio_id' => $id_nuevo_servicio,
        'filas_afectadas' => $stmt->affected_rows
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al ejecutar la actualización',
        'debug' => [
            'ticket_encontrado' => $ticket_actual,
            'nuevo_servicio_id' => $id_nuevo_servicio,
            'error_sql' => $stmt->error
        ]
    ]);
}

$stmt->close();
$conexion->close();
?>