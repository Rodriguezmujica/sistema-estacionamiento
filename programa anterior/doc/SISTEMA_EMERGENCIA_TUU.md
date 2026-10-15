# 🚨 Sistema de Emergencia TUU

## Descripción General

Sistema de **failover automático** entre dos máquinas TUU para garantizar que el negocio nunca se detenga por fallas de hardware.

---

## 🎯 Características

### ✅ Implementadas
- **Cambio rápido entre máquinas TUU** (Principal ↔ Respaldo)
- **Indicador visual en tiempo real** de máquina activa
- **Modal de emergencia** con estado actual y opciones
- **Botón de refresh** para actualizar datos del dashboard
- **Badge clickeable** en navbar para acceso rápido
- **Configuración persistente** en base de datos

### 🔧 Componentes

#### 1. **Base de Datos**
```sql
Tabla: configuracion_tuu
- id (INT, PK)
- maquina (VARCHAR) → 'principal' | 'respaldo'
- device_serial (VARCHAR) → Serial de la máquina TUU
- nombre (VARCHAR) → Nombre descriptivo
- activa (TINYINT) → 1 = activa, 0 = inactiva
- fecha_actualizacion (TIMESTAMP)
```

#### 2. **API Endpoints**

**GET /api/api_config_tuu.php**
```json
{
  "success": true,
  "maquinas": [
    {
      "id": 1,
      "maquina": "principal",
      "device_serial": "6752d2805d5b1d86",
      "nombre": "TUU Principal - Caja 1",
      "activa": true
    },
    {
      "id": 2,
      "maquina": "respaldo",
      "device_serial": "SERIAL_AQUI",
      "nombre": "TUU Respaldo - Caja 2",
      "activa": false
    }
  ],
  "activa": {
    "maquina": "principal",
    "nombre": "TUU Principal - Caja 1",
    "device_serial": "6752d2805d5b1d86"
  }
}
```

**POST /api/api_config_tuu.php**
```json
Request:
{
  "maquina": "respaldo"
}

Response:
{
  "success": true,
  "message": "Cambiado a TUU Respaldo - Caja 2",
  "maquina_activa": {
    "maquina": "respaldo",
    "nombre": "TUU Respaldo - Caja 2",
    "device_serial": "SERIAL_AQUI"
  }
}
```

#### 3. **Frontend (JavaScript)**
- **emergencia-tuu.js**
  - `cargarEstadoTUU()` → Carga estado actual cada 30 seg
  - `abrirModalEmergencia()` → Muestra modal con opciones
  - `cambiarMaquinaTUU(maquina)` → Cambia máquina activa
  - `refrescarDashboard()` → Recarga todos los datos
  - `actualizarIndicadorTUU(maquina)` → Actualiza badge visual

---

## 📖 Manual de Uso

### Situación 1: Máquina Principal No Responde

**Síntomas:**
- Pagos con TUU fallan
- Cliente esperando indefinidamente
- Máquina TUU no responde

**Solución:**
1. Click en botón rojo **"Emergencia"** (abajo a la derecha)
2. Aparece modal mostrando:
   - ✅ Máquina activa actual
   - 📋 Lista de máquinas disponibles
3. Click en **"Cambiar a esta máquina"** en TUU Respaldo
4. Confirmar el cambio
5. ✅ **¡Listo!** Los siguientes pagos usarán la máquina de respaldo

**Indicador visual:**
- Badge en navbar cambia de **🟢 Principal** (azul) a **🟡 Respaldo** (amarillo)

---

### Situación 2: Volver a Máquina Principal

**Cuándo:**
- La máquina principal ya funciona nuevamente
- Mantenimiento de la máquina de respaldo

**Pasos:**
1. Click en **"Emergencia"** o en badge de TUU en navbar
2. Seleccionar **TUU Principal**
3. Confirmar
4. ✅ Sistema vuelve a configuración normal

---

### Situación 3: Verificar Máquina Activa

**Formas de verificar:**

1. **Badge en Navbar** (arriba a la derecha)
   - 🟢 Azul = Principal
   - 🟡 Amarillo = Respaldo
   - 🔴 Rojo = Error

2. **Click en Badge** → Abre modal con detalles completos

3. **Tooltip al pasar mouse** → Muestra nombre completo

---

## ⚙️ Configuración Inicial

### Paso 1: Obtener Serial de Máquina Respaldo

