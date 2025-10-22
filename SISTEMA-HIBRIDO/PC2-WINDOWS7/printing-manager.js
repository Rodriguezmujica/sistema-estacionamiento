/**
 * 🖨️ GESTOR DE IMPRESIÓN
 * Sistema de Estacionamiento Los Ríos
 * 
 * Maneja la impresión en PC2 (Windows 7) con impresora USB
 */

import { systemConfig } from './firebase-config.js';

class PrintingManager {
  constructor() {
    this.isEnabled = systemConfig.printing.enabled;
    this.isPC2 = systemConfig.pcId === 'PC2_WINDOWS7';
    this.printerAvailable = false;
    this.printQueue = [];
    this.isPrinting = false;
    
    // Configuración de impresión
    this.config = {
      retryAttempts: 3,
      retryDelay: 2000,
      timeout: 10000,
      paperWidth: 80, // caracteres
      paperHeight: 1000 // líneas
    };
    
    // Inicializar
    this.init();
  }
  
  /**
   * Inicializar el gestor de impresión
   */
  init() {
    console.log('🖨️ Iniciando gestor de impresión...');
    
    if (!this.isEnabled) {
      console.log('⏸️ Impresión deshabilitada');
      return;
    }
    
    if (!this.isPC2) {
      console.log('⏸️ Esta PC no tiene impresora');
      return;
    }
    
    // Verificar disponibilidad de impresora
    this.checkPrinterAvailability();
    
    // Escuchar eventos de cambio de PC
    window.addEventListener('pcStatusChange', (event) => {
      this.handlePCStatusChange(event.detail);
    });
    
    console.log('✅ Gestor de impresión iniciado');
  }
  
  /**
   * Verificar disponibilidad de impresora
   */
  async checkPrinterAvailability() {
    try {
      // Verificar si hay impresoras disponibles
      const response = await fetch('api/check-printer.php');
      const data = await response.json();
      
      this.printerAvailable = data.available;
      
      if (this.printerAvailable) {
        console.log('✅ Impresora disponible');
      } else {
        console.log('❌ Impresora no disponible');
      }
    } catch (error) {
      console.error('❌ Error verificando impresora:', error);
      this.printerAvailable = false;
    }
  }
  
  /**
   * Manejar cambio de estado de PC
   */
  handlePCStatusChange(detail) {
    if (detail.isActive && this.isPC2) {
      // Esta PC es ahora activa - activar impresión
      this.activatePrinting();
    } else {
      // Esta PC ya no es activa - desactivar impresión
      this.deactivatePrinting();
    }
  }
  
  /**
   * Activar impresión
   */
  activatePrinting() {
    console.log('🖨️ Activando impresión...');
    this.isEnabled = true;
    this.processPrintQueue();
  }
  
  /**
   * Desactivar impresión
   */
  deactivatePrinting() {
    console.log('🖨️ Desactivando impresión...');
    this.isEnabled = false;
  }
  
  /**
   * Imprimir ticket de estacionamiento
   */
  async printTicket(ticketData) {
    if (!this.canPrint()) {
      console.log('⏸️ No se puede imprimir en este momento');
      return false;
    }
    
    const printData = this.formatTicket(ticketData);
    return await this.print(printData, 'ticket');
  }
  
  /**
   * Imprimir servicio de lavado
   */
  async printServicioLavado(servicioData) {
    if (!this.canPrint()) {
      console.log('⏸️ No se puede imprimir en este momento');
      return false;
    }
    
    const printData = this.formatServicioLavado(servicioData);
    return await this.print(printData, 'servicio');
  }
  
  /**
   * Imprimir reporte
   */
  async printReporte(reporteData) {
    if (!this.canPrint()) {
      console.log('⏸️ No se puede imprimir en este momento');
      return false;
    }
    
    const printData = this.formatReporte(reporteData);
    return await this.print(printData, 'reporte');
  }
  
  /**
   * Verificar si se puede imprimir
   */
  canPrint() {
    return this.isEnabled && 
           this.isPC2 && 
           this.printerAvailable && 
           !this.isPrinting;
  }
  
  /**
   * Imprimir datos
   */
  async print(data, type) {
    if (!this.canPrint()) {
      // Agregar a cola si no se puede imprimir ahora
      this.addToQueue(data, type);
      return false;
    }
    
    this.isPrinting = true;
    
    try {
      console.log(`🖨️ Imprimiendo ${type}...`);
      
      const response = await fetch('api/print.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          data: data,
          type: type,
          timestamp: new Date().toISOString()
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        console.log(`✅ ${type} impreso exitosamente`);
        return true;
      } else {
        console.error(`❌ Error imprimiendo ${type}:`, result.error);
        return false;
      }
      
    } catch (error) {
      console.error(`❌ Error imprimiendo ${type}:`, error);
      return false;
    } finally {
      this.isPrinting = false;
      
      // Procesar cola después de imprimir
      setTimeout(() => {
        this.processPrintQueue();
      }, 1000);
    }
  }
  
