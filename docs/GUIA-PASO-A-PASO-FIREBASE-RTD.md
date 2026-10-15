# 🔥 Guía Paso a Paso: Habilitar Firebase Real-time Database

## 📋 Pasos Detallados

### Paso 1: Acceder a Realtime Database

1. En la **barra lateral izquierda** de Firebase Console, busca **"Realtime Database"**
   - Puede estar en la sección superior (sin categoría)
   - O dentro de la categoría **"Compilación" (Build)**

2. **Haz clic** en **"Realtime Database"**

### Paso 2: Crear la Base de Datos

1. Si es la primera vez, verás un botón **"Crear base de datos"** o **"Create database"**
   - Si ya existe una base de datos, verás su contenido

2. Al hacer clic en **"Crear base de datos"**, aparecerá un modal con opciones:

   **a) Ubicación:**
   - Selecciona: **"southamerica-east1"** (São Paulo, Brasil)
   - Esta es la ubicación más cercana para Chile
   - O puedes elegir **"us-central1"** si no aparece la opción de Sudamérica

   **b) Modo de inicio:**
   - ⚠️ **IMPORTANTE**: Selecciona **"Comenzar en modo de prueba"** o **"Start in test mode"**
   - Esto permite escritura y lectura temporalmente (luego configuramos las reglas)

3. Haz clic en **"Listo"** o **"Done"**

### Paso 3: Obtener la URL de la Base de Datos

Una vez creada, verás una pantalla con:

- **URL de la base de datos**: Algo como:
  ```
  https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com
  ```

- **Reglas de seguridad**: Un código JSON

**⚠️ IMPORTANTE**: Copia esta URL. Debe coincidir con la que tienes en tu código.

### Paso 4: Configurar las Reglas de Seguridad

1. En la pantalla de Realtime Database, ve a la pestaña **"Reglas"** o **"Rules"** (arriba)

2. Verás un editor con código JSON. Reemplaza el contenido con esto:

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

3. Haz clic en **"Publicar"** o **"Publish"** (botón azul arriba a la derecha)

4. Confirma la publicación

### Paso 5: Verificar que Funciona

1. Vuelve a la pestaña **"Datos"** o **"Data"**

2. Deberías ver la estructura de la base de datos (probablemente vacía)

3. Ahora ejecuta tu script de verificación:
   ```
   http://localhost/sistemaEstacionamiento/tests/verificar-firebase-tuu.php
   ```

## 🔍 Solución de Problemas

### Problema: No veo "Realtime Database" en el menú

**Solución:**
- Asegúrate de estar en el proyecto correcto: `sistemaestacionamiento-46735`
- Verifica que tengas permisos de administrador
- Intenta refrescar la página (F5)

### Problema: Ya existe una base de datos pero da error 404

**Solución:**
1. Ve a Realtime Database
2. Verifica la URL que aparece
3. Compara con la URL en tu código:
   - En `JS/firebase-tuu-listener.js` línea 28
   - Debe ser: `https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com`

### Problema: Error 403 (Forbidden) después de crear la base de datos

**Solución:**
1. Ve a Realtime Database → Reglas
2. Asegúrate de que las reglas permitan escritura en `tuu_webhook_notifications`
3. Publica las reglas nuevamente

### Problema: La URL es diferente a la esperada

**Solución:**
Si tu URL es diferente (por ejemplo, tiene un nombre personalizado), actualiza estos archivos:

1. `JS/firebase-tuu-listener.js` - línea 28
2. `tests/verificar-firebase-tuu.php` - línea 196
3. `firebase/firebase-webhook-tuu.php` - línea 179

## 📸 Capturas de Pantalla de Referencia

### Vista de Realtime Database (Datos)
```
┌─────────────────────────────────────┐
│ Realtime Database                   │
├─────────────────────────────────────┤
│ URL: https://...firebaseio.com     │
│                                     │
│ [Datos] [Reglas] [Uso] [Backups]   │
│                                     │
│ (Base de datos vacía o con datos)  │
└─────────────────────────────────────┘
```

### Vista de Reglas
```
┌─────────────────────────────────────┐
│ Reglas                              │
├─────────────────────────────────────┤
│ {                                   │
│   "rules": {                        │
│     "tuu_webhook_notifications": {  │
│       ".write": true,               │
│       ".read": true                 │
│     }                               │
│   }                                 │
│ }                                   │
│                                     │
│ [Publicar]                          │
└─────────────────────────────────────┘
```

## ✅ Checklist Final

Antes de probar nuevamente, verifica:

- [ ] Realtime Database está creada
- [ ] La URL coincide con la del código
- [ ] Las reglas están configuradas y publicadas
- [ ] El proyecto correcto está seleccionado (`sistemaestacionamiento-46735`)
- [ ] Tienes permisos de administrador

## 🆘 Si Aún No Funciona

1. **Verifica la URL exacta:**
   - En Firebase Console → Realtime Database → Copia la URL completa
   - Compara con la del código

2. **Prueba manualmente:**
   - Abre esta URL en el navegador (reemplaza con tu URL):
   ```
   https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com/.json
   ```
   - Deberías ver `null` o `{}` (no un error 404)

3. **Revisa los logs:**
   - En Firebase Console → Realtime Database → Uso
   - Verifica si hay intentos de conexión

4. **Contacta:**
   - Si nada funciona, puede ser un problema de permisos del proyecto
   - Verifica que el proyecto esté activo y no suspendido

