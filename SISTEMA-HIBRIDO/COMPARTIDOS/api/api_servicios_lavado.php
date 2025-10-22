<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Por defecto, solo se obtienen los servicios activos de LAVADO.
            // Si se pasa el parámetro ?todos=1, se obtienen TODOS (incluso estacionamiento e inactivos).
            $mostrarTodos = isset($_GET['todos']) && $_GET['todos'] == '1';
            
            $sql = "SELECT idtipo_ingresos, nombre_servicio, precio, descripcion, activo 
                    FROM tipo_ingreso 
                    WHERE nombre_servicio <> ''";
            
            // Si NO es modo todos, filtrar solo servicios activos de lavado
            if (!$mostrarTodos) {
                $sql .= " AND activo = 1 AND nombre_servicio NOT LIKE '%estacionamiento%'";
            }
            
            $sql .= " ORDER BY activo DESC, precio ASC";

            $result = $conn->query($sql);
            $servicios = [];
            if ($result) {
                while($row = $result->fetch_assoc()) {
                    $servicios[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $servicios]);
            break;

        case 'POST':
            // Crear o actualizar un servicio
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['nombre_servicio'], $data['precio'])) {
                throw new Exception("Nombre y precio son requeridos.");
            }

            $id = $data['id'] ?? null;
            $nombre = $conn->real_escape_string($data['nombre_servicio']);
            $precio = floatval($data['precio']);
            $descripcion = $conn->real_escape_string($data['descripcion'] ?? '');

            if ($id) {
                // Actualizar
                $stmt = $conn->prepare("UPDATE tipo_ingreso SET nombre_servicio = ?, precio = ?, descripcion = ? WHERE idtipo_ingresos = ?");
                $stmt->bind_param("sdsi", $nombre, $precio, $descripcion, $id);
                $message = "Servicio actualizado correctamente.";
            } else {
                // Crear
                $stmt = $conn->prepare("INSERT INTO tipo_ingreso (nombre_servicio, precio, descripcion) VALUES (?, ?, ?)");
                $stmt->bind_param("sds", $nombre, $precio, $descripcion);
                $message = "Servicio agregado correctamente.";
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
            $stmt->close();
            break;

        case 'PUT':
            // Toggle estado activo/inactivo
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            $nuevoEstado = isset($data['activo']) ? intval($data['activo']) : null;
            
            if (!$id || $nuevoEstado === null) {
                throw new Exception("ID y estado son requeridos");
            }
            
            $id = intval($id);
            $stmt = $conn->prepare("UPDATE tipo_ingreso SET activo = ? WHERE idtipo_ingresos = ?");
            $stmt->bind_param("ii", $nuevoEstado, $id);
            
            if ($stmt->execute()) {
                $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';
                echo json_encode(['success' => true, 'message' => "Servicio {$estadoTexto} correctamente"]);
            } else {
                throw new Exception("Error al cambiar estado: " . $stmt->error);
            }
            $stmt->close();
            break;
            
        case 'DELETE':
            // Eliminar (desactivar) un servicio
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception("No se proporcionó ID para eliminar.");
            }

            $id = intval($id);
            
            // En lugar de eliminar, desactivamos el servicio (soft delete)
            $delete_stmt = $conn->prepare("UPDATE tipo_ingreso SET activo = 0 WHERE idtipo_ingresos = ?");
            $delete_stmt->bind_param("i", $id);

            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    // Si el servicio se desactiva, también lo eliminamos de la tabla de lavados pendientes si es que existe ahí
                    $conn->query("DELETE FROM lavados_pendientes WHERE id_servicio = $id");
                    echo json_encode(['success' => true, 'message' => 'Servicio desactivado correctamente. Ya no aparecerá en las listas.']);
                } else {
                    throw new Exception("No se encontró el servicio con ID $id o ya estaba inactivo.");
                }
            } else {
                throw new Exception("Error al desactivar el servicio: " . $delete_stmt->error);
            }
            $delete_stmt->close();
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