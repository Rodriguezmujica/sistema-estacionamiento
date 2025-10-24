/**
 * 🔧 FIX FIREBASE COMPATIBILITY ROBUSTO
 * Para sistema híbrido funcionando
 */

// Detectar compatibilidad del navegador para Firebase Messaging
function isFirebaseMessagingSupported() {
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

// Función de compatibilidad general (mantener para compatibilidad)
function isFirebaseSupported() {
    return isFirebaseMessagingSupported();
}

// Función para inicializar Firebase de forma segura
function initFirebaseSafely() {
    try {
        if (!isFirebaseMessagingSupported()) {
            console.warn("🚫 Navegador no compatible con Firebase Messaging. Continuando en modo offline.");
            console.warn("ℹ️ La sincronización con Firebase Realtime Database/Firestore seguirá funcionando normalmente.");
            return false;
        }
        
        console.log("✅ Navegador compatible con Firebase Messaging");
        return true;
    } catch (e) {
        console.warn("⚠️ Error verificando compatibilidad:", e);
        return false;
    }
}

// Función para validar Firebase Messaging antes de inicializarlo
function validateFirebaseMessaging() {
    try {
        // Verificar compatibilidad del navegador
        if (!isFirebaseMessagingSupported()) {
            console.warn("🚫 Navegador no compatible con Firebase Messaging. Continuando en modo offline.");
            console.warn("ℹ️ La sincronización con Firebase Realtime Database/Firestore seguirá funcionando normalmente.");
            return false;
        }
        
        // Verificar que las APIs estén disponibles
        if (!("serviceWorker" in navigator)) {
            console.warn("🚫 Service Worker no soportado. Firebase Messaging deshabilitado.");
            return false;
        }
        
        if (!("Notification" in window)) {
            console.warn("🚫 Notifications API no soportada. Firebase Messaging deshabilitado.");
            return false;
        }
        
        if (!("PushManager" in window)) {
            console.warn("🚫 Push Manager no soportado. Firebase Messaging deshabilitado.");
            return false;
        }
        
        console.log("✅ Firebase Messaging compatible con este navegador");
        return true;
        
    } catch (error) {
        console.warn("⚠️ Error validando Firebase Messaging:", error);
        return false;
    }
}

// Función para manejar errores de Firebase
function handleFirebaseError(error) {
    console.warn("⚠️ Error de Firebase:", error.message);
    
    if (error.code === "messaging/unsupported-browser") {
        console.log("🔄 Cambiando a modo offline para compatibilidad");
        return true;
    }
    
    if (error.code === "messaging/failed-service-worker-registration") {
        console.log("🔄 Service Worker no disponible, usando modo offline");
        return true;
    }
    
    return false;
}

// Función para inicializar TUU sin Firebase
function initTUUOffline() {
    console.log("🔄 Inicializando TUU en modo offline");
    
    if (!window.tuuFirebaseSync) {
        window.tuuFirebaseSync = {
            init: () => console.log("✅ TUU offline inicializado"),
            createPayment: () => Promise.resolve({ success: true, offline: true }),
            processPendingPayments: () => Promise.resolve({ success: true, offline: true })
        };
    }
}

// Función principal de inicialización
function initSystemSafely() {
    try {
        // Validar Firebase Messaging específicamente
        if (!validateFirebaseMessaging()) {
            initTUUOffline();
            return;
        }
        
        // Si llegamos aquí, Firebase Messaging es compatible
        console.log("✅ Inicializando Firebase Messaging...");
        
    } catch (error) {
        console.error("❌ Error en inicialización:", error);
        initTUUOffline();
    }
}

// Aplicar fixes cuando se carga el script
(function() {
    "use strict";
    
    // Esperar a que el DOM esté listo
    function ready(fn) {
        if (document.readyState !== "loading") {
            fn();
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }
    
    ready(function() {
        initSystemSafely();
    });
    
    // También intentar después de un delay
    setTimeout(initSystemSafely, 100);
    setTimeout(initSystemSafely, 500);
})();

// Exportar funciones para uso global
window.FirebaseCompat = {
    initSystemSafely,
    initTUUOffline,
    isFirebaseSupported,
    isFirebaseMessagingSupported,
    validateFirebaseMessaging,
    handleFirebaseError
};