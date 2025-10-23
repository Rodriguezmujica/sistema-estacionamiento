<?php
/**
 * Firebase Webhook TUU - Recibe notificaciones de TUU a través de Firebase
 * Para sistemas locales sin dominio público
 */

require_once 'firebase-config.php';

// Función para procesar notificación TUU desde Firebase
function procesarNotificacionTUU($data) {
    global $database;
    
    $transaction_id = $data['transaction_id'] ?? null;
    $status = $data['status'] ?? null;
    $amount = $data['amount'] ?? null;
    $patente = $data['patente'] ?? null;
    $fecha_pago = $data['fecha_pago'] ?? date('Y-m-d H:i:s');
    
    if (!$transaction_id || !$status) {
        error_log("Firebase Webhook TUU: Faltan datos requeridos");
        return false;
    }
    
    // Conectar a la base de datos local
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
            error_log("Firebase Webhook TUU: Ticket no encontrado - " . $transaction_id);
            return false;
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
                
                // Notificar a Firebase para sincronización
                $firebase_data = [
                    'type' => 'tuu_payment_confirmed',
                    'transaction_id' => $transaction_id,
                    'status' => 'success',
                    'amount' => $amount,
                    'patente' => $patente,
                    'timestamp' => time(),
                    'processed' => true
                ];
                
                $firebase_ref = $database->getReference('tuu_payments/' . $transaction_id);
                $firebase_ref->set($firebase_data);
                
                error_log("Firebase Webhook TUU: Pago confirmado - " . $transaction_id);
                return true;
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
                // Notificar a Firebase para sincronización
                $firebase_data = [
                    'type' => 'tuu_payment_failed',
                    'transaction_id' => $transaction_id,
                    'status' => 'failed',
                    'amount' => $amount,
                    'patente' => $patente,
                    'timestamp' => time(),
                    'processed' => true
                ];
                
                $firebase_ref = $database->getReference('tuu_payments/' . $transaction_id);
                $firebase_ref->set($firebase_data);
                
                error_log("Firebase Webhook TUU: Pago fallido - " . $transaction_id);
                return true;
            }
        }
        
    } catch (Exception $e) {
        error_log("Firebase Webhook TUU Error: " . $e->getMessage());
        return false;
    }
    
    return false;
}

// Función para escuchar notificaciones TUU en Firebase
function escucharNotificacionesTUU() {
    global $database;
    
    try {
        // Escuchar cambios en la colección de notificaciones TUU
        $reference = $database->getReference('tuu_webhook_notifications');
        
        $reference->onChildAdded(function ($snapshot) {
            $data = $snapshot->getValue();
            
            if ($data && !isset($data['processed'])) {
                // Procesar la notificación
                $resultado = procesarNotificacionTUU($data);
                
                if ($resultado) {
                    // Marcar como procesada
                    $snapshot->getRef()->update(['processed' => true, 'processed_at' => time()]);
                }
            }
        });
        
    } catch (Exception $e) {
        error_log("Error escuchando notificaciones TUU: " . $e->getMessage());
    }
}

// Función para simular webhook TUU (para testing)
function simularWebhookTUU($transaction_id, $status, $amount, $patente) {
    global $database;
    
    $webhook_data = [
        'transaction_id' => $transaction_id,
        'status' => $status,
        'amount' => $amount,
        'patente' => $patente,
        'fecha_pago' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'source' => 'simulated'
    ];
    
    // Enviar a Firebase
    $firebase_ref = $database->getReference('tuu_webhook_notifications/' . uniqid());
    $firebase_ref->set($webhook_data);
    
    return true;
}

// Función para configurar webhook TUU con Haulmer
function configurarWebhookTUU() {
    // Esta función se ejecuta una vez para configurar el webhook
    // Haulmer enviará notificaciones a Firebase en lugar de a una URL
    
    $webhook_config = [
        'firebase_url' => 'https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com/tuu_webhook_notifications',
        'api_key' => 'AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg',
        'method' => 'POST',
        'format' => 'JSON',
        'notifications' => ['success', 'failed', 'pending'],
        'configured_at' => date('Y-m-d H:i:s')
    ];
    
    // Guardar configuración en Firebase
    global $database;
    $config_ref = $database->getReference('tuu_webhook_config');
    $config_ref->set($webhook_config);
    
    return $webhook_config;
}

// Inicializar escucha de notificaciones
if (php_sapi_name() === 'cli') {
    // Ejecutar desde línea de comandos
    escucharNotificacionesTUU();
} else {
    // Ejecutar desde web
    echo json_encode([
        'success' => true,
        'message' => 'Firebase Webhook TUU configurado',
        'firebase_url' => 'https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com/tuu_webhook_notifications'
    ]);
}
?>




