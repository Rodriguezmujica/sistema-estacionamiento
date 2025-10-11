# 🚨 Guía Rápida: Cambio de Máquina TUU

## ⚡ En 3 Pasos Simples

### 🔴 Paso 1: Click en "Emergencia"
```
Dashboard → Botón rojo "Emergencia" (abajo a la derecha)
```
O también puedes hacer click en el badge azul/amarillo que dice "Principal" o "Respaldo" en la parte superior derecha.

---

### 👁️ Paso 2: Ver Estado Actual
El modal te mostrará:
- ✅ **Máquina activa actual** (con fondo verde)
- 📋 **Máquinas disponibles** (Principal y Respaldo)

---

### 🔄 Paso 3: Cambiar Máquina
- Click en **"Cambiar a esta máquina"** en la que quieres activar
- Confirmar cuando te pregunte
- ✅ **¡Listo!** Los pagos ahora usarán esa máquina

---

## 🎯 ¿Cuándo Usar?

### ✅ Úsalo cuando:
- ❌ La máquina TUU principal no responde
- ⏰ Pagos tardan mucho en procesar
- 🔧 Vas a hacer mantenimiento a una máquina
- 🧪 Quieres probar la máquina de respaldo

### ❌ NO lo uses:
- ✅ Si todo funciona normal con la principal
- 🤷 "Por curiosidad" durante horas pico

---

## 👀 Indicadores Visuales

### Navbar (Arriba a la derecha)
```
🟢 Principal  → Fondo AZUL    → Todo normal ✅
🟡 Respaldo   → Fondo AMARILLO → Modo emergencia ⚠️
🔴 Error      → Fondo ROJO     → Hay un problema ❌
```

**Tip:** Puedes hacer **click en el badge** para abrir rápidamente el modal de cambio.

---

## ⚙️ Primera Vez: Configurar Serial de Respaldo

**⚠️ IMPORTANTE:** La primera vez debes configurar el serial de tu máquina de respaldo.

### Paso a paso:
1. **Obtén el serial de tu segunda máquina TUU:**
   - Ve a tu panel de TUU: https://tuu.cl
   - Busca el número de serie de la segunda máquina
   - Cópialo

2. **Actualiza en la base de datos:**
   - Abre **phpMyAdmin**
   - Base de datos: `estacionamiento`
   - Tabla: `configuracion_tuu`
   - Edita el registro de `respaldo`
   - Pega el serial en `device_serial`
   - Guarda

3. **Verifica:**
   - Refresca el dashboard
   - Click en "Emergencia"
   - Deberías ver ambas máquinas con sus seriales correctos

---

## 🔄 Botón "Actualizar Datos"

**Qué hace:**
- 🔄 Recarga el estado de TUU
- 💰 Actualiza precio por minuto
- 📊 Refresca estadísticas del día
- 🚗 Actualiza últimos ingresos

**Cuándo usarlo:**
- Después de cambiar de máquina TUU
- Si los datos se ven desactualizados
- Cada vez que necesites datos frescos

---

## ❓ Preguntas Frecuentes

### ¿Puedo cambiar de máquina en medio de un cobro?
**NO.** Espera a que termine el cobro actual, luego cambia.

### ¿Los cobros anteriores se afectan?
**NO.** Solo los nuevos cobros usarán la máquina que selecciones.

### ¿Se guarda qué máquina está activa si reinicio el sistema?
**SÍ.** La configuración se guarda en la base de datos.

### ¿Puedo tener ambas máquinas activas?
**NO.** Solo una puede estar activa a la vez.

### ¿Qué pasa si la máquina de respaldo tampoco funciona?
Usa el botón **"Pago Manual"** para registrar cobros sin TUU hasta que se solucione.

---

## 🆘 Solución Rápida de Problemas

### Problema: Badge dice "Error"
**Solución rápida:**
1. Click en **"Actualizar Datos"**
2. Si persiste, verifica que XAMPP esté corriendo
3. Contacta soporte técnico

### Problema: Al cambiar máquina, cobros siguen fallando
**Solución rápida:**
1. Verifica que hayas configurado el serial correcto de la máquina respaldo
2. Click en **"Actualizar Datos"**
3. Intenta de nuevo

### Problema: Modal no abre
**Solución rápida:**
1. Refresca la página (F5)
2. Cierra sesión y vuelve a entrar
3. Revisa que tu navegador esté actualizado

---

## 📞 ¿Necesitas Ayuda?

Si nada de esto funciona:
1. Usa **"Pago Manual"** mientras tanto
2. Contacta al administrador del sistema
3. Revisa la documentación completa: `SISTEMA_EMERGENCIA_TUU.md`

---

## ✅ Checklist Rápido

Antes de usar el sistema en producción:
- [ ] Configuré el serial de la máquina respaldo
- [ ] Probé cambiar entre máquinas
- [ ] Verifiqué que el badge actualice correctamente
- [ ] Hice un cobro de prueba con cada máquina
- [ ] Todos los empleados saben cómo cambiar de máquina

---

**¡Listo para usar! 🎉**

_Última actualización: 11 de Octubre, 2025_

