<?php
/**
 * 🔥 API PARA GUARDAR TOKENS FCM
 * Sistema de Estacionamiento Los Ríos
 * 
 * Guarda tokens FCM de dispositivos conectados
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');

try {
    // Obtener datos del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['token'])) {
        throw new Exception('Token FCM requerido');
    }
    
    $token = $data['token'];
    
    // Validar token FCM (formato básico)
    if (strlen($token) < 100) {
        throw new Exception('Token FCM inválido');
    }
    
    // Leer tokens existentes
    $tokenFile = __DIR__ . '/fcm_tokens.json';
    $tokens = [];
    
    if (file_exists($tokenFile)) {
        $savedTokens = json_decode(file_get_contents($tokenFile), true);
        if (is_array($savedTokens)) {
            $tokens = $savedTokens;
        }
    }
    
    // Agregar nuevo token si no existe
    if (!in_array($token, $tokens)) {
        $tokens[] = $token;
        
        // Limitar a 10 tokens máximo (para evitar archivo muy grande)
        if (count($tokens) > 10) {
            $tokens = array_slice($tokens, -10);
        }
        
        // Guardar tokens
        if (file_put_contents($tokenFile, json_encode($tokens, JSON_PRETTY_PRINT))) {
            error_log("FCM - Token guardado: " . substr($token, 0, 20) . "...");
            
            echo json_encode([
                'success' => true,
                'message' => 'Token FCM guardado correctamente',
                'token_count' => count($tokens)
            ]);
        } else {
            throw new Exception('Error al guardar token');
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Token FCM ya existe',
            'token_count' => count($tokens)
        ]);
    }
    
} catch (Exception $e) {
    error_log("FCM API ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
