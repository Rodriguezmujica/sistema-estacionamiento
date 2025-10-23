// Firebase Service Worker - Sistema Híbrido
importScripts("https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js");

const firebaseConfig = {
  apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
  authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
  projectId: "sistemaestacionamiento-46735",
  storageBucket: "sistemaestacionamiento-46735.appspot.com",
  messagingSenderId: "570161231939",
  appId: "1:570161231939:web:your-app-id"
};

// Validar compatibilidad antes de inicializar Firebase Messaging
function isFirebaseMessagingSupported() {
  try {
    // Verificar si estamos en un contexto de Service Worker
    if (typeof self === 'undefined' || !self.registration) {
      return false;
    }
    
    // Verificar si Firebase está disponible
    if (typeof firebase === 'undefined' || !firebase.messaging) {
      return false;
    }
    
    return true;
  } catch (error) {
    return false;
  }
}

// Inicializar Firebase de forma segura
try {
  firebase.initializeApp(firebaseConfig);
  
  // Verificar compatibilidad antes de usar messaging
  if (isFirebaseMessagingSupported()) {
    const messaging = firebase.messaging();
    console.log("✅ Firebase Messaging inicializado en Service Worker");
    
    // Configurar listener de mensajes solo si messaging está disponible
    messaging.onBackgroundMessage(function(payload) {
      console.log("Mensaje recibido en background:", payload);
      
      const notificationTitle = payload.notification?.title || "Sistema Estacionamiento";
      const notificationOptions = {
        body: payload.notification?.body || "Nueva notificación",
        icon: "/imagenes/Logo_sin_fondo.png",
        badge: "/imagenes/Logo_sin_fondo.png"
      };
      
      self.registration.showNotification(notificationTitle, notificationOptions);
    });
  } else {
    console.warn("🧩 Firebase Messaging deshabilitado (modo seguro)");
  }
} catch (error) {
  console.warn("🧩 Firebase Messaging deshabilitado (modo seguro):", error.message);
}