/**
 * 🔔 NOTIFICACIONES DE SINCRONIZACIÓN
 * Sistema de Estacionamiento Los Ríos
 * 
 * Notifica cuando Firebase recibe datos y cuando Antix los procesa
 */

class SyncNotifications {
  constructor() {
    this.notifications = [];
    this.isEnabled = true;
    this.soundEnabled = true;
    
    this.init();
  }

  init() {
    console.log('🔔 Iniciando sistema de notificaciones de sincronización');
    this.setupNotificationPermission();
    this.setupEventListeners();
  }

  /**
   * Solicitar permisos de notificación
   */
  async setupNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
      try {
        await Notification.requestPermission();
        console.log('🔔 Permisos de notificación:', Notification.permission);
      } catch (error) {
        console.warn('Error solicitando permisos de notificación:', error);
      }
    }
  }

  /**
   * Mostrar notificación
   */
  showNotification(title, message, type = 'info') {
    if (!this.isEnabled) return;

    const notification = {
      id: Date.now(),
      title: title,
      message: message,
      type: type,
      timestamp: new Date().toISOString(),
      read: false
    };

    this.notifications.unshift(notification);
    this.notifications = this.notifications.slice(0, 50); // Mantener solo 50

    // Notificación del navegador
    this.showBrowserNotification(title, message, type);

    // Notificación visual en la página
    this.showVisualNotification(notification);

    // Sonido (si está habilitado)
    if (this.soundEnabled) {
      this.playNotificationSound(type);
    }

    // Actualizar contador de notificaciones
    this.updateNotificationCounter();

    console.log('🔔 Notificación:', notification);
  }

  /**
   * Mostrar notificación del navegador
   */
  showBrowserNotification(title, message, type) {
    if ('Notification' in window && Notification.permission === 'granted') {
      const icon = this.getNotificationIcon(type);
      
      const notification = new Notification(title, {
        body: message,
        icon: icon,
        badge: '/imagenes/Logo_sin_fondo.png',
        tag: 'sync-notification',
        requireInteraction: false,
        silent: false
      });

      // Auto-cerrar después de 5 segundos
      setTimeout(() => {
        notification.close();
      }, 5000);

      // Manejar clic en la notificación
      notification.onclick = () => {
        window.focus();
        notification.close();
      };
    }
  }

  /**
   * Mostrar notificación visual en la página
   */
  showVisualNotification(notification) {
    const container = this.getNotificationContainer();
    
    const notificationElement = document.createElement('div');
    notificationElement.className = `alert alert-${this.getBootstrapClass(notification.type)} alert-dismissible fade show notification-item`;
    notificationElement.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      max-width: 350px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      animation: slideInRight 0.3s ease-out;
    `;

    notificationElement.innerHTML = `
      <div class="d-flex align-items-start">
        <i class="${this.getNotificationIcon(notification.type)} me-2 mt-1"></i>
        <div class="flex-grow-1">
          <strong>${notification.title}</strong><br>
          <small>${notification.message}</small>
        </div>
        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
      </div>
    `;

    container.appendChild(notificationElement);

    // Auto-remover después de 8 segundos
    setTimeout(() => {
      if (notificationElement.parentElement) {
        notificationElement.remove();
      }
    }, 8000);
  }

  /**
   * Reproducir sonido de notificación
   */
  playNotificationSound(type) {
    try {
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      
      // Crear diferentes tonos según el tipo
      const frequencies = {
        'success': [523, 659, 784], // Do, Mi, Sol
        'error': [200, 150, 100],    // Tonos graves
        'warning': [440, 330],      // La, Mi
        'info': [440, 554]          // La, Do#
      };

      const freq = frequencies[type] || frequencies['info'];
      
      freq.forEach((frequency, index) => {
        setTimeout(() => {
          const oscillator = audioContext.createOscillator();
          const gainNode = audioContext.createGain();
          
          oscillator.connect(gainNode);
          gainNode.connect(audioContext.destination);
          
          oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
          oscillator.type = 'sine';
          
          gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
          gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
          
          oscillator.start(audioContext.currentTime);
          oscillator.stop(audioContext.currentTime + 0.3);
        }, index * 200);
      });
    } catch (error) {
      console.warn('Error reproduciendo sonido:', error);
    }
  }

  /**
   * Obtener contenedor de notificaciones
   */
  getNotificationContainer() {
    let container = document.getElementById('notification-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'notification-container';
      container.style.cssText = 'position: fixed; top: 0; right: 0; z-index: 9999;';
      document.body.appendChild(container);
    }
    return container;
  }

  /**
   * Actualizar contador de notificaciones
   */
  updateNotificationCounter() {
    const unreadCount = this.notifications.filter(n => !n.read).length;
    const counter = document.getElementById('notification-counter');
    
    if (counter) {
      counter.textContent = unreadCount;
      counter.style.display = unreadCount > 0 ? 'inline' : 'none';
    }
  }

  /**
   * Configurar event listeners
   */
  setupEventListeners() {
    // Escuchar eventos de sincronización
    window.addEventListener('firebase-sync-sent', (event) => {
      this.showNotification(
        '📤 Enviado a Firebase',
        `Operación ${event.detail.collection}/${event.detail.docId} enviada correctamente`,
        'success'
      );
    });

    window.addEventListener('antix-sync-received', (event) => {
      this.showNotification(
        '📥 Recibido por Antix',
        `Operación ${event.detail.collection}/${event.detail.docId} recibida en Antix`,
        'info'
      );
    });

    window.addEventListener('antix-sync-processed', (event) => {
      this.showNotification(
        '✅ Procesado por Antix',
        `Operación ${event.detail.operation.collection}/${event.detail.operation.docId} procesada exitosamente`,
        'success'
      );
    });

    window.addEventListener('sync-error', (event) => {
      this.showNotification(
        '❌ Error de Sincronización',
        `Error en ${event.detail.operation?.collection || 'operación'}: ${event.detail.error}`,
        'error'
      );
    });

    // Escuchar cambios de conectividad
    window.addEventListener('online', () => {
      this.showNotification(
        '🌐 Conexión Restaurada',
        'La conexión a internet se ha restaurado',
        'success'
      );
    });

    window.addEventListener('offline', () => {
      this.showNotification(
        '📴 Sin Conexión',
        'Se ha perdido la conexión a internet',
        'warning'
      );
    });
  }

  /**
   * Obtener icono para tipo de notificación
   */
  getNotificationIcon(type) {
    const icons = {
      'success': 'fas fa-check-circle',
      'error': 'fas fa-exclamation-circle',
      'warning': 'fas fa-exclamation-triangle',
      'info': 'fas fa-info-circle'
    };
    return icons[type] || icons['info'];
  }

  /**
   * Obtener clase de Bootstrap para tipo
   */
  getBootstrapClass(type) {
    const classes = {
      'success': 'success',
      'error': 'danger',
      'warning': 'warning',
      'info': 'info'
    };
    return classes[type] || 'info';
  }

  /**
   * Habilitar/deshabilitar notificaciones
   */
  toggleNotifications() {
    this.isEnabled = !this.isEnabled;
    console.log('🔔 Notificaciones:', this.isEnabled ? 'Habilitadas' : 'Deshabilitadas');
    return this.isEnabled;
  }

  /**
   * Habilitar/deshabilitar sonido
   */
  toggleSound() {
    this.soundEnabled = !this.soundEnabled;
    console.log('🔔 Sonido:', this.soundEnabled ? 'Habilitado' : 'Deshabilitado');
    return this.soundEnabled;
  }

  /**
   * Marcar notificación como leída
   */
  markAsRead(notificationId) {
    const notification = this.notifications.find(n => n.id === notificationId);
    if (notification) {
      notification.read = true;
      this.updateNotificationCounter();
    }
  }

  /**
   * Marcar todas como leídas
   */
  markAllAsRead() {
    this.notifications.forEach(n => n.read = true);
    this.updateNotificationCounter();
  }

  /**
   * Obtener notificaciones no leídas
   */
  getUnreadNotifications() {
    return this.notifications.filter(n => !n.read);
  }

  /**
   * Limpiar notificaciones antiguas
   */
  clearOldNotifications() {
    const cutoff = new Date(Date.now() - 24 * 60 * 60 * 1000); // 24 horas
    this.notifications = this.notifications.filter(n => new Date(n.timestamp) > cutoff);
  }
}

// Crear instancia global
window.SyncNotifications = new SyncNotifications();

// CSS para animaciones
const notificationStyle = document.createElement('style');
notificationStyle.id = 'sync-notifications-style';
notificationStyle.textContent = `
  @keyframes slideInRight {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  .notification-item {
    animation: slideInRight 0.3s ease-out;
  }
`;

// Solo agregar si no existe
if (!document.getElementById('sync-notifications-style')) {
  document.head.appendChild(notificationStyle);
}
