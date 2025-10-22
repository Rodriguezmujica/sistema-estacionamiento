/**
 * 🔄 INTEGRACIÓN COMPLETA TUU + FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Integra completamente el sistema TUU existente con Firebase
 * Para usar en index.php y otros archivos del sistema
 */

// Importar módulos
import { firebaseConfig, sistemaConfig, getPCConfig, validateConfig, getSystemInfo, log } from './config-sistema-hibrido.js';
import { initializeApp } from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js';
import { getAuth } from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js';
import { getFirestore, collection, addDoc, getDocs, doc, setDoc, onSnapshot, query, where, orderBy, limit } from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js';
import tuuFirebaseSync from './tuu-firebase-sync.js';
import tuuFirebaseIntegration from './tuu-firebase-integration.js';
import tuuPaymentInterceptor from './tuu-payment-interceptor.js';

class TUUFirebaseIntegrationComplete {
  constructor() {
    this.isInitialized = false;
    this.app = null;
    this.auth = null;
    this.db = null;
    this.systemInfo = getSystemInfo();
    
    this.init();
  }
  
  /**
   * Inicializar integración completa
   */
  async init() {
    try {
      log('info', 'Iniciando integración completa TUU + Firebase...');
      
      // Inicializar Firebase
      await this.initializeFirebase();
      
      // Inicializar componentes
      await this.initializeComponents();
      
      // Configurar eventos
      this.setupEventListeners();
      
      this.isInitialized = true;
      log('success', 'Integración completa TUU + Firebase iniciada correctamente');
      
      // Notificar inicialización
      this.notifyInitialization();
      
    } catch (error) {
      log('error', 'Error inicializando integración completa:', error);
    }
  }
  
  /**
   * Inicializar Firebase
   */
  async initializeFirebase() {
    try {
      this.app = initializeApp(firebaseConfig);
      this.auth = getAuth(this.app);
      this.db = getFirestore(this.app);
      
      log('success', 'Firebase inicializado correctamente');
      
    } catch (error) {
      log('error', 'Error inicializando Firebase:', error);
      throw error;
    }
  }
  
  /**
   * Inicializar componentes
   */
  async initializeComponents() {
    try {
      // Los componentes se inicializan automáticamente
      // Solo verificamos que estén disponibles
      
      if (!window.tuuFirebaseSync) {
        throw new Error('TUU Firebase Sync no disponible');
      }
      
      if (!window.tuuFirebaseIntegration) {
        throw new Error('TUU Firebase Integration no disponible');
      }
      
      if (!window.tuuPaymentInterceptor) {
        throw new Error('TUU Payment Interceptor no disponible');
      }
      
      log('success', 'Componentes TUU + Firebase inicializados');
      
    } catch (error) {
      log('error', 'Error inicializando componentes:', error);
      throw error;
    }
  }
  
  /**
   * Configurar event listeners
   */
  setupEventListeners() {
    // Escuchar cambios en el estado de sincronización
    window.addEventListener('tuuPaymentStatusChange', (event) => {
      this.handlePaymentStatusChange(event.detail);
    });
    
    // Escuchar eventos de pago completado
    window.addEventListener('tuuPaymentCompleted', (event) => {
      this.handlePaymentCompleted(event.detail);
    });
    
    // Escuchar cambios en la conexión
    window.addEventListener('online', () => {
      this.handleConnectionChange(true);
    });
    
    window.addEventListener('offline', () => {
      this.handleConnectionChange(false);
    });
  }
  
  /**
   * Manejar cambio de estado de pago
   */
  handlePaymentStatusChange(detail) {
    log('info', 'Estado de pago TUU cambiado:', detail);
    
    // Actualizar UI si es necesario
    this.updatePaymentUI(detail);
  }
  
  /**
   * Manejar pago completado
   */
  handlePaymentCompleted(detail) {
    log('success', 'Pago TUU completado:', detail);
    
    // Mostrar notificación
    this.showPaymentNotification(detail);
    
    // Actualizar UI
    this.updatePaymentUI(detail);
  }
  
