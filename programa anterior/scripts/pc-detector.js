/**
 * 🖥️ DETECTOR DE PC ACTIVA
 * Sistema de Estacionamiento Los Ríos
 * 
 * Detecta qué PC está activa y maneja el cambio de control
 */

import { db, systemConfig } from './firebase-config.js';
import { 
  collection, 
  doc, 
  setDoc, 
  getDoc, 
  onSnapshot, 
  serverTimestamp 
} from 'https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js';

class PCDetector {
  constructor() {
    this.currentPC = systemConfig.pcId;
    this.activePC = null;
    this.lastHeartbeat = null;
    this.heartbeatInterval = null;
    this.statusCheckInterval = null;
    
    // Configuración
    this.heartbeatIntervalMs = 10000; // 10 segundos
    this.statusCheckIntervalMs = 5000; // 5 segundos
    this.timeoutThreshold = 30000; // 30 segundos
    
    // Estados
    this.isActive = false;
    this.isOnline = navigator.onLine;
    
    // Inicializar
    this.init();
  }
  
  /**
   * Inicializar el detector de PC
   */
  init() {
    console.log('🖥️ Iniciando detector de PC...');
    
    // Escuchar cambios de conectividad
    window.addEventListener('online', () => this.handleOnline());
    window.addEventListener('offline', () => this.handleOffline());
    
    // Iniciar heartbeat
    this.startHeartbeat();
    
    // Iniciar monitoreo de estado
    this.startStatusMonitoring();
    
    // Escuchar cambios en Firebase
    this.listenToPCStatus();
    
    console.log('✅ Detector de PC iniciado');
  }
  
  /**
   * Manejar cuando se conecta a internet
   */
  handleOnline() {
    console.log('🌐 Conexión restaurada - Reanudando monitoreo');
    this.isOnline = true;
    this.startHeartbeat();
    this.startStatusMonitoring();
  }
  
  /**
   * Manejar cuando se pierde la conexión
   */
  handleOffline() {
    console.log('📴 Conexión perdida - Pausando monitoreo');
    this.isOnline = false;
    this.stopHeartbeat();
    this.stopStatusMonitoring();
  }
  
