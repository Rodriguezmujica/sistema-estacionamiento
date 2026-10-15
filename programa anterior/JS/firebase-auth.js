/**
 * 🔥 AUTENTICACIÓN FIREBASE - LADO CLIENTE
 * Sistema de Estacionamiento Los Ríos
 */

// Importar Firebase (usando CDN)
import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-app.js';
import { getAuth, signInWithEmailAndPassword, signOut, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-auth.js';
import { getFirestore, doc, getDoc, setDoc } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-firestore.js';

// Configuración de Firebase (debe coincidir con firebase-config.js)
const firebaseConfig = {
  apiKey: "TU_API_KEY_AQUI",
  authDomain: "tu-proyecto.firebaseapp.com",
  projectId: "tu-proyecto-id",
  storageBucket: "tu-proyecto.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abcdef123456"
};

// Inicializar Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

class FirebaseAuthClient {
    constructor() {
        this.isInitialized = false;
        this.currentUser = null;
        this.init();
    }
    
    async init() {
        try {
            // Escuchar cambios en el estado de autenticación
            onAuthStateChanged(auth, (user) => {
                this.currentUser = user;
                this.isInitialized = true;
                
                if (user) {
                    console.log('Usuario autenticado:', user.email);
                    this.updateUIForAuthenticatedUser(user);
                } else {
                    console.log('Usuario no autenticado');
                    this.updateUIForUnauthenticatedUser();
                }
            });
        } catch (error) {
            console.error('Error inicializando Firebase Auth:', error);
        }
    }
    
    /**
     * Iniciar sesión con email y contraseña
     */
    async signIn(email, password) {
        try {
            const userCredential = await signInWithEmailAndPassword(auth, email, password);
            const user = userCredential.user;
            
            // Obtener datos adicionales del usuario desde Firestore
            const userDoc = await getDoc(doc(db, 'usuarios', user.uid));
            
            if (userDoc.exists()) {
                const userData = userDoc.data();
                return {
                    success: true,
                    user: {
                        uid: user.uid,
                        email: user.email,
                        usuario: userData.usuario || user.email,
                        rol: userData.rol || 'operador'
                    }
                };
            } else {
                // Si no existe el documento, crearlo
                await this.createUserDocument(user, email);
                return {
                    success: true,
                    user: {
                        uid: user.uid,
                        email: user.email,
                        usuario: email,
                        rol: 'operador'
                    }
                };
            }
        } catch (error) {
            console.error('Error en signIn:', error);
            return {
                success: false,
                error: this.getErrorMessage(error.code)
            };
        }
    }
    
    /**
     * Cerrar sesión
     */
    async signOut() {
        try {
            await signOut(auth);
            return { success: true };
        } catch (error) {
            console.error('Error en signOut:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Crear documento de usuario en Firestore
     */
    async createUserDocument(user, email) {
        try {
            await setDoc(doc(db, 'usuarios', user.uid), {
                usuario: email,
                rol: 'operador',
                fecha_creacion: new Date(),
                activo: true
            });
        } catch (error) {
            console.error('Error creando documento de usuario:', error);
        }
    }
    
    /**
     * Obtener mensaje de error en español
     */
    getErrorMessage(errorCode) {
        const errorMessages = {
            'auth/user-not-found': 'Usuario no encontrado',
            'auth/wrong-password': 'Contraseña incorrecta',
            'auth/invalid-email': 'Email inválido',
            'auth/user-disabled': 'Usuario deshabilitado',
            'auth/too-many-requests': 'Demasiados intentos. Intenta más tarde',
            'auth/network-request-failed': 'Error de conexión',
            'auth/invalid-credential': 'Credenciales inválidas'
        };
        
        return errorMessages[errorCode] || 'Error de autenticación';
    }
    
    /**
     * Actualizar UI para usuario autenticado
     */
    updateUIForAuthenticatedUser(user) {
        // Ocultar formulario de login
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.style.display = 'none';
        }
        
        // Mostrar información del usuario
        const userInfo = document.getElementById('user-info');
        if (userInfo) {
            userInfo.innerHTML = `
                <div class="alert alert-success">
                    <strong>Usuario autenticado:</strong> ${user.email}
                    <button class="btn btn-sm btn-outline-danger ms-2" onclick="firebaseAuth.signOut()">
                        Cerrar Sesión
                    </button>
                </div>
            `;
        }
    }
    
    /**
     * Actualizar UI para usuario no autenticado
     */
    updateUIForUnauthenticatedUser() {
        // Mostrar formulario de login
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.style.display = 'block';
        }
        
        // Ocultar información del usuario
        const userInfo = document.getElementById('user-info');
        if (userInfo) {
            userInfo.innerHTML = '';
        }
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    isAuthenticated() {
        return this.currentUser !== null;
    }
    
    /**
     * Obtener usuario actual
     */
    getCurrentUser() {
        return this.currentUser;
    }
}

// Crear instancia global
const firebaseAuth = new FirebaseAuthClient();

// Función para manejar el formulario de login
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('usuario').value;
    const password = document.getElementById('password').value;
    
    const result = await firebaseAuth.signIn(email, password);
    
    if (result.success) {
        // Redirigir al dashboard
        window.location.href = 'index.php';
    } else {
        // Mostrar error
        const errorDiv = document.getElementById('error-message');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger">
                    ${result.error}
                </div>
            `;
        }
    }
}

// Función para cerrar sesión
async function handleSignOut() {
    const result = await firebaseAuth.signOut();
    if (result.success) {
        window.location.href = 'login-firebase.php';
    }
}

// Exportar para uso global
window.firebaseAuth = firebaseAuth;
window.handleLogin = handleLogin;
window.handleSignOut = handleSignOut;
