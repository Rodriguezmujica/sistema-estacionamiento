/**
 * 🔄 INTEGRACIÓN TUU + FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Integra el sistema de cobro TUU existente con Firebase
 */

import tuuFirebaseSync from './tuu-firebase-sync.js';

class TUUFirebaseIntegration {
  constructor() {
    this.isInitialized = false;
    this.originalFunctions = {};
    
    this.init();
  }
  
  /**
   * Inicializar integración
   */
  async init() {
    try {
      console.log('🔄 Iniciando integración TUU + Firebase...');
      
      // Interceptar funciones de TUU existentes
      this.interceptTUUFunctions();
      
      // Configurar eventos
      this.setupEventListeners();
      
      this.isInitialized = true;
      console.log('✅ Integración TUU + Firebase iniciada');
      
    } catch (error) {
      console.error('❌ Error inicializando integración TUU + Firebase:', error);
    }
  }
  
  /**
   * Interceptar funciones de TUU existentes
   */
  interceptTUUFunctions() {
    // Interceptar función de verificación de estado
    if (typeof iniciarVerificacionEstadoTUU === 'function') {
      this.originalFunctions.iniciarVerificacionEstadoTUU = iniciarVerificacionEstadoTUU;
      window.iniciarVerificacionEstadoTUU = this.enhancedIniciarVerificacionEstadoTUU.bind(this);
    }
    
    // Interceptar función de confirmación manual
    if (typeof confirmarPagoManualTUU === 'function') {
      this.originalFunctions.confirmarPagoManualTUU = confirmarPagoManualTUU;
      window.confirmarPagoManualTUU = this.enhancedConfirmarPagoManualTUU.bind(this);
    }
    
    // Interceptar función de finalización de cobro
    if (typeof finalizarCobroExitoso === 'function') {
      this.originalFunctions.finalizarCobroExitoso = finalizarCobroExitoso;
      window.finalizarCobroExitoso = this.enhancedFinalizarCobroExitoso.bind(this);
    }
  }
  
  /**
   * Función mejorada de verificación de estado TUU
   */
  async enhancedIniciarVerificacionEstadoTUU(toastId, transactionId) {
    try {
      console.log(`🔄 Verificación TUU mejorada: ${transactionId}`);
      
      // Crear pago en Firebase
      const paymentData = await this.createPaymentInFirebase(transactionId);
      
      // Llamar función original
      if (this.originalFunctions.iniciarVerificacionEstadoTUU) {
        return await this.originalFunctions.iniciarVerificacionEstadoTUU(toastId, transactionId);
      }
      
    } catch (error) {
      console.error('❌ Error en verificación TUU mejorada:', error);
      
      // Fallback a función original
      if (this.originalFunctions.iniciarVerificacionEstadoTUU) {
        return await this.originalFunctions.iniciarVerificacionEstadoTUU(toastId, transactionId);
      }
    }
  }
  
  /**
   * Función mejorada de confirmación manual TUU
   */
  async enhancedConfirmarPagoManualTUU(transactionId, patente, toastId) {
    try {
      console.log(`🔧 Confirmación manual TUU mejorada: ${transactionId}`);
      
      // Actualizar pago en Firebase
      await this.updatePaymentInFirebase(transactionId, 'completed', { patente });
      
      // Llamar función original
      if (this.originalFunctions.confirmarPagoManualTUU) {
        return await this.originalFunctions.confirmarPagoManualTUU(transactionId, patente, toastId);
      }
      
    } catch (error) {
      console.error('❌ Error en confirmación manual TUU mejorada:', error);
      
      // Fallback a función original
      if (this.originalFunctions.confirmarPagoManualTUU) {
        return await this.originalFunctions.confirmarPagoManualTUU(transactionId, patente, toastId);
      }
    }
  }
  
  /**
   * Función mejorada de finalización de cobro
   */
  async enhancedFinalizarCobroExitoso(metodo, total, data) {
    try {
      console.log(`✅ Finalización de cobro mejorada: ${metodo} - $${total}`);
      
      // Si es TUU, actualizar en Firebase
      if (metodo === 'TUU') {
        await this.updatePaymentInFirebase(data.transaction_id, 'completed', {
          total,
          authorization_code: data.authorization_code,
          card_type: data.card_type,
          card_last4: data.card_last4
        });
      }
      
      // Llamar función original
      if (this.originalFunctions.finalizarCobroExitoso) {
        return await this.originalFunctions.finalizarCobroExitoso(metodo, total, data);
      }
      
    } catch (error) {
      console.error('❌ Error en finalización de cobro mejorada:', error);
      
      // Fallback a función original
      if (this.originalFunctions.finalizarCobroExitoso) {
        return await this.originalFunctions.finalizarCobroExitoso(metodo, total, data);
      }
    }
  }
  
