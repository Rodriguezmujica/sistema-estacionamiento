/**
 * 📊 FIREBASE SYNC MONITOR
 * Sistema de Estacionamiento Los Ríos
 * 
 * Monitorea la sincronización entre Firebase y Antix
 */

class FirebaseSyncMonitor {
  constructor() {
    this.logs = [];
    this.syncStatus = {
      firebase: 'disconnected',
      antix: 'disconnected',
      lastSync: null,
      pendingOperations: 0,
      failedOperations: 0
    };
    
    this.init();
  }

  init() {
    console.log('📊 Iniciando Firebase Sync Monitor');
    this.loadLogs();
    this.startMonitoring();
    this.setupEventListeners();
  }

  /**
   * Registrar evento de sincronización
   */
  logSyncEvent(type, data) {
    const event = {
      id: Date.now() + Math.random(),
      timestamp: new Date().toISOString(),
      type: type, // 'firebase_sent', 'antix_received', 'antix_processed', 'error'
      data: data,
      pcId: this.getPCId()
    };

    this.logs.unshift(event); // Agregar al inicio
    this.logs = this.logs.slice(0, 100); // Mantener solo los últimos 100

    // Guardar en localStorage
    this.saveLogs();

    // Actualizar UI
    this.updateSyncStatus(type, data);
    this.updateLogDisplay();

    console.log(`📊 Sync Event [${type}]:`, event);
  }

  /**
   * Registrar envío a Firebase
   */
  logFirebaseSent(operation) {
    this.logSyncEvent('firebase_sent', {
      operation: operation,
      collection: operation.collection,
      docId: operation.docId,
      status: 'sent_to_firebase'
    });
  }

  /**
   * Registrar recepción en Antix
   */
  logAntixReceived(operation) {
    this.logSyncEvent('antix_received', {
      operation: operation,
      collection: operation.collection,
      docId: operation.docId,
      status: 'received_by_antix'
    });
  }

  /**
   * Registrar procesamiento en Antix
   */
  logAntixProcessed(operation, result) {
    this.logSyncEvent('antix_processed', {
      operation: operation,
      result: result,
      status: 'processed_by_antix'
    });
  }

  /**
   * Registrar error de sincronización
   */
  logSyncError(operation, error) {
    this.logSyncEvent('error', {
      operation: operation,
      error: error.message || error,
      status: 'sync_failed'
    });
  }

  /**
   * Verificar estado de sincronización
   */
  async checkSyncStatus() {
    try {
      // Verificar conexión a Firebase
      const firebaseStatus = await this.checkFirebaseConnection();
      
      // Verificar estado de Antix
      const antixStatus = await this.checkAntixStatus();
      
      // Actualizar estado
      this.syncStatus.firebase = firebaseStatus ? 'connected' : 'disconnected';
      this.syncStatus.antix = antixStatus ? 'connected' : 'disconnected';
      this.syncStatus.lastSync = new Date().toISOString();
      
      // Actualizar UI
      this.updateStatusDisplay();
      
    } catch (error) {
      console.error('Error verificando estado de sincronización:', error);
      this.logSyncError({ type: 'status_check' }, error);
    }
  }

  /**
   * Verificar conexión a Firebase
   */
  async checkFirebaseConnection() {
    try {
      // Simular verificación de Firebase
      // En implementación real, harías una consulta simple
      return navigator.onLine;
    } catch (error) {
      return false;
    }
  }

  /**
   * Verificar estado de Antix
   */
  async checkAntixStatus() {
    try {
      // Verificar si estamos en Antix o si Antix está disponible
      const response = await fetch('api/check-antix-status.php', {
        method: 'GET',
        timeout: 5000
      });
      
      if (response.ok) {
        const data = await response.json();
        return data.status === 'online';
      }
      
      return false;
    } catch (error) {
      // Si no hay respuesta, asumir que Antix está offline
      return false;
    }
  }

  /**
   * Obtener estadísticas de sincronización
   */
  getSyncStats() {
    const now = new Date();
    const last24h = new Date(now.getTime() - 24 * 60 * 60 * 1000);
    
    const recentLogs = this.logs.filter(log => 
      new Date(log.timestamp) > last24h
    );

    const stats = {
      total: recentLogs.length,
      firebase_sent: recentLogs.filter(log => log.type === 'firebase_sent').length,
      antix_received: recentLogs.filter(log => log.type === 'antix_received').length,
      antix_processed: recentLogs.filter(log => log.type === 'antix_processed').length,
      errors: recentLogs.filter(log => log.type === 'error').length,
      success_rate: 0
    };

    if (stats.total > 0) {
      stats.success_rate = ((stats.antix_processed / stats.firebase_sent) * 100).toFixed(1);
    }

    return stats;
  }

  /**
   * Actualizar estado de sincronización
   */
  updateSyncStatus(type, data) {
    switch (type) {
      case 'firebase_sent':
        this.syncStatus.pendingOperations++;
        break;
      case 'antix_received':
        // Mantener pendingOperations
        break;
      case 'antix_processed':
        this.syncStatus.pendingOperations = Math.max(0, this.syncStatus.pendingOperations - 1);
        break;
      case 'error':
        this.syncStatus.failedOperations++;
        break;
    }
  }

