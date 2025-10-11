# ✅ Checklist Para Probar Integración TUU Mañana

**Fecha de creación:** 11 de Octubre, 2025  
**Objetivo:** Probar la integración real con las máquinas TUU

---

## 📋 ANTES DE EMPEZAR

### **Lo que YA tienes configurado:** ✅

| Item | Estado | Valor Actual |
|------|--------|--------------|
| **API Key** | ✅ Configurada | `uIAwXISF5...` |
| **Device Serial Principal** | ✅ Configurado | `57964` |
| **URL API** | ✅ Correcta | `RemotePayment/v2/Create` |
| **Modo Prueba** | ✅ Activo | `true` |
| **Sistema Emergencia** | ✅ Funcional | Principal/Respaldo |
| **Código de Integración** | ✅ Completo | `tuu-pago.php` |

---

## 🎯 LO QUE NECESITAS HACER MAÑANA

### **Checklist en Orden de Prioridad:**

---

## 1️⃣ **PASO 1: Verificar Credenciales TUU** 🔐

### A. Entrar al Panel de TUU
```
🌐 URL: https://tuu.cl
📧 Usuario: Tu email de registro
🔑 Contraseña: Tu contraseña
```

### B. Verificar API Key
**Ubicación en panel TUU:**
- Configuración → API → Keys
- O: Settings → Workspace → API Key

**¿Qué verificar?**
- [ ] La API Key que tienes en el código (`uIAwXISF5...`) es la correcta
- [ ] La API Key está activa (no vencida ni revocada)
- [ ] Tienes permisos de "Crear Pagos Remotos"

**Si la API Key está mal o vencida:**
```php
// Editar archivo: api/tuu-pago.php
// Línea 19:
define('TUU_API_KEY', 'TU_NUEVA_API_KEY_AQUI');
```

---

## 2️⃣ **PASO 2: Configurar Device Serial de Respaldo** 📱

### A. Prender la Máquina 2
- [ ] Conectar a corriente
- [ ] Encender dispositivo
- [ ] Esperar que termine de cargar

### B. Obtener el Device Serial
**Opción 1: Panel TUU**
```
1. Ir a: Dispositivos → Mis Dispositivos
2. Buscar la segunda máquina
3. Copiar el "Serial Number" o "Device ID"
```

**Opción 2: En el Dispositivo**
```
1. Buscar etiqueta física en la máquina
2. Buscar "Serial" o "Device ID"
3. Copiar el número
```

### C. Actualizar en el Sistema
**Método Fácil:**
```
1. Editar: sql/actualizar_serial_respaldo_MANANA.php
2. Cambiar línea 14:
   $nuevoSerial = 'SERIAL_REAL_AQUI';
3. Abrir en navegador:
   http://localhost:8080/sistemaEstacionamiento/sql/actualizar_serial_respaldo_MANANA.php
```

**O SQL directo:**
```sql
UPDATE configuracion_tuu 
SET device_serial = 'SERIAL_MAQUINA_2' 
WHERE maquina = 'respaldo';
```

---

## 3️⃣ **PASO 3: Verificar Conectividad** 🌐

### A. Verificar Red
- [ ] Las máquinas TUU están conectadas a Internet
- [ ] Pueden alcanzar: `integrations.payment.haulmer.com`
- [ ] No hay firewall bloqueando

**Test de Conectividad:**
```bash
# Desde CMD/PowerShell:
ping integrations.payment.haulmer.com
```

### B. Verificar Permiso de API
- [ ] El Workspace tiene activado "Pago Remoto"
- [ ] La API Key tiene permisos para crear pagos
- [ ] Los dispositivos están asociados al Workspace

---

## 4️⃣ **PASO 4: Hacer Prueba en MODO TEST** 🧪

### A. Mantener Modo Prueba Activo
**Ubicación:** `api/tuu-pago.php` línea 21
```php
define('TUU_MODO_PRUEBA', true); // ✅ Mantener en true primero
```

**¿Por qué?**
- ✅ No se conecta a la máquina real
- ✅ No genera boletas reales
- ✅ Simula pagos exitosos
- ✅ Puedes probar todo el flujo sin riesgo

### B. Probar el Flujo Completo
1. **Registrar un Ingreso:**
   - Patente de prueba: `TEST01`
   - Servicio: Estacionamiento por minuto
   
2. **Calcular Cobro:**
   - Buscar patente `TEST01`
   - Verificar que calcule el monto

3. **Probar Pago con TUU (Modo Prueba):**
   - Click en "Pagar con TUU"
   - Seleccionar método: Efectivo
   - El sistema simulará pago exitoso
   - **Resultado esperado:** Registro en BD con `tipo_pago='tuu'`

4. **Verificar en Base de Datos:**
   ```sql
   SELECT * FROM salidas 
   WHERE patente = 'TEST01' 
   ORDER BY fecha_salida DESC 
   LIMIT 1;
   ```
   
   **Debe mostrar:**
   - `metodo_pago = 'TUU'`
   - `tipo_pago = 'tuu'`
   - `transaction_id = 'TUU-TEST-...'`
   - `authorization_code = 'AUTH...'`

