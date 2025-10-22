<?php
/**
 * Webhook endpoint para recibir notificaciones de TUU cuando se completa un pago
 * Versión mejorada con integración Firebase
 * Sistema de Estacionamiento Los Ríos
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/firebase-config.php';

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
    
    error_log("TUU WEBHOOK FIREBASE - Datos recibidos: " . json_encode($webhookData));
    
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
    
    error_log("TUU WEBHOOK FIREBASE - Estado TUU: " . json_encode($estadoTUU));
    
    // Solo procesar si el pago se completó
    if ($estadoTUU['status'] === 'Completed' || $estadoTUU['status'] === 'Paid') {
        
        // Verificar si ya está registrado en nuestra BD
        $sql_check = "SELECT s.*, i.patente, i.precio 
                      FROM salidas s 
                      LEFT JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                      WHERE s.transaction_id = ? LIMIT 1";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('s', $transactionId);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($pago_existente = $result_check->fetch_assoc()) {
            // Ya está registrado, solo actualizar Firebase
            error_log("TUU WEBHOOK FIREBASE - Pago ya registrado, actualizando Firebase: " . $transactionId);
            await actualizarPagoEnFirebase($transactionId, 'completed', $pago_existente);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pago ya registrado, Firebase actualizado',
                'transaction_id' => $transactionId,
                'patente' => $pago_existente['patente']
            ]);
            exit;
        }
        
        // Buscar ingreso por transaction_id en la tabla tickets
        $sql_ingreso = "SELECT * FROM tickets WHERE CONCAT('EST-', id, '-', UNIX_TIMESTAMP()) = ? OR id = ? LIMIT 1";
        $stmt_ingreso = $conn->prepare($sql_ingreso);
        
        // Extraer ID del transaction_id
        $id_ingreso = 0;
        if (preg_match('/EST-(\d+)-/', $transactionId, $matches)) {
            $id_ingreso = intval($matches[1]);
        }
        
        $stmt_ingreso->bind_param('si', $transactionId, $id_ingreso);
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
                
                // Actualizar ticket como pagado
                $sql_update = "UPDATE tickets SET pagado = 1, fecha_salida = NOW() WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param('i', $id_ingreso);
                $stmt_update->execute();
                
                $conn->commit();
                
                // Actualizar Firebase
                $pagoData = [
                    'transaction_id' => $transactionId,
                    'patente' => $ingreso['patente'],
                    'precio' => $total,
                    'cliente_nombre' => $ingreso['cliente_nombre'],
                    'authorization_code' => $auth_code,
                    'card_type' => $card_type,
                    'card_last4' => $card_last4,
                    'status' => 'completed',
                    'completed_at' => date('c')
                ];
                
                await actualizarPagoEnFirebase($transactionId, 'completed', $pagoData);
                
                error_log("TUU WEBHOOK FIREBASE - Pago registrado exitosamente: $transactionId para patente {$ingreso['patente']}");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Pago registrado correctamente con Firebase',
                    'transaction_id' => $transactionId,
                    'patente' => $ingreso['patente'],
                    'firebase_synced' => true
                ]);
            } else {
                $conn->rollback();
                throw new Exception('Error insertando salida: ' . $stmt_salida->error);
            }
        } else {
            throw new Exception('Ingreso no encontrado para transaction_id: ' . $transactionId);
        }
        
    } else {
        // Pago no completado, solo actualizar Firebase
        error_log("TUU WEBHOOK FIREBASE - Pago no completado: " . $estadoTUU['status']);
        await actualizarPagoEnFirebase($transactionId, $estadoTUU['status'], $estadoTUU);
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado en Firebase',
            'transaction_id' => $transactionId,
            'status' => $estadoTUU['status']
        ]);
    }
    
} catch (Exception $e) {
    error_log("TUU WEBHOOK FIREBASE - Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}

/**
 * Consultar estado de pago en TUU
 */
function consultarEstadoTUU($transactionId) {
    $url = TUU_API_URL_GET . $transactionId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . TUU_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("TUU WEBHOOK FIREBASE - Error consultando TUU: HTTP $httpCode - $response");
        return null;
    }
    
    $data = json_decode($response, true);
    return $data;
}

/**
 * Actualizar pago en Firebase
 */
function actualizarPagoEnFirebase($transactionId, $status, $data) {
    try {
        // Crear pago en Firebase usando la API REST
        $firebaseUrl = FIREBASE_BASE_URL . '/databases/(default)/documents/tuu_payments/' . urlencode($transactionId);
        
        $pagoData = [
            'fields' => [
                'transaction_id' => ['stringValue' => $transactionId],
                'status' => ['stringValue' => $status],
                'updated_at' => ['timestampValue' => date('c')],
                'patente' => ['stringValue' => $data['patente'] ?? ''],
                'precio' => ['doubleValue' => floatval($data['precio'] ?? 0)],
                'cliente_nombre' => ['stringValue' => $data['cliente_nombre'] ?? ''],
                'authorization_code' => ['stringValue' => $data['authorization_code'] ?? ''],
                'card_type' => ['stringValue' => $data['card_type'] ?? ''],
                'card_last4' => ['stringValue' => $data['card_last4'] ?? '']
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $firebaseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pagoData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("TUU WEBHOOK FIREBASE - Pago actualizado en Firebase: $transactionId");
            return true;
        } else {
            error_log("TUU WEBHOOK FIREBASE - Error actualizando Firebase: HTTP $httpCode - $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("TUU WEBHOOK FIREBASE - Error en Firebase: " . $e->getMessage());
        return false;
    }
}
?>
