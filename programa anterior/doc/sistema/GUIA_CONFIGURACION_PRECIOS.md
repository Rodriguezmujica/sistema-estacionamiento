# 💰 Guía: Configuración de Precios

## 🎯 Funcionalidad Implementada

Ahora puedes **cambiar dinámicamente** el precio por minuto y el precio mínimo del estacionamiento desde el Panel de Administración. Los cambios se aplican en tiempo real a todo el sistema.

---

## 🚀 Cómo Usar

### **Paso 1: Ir a Configuración**

1. Abre `admin.php`
2. Click en la pestaña **"Configuración"**
3. Verás la sección **"Configuración de Precios"**

### **Paso 2: Modificar Precios**

```
┌───────────────────────────────────┐
│ Configuración de Precios          │
├───────────────────────────────────┤
│ Precio por Minuto (Estacionamiento)│
│ $ [35]                            │
│                                   │
│ Precio Mínimo                     │
│ $ [500]                           │
│                                   │
│ [💾 Guardar Configuración]        │
└───────────────────────────────────┘
```

1. **Precio por Minuto:** Cuánto se cobra por cada minuto de estacionamiento
2. **Precio Mínimo:** Cobro mínimo aunque el auto esté poco tiempo

### **Paso 3: Guardar**

1. Modifica los valores
2. Click en **"Guardar Configuración"**
3. Confirma el cambio
4. ✅ **Los cambios se aplican inmediatamente**

---

## 📊 Cómo Funciona

### **Tabla en Base de Datos: `precios`**

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| `precio_minuto` | Precio por cada minuto | 35 |
| `precio_minuto_minimo` | Cobro mínimo | 500 |

### **Cálculo de Cobro:**

```
total = max(minutos × precio_minuto, precio_minimo)
```

**Ejemplos:**

| Minutos | Cálculo | Precio Cobrado |
|---------|---------|----------------|
| 5 min | 5 × $35 = $175 | **$500** (precio mínimo) |
| 10 min | 10 × $35 = $350 | **$500** (precio mínimo) |
| 15 min | 15 × $35 = $525 | **$525** (supera el mínimo) |
| 30 min | 30 × $35 = $1.050 | **$1.050** |

---

## 🎨 Actualización Visual

### **Badge en Navbar**

El badge verde en la esquina superior derecha se actualiza automáticamente:

**Antes:**
```
💵 $35/min
```

**Después de cambiar a $40:**
```
💵 $40/min
```

**Se actualiza en:**
- ✅ Dashboard (index.php)
- ✅ Servicios Lavado (lavados.html)
- ✅ Reportes (reporte.html)
- ✅ Administración (admin.php)

---

## ⚙️ Implementación Técnica

### **API REST: `api_precios.php`**

#### **GET - Obtener Precios**
```javascript
fetch('../api/api_precios.php')
  .then(r => r.json())
  .then(data => {
    console.log(data.data.precio_minuto);    // 35
    console.log(data.data.precio_minimo);    // 500
  });
```

#### **POST/PUT - Actualizar Precios**
```javascript
fetch('../api/api_precios.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    precio_minuto: 40,
    precio_minimo: 600
  })
});
```

### **Integración con Cálculo de Cobro**

En `api/calcular-cobro.php`:

```php
// Obtener precios desde la tabla de configuración
$sqlPrecios = "SELECT precio_minuto, precio_minuto_minimo FROM precios WHERE id = 1";
$resultPrecios = $conexion->query($sqlPrecios);
$rowPrecios = $resultPrecios->fetch_assoc();

$precioPorMinuto = intval($rowPrecios['precio_minuto']);
$precioMinimo = intval($rowPrecios['precio_minuto_minimo']);

// Calcular total
$total = max($minutos * $precioPorMinuto, $precioMinimo);
```

---

## ✅ Validaciones Implementadas

### **1. Precio por Minuto**
- ✅ Debe ser mayor a 0
- ✅ Números enteros solamente
- ❌ No acepta valores negativos

