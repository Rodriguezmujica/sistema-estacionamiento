# 🎯 Guía: Sistema de Metas Progresivas

**Nueva funcionalidad:** Las metas se cuentan de forma progresiva por cada millón adicional alcanzado.

---

## 📊 ¿Cómo Funciona?

### **Concepto:**

Cuando se alcanza la meta base, **por cada millón adicional** se suma una nueva meta alcanzada.

---

## 🔢 Ejemplos Prácticos

### **Ejemplo 1: Meta Alcanzada Exacta**
```
Meta Base: $6.700.000
Ingresos:  $6.700.000

Resultado: 🎯 1 meta alcanzada!
```

### **Ejemplo 2: Superando por Menos de 1 Millón**
```
Meta Base: $6.700.000
Ingresos:  $7.200.000

Resultado: 🎯 1 meta alcanzada!
           +$500.000 hacia siguiente meta (50%)
```

### **Ejemplo 3: Superando por Más de 1 Millón**
```
Meta Base: $6.700.000
Ingresos:  $7.700.000

Resultado: 🎯🎯 2 metas alcanzadas!
```

### **Ejemplo 4: Superando por 2 Millones Completos**
```
Meta Base: $6.700.000
Ingresos:  $8.700.000

Resultado: 🎯🎯🎯 3 metas alcanzadas!
```

### **Ejemplo 5: Superando por 2.5 Millones**
```
Meta Base: $6.700.000
Ingresos:  $9.200.000

Excedente: $2.500.000
Metas por excedente: 2 (2 millones completos)
Sobrante hacia siguiente: $500.000 (50%)

Resultado: 🎯🎯🎯 3 metas alcanzadas!
           +$500.000 hacia siguiente meta (50%)
```

---

## 🧮 Fórmula de Cálculo

```
1. SI ingresos >= meta_base:
   metas_alcanzadas = 1
   
2. Calcular excedente:
   excedente = ingresos - meta_base
   
3. Por cada millón completo en el excedente:
   metas_adicionales = floor(excedente / 1.000.000)
   metas_alcanzadas = 1 + metas_adicionales
   
4. Calcular progreso hacia siguiente meta:
   sobrante = excedente % 1.000.000
   porcentaje_siguiente = (sobrante / 1.000.000) * 100
```

---

## 💻 Implementación Técnica

### **Backend (PHP):**

Archivo: `api/api_resumen_ejecutivo.php`

```php
// Calcular metas alcanzadas
$metasAlcanzadas = 0;

if ($totalParaMeta >= $metaMonto) {
    // Meta base alcanzada
    $metasAlcanzadas = 1;
    
    // Excedente
    $excedente = $totalParaMeta - $metaMonto;
    
    // Por cada millón adicional
    $metasAdicionales = floor($excedente / 1000000);
    $metasAlcanzadas += $metasAdicionales;
    
    // Progreso hacia siguiente meta
    $metasSobrantes = $excedente % 1000000;
    $porcentajeMetaSobrante = ($metasSobrantes / 1000000) * 100;
}
```

### **Frontend (JavaScript):**

Archivo: `JS/admin.js`

```javascript
// Mostrar metas alcanzadas
const metasAlcanzadas = meta.metas_alcanzadas || 0;
if (metasAlcanzadas > 0) {
    // Iconos de trofeos
    let iconosMetas = '🎯'.repeat(metasAlcanzadas);
    
    // Mensaje
    document.getElementById('meta-falta').innerHTML = 
        `<span class="text-success">${iconosMetas} ${metasAlcanzadas} meta(s) alcanzada(s)!</span>`;
    
    // Progreso hacia siguiente
    if (metasSobrantes > 0) {
        document.getElementById('meta-falta').innerHTML += 
            `<br><small>+$${metasSobrantes} hacia siguiente meta (${porcentajeSobrante}%)</small>`;
    }
}
```

---

## 🎨 Visualización en el Sistema

### **En el Panel de Administración:**

