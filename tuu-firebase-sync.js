/**
 * 🔄 SINCRONIZACIÓN TUU + FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Sincroniza confirmaciones de pago TUU entre PC1 y PC2
 */

import { db } from './firebase-config.js';
import { collection, doc, setDoc, getDoc, onSnapshot, query, where, orderBy, limit } from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js';
import { log } from './config-sistema-hibrido.js';

class TUUFirebaseSync {
  constructor() {
    this.isInitialized = false;
    this.paymentListeners = new Map();
    this.pendingPayments = new Map();
    this.syncConfig = {
      collection: 'tuu_payments',
      retryAttempts: 3,
      retryDelay: 2000,
      timeout: 30000
    };
    
    this.init();
  }
  
  /**
   * Inicializar sincronización TUU
   */
  async init() {
    try {
      log('info', 'Iniciando sincronización TUU + Firebase...');
      
      // Configurar listener para pagos pendientes
      this.setupPaymentListener();
      
      // Procesar pagos pendientes locales
      await this.processPendingPayments();
      
      this.isInitialized = true;
      log('success', 'Sincronización TUU + Firebase iniciada correctamente');
      
    } catch (error) {
      log('error', 'Error inicializando sincronización TUU:', error);
    }
  }
  
  /**
   * Configurar listener de pagos en tiempo real
   */
  setupPaymentListener() {
    try {
      const paymentsRef = collection(db, this.syncConfig.collection);
      const q = query(
        paymentsRef,
        where('status', '==', 'pending'),
        limit(50)
      );
      
      onSnapshot(q, (snapshot) => {
        snapshot.docChanges().forEach((change) => {
          if (change.type === 'added' || change.type === 'modified') {
            this.handlePaymentUpdate(change.doc.data(), change.doc.id);
          }
        });
      });
      
      log('info', 'Listener de pagos TUU configurado');
      
    } catch (error) {
      log('error', 'Error configurando listener de pagos:', error);
    }
  }
  
  /**
   * Manejar actualización de pago
   */
  async handlePaymentUpdate(paymentData, paymentId) {
    try {
      log('info', `Procesando actualización de pago TUU: ${paymentId}`);
      
      // Verificar si este pago es relevante para esta PC
      if (this.isRelevantPayment(paymentData)) {
        await this.processPaymentUpdate(paymentData, paymentId);
      }
      
    } catch (error) {
      log('error', 'Error procesando actualización de pago:', error);
    }
  }
  
  /**
   * Verificar si el pago es relevante para esta PC
   */
  isRelevantPayment(paymentData) {
    // Un pago es relevante si:
    // 1. Fue creado por esta PC
    // 2. Es un pago pendiente que necesita confirmación
    // 3. Es un pago confirmado que necesita sincronización
    
    const currentPC = this.getCurrentPC();
    return paymentData.pc_id === currentPC || 
           paymentData.status === 'pending' || 
           paymentData.status === 'completed';
  }
  
  /**
   * Obtener ID de PC actual
   */
  getCurrentPC() {
    const userAgent = navigator.userAgent;
    if (userAgent.includes('Windows NT 6.1')) {
      return 'PC2_WINDOWS7';
    } else if (userAgent.includes('Linux')) {
      return 'PC1_ANTIX';
    }
    return 'UNKNOWN';
  }
  
  /**
   * Procesar actualización de pago
   */
  async processPaymentUpdate(paymentData, paymentId) {
    try {
      switch (paymentData.status) {
        case 'pending':
          await this.handlePendingPayment(paymentData, paymentId);
          break;
        case 'completed':
          await this.handleCompletedPayment(paymentData, paymentId);
          break;
        case 'failed':
          await this.handleFailedPayment(paymentData, paymentId);
          break;
      }
      
    } catch (error) {
      log('error', 'Error procesando actualización de pago:', error);
    }
  }
  
  /**
   * Manejar pago pendiente
   */
  async handlePendingPayment(paymentData, paymentId) {
    log('info', `Pago pendiente detectado: ${paymentId}`);
    
    // Si es un pago creado por esta PC, iniciar verificación
    if (paymentData.pc_id === this.getCurrentPC()) {
      await this.startPaymentVerification(paymentData, paymentId);
    }
  }
  
  /**
   * Manejar pago completado
   */
  async handleCompletedPayment(paymentData, paymentId) {
    log('info', `Pago completado detectado: ${paymentId}`);
    
    // Sincronizar con base de datos local
    await this.syncCompletedPayment(paymentData);
  }
  
  /**
   * Manejar pago fallido
   */
  async handleFailedPayment(paymentData, paymentId) {
    log('info', `Pago fallido detectado: ${paymentId}`);
    
    // Notificar al usuario
    this.notifyPaymentFailed(paymentData);
  }
  