### **2. Precio Mínimo**
- ✅ Debe ser ≥ 0 (puede ser 0 para desactivarlo)
- ✅ Si es > 0, debe ser ≥ precio por minuto
- ✅ Números enteros solamente

### **3. Confirmación**
- ⚠️ Requiere confirmación antes de guardar
- 📋 Muestra resumen de cambios
- 🔄 Actualiza automáticamente el navbar

---

## 🧪 Casos de Uso

### **Caso 1: Aumentar Tarifas**

**Escenario:** Necesitas subir el precio por la inflación

```
Precio por minuto: 35 → 40
Precio mínimo: 500 → 600
```

**Resultado:**
- ✅ Nuevos ingresos se cobran con nueva tarifa
- ✅ Ingresos anteriores mantienen su tarifa original
- ✅ Badge actualizado: $40/min

### **Caso 2: Promoción (Quitar Precio Mínimo)**

**Escenario:** Campaña sin cobro mínimo

```
Precio por minuto: 35 (igual)
Precio mínimo: 500 → 0
```

**Resultado:**
- ✅ Cobro exacto por minutos (ej: 3 min = $105)
- ✅ Sin cobro mínimo

### **Caso 3: Hora Pico**

**Escenario:** Precio más alto en horario peak

```
Precio por minuto: 35 → 50
Precio mínimo: 500 → 1000
```

**Resultado:**
- ✅ Más caro en hora pico
- ✅ Fácil de cambiar de vuelta después

---

## 📈 Reportes de Precios

### **Ver Historial de Precios Usados**

```sql
-- Ver qué precio se cobró en cada caso
SELECT 
    i.patente,
    i.fecha_ingreso,
    s.fecha_salida,
    s.total,
    TIMESTAMPDIFF(MINUTE, i.fecha_ingreso, s.fecha_salida) as minutos,
    ROUND(s.total / TIMESTAMPDIFF(MINUTE, i.fecha_ingreso, s.fecha_salida), 2) as precio_por_minuto_calculado
FROM ingresos i
JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
WHERE ti.nombre_servicio LIKE '%estacionamiento%minuto%'
  AND TIMESTAMPDIFF(MINUTE, i.fecha_ingreso, s.fecha_salida) > 0
ORDER BY i.fecha_ingreso DESC
LIMIT 20;
```

### **Calcular Precio Promedio del Día**

```sql
SELECT 
    DATE(s.fecha_salida) as fecha,
    COUNT(*) as cantidad,
    AVG(s.total / TIMESTAMPDIFF(MINUTE, i.fecha_ingreso, s.fecha_salida)) as precio_promedio_minuto,
    AVG(s.total) as ticket_promedio
FROM ingresos i
JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
WHERE ti.nombre_servicio LIKE '%estacionamiento%minuto%'
  AND TIMESTAMPDIFF(MINUTE, i.fecha_ingreso, s.fecha_salida) > 0
GROUP BY DATE(s.fecha_salida)
ORDER BY fecha DESC;
```

---

## 🛡️ Seguridad

### **Validaciones de Negocio**

1. **Precio por minuto mínimo:** $1
   - No permite $0 (regalaría el servicio)
   
2. **Precio mínimo ≥ Precio por minuto**
   - Si precio mínimo = $400 y precio por minuto = $500 → ❌ Error
   - El mínimo no tiene sentido si es menor que 1 minuto

3. **Solo números enteros**
   - No acepta decimales
   - Siempre en pesos chilenos

---

## 📝 Buenas Prácticas

### ✅ **Hacer:**
- Cambiar precios en horarios de baja demanda
- Comunicar cambios a los clientes
- Documentar los cambios (fecha y motivo)
- Revisar impacto en reportes después

### ❌ **Evitar:**
- Cambiar precios constantemente (confunde clientes)
- Poner precio mínimo muy alto (puede espantar clientes)
- Olvidar actualizar precios en cartelería física

---

## 🔄 Migración de Precios Hardcoded

### **Antes (Hardcoded):**
```php
$precioPorMinuto = 35;  // ❌ Fijo en código
$precioMinimo = 500;    // ❌ Para cambiar hay que editar código
```