---

## 5️⃣ **PASO 5: Activar MODO PRODUCCIÓN** 🚀

### ⚠️ **SOLO SI EL PASO 4 FUNCIONÓ CORRECTAMENTE**

### A. Cambiar a Modo Producción
**Editar:** `api/tuu-pago.php` línea 21
```php
// ANTES:
define('TUU_MODO_PRUEBA', true);

// DESPUÉS:
define('TUU_MODO_PRUEBA', false); // ✅ Modo producción
```

### B. Primera Prueba Real
**⚠️ Importante:** La primera prueba real generará una boleta oficial.

1. **Registrar un Ingreso de Prueba:**
   - Patente: `PRUEREAL`
   - Servicio: Estacionamiento por minuto
   - Esperar 5 minutos

2. **Calcular Cobro:**
   - Buscar `PRUEREAL`
   - Monto será mínimo (ej: $500)

3. **Pagar con TUU (REAL):**
   - Click en "Pagar con TUU"
   - **Seleccionar Efectivo** (más fácil para prueba)
   - **Observar la Máquina TUU:**
     - Debe mostrar el monto
     - Debe pedir confirmación
     - Debe imprimir boleta

4. **Verificar Resultado:**
   - Sistema debe marcar como pagado
   - Boleta impresa en la máquina
   - Registro en BD con datos reales

---

## 6️⃣ **PASO 6: Probar Sistema de Emergencia** 🚨

### A. Probar con Máquina Principal
1. Hacer un cobro con máquina principal
2. Verificar que use serial `57964`

### B. Cambiar a Máquina de Respaldo
1. Click en "Emergencia"
2. Cambiar a "Respaldo"
3. Hacer un cobro
4. Verificar que use el serial de respaldo

### C. Volver a Principal
1. Click en "Emergencia"
2. Cambiar a "Principal"
3. Verificar badge en navbar: 🟢 Principal

---

## 7️⃣ **PASO 7: Probar Diferentes Escenarios** 🎭

### A. Pago con Tarjeta de Crédito
```
1. Registrar ingreso
2. Pagar con TUU → Seleccionar "Crédito"
3. Pasar tarjeta en máquina TUU
4. Verificar boleta y registro en BD
```

### B. Pago con Tarjeta de Débito
```
1. Registrar ingreso
2. Pagar con TUU → Seleccionar "Débito"
3. Pasar tarjeta en máquina TUU
4. Verificar boleta y registro en BD
```

### C. Pago en Efectivo
```
1. Registrar ingreso
2. Pagar con TUU → Seleccionar "Efectivo"
3. Confirmar en máquina TUU
4. Verificar boleta y registro en BD
```

### D. Factura (con RUT)
```
1. Registrar ingreso
2. Pagar con TUU
3. Seleccionar "Factura"
4. Ingresar RUT del cliente
5. TUU consultará datos en SII automáticamente
6. Verificar factura impresa
```

### E. Error de Ingreso
```
1. Registrar "Error de Ingreso"
2. Intentar cobrar
3. Debe cobrar solo $1
4. Verificar que se registre correctamente
```

### F. Servicio de Lavado
```
1. Registrar servicio de lavado con "motivos extra"
2. Agregar precio extra
3. Pagar con TUU
4. Verificar que el total incluya precio base + extra
5. Verificar que motivos_extra se guarden en BD
```

---

## 🛠️ **TROUBLESHOOTING**

### Problema 1: "Error de Autenticación"
**Causa:** API Key inválida  
**Solución:**
1. Verificar API Key en panel TUU
2. Copiar nueva API Key
3. Actualizar en `api/tuu-pago.php` línea 19

---

### Problema 2: "Device not found"
**Causa:** Device Serial incorrecto  
**Solución:**
1. Verificar serial en panel TUU
2. Verificar serial en tabla `configuracion_tuu`
3. Actualizar si es necesario:
   ```sql
   UPDATE configuracion_tuu 
   SET device_serial = 'SERIAL_CORRECTO' 
   WHERE maquina = 'principal';
   ```

---

### Problema 3: "Timeout - Máquina no responde"
**Causa:** Máquina apagada o sin conexión  
**Solución:**
1. Verificar que la máquina esté encendida
2. Verificar conexión a Internet
3. Probar cambiar a máquina de respaldo:
   - Click en "Emergencia"
   - Cambiar a respaldo

---

### Problema 4: "Payment rejected"
**Causa:** Pago rechazado por el cliente o error en tarjeta  
**Solución:**
1. Si es tarjeta: Verificar que tenga fondos
2. Si es débito: Verificar que tenga saldo
3. Intentar con otro método de pago
4. O usar "Pago Manual" como fallback

---

### Problema 5: Boleta no imprime
**Causa:** Papel agotado o impresora con problema  
**Solución:**
1. Verificar papel en la máquina TUU
2. Revisar estado de impresora en panel TUU
3. El pago YA está registrado, solo falta imprimir
4. Puedes reimprimir desde panel TUU