  /**
   * Iniciar heartbeat para mantener esta PC activa
   */
  startHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
    }
    
    this.heartbeatInterval = setInterval(() => {
      this.sendHeartbeat();
    }, this.heartbeatIntervalMs);
    
    // Enviar heartbeat inmediatamente
    this.sendHeartbeat();
  }
  
  /**
   * Detener heartbeat
   */
  stopHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }
  
  /**
   * Enviar heartbeat a Firebase
   */
  async sendHeartbeat() {
    if (!this.isOnline) {
      return;
    }
    
    try {
      const pcStatusRef = doc(db, 'pc_status', this.currentPC);
      const statusData = {
        pcId: this.currentPC,
        isOnline: true,
        lastHeartbeat: serverTimestamp(),
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        hostname: window.location.hostname,
        hasPrinter: systemConfig.pcId === 'PC2_WINDOWS7',
        isMainServer: systemConfig.pcId === 'PC1_ANTIX'
      };
      
      await setDoc(pcStatusRef, statusData, { merge: true });
      this.lastHeartbeat = new Date();
      
      console.log('💓 Heartbeat enviado:', this.currentPC);
    } catch (error) {
      console.error('❌ Error enviando heartbeat:', error);
    }
  }
  
  /**
   * Iniciar monitoreo de estado de otras PC
   */
  startStatusMonitoring() {
    if (this.statusCheckInterval) {
      clearInterval(this.statusCheckInterval);
    }
    
    this.statusCheckInterval = setInterval(() => {
      this.checkPCStatus();
    }, this.statusCheckIntervalMs);
  }
  
  /**
   * Detener monitoreo de estado
   */
  stopStatusMonitoring() {
    if (this.statusCheckInterval) {
      clearInterval(this.statusCheckInterval);
      this.statusCheckInterval = null;
    }
  }
  
  /**
   * Verificar estado de otras PC
   */
  async checkPCStatus() {
    if (!this.isOnline) {
      return;
    }
    
    try {
      const pcStatusRef = doc(db, 'pc_status', 'PC1_ANTIX');
      const pc1Doc = await getDoc(pcStatusRef);
      
      const pc2StatusRef = doc(db, 'pc_status', 'PC2_WINDOWS7');
      const pc2Doc = await getDoc(pc2StatusRef);
      
      const pc1Status = pc1Doc.exists() ? pc1Doc.data() : null;
      const pc2Status = pc2Doc.exists() ? pc2Doc.data() : null;
      
      // Determinar qué PC está activa
      const activePC = this.determineActivePC(pc1Status, pc2Status);
      
      if (activePC !== this.activePC) {
        this.handlePCChange(activePC);
      }
      
      this.activePC = activePC;
      
    } catch (error) {
      console.error('❌ Error verificando estado de PC:', error);
    }
  }
  
  /**
   * Determinar qué PC está activa
   */
  determineActivePC(pc1Status, pc2Status) {
    const now = new Date();
    
    // Verificar si PC1 está activa
    const pc1Active = pc1Status && 
      pc1Status.isOnline && 
      this.isRecentHeartbeat(pc1Status.lastHeartbeat, now);
    
    // Verificar si PC2 está activa
    const pc2Active = pc2Status && 
      pc2Status.isOnline && 
      this.isRecentHeartbeat(pc2Status.lastHeartbeat, now);
    
    // Lógica de prioridad
    if (pc1Active && pc2Active) {
      // Ambas están activas - PC1 tiene prioridad
      return 'PC1_ANTIX';
    } else if (pc1Active) {
      // Solo PC1 está activa
      return 'PC1_ANTIX';
    } else if (pc2Active) {
      // Solo PC2 está activa
      return 'PC2_WINDOWS7';
    } else {
      // Ninguna está activa - usar esta PC
      return this.currentPC;
    }
  }
  
  /**
   * Verificar si el heartbeat es reciente
   */
  isRecentHeartbeat(heartbeat, now) {
    if (!heartbeat) {
      return false;
    }
    
    const heartbeatTime = heartbeat.toDate ? heartbeat.toDate() : new Date(heartbeat);
    const diff = now - heartbeatTime;
    
    return diff < this.timeoutThreshold;
  }
  
  /**
   * Manejar cambio de PC activa
   */
  handlePCChange(newActivePC) {
    console.log(`🔄 Cambio de PC activa: ${this.activePC} → ${newActivePC}`);
    
    const wasActive = this.isActive;
    this.isActive = (newActivePC === this.currentPC);
    
    if (this.isActive && !wasActive) {
      this.onBecomeActive();
    } else if (!this.isActive && wasActive) {
      this.onBecomeInactive();
    }
    
    // Notificar a otros componentes
    this.notifyPCChange(newActivePC);
  }
  
  /**
   * Esta PC se convirtió en activa
   */
  onBecomeActive() {
    console.log('✅ Esta PC es ahora la activa');
    
    // Activar funcionalidades específicas
    this.activatePCFeatures();
    
    // Notificar al usuario
    this.showActiveNotification();
  }
  
  /**
   * Esta PC se convirtió en inactiva
   */
  onBecomeInactive() {
    console.log('⏸️ Esta PC ya no es la activa');
    
    // Desactivar funcionalidades específicas
    this.deactivatePCFeatures();
    
    // Notificar al usuario
    this.showInactiveNotification();
  }
  
  /**
   * Activar funcionalidades específicas de esta PC
   */
  activatePCFeatures() {
    // Activar impresión si es PC2
    if (systemConfig.pcId === 'PC2_WINDOWS7') {
      this.activatePrinting();
    }
    
    // Activar sincronización
    if (window.syncManager) {
      window.syncManager.syncData();
    }
    
    // Activar otras funcionalidades específicas
    this.activateSpecificFeatures();
  }
  
  /**
   * Desactivar funcionalidades específicas de esta PC
   */
  deactivatePCFeatures() {
    // Desactivar impresión si es PC2
    if (systemConfig.pcId === 'PC2_WINDOWS7') {
      this.deactivatePrinting();
    }
    
    // Desactivar otras funcionalidades específicas
    this.deactivateSpecificFeatures();
  }
  
  /**
   * Activar impresión
   */
  activatePrinting() {
    console.log('🖨️ Activando impresión...');
    // Implementar lógica de activación de impresión
  }
  
  /**
   * Desactivar impresión
   */
  deactivatePrinting() {
    console.log('🖨️ Desactivando impresión...');
    // Implementar lógica de desactivación de impresión
  }
  
  /**
   * Activar funcionalidades específicas
   */
  activateSpecificFeatures() {
    // Implementar funcionalidades específicas según la PC
    if (systemConfig.pcId === 'PC1_ANTIX') {
      // Funcionalidades de servidor principal
      console.log('🖥️ Activando funcionalidades de servidor principal');
    } else if (systemConfig.pcId === 'PC2_WINDOWS7') {
      // Funcionalidades de PC de producción
      console.log('💻 Activando funcionalidades de PC de producción');
    }
  }
  
  /**
   * Desactivar funcionalidades específicas
   */
  deactivateSpecificFeatures() {
    // Implementar desactivación de funcionalidades específicas
    console.log('⏸️ Desactivando funcionalidades específicas');
  }
  
  /**
   * Mostrar notificación de PC activa
   */
  showActiveNotification() {
    // Implementar notificación visual
    console.log('🔔 Notificación: Esta PC es ahora la activa');
  }
  
  /**
   * Mostrar notificación de PC inactiva
   */
  showInactiveNotification() {
    // Implementar notificación visual
    console.log('🔔 Notificación: Esta PC ya no es la activa');
  }
  
  /**
   * Notificar cambio de PC a otros componentes
   */
  notifyPCChange(newActivePC) {
    // Disparar evento personalizado
    const event = new CustomEvent('pcStatusChange', {
      detail: {
        activePC: newActivePC,
        isActive: this.isActive,
        timestamp: new Date()
      }
    });
    
    window.dispatchEvent(event);
  }
  
  /**
   * Escuchar cambios en Firebase
   */
  listenToPCStatus() {
    const pcStatusRef = collection(db, 'pc_status');
    
    onSnapshot(pcStatusRef, (snapshot) => {
      snapshot.docChanges().forEach((change) => {
        if (change.type === 'modified') {
          const data = change.doc.data();
          console.log('📡 Cambio detectado en Firebase:', data.pcId, data.isOnline);
        }
      });
    });
  }
  
  /**
   * Obtener estado actual
   */
  getStatus() {
    return {
      currentPC: this.currentPC,
      activePC: this.activePC,
      isActive: this.isActive,
      isOnline: this.isOnline,
      lastHeartbeat: this.lastHeartbeat,
      timestamp: new Date()
    };
  }
  
  /**
   * Forzar esta PC como activa
   */
  async forceActive() {
    console.log('🔧 Forzando esta PC como activa...');
    
    // Enviar heartbeat inmediatamente
    await this.sendHeartbeat();
    
    // Esperar un momento y verificar
    setTimeout(() => {
      this.checkPCStatus();
    }, 1000);
  }
}

// Crear instancia global
const pcDetector = new PCDetector();

// Exportar para uso global
window.pcDetector = pcDetector;
export default pcDetector;
