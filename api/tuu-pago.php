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
define('TUU_API_URL', 'https://api.haulmer.com/v2/payment/request'); // URL según documentación oficial
define('TUU_API_KEY', 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux'); // API Key proporcionada por TUU
define('TUU_MERCHANT_ID', 'BUSCA_EL_ID_DE_COMERCIO_EN_EL_PANEL'); // ID de comercio desde tu panel
define('TUU_TERMINAL_ID', '6752d2805d5b1d86'); // Este es el UUID de tu máquina "Estacionamiento 1"
define('TUU_TIMEOUT', 90); // Aumentamos el timeout a 90 segundos para dar tiempo al cliente a pagar
define('TUU_MODO_PRUEBA', true); // Cambiar a false para procesar pagos reales

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
$metodo_tarjeta = isset($_POST['metodo_tarjeta']) ? $_POST['metodo_tarjeta'] : 'desconocido';
$tipo_documento = isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : 'boleta';
$rut_cliente = isset($_POST['rut_cliente']) ? trim($_POST['rut_cliente']) : null;

if ($id_ingreso <= 0 || empty($patente) || $total <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// ====== FUNCIÓN PARA PROCESAR PAGO CON TUU ======
function procesarPagoTUU($monto, $idTransaccion, $patente) {
    if (TUU_MODO_PRUEBA || TUU_API_KEY === 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux') {
        // MODO PRUEBA: Simula un pago exitoso
        error_log("TUU MODO PRUEBA - Pago de $monto para transacción $idTransaccion");
        
        // Simulación: 90% de éxito, 10% de rechazo
        $exito = rand(1, 10) > 1;
        
        return [
            'status' => $exito ? 'PAID' : 'REJECTED',
            'success' => $exito,
            'transaction_id' => 'TUU-TEST-' . time(),
            'authorization_code' => $exito ? 'AUTH' . rand(100000, 999999) : '',
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
        // Formato según: https://developers.tuu.cl/reference/post_paymentrequest-create
        $datosTransaccion = [
            'amount' => (int)$monto, // Debe ser un entero
            'subject' => "Estacionamiento Patente: $patente",
            'currency' => 'CLP',
            'merchantId' => TUU_MERCHANT_ID,
            'terminalId' => TUU_TERMINAL_ID,
            'externalId' => $idTransaccion
        ];
        
        // Iniciar cURL para comunicación con TUU
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TUU_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . TUU_API_KEY,
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($datosTransaccion),
            CURLOPT_URL => TUU_API_URL
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_curl = curl_error($ch);
        curl_close($ch);
        
        if ($error_curl) {
            return ['success' => false, 'error' => 'Error cURL: ' . $error_curl];
        }

        // La API de TUU responde con 201 (Created) cuando la solicitud es exitosa
        if ($httpCode === 201) {
            $resultado = json_decode($response, true);
            // La API real es asíncrona. El estado 'PAID' o 'REJECTED' viene en la respuesta.
            // La máquina esperará a que el cliente pague.
            $pagoExitoso = isset($resultado['status']) && $resultado['status'] === 'PAID';

            return [
                'success' => $pagoExitoso,
                'status' => $resultado['status'] ?? 'UNKNOWN',
                'transaction_id' => $resultado['id'] ?? $idTransaccion, // El ID de la transacción de TUU
                'authorization_code' => $resultado['paymentData']['authorizationCode'] ?? null,
                'message' => $pagoExitoso ? 'Pago Aprobado' : ($resultado['status'] ?? 'Estado desconocido'),
                'card_type' => $resultado['paymentData']['cardType'] ?? null,
                'card_last4' => $resultado['paymentData']['last4Digits'] ?? null,
                'modo_prueba' => false
            ];
        } else {
            $error_details = json_decode($response, true);
            return [
                'success' => false,
                'error' => 'Error de comunicación con TUU (HTTP ' . $httpCode . '): ' . ($error_details['message'] ?? 'Error desconocido'),
                'response' => $error_details
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
        // Construcción dinámica de la consulta para ser más robusto
        $columnas = ['id_ingresos', 'fecha_salida', 'total', 'metodo_pago', 'metodo_tarjeta', 'tipo_documento', 'rut_cliente'];
        $valores = [$id_ingreso, $fecha_salida, $total, 'TUU', $metodo_tarjeta, $tipo_documento, $rut_cliente];
        $tipos = 'isdssss';

        $campos_pago = [
            'transaction_id' => 's',
            'authorization_code' => 's',
            'card_type' => 's',
            'card_last4' => 's'
        ];

        foreach ($campos_pago as $campo => $tipo) {
            if (!empty($resultadoPago[$campo])) {
                $columnas[] = $campo;
                $valores[] = $resultadoPago[$campo];
                $tipos .= $tipo;
            }
        }

        $sql_columnas = implode(', ', $columnas);
        $sql_placeholders = implode(', ', array_fill(0, count($columnas), '?'));

        $sql_salida = "INSERT INTO salidas ($sql_columnas) VALUES ($sql_placeholders)";
        $stmt_salida = $conexion->prepare($sql_salida);
        $stmt_salida->bind_param($tipos, ...$valores);
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
