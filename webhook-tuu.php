<?php
/**
 * Webhook endpoint para recibir notificaciones de TUU cuando se completa un pago
 * Este archivo puede ser llamado por TUU para notificar cambios de estado
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');

require_once __DIR__ . '/conexion.php';

// Configuración TUU
if (!defined('TUU_API_URL_GET')) {
    define('TUU_API_URL_GET', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Get/');
    define('TUU_API_KEY', 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux');
}

try {
    // Obtener datos del webhook (POST o GET)
    $input = file_get_contents('php://input');
    $webhookData = json_decode($input, true);
    
    // Si no hay datos JSON, intentar GET
    if (!$webhookData) {
        $webhookData = $_GET;
    }
    
    error_log("TUU WEBHOOK - Datos recibidos: " . json_encode($webhookData));
    
    // Validar que tenemos el transaction_id
    $transactionId = $webhookData['transaction_id'] ?? $webhookData['id'] ?? null;
    
    if (!$transactionId) {
        throw new Exception('No se recibió transaction_id en el webhook');
    }
    
    // Verificar estado actual en TUU
    $estadoTUU = consultarEstadoTUU($transactionId);
    
    if (!$estadoTUU) {
        throw new Exception('No se pudo obtener estado de TUU');
    }
    
    // Solo procesar si el pago se completó
    if ($estadoTUU['status'] === 'Completed' || $estadoTUU['status'] === 'Paid') {
        
        // Verificar si ya está registrado en nuestra BD
        $sql_check = "SELECT id FROM salidas WHERE transaction_id = ? LIMIT 1";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('s', $transactionId);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            // Ya está registrado, solo confirmar
            echo json_encode(['success' => true, 'message' => 'Pago ya registrado']);
            exit;
        }
        
        // Extraer ID de ingreso del transaction_id (formato: EST-123-timestamp)
        $partes = explode('-', $transactionId);
        if (count($partes) >= 3 && is_numeric($partes[1])) {
            $id_ingreso = intval($partes[1]);
            
            // Obtener datos del ingreso
            $sql_ingreso = "SELECT i.*, ti.nombre_servicio, ti.precio
                           FROM ingresos i
                           JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
                           WHERE i.idautos_estacionados = ? AND i.salida = 0";
            
            $stmt_ingreso = $conn->prepare($sql_ingreso);
            $stmt_ingreso->bind_param('i', $id_ingreso);
            $stmt_ingreso->execute();
            $result_ingreso = $stmt_ingreso->get_result();
            
            if ($ingreso = $result_ingreso->fetch_assoc()) {
                
                // Registrar el pago
                $conn->begin_transaction();
                
                $sql_salida = "INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago, 
                               transaction_id, authorization_code, card_type, card_last4) 
                               VALUES (?, NOW(), ?, 'TUU', 'tuu', ?, ?, ?, ?)";
                
                $stmt_salida = $conn->prepare($sql_salida);
                $total = floatval($ingreso['precio'] ?? 0);
                $auth_code = $estadoTUU['paymentData']['authorizationCode'] ?? '';
                $card_type = $estadoTUU['paymentData']['cardType'] ?? '';
                $card_last4 = $estadoTUU['paymentData']['last4Digits'] ?? '';
                
                $stmt_salida->bind_param('idsssss', $id_ingreso, $total, $transactionId, 
                                       $auth_code, $card_type, $card_last4);
                
                if ($stmt_salida->execute()) {
                    
                    // Actualizar ingreso como pagado
                    $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param('i', $id_ingreso);
                    $stmt_update->execute();
                    
                    $conn->commit();
                    
                    error_log("TUU WEBHOOK - Pago registrado exitosamente: $transactionId para patente {$ingreso['patente']}");
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Pago registrado correctamente',
                        'transaction_id' => $transactionId,
                        'patente' => $ingreso['patente']
                    ]);
                } else {
                    $conn->rollback();
                    throw new Exception('Error al insertar en tabla salidas');
                }
            } else {
                throw new Exception('No se encontró el ingreso o ya fue pagado');
            }
        } else {
            throw new Exception('Formato de transaction_id inválido');
        }
        
    } else {
        // Pago no completado, solo log
        error_log("TUU WEBHOOK - Pago no completado: $transactionId, estado: {$estadoTUU['status']}");
        echo json_encode(['success' => true, 'message' => 'Estado actualizado, pago no completado aún']);
    }
    
} catch (Exception $e) {
    error_log("TUU WEBHOOK ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function consultarEstadoTUU($transactionId) {
    $urlGet = TUU_API_URL_GET . $transactionId;
    $ch = curl_init($urlGet);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . TUU_API_KEY,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

$conn->close();
?>
