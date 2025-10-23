/**
 * 🔥 EJEMPLO DE USO DE FIREBASE MESSAGING SEGURO
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo muestra cómo usar la validación segura de Firebase Messaging
 */

// Ejemplo de inicialización segura de Firebase Messaging
async function initFirebaseMessagingExample() {
    try {
        // 1. Verificar compatibilidad del navegador
        if (!window.FirebaseMessagingSafe.isFirebaseMessagingCompatible()) {
            console.warn("🚫 Navegador no compatible con Firebase Messaging. Continuando en modo offline.");
            console.warn("ℹ️ La sincronización con Firebase Realtime Database/Firestore seguirá funcionando normalmente.");
            return false;
        }
        
        // 2. Inicializar Firebase Messaging de forma segura
        const messagingResult = await window.FirebaseMessagingSafe.initFirebaseMessagingSafely();
        
        if (!messagingResult) {
            console.warn("🚫 No se pudo inicializar Firebase Messaging. Continuando sin notificaciones push.");
            return false;
        }
        
        const { messaging, getToken, onMessage } = messagingResult;
        
        // 3. Configurar notificaciones
        const vapidKey = "BL38f3jX5zj-73XuxYytU9m6bCMKA2mKHcxBwJWUI0u1I_IDfFjAtuUw91DSH1gLEgsLr1XCrdqOp9IqmfK8yDI";
        const token = await window.FirebaseMessagingSafe.setupFirebaseNotifications(messaging, vapidKey);
        
        if (token) {
            console.log("✅ Firebase Messaging configurado correctamente");
            
            // Guardar token en localStorage
            localStorage.setItem('fcm_token', token);
            
            // Guardar token en servidor
            await guardarTokenEnServidor(token);
        }
        
        // 4. Configurar listener de mensajes
        window.FirebaseMessagingSafe.setupMessageListener(messaging, (payload) => {
            console.log("📨 Mensaje FCM recibido:", payload);
            
            // Mostrar notificación
            if (payload.notification) {
                const notification = new Notification(payload.notification.title, {
                    body: payload.notification.body,
                    icon: '/imagenes/Logo_sin_fondo.png',
                    tag: 'tuu-payment',
                    requireInteraction: true
                });
                
                notification.onclick = function() {
                    console.log("🔔 Notificación clickeada");
                    notification.close();
                    
                    // Actualizar estado del pago
                    if (payload.data?.transaction_id) {
                        verificarPagoTUU(payload.data.transaction_id);
                    }
                };
            }
            
            // Actualizar estado del pago automáticamente
            if (payload.data?.transaction_id) {
                console.log("🔄 Actualizando pago automáticamente:", payload.data.transaction_id);
                verificarPagoTUU(payload.data.transaction_id);
            }
        });
        
        return true;
        
    } catch (error) {
        console.error("❌ Error en inicialización de Firebase Messaging:", error);
        return false;
    }
}

// Función para guardar token en servidor
async function guardarTokenEnServidor(token) {
    try {
        await fetch('api/guardar-token-fcm.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token })
        });
        console.log("💾 Token guardado en servidor");
    } catch (error) {
        console.log("⚠️ No se pudo guardar token en servidor:", error);
    }
}

// Función para verificar pago TUU
async function verificarPagoTUU(transactionId) {
    try {
        const response = await fetch(`tuu-status-websocket.php?action=check_status&transaction_id=${encodeURIComponent(transactionId)}`);
        const data = await response.json();
        
        if (data.success && data.status === 'completed') {
            console.log("✅ Pago confirmado automáticamente:", transactionId);
            
            // Mostrar mensaje de éxito
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta('success', `Pago confirmado automáticamente: ${transactionId}`);
            }
            
            // Recargar página para actualizar datos
            setTimeout(() => {
                window.location.reload();
            }, 2000);
            
        } else {
            console.log("⚠️ Pago aún pendiente:", transactionId);
        }
    } catch (error) {
        console.error("❌ Error verificando pago:", error);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async () => {
    // Inicializar Firebase Messaging de forma segura
    await initFirebaseMessagingExample();
});

// Exportar para uso global
window.initFirebaseMessagingExample = initFirebaseMessagingExample;
