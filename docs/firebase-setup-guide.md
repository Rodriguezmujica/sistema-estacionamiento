# 🔥 Guía de Configuración de Firebase

## Pasos para configurar Firebase en el Sistema de Estacionamiento

### 1. Crear Proyecto en Firebase Console

1. Ve a [Firebase Console](https://console.firebase.google.com/)
2. Haz clic en "Crear un proyecto"
3. Nombra tu proyecto: `sistema-estacionamiento-los-rios`
4. Habilita Google Analytics (opcional)
5. Crea el proyecto

### 2. Configurar Authentication

1. En el panel izquierdo, ve a "Authentication"
2. Haz clic en "Comenzar"
3. Ve a la pestaña "Sign-in method"
4. Habilita "Email/Password"
5. Guarda los cambios

### 3. Configurar Firestore Database

1. En el panel izquierdo, ve a "Firestore Database"
2. Haz clic en "Crear base de datos"
3. Selecciona "Comenzar en modo de prueba" (para desarrollo)
4. Elige una ubicación (usar `southamerica-east1` para Chile)
5. Crea la base de datos

### 4. Configurar Storage

1. En el panel izquierdo, ve a "Storage"
2. Haz clic en "Comenzar"
3. Acepta las reglas de seguridad por defecto
4. Elige la misma ubicación que Firestore

### 5. Obtener Configuración del Proyecto

1. Ve a "Configuración del proyecto" (ícono de engranaje)
2. Desplázate hacia abajo hasta "Tus aplicaciones"
3. Haz clic en "Agregar app" y selecciona "Web" (</>)
4. Registra tu app con el nombre "Sistema Estacionamiento"
5. Copia la configuración que aparece

### 6. Actualizar Archivos de Configuración

Reemplaza los valores en `firebase-config.js` y `firebase-config.php`:

```javascript
// firebase-config.js
const firebaseConfig = {
  apiKey: "tu-api-key-real",
  authDomain: "tu-proyecto-real.firebaseapp.com",
  projectId: "tu-proyecto-real-id",
  storageBucket: "tu-proyecto-real.appspot.com",
  messagingSenderId: "tu-sender-id-real",
  appId: "tu-app-id-real"
};
```

```php
// firebase-config.php
define('FIREBASE_API_KEY', 'tu-api-key-real');
define('FIREBASE_AUTH_DOMAIN', 'tu-proyecto-real.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'tu-proyecto-real-id');
// ... etc
```

### 7. Instalar Dependencias

Para el frontend, necesitarás instalar Firebase SDK:

```bash
npm install firebase
```

O usar CDN (recomendado para este proyecto):

```html
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-auth.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-firestore.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-storage.js"></script>
```

### 8. Configurar Reglas de Seguridad

#### Reglas de Firestore:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Usuarios solo pueden leer/escribir sus propios datos
    match /usuarios/{userId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
    
    // Tickets de estacionamiento - solo usuarios autenticados
    match /tickets/{ticketId} {
      allow read, write: if request.auth != null;
    }
    
    // Servicios de lavado - solo usuarios autenticados
    match /servicios_lavado/{servicioId} {
      allow read, write: if request.auth != null;
    }
    
    // Reportes - solo administradores
    match /reportes/{reporteId} {
      allow read, write: if request.auth != null && 
        get(/databases/$(database)/documents/usuarios/$(request.auth.uid)).data.rol == 'admin';
    }
  }
}
```

#### Reglas de Storage:

```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read, write: if request.auth != null;
    }
  }
}
```

### 9. Estructura de Datos en Firestore

#### Colección: usuarios
```json
{
  "id": "usuario_id",
  "email": "usuario@ejemplo.com",
  "nombre": "Nombre Usuario",
  "rol": "admin|operador",
  "fecha_creacion": "timestamp",
  "activo": true
}
```

#### Colección: tickets
```json
{
  "id": "ticket_id",
  "patente": "ABC123",
  "tipo_servicio": "estacionamiento|lavado",
  "fecha_ingreso": "timestamp",
  "fecha_salida": "timestamp",
  "precio": 1500,
  "pagado": false,
  "usuario_id": "usuario_id",
  "cliente_nombre": "Nombre Cliente"
}
```

#### Colección: servicios_lavado
```json
{
  "id": "servicio_id",
  "patente": "ABC123",
  "tipo_lavado": "básico|premium|completo",
  "precio_base": 5000,
  "precio_extra": 1000,
  "motivos_extra": ["hongos", "barro"],
  "fecha_servicio": "timestamp",
  "usuario_id": "usuario_id"
}
```

### 10. Próximos Pasos

1. ✅ Configurar Firebase
2. 🔄 Migrar sistema de autenticación
3. 🔄 Migrar base de datos MySQL a Firestore
4. 🔄 Implementar Storage para archivos
5. 🔄 Configurar reglas de seguridad
6. 🔄 Probar integración completa

---

**Nota**: Esta es una guía de configuración inicial. Los archivos de configuración ya están creados y listos para ser personalizados con tus credenciales de Firebase.
