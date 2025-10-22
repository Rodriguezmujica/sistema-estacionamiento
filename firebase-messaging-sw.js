// firebase-messaging-sw.js
// Service Worker para Firebase Cloud Messaging
importScripts('https://www.gstatic.com/firebasejs/12.4.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.4.0/firebase-messaging-compat.js');

const firebaseConfig = {
  apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
  authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
  projectId: "sistemaestacionamiento-46735",
  storageBucket: "sistemaestacionamiento-46735.firebasestorage.app",
  messagingSenderId: "570161231939",
  appId: "1:570161231939:web:50a5f88fcd65e98fa03cf6"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Escuchar mensajes de FCM en background
messaging.onBackgroundMessage((payload) => {
  console.log('📨 Mensaje recibido en background:', payload);
  
  const notificationTitle = payload.notification?.title || 'Notificación TUU';
  const notificationOptions = {
    body: payload.notification?.body || 'Pago confirmado',
    icon: '/imagenes/logo.png',
    badge: '/imagenes/logo.png',
    tag: 'tuu-payment',
    requireInteraction: true,
    actions: [
      {
        action: 'view',
        title: 'Ver Detalles'
      },
      {
        action: 'close',
        title: 'Cerrar'
      }
    ]
  };
  
  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Manejar clics en notificaciones
self.addEventListener('notificationclick', (event) => {
  console.log('🔔 Notificación clickeada:', event);
  
  event.notification.close();
  
  if (event.action === 'view') {
    // Abrir la aplicación
    event.waitUntil(
      clients.openWindow('/sistemaEstacionamiento/index.php')
    );
  }
});
