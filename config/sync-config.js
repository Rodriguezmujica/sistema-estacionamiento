/**
 * ⚙️ CONFIGURACIÓN DE SINCRONIZACIÓN
 * Sistema de Estacionamiento Los Ríos
 * 
 * Configuración específica para tiempos de sincronización
 */

export const syncConfig = {
  // Configuración de intervalos
  intervals: {
    // Sincronización automática cada X segundos
    autoSync: 5000, // 5 segundos
    
    // Verificación de conectividad
    connectivityCheck: 10000, // 10 segundos
    
    // Heartbeat entre PC
    heartbeat: 30000, // 30 segundos
    
    // Limpieza de logs
    logCleanup: 300000 // 5 minutos
  },
  
  // Configuración de reintentos
  retry: {
    // Número máximo de intentos
    maxAttempts: 3,
    
    // Delay entre intentos (ms)
    delay: 2000, // 2 segundos
    
    // Delay exponencial (se duplica cada intento)
    exponential: true
  },
  
  // Configuración de timeouts
  timeout: {
    // Timeout para operaciones de Firebase
    firebase: 10000, // 10 segundos
    
    // Timeout para operaciones de MySQL
    mysql: 5000, // 5 segundos
    
    // Timeout para operaciones de impresión
    printing: 15000 // 15 segundos
  },
  
  // Configuración de cola offline
  offline: {
    // Tamaño máximo de cola offline
    maxQueueSize: 1000,
    
    // Tiempo máximo de retención (ms)
    maxRetentionTime: 86400000, // 24 horas
    
    // Intervalo de procesamiento de cola
    processInterval: 10000 // 10 segundos
  },
  
  // Configuración de notificaciones
  notifications: {
    // Mostrar notificación de sincronización exitosa
    showSuccess: true,
    
    // Mostrar notificación de error
    showError: true,
    
    // Mostrar notificación de modo offline
    showOffline: true,
    
    // Duración de notificaciones (ms)
    duration: 5000 // 5 segundos
  },
  
  // Configuración de logging
  logging: {
    // Nivel de log (debug, info, warn, error)
    level: 'info',
    
    // Log en consola
    console: true,
    
    // Log en Firebase
    firebase: true,
    
    // Log local
    local: true,
    
    // Rotación de logs (días)
    rotationDays: 7
  },
  
  // Configuración específica por PC
  pc: {
    PC1_ANTIX: {
      // PC1 es el servidor principal - prioridad alta
      priority: 1,
      isMainServer: true,
      hasPrinter: false,
      syncEnabled: true,
      // Sincronización más frecuente en servidor principal
      autoSyncInterval: 3000 // 3 segundos
    },
    PC2_WINDOWS7: {
      // PC2 es la PC de producción - prioridad baja
      priority: 2,
      isMainServer: false,
      hasPrinter: true,
      syncEnabled: true,
      // Sincronización menos frecuente en PC de producción
      autoSyncInterval: 5000 // 5 segundos
    }
  },
  
  // Configuración de datos a sincronizar
  dataTypes: {
    tickets: {
      enabled: true,
      collection: 'tickets',
      syncInterval: 5000, // 5 segundos
      batchSize: 50
    },
    servicios: {
      enabled: true,
      collection: 'servicios_lavado',
      syncInterval: 5000, // 5 segundos
      batchSize: 50
    },
    usuarios: {
      enabled: true,
      collection: 'usuarios',
      syncInterval: 10000, // 10 segundos
      batchSize: 20
    },
    configuracion: {
      enabled: true,
      collection: 'configuracion',
      syncInterval: 30000, // 30 segundos
      batchSize: 10
    }
  }
};

// Función para obtener configuración específica de la PC actual
export function getPCSyncConfig() {
  const pcType = window.location.hostname.includes('antix') ? 'PC1_ANTIX' : 'PC2_WINDOWS7';
  return {
    ...syncConfig,
    pc: syncConfig.pc[pcType],
    intervals: {
      ...syncConfig.intervals,
      autoSync: syncConfig.pc[pcType].autoSyncInterval
    }
  };
}

// Función para calcular delay de reintento
export function calculateRetryDelay(attempt) {
  const baseDelay = syncConfig.retry.delay;
  if (syncConfig.retry.exponential) {
    return baseDelay * Math.pow(2, attempt - 1);
  }
  return baseDelay;
}

// Función para verificar si es tiempo de sincronizar
export function shouldSync(dataType, lastSync) {
  const config = syncConfig.dataTypes[dataType];
  if (!config || !config.enabled) return false;
  
  const now = Date.now();
  const timeSinceLastSync = now - lastSync;
  
  return timeSinceLastSync >= config.syncInterval;
}

// Función para obtener configuración de timeout
export function getTimeout(operation) {
  return syncConfig.timeout[operation] || 5000;
}

export default syncConfig;
