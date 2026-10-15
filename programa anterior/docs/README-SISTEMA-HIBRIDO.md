# 🔄 Sistema Híbrido Firebase - Estacionamiento Los Ríos

## 🎯 Objetivo

Crear un sistema híbrido que permita la operación continua del sistema de estacionamiento entre dos PC:

- **PC 1 (Antix)**: Servidor principal con base de datos MySQL
- **PC 2 (Windows 7)**: PC de producción con impresora USB

## 🏗️ Arquitectura

```
┌─────────────────┐    ┌─────────────────┐
│   PC 1 (Antix)  │    │ PC 2 (Windows 7)│
│                 │    │                 │
│  MySQL Server   │    │  Impresora USB  │
│  VPN Tailscale  │    │  Batería        │
│  Sistema Web    │    │  Sistema Web    │
└─────────┬───────┘    └─────────┬───────┘
          │                      │
          └──────────┬───────────┘
                     │
            ┌────────▼────────┐
            │   Firebase      │
            │                 │
            │  Firestore      │
            │  Storage        │
            │  Functions      │
            └─────────────────┘
```

## 📁 Archivos del Sistema

### Configuración Base
- `firebase-config.js` - Configuración de Firebase y detección de PC
- `sistema-hibrido.js` - Sistema principal que integra todo

### Sincronización
- `firebase-sync.js` - Sincronización bidireccional con Firebase
- `pc-detector.js` - Detección de PC activa y cambio de control

### Impresión
- `printing-manager.js` - Gestión de impresión en PC2

### Pruebas
- `test-sistema-hibrido.html` - Interfaz de prueba del sistema

## 🚀 Funcionalidades

### ✅ Detección Automática de PC
- Identifica automáticamente qué PC es (Antix o Windows 7)
- Monitorea el estado de ambas PC mediante heartbeat
- Cambia el control automáticamente según disponibilidad

### ✅ Sincronización Bidireccional
- Sincroniza datos entre MySQL (PC1) y Firebase
- Mantiene ambas PC actualizadas en tiempo real
- Cola de sincronización para operaciones offline

### ✅ Gestión de Impresión
- Solo PC2 (Windows 7) puede imprimir
- Cola de impresión cuando PC2 no está activa
- Formateo automático de tickets y reportes

### ✅ Modo Offline
- PC2 puede operar sin conexión a internet
- Datos se sincronizan cuando se restaura la conexión
- Continuidad del servicio durante cortes de luz

## 🔧 Configuración

### 1. Configurar Firebase
```javascript
// firebase-config.js
const firebaseConfig = {
  apiKey: "tu-api-key",
  authDomain: "tu-proyecto.firebaseapp.com",
  projectId: "tu-proyecto-id",
  storageBucket: "tu-proyecto.appspot.com",
  messagingSenderId: "tu-sender-id",
  appId: "tu-app-id"
};
```

### 2. Configurar Detección de PC
```javascript
// El sistema detecta automáticamente la PC basándose en:
// - User Agent (Windows NT 6.1 = Windows 7)
// - Hostname
// - IP address
```

### 3. Configurar Sincronización
```javascript
// firebase-sync.js
const syncConfig = {
  enabled: true,
  interval: 5000, // 5 segundos
  retryAttempts: 3,
  retryDelay: 2000
};
```

## 📊 Flujo de Operación

### Operación Normal
1. PC1 (Antix) es el servidor principal
2. PC2 (Windows 7) está en modo standby
3. Datos se sincronizan automáticamente
4. Impresión se maneja desde PC1

### Corte de Luz
1. PC1 se apaga (no hay batería)
2. PC2 detecta que PC1 está offline
3. PC2 toma el control automáticamente
4. PC2 puede imprimir y registrar datos
5. Datos se sincronizan cuando vuelve la luz

### Recuperación
1. PC1 se enciende y se conecta
2. PC1 detecta que PC2 está activa
3. PC1 toma el control (tiene prioridad)
4. PC2 vuelve a modo standby
5. Datos se sincronizan completamente

