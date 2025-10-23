// 🔧 HABILITAR CORS PARA SISTEMA HÍBRIDO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (pre-flight), responder vacía
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

<?php
// Configuración de manejo de errores mejorada
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Manejar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit;
    }
});

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
define('TUU_API_URL_CREATE', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Create'); // URL para crear el pago
define('TUU_API_URL_GET', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Get/'); // URL para consultar estado (termina en /)
define('TUU_API_KEY', 'getenv('TUU_API_KEY') ?: 'TU_API_KEY_AQUI''); // API Key desde Espacio de Trabajo
define('TUU_TIMEOUT', 90); // Timeout de 90 segundos para dar tiempo al cliente a pagar
define('TUU_MODO_PRUEBA', false); // ✅ MODO PRODUCCIÓN ACTIVADO

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../utils/redondeo_chile.php';

// Verificar conexión a BD inmediatamente
if ($conexion && $conexion->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a base de datos: ' . $conexion->connect_error
    ]);
    exit;
}

// Verificar que la extensión cURL esté disponible
if (!function_exists('curl_init')) {
    echo json_encode([
        'success' => false,
        'error' => 'Extensión cURL no está disponible en este servidor. Por favor, instale php-curl.',
        'suggestion' => 'En Ubuntu/Debian ejecute: sudo apt-get install php-curl'
    ]);
    exit;
}

// Device serial FIJO - Número de serie real de la app TUU
define('TUU_DEVICE_SERIAL', '6010B232511900353'); // ✅ CORREGIDO según panel TUU (era 6010B232519... y es 6010B232511...)

