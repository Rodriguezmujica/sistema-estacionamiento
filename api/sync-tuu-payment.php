<?php
/**
 * API para sincronizar pago TUU completado con base de datos local
 * Sistema de Estacionamiento Los Ríos
 */

require_once '../conexion.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.',
        'timestamp' => date('c')
    ]);
    exit();
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de pago no proporcionados');
    }
    
    // Validar datos requeridos
    $requiredFields = ['transaction_id', 'patente', 'precio'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Extraer datos
    $transaction_id = $input['transaction_id'];
    $patente = $input['patente'];
    $precio = floatval($input['precio']);
    $authorization_code = $input['authorization_code'] ?? '';
    $card_type = $input['card_type'] ?? '';
    $card_last4 = $input['card_last4'] ?? '';
    $id_ingreso = $input['id_ingreso'] ?? 0;
    
    // Si no se proporciona id_ingreso, intentar extraerlo del transaction_id
    if ($id_ingreso == 0 && preg_match('/EST-(\d+)-/', $transaction_id, $matches)) {
        $id_ingreso = intval($matches[1]);
    }
    
    if ($id_ingreso == 0) {
        throw new Exception('ID de ingreso no válido');
    }
    
    // Verificar que el ingreso existe
    $sql_check = "SELECT idautos_estacionados, patente, precio, salida 
                  FROM ingresos 
                  WHERE idautos_estacionados = ?";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param('i', $id_ingreso);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($ingreso = $result_check->fetch_assoc()) {
        // Verificar que no esté ya pagado
        if ($ingreso['salida'] == 1) {
            throw new Exception('El ingreso ya está pagado');
        }
        
        // Verificar que la patente coincida
        if ($ingreso['patente'] !== $patente) {
            throw new Exception('La patente no coincide con el ingreso');
        }
        
    } else {
        throw new Exception('Ingreso no encontrado');
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Insertar registro de salida
        $sql_salida = "INSERT INTO salidas (
            id_ingresos, 
            fecha_salida, 
            total, 
            metodo_pago, 
            tipo_pago, 
            transaction_id, 
            authorization_code, 
            card_type, 
            card_last4
        ) VALUES (?, NOW(), ?, 'TUU', 'tuu', ?, ?, ?, ?)";
        
        $stmt_salida = $conn->prepare($sql_salida);
        $stmt_salida->bind_param('idsssss', 
            $id_ingreso, 
            $precio, 
            $transaction_id, 
            $authorization_code, 
            $card_type, 
            $card_last4
        );
        
        if (!$stmt_salida->execute()) {
            throw new Exception('Error insertando salida: ' . $stmt_salida->error);
        }
        
        // 2. Actualizar ingreso como pagado
        $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('i', $id_ingreso);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Error actualizando ingreso: ' . $stmt_update->error);
        }
        
        // 3. Eliminar lavados pendientes si existen
        $sql_eliminar = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
        $stmt_eliminar = $conn->prepare($sql_eliminar);
        $stmt_eliminar->bind_param('i', $id_ingreso);
        $stmt_eliminar->execute();
        
        // Confirmar transacción
        $conn->commit();
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Pago TUU sincronizado correctamente',
            'transaction_id' => $transaction_id,
            'id_ingreso' => $id_ingreso,
            'patente' => $patente,
            'precio' => $precio,
            'timestamp' => date('c')
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
