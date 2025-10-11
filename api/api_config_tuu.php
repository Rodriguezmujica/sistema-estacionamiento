<?php
header('Content-Type: application/json');
require_once '../conexion.php';

/**
 * API para Gestión de Configuración de Máquinas TUU
 * Permite cambiar entre TUU Principal y TUU Respaldo
 */

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener configuración actual de máquinas TUU
            $sql = "SELECT * FROM configuracion_tuu ORDER BY activa DESC, maquina ASC";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Error al consultar configuración TUU");
            }
            
            $maquinas = [];
            $maquinaActiva = null;
            
            while ($row = $result->fetch_assoc()) {
                $maquina = [
                    'id' => intval($row['id']),
                    'maquina' => $row['maquina'],
                    'device_serial' => $row['device_serial'],
                    'nombre' => $row['nombre'],
                    'activa' => intval($row['activa']) === 1
                ];
                
                $maquinas[] = $maquina;
                
                if ($maquina['activa']) {
                    $maquinaActiva = $maquina;
                }
            }
            
            // Leer configuración del archivo tuu-pago.php
            $tuu_pago_file = __DIR__ . '/tuu-pago.php';
            $api_key = '';
            $modo_prueba = true;
            
            if (file_exists($tuu_pago_file)) {
                $content = file_get_contents($tuu_pago_file);
                // Extraer API Key
                if (preg_match("/define\('TUU_API_KEY',\s*'([^']+)'/", $content, $matches)) {
                    $api_key = $matches[1];
                }
                // Extraer modo prueba
                if (preg_match("/define\('TUU_MODO_PRUEBA',\s*(true|false)/", $content, $matches)) {
                    $modo_prueba = ($matches[1] === 'true');
                }
            }
            
            echo json_encode([
                'success' => true,
                'maquinas' => $maquinas,
                'activa' => $maquinaActiva,
                'maquina_activa' => $maquinaActiva,
                'api_key' => $api_key,
                'modo_prueba' => $modo_prueba
            ]);
            break;
            
        case 'POST':
            // Cambiar máquina activa
            $data = json_decode(file_get_contents('php://input'), true);
            $maquinaSeleccionada = $data['maquina'] ?? '';
            
            if (!in_array($maquinaSeleccionada, ['principal', 'respaldo'])) {
                throw new Exception("Máquina inválida");
            }
            
            // Desactivar todas
            $conn->query("UPDATE configuracion_tuu SET activa = 0");
            
            // Activar la seleccionada
            $stmt = $conn->prepare("UPDATE configuracion_tuu SET activa = 1 WHERE maquina = ?");
            $stmt->bind_param('s', $maquinaSeleccionada);
            
            if ($stmt->execute()) {
                // Obtener información de la máquina activada
                $sqlInfo = "SELECT * FROM configuracion_tuu WHERE maquina = ?";
                $stmtInfo = $conn->prepare($sqlInfo);
                $stmtInfo->bind_param('s', $maquinaSeleccionada);
                $stmtInfo->execute();
                $resultInfo = $stmtInfo->get_result();
                $info = $resultInfo->fetch_assoc();
                $stmtInfo->close();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Cambiado a " . $info['nombre'],
                    'maquina_activa' => [
                        'maquina' => $info['maquina'],
                        'nombre' => $info['nombre'],
                        'device_serial' => $info['device_serial']
                    ]
                ]);
            } else {
                throw new Exception("Error al cambiar máquina: " . $stmt->error);
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

