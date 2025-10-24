<?php
/**
 * 🔒 ARCHIVO DE CONFIGURACIÓN PRIVADA (EJEMPLO)
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo a: config.private.php
 * 2. Reemplaza los valores de ejemplo con tus valores reales
 * 3. NO subas config.private.php a GitHub (ya está en .gitignore)
 * 4. Guarda config.private.php en un lugar seguro
 */

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

define('DB_HOST', '127.0.0.1');  // O 'localhost'
define('DB_USER', 'tu_usuario_db');  // NO uses 'root' en producción
define('DB_PASS', 'TuContraseñaSegura123!');  // Cambia esto
define('DB_NAME', 'estacionamiento');
define('DB_PORT', 3306);

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================

// Clave secreta para sesiones (genera una aleatoria)
define('SESSION_SECRET', 'cambia_esto_por_algo_aleatorio_largo');

// Tiempo de expiración de sesión (en segundos)
define('SESSION_TIMEOUT', 3600); // 1 hora

// Clave de cifrado (para datos sensibles)
define('ENCRYPTION_KEY', 'otra_clave_secreta_aleatoria');

// ============================================
// USUARIOS DEL SISTEMA
// ============================================

// Contraseñas hasheadas (usa password_hash())
$USUARIOS = [
    'admin' => [
        'nombre_completo' => 'Administrador Principal',
        'email' => 'admin@losrios.cl',
        'rol' => 'admin',
        'password_hash' => password_hash('CambiaEstaContraseña123!', PASSWORD_DEFAULT),
        'permisos' => ['todo']
    ],
    'cajero1' => [
        'nombre_completo' => 'Cajero 1',
        'email' => 'cajero1@losrios.cl',
        'rol' => 'operador',
        'password_hash' => password_hash('OtraContraseñaSegura456!', PASSWORD_DEFAULT),
        'permisos' => ['registrar', 'cobrar', 'imprimir']
    ]
];

// ============================================
// CONFIGURACIÓN DEL SERVIDOR (Solo para admin)
// ============================================

// Info del servidor (NO guardes contraseñas aquí)
define('SERVER_NAME', 'ServidorLosRios');
define('SERVER_IP', 'tu.ip.aqui');
define('SERVER_USER', 'admin_servidor');  // Usuario, NO contraseña

// ============================================
// OTRAS CONFIGURACIONES
// ============================================

// Zona horaria
define('TIMEZONE', 'America/Santiago');

// Modo debug (solo en desarrollo)
define('DEBUG_MODE', false);  // Cambiar a false en producción

// Logs
define('LOG_FILE', __DIR__ . '/logs/system.log');
define('LOG_LEVEL', 'INFO');  // DEBUG, INFO, WARNING, ERROR

// ============================================
// FUNCIONES DE AYUDA
// ============================================

/**
 * Obtener conexión segura a la base de datos
 */
function getSecureDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            error_log("Error de conexión DB: " . $conn->connect_error);
            throw new Exception("Error de conexión a la base de datos");
        }
        
        // Configurar charset
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        // NO mostrar detalles del error al usuario
        die(json_encode([
            'success' => false,
            'error' => 'Error de conexión al sistema. Contacte al administrador.'
        ]));
    }
}

/**
 * Verificar usuario y contraseña
 */
function verificarUsuario($usuario, $password) {
    global $USUARIOS;
    
    if (!isset($USUARIOS[$usuario])) {
        return false;
    }
    
    return password_verify($password, $USUARIOS[$usuario]['password_hash']);
}

/**
 * Obtener información del usuario
 */
function getUsuarioInfo($usuario) {
    global $USUARIOS;
    return $USUARIOS[$usuario] ?? null;
}

?>