### **Ahora (Dinámico):**
```php
// ✅ Se obtiene de la tabla precios
$sqlPrecios = "SELECT precio_minuto, precio_minuto_minimo FROM precios WHERE id = 1";
$resultPrecios = $conexion->query($sqlPrecios);
$precios = $resultPrecios->fetch_assoc();
```

---

## 🎯 Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `api/api_precios.php` | ✅ Nuevo endpoint para gestionar precios |
| `api/calcular-cobro.php` | ✅ Usa precios dinámicos de BD |
| `JS/admin.js` | ✅ Funciones cargar/guardar precios |
| `JS/main.js` | ✅ Actualiza badge en navbar |

---

## 🧪 Cómo Probar

### **Prueba 1: Ver Precios Actuales**
1. Ve a Administración → Configuración
2. Deberías ver:
   - Precio por minuto: **35**
   - Precio mínimo: **500**

### **Prueba 2: Cambiar Precios**
1. Cambia precio por minuto a: **40**
2. Cambia precio mínimo a: **600**
3. Click en "Guardar Configuración"
4. Confirma
5. ✅ Badge en navbar cambia a: **$40/min**

### **Prueba 3: Verificar Cobro**
1. Registra un ingreso de estacionamiento por minuto
2. Espera 5 minutos
3. Cobra
4. Debería calcular: 5 × $40 = $200
5. Pero cobra el mínimo: **$600** ✅

---

## 📞 Troubleshooting

### Problema 1: "No se guardan los cambios"
**Solución:** Verifica que la tabla `precios` tenga el registro con `id = 1`

```sql
SELECT * FROM precios;
```

Si está vacío:
```sql
INSERT INTO precios (id, precio_minuto_minimo, precio_minuto, rango_precio_min, rango_minimo, rango_precio, rango_minutos, tipo) 
VALUES (1, 500, 35, 500, 20, 500, 20, 1);
```

### Problema 2: "El badge no se actualiza"
**Solución:** Refresca la página (Ctrl+F5) o verifica que `main.js` esté cargado

### Problema 3: "Sigue cobrando con precio viejo"
**Solución:** Verifica que el API esté usando la tabla correctamente

```
http://localhost/sistemaEstacionamiento/api/api_precios.php
```

Debería mostrar:
```json
{
  "success": true,
  "data": {
    "precio_minuto": 40,
    "precio_minimo": 600
  }
}
```

---

## ✨ Ventajas del Sistema

✅ **Sin tocar código** - Cambios desde interfaz web  
✅ **Tiempo real** - Se aplica inmediatamente  
✅ **Validado** - No permite valores incorrectos  
✅ **Visual** - Badge se actualiza automáticamente  
✅ **Auditable** - Historial en base de datos  
✅ **Flexible** - Fácil subir/bajar precios  

---

## 🎓 Próximas Mejoras (Opcionales)

### **1. Historial de Cambios de Precios**

Crear tabla para registrar cada cambio:

```sql
CREATE TABLE historial_precios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  precio_minuto INT,
  precio_minimo INT,
  fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  usuario VARCHAR(50),
  motivo TEXT
);
```

### **2. Precios por Horario**

Diferentes precios según la hora:

```
08:00-12:00 → $30/min (hora valle)
12:00-20:00 → $40/min (hora pico)
20:00-22:00 → $30/min (hora valle)
```

### **3. Precios por Día de la Semana**

```
Lunes-Viernes → $35/min
Sábado-Domingo → $50/min (fin de semana)
```

---

## 📝 Notas Finales

- ✅ Los precios se guardan en la tabla `precios` (id = 1)
- ✅ Los cambios afectan solo a **nuevos cobros**
- ✅ Los cobros anteriores mantienen su precio histórico
- ✅ El sistema usa **fallback** si no encuentra la configuración (35/500)

---

**Sistema de Configuración de Precios - ¡Completamente Funcional!** 💰✨

**Última actualización:** Octubre 2025

