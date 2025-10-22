<?php
/**
 * 🔒 CONFIGURACIÓN DE SEGURIDAD FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo maneja la configuración y aplicación de reglas de seguridad
 */

require_once __DIR__ . '/firebase-config.php';

class FirebaseSecurityConfig {
    private $projectId;
    private $accessToken;
    
    public function __construct() {
        $this->projectId = FIREBASE_PROJECT_ID;
        $this->accessToken = getFirebaseAccessToken();
    }
    
    /**
     * Aplicar reglas de Firestore
     */
    public function applyFirestoreRules() {
        $rulesFile = __DIR__ . '/firebase-security-rules/firestore.rules';
        
        if (!file_exists($rulesFile)) {
            return [
                'success' => false,
                'error' => 'Archivo de reglas de Firestore no encontrado'
            ];
        }
        
        $rules = file_get_contents($rulesFile);
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default):commit";
        
        $data = [
            'writes' => [
                [
                    'update' => [
                        'name' => "projects/{$this->projectId}/databases/(default)/documents/__rules__",
                        'fields' => [
                            'rules' => [
                                'stringValue' => $rules
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest($url, 'POST', $data);
    }
    
    /**
     * Aplicar reglas de Storage
     */
    public function applyStorageRules() {
        $rulesFile = __DIR__ . '/firebase-security-rules/storage.rules';
        
        if (!file_exists($rulesFile)) {
            return [
                'success' => false,
                'error' => 'Archivo de reglas de Storage no encontrado'
            ];
        }
        
        $rules = file_get_contents($rulesFile);
        $url = "https://firebaserules.googleapis.com/v1/projects/{$this->projectId}/releases";
        
        $data = [
            'rulesetName' => "projects/{$this->projectId}/rulesets/storage",
            'release' => [
                'name' => "projects/{$this->projectId}/releases/storage",
                'rulesetName' => "projects/{$this->projectId}/rulesets/storage"
            ]
        ];
        
        return $this->makeRequest($url, 'POST', $data);
    }
    
    /**
     * Verificar reglas de Firestore
     */
    public function verifyFirestoreRules() {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/__rules__";
        
        $result = $this->makeRequest($url, 'GET');
        
        if ($result['success']) {
            return [
                'success' => true,
                'rules' => $result['data']['fields']['rules']['stringValue'] ?? null
            ];
        }
        
        return $result;
    }
    
    /**
     * Verificar reglas de Storage
     */
    public function verifyStorageRules() {
        $url = "https://firebaserules.googleapis.com/v1/projects/{$this->projectId}/rulesets";
        
        $result = $this->makeRequest($url, 'GET');
        
        if ($result['success']) {
            return [
                'success' => true,
                'rulesets' => $result['data']['rulesets'] ?? []
            ];
        }
        
        return $result;
    }
    
    /**
     * Crear usuario de prueba para desarrollo
     */
    public function createTestUser($email, $password, $rol = 'operador') {
        $url = FIREBASE_AUTH_URL . ':signUp?key=' . FIREBASE_API_KEY;
        
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];
        
        $result = $this->makeRequest($url, 'POST', $data);
        
        if ($result['success'] && isset($result['data']['localId'])) {
            $userId = $result['data']['localId'];
            
            // Crear documento de usuario en Firestore
            $userData = [
                'fields' => [
                    'usuario' => ['stringValue' => $email],
                    'rol' => ['stringValue' => $rol],
                    'fecha_creacion' => ['timestampValue' => date('c')],
                    'activo' => ['booleanValue' => true],
                    'es_usuario_prueba' => ['booleanValue' => true]
                ]
            ];
            
            $firestoreUrl = FIRESTORE_URL . '/usuarios/' . $userId;
            $firestoreResult = $this->makeRequest($firestoreUrl, 'PATCH', $userData);
            
            if ($firestoreResult['success']) {
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'email' => $email,
                    'rol' => $rol
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Configurar reglas de seguridad por defecto
     */
    public function setupDefaultSecurity() {
        $this->log("Configurando seguridad por defecto...");
        
        // Aplicar reglas de Firestore
        $firestoreResult = $this->applyFirestoreRules();
        if ($firestoreResult['success']) {
            $this->log("Reglas de Firestore aplicadas correctamente");
        } else {
            $this->log("Error aplicando reglas de Firestore: " . ($firestoreResult['error'] ?? 'Desconocido'), 'ERROR');
        }
        
        // Aplicar reglas de Storage
        $storageResult = $this->applyStorageRules();
        if ($storageResult['success']) {
            $this->log("Reglas de Storage aplicadas correctamente");
        } else {
            $this->log("Error aplicando reglas de Storage: " . ($storageResult['error'] ?? 'Desconocido'), 'ERROR');
        }
        
        // Crear usuario de prueba
        $testUserResult = $this->createTestUser('test@estacionamiento.com', 'test123', 'operador');
        if ($testUserResult['success']) {
            $this->log("Usuario de prueba creado: " . $testUserResult['email']);
        } else {
            $this->log("Error creando usuario de prueba: " . ($testUserResult['error'] ?? 'Desconocido'), 'ERROR');
        }
        
        return [
            'firestore' => $firestoreResult,
            'storage' => $storageResult,
            'test_user' => $testUserResult
        ];
    }
    
    /**
     * Verificar configuración de seguridad
     */
    public function verifySecurityConfig() {
        $this->log("Verificando configuración de seguridad...");
        
        $firestoreRules = $this->verifyFirestoreRules();
        $storageRules = $this->verifyStorageRules();
        
        $firestoreOk = $firestoreRules['success'] && !empty($firestoreRules['rules']);
        $storageOk = $storageRules['success'] && !empty($storageRules['rulesets']);
        
        if ($firestoreOk) {
            $this->log("✅ Reglas de Firestore configuradas correctamente");
        } else {
            $this->log("❌ Problemas con reglas de Firestore", 'ERROR');
        }
        
        if ($storageOk) {
            $this->log("✅ Reglas de Storage configuradas correctamente");
        } else {
            $this->log("❌ Problemas con reglas de Storage", 'ERROR');
        }
        
        return [
            'firestore' => $firestoreOk,
            'storage' => $storageOk,
            'overall' => $firestoreOk && $storageOk
        ];
    }
    
    /**
     * Hacer petición HTTP
     */
    private function makeRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ]);
        
        if ($method === 'POST' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => json_decode($response, true),
            'status' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    /**
     * Log de seguridad
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] [$level] $message\n";
    }
}

// Funciones de conveniencia
function getFirebaseSecurityConfig() {
    return new FirebaseSecurityConfig();
}

function setupFirebaseSecurity() {
    $security = getFirebaseSecurityConfig();
    return $security->setupDefaultSecurity();
}

function verifyFirebaseSecurity() {
    $security = getFirebaseSecurityConfig();
    return $security->verifySecurityConfig();
}
?>
