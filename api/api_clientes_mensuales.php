<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener todos los clientes
            $result = $conn->query("SELECT *, idclientes as id, CONCAT(nombres, ' ', apellidos) as nombre_cliente FROM clientes ORDER BY fecha_proximo_vencimiento DESC");
            $clientes = [];
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $clientes]);
            break;

        case 'POST':
            // Crear o actualizar un cliente
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['patente'], $data['nombres'], $data['dia_pago_mensual'], $data['fecha_proximo_vencimiento'], $data['monto_plan'])) {
                throw new Exception("Faltan datos requeridos.");
            }

            $id = $data['id'] ?? null;
            $patente = $conn->real_escape_string($data['patente']);
            $nombres = $conn->real_escape_string($data['nombres']);
            $apellidos = $conn->real_escape_string($data['apellidos'] ?? '');
            $tipo_vehiculo = $conn->real_escape_string($data['tipo_vehiculo'] ?? '');
            $dia_pago_mensual = intval($data['dia_pago_mensual']);
            $fecha_proximo_vencimiento = $conn->real_escape_string($data['fecha_proximo_vencimiento']);
            $monto_plan = floatval($data['monto_plan']);
            $notas = $conn->real_escape_string($data['notas'] ?? '');

            if ($id) {
                // Actualizar
                $sql = "UPDATE clientes SET 
                            patente = '$patente', 
                            nombres = '$nombres', 
                            apellidos = '$apellidos',
                            tipo_vehiculo = '$tipo_vehiculo', 
                            dia_pago_mensual = $dia_pago_mensual, 
                            fecha_proximo_vencimiento = '$fecha_proximo_vencimiento', 
                            monto_plan = $monto_plan, 
                            notas = '$notas' 
                        WHERE idclientes = $id";
                $message = "Cliente actualizado correctamente.";
            } else {
                // Crear
                $sql = "INSERT INTO clientes (patente, nombres, apellidos, tipo_vehiculo, dia_pago_mensual, fecha_proximo_vencimiento, monto_plan, notas) 
                        VALUES ('$patente', '$nombres', '$apellidos', '$tipo_vehiculo', $dia_pago_mensual, '$fecha_proximo_vencimiento', $monto_plan, '$notas')";
                $message = "Cliente agregado correctamente.";
            }

            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $conn->error);
            }
            break;

        case 'PUT':
            // Actualizar estado de pago de un cliente
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'], $data['action'])) {
                throw new Exception("Faltan datos requeridos para la actualización.");
            }
            
            $id = intval($data['id']);
            $action = $data['action'];
            
            if ($action === 'pagar') {
                // Obtener datos del cliente actual
                $sqlCliente = "SELECT * FROM clientes WHERE idclientes = $id";
                $result = $conn->query($sqlCliente);
                
                if ($result->num_rows === 0) {
                    throw new Exception("No se encontró el cliente con ID $id.");
                }
                
                $cliente = $result->fetch_assoc();
                $diaPago = intval($cliente['dia_pago_mensual']);
                
                // Calcular nueva fecha de vencimiento (próximo mes)
                $fechaActual = new DateTime();
                $proximoMes = clone $fechaActual;
                $proximoMes->modify('+1 month');
                
                // Ajustar al día de pago del cliente
                $nuevaFechaVencimiento = $proximoMes->format('Y-m-' . str_pad($diaPago, 2, '0', STR_PAD_LEFT));
                
                // Si el día de pago ya pasó este mes, usar el próximo mes
                if ($diaPago < $fechaActual->format('d')) {
                    $proximoMes->modify('+1 month');
                    $nuevaFechaVencimiento = $proximoMes->format('Y-m-' . str_pad($diaPago, 2, '0', STR_PAD_LEFT));
                }
                
                // Actualizar la fecha de vencimiento
                $sql = "UPDATE clientes SET fecha_proximo_vencimiento = '$nuevaFechaVencimiento' WHERE idclientes = $id";
                
                if ($conn->query($sql)) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Cliente marcado como pagado. Nueva fecha de vencimiento: ' . $nuevaFechaVencimiento
                    ]);
                } else {
                    throw new Exception("Error al actualizar la fecha de vencimiento: " . $conn->error);
                }
            } else {
                throw new Exception("Acción no válida.");
            }
            break;

        case 'DELETE':
            // Eliminar un cliente
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception("No se proporcionó ID para eliminar.");
            }

            $id = intval($id);
            $sql = "DELETE FROM clientes WHERE idclientes = $id";

            if ($conn->query($sql)) {
                if ($conn->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Cliente eliminado correctamente.']);
                } else {
                    throw new Exception("No se encontró el cliente con ID $id para eliminar.");
                }
            } else {
                throw new Exception("Error al eliminar: " . $conn->error);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>