```
┌─────────────────────────────────────────────┐
│  📊 RESUMEN EJECUTIVO                       │
├─────────────────────────────────────────────┤
│                                             │
│  Meta Base:   $6.700.000                    │
│  Logrado:     $9.200.000                    │
│  Estado:      🎯🎯🎯 3 meta(s) alcanzada(s)! │
│               +$500.000 hacia siguiente     │
│                meta (50%)                   │
│                                             │
│  Progreso: [████████████████] 137.3%        │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 🎁 Ventajas del Sistema

1. **Motivación Continua:**
   - No solo alcanzar la meta, sino superarla
   - Metas incrementales mantienen motivado al equipo

2. **Transparencia:**
   - Se ve claramente cuántas metas se han logrado
   - Progreso visual hacia la siguiente

3. **Reconocimiento:**
   - Cada millón adicional es un logro celebrable
   - Los trofeos 🎯 son visualmente atractivos

4. **Flexibilidad:**
   - La meta base sigue siendo modificable
   - El sistema se adapta automáticamente

---

## ⚙️ Configuración

### **Cambiar la Meta Base:**

1. Ir a **Panel de Administración**
2. Sección **Resumen Ejecutivo**
3. Ingresar nueva meta mensual
4. Guardar

**Notas:**
- La meta base es el primer objetivo
- Por cada millón adicional sobre esta meta, se suma otra meta
- El cambio aplica solo al mes seleccionado

---

## 📈 Ejemplos de Uso Real

### **Escenario 1: Mes Bueno**
```
Meta: $6.700.000
Resultado: $8.900.000

Análisis:
- Meta base: ✅ Alcanzada
- Excedente: $2.200.000
- Metas adicionales: 2
- Total: 🎯🎯🎯 3 metas!
- Progreso siguiente: $200.000 (20%)

Mensaje al equipo:
"¡Excelente! Logramos 3 metas este mes.
Superamos la meta base por $2.200.000"
```

### **Escenario 2: Mes Regular**
```
Meta: $6.700.000
Resultado: $6.950.000

Análisis:
- Meta base: ✅ Alcanzada
- Excedente: $250.000
- Metas adicionales: 0 (menos de 1 millón)
- Total: 🎯 1 meta
- Progreso siguiente: $250.000 (25%)

Mensaje al equipo:
"¡Bien! Alcanzamos la meta.
Ya tenemos $250.000 hacia la siguiente"
```

### **Escenario 3: Mes Excepcional**
```
Meta: $6.700.000
Resultado: $11.200.000

Análisis:
- Meta base: ✅ Alcanzada
- Excedente: $4.500.000
- Metas adicionales: 4
- Total: 🎯🎯🎯🎯🎯 5 metas!
- Progreso siguiente: $500.000 (50%)

Mensaje al equipo:
"¡INCREÍBLE! 5 metas alcanzadas.
Superamos la meta base por $4.500.000"
```

---

## 🔧 Mantenimiento

### **Verificar Cálculos:**

Puedes verificar manualmente:
```
1. Meta base: $6.700.000
2. Ingresos del mes: $X
3. Si X >= Meta base:
   - Excedente = X - 6.700.000
   - Metas adicionales = floor(Excedente / 1.000.000)
   - Total metas = 1 + Metas adicionales
```

### **Logs:**

Los cálculos se realizan en tiempo real en la API.
No se guardan en base de datos, solo se calculan al consultar.

---

## 🎯 Consejos de Uso

1. **Establecer Meta Realista:**
   - La meta base debe ser alcanzable pero desafiante
   - Considera el promedio de meses anteriores

2. **Comunicar al Equipo:**
   - Explica cómo funciona el sistema de metas progresivas
   - Celebra cada meta alcanzada

3. **Monitoreo:**
   - Revisa el progreso regularmente
   - Ajusta estrategias según los resultados

4. **Incentivos:**
   - Considera bonos o reconocimientos por metas adicionales
   - El sistema facilita la transparencia en los logros

---

## ❓ Preguntas Frecuentes

### **¿La meta siempre incrementa por millones?**
Sí, cada millón completo sobre la meta base suma una meta adicional.

### **¿Qué pasa si no alcanzamos la meta base?**
Se muestra cuánto falta para alcanzarla (como antes).

### **¿Puedo cambiar el incremento (ej: cada $500.000)?**
Sí, se puede modificar en el código PHP:
```php
// Cambiar 1000000 por 500000
$metasAdicionales = floor($excedente / 500000);
```

### **¿Se guardan las metas alcanzadas en la BD?**
No, se calculan dinámicamente cada vez que se consulta.
Esto permite cambiar la meta base sin perder historial.

---

**Fecha de Implementación:** 13 de Octubre, 2025  
**Versión:** 2.1 - Metas Progresivas