// Log de debugging para ver qué datos llegan
error_log("TUU DEBUG - POST recibido: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

// Obtener datos del POST
$id_ingreso = isset($_POST['id_ingreso']) ? intval($_POST['id_ingreso']) : 0;
$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;
$total = redondearSegunLeyChilena($total); // Aplicar redondeo según ley chilena
$metodo_tarjeta = isset($_POST['metodo_tarjeta']) ? $_POST['metodo_tarjeta'] : 'auto';

// Log de los valores parseados
error_log("TUU DEBUG - Valores parseados - id_ingreso: $id_ingreso, patente: $patente, total: $total, metodo_tarjeta: $metodo_tarjeta");

// Si viene 'auto', lo cambiamos a un valor por defecto para la base de datos
if ($metodo_tarjeta === 'auto') {
    $metodo_tarjeta = 'desconocido'; // Se actualizará con el valor real cuando TUU responda
}
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

    // Función interna para consultar el estado de una transacción
    function consultarEstadoTUU($transactionId, $apiKey) {
        $urlGet = TUU_API_URL_GET . $transactionId;
        $ch = curl_init($urlGet);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15, // Timeout corto para consultas
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $apiKey,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_curl = curl_error($ch);
        curl_close($ch);

        if ($error_curl) {
            // Si hay error de cURL, no podemos continuar
            return ['success' => false, 'error' => "Error cURL al consultar estado: $error_curl"];
        }

        if ($httpCode !== 200) {
            // Si TUU da un error HTTP, lo reportamos
            $errorData = json_decode($response, true);
            return ['success' => false, 'error' => "Error TUU al consultar estado ($httpCode): " . ($errorData['message'] ?? $response)];
        }

        return json_decode($response, true);
    }

    // Implementación según documentación oficial: https://developers.tuu.cl/docs/pago-remoto
    
    try {
        // Construir customFields para extraData según documentación TUU
        $customFields = [];
        
        // Agregar hora de ingreso si existe en extraData
        // Límite TUU: name + value <= 28 caracteres
        if (isset($extraData['Hora Ingreso'])) {
            $customFields[] = [
                'name' => 'Entrada',  // 7 caracteres
                'value' => $extraData['Hora Ingreso'],  // 8 caracteres (HH:MM:SS)
                'print' => true
            ];  // Total: 15 caracteres ✅
        }
        
        // Agregar hora de salida si existe
        if (isset($extraData['Hora Salida'])) {
            $customFields[] = [
                'name' => 'Salida',  // 6 caracteres
                'value' => $extraData['Hora Salida'],  // 8 caracteres (HH:MM:SS)
                'print' => true
            ];  // Total: 14 caracteres ✅
        }
        
        // Agregar tipo de servicio si existe (ACORTADO para cumplir límite de 28 caracteres)
        if (isset($extraData['Servicio'])) {
            $servicioCorto = $extraData['Servicio'];
            // Acortar nombres largos de servicios
            $servicioCorto = str_replace('Estacionamiento por minuto', 'Est x min', $servicioCorto);
            $servicioCorto = str_replace('Estacionamiento', 'Est', $servicioCorto);
            // Limitar a máximo 20 caracteres para el valor (dejando 8 para "Tipo")
            $servicioCorto = substr($servicioCorto, 0, 20);
            
            $customFields[] = [
                'name' => 'Tipo',  // 4 caracteres
                'value' => $servicioCorto,  // máximo 20 caracteres
                'print' => true
            ];  // Total: máximo 24 caracteres ✅
        }
        
        // Preparar datos según API V2 con idempotencia
        // Referencia: https://developers.tuu.cl/docs/pago-remoto
        
        // Sanitizar idempotencyKey: máximo 36 caracteres, alfanuméricos y guiones permitidos
        $idempotencyKeyRaw = preg_replace('/[^a-zA-Z0-9\-_]/', '', $idTransaccion);
        $idempotencyKeySafe = substr($idempotencyKeyRaw, 0, 36); // Máximo 36 caracteres según doc
        
        // Sanitizar descripción: máximo 28 caracteres según RP-015
        $descripcionRaw = "Estacionamiento Patente $patente";
        $descripcionSafe = substr($descripcionRaw, 0, 28);
        
        // Configurar DTE según versión que funcionaba
        $dteType = ($tipo_documento === 'factura') ? 33 : 48; // REVERTIDO: 48 para boletas, no 99
        
        $datosTransaccion = [
            'idempotencyKey' => $idempotencyKeySafe, // Identificador único solo alfanumérico
            'amount' => (int)$monto, // Monto en entero (mínimo 100, máximo 99999999)
            'device' => TUU_DEVICE_SERIAL, // Número de serie del dispositivo POS
            'description' => $descripcionSafe, // Descripción sin caracteres especiales
            'dteType' => $dteType, // 33 = Factura, 48 = Boleta (REVERTIDO)
            'extradata' => [ // Objeto extradata (minúscula según doc)
                'customFields' => $customFields, // Array de campos personalizados
                'sourceName' => 'Sistema Estacionamiento Los Rios', // Sin tilde
                'sourceVersion' => 'v2.0'
            ]
        ];

        // ✅ Si es factura, agregamos el RUT del cliente
        // El sistema TUU buscará automáticamente los datos en el SII
        if ($datosTransaccion['dteType'] == 33 && !empty($rut_cliente)) {
            // Limpiar y validar formato del RUT
            $rut_limpio = preg_replace('/[^0-9kK\-\.]/', '', $rut_cliente);
            
            // Debug: Log del RUT que se está enviando
            error_log("TUU DEBUG - RUT cliente: '$rut_cliente' -> limpio: '$rut_limpio'");
            
            $datosTransaccion['customer'] = [
                'rut' => $rut_limpio // RUT limpio sin espacios ni caracteres extra
            ];
        }

        // ✅ PaymentMethod: NO enviar para que la máquina muestre todas las opciones
        // Según documentación TUU: "Si no se envía, la máquina muestra todas las opciones (incluyendo efectivo)"
        // Esto evita el error RP-032: "Device settings do not support the payment method entered"
        
        // COMENTADO: No enviar paymentMethod para evitar errores de configuración del dispositivo
        // if ($metodo_tarjeta === 'credito') {
        //     $datosTransaccion['paymentMethod'] = 1;
        // } elseif ($metodo_tarjeta === 'debito') {
        //     $datosTransaccion['paymentMethod'] = 2;
        // }
        
        // Debug: Log de datos que se envían a TUU con validaciones
        error_log("TUU DEBUG - Datos enviados: " . json_encode($datosTransaccion, JSON_PRETTY_PRINT));
        
        // Validaciones según códigos de error de la documentación v2
        if (!isset($datosTransaccion['device']) || empty($datosTransaccion['device'])) {
            error_log("TUU ERROR - MR-191: Device for API-Key doesn't exist");
        }
        if (!isset($datosTransaccion['amount']) || $datosTransaccion['amount'] < 100) {
            error_log("TUU ERROR - RP-028: Invalid amount, must be equal to or greater than 100. Actual: " . ($datosTransaccion['amount'] ?? 'no definido'));
        }
        if (!isset($datosTransaccion['dteType']) || !in_array($datosTransaccion['dteType'], [33, 48])) {
            error_log("TUU ERROR - MR-130: DTE type not recognized. Value: " . ($datosTransaccion['dteType'] ?? 'no definido'));
        }
        if (strlen($datosTransaccion['idempotencyKey']) < 1 || strlen($datosTransaccion['idempotencyKey']) > 36) {
            error_log("TUU ERROR - RP-001: Idempotency Key length must be between 1 and 36 characters. Actual: " . strlen($datosTransaccion['idempotencyKey']));
        }
        if (strlen($datosTransaccion['description']) > 28) {
            error_log("TUU ERROR - RP-015: Invalid length. Description must be between 1 and 28 characters. Actual: " . strlen($datosTransaccion['description']));
        }
        
        // Log de debugging básico (sin validaciones exempAmount como versión que funcionaba)
        error_log("TUU DEBUG - DTE Type: $dteType, amount: " . $monto . " (versión revertida que funcionaba)");
        
        // Iniciar cURL para comunicación con TUU
        $ch = curl_init(TUU_API_URL_CREATE);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TUU_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true, // Verificar certificados SSL
            CURLOPT_SSL_VERIFYHOST => 2, // Verificar host SSL
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2, // Forzar TLS 1.2 o superior
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
        
        // Debug: Log de respuesta de TUU
        error_log("TUU DEBUG - HTTP Code: $httpCode, Response: " . json_encode($resultado, JSON_PRETTY_PRINT));
        
        // Si la creación del pago falla, retornamos el error inmediatamente
        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorCode = $resultado['code'] ?? 'UNKNOWN';
            $errorMessage = $resultado['message'] ?? 'Error desconocido';
            $errorDetails = $resultado['details'] ?? null;
            $rawResponse = $response; // Respuesta cruda por si no es JSON válido
            
            // Log detallado del error
            error_log("TUU ERROR HTTP $httpCode - Code: $errorCode, Message: $errorMessage");
            if ($errorDetails) {
                error_log("TUU ERROR Details: " . json_encode($errorDetails, JSON_PRETTY_PRINT));
            }
            
            // Mensajes específicos según códigos de error de la documentación v2
            $mensajeUsuario = $errorMessage;
            
            // Mapeo de códigos de error comunes según documentación
            $mapaErrores = [
                'MR-100' => 'Dispositivo no configurado correctamente',
                'MR-110' => 'Monto menor al mínimo permitido',
                'MR-120' => 'Monto excede el máximo permitido', 
                'MR-130' => 'Tipo de documento no reconocido',
                'MR-140' => 'Falta monto exento para boleta',
                'MR-141' => 'Monto exento no coincide con total',
                'RP-001' => 'Clave de idempotencia inválida (1-36 caracteres)',
                'RP-005' => 'No se especificó monto exento para boleta',
                'RP-006' => 'Monto exento no coincide con total',
                'RP-008' => 'Falta método de pago',
                'RP-015' => 'Longitud inválida en descripción (máx 28 caracteres)',
                'RP-025' => 'Método de pago no válido',
                'RP-028' => 'Monto debe ser mayor o igual a 100',
            ];
            
            if (isset($mapaErrores[$errorCode])) {
                $mensajeUsuario = $mapaErrores[$errorCode];
            } elseif (strpos($errorCode, 'RP-') === 0 || strpos($errorCode, 'MR-') === 0) {
                $mensajeUsuario = "Error de configuración TUU: $errorMessage";
            }
            
            return [
                'success' => false,
                'error' => "pago rechazado por tuu ($errorCode)",
                'error_code' => $errorCode,
                'response' => $resultado,
                'raw_response' => $rawResponse
            ];
        }

        // Si la creación fue exitosa, el estado inicial será "Pending"
        $status = $resultado['status'] ?? null;
        $tuuTransactionId = $resultado['id'] ?? null;

        if ($status === 'Pending' && $tuuTransactionId) {
            // 🔄 MODO RED LOCAL: No hacer polling aquí, dejar que JavaScript maneje la verificación
            // Esto mejora la experiencia del usuario y reduce carga en el servidor
            
            error_log("TUU RED LOCAL - Pago iniciado (Pending): $tuuTransactionId para $patente");
            
            // Retornar inmediatamente con estado "pending" para que JavaScript maneje la verificación
            return [
                'success' => false, // Temporal, será true cuando se confirme
                'status' => 'pending',
                'transaction_id' => $tuuTransactionId,
                'message' => 'Pago enviado a TUU. Verificando estado...',
                'red_local' => true // Indicador para JavaScript
            ];
            
            /* CÓDIGO ORIGINAL (comentado para red local):
            // Inicia el bucle de sondeo (polling)
            $tiempoInicio = time();
            
            while (time() - $tiempoInicio < TUU_TIMEOUT) {
                sleep(3); // Esperar 3 segundos entre cada consulta

                $estadoActual = consultarEstadoTUU($tuuTransactionId, TUU_API_KEY);

                if (!$estadoActual['success']) {
                    // Si la consulta de estado falla, retornamos el error
                    return $estadoActual;
                }

                $statusConsulta = $estadoActual['status'] ?? null;

                // Estados finales: Completed, Failed, Canceled
                if (in_array($statusConsulta, ['Completed', 'Failed', 'Canceled'])) {
                    $resultado = $estadoActual; // Usamos la respuesta final
                    break; // Salir del bucle
                }
                // Si sigue en Pending o Processing, el bucle continúa
            }
            */
        }

        // Analizar el resultado final (después del bucle o si no fue 'Pending')
        $finalStatus = $resultado['status'] ?? null;
        $pagoExitoso = ($finalStatus === 'Completed');

        return [
            'success' => $pagoExitoso,
            'status' => $finalStatus,
            'transaction_id' => $resultado['id'] ?? $idTransaccion,
            'authorization_code' => $resultado['paymentData']['authorizationCode'] ?? null,
            'message' => $pagoExitoso ? 'Pago Aprobado' : ($resultado['message'] ?? 'Estado: ' . $finalStatus),
            'card_type' => $resultado['paymentData']['cardType'] ?? null,
            'card_last4' => $resultado['paymentData']['last4Digits'] ?? null,
            'modo_prueba' => false
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Excepción al procesar pago: ' . $e->getMessage()
        ];
    }
}

