/**
 * 🔧 FIX FIREBASE SIMPLE - Windows 7
 * Soluciona el problema "Firebase no está disponible"
 */

console.log("🔧 Iniciando fix de Firebase...");

// Función para esperar a que Firebase se cargue
function waitForFirebase() {
    return new Promise((resolve) => {
        let attempts = 0;
        const maxAttempts = 50; // 5 segundos máximo
        
        const checkFirebase = () => {
            attempts++;
            
            // Verificar si Firebase está disponible
            if (typeof window !== 'undefined' && 
                window.firebase && 
                window.firebase.initializeApp) {
                console.log("✅ Firebase detectado (compat)");
                resolve('compat');
                return;
            }
            
            // Verificar si los módulos ES6 están disponibles
            if (typeof window !== 'undefined' && 
                window.import && 
                typeof window.import === 'function') {
                console.log("✅ Módulos ES6 disponibles");
                resolve('es6');
                return;
            }
            
            if (attempts >= maxAttempts) {
                console.warn("⚠️ Firebase no disponible después de 5 segundos");
                resolve('offline');
                return;
            }
            
            setTimeout(checkFirebase, 100);
        };
        
        checkFirebase();
    });
}

// Función para configurar Firebase
async function configurarFirebase() {
    try {
        const firebaseType = await waitForFirebase();
        
        if (firebaseType === 'offline') {
            console.log("🔄 Usando modo offline");
            initTUUOffline();
            return;
        }
        
        if (firebaseType === 'compat') {
            console.log("✅ Configurando Firebase compat");
            configurarFirebaseCompat();
            return;
        }
        
        if (firebaseType === 'es6') {
            console.log("✅ Configurando Firebase ES6");
            await configurarFirebaseES6();
            return;
        }
        
    } catch (error) {
        console.error("❌ Error configurando Firebase:", error);
        initTUUOffline();
    }
}

// Configurar Firebase compat (versión antigua)
function configurarFirebaseCompat() {
    try {
        const firebaseConfig = {
            apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
            authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
            projectId: "sistemaestacionamiento-46735",
            storageBucket: "sistemaestacionamiento-46735.appspot.com",
            messagingSenderId: "570161231939",
            appId: "1:570161231939:web:your-app-id"
        };
        
        if (window.firebase && window.firebase.initializeApp) {
            window.firebase.initializeApp(firebaseConfig);
            console.log("✅ Firebase compat inicializado");
        }
    } catch (error) {
        console.error("❌ Error inicializando Firebase compat:", error);
    }
}

// Configurar Firebase ES6 (versión nueva)
async function configurarFirebaseES6() {
    try {
        const firebaseConfig = {
            apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
            authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
            projectId: "sistemaestacionamiento-46735",
            storageBucket: "sistemaestacionamiento-46735.appspot.com",
            messagingSenderId: "570161231939",
            appId: "1:570161231939:web:your-app-id"
        };
        
        // Intentar obtener la app existente
        try {
            const { getApp } = await import("https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js");
            const app = getApp();
            console.log("✅ Firebase app existente encontrada");
        } catch (error) {
            // Si no existe, inicializar
            const { initializeApp } = await import("https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js");
            const app = initializeApp(firebaseConfig);
            console.log("✅ Firebase app inicializada");
        }
        
        // Configurar Service Worker
        try {
            const registration = await navigator.serviceWorker.register("/firebase-messaging-sw.js");
            console.log("✅ Service Worker registrado:", registration);
        } catch (swError) {
            console.warn("⚠️ Error registrando Service Worker:", swError);
        }
        
    } catch (error) {
        console.error("❌ Error configurando Firebase ES6:", error);
        throw error;
    }
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

// Función principal
function initSystem() {
    console.log("🚀 Inicializando sistema...");
    
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', configurarFirebase);
    } else {
        configurarFirebase();
    }
}

// Inicializar inmediatamente
initSystem();

// También intentar después de un delay
setTimeout(configurarFirebase, 1000);
setTimeout(configurarFirebase, 3000);

console.log("🔧 Fix de Firebase cargado");