  /**
   * Manejar cambio de conexión
   */
  handleConnectionChange(isOnline) {
    if (isOnline) {
      log('info', 'Conexión restaurada, sincronizando...');
      this.syncPendingPayments();
    } else {
      log('warning', 'Conexión perdida, modo offline activado');
    }
  }
  
  /**
   * Sincronizar pagos pendientes
   */
  async syncPendingPayments() {
    try {
      if (window.tuuFirebaseSync) {
        await window.tuuFirebaseSync.processPendingPayments();
        log('success', 'Pagos pendientes sincronizados');
      }
    } catch (error) {
      log('error', 'Error sincronizando pagos pendientes:', error);
    }
  }
  
  /**
   * Actualizar UI de pago
   */
  updatePaymentUI(paymentData) {
    // Implementar actualización de UI según sea necesario
    log('info', 'Actualizando UI de pago:', paymentData);
    
    // Buscar elementos de UI relacionados con el pago
    const paymentElements = document.querySelectorAll('[data-transaction-id="' + paymentData.transaction_id + '"]');
    
    paymentElements.forEach(element => {
      if (paymentData.status === 'completed') {
        element.classList.add('payment-completed');
        element.classList.remove('payment-pending', 'payment-verifying');
      } else if (paymentData.status === 'verifying') {
        element.classList.add('payment-verifying');
        element.classList.remove('payment-pending', 'payment-completed');
      } else {
        element.classList.add('payment-pending');
        element.classList.remove('payment-verifying', 'payment-completed');
      }
    });
  }
  
  /**
   * Mostrar notificación de pago
   */
  showPaymentNotification(paymentData) {
    // Usar función de notificación existente si está disponible
    if (typeof showNotification === 'function') {
      showNotification(`Pago TUU confirmado: ${paymentData.patente} - $${paymentData.precio}`, 'success');
    } else if (typeof toastr !== 'undefined') {
      toastr.success(`Pago TUU confirmado: ${paymentData.patente} - $${paymentData.precio}`);
    } else {
      // Fallback a alert
      alert(`Pago TUU confirmado: ${paymentData.patente} - $${paymentData.precio}`);
    }
  }
  
  /**
   * Notificar inicialización
   */
  notifyInitialization() {
    // Disparar evento personalizado
    const event = new CustomEvent('tuuFirebaseIntegrationReady', {
      detail: {
        systemInfo: this.systemInfo,
        timestamp: new Date()
      }
    });
    window.dispatchEvent(event);
  }
  
  /**
   * Obtener estado de la integración
   */
  getIntegrationStatus() {
    return {
      initialized: this.isInitialized,
      systemInfo: this.systemInfo,
      tuuSyncStatus: window.tuuFirebaseSync?.getSyncStatus(),
      tuuIntegrationStatus: window.tuuFirebaseIntegration?.getIntegrationStatus(),
      tuuInterceptorStatus: window.tuuPaymentInterceptor?.getInterceptorStatus(),
      timestamp: new Date()
    };
  }
  
  /**
   * Forzar sincronización
   */
  async forceSync() {
    try {
      log('info', 'Forzando sincronización...');
      
      if (window.tuuFirebaseSync) {
        await window.tuuFirebaseSync.processPendingPayments();
      }
      
      log('success', 'Sincronización forzada completada');
      
    } catch (error) {
      log('error', 'Error en sincronización forzada:', error);
    }
  }
  
  /**
   * Obtener información de diagnóstico
   */
  getDiagnosticInfo() {
    return {
      initialized: this.isInitialized,
      systemInfo: this.systemInfo,
      components: {
        tuuFirebaseSync: !!window.tuuFirebaseSync,
        tuuFirebaseIntegration: !!window.tuuFirebaseIntegration,
        tuuPaymentInterceptor: !!window.tuuPaymentInterceptor
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
const tuuFirebaseIntegrationComplete = new TUUFirebaseIntegrationComplete();

// Exportar para uso global
window.tuuFirebaseIntegrationComplete = tuuFirebaseIntegrationComplete;
export default tuuFirebaseIntegrationComplete;
