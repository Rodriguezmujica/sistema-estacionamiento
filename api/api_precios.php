<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener configuración de precios
            $sql = "SELECT * FROM precios WHERE id = 1 LIMIT 1";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $precios = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'precio_minuto' => intval($precios['precio_minuto']),
                        'precio_minimo' => intval($precios['precio_minuto_minimo']),
                        'rango_precio_min' => intval($precios['rango_precio_min']),
                        'rango_minimo' => intval($precios['rango_minimo']),
                        'rango_precio' => intval($precios['rango_precio']),
                        'rango_minutos' => intval($precios['rango_minutos']),
                        'tipo' => intval($precios['tipo'])
                    ]
                ]);
            } else {
                // Si no existe, crear registro por defecto
                $sqlInsert = "INSERT INTO precios (id, precio_minuto_minimo, precio_minuto, rango_precio_min, rango_minimo, rango_precio, rango_minutos, tipo) 
                              VALUES (1, 500, 35, 500, 20, 500, 20, 1)";
                $conn->query($sqlInsert);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'precio_minuto' => 35,
                        'precio_minimo' => 500,
                        'rango_precio_min' => 500,
                        'rango_minimo' => 20,
                        'rango_precio' => 500,
                        'rango_minutos' => 20,
                        'tipo' => 1
                    ]
                ]);
            }
            break;

        case 'POST':
        case 'PUT':
            // Actualizar configuración de precios
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['precio_minuto']) || !isset($data['precio_minimo'])) {
                throw new Exception("Precio por minuto y precio mínimo son requeridos");
            }
            
            $precioMinuto = intval($data['precio_minuto']);
            $precioMinimo = intval($data['precio_minimo']);
            
            // Validaciones
            if ($precioMinuto < 1) {
                throw new Exception("El precio por minuto debe ser mayor a 0");
            }
            if ($precioMinimo < 0) {
                throw new Exception("El precio mínimo debe ser mayor o igual a 0");
            }
            if ($precioMinimo > 0 && $precioMinimo < $precioMinuto) {
                throw new Exception("El precio mínimo debe ser mayor o igual al precio por minuto");
            }
            
            // Verificar si existe el registro
            $checkSql = "SELECT id FROM precios WHERE id = 1";
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                // Actualizar
                $sql = "UPDATE precios 
                        SET precio_minuto = ?, precio_minuto_minimo = ? 
                        WHERE id = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $precioMinuto, $precioMinimo);
                $mensaje = "Precios actualizados correctamente";
            } else {
                // Insertar
                $sql = "INSERT INTO precios (id, precio_minuto, precio_minuto_minimo, rango_precio_min, rango_minimo, rango_precio, rango_minutos, tipo) 
                        VALUES (1, ?, ?, 500, 20, 500, 20, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $precioMinuto, $precioMinimo);
                $mensaje = "Precios guardados correctamente";
            }
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => $mensaje,
                    'data' => [
                        'precio_minuto' => $precioMinuto,
                        'precio_minimo' => $precioMinimo
                    ]
                ]);
            } else {
                throw new Exception("Error al guardar precios: " . $stmt->error);
            }
            $stmt->close();
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

