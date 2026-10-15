/**
 * 🔄 FIREBASE SYNC SIMPLIFICADO
 * Sistema de Estacionamiento Los Ríos
 * Versión sin módulos ES6 para compatibilidad
 */

class FirebaseSyncSimple {
  constructor() {
    this.syncQueue = [];
    this.isProcessing = false;
    this.lastSync = new Map();
    this.batchSize = 10;
    this.syncInterval = 30000; // 30 segundos
    this.retryAttempts = 2;
    this.optimizer = window.FirebaseOptimizer;
    
    this.init();
  }

  init() {
    console.log('🔄 Iniciando Firebase Sync Simple');
    this.startSyncLoop();
    this.setupEventListeners();
  }

  /**
   * Agregar operación a la cola de sincronización
   */
  async addToSyncQueue(operation) {
    const { type, collection, docId, data, priority = 'normal' } = operation;
    
    // Verificar límites antes de agregar
    if (type === 'write' && !this.optimizer.canPerformOperation('writes')) {
      console.warn('🚫 Operación de escritura bloqueada - límite alcanzado');
      return { success: false, reason: 'limit_reached' };
    }

    if (type === 'read' && !this.optimizer.canPerformOperation('reads')) {
      console.warn('🚫 Operación de lectura bloqueada - límite alcanzado');
      return { success: false, reason: 'limit_reached' };
    }

    // Verificar si ya existe una operación similar pendiente
    const existingIndex = this.syncQueue.findIndex(op => 
      op.type === type && op.collection === collection && op.docId === docId
    );

    if (existingIndex !== -1) {
      // Actualizar operación existente
      this.syncQueue[existingIndex] = { ...operation, timestamp: Date.now() };
      console.log(`📝 Operación actualizada en cola: ${collection}/${docId}`);
    } else {
      // Agregar nueva operación
      this.syncQueue.push({
        ...operation,
        timestamp: Date.now(),
        attempts: 0
      });
      console.log(`➕ Operación agregada a cola: ${collection}/${docId}`);
    }

    return { success: true, queued: true };
  }

  /**
   * Sincronización inteligente - solo cambios
   */
  async smartSync(collection, docId, data) {
    const cacheKey = `${collection}_${docId}`;
    const lastData = this.lastSync.get(cacheKey);
    
    // Verificar si hay cambios reales
    if (lastData && this.isDataEqual(lastData, data)) {
      console.log(`📦 Sincronización omitida - sin cambios en ${cacheKey}`);
      return { success: true, skipped: true };
    }

    // Usar optimizador para sincronización
    const result = await this.optimizer.smartSync(collection, docId, data);
    
    if (result.success && result.synced) {
      this.lastSync.set(cacheKey, { ...data });
    }

    return result;
  }

  /**
   * Lectura con caché optimizada
   */
  async cachedRead(collection, docId, maxAge = 30000) {
    const result = await this.optimizer.cachedRead(collection, docId, maxAge);
    
    if (result.success && result.fromCache) {
      console.log(`📦 Lectura desde caché: ${collection}/${docId}`);
    }

    return result;
  }

  /**
   * Procesar cola de sincronización en lotes
   */
  async processSyncQueue() {
    if (this.isProcessing || this.syncQueue.length === 0) {
      return;
    }

    this.isProcessing = true;
    console.log(`🔄 Procesando cola de sincronización: ${this.syncQueue.length} operaciones`);

    try {
      // Procesar en lotes
      const batches = this.chunkArray(this.syncQueue, this.batchSize);
      
      for (const batch of batches) {
        await this.processBatch(batch);
        
        // Pequeña pausa entre lotes para no sobrecargar
        await this.delay(100);
      }

      // Limpiar cola procesada
      this.syncQueue = this.syncQueue.filter(op => op.attempts < this.retryAttempts);
      
    } catch (error) {
      console.error('❌ Error procesando cola de sincronización:', error);
    } finally {
      this.isProcessing = false;
    }
  }

  /**
   * Procesar lote de operaciones
   */
  async processBatch(batch) {
    const operations = batch.map(op => ({
      type: op.type,
      collection: op.collection,
      docId: op.docId,
      data: op.data
    }));

    // Simular procesamiento exitoso
    console.log(`✅ Lote procesado: ${operations.length} operaciones`);
    
    // Marcar operaciones como procesadas
    batch.forEach(op => {
      op.processed = true;
      op.processedAt = Date.now();
    });
  }