// ====== PROCESAR PAGO ======
try {
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
    // Usar formato sin caracteres especiales (TUU elimina los : )
    $extraDataParaTUU = [
        "Servicio" => $info['nombre_servicio'],
        "Hora Ingreso" => $fecha_ingreso_dt->format('H.i.s'), // Usar puntos en lugar de dos puntos
        "Hora Salida" => $fecha_salida_dt->format('H.i.s')     // Ejemplo: 14.45.30
    ];

    // Si es factura y hay un RUT, lo agregamos al voucher para que se imprima
    if ($tipo_documento === 'factura' && !empty($rut_cliente)) {
        $extraDataParaTUU["RUT Cliente"] = $rut_cliente;
    }
}
$stmt_info->close();

// Guardar el transaction_id y total antes de procesar con TUU
// Esto permite identificar pagos pendientes si falla la confirmación automática
try {
    $sql_update_ingreso = "UPDATE ingresos SET 
                           transaction_id_tuu = ?, 
                           total_calculado_tuu = ?,
                           fecha_intento_tuu = NOW()
                           WHERE idautos_estacionados = ?";
    $stmt_update = $conexion->prepare($sql_update_ingreso);
    $stmt_update->bind_param("sdi", $transactionId, $total, $id_ingreso);
    $stmt_update->execute();
    $stmt_update->close();
    
    error_log("TUU - Transaction ID guardado: $transactionId para patente $patente, total: $total");
} catch (Exception $e) {
    error_log("TUU - Error guardando transaction_id: " . $e->getMessage());
    // Continuar con el procesamiento aunque falle el guardado
}

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
    // Pago rechazado - manejar diferentes tipos de errores
    $errorPrincipal = $resultadoPago['error'] ?? $resultadoPago['message'] ?? 'Pago rechazado por TUU';
    $errorCode = $resultadoPago['error_code'] ?? null;
    $responseData = $resultadoPago['response'] ?? null;
    
    // Si hay un error específico de TUU, usarlo
    if (isset($resultadoPago['error']) && strpos($resultadoPago['error'], 'TUU Error') === 0) {
        $errorPrincipal = $resultadoPago['error'];
    }
    
    // Log detallado del error para debugging
    error_log("TUU PAGO RECHAZADO - Error: $errorPrincipal, Code: $errorCode, Response: " . json_encode($responseData));
    
    echo json_encode([
        'success' => false,
        'error' => $errorPrincipal,
        'error_code' => $errorCode,
        'details' => $resultadoPago
    ]);
}

} catch (Exception $e) {
    // Capturar cualquier error no manejado
    error_log("TUU Error no manejado: " . $e->getMessage() . " en línea " . $e->getLine());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

if ($conexion) {
    $conexion->close();
}

/*
 * NOTA PARA EL ADMINISTRADOR DEL SERVIDOR:
 * 
 * Si recibe el error: "Extensión cURL no está disponible en este servidor"
 * 
 * Para solucionarlo, instale la extensión cURL de PHP:
 * 
 * En Ubuntu/Debian:
 * sudo apt-get update
 * sudo apt-get install php-curl
 * sudo systemctl restart apache2  # o nginx
 * 
 * En CentOS/RHEL:
 * sudo yum install php-curl
 * sudo systemctl restart httpd
 * 
 * En XAMPP (Windows/Linux):
 * - La extensión ya viene incluida, verifique que esté habilitada en php.ini
 * - Busque la línea: extension=curl y descoméntela si está comentada
 * 
 * Verificar instalación:
 * php -m | grep curl
 */
?>
