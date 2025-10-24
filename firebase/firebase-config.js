/**
 * 🔥 CONFIGURACIÓN DE FIREBASE - SISTEMA HÍBRIDO
 * Sistema de Estacionamiento Los Ríos
 * 
 * Configuración para PC 1 (Antix) y PC 2 (Windows 7)
 */

// Importar Firebase desde CDN
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js";
import { getStorage, ref } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-storage.js";
import { getFunctions } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-functions.js";

// Configuración de Firebase
const firebaseConfig = {
  apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
  authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
  projectId: "sistemaestacionamiento-46735",
  storageBucket: "sistemaestacionamiento-46735.firebasestorage.app",
  messagingSenderId: "570161231939",
  appId: "1:570161231939:web:50a5f88fcd65e98fa03cf6",
  measurementId: "G-7E9CZ89DR4"
};

// Inicializar Firebase
const app = initializeApp(firebaseConfig);

// Exportar servicios de Firebase
export const auth = getAuth(app);
export const db = getFirestore(app);
export const storage = getStorage(app);
export const functions = getFunctions(app);

// Exportar funciones de Storage
export { ref };

// Configuración del sistema híbrido
export const systemConfig = {
  // Identificar qué PC es esta
  pcId: detectPC(),
  
  // Configuración de sincronización OPTIMIZADA
  sync: {
    enabled: true,
    interval: 30000, // 30 segundos (optimizado para reducir operaciones)
    retryAttempts: 2, // Menos reintentos
    retryDelay: 5000, // 5 segundos
    smartSync: true, // Solo sincronizar cambios
    batchSize: 10 // Procesar en lotes
  },
  
  // Configuración de conectividad OPTIMIZADA
  connectivity: {
    checkInterval: 30000, // 30 segundos (reducido para optimizar)
    timeout: 10000, // 10 segundos
    offlineThreshold: 60000, // 60 segundos
    adaptiveInterval: true // Ajustar intervalo según actividad
  },
  
  // Configuración de impresión
  printing: {
    enabled: true,
    pc2Only: true, // Solo PC 2 tiene impresora
    fallback: 'manual' // Fallback si no hay impresora
  }
};

/**
 * Detectar qué PC es esta basándose en características del sistema
 */
function detectPC() {
  const userAgent = navigator.userAgent;
  const platform = navigator.platform;
  
  // Detectar Windows 7 (PC 2)
  if (userAgent.includes('Windows NT 6.1')) {
    return 'PC2_WINDOWS7';
  }
  
  // Detectar Linux (PC 1 - Antix)
  if (platform.includes('Linux') || userAgent.includes('Linux')) {
    return 'PC1_ANTIX';
  }
  
  // Detectar por hostname o IP
  const hostname = window.location.hostname;
  if (hostname.includes('antix') || hostname.includes('192.168.1.10')) {
    return 'PC1_ANTIX';
  }
  
  if (hostname.includes('windows7') || hostname.includes('192.168.1.11')) {
    return 'PC2_WINDOWS7';
  }
  
  // Por defecto, asumir PC 1
  return 'PC1_ANTIX';
}

/**
 * Obtener información del sistema actual
 */
export function getSystemInfo() {
  return {
    pcId: systemConfig.pcId,
    userAgent: navigator.userAgent,
    platform: navigator.platform,
    hostname: window.location.hostname,
    timestamp: new Date().toISOString(),
    online: navigator.onLine
  };
}

/**
 * Verificar si esta PC tiene impresora
 */
export function hasPrinter() {
  return systemConfig.pcId === 'PC2_WINDOWS7';
}

/**
 * Verificar si esta PC es el servidor principal
 */
export function isMainServer() {
  return systemConfig.pcId === 'PC1_ANTIX';
}

// Log de inicialización
console.log('🔥 Firebase configurado para:', systemConfig.pcId);
console.log('🖨️ Tiene impresora:', hasPrinter());
console.log('🖥️ Es servidor principal:', isMainServer());