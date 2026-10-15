<?php
/**
 * 🔥 FCM WEBHOOK ENDPOINT
 * Sistema de Estacionamiento Los Ríos
 * 
 * Endpoint para recibir notificaciones FCM de TUU
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexion.php';

// Configuración FCM
const FCM_SERVER_KEY = 'BL38f3jX5zj-73XuxYytU9m6bCMKA2mKHcxBwJWUI0u1I_IDfFjAtuUw91DSH1gLEgsLr1XCrdqOp9IqmfK8yDI';
const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

try {
    // Obtener datos del webhook
    $input = file_get_contents('php://input');
    $webhookData = json_decode($input, true);
    
    if (!$webhookData) {
        $webhookData = $_POST;
    }
    
    error_log("FCM WEBHOOK - Datos recibidos: " . json_encode($webhookData));
    
    // Validar datos requeridos
    $transactionId = $webhookData['transaction_id'] ?? $webhookData['id'] ?? null;
    $status = $webhookData['status'] ?? 'pending';
    $amount = $webhookData['amount'] ?? 0;
    
    if (!$transactionId) {
        throw new Exception('No se recibió transaction_id en el webhook');
    }
    
    // Buscar el ingreso correspondiente
    $sql_ingreso = "SELECT i.*, ti.nombre_servicio, ti.precio
                   FROM ingresos i
                   JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
                   WHERE i.idautos_estacionados = ? AND i.salida = 0";
    
    // Extraer ID del transaction_id (formato: EST-123-timestamp)
    $partes = explode('-', $transactionId);
    if (count($partes) >= 3 && is_numeric($partes[1])) {
        $id_ingreso = intval($partes[1]);
        
        $stmt_ingreso = $conn->prepare($sql_ingreso);
        $stmt_ingreso->bind_param('i', $id_ingreso);
        $stmt_ingreso->execute();
        $result_ingreso = $stmt_ingreso->get_result();
        
        if ($ingreso = $result_ingreso->fetch_assoc()) {
            
            if ($status === 'completed' || $status === 'paid') {
                // ✅ Pago confirmado - Registrar en BD
                $conn->begin_transaction();
                
                // Verificar si ya está registrado
                $sql_check = "SELECT id FROM salidas WHERE transaction_id = ? LIMIT 1";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param('s', $transactionId);
                $stmt_check->execute();
                
                if ($stmt_check->get_result()->num_rows === 0) {
                    // Registrar el pago
                    $sql_salida = "INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago, 
                                   transaction_id, authorization_code, card_type, card_last4) 
                                   VALUES (?, NOW(), ?, 'TUU', 'tuu', ?, ?, ?, ?)";
                    
                    $stmt_salida = $conn->prepare($sql_salida);
                    $total = floatval($ingreso['precio'] ?? $amount);
                    $auth_code = $webhookData['authorization_code'] ?? '';
                    $card_type = $webhookData['card_type'] ?? '';
                    $card_last4 = $webhookData['card_last4'] ?? '';
                    
                    $stmt_salida->bind_param('idsssss', $id_ingreso, $total, $transactionId, 
                                           $auth_code, $card_type, $card_last4);
                    
                    if ($stmt_salida->execute()) {
                        
                        // Actualizar ingreso como pagado
                        $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param('i', $id_ingreso);
                        $stmt_update->execute();
                        
                        $conn->commit();
                        
                        error_log("FCM WEBHOOK - Pago registrado exitosamente: $transactionId para patente {$ingreso['patente']}");
                        
                        // Enviar notificación FCM a dispositivos conectados
                        enviarNotificacionFCM($transactionId, $ingreso['patente'], $total);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Pago registrado correctamente',
                            'transaction_id' => $transactionId,
                            'patente' => $ingreso['patente'],
                            'total' => $total
                        ]);
                    } else {
                        $conn->rollback();
                        throw new Exception('Error al registrar pago en BD');
                    }
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Pago ya registrado'
                    ]);
                }
            } else {
                // ⏳ Pago pendiente
                echo json_encode([
                    'success' => true,
                    'message' => 'Pago pendiente',
                    'status' => $status
                ]);
            }
        } else {
            throw new Exception('No se encontró el ingreso especificado');
        }
    } else {
        throw new Exception('Transaction ID inválido');
    }
    
} catch (Exception $e) {
    error_log("FCM WEBHOOK ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Enviar notificación FCM a dispositivos conectados
 */
function enviarNotificacionFCM($transactionId, $patente, $total) {
    try {
        // Obtener tokens FCM de dispositivos conectados
        $tokens = obtenerTokensFCM();
        
        if (empty($tokens)) {
            error_log("FCM - No hay tokens disponibles");
            return;
        }
        
        $notification = [
            'title' => 'Pago Confirmado',
            'body' => "Transacción $transactionId pagada exitosamente - Patente: $patente - Total: $" . number_format($total, 0, ',', '.')
        ];
        
        $data = [
            'transaction_id' => $transactionId,
            'patente' => $patente,
            'total' => $total,
            'status' => 'completed',
            'timestamp' => time()
        ];
        
        foreach ($tokens as $token) {
            $payload = [
                'to' => $token,
                'notification' => $notification,
                'data' => $data
            ];
            
            $headers = [
                'Authorization: key=' . FCM_SERVER_KEY,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FCM_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                error_log("FCM - Notificación enviada exitosamente a token: " . substr($token, 0, 20) . "...");
            } else {
                error_log("FCM - Error enviando notificación: HTTP $httpCode - $response");
            }
        }
        
    } catch (Exception $e) {
        error_log("FCM ERROR: " . $e->getMessage());
    }
}

/**
 * Obtener tokens FCM de dispositivos conectados
 */
function obtenerTokensFCM() {
    global $conn;
    
    try {
        // Por ahora, obtener tokens de localStorage o una tabla específica
        // En una implementación completa, guardarías los tokens en BD
        
        $tokens = [];
        
        // Verificar si hay tokens guardados en archivo temporal
        $tokenFile = __DIR__ . '/fcm_tokens.json';
        if (file_exists($tokenFile)) {
            $savedTokens = json_decode(file_get_contents($tokenFile), true);
            if (is_array($savedTokens)) {
                $tokens = array_merge($tokens, $savedTokens);
            }
        }
        
        return array_unique($tokens);
        
    } catch (Exception $e) {
        error_log("Error obteniendo tokens FCM: " . $e->getMessage());
        return [];
    }
}

$conn->close();
?>