  /**
   * Actualizar display de estado
   */
  updateStatusDisplay() {
    const firebaseStatus = document.getElementById('firebase-sync-status');
    const antixStatus = document.getElementById('antix-sync-status');
    const lastSync = document.getElementById('last-sync-time');
    const pendingOps = document.getElementById('pending-operations');

    if (firebaseStatus) {
      firebaseStatus.textContent = this.syncStatus.firebase === 'connected' ? 'Conectado' : 'Desconectado';
      firebaseStatus.className = `badge ${this.syncStatus.firebase === 'connected' ? 'bg-success' : 'bg-danger'}`;
    }

    if (antixStatus) {
      antixStatus.textContent = this.syncStatus.antix === 'connected' ? 'Conectado' : 'Desconectado';
      antixStatus.className = `badge ${this.syncStatus.antix === 'connected' ? 'bg-success' : 'bg-danger'}`;
    }

    if (lastSync) {
      lastSync.textContent = this.syncStatus.lastSync ? 
        new Date(this.syncStatus.lastSync).toLocaleString() : 'Nunca';
    }

    if (pendingOps) {
      pendingOps.textContent = this.syncStatus.pendingOperations;
    }
  }

  /**
   * Actualizar display de logs
   */
  updateLogDisplay() {
    const logContainer = document.getElementById('sync-logs');
    if (!logContainer) return;

    const recentLogs = this.logs.slice(0, 20); // Mostrar últimos 20
    
    logContainer.innerHTML = recentLogs.map(log => {
      const icon = this.getLogIcon(log.type);
      const color = this.getLogColor(log.type);
      const time = new Date(log.timestamp).toLocaleTimeString();
      
      return `
        <div class="log-entry ${color}">
          <i class="${icon} me-2"></i>
          <span class="log-time">${time}</span>
          <span class="log-type">${log.type}</span>
          <span class="log-data">${JSON.stringify(log.data)}</span>
        </div>
      `;
    }).join('');
  }

  /**
   * Obtener icono para tipo de log
   */
  getLogIcon(type) {
    const icons = {
      'firebase_sent': 'fas fa-upload',
      'antix_received': 'fas fa-download',
      'antix_processed': 'fas fa-check',
      'error': 'fas fa-exclamation-triangle'
    };
    return icons[type] || 'fas fa-info';
  }

  /**
   * Obtener color para tipo de log
   */
  getLogColor(type) {
    const colors = {
      'firebase_sent': 'text-primary',
      'antix_received': 'text-info',
      'antix_processed': 'text-success',
      'error': 'text-danger'
    };
    return colors[type] || 'text-muted';
  }

  /**
   * Iniciar monitoreo automático
   */
  startMonitoring() {
    // Verificar estado cada 30 segundos
    setInterval(() => {
      this.checkSyncStatus();
    }, 30000);

    // Verificar estado inmediatamente
    setTimeout(() => {
      this.checkSyncStatus();
    }, 2000);
  }

  /**
   * Configurar event listeners
   */
  setupEventListeners() {
    // Escuchar cambios de conectividad
    window.addEventListener('online', () => {
      this.logSyncEvent('firebase_sent', { status: 'connection_restored' });
      this.checkSyncStatus();
    });

    window.addEventListener('offline', () => {
      this.logSyncEvent('error', { status: 'connection_lost' });
    });

    // Escuchar eventos de sincronización personalizados
    window.addEventListener('firebase-sync-sent', (event) => {
      this.logFirebaseSent(event.detail);
    });

    window.addEventListener('antix-sync-received', (event) => {
      this.logAntixReceived(event.detail);
    });

    window.addEventListener('antix-sync-processed', (event) => {
      this.logAntixProcessed(event.detail.operation, event.detail.result);
    });
  }

  /**
   * Guardar logs en localStorage
   */
  saveLogs() {
    try {
      localStorage.setItem('firebase_sync_logs', JSON.stringify(this.logs));
    } catch (error) {
      console.warn('Error guardando logs:', error);
    }
  }

  /**
   * Cargar logs desde localStorage
   */
  loadLogs() {
    try {
      const saved = localStorage.getItem('firebase_sync_logs');
      if (saved) {
        this.logs = JSON.parse(saved);
      }
    } catch (error) {
      console.warn('Error cargando logs:', error);
    }
  }

  /**
   * Obtener ID de PC
   */
  getPCId() {
    return window.systemConfig?.pcId || 'unknown';
  }

  /**
   * Limpiar logs antiguos
   */
  clearOldLogs() {
    const cutoff = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000); // 7 días
    this.logs = this.logs.filter(log => new Date(log.timestamp) > cutoff);
    this.saveLogs();
  }

  /**
   * Exportar logs para análisis
   */
  exportLogs() {
    const data = {
      exportDate: new Date().toISOString(),
      logs: this.logs,
      stats: this.getSyncStats(),
      syncStatus: this.syncStatus
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `firebase-sync-logs-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
  }
}

// Crear instancia global
window.FirebaseSyncMonitor = new FirebaseSyncMonitor();
