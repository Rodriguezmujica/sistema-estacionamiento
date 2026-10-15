# 🔥 Instrucciones para Habilitar Firebase Real-time Database

## Problema: Error 404 al conectar con Firebase Real-time Database

Si el script de verificación muestra un error 404 al intentar escribir en Firebase Real-time Database, necesitas habilitar y configurar el servicio.

## Pasos para Habilitar Firebase Real-time Database

### 1. Ir a Firebase Console

1. Ve a [Firebase Console](https://console.firebase.google.com/)
2. Selecciona tu proyecto: `sistemaestacionamiento-46735`

### 2. Habilitar Real-time Database

1. En el menú lateral, busca **"Realtime Database"** o **"Base de datos en tiempo real"**
2. Si no está habilitado, haz clic en **"Crear base de datos"**
3. Selecciona la ubicación: **southamerica-east1** (São Paulo) - recomendado para Chile
4. Elige **"Comenzar en modo de prueba"** (puedes cambiar las reglas después)

### 3. Configurar Reglas de Seguridad

Una vez creada la base de datos, ve a la pestaña **"Reglas"** y configura lo siguiente:

```json
{
  "rules": {
    "tuu_webhook_notifications": {
      ".write": true,
      ".read": true
    },
    "test_connection": {
      ".write": true,
      ".read": true
    },
    "$other": {
      ".read": false,
      ".write": false
    }
  }
}
```

**Explicación:**
- `tuu_webhook_notifications`: Permite escritura y lectura pública (solo para este endpoint)
- `test_connection`: Permite escritura y lectura para pruebas
- `$other`: Bloquea todo lo demás por seguridad

### 4. Obtener la URL de la Base de Datos

La URL debería ser:
```
https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com
```

Si tienes una URL diferente, actualiza `firebase-config.php` y `firebase-tuu-listener.js`.

### 5. Verificar la Conexión

1. Ejecuta nuevamente el script de verificación:
   ```
   http://localhost/sistemaEstacionamiento/tests/verificar-firebase-tuu.php
   ```

2. El test de conexión debería pasar ahora (HTTP 200 en lugar de 404)

## Configuración Segura para Producción

Para producción, puedes usar reglas más restrictivas con autenticación:

```json
{
  "rules": {
    "tuu_webhook_notifications": {
      ".write": "auth != null || newData.child('source').val() == 'tuu'",
      ".read": "auth != null"
    },
    "$other": {
      ".read": false,
      ".write": false
    }
  }
}
```

Pero para empezar, usa las reglas públicas en `tuu_webhook_notifications` para que TUU pueda enviar webhooks.

## Solución de Problemas

### Error 404
- **Causa**: Real-time Database no está habilitado
- **Solución**: Sigue los pasos 1-2 arriba

### Error 403 (Forbidden)
- **Causa**: Las reglas de seguridad están bloqueando
- **Solución**: Actualiza las reglas como se muestra en el paso 3

### Error de conexión
- **Causa**: URL incorrecta o proyecto incorrecto
- **Solución**: Verifica que la URL en Firebase Console coincida con la configurada

## Notas Importantes

1. **Seguridad**: Las reglas públicas en `tuu_webhook_notifications` son necesarias para que TUU pueda enviar webhooks. Esto es seguro porque:
   - Solo permite escribir en ese endpoint específico
   - Tu sistema procesará y validará los datos
   - Puedes agregar validación adicional en tu código PHP

2. **Costo**: Firebase Real-time Database tiene un plan gratuito generoso. El uso de webhooks no debería generar costos significativos.

3. **Backup**: Considera hacer backup periódico de los datos importantes si usas Firebase para almacenamiento a largo plazo.

## Contacto

Si sigues teniendo problemas después de seguir estos pasos, verifica:
- Que el proyecto Firebase esté activo
- Que tengas permisos de administrador en el proyecto
- Que no haya límites de cuota alcanzados