  /**
   * Crear pago en Firebase
   */
  async createPaymentInFirebase(transactionId) {
    try {
      // Obtener datos del ticket actual
      const ticketData = this.getCurrentTicketData();
      
      const paymentData = {
        transaction_id: transactionId,
        patente: ticketData.patente,
        precio: ticketData.total,
        cliente_nombre: ticketData.cliente_nombre,
        cliente_telefono: ticketData.cliente_telefono,
        observaciones: ticketData.observaciones,
        metodo_pago: 'TUU',
        tipo_pago: 'tuu'
      };
      
      const paymentId = await tuuFirebaseSync.createPayment(paymentData);
      console.log(`✅ Pago creado en Firebase: ${paymentId}`);
      
      return paymentData;
      
    } catch (error) {
      console.error('❌ Error creando pago en Firebase:', error);
      throw error;
    }
  }
  
  /**
   * Actualizar pago en Firebase
   */
  async updatePaymentInFirebase(transactionId, status, data = {}) {
    try {
      // Buscar pago por transaction_id
      const paymentId = await this.findPaymentByTransactionId(transactionId);
      
      if (paymentId) {
        await tuuFirebaseSync.updatePaymentStatus(paymentId, status, data);
        console.log(`✅ Pago actualizado en Firebase: ${paymentId} -> ${status}`);
      } else {
        console.warn(`⚠️ Pago no encontrado en Firebase: ${transactionId}`);
      }
      
    } catch (error) {
      console.error('❌ Error actualizando pago en Firebase:', error);
    }
  }
  
  /**
   * Buscar pago por transaction_id
   */
  async findPaymentByTransactionId(transactionId) {
    try {
      // Esta función necesitaría implementarse según la estructura de Firebase
      // Por ahora retornamos null
      return null;
    } catch (error) {
      console.error('❌ Error buscando pago:', error);
      return null;
    }
  }
  
  /**
   * Obtener datos del ticket actual
   */
  getCurrentTicketData() {
    // Intentar obtener datos del ticket actual desde el DOM o variables globales
    const ticketData = {
      patente: '',
      total: 0,
      cliente_nombre: '',
      cliente_telefono: '',
      observaciones: ''
    };
    
    // Buscar en variables globales
    if (typeof ticketCobroActual !== 'undefined') {
      ticketData.patente = ticketCobroActual.patente || '';
      ticketData.total = ticketCobroActual.total || 0;
      ticketData.cliente_nombre = ticketCobroActual.cliente_nombre || '';
      ticketData.cliente_telefono = ticketCobroActual.cliente_telefono || '';
      ticketData.observaciones = ticketCobroActual.observaciones || '';
    }
    
    // Buscar en el DOM
    const patenteElement = document.querySelector('[name="patente"]');
    if (patenteElement) {
      ticketData.patente = patenteElement.value || '';
    }
    
    const totalElement = document.querySelector('[name="total"]');
    if (totalElement) {
      ticketData.total = parseFloat(totalElement.value) || 0;
    }
    
    return ticketData;
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
  }
  
  /**
   * Manejar cambio de estado de pago
   */
  handlePaymentStatusChange(detail) {
    console.log('🔄 Estado de pago TUU cambiado:', detail);
    
    // Actualizar UI si es necesario
    this.updatePaymentUI(detail);
  }
  
  /**
   * Manejar pago completado
   */
  handlePaymentCompleted(detail) {
    console.log('✅ Pago TUU completado:', detail);
    
    // Mostrar notificación
    this.showPaymentNotification(detail);
  }
  
  /**
   * Actualizar UI de pago
   */
  updatePaymentUI(paymentData) {
    // Implementar actualización de UI según sea necesario
    console.log('🔄 Actualizando UI de pago:', paymentData);
  }
  
  /**
   * Mostrar notificación de pago
   */
  showPaymentNotification(paymentData) {
    if (typeof showNotification === 'function') {
      showNotification(`Pago TUU confirmado: ${paymentData.patente} - $${paymentData.precio}`, 'success');
    }
  }
  
  /**
   * Obtener estado de la integración
   */
  getIntegrationStatus() {
    return {
      initialized: this.isInitialized,
      tuuSyncStatus: tuuFirebaseSync.getSyncStatus(),
      interceptedFunctions: Object.keys(this.originalFunctions)
    };
  }
}

// Crear instancia global
const tuuFirebaseIntegration = new TUUFirebaseIntegration();

// Exportar para uso global
window.tuuFirebaseIntegration = tuuFirebaseIntegration;
export default tuuFirebaseIntegration;
