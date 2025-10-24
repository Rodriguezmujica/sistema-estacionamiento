# 🔥 Integración de Firebase - Sistema de Estacionamiento Los Ríos

## Descripción

Este documento describe la integración de Firebase en el sistema de estacionamiento, permitiendo una transición gradual de MySQL a Firebase mientras se mantiene la funcionalidad existente.

## Archivos Creados

### Configuración
- `firebase-config.js` - Configuración de Firebase para el frontend
- `firebase-config.php` - Configuración de Firebase para el backend PHP
- `firebase-setup-guide.md` - Guía paso a paso para configurar Firebase

### Autenticación
- `auth-hybrid.php` - Sistema de autenticación híbrido (MySQL + Firebase)
- `login-firebase.php` - Página de login actualizada con soporte híbrido
- `JS/firebase-auth.js` - Cliente de autenticación Firebase para JavaScript

### Migración
- `firebase-migration.php` - Herramienta para migrar datos de MySQL a Firestore
- `test-firebase.php` - Pruebas de configuración de Firebase
- `test-firebase-auth.html` - Pruebas de autenticación Firebase

## Características

### ✅ Sistema Híbrido
- Permite usar MySQL y Firebase simultáneamente
- Transición gradual sin interrumpir el servicio
- Fácil rollback si es necesario

### ✅ Autenticación Flexible
- Soporte para autenticación tradicional (MySQL)
- Soporte para autenticación moderna (Firebase Auth)
- Cambio de modo en tiempo de ejecución

### ✅ Migración de Datos
- Herramienta automática para migrar datos existentes
- Preserva la estructura de datos actual
- Log detallado del proceso de migración

## Instalación

### 1. Configurar Firebase

1. Ve a [Firebase Console](https://console.firebase.google.com/)
2. Crea un nuevo proyecto
3. Habilita Authentication (Email/Password)
4. Habilita Firestore Database
5. Habilita Storage
6. Obtén la configuración del proyecto

### 2. Actualizar Configuración

Edita los archivos de configuración con tus credenciales:

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

### 3. Probar Configuración

1. Abre `test-firebase.php` en tu navegador
2. Verifica que todas las pruebas pasen
3. Si hay errores, revisa la configuración

### 4. Probar Autenticación

1. Abre `test-firebase-auth.html` en tu navegador
2. Intenta iniciar sesión con credenciales de prueba
3. Verifica que la autenticación funcione

## Uso

### Modo MySQL (Actual)
```php
$auth = getHybridAuth();
// Por defecto usa MySQL
$result = $auth->authenticate($usuario, $password);
```

### Modo Firebase
```php
$auth = getHybridAuth();
$auth->enableFirebase(); // Cambiar a Firebase
$result = $auth->authenticate($email, $password);
```

### Migración de Datos
```php
// Ejecutar migración completa
$migration = new FirebaseMigration($conn);
$migration->runFullMigration();
```

## Estructura de Datos en Firestore

### Colección: usuarios
```json
{
  "id": "usuario_id",
  "usuario": "usuario@ejemplo.com",
  "rol": "admin|operador",
  "fecha_creacion": "timestamp",
  "activo": true
}
```

### Colección: tickets
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

### Colección: servicios_lavado
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

## Reglas de Seguridad

### Firestore
```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /usuarios/{userId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
    match /tickets/{ticketId} {
      allow read, write: if request.auth != null;
    }
    match /servicios_lavado/{servicioId} {
      allow read, write: if request.auth != null;
    }
  }
}
```

### Storage
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

## Próximos Pasos

1. ✅ Configurar Firebase
2. ✅ Implementar autenticación híbrida
3. 🔄 Migrar base de datos MySQL a Firestore
4. 🔄 Implementar Storage para archivos
5. 🔄 Configurar reglas de seguridad
6. 🔄 Probar integración completa

## Troubleshooting

### Error: "Firebase not initialized"
- Verifica que la configuración de Firebase sea correcta
- Asegúrate de que las credenciales sean válidas

### Error: "Permission denied"
- Revisa las reglas de seguridad de Firestore
- Verifica que el usuario esté autenticado

### Error: "Network request failed"
- Verifica la conexión a Internet
- Revisa que el proyecto Firebase esté activo

## Soporte

Para problemas o preguntas sobre la integración de Firebase:
1. Revisa los logs en `test-firebase.php`
2. Verifica la configuración en Firebase Console
3. Consulta la documentación oficial de Firebase

---

**Nota**: Esta integración está diseñada para ser gradual y segura. Siempre puedes volver al modo MySQL si es necesario.
