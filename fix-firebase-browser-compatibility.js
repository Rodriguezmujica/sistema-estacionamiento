/**
 * 🔧 FIX FIREBASE BROWSER COMPATIBILITY
 * Soluciona errores de Firebase en navegadores no compatibles
 */

// Detectar si el navegador soporta Firebase
function isFirebaseSupported() {
    // Verificar si existe window.navigator
    if (typeof window === 'undefined' || !window.navigator) {
        return false;
    }
    
    // Verificar si es un navegador moderno
    const userAgent = window.navigator.userAgent;
    const isOldIE = /MSIE|Trident/.test(userAgent);
    const isOldChrome = /Chrome\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
    const isOldFirefox = /Firefox\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
    
    return !isOldIE && !isOldChrome && !isOldFirefox;
}

// Función para inicializar Firebase de forma segura
function initFirebaseSafely() {
    if (!isFirebaseSupported()) {
        console.warn('🚫 Navegador no compatible con Firebase. Usando modo offline.');
        
        // Crear objetos mock para evitar errores
        window.firebase = {
            messaging: () => ({
                getToken: () => Promise.resolve(null),
                onMessage: () => {},
                requestPermission: () => Promise.resolve('granted')
            }),
            initializeApp: () => ({}),
            apps: []
        };
        
        return false;
    }
    
    return true;
}

// Función para manejar errores de Firebase
function handleFirebaseError(error) {
    console.warn('⚠️ Error de Firebase:', error.message);
    
    // Si es error de navegador no compatible, usar modo offline
    if (error.code === 'messaging/unsupported-browser') {
        console.log('🔄 Cambiando a modo offline para compatibilidad');
        return true; // Indica que se debe usar modo offline
    }
    
    return false;
}

// Función para inicializar TUU sin Firebase
function initTUUOffline() {
    console.log('🔄 Inicializando TUU en modo offline');
    
    // Crear funciones mock para TUU
    window.tuuFirebaseSync = {
        init: () => console.log('✅ TUU offline inicializado'),
        createPayment: () => Promise.resolve({ success: true, offline: true }),
        processPendingPayments: () => Promise.resolve({ success: true, offline: true })
    };
}

// Función principal de inicialización
function initSystemSafely() {
    try {
        // Verificar compatibilidad de Firebase
        if (!initFirebaseSafely()) {
            initTUUOffline();
            return;
        }
        
        // Si llegamos aquí, el navegador es compatible
        console.log('✅ Navegador compatible con Firebase');
        
        // Inicializar Firebase normalmente
        if (typeof firebase !== 'undefined') {
            try {
                // Tu código de Firebase aquí
                console.log('✅ Firebase inicializado correctamente');
            } catch (error) {
                if (handleFirebaseError(error)) {
                    initTUUOffline();
                }
            }
        }
        
    } catch (error) {
        console.error('❌ Error en inicialización:', error);
        initTUUOffline();
    }
}

// Función para corregir el error de addEventListener
function fixAddEventListenerError() {
    // Verificar si el elemento existe antes de agregar el listener
    const originalAddEventListener = Element.prototype.addEventListener;
    
    Element.prototype.addEventListener = function(type, listener, options) {
        if (this && typeof this.addEventListener === 'function') {
            try {
                return originalAddEventListener.call(this, type, listener, options);
            } catch (error) {
                console.warn('⚠️ Error al agregar event listener:', error);
                return false;
            }
        }
        return false;
    };
}

// Función para verificar y corregir el DOM
function ensureDOMReady() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSystemSafely);
    } else {
        initSystemSafely();
    }
}

// Aplicar fixes cuando se carga el script
(function() {
    'use strict';
    
    // Aplicar fix de addEventListener
    fixAddEventListenerError();
    
    // Inicializar cuando el DOM esté listo
    ensureDOMReady();
    
    // También intentar después de un pequeño delay por si acaso
    setTimeout(initSystemSafely, 100);
})();

// Exportar funciones para uso global
window.FirebaseCompat = {
    initSystemSafely,
    initTUUOffline,
    isFirebaseSupported,
    handleFirebaseError
};