1. Accede al **panel de TUU** → [https://tuu.cl](https://tuu.cl)
2. Ve a **Dispositivos** o **Configuración**
3. Busca tu segunda máquina TUU
4. Copia el **número de serie** (device serial)

### Paso 2: Actualizar en Base de Datos

**Opción A: Desde phpMyAdmin**
1. Abre phpMyAdmin → Base `estacionamiento`
2. Ve a tabla `configuracion_tuu`
3. Edita el registro de `respaldo`
4. Cambia el campo `device_serial` por el serial real

**Opción B: Desde MySQL**
```sql
UPDATE configuracion_tuu 
SET device_serial = 'TU_SERIAL_REAL_AQUI',
    nombre = 'TUU Respaldo - Caja 2'
WHERE maquina = 'respaldo';
```

### Paso 3: Verificar Configuración

1. Abre el sistema → `index.php`
2. Click en **"Emergencia"**
3. Verifica que ambas máquinas aparezcan con sus seriales correctos

---

## 🔍 Troubleshooting

### Problema: Badge muestra "Error"
**Causa:** No puede conectar con la API de configuración
**Solución:**
```sql
-- Verificar que la tabla existe
SHOW TABLES LIKE 'configuracion_tuu';

-- Verificar registros
SELECT * FROM configuracion_tuu;

-- Debe haber exactamente 2 registros (principal y respaldo)
```

### Problema: Al cambiar máquina, pagos siguen fallando
**Causa:** Serial de máquina respaldo incorrecto
**Solución:**
1. Verificar serial en panel de TUU
2. Actualizar en BD
3. Click en **Refresh** en dashboard
4. Intentar de nuevo

### Problema: Modal de emergencia no carga
**Causa:** Error en JavaScript o API
**Solución:**
1. Abrir consola del navegador (F12)
2. Buscar errores en JavaScript
3. Verificar que `/api/api_config_tuu.php` responda correctamente:
   ```
   http://localhost:8080/sistemaEstacionamiento/api/api_config_tuu.php
   ```

---

## 🧪 Testing

### Test 1: Verificar Estado Actual
```javascript
// Abrir consola del navegador y ejecutar:
fetch('./api/api_config_tuu.php')
  .then(r => r.json())
  .then(d => console.log(d));
```

### Test 2: Simular Cambio de Máquina
```javascript
fetch('./api/api_config_tuu.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({maquina: 'respaldo'})
})
.then(r => r.json())
.then(d => console.log(d));
```

### Test 3: Verificar Device Serial Dinámico
1. Cambiar a máquina respaldo
2. Abrir `/api/tuu-pago.php` en modo debug
3. Verificar que use el serial de respaldo

---

## 📊 Logs y Auditoría

La tabla `configuracion_tuu` tiene un campo `fecha_actualizacion` que se actualiza automáticamente cada vez que se cambia de máquina.

**Query para ver historial de cambios:**
```sql
SELECT 
    maquina,
    nombre,
    IF(activa = 1, '🟢 ACTIVA', '⚪ Inactiva') as estado,
    fecha_actualizacion as ultimo_cambio
FROM configuracion_tuu
ORDER BY fecha_actualizacion DESC;
```

---

## 🔐 Seguridad

- ✅ Solo usuarios autenticados pueden cambiar máquinas
- ✅ Cambios requieren confirmación explícita
- ✅ Registro automático de cambios con timestamp
- ✅ Validación en backend de máquinas permitidas

---

## 📈 Mejoras Futuras

### v2.0 (Sugerencias)
- [ ] Log de cambios en tabla separada para auditoría completa
- [ ] Notificación automática al admin cuando se usa respaldo
- [ ] Health check automático de máquinas TUU
- [ ] Auto-failover si la máquina activa no responde
- [ ] Dashboard de estadísticas por máquina
- [ ] Alertas por email/WhatsApp cuando se cambia de máquina

---

## 🆘 Soporte

**En caso de problemas:**
1. Verificar tabla `configuracion_tuu` existe y tiene datos
2. Revisar consola del navegador (F12) por errores JS
3. Verificar que XAMPP/Apache esté corriendo
4. Probar acceso directo a API:
   - `http://localhost:8080/sistemaEstacionamiento/api/api_config_tuu.php`

**Contacto:**
- Desarrollador: [Tu información de contacto]
- Fecha de implementación: 11 de Octubre, 2025
- Versión: 1.0

