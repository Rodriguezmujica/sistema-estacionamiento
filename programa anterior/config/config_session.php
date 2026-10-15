<?php
/**
 * 🔧 CONFIGURACIÓN DE SESIONES PARA SISTEMA DE ESTACIONAMIENTO
 * 
 * Este archivo debe incluirse ANTES de cualquier session_start()
 * para configurar correctamente el timeout de sesiones.
 */

// ============================================
// CONFIGURACIÓN DE SESIONES
// ============================================

// Tiempo de vida de la sesión en segundos (8 horas = 28800 segundos)
$session_timeout = 8 * 60 * 60; // 8 horas

// Configurar parámetros de sesión ANTES de iniciarla
ini_set('session.gc_maxlifetime', $session_timeout);
ini_set('session.cookie_lifetime', $session_timeout);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 0); // 0 para HTTP local, 1 para HTTPS

// Configurar el directorio de sesiones si no existe
$session_path = __DIR__ . '/logs/sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

// ============================================
// FUNCIÓN PARA VERIFICAR SESIÓN ACTIVA
// ============================================

function verificarSesionActiva() {
    if (!isset($_SESSION['usuario'])) {
        return false;
    }
    
    // Verificar si la sesión ha expirado
    if (isset($_SESSION['ultima_actividad'])) {
        $tiempo_actual = time();
        $tiempo_ultima_actividad = $_SESSION['ultima_actividad'];
        $tiempo_expiracion = 8 * 60 * 60; // 8 horas
        
        if (($tiempo_actual - $tiempo_ultima_actividad) > $tiempo_expiracion) {
            // Sesión expirada
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Actualizar tiempo de última actividad
    $_SESSION['ultima_actividad'] = time();
    return true;
}

// ============================================
// FUNCIÓN PARA RENOVAR SESIÓN
// ============================================

function renovarSesion() {
    if (isset($_SESSION['usuario'])) {
        $_SESSION['ultima_actividad'] = time();
        // Regenerar ID de sesión para mayor seguridad
        session_regenerate_id(false);
    }
}

// ============================================
// CONFIGURACIÓN ADICIONAL DE SEGURIDAD
// ============================================

// Configurar headers de seguridad
if (!headers_sent()) {
    // Prevenir caching de páginas de login
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// ============================================
// LOG DE CONFIGURACIÓN (para debugging)
// ============================================

if (defined('DEBUG_SESSIONS') && DEBUG_SESSIONS) {
    error_log("🔧 Configuración de sesiones aplicada:");
    error_log("- Timeout: " . $session_timeout . " segundos (" . ($session_timeout/3600) . " horas)");
    error_log("- Directorio: " . $session_path);
    error_log("- Cookie lifetime: " . ini_get('session.cookie_lifetime'));
}
?>

