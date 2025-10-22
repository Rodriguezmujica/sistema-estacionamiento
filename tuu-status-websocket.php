<?php
/**
 * WebSocket simple para verificar estado de pagos TUU en tiempo real
 * Para uso en red local sin dependencias externas
 */

require_once __DIR__ . '/conexion.php';

// Configuración básica de WebSocket (PHP puro)
ini_set('max_execution_time', 0);
ignore_user_abort(true);
set_time_limit(0);

// Headers para WebSocket
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Obtener parámetros
$action = $_GET['action'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';

if ($action === 'check_status') {
    // Verificar estado del pago consultando TUU directamente
    if (empty($transaction_id)) {
        echo json_encode(['success' => false, 'error' => 'Transaction ID requerido']);
        exit;
    }
    
    // 1. Primero verificar en BD local si ya está registrado
    $sql = "SELECT s.*, i.patente 
            FROM salidas s 
            JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados 
            WHERE s.transaction_id = ? OR s.id_ingresos = ?";
    
    $stmt = $conn->prepare($sql);
    $id_ingreso = intval(str_replace(['EST-', '-'], '', explode('-', $transaction_id)[1] ?? '0'));
    $stmt->bind_param('si', $transaction_id, $id_ingreso);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($pago = $result->fetch_assoc()) {
        // Ya está en la BD, pago confirmado
        echo json_encode([
            'success' => true,
            'status' => 'completed',
            'data' => [
                'paid' => true,
                'transaction_id' => $pago['transaction_id'],
                'authorization_code' => $pago['authorization_code'],
                'patente' => $pago['patente'],
                'total' => $pago['total'],
                'fecha_pago' => $pago['fecha_salida']
            ]
        ]);
        exit;
    }
    
    // 2. No está en BD local, consultar directamente a TUU
    // Definir constantes TUU necesarias
    if (!defined('TUU_API_URL_GET')) {
        define('TUU_API_URL_GET', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Get/');
        define('TUU_API_KEY', 'getenv('TUU_API_KEY') ?: 'TU_API_KEY_AQUI'');
    }
    
    // Función para consultar estado en TUU
    function consultarEstadoTUUDirecto($transactionId) {
        // Verificar que cURL esté disponible
        if (!function_exists('curl_init')) {
            error_log("TUU ERROR: Extensión cURL no disponible para consultar estado");
            return null;
        }
        
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

        // Log de la respuesta de TUU para debugging
        error_log("TUU API RESPONSE - URL: $urlGet, HTTP Code: $httpCode, Response: $response");

        if ($httpCode === 200) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            } else {
                error_log("TUU JSON ERROR - Error parsing JSON: " . json_last_error_msg());
                return null;
            }
        } else {
            error_log("TUU HTTP ERROR - HTTP Code: $httpCode, Response: $response");
            return null;
        }
    }
    
    // Consultar estado en TUU
    $estadoTUU = consultarEstadoTUUDirecto($transaction_id);
    
    // Log detallado para debugging
    error_log("TUU STATUS CHECK - Transaction: $transaction_id, Estado: " . json_encode($estadoTUU));
    
    // Verificar más estados posibles que indican pago exitoso
    $estadosExitosos = ['Completed', 'Paid', 'COMPLETED', 'PAID', 'APPROVED', 'SUCCESS'];
    $esExitoso = false;
    
    if ($estadoTUU && isset($estadoTUU['status'])) {
        $statusTUU = $estadoTUU['status'];
        $esExitoso = in_array($statusTUU, $estadosExitosos);
        
        // Log del estado específico
        error_log("TUU STATUS - Estado recibido: '$statusTUU', Es exitoso: " . ($esExitoso ? 'SÍ' : 'NO'));
    } else {
        error_log("TUU STATUS - No se obtuvo estado válido de TUU");
    }
    
    if ($estadoTUU && $esExitoso) {
        // ✅ Pago confirmado en TUU, pero no en nuestra BD
        
        // Extraer ID de ingreso del transaction_id (formato: EST-123-timestamp)
        $partes = explode('-', $transaction_id);
        if (count($partes) >= 3 && is_numeric($partes[1])) {
            $id_ingreso_tuu = intval($partes[1]);
            
            // Buscar datos del ingreso para registrar el pago
            $sql_ingreso = "SELECT i.*, ti.nombre_servicio, ti.precio
                           FROM ingresos i
                           JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
                           WHERE i.idautos_estacionados = ?";
            
            $stmt_ingreso = $conn->prepare($sql_ingreso);
            $stmt_ingreso->bind_param('i', $id_ingreso_tuu);
            $stmt_ingreso->execute();
            $result_ingreso = $stmt_ingreso->get_result();
            
            if ($ingreso = $result_ingreso->fetch_assoc()) {
                // Registrar el pago en nuestra BD
                $sql_insert = "INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago, 
                              transaction_id, authorization_code, card_type, card_last4) 
                              VALUES (?, NOW(), ?, 'TUU', 'tuu', ?, ?, ?, ?)";
                
                $stmt_insert = $conn->prepare($sql_insert);
                $total = floatval($ingreso['precio'] ?? 0);
                $auth_code = $estadoTUU['paymentData']['authorizationCode'] ?? '';
                $card_type = $estadoTUU['paymentData']['cardType'] ?? '';
                $card_last4 = $estadoTUU['paymentData']['last4Digits'] ?? '';
                
                $stmt_insert->bind_param('idsssss', $id_ingreso_tuu, $total, $transaction_id, 
                                       $auth_code, $card_type, $card_last4);
                
                if ($stmt_insert->execute()) {
                    // Actualizar ingreso como pagado
                    $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param('i', $id_ingreso_tuu);
                    $stmt_update->execute();
                    
                    echo json_encode([
                        'success' => true,
                        'status' => 'completed',
                        'data' => [
                            'paid' => true,
                            'transaction_id' => $transaction_id,
                            'authorization_code' => $auth_code,
                            'patente' => $ingreso['patente'],
                            'total' => $total,
                            'fecha_pago' => date('Y-m-d H:i:s')
                        ]
                    ]);
                    exit;
                }
            }
        }
        
        // Si llegamos aquí, hubo error al registrar
        echo json_encode([
            'success' => false,
            'status' => 'completed_tuu_but_error_registering',
            'error' => 'Pago confirmado en TUU pero error al registrar en sistema local'
        ]);
        
    } elseif ($estadoTUU && in_array($estadoTUU['status'], ['Failed', 'Canceled', 'Rejected'])) {
        // ❌ Pago rechazado en TUU
        echo json_encode([
            'success' => false,
            'status' => 'failed',
            'error' => 'Pago rechazado en TUU: ' . ($estadoTUU['message'] ?? 'Error desconocido')
        ]);
        
    } else {
        // ⏳ Pago aún pendiente o estado no reconocido
        $statusActual = $estadoTUU['status'] ?? 'null';
        error_log("TUU STATUS - Estado no exitoso detectado: '$statusActual' para transacción $transaction_id");
        
        echo json_encode([
            'success' => true,
            'status' => 'pending',
            'data' => [
                'paid' => false, 
                'tuu_status' => $statusActual,
                'debug_info' => [
                    'transaction_id' => $transaction_id,
                    'full_response' => $estadoTUU
                ]
            ]
        ]);
    }
    
} elseif ($action === 'poll_status') {
    // Polling mejorado - solo verifica cambios
    $last_check = $_GET['last_check'] ?? time() - 60;
    
    // Buscar pagos nuevos desde la última verificación
    $sql = "SELECT s.*, i.patente 
            FROM salidas s 
            JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados 
            WHERE s.fecha_salida > FROM_UNIXTIME(?) 
            AND s.tipo_pago = 'tuu'
            ORDER BY s.fecha_salida DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $last_check);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $nuevos_pagos = [];
    while ($pago = $result->fetch_assoc()) {
        $nuevos_pagos[] = [
            'transaction_id' => $pago['transaction_id'],
            'patente' => $pago['patente'],
            'total' => $pago['total'],
            'status' => 'completed',
            'timestamp' => strtotime($pago['fecha_salida'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'nuevos_pagos' => $nuevos_pagos,
        'last_check' => time()
    ]);
    
} elseif ($action === 'confirm_manual_payment') {
    // Acción especial: Confirmar pago manual cuando el usuario reporta que pagó en TUU
    $transaction_id = $_POST['transaction_id'] ?? '';
    $patente = $_POST['patente'] ?? '';
    
    if (!$transaction_id || !$patente) {
        echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos']);
        exit;
    }
    
    // Extraer ID de ingreso del transaction_id (formato: EST-123-timestamp)
    $partes = explode('-', $transaction_id);
    if (count($partes) >= 3 && is_numeric($partes[1])) {
        $id_ingreso_tuu = intval($partes[1]);
        
        // Buscar datos del ingreso
        $sql_ingreso = "SELECT i.*, ti.nombre_servicio, ti.precio
                       FROM ingresos i
                       JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos 
                       WHERE i.idautos_estacionados = ? AND i.patente = ?";
        
        $stmt_ingreso = $conn->prepare($sql_ingreso);
        $stmt_ingreso->bind_param('is', $id_ingreso_tuu, $patente);
        $stmt_ingreso->execute();
        $result_ingreso = $stmt_ingreso->get_result();
        
        if ($ingreso = $result_ingreso->fetch_assoc()) {
            // Verificar si ya existe el pago
            $sql_check = "SELECT * FROM salidas WHERE id_ingresos = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param('i', $id_ingreso_tuu);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows === 0) {
                // Registrar el pago manualmente
                $sql_insert = "INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago, 
                              transaction_id, authorization_code, card_type, card_last4) 
                              VALUES (?, NOW(), ?, 'TUU', 'tuu', ?, 'MANUAL_CONFIRM', 'MANUAL', 'MANUAL')";
                
                $stmt_insert = $conn->prepare($sql_insert);
                $total = floatval($ingreso['precio'] ?? 0);
                $stmt_insert->bind_param('ids', $id_ingreso_tuu, $total, $transaction_id);
                
                if ($stmt_insert->execute()) {
                    // Actualizar ingreso como pagado
                    $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param('i', $id_ingreso_tuu);
                    $stmt_update->execute();
                    
                    error_log("TUU MANUAL CONFIRM - Pago confirmado manualmente para $patente - ID: $transaction_id");
                    
                    echo json_encode([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Pago confirmado manualmente',
                        'data' => [
                            'paid' => true,
                            'transaction_id' => $transaction_id,
                            'patente' => $patente,
                            'total' => $total,
                            'manual_confirm' => true
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error al registrar pago manual']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Este pago ya está registrado']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró el ingreso especificado']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Transaction ID inválido']);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

$conn->close();
?>
