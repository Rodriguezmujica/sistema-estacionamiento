/**
 * 🔄 SISTEMA DE SINCRONIZACIÓN FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Sincronización bidireccional entre PC 1 (Antix) y PC 2 (Windows 7)
 */

import { db, systemConfig } from './firebase-config.js';
import { 
  collection, 
  doc, 
  addDoc, 
  updateDoc, 
  deleteDoc, 
  getDocs, 
  getDoc, 
  onSnapshot, 
  query, 
  where, 
  orderBy, 
  limit,
  serverTimestamp,
  writeBatch
} from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js';

class FirebaseSyncManager {
  constructor() {
    this.isOnline = navigator.onLine;
    this.syncQueue = [];
    this.isSyncing = false;
    this.lastSync = null;
    this.listeners = new Map();
    
    // Configuración de sincronización
    this.config = systemConfig.sync;
    this.connectivityConfig = systemConfig.connectivity;
    
    // Inicializar
    this.init();
  }
  
  /**
   * Inicializar el sistema de sincronización
   */
  init() {
    console.log('🔄 Iniciando sistema de sincronización...');
    
    // Escuchar cambios de conectividad
    window.addEventListener('online', () => this.handleOnline());
    window.addEventListener('offline', () => this.handleOffline());
    
    // Iniciar sincronización periódica
    if (this.config.enabled) {
      this.startPeriodicSync();
    }
    
    // Iniciar monitoreo de conectividad
    this.startConnectivityMonitoring();
    
    console.log('✅ Sistema de sincronización iniciado');
  }
  
  /**
   * Manejar cuando se conecta a internet
   */
  handleOnline() {
    console.log('🌐 Conexión restaurada');
    this.isOnline = true;
    this.processSyncQueue();
  }
  
  /**
   * Manejar cuando se pierde la conexión
   */
  handleOffline() {
    console.log('📴 Conexión perdida - Modo offline activado');
    this.isOnline = false;
  }
  
  /**
   * Iniciar sincronización periódica
   */
  startPeriodicSync() {
    setInterval(() => {
      if (this.isOnline && !this.isSyncing) {
        this.syncData();
      }
    }, this.config.interval);
  }
  
  /**
   * Iniciar monitoreo de conectividad
   */
  startConnectivityMonitoring() {
    setInterval(() => {
      this.checkConnectivity();
    }, this.connectivityConfig.checkInterval);
  }
  
