/**
 * ⚙️ CONFIGURACIÓN DEL SISTEMA HÍBRIDO
 * Sistema de Estacionamiento Los Ríos
 * 
 * Configuración específica para el sistema híbrido con credenciales reales
 */

// Configuración de Firebase (CREDENCIALES REALES)
export const firebaseConfig = {
  apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
  authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
  projectId: "sistemaestacionamiento-46735",
  storageBucket: "sistemaestacionamiento-46735.firebasestorage.app",
  messagingSenderId: "570161231939",
  appId: "1:570161231939:web:50a5f88fcd65e98fa03cf6",
  measurementId: "G-7E9CZ89DR4"
};

// Configuración del sistema híbrido
export const sistemaConfig = {
  // Identificación de PC
  pc: {
    // Detectar automáticamente el tipo de PC
    detect: () => {
      const userAgent = navigator.userAgent;
      const hostname = window.location.hostname;
      
      // Detectar Windows 7 (PC 2)
      if (userAgent.includes('Windows NT 6.1')) {
        return 'PC2_WINDOWS7';
      }
      
      // Detectar Linux (PC 1 - Antix)
      if (userAgent.includes('Linux')) {
        return 'PC1_ANTIX';
      }
      
      // Detectar por hostname
      if (hostname.includes('antix') || hostname.includes('192.168.1.10')) {
        return 'PC1_ANTIX';
      }
      
      if (hostname.includes('windows7') || hostname.includes('192.168.1.11')) {
        return 'PC2_WINDOWS7';
      }
      
      // Por defecto, asumir PC 1
      return 'PC1_ANTIX';
    },
    
    // Configuración específica por PC
    config: {
      PC1_ANTIX: {
        name: 'Servidor Principal (Antix)',
        hasPrinter: false,
        isMainServer: true,
        priority: 1,
        description: 'Servidor principal con base de datos MySQL'
      },
      PC2_WINDOWS7: {
        name: 'PC de Producción (Windows 7)',
        hasPrinter: true,
        isMainServer: false,
        priority: 2,
        description: 'PC de producción con impresora USB'
      }
    }
  },
  
  // Configuración de sincronización
  sync: {
    enabled: true,
    interval: 5000, // 5 segundos
    retryAttempts: 3,
    retryDelay: 2000, // 2 segundos
    batchSize: 10, // Procesar 10 elementos por lote
    timeout: 30000 // 30 segundos
  },
  
  // Configuración de conectividad
  connectivity: {
    checkInterval: 10000, // 10 segundos
    timeout: 5000, // 5 segundos
    offlineThreshold: 30000, // 30 segundos
    retryInterval: 5000 // 5 segundos
  },
  
  // Configuración de impresión
  printing: {
    enabled: true,
    pc2Only: true,
    paperWidth: 80, // caracteres
    paperHeight: 1000, // líneas
    timeout: 10000, // 10 segundos
    retryAttempts: 3,
    retryDelay: 2000 // 2 segundos
  },
  
  // Configuración de Firebase
  firebase: {
    collections: {
      tickets: 'tickets',
      servicios_lavado: 'servicios_lavado',
      usuarios: 'usuarios',
      pc_status: 'pc_status',
      sync_queue: 'sync_queue',
      logs: 'logs'
    },
    rules: {
      // Reglas de seguridad para Firestore
      firestore: {
        version: '2',
        rules: `
          rules_version = '2';
          service cloud.firestore {
            match /databases/{database}/documents {
              match /{document=**} {
                allow read, write: if request.auth != null;
              }
            }
          }
        `
      },
      // Reglas de seguridad para Storage
      storage: {
        version: '2',
        rules: `
          rules_version = '2';
          service firebase.storage {
            match /b/{bucket}/o {
              match /{allPaths=**} {
                allow read, write: if request.auth != null;
              }
            }
          }
        `
      }
    }
  },
  
  // Configuración de APIs
  api: {
    baseUrl: window.location.origin,
    endpoints: {
      tickets: '/api/get-tickets.php',
      servicios: '/api/get-servicios-lavado.php',
      usuarios: '/api/get-usuarios.php',
      print: '/api/print.php',
      checkPrinter: '/api/check-printer.php'
    }
  },
  
  // Configuración de logging
  logging: {
    enabled: true,
    level: 'info', // debug, info, warn, error
    console: true,
    firebase: true,
    local: true
  },
  
  // Configuración de notificaciones
  notifications: {
    enabled: true,
    duration: 5000, // 5 segundos
    position: 'top-right',
    types: {
      success: { color: '#28a745', icon: '✅' },
      warning: { color: '#ffc107', icon: '⚠️' },
      error: { color: '#dc3545', icon: '❌' },
      info: { color: '#17a2b8', icon: 'ℹ️' }
    }
  },
  
  // Configuración de desarrollo
  development: {
    enabled: window.location.hostname === 'localhost' || window.location.hostname.includes('192.168'),
    debug: true,
    mockData: false,
    testMode: false
  }
};

