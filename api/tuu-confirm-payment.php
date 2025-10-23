//  HABILITAR CORS PARA SISTEMA HÍBRIDO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (pre-flight), responder vacía
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

<?php
/**
 *  API TUU - CONFIRMACIÓN DIRECTA DE PAGOS
 * Sistema de Estacionamiento Los Ríos
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');

require_once __DIR__ . '/conexion.php';

// Configuración TUU
// Cargar configuración sensible
require_once __DIR__ . '/../config-sensible.php';
const TUU_API_BASE = 'https://integrations.payment.haulmer.com/RemotePayment/v2';

try {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'confirm_payment') {
        $transactionId = $_GET['transaction_id'] ?? '';
        $amount = $_GET['amount'] ?? 0;
        $patente = $_GET['patente'] ?? 'SIN-PATENTE';
        
        if (!$transactionId) {
            throw new Exception('Transaction ID requerido');
        }
        
        // Confirmar pago directamente en TUU
        $resultado = confirmarPagoEnTUU($transactionId, $amount, $patente);
        
        if ($resultado['success']) {
            // Sincronizar con base de datos local
            $syncResult = sincronizarPagoLocal($transactionId, $patente, $amount);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pago confirmado en TUU y sincronizado localmente',
                'tuu_response' => $resultado['data'],
                'local_sync' => $syncResult,
                'timestamp' => date('c')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $resultado['error'],
                'debug' => $resultado['debug']
            ]);
        }
        
    } elseif ($action === 'check_payment_status') {
        $transactionId = $_GET['transaction_id'] ?? '';
        
        if (!$transactionId) {
            throw new Exception('Transaction ID requerido');
        }
        
        // Verificar estado del pago en TUU
        $resultado = verificarEstadoPagoTUU($transactionId);
        
        echo json_encode([
            'success' => true,
            'data' => $resultado,
            'timestamp' => date('c')
        ]);
        
    } else {
        throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("TUU Confirm API ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

/**
 * Confirmar pago directamente en TUU
 */
function confirmarPagoEnTUU($transactionId, $amount, $patente) {
    $url = TUU_API_BASE . '/CreateRemotePaymentRequest';
    
    $headers = [
        'X-API-Key: ' . TUU_API_KEY,
        'accept: application/json',
        'Content-Type: application/json'
    ];
    
    // Datos para confirmar el pago
    $data = [
        'idempotencyKey' => $transactionId,
        'amount' => $amount,
        'description' => "Pago confirmado - Patente: $patente",
        'metadata' => [
            'patente' => $patente,
            'sistema' => 'estacionamiento',
            'confirmado_por' => 'sistema_automatico'
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $debug = [
        'url' => $url,
        'http_code' => $httpCode,
        'curl_error' => $error,
        'request_data' => $data,
        'response' => substr($response, 0, 500)
    ];
    
    if ($error) {
        return [
            'success' => false,
            'error' => "Error cURL: $error",
            'debug' => $debug
        ];
    }
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        return [
            'success' => false,
            'error' => "Error HTTP $httpCode: " . substr($response, 0, 200),
            'debug' => $debug
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (!$responseData) {
        return [
            'success' => false,
            'error' => "Respuesta inválida de TUU: " . substr($response, 0, 200),
            'debug' => $debug
        ];
    }
    
    return [
        'success' => true,
        'data' => $responseData,
        'debug' => $debug
    ];
}

/**
 * Verificar estado del pago en TUU
 */
function verificarEstadoPagoTUU($transactionId) {
    $url = TUU_API_BASE . '/GetPaymentRequest/' . urlencode($transactionId);
    
    $headers = [
        'X-API-Key: ' . TUU_API_KEY,
        'accept: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'status' => 'error',
            'message' => "Error cURL: $error"
        ];
    }
    
    if ($httpCode === 404) {
        return [
            'status' => 'not_found',
            'message' => 'Transaction ID no encontrado en TUU'
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'status' => 'error',
            'message' => "Error HTTP $httpCode: " . substr($response, 0, 200)
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        return [
            'status' => 'error',
            'message' => 'Respuesta inválida de TUU'
        ];
    }
    
    return $data;
}

/**
 * Sincronizar pago con base de datos local
 */
function sincronizarPagoLocal($transactionId, $patente, $amount) {
    global $conn;
    
    try {
        // Buscar ingreso asociado
        $sqlIngreso = "SELECT idautos_estacionados, patente, fecha_ingreso 
                       FROM ingresos 
                       WHERE patente = ? 
                       AND idautos_estacionados NOT IN (
                           SELECT id_ingresos FROM salidas WHERE id_ingresos IS NOT NULL
                       )
                       ORDER BY fecha_ingreso DESC 
                       LIMIT 1";
        
        $stmtIngreso = $conn->prepare($sqlIngreso);
        if (!$stmtIngreso) {
            throw new Exception("Error preparando consulta de ingreso: " . $conn->error);
        }
        
        $stmtIngreso->bind_param('s', $patente);
        $stmtIngreso->execute();
        $resultIngreso = $stmtIngreso->get_result();
        $ingreso = $resultIngreso->fetch_assoc();
        
        if ($ingreso) {
            // Crear salida asociada al ingreso
            $sqlSalida = "INSERT INTO salidas (
                id_ingresos, 
                fecha_salida, 
                transaction_id
            ) VALUES (?, NOW(), ?)";
            
            $stmtSalida = $conn->prepare($sqlSalida);
            if (!$stmtSalida) {
                throw new Exception("Error preparando inserción de salida: " . $conn->error);
            }
            
            $stmtSalida->bind_param('is', 
                $ingreso['idautos_estacionados'],
                $transactionId
            );
            $stmtSalida->execute();
            
            return [
                'status' => 'created',
                'message' => 'Pago sincronizado con ingreso existente',
                'patente' => $patente,
                'amount' => $amount
            ];
        } else {
            return [
                'status' => 'no_ingreso',
                'message' => 'Pago confirmado pero no hay ingreso asociado',
                'patente' => $patente,
                'amount' => $amount
            ];
        }
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

$conn->close();
?>