  /**
   * Verificar conectividad
   */
  async checkConnectivity() {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), this.connectivityConfig.timeout);
      
      await fetch('https://www.google.com', {
        method: 'HEAD',
        mode: 'no-cors',
        signal: controller.signal
      });
      
      clearTimeout(timeoutId);
      
      if (!this.isOnline) {
        this.handleOnline();
      }
    } catch (error) {
      if (this.isOnline) {
        this.handleOffline();
      }
    }
  }
  
  /**
   * Sincronizar datos con Firebase
   */
  async syncData() {
    if (this.isSyncing || !this.isOnline) {
      return;
    }
    
    this.isSyncing = true;
    console.log('🔄 Iniciando sincronización...');
    
    try {
      // Sincronizar tickets
      await this.syncTickets();
      
      // Sincronizar servicios de lavado
      await this.syncServiciosLavado();
      
      // Sincronizar usuarios
      await this.syncUsuarios();
      
      // Procesar cola de sincronización
      await this.processSyncQueue();
      
      this.lastSync = new Date();
      console.log('✅ Sincronización completada');
      
    } catch (error) {
      console.error('❌ Error en sincronización:', error);
    } finally {
      this.isSyncing = false;
    }
  }
  
  /**
   * Sincronizar tickets de estacionamiento
   */
  async syncTickets() {
    const ticketsRef = collection(db, 'tickets');
    
    // Obtener tickets locales (desde MySQL)
    const localTickets = await this.getLocalTickets();
    
    // Sincronizar cada ticket
    for (const ticket of localTickets) {
      try {
        const ticketRef = doc(ticketsRef, `ticket_${ticket.id}`);
        const ticketDoc = await getDoc(ticketRef);
        
        if (!ticketDoc.exists()) {
          // Crear nuevo ticket en Firebase
          await this.createTicketInFirebase(ticket);
        } else {
          // Actualizar ticket existente si es necesario
          await this.updateTicketInFirebase(ticket, ticketDoc.data());
        }
      } catch (error) {
        console.error('Error sincronizando ticket:', ticket.id, error);
      }
    }
  }
  
  /**
   * Sincronizar servicios de lavado
   */
  async syncServiciosLavado() {
    const serviciosRef = collection(db, 'servicios_lavado');
    
    // Obtener servicios locales
    const localServicios = await this.getLocalServiciosLavado();
    
    // Sincronizar cada servicio
    for (const servicio of localServicios) {
      try {
        const servicioRef = doc(serviciosRef, `servicio_${servicio.id}`);
        const servicioDoc = await getDoc(servicioRef);
        
        if (!servicioDoc.exists()) {
          await this.createServicioInFirebase(servicio);
        } else {
          await this.updateServicioInFirebase(servicio, servicioDoc.data());
        }
      } catch (error) {
        console.error('Error sincronizando servicio:', servicio.id, error);
      }
    }
  }
  
  /**
   * Sincronizar usuarios
   */
  async syncUsuarios() {
    const usuariosRef = collection(db, 'usuarios');
    
    // Obtener usuarios locales
    const localUsuarios = await this.getLocalUsuarios();
    
    // Sincronizar cada usuario
    for (const usuario of localUsuarios) {
      try {
        const usuarioRef = doc(usuariosRef, `usuario_${usuario.id}`);
        const usuarioDoc = await getDoc(usuarioRef);
        
        if (!usuarioDoc.exists()) {
          await this.createUsuarioInFirebase(usuario);
        } else {
          await this.updateUsuarioInFirebase(usuario, usuarioDoc.data());
        }
      } catch (error) {
        console.error('Error sincronizando usuario:', usuario.id, error);
      }
    }
  }
  
  /**
   * Obtener tickets locales desde MySQL
   */
  async getLocalTickets() {
    try {
      const response = await fetch('api/get-tickets.php');
      const data = await response.json();
      return data.tickets || [];
    } catch (error) {
      console.error('Error obteniendo tickets locales:', error);
      return [];
    }
  }
  
  /**
   * Obtener servicios de lavado locales
   */
  async getLocalServiciosLavado() {
    try {
      const response = await fetch('api/get-servicios-lavado.php');
      const data = await response.json();
      return data.servicios || [];
    } catch (error) {
      console.error('Error obteniendo servicios locales:', error);
      return [];
    }
  }
  
  /**
   * Obtener usuarios locales
   */
  async getLocalUsuarios() {
    try {
      const response = await fetch('api/get-usuarios.php');
      const data = await response.json();
      return data.usuarios || [];
    } catch (error) {
      console.error('Error obteniendo usuarios locales:', error);
      return [];
    }
  }
  
  /**
   * Crear ticket en Firebase
   */
  async createTicketInFirebase(ticket) {
    const ticketsRef = collection(db, 'tickets');
    const ticketData = {
      ...ticket,
      pcId: systemConfig.pcId,
      createdAt: serverTimestamp(),
      updatedAt: serverTimestamp(),
      synced: true
    };
    
    await addDoc(ticketsRef, ticketData);
    console.log('✅ Ticket creado en Firebase:', ticket.id);
  }
  
  /**
   * Actualizar ticket en Firebase
   */
  async updateTicketInFirebase(ticket, firebaseData) {
    const ticketRef = doc(db, 'tickets', `ticket_${ticket.id}`);
    const updateData = {
      ...ticket,
      pcId: systemConfig.pcId,
      updatedAt: serverTimestamp(),
      synced: true
    };
    
    await updateDoc(ticketRef, updateData);
    console.log('✅ Ticket actualizado en Firebase:', ticket.id);
  }
  
  /**
   * Crear servicio en Firebase
   */
  async createServicioInFirebase(servicio) {
    const serviciosRef = collection(db, 'servicios_lavado');
    const servicioData = {
      ...servicio,
      pcId: systemConfig.pcId,
      createdAt: serverTimestamp(),
      updatedAt: serverTimestamp(),
      synced: true
    };
    
    await addDoc(serviciosRef, servicioData);
    console.log('✅ Servicio creado en Firebase:', servicio.id);
  }
  
  /**
   * Actualizar servicio en Firebase
   */
  async updateServicioInFirebase(servicio, firebaseData) {
    const servicioRef = doc(db, 'servicios_lavado', `servicio_${servicio.id}`);
    const updateData = {
      ...servicio,
      pcId: systemConfig.pcId,
      updatedAt: serverTimestamp(),
      synced: true
    };
    
    await updateDoc(servicioRef, updateData);
    console.log('✅ Servicio actualizado en Firebase:', servicio.id);
  }
  
  /**
   * Crear usuario en Firebase
   */
  async createUsuarioInFirebase(usuario) {
    const usuariosRef = collection(db, 'usuarios');
    const usuarioData = {
      ...usuario,
      pcId: systemConfig.pcId,
      createdAt: serverTimestamp(),
      updatedAt: serverTimestamp(),
      synced: true
    };
    
    await addDoc(usuariosRef, usuarioData);
    console.log('✅ Usuario creado en Firebase:', usuario.id);
  }
  
  /**
   * Actualizar usuario en Firebase
   */
  async updateUsuarioInFirebase(usuario, firebaseData) {
    const usuarioRef = doc(db, 'usuarios', `usuario_${usuario.id}`);
    const updateData = {
      ...usuario,
      pcId: systemConfig.pcId,
      updatedAt: serverTimestamp(),
      synced: true
    };
    
    await updateDoc(usuarioRef, updateData);
    console.log('✅ Usuario actualizado en Firebase:', usuario.id);
  }
  
  /**
   * Procesar cola de sincronización
   */
  async processSyncQueue() {
    if (this.syncQueue.length === 0) {
      return;
    }
    
    console.log(`🔄 Procesando ${this.syncQueue.length} elementos en cola...`);
    
    const batch = writeBatch(db);
    let processed = 0;
    
    for (const item of this.syncQueue) {
      try {
        const { type, data, action } = item;
        
        switch (type) {
          case 'ticket':
            await this.processTicketSync(batch, data, action);
            break;
          case 'servicio':
            await this.processServicioSync(batch, data, action);
            break;
          case 'usuario':
            await this.processUsuarioSync(batch, data, action);
            break;
        }
        
        processed++;
      } catch (error) {
        console.error('Error procesando elemento de cola:', error);
      }
    }
    
    if (processed > 0) {
      await batch.commit();
      this.syncQueue = this.syncQueue.slice(processed);
      console.log(`✅ ${processed} elementos procesados`);
    }
  }
  
  /**
   * Agregar elemento a la cola de sincronización
   */
  addToSyncQueue(type, data, action = 'create') {
    this.syncQueue.push({
      type,
      data,
      action,
      timestamp: new Date(),
      pcId: systemConfig.pcId
    });
    
    console.log(`📝 Elemento agregado a cola: ${type} - ${action}`);
  }
  
  /**
   * Obtener estado de sincronización
   */
  getSyncStatus() {
    return {
      isOnline: this.isOnline,
      isSyncing: this.isSyncing,
      lastSync: this.lastSync,
      queueLength: this.syncQueue.length,
      pcId: systemConfig.pcId
    };
  }
}

// Crear instancia global
const syncManager = new FirebaseSyncManager();

// Exportar para uso global
window.syncManager = syncManager;
export default syncManager;
