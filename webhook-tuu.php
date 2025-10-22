<?php
/**
 * Webhook TUU - Recibe notificaciones de pagos exitosos y fallidos
 * Configurado manualmente por el equipo de Haulmer
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo aceptar POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Obtener datos del webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log del webhook
error_log("Webhook TUU recibido: " . $input);

// Verificar que tenemos datos
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit();
}

// Extraer información del pago
$transaction_id = $data['transaction_id'] ?? null;
$status = $data['status'] ?? null;
$amount = $data['amount'] ?? null;
$patente = $data['patente'] ?? null;
$fecha_pago = $data['fecha_pago'] ?? date('Y-m-d H:i:s');

// Verificar datos requeridos
if (!$transaction_id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos requeridos']);
    exit();
}

// Conectar a la base de datos
require_once 'conexion.php';

try {
    // Buscar el ticket por transaction_id_tuu
    $stmt = $conn->prepare("
        SELECT i.*, t.precio 
        FROM ingresos i 
        LEFT JOIN tickets t ON i.id = t.id_ingreso 
        WHERE i.transaction_id_tuu = ?
    ");
    $stmt->bind_param("s", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Ticket no encontrado
        echo json_encode([
            'success' => false,
            'message' => 'Ticket no encontrado',
            'transaction_id' => $transaction_id
        ]);
        exit();
    }
    
    $ticket = $result->fetch_assoc();
    
    // Procesar según el estado
    if ($status === 'success' || $status === 'approved') {
        // Pago exitoso
        $stmt_update = $conn->prepare("
            UPDATE ingresos 
            SET 
                total_calculado_tuu = ?,
                fecha_intento_tuu = ?,
                estado = 'pagado'
            WHERE transaction_id_tuu = ?
        ");
        $stmt_update->bind_param("dss", $amount, $fecha_pago, $transaction_id);
        
        if ($stmt_update->execute()) {
            // Registrar salida
            $stmt_salida = $conn->prepare("
                INSERT INTO salidas (id_ingreso, fecha_salida, total_pagado, metodo_pago) 
                VALUES (?, ?, ?, 'TUU')
            ");
            $stmt_salida->bind_param("isd", $ticket['id'], $fecha_pago, $amount);
            $stmt_salida->execute();
            
            // Notificar a Firebase
            require_once 'firebase-config.php';
            
            $firebase_data = [
                'type' => 'tuu_payment_confirmed',
                'transaction_id' => $transaction_id,
                'status' => 'success',
                'amount' => $amount,
                'patente' => $patente,
                'timestamp' => time()
            ];
            
            // Enviar a Firebase
            $firebase_ref = $database->getReference('notifications/' . uniqid());
            $firebase_ref->set($firebase_data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'transaction_id' => $transaction_id,
                'status' => 'success'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error actualizando ticket',
                'transaction_id' => $transaction_id
            ]);
        }
        
    } elseif ($status === 'failed' || $status === 'rejected') {
        // Pago fallido
        $stmt_update = $conn->prepare("
            UPDATE ingresos 
            SET 
                fecha_intento_tuu = ?,
                estado = 'pendiente'
            WHERE transaction_id_tuu = ?
        ");
        $stmt_update->bind_param("ss", $fecha_pago, $transaction_id);
        
        if ($stmt_update->execute()) {
            // Notificar a Firebase
            require_once 'firebase-config.php';
            
            $firebase_data = [
                'type' => 'tuu_payment_failed',
                'transaction_id' => $transaction_id,
                'status' => 'failed',
                'amount' => $amount,
                'patente' => $patente,
                'timestamp' => time()
            ];
            
            // Enviar a Firebase
            $firebase_ref = $database->getReference('notifications/' . uniqid());
            $firebase_ref->set($firebase_data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pago fallido registrado',
                'transaction_id' => $transaction_id,
                'status' => 'failed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error actualizando ticket',
                'transaction_id' => $transaction_id
            ]);
        }
    } else {
        // Estado desconocido
        echo json_encode([
            'success' => false,
            'message' => 'Estado desconocido: ' . $status,
            'transaction_id' => $transaction_id
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en webhook TUU: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}
?>