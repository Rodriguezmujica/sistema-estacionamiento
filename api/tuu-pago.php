<?php
header('Content-Type: application/json');

/**
 * API de Integración con TUU - Sistema de Cobro
 * 
 * NOTA: Este archivo está preparado para la integración con la máquina TUU.
 * Cuando tengas acceso a la máquina, deberás:
 * 1. Obtener las credenciales de TUU (API Key, Merchant ID, etc.)
 * 2. Actualizar las constantes de configuración
 * 3. Implementar el método de comunicación según la documentación de TUU
 */

// ====== CONFIGURACIÓN TUU ======
// TODO: Actualizar estos valores con las credenciales reales de TUU
define('TUU_API_URL', 'https://api.tuu.cl/v1/pagos'); // URL de la API de TUU (verificar con TUU)
define('TUU_API_KEY', 'TU_API_KEY_AQUI'); // API Key proporcionada por TUU
define('TUU_MERCHANT_ID', 'TU_MERCHANT_ID_AQUI'); // ID de comercio
define('TUU_TIMEOUT', 30); // Timeout en segundos
define('TUU_MODO_PRUEBA', true); // Cambiar a false en producción

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Obtener datos del POST
$id_ingreso = isset($_POST['id_ingreso']) ? intval($_POST['id_ingreso']) : 0;
$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

if (!$id_ingreso || !$patente || $total <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// ====== FUNCIÓN PARA PROCESAR PAGO CON TUU ======
function procesarPagoTUU($monto, $idTransaccion, $patente) {
    if (TUU_MODO_PRUEBA) {
        // MODO PRUEBA: Simula un pago exitoso
        error_log("TUU MODO PRUEBA - Pago de $monto para transacción $idTransaccion");
        
        // Simulación: 90% de éxito, 10% de rechazo
        $exito = rand(1, 10) > 1;
        
        return [
            'success' => $exito,
            'transaction_id' => 'TUU-TEST-' . time(),
            'authorization_code' => $exito ? 'AUTH' . rand(100000, 999999) : null,
            'message' => $exito ? 'Pago aprobado (MODO PRUEBA)' : 'Pago rechazado (MODO PRUEBA)',
            'card_type' => 'VISA',
            'card_last4' => '****' . rand(1000, 9999),
            'modo_prueba' => true
        ];
    }
    
    // ====== MODO PRODUCCIÓN ======
    // TODO: Implementar la comunicación real con TUU según su documentación
    
    try {
        // Preparar datos para enviar a TUU
        $datosTransaccion = [
            'merchant_id' => TUU_MERCHANT_ID,
            'amount' => $monto,
            'currency' => 'CLP',
            'transaction_id' => $idTransaccion,
            'description' => "Estacionamiento - Patente: $patente",
            'metadata' => [
                'patente' => $patente,
                'tipo' => 'estacionamiento'
            ]
        ];
        
        // Iniciar cURL para comunicación con TUU
        $ch = curl_init(TUU_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TUU_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . TUU_API_KEY,
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($datosTransaccion)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            $resultado = json_decode($response, true);
            return [
                'success' => isset($resultado['approved']) && $resultado['approved'],
                'transaction_id' => $resultado['transaction_id'] ?? null,
                'authorization_code' => $resultado['authorization_code'] ?? null,
                'message' => $resultado['message'] ?? 'Transacción procesada',
                'card_type' => $resultado['card_type'] ?? null,
                'card_last4' => $resultado['card_last4'] ?? null,
                'modo_prueba' => false
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error de comunicación con TUU (HTTP ' . $httpCode . ')',
                'response' => $response
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Excepción al procesar pago: ' . $e->getMessage()
        ];
    }
}

// ====== PROCESAR PAGO ======
date_default_timezone_set('America/Santiago');
$fecha_salida = date('Y-m-d H:i:s');
$transactionId = 'EST-' . $id_ingreso . '-' . time();

// Intentar procesar el pago con TUU
$resultadoPago = procesarPagoTUU($total, $transactionId, $patente);

if ($resultadoPago['success']) {
    // Pago aprobado: Registrar en la base de datos
    $conexion->begin_transaction();
    
    try {
        // Insertar en tabla salidas
        $sql_salida = "INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, transaction_id, authorization_code) 
                       VALUES (?, ?, ?, 'TUU', ?, ?)";
        $stmt_salida = $conexion->prepare($sql_salida);
        $stmt_salida->bind_param("isdss", 
            $id_ingreso, 
            $fecha_salida, 
            $total,
            $resultadoPago['transaction_id'],
            $resultadoPago['authorization_code']
        );
        $stmt_salida->execute();
        $stmt_salida->close();
        
        // Actualizar registro de ingreso
        $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bind_param("i", $id_ingreso);
        $stmt_update->execute();
        $stmt_update->close();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pago procesado correctamente con TUU',
            'transaction_id' => $resultadoPago['transaction_id'],
            'authorization_code' => $resultadoPago['authorization_code'],
            'card_type' => $resultadoPago['card_type'] ?? null,
            'card_last4' => $resultadoPago['card_last4'] ?? null,
            'modo_prueba' => $resultadoPago['modo_prueba'] ?? false
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Error al registrar en base de datos: ' . $e->getMessage()
        ]);
    }
    
} else {
    // Pago rechazado
    echo json_encode([
        'success' => false,
        'error' => $resultadoPago['message'] ?? 'Pago rechazado por TUU',
        'details' => $resultadoPago
    ]);
}

$conexion->close();
?>

