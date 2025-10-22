<?php
/**
 * 🔐 SISTEMA DE AUTENTICACIÓN HÍBRIDO
 * MySQL + Firebase para transición gradual
 * Sistema de Estacionamiento Los Ríos
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/firebase-config.php';

class HybridAuth {
    private $conn;
    private $useFirebase = false; // Cambiar a true cuando esté listo
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Autenticar usuario (MySQL o Firebase)
     */
    public function authenticate($usuario, $password) {
        if ($this->useFirebase) {
            return $this->authenticateWithFirebase($usuario, $password);
        } else {
            return $this->authenticateWithMySQL($usuario, $password);
        }
    }
    
    /**
     * Autenticación con MySQL (actual)
     */
    private function authenticateWithMySQL($usuario, $password) {
        $sql = "SELECT id, usuario, password_hash, rol FROM usuarios WHERE usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                return [
                    'success' => true,
                    'user' => [
                        'id' => $row['id'],
                        'usuario' => $row['usuario'],
                        'rol' => ($row['rol'] === 'cajero') ? 'operador' : $row['rol']
                    ]
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Credenciales incorrectas'];
    }
    
    /**
     * Autenticación con Firebase
     */
    private function authenticateWithFirebase($email, $password) {
        $response = authenticateUserWithFirebase($email, $password);
        
        if ($response['status'] === 200 && isset($response['data']['localId'])) {
            // Obtener datos adicionales del usuario desde Firestore
            $userDoc = getFirestoreDocument('usuarios', $response['data']['localId']);
            
            if ($userDoc['status'] === 200 && isset($userDoc['data']['fields'])) {
                $userData = $userDoc['data']['fields'];
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $userData['id']['integerValue'] ?? $response['data']['localId'],
                        'usuario' => $userData['usuario']['stringValue'] ?? $email,
                        'rol' => $userData['rol']['stringValue'] ?? 'operador'
                    ],
                    'firebase_token' => $response['data']['idToken']
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Error de autenticación con Firebase'];
    }
    
    /**
     * Crear usuario (MySQL o Firebase)
     */
    public function createUser($usuario, $password, $rol = 'operador') {
        if ($this->useFirebase) {
            return $this->createUserInFirebase($usuario, $password, $rol);
        } else {
            return $this->createUserInMySQL($usuario, $password, $rol);
        }
    }
    
    /**
     * Crear usuario en MySQL
     */
    private function createUserInMySQL($usuario, $password, $rol) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (usuario, password_hash, rol) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sss', $usuario, $passwordHash, $rol);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'user_id' => $this->conn->insert_id
            ];
        }
        
        return ['success' => false, 'error' => 'Error al crear usuario en MySQL'];
    }
    
    /**
     * Crear usuario en Firebase
     */
    private function createUserInFirebase($email, $password, $rol) {
        // Crear usuario en Firebase Auth
        $authUrl = FIREBASE_AUTH_URL . ':signUp?key=' . FIREBASE_API_KEY;
        $authData = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];
        
        $authResponse = makeFirebaseRequest($authUrl, 'POST', $authData);
        
        if ($authResponse['status'] === 200 && isset($authResponse['data']['localId'])) {
            $userId = $authResponse['data']['localId'];
            
            // Crear documento en Firestore
            $userData = [
                'fields' => [
                    'usuario' => ['stringValue' => $email],
                    'rol' => ['stringValue' => $rol],
                    'fecha_creacion' => ['timestampValue' => date('c')],
                    'activo' => ['booleanValue' => true]
                ]
            ];
            
            $firestoreResponse = createFirestoreDocument('usuarios', $userData);
            
            if ($firestoreResponse['status'] === 200) {
                return [
                    'success' => true,
                    'user_id' => $userId
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Error al crear usuario en Firebase'];
    }
    
    /**
     * Cambiar a Firebase (para transición)
     */
    public function enableFirebase() {
        $this->useFirebase = true;
    }
    
    /**
     * Volver a MySQL (para rollback)
     */
    public function enableMySQL() {
        $this->useFirebase = false;
    }
    
    /**
     * Obtener estado actual
     */
    public function getCurrentMode() {
        return $this->useFirebase ? 'Firebase' : 'MySQL';
    }
}

// Función de conveniencia para usar en otros archivos
function getHybridAuth() {
    global $conn;
    return new HybridAuth($conn);
}
?>
