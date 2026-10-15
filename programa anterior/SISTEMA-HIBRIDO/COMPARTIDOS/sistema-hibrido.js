/**
 * 🔄 SISTEMA HÍBRIDO COMPLETO
 * Sistema de Estacionamiento Los Ríos
 * 
 * Integra Firebase, sincronización, detección de PC y impresión
 */

// Importar módulos
import { systemConfig, getSystemInfo } from './firebase-config.js';
import syncManager from './firebase-sync.js';
import pcDetector from './pc-detector.js';
import printingManager from './printing-manager.js';

class SistemaHibrido {
  constructor() {
    this.isInitialized = false;
    this.systemInfo = getSystemInfo();
    this.status = {
      online: navigator.onLine,
      active: false,
      syncing: false,
      printing: false
    };
    
    // Inicializar
    this.init();
  }
  
  /**
   * Inicializar el sistema híbrido
   */
  async init() {
    console.log('🚀 Iniciando sistema híbrido...');
    console.log('📊 Información del sistema:', this.systemInfo);
    
    try {
      // Inicializar componentes
      await this.initializeComponents();
      
      // Configurar eventos
      this.setupEventListeners();
      
      // Iniciar monitoreo
      this.startMonitoring();
      
      this.isInitialized = true;
      console.log('✅ Sistema híbrido iniciado correctamente');
      
      // Notificar inicialización
      this.notifyInitialization();
      
    } catch (error) {
      console.error('❌ Error inicializando sistema híbrido:', error);
      this.handleInitializationError(error);
    }
  }
  
  /**
   * Inicializar componentes
   */
  async initializeComponents() {
    console.log('🔧 Inicializando componentes...');
    
    // Los componentes se inicializan automáticamente
    // Solo verificamos que estén disponibles
    
    if (!window.syncManager) {
      throw new Error('SyncManager no disponible');
    }
    
    if (!window.pcDetector) {
      throw new Error('PCDetector no disponible');
    }
    
    if (!window.printingManager) {
      throw new Error('PrintingManager no disponible');
    }
    
    console.log('✅ Componentes inicializados');
  }
  
  /**
   * Configurar eventos
   */
  setupEventListeners() {
    console.log('📡 Configurando eventos...');
    
    // Evento de cambio de PC
    window.addEventListener('pcStatusChange', (event) => {
      this.handlePCStatusChange(event.detail);
    });
    
    // Evento de cambio de conectividad
    window.addEventListener('online', () => {
      this.handleOnline();
    });
    
    window.addEventListener('offline', () => {
      this.handleOffline();
    });
    
    // Evento de sincronización
    window.addEventListener('syncStatusChange', (event) => {
      this.handleSyncStatusChange(event.detail);
    });
    
    console.log('✅ Eventos configurados');
  }
  
  /**
   * Iniciar monitoreo del sistema
   */
  startMonitoring() {
    console.log('📊 Iniciando monitoreo...');
    
    // Monitoreo cada 30 segundos
    setInterval(() => {
      this.updateStatus();
    }, 30000);
    
    console.log('✅ Monitoreo iniciado');
  }
  
  /**
   * Manejar cambio de estado de PC
   */
  handlePCStatusChange(detail) {
    console.log('🔄 Cambio de estado de PC:', detail);
    
    this.status.active = detail.isActive;
    
    // Actualizar UI
    this.updateUI();
    
    // Notificar cambio
    this.notifyStatusChange('pc', detail);
  }
  
  /**
   * Manejar conexión online
   */
  handleOnline() {
    console.log('🌐 Sistema online');
    this.status.online = true;
    this.updateUI();
    this.notifyStatusChange('connectivity', { online: true });
  }
  
  /**
   * Manejar conexión offline
   */
  handleOffline() {
    console.log('📴 Sistema offline');
    this.status.online = false;
    this.updateUI();
    this.notifyStatusChange('connectivity', { online: false });
  }
  
  /**
   * Manejar cambio de estado de sincronización
   */
  handleSyncStatusChange(detail) {
    console.log('🔄 Cambio de estado de sincronización:', detail);
    this.status.syncing = detail.isSyncing;
    this.updateUI();
  }
  
  /**
   * Actualizar estado del sistema
   */
  updateStatus() {
    const syncStatus = window.syncManager.getSyncStatus();
    const pcStatus = window.pcDetector.getStatus();
    const printStatus = window.printingManager.getStatus();
    
    this.status = {
      online: navigator.onLine,
      active: pcStatus.isActive,
      syncing: syncStatus.isSyncing,
      printing: printStatus.isPrinting
    };
    
    // Log de estado
    console.log('📊 Estado del sistema:', this.status);
  }
  
  /**
   * Actualizar UI
   */
  updateUI() {
    // Actualizar indicadores de estado
    this.updateStatusIndicators();
    
    // Actualizar botones y controles
    this.updateControls();
    
    // Actualizar notificaciones
    this.updateNotifications();
  }
  