---

## 📊 **VERIFICACIÓN FINAL**

### Checklist de Verificación Post-Prueba

#### ✅ Credenciales
- [ ] API Key correcta y activa
- [ ] Device Serial Principal correcto (57964)
- [ ] Device Serial Respaldo configurado

#### ✅ Conectividad
- [ ] Máquinas TUU conectadas a Internet
- [ ] Sistema puede alcanzar API de TUU
- [ ] Sin errores de firewall

#### ✅ Modo Producción
- [ ] `TUU_MODO_PRUEBA = false` (solo después de probar)
- [ ] Primera prueba real exitosa
- [ ] Boleta impresa correctamente

#### ✅ Flujos de Pago
- [ ] Pago con Efectivo funciona
- [ ] Pago con Crédito funciona
- [ ] Pago con Débito funciona
- [ ] Generación de Factura funciona

#### ✅ Sistema de Emergencia
- [ ] Cambio a máquina respaldo funciona
- [ ] Badge en navbar actualiza correctamente
- [ ] Pagos con respaldo funcionan
- [ ] Volver a principal funciona

#### ✅ Casos Especiales
- [ ] Error de Ingreso ($1) funciona
- [ ] Lavados con precio extra funcionan
- [ ] Motivos extra se guardan correctamente

#### ✅ Base de Datos
- [ ] Pagos TUU se guardan con `tipo_pago='tuu'`
- [ ] Transaction ID se guarda correctamente
- [ ] Authorization code se guarda
- [ ] Método de pago se registra (efectivo/credito/debito)

---

## 🎯 **RESUMEN: ¿Qué te falta?**

### **Para Modo Prueba (Hoy/Mañana):**
✅ Ya puedes probarlo → Todo está configurado

### **Para Modo Producción (Mañana):**
1. ⏳ **Configurar Device Serial de Respaldo** (cuando prendas máquina 2)
2. ⚠️ **Verificar API Key** (entrar al panel TUU y confirmar)
3. ⚠️ **Cambiar `TUU_MODO_PRUEBA = false`** (solo después de probar en test)
4. ✅ **Hacer primera prueba real** (con efectivo, es más fácil)

---

## 📞 **Soporte Durante la Prueba**

### Si algo falla:

**1. Revisar logs de PHP:**
```
C:\xampp\apache\logs\error.log
```

**2. Revisar consola del navegador (F12):**
- Tab "Console" para errores JavaScript
- Tab "Network" para ver requests a TUU

**3. Usar Pago Manual como fallback:**
- Si TUU no responde
- Registrar pago manual
- Resolver después

**4. Contactar Soporte TUU:**
- Email: soporte@tuu.cl
- Teléfono: (verificar en su sitio)
- Chat en panel de TUU

---

## 📝 **Log de Prueba (Para llenar mañana)**

```
═══════════════════════════════════════════════════════════
FECHA: ___/___/2025
HORA INICIO: _____
PERSONA QUE PRUEBA: _______________
═══════════════════════════════════════════════════════════

✅ PASO 1: Verificar Credenciales
   API Key válida: [ ] Sí [ ] No
   Device Serial Principal: [ ] OK [ ] Falla
   Device Serial Respaldo: [ ] OK [ ] Pendiente

✅ PASO 2: Modo Prueba
   Ingreso registrado: [ ] Sí [ ] No
   Cobro calculado: [ ] Sí [ ] No
   Pago TUU simulado: [ ] Éxito [ ] Falla
   
✅ PASO 3: Modo Producción
   Cambio a producción: [ ] Hecho
   Primera prueba real: [ ] Éxito [ ] Falla
   Boleta impresa: [ ] Sí [ ] No
   
✅ PASO 4: Tipos de Pago
   Efectivo: [ ] OK [ ] Falla
   Crédito: [ ] OK [ ] Falla
   Débito: [ ] OK [ ] Falla
   Factura: [ ] OK [ ] No probado
   
✅ PASO 5: Sistema Emergencia
   Cambio a respaldo: [ ] OK [ ] Falla
   Pago con respaldo: [ ] OK [ ] Falla
   Volver a principal: [ ] OK [ ] Falla

═══════════════════════════════════════════════════════════
PROBLEMAS ENCONTRADOS:
___________________________________________________________
___________________________________________________________
___________________________________________________________

═══════════════════════════════════════════════════════════
HORA FIN: _____
RESULTADO GENERAL: [ ] ✅ TODO OK [ ] ⚠️ PROBLEMAS MENORES [ ] ❌ FALLAS CRÍTICAS
═══════════════════════════════════════════════════════════
```

---

## 🎉 **¡Listo para Probar!**

**Recuerda:**
1. ✅ Empezar en modo PRUEBA
2. ✅ Probar todos los flujos
3. ✅ Solo entonces pasar a PRODUCCIÓN
4. ✅ Tener "Pago Manual" como fallback

**¡Mucha suerte mañana! 🚀**

