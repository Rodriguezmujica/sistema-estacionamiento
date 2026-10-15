<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener todos los clientes o un cliente específico por ID
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                // Obtener un cliente específico por ID
                $id = intval($_GET['id']);
                $stmt = $conn->prepare("SELECT *, idclientes as id, CONCAT(nombres, ' ', apellidos) as nombre_cliente FROM clientes WHERE idclientes = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $cliente = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $cliente]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
                }
                $stmt->close();
            } else {
                // Obtener todos los clientes
                $result = $conn->query("SELECT *, idclientes as id, CONCAT(nombres, ' ', apellidos) as nombre_cliente FROM clientes ORDER BY fecha_proximo_vencimiento DESC");
                $clientes = [];
                while ($row = $result->fetch_assoc()) {
                    $clientes[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $clientes]);
            }
            break;

        case 'POST':
            // Crear o actualizar un cliente
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['patente'], $data['nombres'], $data['dia_pago_mensual'], $data['fecha_proximo_vencimiento'], $data['monto_plan'])) {
                throw new Exception("Faltan datos requeridos.");
            }

            // 🔧 CORRECCIÓN: Asegurar que el tipo de ingreso para "Plan Mensual" exista para evitar errores de FK.
            $id_tipo_ingreso_plan = 17; // ID reservado para planes mensuales
            $nombre_servicio_plan = "Plan Mensual";
            
            $check_stmt = $conn->prepare("SELECT idtipo_ingresos FROM tipo_ingreso WHERE idtipo_ingresos = ?");
            $check_stmt->bind_param("i", $id_tipo_ingreso_plan);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Si no existe, lo creamos para que el sistema se autorepare.
                $create_stmt = $conn->prepare("INSERT INTO tipo_ingreso (idtipo_ingresos, nombre_servicio, precio, es_plan, activo) VALUES (?, ?, 0, 1, 0)");
                $create_stmt->bind_param("is", $id_tipo_ingreso_plan, $nombre_servicio_plan);
                if (!$create_stmt->execute()) {
                    throw new Exception("Error crítico: No se pudo crear el tipo de ingreso para planes mensuales.");
                }
                $create_stmt->close();
            }
            $check_stmt->close();
            // Fin de la corrección de auto-reparación.

            $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : null;
            $patente = $conn->real_escape_string($data['patente']);
            $nombres = $conn->real_escape_string($data['nombres']);
            $apellidos = $conn->real_escape_string($data['apellidos'] ?? '');
            $tipo_vehiculo = $conn->real_escape_string($data['tipo_vehiculo'] ?? '');
            $dia_pago_mensual = intval($data['dia_pago_mensual']);
            $fecha_proximo_vencimiento = $data['fecha_proximo_vencimiento'];
            $monto_plan = floatval($data['monto_plan']);
            $notas = $conn->real_escape_string($data['notas'] ?? '');

            // 🔧 CORRECCIÓN: Limpieza y unificación de la lógica de creación/actualización.
            if ($id) {
                // Actualizar
                $stmt = $conn->prepare("UPDATE clientes SET 
                                            patente = ?, 
                                            nombres = ?, 
                                            apellidos = ?,
                                            tipo_vehiculo = ?, 
                                            dia_pago_mensual = ?, 
                                            fecha_proximo_vencimiento = ?, 
                                            monto_plan = ?, 
                                            notas = ?
                                        WHERE idclientes = ?");
                // El bind_param debe coincidir con los '?' -> 9 parámetros
                $stmt->bind_param("ssssisdsi", $patente, $nombres, $apellidos, $tipo_vehiculo, $dia_pago_mensual, $fecha_proximo_vencimiento, $monto_plan, $notas, $id);
                $message = "Cliente actualizado correctamente.";
            } else {
                // Crear
                // Se eliminan las consultas duplicadas y se usa la versión correcta sin 'es_plan'.
                $stmt = $conn->prepare("INSERT INTO clientes (patente, nombres, apellidos, tipo_vehiculo, dia_pago_mensual, fecha_proximo_vencimiento, monto_plan, notas, inicio_plan, idtipo_ingresos) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)");
                // El bind_param debe coincidir con los '?' -> 9 parámetros
                $stmt->bind_param("ssssisdsi", $patente, $nombres, $apellidos, $tipo_vehiculo, $dia_pago_mensual, $fecha_proximo_vencimiento, $monto_plan, $notas, $id_tipo_ingreso_plan);
                $message = "Cliente agregado correctamente.";
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
            $stmt->close();
            break;

        case 'PUT':
            // Actualizar estado de pago de un cliente
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['action'])) {
                throw new Exception("Faltan datos requeridos para la actualización.");
            }
            
            $id = intval($data['id']);
            $action = $data['action'];
            
            if ($action === 'pagar') {
                // Iniciar transacción
                $conn->begin_transaction();
                
                try {
                    // Obtener datos del cliente actual usando consulta preparada
                    $stmtCliente = $conn->prepare("SELECT fecha_proximo_vencimiento, dia_pago_mensual, monto_plan, patente, CONCAT(nombres, ' ', apellidos) as nombre_cliente FROM clientes WHERE idclientes = ?");
                    $stmtCliente->bind_param("i", $id);
                    $stmtCliente->execute();
                    $result = $stmtCliente->get_result();
                    
                    if ($result->num_rows === 0) {
                        throw new Exception("No se encontró el cliente con ID $id.");
                    }
                    
                    $cliente = $result->fetch_assoc();
                    $diaPago = intval($cliente['dia_pago_mensual']);
                    $montoPlan = floatval($cliente['monto_plan']);
                    $fechaVencimientoActual = new DateTime($cliente['fecha_proximo_vencimiento']);
                    
                    // Calcular nueva fecha de vencimiento sumando 1 mes a la fecha de vencimiento actual
                    $nuevaFecha = clone $fechaVencimientoActual;
                    $nuevaFecha->modify('+1 month');
                    
                    // Formatear la fecha final
                    $nuevaFechaVencimientoStr = $nuevaFecha->format('Y-m-d');
                    
                    // Actualizar la fecha de vencimiento y la fecha de inicio del plan (para reportes)
                    $stmtUpdate = $conn->prepare("UPDATE clientes SET fecha_proximo_vencimiento = ?, inicio_plan = CURDATE() WHERE idclientes = ?");
                    $stmtUpdate->bind_param("si", $nuevaFechaVencimientoStr, $id);
                    
                    if (!$stmtUpdate->execute()) {
                        throw new Exception("Error al actualizar la fecha de vencimiento: " . $conn->error);
                    }
                    $stmtUpdate->close();
                    
                    // Registrar el pago en la tabla salidas
                    // Para pagos mensuales, usamos id_ingresos = 0 ya que no hay un ingreso previo
                    // (la columna no permite NULL)
                    $idIngresosMensual = 0;
                    $stmtSalida = $conn->prepare("INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago) VALUES (?, NOW(), ?, 'MENSUAL', 'manual')");
                    $stmtSalida->bind_param("id", $idIngresosMensual, $montoPlan);
                    
                    if (!$stmtSalida->execute()) {
                        throw new Exception("Error al registrar el pago en salidas: " . $conn->error);
                    }
                    $stmtSalida->close();
                    
                    // Confirmar transacción
                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Cliente marcado como pagado. Nueva fecha de vencimiento: ' . $nuevaFechaVencimientoStr,
                        'monto_registrado' => $montoPlan
                    ]);
                    
                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $conn->rollback();
                    throw $e;
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
            $stmt = $conn->prepare("DELETE FROM clientes WHERE idclientes = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Cliente eliminado correctamente.']);
                } else {
                    throw new Exception("No se encontró el cliente con ID $id para eliminar.");
                }
            } else {
                throw new Exception("Error al eliminar: " . $stmt->error);
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