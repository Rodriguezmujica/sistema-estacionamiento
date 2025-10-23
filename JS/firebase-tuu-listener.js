/**
 * Firebase TUU Listener - Escucha notificaciones de TUU desde Firebase
 * Para sistemas locales sin dominio público
 */

class FirebaseTUUListener {
    constructor() {
        this.database = null;
        this.isListening = false;
        this.callbacks = {
            onPaymentSuccess: null,
            onPaymentFailed: null,
            onPaymentPending: null
        };
    }

    // Inicializar Firebase
    async initialize() {
        try {
            // Importar Firebase
            const { initializeApp } = await import('https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js');
            const { getDatabase, ref, onChildAdded, onChildChanged } = await import('https://www.gstatic.com/firebasejs/12.4.0/firebase-database.js');
            
            // Configuración Firebase
            const firebaseConfig = {
                apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
                authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
                databaseURL: "https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com",
                projectId: "sistemaestacionamiento-46735",
                storageBucket: "sistemaestacionamiento-46735.firebasestorage.app",
                messagingSenderId: "570161231939",
                appId: "1:570161231939:web:50a5f88fcd65e98fa03cf6"
            };

            // Inicializar Firebase
            const app = initializeApp(firebaseConfig);
            this.database = getDatabase(app);
            
            console.log('🔥 Firebase TUU Listener inicializado');
            return true;
            
        } catch (error) {
            console.error('❌ Error inicializando Firebase TUU Listener:', error);
            return false;
        }
    }

    // Configurar callbacks
    onPaymentSuccess(callback) {
        this.callbacks.onPaymentSuccess = callback;
    }

    onPaymentFailed(callback) {
        this.callbacks.onPaymentFailed = callback;
    }

    onPaymentPending(callback) {
        this.callbacks.onPaymentPending = callback;
    }

    // Iniciar escucha de notificaciones TUU
    startListening() {
        if (!this.database) {
            console.error('❌ Firebase no inicializado');
            return false;
        }

        if (this.isListening) {
            console.log('⚠️ Ya está escuchando notificaciones TUU');
            return true;
        }

        try {
            const { ref, onChildAdded } = require('firebase/database');
            
            // Escuchar notificaciones de webhook TUU
            const webhookRef = ref(this.database, 'tuu_webhook_notifications');
            
            onChildAdded(webhookRef, (snapshot) => {
                const data = snapshot.val();
                
                if (data && !data.processed) {
                    this.procesarNotificacionTUU(data, snapshot.key);
                }
            });

            // Escuchar pagos TUU procesados
            const paymentsRef = ref(this.database, 'tuu_payments');
            
            onChildAdded(paymentsRef, (snapshot) => {
                const data = snapshot.val();
                
                if (data && data.processed) {
                    this.procesarPagoTUU(data);
                }
            });

            this.isListening = true;
            console.log('🎧 Escuchando notificaciones TUU desde Firebase');
            return true;

        } catch (error) {
            console.error('❌ Error iniciando escucha TUU:', error);
            return false;
        }
    }

    // Procesar notificación TUU
    async procesarNotificacionTUU(data, key) {
        console.log('🔔 Notificación TUU recibida:', data);

        const { transaction_id, status, amount, patente } = data;

        // Procesar en el servidor local
        try {
            const response = await fetch('firebase-webhook-tuu.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Pago TUU procesado:', transaction_id);
                
                // Ejecutar callback correspondiente
                if (status === 'success' || status === 'approved') {
                    if (this.callbacks.onPaymentSuccess) {
                        this.callbacks.onPaymentSuccess({
                            transaction_id,
                            amount,
                            patente,
                            status: 'success'
                        });
                    }
                } else if (status === 'failed' || status === 'rejected') {
                    if (this.callbacks.onPaymentFailed) {
                        this.callbacks.onPaymentFailed({
                            transaction_id,
                            amount,
                            patente,
                            status: 'failed'
                        });
                    }
                } else if (status === 'pending') {
                    if (this.callbacks.onPaymentPending) {
                        this.callbacks.onPaymentPending({
                            transaction_id,
                            amount,
                            patente,
                            status: 'pending'
                        });
                    }
                }
            } else {
                console.error('❌ Error procesando pago TUU:', result.message);
            }

        } catch (error) {
            console.error('❌ Error enviando notificación TUU:', error);
        }
    }

    // Procesar pago TUU ya procesado
    procesarPagoTUU(data) {
        console.log('💳 Pago TUU procesado:', data);

        const { transaction_id, status, amount, patente } = data;

        // Ejecutar callback correspondiente
        if (status === 'success') {
            if (this.callbacks.onPaymentSuccess) {
                this.callbacks.onPaymentSuccess({
                    transaction_id,
                    amount,
                    patente,
                    status: 'success'
                });
            }
        } else if (status === 'failed') {
            if (this.callbacks.onPaymentFailed) {
                this.callbacks.onPaymentFailed({
                    transaction_id,
                    amount,
                    patente,
                    status: 'failed'
                });
            }
        }
    }

    // Simular webhook TUU (para testing)
    async simularWebhookTUU(transaction_id, status, amount, patente) {
        try {
            const response = await fetch('firebase-webhook-tuu.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'simulate',
                    transaction_id,
                    status,
                    amount,
                    patente
                })
            });

            const result = await response.json();
            console.log('🧪 Webhook TUU simulado:', result);
            return result;

        } catch (error) {
            console.error('❌ Error simulando webhook TUU:', error);
            return false;
        }
    }

    // Detener escucha
    stopListening() {
        this.isListening = false;
        console.log('🛑 Escucha TUU detenida');
    }
}

// Instancia global
window.firebaseTUUListener = new FirebaseTUUListener();

// Auto-inicializar si está en el contexto correcto
if (typeof window !== 'undefined') {
    window.addEventListener('DOMContentLoaded', async () => {
        await window.firebaseTUUListener.initialize();
        window.firebaseTUUListener.startListening();
    });
}