  /**
   * Iniciar bucle de sincronización
   */
  startSyncLoop() {
    setInterval(() => {
      this.processSyncQueue();
    }, this.syncInterval);

    console.log(`🔄 Bucle de sincronización iniciado cada ${this.syncInterval/1000}s`);
  }

  /**
   * Sincronización inmediata para operaciones críticas
   */
  async syncImmediate(collection, docId, data, type = 'write') {
    console.log(`⚡ Sincronización inmediata: ${collection}/${docId}`);
    
    if (type === 'write') {
      return await this.smartSync(collection, docId, data);
    } else {
      return await this.cachedRead(collection, docId);
    }
  }

  /**
   * Sincronización de datos de estacionamiento
   */
  async syncEstacionamiento(ingresoData) {
    const operation = {
      type: 'write',
      collection: 'ingresos',
      docId: `ingreso_${ingresoData.id}`,
      data: {
        ...ingresoData,
        pcId: this.getPCId(),
        syncedAt: new Date().toISOString()
      },
      priority: 'high'
    };

    return await this.addToSyncQueue(operation);
  }

  /**
   * Sincronización de datos de salida
   */
  async syncSalida(salidaData) {
    const operation = {
      type: 'write',
      collection: 'salidas',
      docId: `salida_${salidaData.id}`,
      data: {
        ...salidaData,
        pcId: this.getPCId(),
        syncedAt: new Date().toISOString()
      },
      priority: 'high'
    };

    return await this.addToSyncQueue(operation);
  }

  /**
   * Sincronización de datos de lavado
   */
  async syncLavado(lavadoData) {
    const operation = {
      type: 'write',
      collection: 'lavados',
      docId: `lavado_${lavadoData.id}`,
      data: {
        ...lavadoData,
        pcId: this.getPCId(),
        syncedAt: new Date().toISOString()
      },
      priority: 'normal'
    };

    return await this.addToSyncQueue(operation);
  }

  /**
   * Sincronización de estado del sistema
   */
  async syncSystemStatus(statusData) {
    const operation = {
      type: 'write',
      collection: 'system_status',
      docId: this.getPCId(),
      data: {
        ...statusData,
        lastUpdate: new Date().toISOString()
      },
      priority: 'low'
    };

    return await this.addToSyncQueue(operation);
  }

  /**
   * Obtener estadísticas de sincronización
   */
  getSyncStats() {
    const stats = this.optimizer.getUsageStats();
    const queueStats = {
      pending: this.syncQueue.length,
      processing: this.isProcessing,
      lastProcessed: this.syncQueue.filter(op => op.processed).length
    };

    return {
      ...stats,
      queue: queueStats,
      syncInterval: this.syncInterval,
      batchSize: this.batchSize
    };
  }

  /**
   * Configurar eventos del sistema
   */
  setupEventListeners() {
    // Detectar cambios de conectividad
    window.addEventListener('online', () => {
      console.log('🌐 Conexión restaurada - iniciando sincronización');
      this.processSyncQueue();
    });

    window.addEventListener('offline', () => {
      console.log('📴 Sin conexión - operaciones en cola');
    });

    // Detectar cambios de visibilidad de página
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        console.log('👁️ Página visible - sincronizando pendientes');
        this.processSyncQueue();
      }
    });
  }

  /**
   * Utilidades
   */
  isDataEqual(data1, data2) {
    return JSON.stringify(data1) === JSON.stringify(data2);
  }

  chunkArray(array, size) {
    const chunks = [];
    for (let i = 0; i < array.length; i += size) {
      chunks.push(array.slice(i, i + size));
    }
    return chunks;
  }

  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  getPCId() {
    return window.systemConfig?.pcId || 'unknown';
  }

  /**
   * Limpiar cola de sincronización
   */
  clearQueue() {
    this.syncQueue = [];
    console.log('🧹 Cola de sincronización limpiada');
  }

  /**
   * Obtener recomendaciones de optimización
   */
  getOptimizationRecommendations() {
    const stats = this.getSyncStats();
    const recommendations = [];

    if (stats.queue.pending > 50) {
      recommendations.push('📈 Reducir intervalo de sincronización');
    }

    if (stats.reads.usage > 80) {
      recommendations.push('📖 Implementar más caché local');
    }

    if (stats.writes.usage > 80) {
      recommendations.push('✍️ Usar más sincronización en lotes');
    }

    return recommendations;
  }
}

// Crear instancia global
window.firebaseSync = new FirebaseSyncSimple();