## 🖨️ Sistema de Impresión

### PC2 (Windows 7)
- Tiene impresora USB conectada
- Puede imprimir tickets y reportes
- Formateo automático para impresora térmica

### PC1 (Antix)
- No tiene impresora
- Datos se envían a PC2 para impresión
- Cola de impresión cuando PC2 no está activa

## 🔄 Sincronización de Datos

### Datos Sincronizados
- **Tickets de estacionamiento**
- **Servicios de lavado**
- **Usuarios del sistema**
- **Configuración del sistema**

### Estrategia de Sincronización
1. **Tiempo Real**: Cambios se sincronizan inmediatamente
2. **Periódica**: Sincronización cada 5 segundos
3. **Offline**: Cola de sincronización para operaciones offline
4. **Resolución de Conflictos**: PC1 tiene prioridad sobre PC2

## 🧪 Pruebas

### Interfaz de Prueba
Abre `test-sistema-hibrido.html` en tu navegador para:

- Ver el estado del sistema en tiempo real
- Probar funcionalidades de sincronización
- Probar sistema de impresión
- Ver logs del sistema
- Ejecutar diagnósticos

### Pruebas Recomendadas
1. **Prueba de Conectividad**: Desconectar internet y verificar modo offline
2. **Prueba de Cambio de PC**: Simular que PC1 se apaga
3. **Prueba de Impresión**: Verificar que solo PC2 puede imprimir
4. **Prueba de Sincronización**: Verificar que los datos se sincronizan

## 📱 Monitoreo

### Indicadores de Estado
- **🌐 Online/Offline**: Estado de conectividad
- **✅ Activa/Inactiva**: Estado de la PC actual
- **🔄 Sincronizando/Sincronizado**: Estado de sincronización
- **🖨️ Imprimiendo/Listo**: Estado de impresión

### Logs del Sistema
- Todos los eventos se registran en consola
- Logs se muestran en la interfaz de prueba
- Timestamps para seguimiento temporal

## 🚨 Troubleshooting

### Problemas Comunes

#### PC no se detecta como activa
- Verificar conectividad a Firebase
- Revisar configuración de heartbeat
- Forzar PC activa manualmente

#### Sincronización no funciona
- Verificar conexión a internet
- Revisar configuración de Firebase
- Verificar que las APIs estén funcionando

#### Impresión no funciona
- Verificar que sea PC2 (Windows 7)
- Verificar que la impresora esté conectada
- Revisar configuración de impresión

### Comandos de Diagnóstico
```javascript
// Obtener estado del sistema
window.sistemaHibrido.getSystemStatus();

// Obtener información de diagnóstico
window.sistemaHibrido.getDiagnosticInfo();

// Forzar sincronización
await window.sistemaHibrido.forceSync();

// Forzar PC activa
await window.sistemaHibrido.forceActive();
```

## 🔒 Seguridad

### Reglas de Firebase
- Solo PCs autenticadas pueden acceder a los datos
- Validación de datos en escritura
- Control de acceso por roles

### Validación de Datos
- Todos los datos se validan antes de sincronizar
- Timestamps para auditoría
- Logs de todas las operaciones

## 📈 Rendimiento

### Optimizaciones
- Sincronización incremental
- Cola de operaciones offline
- Caché local de datos
- Compresión de datos

### Monitoreo
- Métricas de sincronización
- Tiempo de respuesta
- Uso de ancho de banda
- Estado de la cola

## 🎯 Próximos Pasos

1. **Configurar Firebase** con tus credenciales
2. **Probar el sistema** en ambas PC
3. **Configurar impresora** en PC2
4. **Probar escenarios** de corte de luz
5. **Entrenar al personal** en el uso del sistema

---

**¡El sistema híbrido está listo para usar!** 🎉

Para cualquier pregunta o problema, consulta los logs del sistema o ejecuta las pruebas de diagnóstico.
