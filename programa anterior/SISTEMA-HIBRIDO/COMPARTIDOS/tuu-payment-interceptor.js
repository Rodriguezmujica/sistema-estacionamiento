/**
 * 🔄 INTERCEPTOR DE PAGOS TUU
 * Sistema de Estacionamiento Los Ríos
 * 
 * Intercepta la creación de pagos TUU para sincronizar con Firebase
 */

import { db } from './firebase-config.js';
import { collection, doc, setDoc } from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js';
import { log } from './config-sistema-hibrido.js';

class TUUPaymentInterceptor {
  constructor() {
    this.isInitialized = false;
    this.originalFunctions = {};
    
    this.init();
  }
  
  /**
   * Inicializar interceptor
   */
  async init() {
    try {
      log('info', 'Iniciando interceptor de pagos TUU...');
      
      // Interceptar funciones de TUU existentes
      this.interceptTUUFunctions();
      
      // Configurar eventos
      this.setupEventListeners();
      
      this.isInitialized = true;
      log('success', 'Interceptor de pagos TUU iniciado correctamente');
      
    } catch (error) {
      log('error', 'Error inicializando interceptor TUU:', error);
    }
  }
  
  /**
   * Interceptar funciones de TUU existentes
   */
  interceptTUUFunctions() {
    // Interceptar función de procesamiento de pago TUU
    if (typeof procesarPagoTUU === 'function') {
      this.originalFunctions.procesarPagoTUU = procesarPagoTUU;
      window.procesarPagoTUU = this.enhancedProcesarPagoTUU.bind(this);
    }
    
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
  }
  
  /**
   * Función mejorada de procesamiento de pago TUU
   */
  async enhancedProcesarPagoTUU(monto, idTransaccion, patente, extraData = [], metodo_tarjeta = 'desconocido', rut_cliente = null, tipo_documento = 'boleta') {
    try {
      log('info', `Procesando pago TUU mejorado: ${patente} - $${monto}`);
      
      // Crear pago en Firebase antes de procesar
      await this.createPaymentInFirebase({
        transaction_id: idTransaccion,
        patente: patente,
        precio: monto,
        cliente_nombre: extraData.cliente_nombre || '',
        cliente_telefono: extraData.cliente_telefono || '',
        observaciones: extraData.observaciones || '',
        metodo_pago: 'TUU',
        tipo_pago: 'tuu',
        metodo_tarjeta: metodo_tarjeta,
        rut_cliente: rut_cliente,
        tipo_documento: tipo_documento
      });
      
      // Llamar función original
      if (this.originalFunctions.procesarPagoTUU) {
        return await this.originalFunctions.procesarPagoTUU(monto, idTransaccion, patente, extraData, metodo_tarjeta, rut_cliente, tipo_documento);
      }
      
    } catch (error) {
      log('error', 'Error en procesamiento TUU mejorado:', error);
      
      // Fallback a función original
      if (this.originalFunctions.procesarPagoTUU) {
        return await this.originalFunctions.procesarPagoTUU(monto, idTransaccion, patente, extraData, metodo_tarjeta, rut_cliente, tipo_documento);
      }
    }
  }
  
  /**
   * Función mejorada de verificación de estado TUU
   */
  async enhancedIniciarVerificacionEstadoTUU(toastId, transactionId) {
    try {
      log('info', `Verificación TUU mejorada: ${transactionId}`);
      
      // Actualizar estado en Firebase
      await this.updatePaymentStatus(transactionId, 'verifying');
      
      // Llamar función original
      if (this.originalFunctions.iniciarVerificacionEstadoTUU) {
        return await this.originalFunctions.iniciarVerificacionEstadoTUU(toastId, transactionId);
      }
      
    } catch (error) {
      log('error', 'Error en verificación TUU mejorada:', error);
      
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
      log('info', `Confirmación manual TUU mejorada: ${transactionId}`);
      
      // Actualizar estado en Firebase
      await this.updatePaymentStatus(transactionId, 'completed', { patente });
      
      // Llamar función original
      if (this.originalFunctions.confirmarPagoManualTUU) {
        return await this.originalFunctions.confirmarPagoManualTUU(transactionId, patente, toastId);
      }
      
    } catch (error) {
      log('error', 'Error en confirmación manual TUU mejorada:', error);
      
      // Fallback a función original
      if (this.originalFunctions.confirmarPagoManualTUU) {
        return await this.originalFunctions.confirmarPagoManualTUU(transactionId, patente, toastId);
      }
    }
  }
  
  /**
   * Crear pago en Firebase
   */
  async createPaymentInFirebase(paymentData) {
    try {
      const paymentId = `tuu_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
      
      const paymentRef = doc(db, 'tuu_payments', paymentId);
      await setDoc(paymentRef, {
        ...paymentData,
        payment_id: paymentId,
        pc_id: this.getCurrentPC(),
        status: 'pending',
        created_at: new Date(),
        updated_at: new Date()
      });
      
      log('success', `Pago creado en Firebase: ${paymentId}`);
      return paymentId;
      
    } catch (error) {
      log('error', 'Error creando pago en Firebase:', error);
      throw error;
    }
  }
  
  /**
   * Actualizar estado de pago en Firebase
   */
  async updatePaymentStatus(transactionId, status, data = {}) {
    try {
      // Buscar pago por transaction_id
      const paymentId = await this.findPaymentByTransactionId(transactionId);
      
      if (paymentId) {
        const paymentRef = doc(db, 'tuu_payments', paymentId);
        await setDoc(paymentRef, {
          status,
          updated_at: new Date(),
          ...data
        }, { merge: true });
        
        log('info', `Estado de pago actualizado: ${paymentId} -> ${status}`);
      } else {
        log('warning', `Pago no encontrado en Firebase: ${transactionId}`);
      }
      
    } catch (error) {
      log('error', 'Error actualizando estado de pago:', error);
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
      log('error', 'Error buscando pago:', error);
      return null;
    }
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
    log('info', 'Estado de pago TUU cambiado:', detail);
  }
  
  /**
   * Manejar pago completado
   */
  handlePaymentCompleted(detail) {
    log('success', 'Pago TUU completado:', detail);
  }
  
  /**
   * Obtener estado del interceptor
   */
  getInterceptorStatus() {
    return {
      initialized: this.isInitialized,
      interceptedFunctions: Object.keys(this.originalFunctions)
    };
  }
}

// Crear instancia global
const tuuPaymentInterceptor = new TUUPaymentInterceptor();

// Exportar para uso global
window.tuuPaymentInterceptor = tuuPaymentInterceptor;
export default tuuPaymentInterceptor;