// Función para obtener configuración específica de la PC actual
export function getPCConfig() {
  const pcType = sistemaConfig.pc.detect();
  return {
    type: pcType,
    ...sistemaConfig.pc.config[pcType]
  };
}

// Función para verificar si la configuración es válida
export function validateConfig() {
  const errors = [];
  
  // Verificar configuración de Firebase
  if (!firebaseConfig.apiKey || firebaseConfig.apiKey === 'TU_API_KEY_AQUI') {
    errors.push('API Key de Firebase no configurada');
  }
  
  if (!firebaseConfig.projectId || firebaseConfig.projectId === 'tu-proyecto-id') {
    errors.push('Project ID de Firebase no configurado');
  }
  
  // Verificar configuración de PC
  const pcConfig = getPCConfig();
  if (!pcConfig.type) {
    errors.push('No se pudo detectar el tipo de PC');
  }
  
  return {
    valid: errors.length === 0,
    errors
  };
}

// Función para obtener información del sistema
export function getSystemInfo() {
  const pcConfig = getPCConfig();
  const configValidation = validateConfig();
  
  return {
    pc: pcConfig,
    firebase: {
      projectId: firebaseConfig.projectId,
      authDomain: firebaseConfig.authDomain,
      storageBucket: firebaseConfig.storageBucket
    },
    config: {
      valid: configValidation.valid,
      errors: configValidation.errors
    },
    environment: {
      userAgent: navigator.userAgent,
      platform: navigator.platform,
      hostname: window.location.hostname,
      online: navigator.onLine,
      timestamp: new Date().toISOString()
    }
  };
}

// Función para log con configuración
export function log(level, message, data = null) {
  if (!sistemaConfig.logging.enabled) return;
  
  const timestamp = new Date().toISOString();
  const pcConfig = getPCConfig();
  const logMessage = `[${timestamp}] [${pcConfig.type}] [${level.toUpperCase()}] ${message}`;
  
  // Log en consola
  if (sistemaConfig.logging.console) {
    switch (level) {
      case 'debug':
        console.debug(logMessage, data);
        break;
      case 'info':
        console.info(logMessage, data);
        break;
      case 'warn':
        console.warn(logMessage, data);
        break;
      case 'error':
        console.error(logMessage, data);
        break;
      default:
        console.log(logMessage, data);
    }
  }
  
  // Log en Firebase (si está configurado)
  if (sistemaConfig.logging.firebase && window.db) {
    // Implementar logging en Firebase
    // TODO: Implementar logging en Firestore
  }
  
  // Log local (si está habilitado)
  if (sistemaConfig.logging.local) {
    // Implementar logging local
    // TODO: Implementar almacenamiento local de logs
  }
}

// Exportar configuración por defecto
export default {
  firebaseConfig,
  sistemaConfig,
  getPCConfig,
  validateConfig,
  getSystemInfo,
  log
};