  /**
   * Iniciar verificación de pago
   */
  async startPaymentVerification(paymentData, paymentId) {
    try {
      log('info', `Iniciando verificación de pago: ${paymentId}`);
      
      // Marcar como verificando
      await this.updatePaymentStatus(paymentId, 'verifying');
      
      // Iniciar verificación con TUU
      const verificationResult = await this.verifyPaymentWithTUU(paymentData);
      
      if (verificationResult.success) {
        // Pago confirmado
        await this.updatePaymentStatus(paymentId, 'completed', verificationResult.data);
        log('success', `Pago confirmado: ${paymentId}`);
      } else {
        // Pago fallido o pendiente
        await this.updatePaymentStatus(paymentId, verificationResult.status, verificationResult.data);
        log('warning', `Pago ${verificationResult.status}: ${paymentId}`);
      }
      
    } catch (error) {
      log('error', 'Error verificando pago:', error);
      await this.updatePaymentStatus(paymentId, 'failed', { error: error.message });
    }
  }
  
  /**
   * Verificar pago con TUU
   */
  async verifyPaymentWithTUU(paymentData) {
    try {
      const response = await fetch(`tuu-status-websocket.php?action=check_status&transaction_id=${encodeURIComponent(paymentData.transaction_id)}`);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const data = await response.json();
      
      return {
        success: data.success,
        status: data.status,
        data: data.data
      };
      
    } catch (error) {
      return {
        success: false,
        status: 'failed',
        data: { error: error.message }
      };
    }
  }
  
  /**
   * Actualizar estado de pago en Firebase
   */
  async updatePaymentStatus(paymentId, status, data = {}) {
    try {
      const paymentRef = doc(db, this.syncConfig.collection, paymentId);
      await setDoc(paymentRef, {
        status,
        updated_at: new Date(),
        ...data
      }, { merge: true });
      
      log('info', `Estado de pago actualizado: ${paymentId} -> ${status}`);
      
    } catch (error) {
      log('error', 'Error actualizando estado de pago:', error);
    }
  }
  
  /**
   * Sincronizar pago completado con base de datos local
   */
  async syncCompletedPayment(paymentData) {
    try {
      // Verificar si ya está sincronizado
      const isAlreadySynced = await this.isPaymentSynced(paymentData.transaction_id);
      
      if (!isAlreadySynced) {
        // Sincronizar con base de datos local
        await this.syncToLocalDatabase(paymentData);
        log('success', `Pago sincronizado localmente: ${paymentData.transaction_id}`);
      }
      
    } catch (error) {
      log('error', 'Error sincronizando pago completado:', error);
    }
  }
  
  /**
   * Verificar si el pago ya está sincronizado
   */
  async isPaymentSynced(transactionId) {
    try {
      const response = await fetch(`api/check-payment-sync.php?transaction_id=${encodeURIComponent(transactionId)}`);
      const data = await response.json();
      return data.synced || false;
    } catch (error) {
      return false;
    }
  }
  
  /**
   * Sincronizar con base de datos local
   */
  async syncToLocalDatabase(paymentData) {
    try {
      const response = await fetch('api/sync-tuu-payment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(paymentData)
      });
      
      const result = await response.json();
      
      if (!result.success) {
        throw new Error(result.error);
      }
      
    } catch (error) {
      log('error', 'Error sincronizando con base de datos local:', error);
      throw error;
    }
  }
  
  /**
   * Crear pago en Firebase
   */
  async createPayment(paymentData) {
    try {
      const paymentId = `tuu_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
      
      const paymentRef = doc(db, this.syncConfig.collection, paymentId);
      await setDoc(paymentRef, {
        ...paymentData,
        payment_id: paymentId,
        pc_id: this.getCurrentPC(),
        status: 'pending',
        created_at: new Date(),
        updated_at: new Date()
      });
      
      log('info', `Pago creado en Firebase: ${paymentId}`);
      return paymentId;
      
    } catch (error) {
      log('error', 'Error creando pago en Firebase:', error);
      throw error;
    }
  }
  
  /**
   * Procesar pagos pendientes locales
   */
  async processPendingPayments() {
    try {
      // Obtener pagos pendientes de la base de datos local
      const response = await fetch('api/get-pending-tuu-payments.php');
      const data = await response.json();
      
      if (data.success) {
        for (const payment of data.payments) {
          await this.createPayment(payment);
        }
        
        log('info', `${data.payments.length} pagos pendientes procesados`);
      }
      
    } catch (error) {
      log('error', 'Error procesando pagos pendientes:', error);
    }
  }
  
  /**
   * Notificar pago fallido
   */
  notifyPaymentFailed(paymentData) {
    // Mostrar notificación al usuario
    if (typeof showNotification === 'function') {
      showNotification(`Pago TUU fallido: ${paymentData.patente}`, 'error');
    }
  }
  
  /**
   * Obtener estado de sincronización
   */
  getSyncStatus() {
    return {
      initialized: this.isInitialized,
      activeListeners: this.paymentListeners.size,
      pendingPayments: this.pendingPayments.size
    };
  }
}

// Crear instancia global
const tuuFirebaseSync = new TUUFirebaseSync();

// Exportar para uso global
window.tuuFirebaseSync = tuuFirebaseSync;
export default tuuFirebaseSync;
