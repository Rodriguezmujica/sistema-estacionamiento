<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
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
// Configuración según documentación oficial: https://developers.tuu.cl/docs/pago-remoto
define('TUU_API_URL', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Create'); // URL V2 con idempotencia
define('TUU_API_KEY', 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux'); // API Key desde Espacio de Trabajo
define('TUU_TIMEOUT', 90); // Timeout de 90 segundos para dar tiempo al cliente a pagar
define('TUU_MODO_PRUEBA', true); // Cambiar a false para procesar pagos reales

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Obtener device serial de la máquina activa desde la BD
function obtenerDeviceSerialActivo($conexion) {
    $sql = "SELECT device_serial, nombre FROM configuracion_tuu WHERE activa = 1 LIMIT 1";
    $result = $conexion->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        return [
            'serial' => $row['device_serial'],
            'nombre' => $row['nombre']
        ];
    }
    
    // Fallback: si no hay configuración, usar el serial por defecto
    return [
        'serial' => '6752d2805d5b1d86',
        'nombre' => 'TUU Principal (default)'
    ];
}

$tuuConfig = obtenerDeviceSerialActivo($conexion);
define('TUU_DEVICE_SERIAL', $tuuConfig['serial']); // Device serial dinámico

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
function procesarPagoTUU($monto, $idTransaccion, $patente, $extraData = [], $metodo_tarjeta = 'desconocido', $rut_cliente = null, $tipo_documento = 'boleta') {
    if (TUU_MODO_PRUEBA) {
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
            'card_type' => $metodo_tarjeta === 'efectivo' ? 'EFECTIVO' : 'VISA', // Simula efectivo
            'card_last4' => '****' . rand(1000, 9999),
            'modo_prueba' => true
        ];
    }
    
    // ====== MODO PRODUCCIÓN ======
    // Implementación según documentación oficial: https://developers.tuu.cl/docs/pago-remoto
    
    try {
        // Construir customFields para extraData según documentación TUU
        $customFields = [];
        
        // Agregar hora de ingreso si existe en extraData
        if (isset($extraData['Hora Ingreso'])) {
            $customFields[] = [
                'name' => 'Hora Ingreso',
                'value' => $extraData['Hora Ingreso'],
                'print' => true
            ];
        }
        
        // Agregar hora de salida si existe
        if (isset($extraData['Hora Salida'])) {
            $customFields[] = [
                'name' => 'Hora Salida',
                'value' => $extraData['Hora Salida'],
                'print' => true
            ];
        }
        
        // Agregar tipo de servicio si existe
        if (isset($extraData['Servicio'])) {
            $customFields[] = [
                'name' => 'Servicio',
                'value' => $extraData['Servicio'],
                'print' => true
            ];
        }
        
        // Preparar datos según API V2 con idempotencia
        // Referencia: https://developers.tuu.cl/docs/pago-remoto
        $datosTransaccion = [
            'idempotencyKey' => $idTransaccion, // Identificador único (obligatorio en V2)
            'amount' => (int)$monto, // Monto en entero (mínimo 100, máximo 99999999)
            'device' => TUU_DEVICE_SERIAL, // Número de serie del dispositivo POS
            'description' => "Estacionamiento - Patente: $patente", // Descripción de la transacción
            'dteType' => ($tipo_documento === 'factura') ? 33 : 48, // 33 = Factura, 48 = Boleta
            'extradata' => [ // Objeto extradata (minúscula según doc)
                'customFields' => $customFields, // Array de campos personalizados
                'sourceName' => 'Sistema Estacionamiento Los Ríos',
                'sourceVersion' => 'v2.0'
            ]
        ];

        // ✅ Si es factura, agregamos el RUT del cliente
        // El sistema TUU buscará automáticamente los datos en el SII
        if ($datosTransaccion['dteType'] == 33 && !empty($rut_cliente)) {
            $datosTransaccion['customer'] = [
                'rut' => $rut_cliente // RUT con formato XX.XXX.XXX-X o XXXXXXXX-X
            ];
        }

        // ✅ PaymentMethod: 1 = Crédito, 2 = Débito
        // Si no se envía, la máquina muestra todas las opciones (incluyendo efectivo)
        if ($metodo_tarjeta === 'credito') {
            $datosTransaccion['paymentMethod'] = 1;
        } elseif ($metodo_tarjeta === 'debito') {
            $datosTransaccion['paymentMethod'] = 2;
        }
        // ✅ Para 'efectivo': NO enviamos paymentMethod para que muestre todas las opciones
        
        // Iniciar cURL para comunicación con TUU
        $ch = curl_init(TUU_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TUU_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . TUU_API_KEY, // ✅ Header correcto según documentación
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($datosTransaccion, JSON_UNESCAPED_UNICODE)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_curl = curl_error($ch);
        curl_close($ch);
        
        if ($error_curl) {
            return ['success' => false, 'error' => 'Error cURL: ' . $error_curl];
        }

        $resultado = json_decode($response, true);
        
        // Estados según documentación TUU:
        // 0=Pending, 1=Sent, 2=Canceled, 3=Processing, 4=Failed, 5=Completed
        if ($httpCode === 200 || $httpCode === 201) {
            $status = $resultado['status'] ?? null;
            $pagoExitoso = ($status === 5 || $status === 'Completed'); // Estado Completed

            return [
                'success' => $pagoExitoso,
                'status' => $status,
                'transaction_id' => $resultado['id'] ?? $idTransaccion,
                'authorization_code' => $resultado['paymentData']['authorizationCode'] ?? null,
                'message' => $pagoExitoso ? 'Pago Aprobado' : ($resultado['message'] ?? 'Estado: ' . $status),
                'card_type' => $resultado['paymentData']['cardType'] ?? null,
                'card_last4' => $resultado['paymentData']['last4Digits'] ?? null,
                'modo_prueba' => false
            ];
        } else {
            // Manejo de errores según tabla de errores de TUU
            $errorCode = $resultado['code'] ?? 'UNKNOWN';
            $errorMessage = $resultado['message'] ?? 'Error desconocido';
            
            return [
                'success' => false,
                'error' => "TUU Error ($errorCode): $errorMessage",
                'error_code' => $errorCode,
                'response' => $resultado
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
$extraDataParaTUU = [];

// 1. Obtener datos adicionales para el voucher de TUU
$sql_info = "SELECT i.fecha_ingreso, ti.nombre_servicio 
             FROM ingresos i
             JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
             WHERE i.idautos_estacionados = ?";
$stmt_info = $conexion->prepare($sql_info);
$stmt_info->bind_param("i", $id_ingreso);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
if ($info = $result_info->fetch_assoc()) {
    $fecha_ingreso_dt = new DateTime($info['fecha_ingreso']);
    $fecha_salida_dt = new DateTime($fecha_salida);

    // Formateamos los datos para que se vean bien en el voucher
    $extraDataParaTUU = [
        "Servicio" => $info['nombre_servicio'],
        "Hora Ingreso" => $fecha_ingreso_dt->format('H:i:s'),
        "Hora Salida" => $fecha_salida_dt->format('H:i:s')
    ];

    // Si es factura y hay un RUT, lo agregamos al voucher para que se imprima
    if ($tipo_documento === 'factura' && !empty($rut_cliente)) {
        $extraDataParaTUU["RUT Cliente"] = $rut_cliente;
    }
}
$stmt_info->close();

// Intentar procesar el pago con TUU
$resultadoPago = procesarPagoTUU($total, $transactionId, $patente, $extraDataParaTUU, $metodo_tarjeta, $rut_cliente, $tipo_documento);

if ($resultadoPago['success']) {
    // Pago aprobado: Registrar en la base de datos
    $conexion->begin_transaction();
    
    try {
        // 1. Obtener información del lavado pendiente (si existe)
        $motivos_extra = null;
        $descripcion_extra = null;
        $precio_extra = 0;
        
        $sql_pendiente = "SELECT motivos_extra, descripcion_extra, precio_extra FROM lavados_pendientes WHERE id_ingreso = ?";
        $stmt_pendiente = $conexion->prepare($sql_pendiente);
        $stmt_pendiente->bind_param("i", $id_ingreso);
        $stmt_pendiente->execute();
        $result_pendiente = $stmt_pendiente->get_result();
        
        if ($lavado_pendiente = $result_pendiente->fetch_assoc()) {
            $motivos_extra = $lavado_pendiente['motivos_extra'];
            $descripcion_extra = $lavado_pendiente['descripcion_extra'];
            $precio_extra = floatval($lavado_pendiente['precio_extra']);
        }
        $stmt_pendiente->close();
        
        // 2. Insertar en tabla salidas (incluyendo datos del lavado si existen)
        // Construcción dinámica de la consulta para ser más robusto
        $columnas = ['id_ingresos', 'fecha_salida', 'total', 'metodo_pago', 'tipo_pago', 'metodo_tarjeta', 'tipo_documento', 'rut_cliente'];
        $valores = [$id_ingreso, $fecha_salida, $total, 'TUU', 'tuu', $metodo_tarjeta, $tipo_documento, $rut_cliente];
        $tipos = 'isdsssss';
        
        // Agregar datos del lavado si existen
        if (!empty($motivos_extra)) {
            $columnas[] = 'motivos_extra';
            $valores[] = $motivos_extra;
            $tipos .= 's';
        }
        if (!empty($descripcion_extra)) {
            $columnas[] = 'descripcion_extra';
            $valores[] = $descripcion_extra;
            $tipos .= 's';
        }
        if ($precio_extra > 0) {
            $columnas[] = 'precio_extra';
            $valores[] = $precio_extra;
            $tipos .= 'd';
        }

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
        
        // 3. Eliminar el registro de lavados_pendientes (si existe)
        $sql_eliminar = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
        $stmt_eliminar = $conexion->prepare($sql_eliminar);
        $stmt_eliminar->bind_param("i", $id_ingreso);
        $stmt_eliminar->execute();
        $stmt_eliminar->close();
        
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
