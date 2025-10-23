/**
 * 🔥 FIREBASE OPTIMIZER SIMPLIFICADO
 * Sistema de Estacionamiento Los Ríos
 * Versión sin módulos ES6 para compatibilidad
 */

class FirebaseOptimizerSimple {
  constructor() {
    this.cache = new Map();
    this.lastSync = new Map();
    this.operationCount = {
      reads: 0,
      writes: 0,
      today: new Date().toDateString()
    };
    this.limits = {
      reads: 50000,
      writes: 20000
    };
    
    this.init();
  }

  init() {
    // Cargar contador del localStorage
    this.loadOperationCount();
    
    // Resetear contador diario
    this.resetDailyIfNeeded();
    
    // Configurar monitoreo
    this.setupMonitoring();
    
    console.log('🔥 Firebase Optimizer Simple inicializado');
  }

  /**
   * Verificar si podemos realizar una operación
   */
  canPerformOperation(type) {
    const today = new Date().toDateString();
    
    if (this.operationCount.today !== today) {
      this.resetDailyCount();
    }

    const current = this.operationCount[type];
    const limit = this.limits[type];
    const usage = (current / limit) * 100;

    // Alerta al 80% de uso
    if (usage >= 80) {
      console.warn(`⚠️ Firebase ${type} al ${usage.toFixed(1)}% del límite`);
      this.showAlert(`${type.toUpperCase()} al ${usage.toFixed(1)}% del límite`);
    }

    // Bloquear al 95% de uso
    if (usage >= 95) {
      console.error(`🚨 Firebase ${type} bloqueado - límite casi alcanzado`);
      this.showAlert(`🚨 ${type.toUpperCase()} bloqueado - límite casi alcanzado`);
      return false;
    }

    return true;
  }

  /**
   * Registrar operación de lectura
   */
  recordRead() {
    if (!this.canPerformOperation('reads')) {
      return false;
    }
    
    this.operationCount.reads++;
    this.saveOperationCount();
    return true;
  }

  /**
   * Registrar operación de escritura
   */
  recordWrite() {
    if (!this.canPerformOperation('writes')) {
      return false;
    }
    
    this.operationCount.writes++;
    this.saveOperationCount();
    return true;
  }

  /**
   * Sincronización inteligente - solo cambios
   */
  async smartSync(collection, docId, data) {
    const cacheKey = `${collection}_${docId}`;
    const lastData = this.cache.get(cacheKey);
    
    // Solo sincronizar si hay cambios
    if (lastData && JSON.stringify(lastData) === JSON.stringify(data)) {
      console.log(`📦 Sincronización omitida - sin cambios en ${cacheKey}`);
      return { success: true, skipped: true };
    }

    // Verificar límites antes de sincronizar
    if (!this.recordWrite()) {
      console.log(`🚫 Sincronización bloqueada - límite de escrituras alcanzado`);
      return { success: false, reason: 'limit_reached' };
    }

    // Actualizar caché
    this.cache.set(cacheKey, { ...data });
    this.lastSync.set(cacheKey, Date.now());

    return { success: true, synced: true };
  }

  /**
   * Lectura con caché
   */
  async cachedRead(collection, docId, maxAge = 30000) {
    const cacheKey = `${collection}_${docId}`;
    const cached = this.cache.get(cacheKey);
    const lastSync = this.lastSync.get(cacheKey);
    
    // Verificar si el caché es válido
    if (cached && lastSync && (Date.now() - lastSync) < maxAge) {
      console.log(`📦 Lectura desde caché: ${cacheKey}`);
      return { success: true, data: cached, fromCache: true };
    }

    // Verificar límites antes de leer
    if (!this.recordRead()) {
      console.log(`🚫 Lectura bloqueada - límite de lecturas alcanzado`);
      return { success: false, reason: 'limit_reached' };
    }

    return { success: true, fromCache: false };
  }

