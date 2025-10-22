<?php
/**
 * 🔒 CONFIGURACIÓN SENSIBLE - EJEMPLO
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo a: config-sensible.php
 * 2. Reemplaza los valores de ejemplo con tus valores reales
 * 3. NO subas config-sensible.php a GitHub (ya está en .gitignore)
 * 4. Guarda config-sensible.php en un lugar seguro
 */

// ============================================
// API KEY DE TUU
// ============================================
define('TUU_API_KEY', 'tu_api_key_real_aqui');

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');
define('DB_NAME', 'estacionamiento');
define('DB_PORT', 3306);

// ============================================
// CONFIGURACIÓN DE FIREBASE
// ============================================
define('FIREBASE_API_KEY', 'tu_firebase_api_key');
define('FIREBASE_PROJECT_ID', 'tu_project_id');
define('FIREBASE_MESSAGING_SENDER_ID', 'tu_sender_id');

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================
define('SESSION_SECRET', 'tu_session_secret_aqui');
define('ENCRYPTION_KEY', 'tu_encryption_key_aqui');

// ============================================
// CONFIGURACIÓN DE TUU
// ============================================
define('TUU_BASE_URL', 'https://integrations.payment.haulmer.com');
define('TUU_TIMEOUT', 30);
define('TUU_RETRY_ATTEMPTS', 3);
