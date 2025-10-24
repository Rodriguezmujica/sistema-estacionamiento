/**
 * 🔥 FIREBASE MESSAGING SEGURO
 * Sistema de Estacionamiento Los Ríos
 * 
 * Inicialización segura de Firebase Messaging con validación de compatibilidad
 */

// Función para validar compatibilidad de Firebase Messaging
function isFirebaseMessagingCompatible() {
    try {
        if (typeof window === "undefined" || !window.navigator) {
            return false;
        }
        
        const userAgent = window.navigator.userAgent;
        
        // Verificar navegadores incompatibles
        const isOldIE = /MSIE|Trident/.test(userAgent);
        const isOldChrome = /Chrome\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
        const isOldFirefox = /Firefox\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
        const isOldSafari = /Safari\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 10;
        const isOldEdge = /Edge\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 79;
        
        // Verificar APIs requeridas para Firebase Messaging
        const hasServiceWorker = "serviceWorker" in navigator;
        const hasNotification = "Notification" in window;
        const hasPushManager = "PushManager" in window;
        const hasIndexedDB = "indexedDB" in window;
        
        // Verificar si es Windows 7 con Chrome viejo
        const isWindows7 = /Windows NT 6\.1/.test(userAgent);
        const isOldChromeOnWin7 = isWindows7 && isOldChrome;
        
        // Verificar si es Internet Explorer
        const isInternetExplorer = /MSIE|Trident/.test(userAgent);
        
        // Firebase Messaging requiere:
        // 1. Service Worker
        // 2. Notifications API
        // 3. Push Manager
        // 4. IndexedDB
        // 5. Navegador moderno
        const hasRequiredAPIs = hasServiceWorker && hasNotification && hasPushManager && hasIndexedDB;
        const isModernBrowser = !isOldIE && !isOldChrome && !isOldFirefox && !isOldSafari && !isOldEdge && !isInternetExplorer && !isOldChromeOnWin7;
        
        return hasRequiredAPIs && isModernBrowser;
    } catch (e) {
        return false;
    }
}

// Función para inicializar Firebase Messaging de forma segura
async function initFirebaseMessagingSafely(firebaseConfig) {
    try {
        // Validar compatibilidad antes de proceder
        if (!isFirebaseMessagingCompatible()) {
            console.warn("🚫 Navegador no compatible con Firebase Messaging. Continuando en modo offline.");
            console.warn("ℹ️ La sincronización con Firebase Realtime Database/Firestore seguirá funcionando normalmente.");
            return null;
        }
        
        // Importar Firebase Messaging
        const { getMessaging, getToken, onMessage } = await import("https://www.gstatic.com/firebasejs/12.4.0/firebase-messaging.js");
        
        // Obtener la app de Firebase (debe estar inicializada previamente)
        const { getApp } = await import("https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js");
        const app = getApp();
        
        // Inicializar Firebase Messaging
        const messaging = getMessaging(app);
        
        console.log("✅ Firebase Messaging inicializado correctamente");
        return {
            messaging,
            getToken,
            onMessage
        };
        
    } catch (error) {
        console.warn("⚠️ Error inicializando Firebase Messaging:", error);
        return null;
    }
}

// Función para configurar notificaciones de forma segura
async function setupFirebaseNotifications(messaging, vapidKey) {
    try {
        if (!messaging) {
            console.warn("🚫 Firebase Messaging no disponible. Saltando configuración de notificaciones.");
            return false;
        }
        
        // Solicitar permisos de notificación
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log("✅ Permisos de notificación concedidos");
            
            // Obtener token FCM
            const token = await getToken(messaging, { vapidKey });
            
            if (token) {
                console.log("🔑 Token FCM obtenido:", token);
                return token;
            } else {
                console.warn("⚠️ No se pudo obtener token FCM");
                return false;
            }
        } else {
            console.warn("❌ Permisos de notificación denegados");
            return false;
        }
        
    } catch (error) {
        console.warn("⚠️ Error configurando notificaciones:", error);
        return false;
    }
}

// Función para escuchar mensajes de forma segura
function setupMessageListener(messaging, onMessageCallback) {
    try {
        if (!messaging) {
            console.warn("🚫 Firebase Messaging no disponible. Saltando listener de mensajes.");
            return false;
        }
        
        // Configurar listener de mensajes
        onMessage(messaging, (payload) => {
            console.log("📨 Mensaje FCM recibido:", payload);
            if (onMessageCallback) {
                onMessageCallback(payload);
            }
        });
        
        console.log("✅ Listener de mensajes FCM configurado");
        return true;
        
    } catch (error) {
        console.warn("⚠️ Error configurando listener de mensajes:", error);
        return false;
    }
}

// Exportar funciones para uso global
window.FirebaseMessagingSafe = {
    isFirebaseMessagingCompatible,
    initFirebaseMessagingSafely,
    setupFirebaseNotifications,
    setupMessageListener
};

// Auto-inicializar si está disponible
if (typeof window !== 'undefined') {
    console.log("🔥 Firebase Messaging Safe cargado");
}
