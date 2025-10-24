<?php
/**
 * 🔒 CONFIGURACIÓN SENSIBLE - SISTEMA ESTACIONAMIENTO
 * 
 * ⚠️ IMPORTANTE: Este archivo contiene credenciales sensibles
 * NO subir a GitHub - ya está en .gitignore
 */

// ============================================
// API KEY DE TUU
// ============================================
define('TUU_API_KEY', 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux');

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'estacionamiento');
define('DB_PORT', 3306);

// ============================================
// CONFIGURACIÓN DE FIREBASE
// ============================================
define('FIREBASE_API_KEY', 'AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg');
define('FIREBASE_PROJECT_ID', 'sistemaestacionamiento-46735');
define('FIREBASE_MESSAGING_SENDER_ID', '570161231939');

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================
define('SESSION_SECRET', 'sistema_estacionamiento_los_rios_2025_secret_key');
define('ENCRYPTION_KEY', 'encryption_key_estacionamiento_los_rios_2025');

// ============================================
// CONFIGURACIÓN DE TUU
// ============================================
define('TUU_BASE_URL', 'https://integrations.payment.haulmer.com');
define('TUU_TIMEOUT', 30);
define('TUU_RETRY_ATTEMPTS', 3);
define('TUU_MODO_PRUEBA', false);

// ============================================
// CONFIGURACIÓN DEL SISTEMA
// ============================================
define('SISTEMA_VERSION', '2.0.0');
define('SISTEMA_DEBUG', false);
define('SISTEMA_TIMEZONE', 'America/Santiago');
