# 🚀 Instrucciones de Instalación TUU + Firebase

## 📋 Resumen
Integración completa del sistema TUU existente con Firebase para sincronización en tiempo real entre PC1 (Antix) y PC2 (Windows 7).

## 🔧 Archivos Creados

### **Archivos Principales:**
- `webhook-tuu-firebase.php` - Webhook TUU mejorado con Firebase
- `tuu-payment-interceptor.js` - Interceptor de pagos TUU
- `tuu-firebase-integration-complete.js` - Integración completa
- `integrate-tuu-firebase.php` - Archivo para incluir en index.php

### **Archivos de Soporte:**
- `tuu-firebase-sync.js` - Sincronización TUU + Firebase
- `tuu-firebase-integration.js` - Integración con sistema existente
- `api/get-pending-tuu-payments.php` - API de pagos pendientes
- `api/check-payment-sync.php` - API de verificación de sincronización
- `api/sync-tuu-payment.php` - API de sincronización de pagos

## 🚀 Instalación Paso a Paso

### **Paso 1: Configurar Firebase Console**
1. Ir a [Firebase Console](https://console.firebase.google.com/)
2. Seleccionar proyecto: `sistemaestacionamiento-46735`
3. Habilitar **Authentication** (si no está habilitado)
4. Habilitar **Firestore Database** (si no está habilitado)
5. Habilitar **Storage** (si no está habilitado)

### **Paso 2: Instalar en PC1 (Antix - Servidor Principal)**
```bash
# Copiar archivos del sistema híbrido
cp -r SISTEMA-HIBRIDO/COMPARTIDOS/* /var/www/html/sistemaEstacionamiento/

# Copiar archivos de integración TUU
cp webhook-tuu-firebase.php /var/www/html/sistemaEstacionamiento/
cp tuu-payment-interceptor.js /var/www/html/sistemaEstacionamiento/
cp tuu-firebase-integration-complete.js /var/www/html/sistemaEstacionamiento/
cp integrate-tuu-firebase.php /var/www/html/sistemaEstacionamiento/

# Dar permisos
chmod 755 /var/www/html/sistemaEstacionamiento/webhook-tuu-firebase.php
chmod 755 /var/www/html/sistemaEstacionamiento/api/*.php
```

### **Paso 3: Instalar en PC2 (Windows 7 - Producción)**
```batch
# Copiar archivos del sistema híbrido
xcopy SISTEMA-HIBRIDO\COMPARTIDOS\* C:\xampp\htdocs\sistemaEstacionamiento\ /E /Y

# Copiar archivos de integración TUU
copy webhook-tuu-firebase.php C:\xampp\htdocs\sistemaEstacionamiento\
copy tuu-payment-interceptor.js C:\xampp\htdocs\sistemaEstacionamiento\
copy tuu-firebase-integration-complete.js C:\xampp\htdocs\sistemaEstacionamiento\
copy integrate-tuu-firebase.php C:\xampp\htdocs\sistemaEstacionamiento\
```

### **Paso 4: Modificar index.php**
Agregar al final del archivo `index.php` (antes del `</body>`):

```php
<?php
// Incluir integración TUU + Firebase
include_once 'integrate-tuu-firebase.php';
?>
```

### **Paso 5: Configurar Webhook TUU**
1. Ir a la configuración de TUU
2. Cambiar webhook URL a: `https://tu-servidor.com/sistemaEstacionamiento/webhook-tuu-firebase.php`
3. Verificar que la URL sea accesible desde internet

### **Paso 6: Probar Integración**
1. Abrir `test-tuu-firebase-completo.html` en ambas PC
2. Verificar que todos los indicadores estén en verde
3. Probar simulación de pagos
4. Verificar sincronización entre PC

## 🔄 Flujo de Funcionamiento

### **Escenario Normal (PC1 + PC2 conectadas):**
1. **PC2 (Windows 7):** Cliente presiona "Pagar con TUU"
2. **PC2:** Sistema crea pago en Firebase + MySQL local
3. **PC1 (Antix):** Recibe sincronización automática vía Firebase
4. **PC1:** Actualiza su MySQL principal
5. **Ambas PC:** Sincronizadas en tiempo real

### **Escenario Offline (Solo PC2):**
1. **PC2 (Windows 7):** Cliente presiona "Pagar con TUU"
2. **PC2:** Sistema crea pago en MySQL local + cola de Firebase
3. **PC2:** Funciona normalmente (impresión, etc.)
4. **Cuando vuelve internet:** Sincroniza con PC1 automáticamente

## 🧪 Pruebas

### **Prueba 1: Verificar Integración**
```bash
# En PC1
curl http://localhost/sistemaEstacionamiento/test-tuu-firebase-completo.html

# En PC2
curl http://localhost:8080/sistemaEstacionamiento/test-tuu-firebase-completo.html
```

### **Prueba 2: Simular Pago TUU**
1. Abrir `index.php` en PC2
2. Presionar "Pagar con TUU"
3. Verificar que se crea pago en Firebase
4. Verificar que PC1 recibe sincronización

### **Prueba 3: Modo Offline**
1. Desconectar internet en PC2
2. Crear pago TUU
3. Verificar que funciona localmente
4. Reconectar internet
5. Verificar sincronización automática

## 🔧 Solución de Problemas

### **Error: "Firebase no inicializado"**
- Verificar que `firebase-config.js` esté cargado
- Verificar credenciales de Firebase
- Verificar conexión a internet

### **Error: "TUU Sync no disponible"**
- Verificar que `tuu-firebase-sync.js` esté cargado
- Verificar que Firebase esté inicializado
- Verificar permisos de archivos

### **Error: "Webhook no responde"**
- Verificar que `webhook-tuu-firebase.php` sea accesible
- Verificar configuración de TUU
- Verificar logs de Apache/Nginx

## 📊 Monitoreo

### **Logs del Sistema:**
- **PC1:** `/var/log/apache2/error.log`
- **PC2:** `C:\xampp\apache\logs\error.log`

### **Logs de Firebase:**
- Ir a Firebase Console > Functions > Logs

### **Estado del Sistema:**
- Abrir `test-tuu-firebase-completo.html` en cualquier PC
- Verificar indicadores de estado

## 🎯 Resultado Final

Después de la instalación tendrás:

- ✅ **Sincronización TUU en tiempo real** entre PC1 y PC2
- ✅ **Confirmación de pagos** automática
- ✅ **Modo offline** con cola de sincronización
- ✅ **Sistema híbrido completo** funcionando
- ✅ **Respaldo automático** en Firebase
- ✅ **Acceso remoto** vía Tailscale

## 🆘 Soporte

Si encuentras problemas:

1. **Verificar logs** del sistema
2. **Probar APIs** individualmente
3. **Verificar conectividad** a Firebase
4. **Revisar configuración** de TUU

---

**¡Sistema TUU + Firebase listo para producción!** 🎉