  /**
   * Obtener estadísticas de uso
   */
  getUsageStats() {
    const today = new Date().toDateString();
    
    if (this.operationCount.today !== today) {
      this.resetDailyCount();
    }

    const readsUsage = (this.operationCount.reads / this.limits.reads) * 100;
    const writesUsage = (this.operationCount.writes / this.limits.writes) * 100;

    return {
      reads: {
        current: this.operationCount.reads,
        limit: this.limits.reads,
        usage: readsUsage.toFixed(1) + '%'
      },
      writes: {
        current: this.operationCount.writes,
        limit: this.limits.writes,
        usage: writesUsage.toFixed(1) + '%'
      },
      status: readsUsage >= 95 || writesUsage >= 95 ? 'critical' : 
              readsUsage >= 80 || writesUsage >= 80 ? 'warning' : 'ok'
    };
  }

  /**
   * Configurar monitoreo automático
   */
  setupMonitoring() {
    // Verificar cada 5 minutos
    setInterval(() => {
      const stats = this.getUsageStats();
      
      if (stats.status === 'critical') {
        this.showAlert('🚨 CRÍTICO: Límites Firebase casi alcanzados');
      } else if (stats.status === 'warning') {
        this.showAlert('⚠️ ADVERTENCIA: Uso alto de Firebase');
      }
    }, 5 * 60 * 1000);

    // Mostrar estadísticas en consola cada hora
    setInterval(() => {
      const stats = this.getUsageStats();
      console.log('📊 Firebase Usage:', stats);
    }, 60 * 60 * 1000);
  }

  /**
   * Mostrar alerta al usuario
   */
  showAlert(message) {
    // Crear notificación visual
    const alert = document.createElement('div');
    alert.className = 'alert alert-warning position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    alert.innerHTML = `
      <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span>${message}</span>
        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
      </div>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remover después de 10 segundos
    setTimeout(() => {
      if (alert.parentElement) {
        alert.remove();
      }
    }, 10000);
  }

  /**
   * Guardar contador en localStorage
   */
  saveOperationCount() {
    localStorage.setItem('firebase_operations', JSON.stringify(this.operationCount));
  }

  /**
   * Cargar contador desde localStorage
   */
  loadOperationCount() {
    const saved = localStorage.getItem('firebase_operations');
    if (saved) {
      try {
        this.operationCount = { ...this.operationCount, ...JSON.parse(saved) };
      } catch (e) {
        console.warn('Error cargando contador de operaciones:', e);
      }
    }
  }

  /**
   * Resetear contador diario si es necesario
   */
  resetDailyIfNeeded() {
    const today = new Date().toDateString();
    if (this.operationCount.today !== today) {
      this.resetDailyCount();
    }
  }

  /**
   * Resetear contador diario
   */
  resetDailyCount() {
    this.operationCount = {
      reads: 0,
      writes: 0,
      today: new Date().toDateString()
    };
    this.saveOperationCount();
    console.log('🔄 Contador diario de Firebase reseteado');
  }

  /**
   * Limpiar caché
   */
  clearCache() {
    this.cache.clear();
    this.lastSync.clear();
    console.log('🧹 Caché de Firebase limpiado');
  }

  /**
   * Obtener recomendaciones de optimización
   */
  getOptimizationTips() {
    const stats = this.getUsageStats();
    const tips = [];

    if (stats.reads.usage > 70) {
      tips.push('📖 Considera aumentar el intervalo de sincronización');
      tips.push('📖 Implementa más caché local');
    }

    if (stats.writes.usage > 70) {
      tips.push('✍️ Usa sincronización en lotes');
      tips.push('✍️ Reduce la frecuencia de actualizaciones');
    }

    if (stats.status === 'critical') {
      tips.push('🚨 Considera actualizar al plan Blaze de Firebase');
      tips.push('🚨 Implementa modo offline temporal');
    }

    return tips;
  }
}

// Crear instancia global
window.FirebaseOptimizer = new FirebaseOptimizerSimple();