  /**
   * Actualizar indicadores de estado
   */
  updateStatusIndicators() {
    // Indicador de conectividad
    const connectivityIndicator = document.getElementById('connectivity-status');
    if (connectivityIndicator) {
      connectivityIndicator.className = this.status.online ? 'status-online' : 'status-offline';
      connectivityIndicator.textContent = this.status.online ? '🌐 Online' : '📴 Offline';
    }
    
    // Indicador de PC activa
    const activeIndicator = document.getElementById('active-status');
    if (activeIndicator) {
      activeIndicator.className = this.status.active ? 'status-active' : 'status-inactive';
      activeIndicator.textContent = this.status.active ? '✅ Activa' : '⏸️ Inactiva';
    }
    
    // Indicador de sincronización
    const syncIndicator = document.getElementById('sync-status');
    if (syncIndicator) {
      syncIndicator.className = this.status.syncing ? 'status-syncing' : 'status-synced';
      syncIndicator.textContent = this.status.syncing ? '🔄 Sincronizando...' : '✅ Sincronizado';
    }
    
    // Indicador de impresión
    const printIndicator = document.getElementById('print-status');
    if (printIndicator) {
      printIndicator.className = this.status.printing ? 'status-printing' : 'status-ready';
      printIndicator.textContent = this.status.printing ? '🖨️ Imprimiendo...' : '🖨️ Listo';
    }
  }
  
  /**
   * Actualizar controles
   */
  updateControls() {
    // Habilitar/deshabilitar botones según el estado
    const buttons = document.querySelectorAll('[data-requires-active]');
    buttons.forEach(button => {
      button.disabled = !this.status.active;
    });
    
    const syncButtons = document.querySelectorAll('[data-requires-sync]');
    syncButtons.forEach(button => {
      button.disabled = !this.status.online;
    });
  }
  
  /**
   * Actualizar notificaciones
   */
  updateNotifications() {
    // Mostrar notificaciones según el estado
    if (!this.status.online) {
      this.showNotification('Sistema offline - Modo local activado', 'warning');
    }
    
    if (this.status.active) {
      this.showNotification('Esta PC es ahora la activa', 'success');
    }
  }
  
  /**
   * Mostrar notificación
   */
  showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Remover después de 5 segundos
    setTimeout(() => {
      notification.remove();
    }, 5000);
  }
  
  /**
   * Notificar inicialización
   */
  notifyInitialization() {
    const event = new CustomEvent('sistemaHibridoReady', {
      detail: {
        systemInfo: this.systemInfo,
        status: this.status,
        timestamp: new Date()
      }
    });
    
    window.dispatchEvent(event);
  }
  
  /**
   * Notificar cambio de estado
   */
  notifyStatusChange(type, data) {
    const event = new CustomEvent('sistemaHibridoStatusChange', {
      detail: {
        type,
        data,
        status: this.status,
        timestamp: new Date()
      }
    });
    
    window.dispatchEvent(event);
  }
  
  /**
   * Manejar error de inicialización
   */
  handleInitializationError(error) {
    console.error('❌ Error crítico en inicialización:', error);
    
    // Mostrar error al usuario
    this.showNotification('Error crítico en el sistema', 'error');
    
    // Intentar recuperación
    setTimeout(() => {
      this.attemptRecovery();
    }, 5000);
  }
  
  /**
   * Intentar recuperación
   */
  attemptRecovery() {
    console.log('🔄 Intentando recuperación...');
    
    // Reinicializar componentes
    this.init();
  }
  
  /**
   * Obtener estado completo del sistema
   */
  getSystemStatus() {
    return {
      initialized: this.isInitialized,
      systemInfo: this.systemInfo,
      status: this.status,
      syncStatus: window.syncManager?.getSyncStatus(),
      pcStatus: window.pcDetector?.getStatus(),
      printStatus: window.printingManager?.getStatus(),
      timestamp: new Date()
    };
  }
  
  /**
   * Forzar sincronización
   */
  async forceSync() {
    if (!this.status.online) {
      throw new Error('No hay conexión a internet');
    }
    
    console.log('🔄 Forzando sincronización...');
    await window.syncManager.syncData();
  }
  
  /**
   * Forzar esta PC como activa
   */
  async forceActive() {
    console.log('🔧 Forzando esta PC como activa...');
    await window.pcDetector.forceActive();
  }
  
  /**
   * Obtener información de diagnóstico
   */
  getDiagnosticInfo() {
    return {
      systemInfo: this.systemInfo,
      status: this.status,
      components: {
        syncManager: !!window.syncManager,
        pcDetector: !!window.pcDetector,
        printingManager: !!window.printingManager
      },
      environment: {
        userAgent: navigator.userAgent,
        platform: navigator.platform,
        online: navigator.onLine,
        timestamp: new Date()
      }
    };
  }
}

// Crear instancia global
const sistemaHibrido = new SistemaHibrido();

// Exportar para uso global
window.sistemaHibrido = sistemaHibrido;
export default sistemaHibrido;