  /**
   * Formatear ticket para impresión
   */
  formatTicket(ticket) {
    const lines = [];
    const width = this.config.paperWidth;
    
    // Encabezado
    lines.push('='.repeat(width));
    lines.push('ESTACIONAMIENTO LOS RÍOS'.padStart((width + 22) / 2));
    lines.push('='.repeat(width));
    lines.push('');
    
    // Información del ticket
    lines.push(`TICKET: ${ticket.id}`.padEnd(width));
    lines.push(`FECHA: ${new Date(ticket.fecha_ingreso).toLocaleString()}`.padEnd(width));
    lines.push(`PATENTE: ${ticket.patente}`.padEnd(width));
    lines.push(`SERVICIO: ${ticket.tipo_servicio}`.padEnd(width));
    
    if (ticket.cliente_nombre) {
      lines.push(`CLIENTE: ${ticket.cliente_nombre}`.padEnd(width));
    }
    
    lines.push('');
    lines.push('-'.repeat(width));
    lines.push('');
    
    // Estado
    if (ticket.pagado) {
      lines.push('ESTADO: PAGADO'.padEnd(width));
      lines.push(`TOTAL: $${ticket.precio}`.padEnd(width));
    } else {
      lines.push('ESTADO: PENDIENTE DE PAGO'.padEnd(width));
    }
    
    lines.push('');
    lines.push('='.repeat(width));
    lines.push('Gracias por su visita'.padStart((width + 19) / 2));
    lines.push('='.repeat(width));
    
    return lines.join('\n');
  }
  
  /**
   * Formatear servicio de lavado para impresión
   */
  formatServicioLavado(servicio) {
    const lines = [];
    const width = this.config.paperWidth;
    
    // Encabezado
    lines.push('='.repeat(width));
    lines.push('SERVICIO DE LAVADO'.padStart((width + 18) / 2));
    lines.push('='.repeat(width));
    lines.push('');
    
    // Información del servicio
    lines.push(`SERVICIO: ${servicio.id}`.padEnd(width));
    lines.push(`FECHA: ${new Date(servicio.fecha_servicio).toLocaleString()}`.padEnd(width));
    lines.push(`PATENTE: ${servicio.patente}`.padEnd(width));
    lines.push(`TIPO: ${servicio.tipo_lavado}`.padEnd(width));
    
    if (servicio.cliente_nombre) {
      lines.push(`CLIENTE: ${servicio.cliente_nombre}`.padEnd(width));
    }
    
    lines.push('');
    lines.push('-'.repeat(width));
    lines.push('');
    
    // Precios
    lines.push(`PRECIO BASE: $${servicio.precio_base}`.padEnd(width));
    
    if (servicio.precio_extra > 0) {
      lines.push(`PRECIO EXTRA: $${servicio.precio_extra}`.padEnd(width));
    }
    
    const total = servicio.precio_base + servicio.precio_extra;
    lines.push(`TOTAL: $${total}`.padEnd(width));
    
    // Motivos extra
    if (servicio.motivos_extra && servicio.motivos_extra.length > 0) {
      lines.push('');
      lines.push('MOTIVOS EXTRA:'.padEnd(width));
      servicio.motivos_extra.forEach(motivo => {
        lines.push(`- ${motivo}`.padEnd(width));
      });
    }
    
    lines.push('');
    lines.push('='.repeat(width));
    lines.push('Gracias por su visita'.padStart((width + 19) / 2));
    lines.push('='.repeat(width));
    
    return lines.join('\n');
  }
  
  /**
   * Formatear reporte para impresión
   */
  formatReporte(reporte) {
    const lines = [];
    const width = this.config.paperWidth;
    
    // Encabezado
    lines.push('='.repeat(width));
    lines.push('REPORTE DEL SISTEMA'.padStart((width + 19) / 2));
    lines.push('='.repeat(width));
    lines.push('');
    
    // Información del reporte
    lines.push(`FECHA: ${new Date().toLocaleString()}`.padEnd(width));
    lines.push(`TIPO: ${reporte.tipo}`.padEnd(width));
    lines.push('');
    
    // Contenido del reporte
    if (reporte.contenido) {
      lines.push('-'.repeat(width));
      lines.push(reporte.contenido);
      lines.push('-'.repeat(width));
    }
    
    lines.push('');
    lines.push('='.repeat(width));
    
    return lines.join('\n');
  }
  
  /**
   * Agregar a cola de impresión
   */
  addToQueue(data, type) {
    this.printQueue.push({
      data,
      type,
      timestamp: new Date(),
      attempts: 0
    });
    
    console.log(`📝 Agregado a cola de impresión: ${type}`);
  }
  
  /**
   * Procesar cola de impresión
   */
  async processPrintQueue() {
    if (this.printQueue.length === 0 || !this.canPrint()) {
      return;
    }
    
    const item = this.printQueue.shift();
    
    try {
      const success = await this.print(item.data, item.type);
      
      if (!success) {
        item.attempts++;
        
        if (item.attempts < this.config.retryAttempts) {
          // Reintentar más tarde
          setTimeout(() => {
            this.printQueue.unshift(item);
          }, this.config.retryDelay);
        } else {
          console.error(`❌ Máximo de intentos alcanzado para ${item.type}`);
        }
      }
    } catch (error) {
      console.error(`❌ Error procesando cola de impresión:`, error);
    }
  }
  
  /**
   * Obtener estado de impresión
   */
  getStatus() {
    return {
      isEnabled: this.isEnabled,
      isPC2: this.isPC2,
      printerAvailable: this.printerAvailable,
      isPrinting: this.isPrinting,
      queueLength: this.printQueue.length,
      canPrint: this.canPrint()
    };
  }
  
  /**
   * Limpiar cola de impresión
   */
  clearQueue() {
    this.printQueue = [];
    console.log('🗑️ Cola de impresión limpiada');
  }
}

// Crear instancia global
const printingManager = new PrintingManager();

// Exportar para uso global
window.printingManager = printingManager;
export default printingManager;
