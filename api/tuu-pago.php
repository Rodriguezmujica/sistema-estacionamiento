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
define('TUU_API_URL_CREATE', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Create'); // URL para crear el pago
define('TUU_API_URL_GET', 'https://integrations.payment.haulmer.com/RemotePayment/v2/Get/'); // URL para consultar estado (termina en /)
define('TUU_API_KEY', 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux'); // API Key desde Espacio de Trabajo
define('TUU_TIMEOUT', 90); // Timeout de 90 segundos para dar tiempo al cliente a pagar
define('TUU_MODO_PRUEBA', false); // ✅ MODO PRODUCCIÓN ACTIVADO

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Device serial FIJO - Número de serie real de la app TUU
define('TUU_DEVICE_SERIAL', '6010B232511900353'); // ✅ CORREGIDO según panel TUU (era 6010B232519... y es 6010B232511...)

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
        
        // Sanitizar idempotencyKey: solo alfanuméricos (TUU no acepta guiones ni caracteres especiales)
        $idempotencyKeySafe = preg_replace('/[^a-zA-Z0-9]/', '', $idTransaccion);
        
        // Sanitizar descripción: remover caracteres especiales problemáticos
        $descripcionSafe = "Estacionamiento Patente $patente";
        
        $datosTransaccion = [
            'idempotencyKey' => $idempotencyKeySafe, // Identificador único solo alfanumérico
            'amount' => (int)$monto, // Monto en entero (mínimo 100, máximo 99999999)
            'device' => TUU_DEVICE_SERIAL, // Número de serie del dispositivo POS
            'description' => $descripcionSafe, // Descripción sin caracteres especiales
            'dteType' => ($tipo_documento === 'factura') ? 33 : 48, // 33 = Factura, 48 = Boleta
            'extradata' => [ // Objeto extradata (minúscula según doc)
                'customFields' => $customFields, // Array de campos personalizados
                'sourceName' => 'Sistema Estacionamiento Los Rios', // Sin tilde
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

        // ✅ PaymentMethod: NO enviar para que la máquina muestre todas las opciones
        // Según documentación TUU: "Si no se envía, la máquina muestra todas las opciones (incluyendo efectivo)"
        // Esto evita el error RP-032: "Device settings do not support the payment method entered"
        
        // COMENTADO: No enviar paymentMethod para evitar errores de configuración del dispositivo
        // if ($metodo_tarjeta === 'credito') {
        //     $datosTransaccion['paymentMethod'] = 1;
        // } elseif ($metodo_tarjeta === 'debito') {
        //     $datosTransaccion['paymentMethod'] = 2;
        // }
        
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
        
        // Si la creación del pago falla, retornamos el error inmediatamente
        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorCode = $resultado['code'] ?? 'UNKNOWN';
            $errorMessage = $resultado['message'] ?? 'Error desconocido';
            
            return [
                'success' => false,
                'error' => "TUU Error ($errorCode): $errorMessage",
                'error_code' => $errorCode,
                'response' => $resultado
            ];
        }

        // Si la creación fue exitosa, el estado inicial será "Pending"
        $status = $resultado['status'] ?? null;
        $tuuTransactionId = $resultado['id'] ?? null;

        if ($status === 'Pending' && $tuuTransactionId) {
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
