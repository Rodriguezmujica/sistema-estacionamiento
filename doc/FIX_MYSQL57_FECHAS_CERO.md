# 🔧 Fix MySQL 5.7+: Problema con Fechas '0000-00-00'

**Fecha:** 11 de Octubre, 2025  
**Problema:** Error al cargar reportes en servidor Ubuntu con MySQL 5.7+

---

## ❌ **El Problema**

### **Error que aparece:**
```
Invalid default value for 'fecha_salida'
```
O:
```
Incorrect datetime value: '0000-00-00 00:00:00' for column 'fecha_salida'
```

### **¿Por qué pasa?**

| MySQL Antiguo (5.6 o menos) | MySQL Moderno (5.7+) |
|---------------------------|---------------------|
| ✅ Aceptaba `'0000-00-00 00:00:00'` | ❌ **NO** acepta `'0000-00-00 00:00:00'` |
| Era común usarlo como "fecha vacía" | Modo estricto activado por defecto |
| Sin errores | Lanza error y detiene la query |

### **¿Dónde está el problema en tu sistema?**

1. **Datos históricos** importados con fechas `'0000-00-00 00:00:00'`
2. **Queries en PHP** que comparan con ese valor
3. **Servidor Ubuntu** con MySQL 5.7+ en modo estricto

---

## ✅ **La Solución (Sin Perder Datos)**

### **Filosofía:**
- ✅ NO eliminar datos históricos
- ✅ NO romper reportes existentes
- ✅ Convertir `'0000-00-00'` → `NULL` (estándar SQL)
- ✅ Simplificar queries PHP

---

## 📋 **Pasos Para Solucionar**

### **Paso 1: Convertir Fechas '0000-00-00' a NULL en BD** 🗄️

**Ejecutar en el servidor Ubuntu:**

```bash
# Opción A: Desde el navegador
http://TU_SERVIDOR/sistemaEstacionamiento/sql/fix_fechas_cero_mysql57.php
```

**Opción B: Desde MySQL directamente**
```sql
-- Conectar a MySQL
mysql -u root -p estacionamiento

-- Ver cuántos registros tienen fecha '0000-00-00'
SELECT COUNT(*) FROM salidas WHERE fecha_salida = '0000-00-00 00:00:00';

-- Convertir a NULL (SEGURO, no pierde datos)
UPDATE salidas 
SET fecha_salida = NULL 
WHERE fecha_salida = '0000-00-00 00:00:00';

-- Verificar
SELECT COUNT(*) FROM salidas WHERE fecha_salida = '0000-00-00 00:00:00';
-- Debe mostrar: 0
```

**¿Por qué es seguro?**
- ✅ `NULL` es el estándar SQL para "sin valor"
- ✅ Tus queries ya manejan `IS NULL` con `COALESCE`
- ✅ Los reportes siguen funcionando igual
- ✅ Se conserva toda la información (patente, monto, etc.)

---

### **Paso 2: Actualizar Queries PHP** 📝

**Ejecutar en el servidor Ubuntu:**

```bash
http://TU_SERVIDOR/sistemaEstacionamiento/sql/actualizar_queries_php_mysql57.php
```

**¿Qué hace?**

Simplifica las queries de:
```php
// ANTES (incorrecto en MySQL 5.7+)
WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00'
```

A:
```php
// DESPUÉS (correcto y más limpio)
WHEN s.fecha_salida IS NULL
```

**Archivos que se actualizan automáticamente:**
- ✅ `api/api_resumen_ejecutivo.php`
- ✅ `api/api_consulta_fechas.php`
- ✅ `api/api_cierre_caja.php`

---

### **Paso 3: Verificar que Todo Funciona** 🧪

#### **Test 1: Resumen Ejecutivo**
```
1. Ir a: Administración → Resumen Ejecutivo
2. Seleccionar: Octubre 2025
3. Debe cargar sin errores
4. Verificar que muestre datos correctos
```

#### **Test 2: Consulta por Fechas**
```
1. Ir a: Reportes → Consultar por Fechas
2. Seleccionar: Desde 01/10/2025 hasta 11/10/2025
3. Debe mostrar listado de servicios
4. Verificar totales
```

#### **Test 3: Cierre de Caja**
```
1. Ir a: Reportes → Cierre de Caja
2. Seleccionar: Fecha de hoy
3. Debe mostrar resumen
4. Verificar desgloses
```

---

## 🔍 **Verificación Manual (Opcional)**

### **Desde MySQL en Ubuntu:**

```sql
-- 1. Verificar que no queden fechas '0000-00-00'
SELECT COUNT(*) as total_cero 
FROM salidas 
WHERE fecha_salida = '0000-00-00 00:00:00';
-- Debe mostrar: 0

-- 2. Ver cuántas salidas tienen fecha NULL (normal)
SELECT COUNT(*) as total_null 
FROM salidas 
WHERE fecha_salida IS NULL;
-- Mostrará un número (es normal, son salidas sin fecha)

-- 3. Probar query de reporte
SELECT 
    DATE(CASE 
        WHEN s.fecha_salida IS NULL 
        THEN i.fecha_ingreso 
        ELSE s.fecha_salida 
    END) as fecha,
    COUNT(*) as total
FROM ingresos i
LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
WHERE i.salida = 1
GROUP BY fecha
ORDER BY fecha DESC
LIMIT 10;
-- Debe mostrar datos sin errores
```

---

## 📊 **Comparación: Antes vs Después**

### **Datos en la Base de Datos:**

| Registro | ANTES | DESPUÉS | Impacto en Reportes |
|----------|-------|---------|---------------------|
| Salida completada | `fecha_salida = '2025-10-11 14:30:00'` | `fecha_salida = '2025-10-11 14:30:00'` | ✅ Sin cambios |
| Salida sin fecha | `fecha_salida = '0000-00-00 00:00:00'` | `fecha_salida = NULL` | ✅ Reportes usan `fecha_ingreso` |
| Error de ingreso | `fecha_salida = '0000-00-00 00:00:00'` | `fecha_salida = NULL` | ✅ Reportes usan `fecha_ingreso` |

### **Queries en PHP:**

```php
// ═══════════════════════════════════════════════════════════════
// ANTES: Complejo y da error en MySQL 5.7+
// ═══════════════════════════════════════════════════════════════
$sql = "SELECT 
            CASE 
                WHEN s.fecha_salida IS NULL OR s.fecha_salida = '0000-00-00 00:00:00' 
                THEN i.fecha_ingreso 
                ELSE s.fecha_salida 
            END as fecha
        FROM ingresos i
        LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos";

// ═══════════════════════════════════════════════════════════════
// DESPUÉS: Simple y compatible
// ═══════════════════════════════════════════════════════════════
$sql = "SELECT 
            CASE 
                WHEN s.fecha_salida IS NULL 
                THEN i.fecha_ingreso 
                ELSE s.fecha_salida 
            END as fecha
        FROM ingresos i
        LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos";
```

---

## ⚠️ **Preguntas Frecuentes**

### **¿Se pierden datos históricos?**
**NO.** Solo se cambia el formato de la fecha de `'0000-00-00'` a `NULL`. Toda la información (patente, monto, servicio, etc.) se conserva intacta.

### **¿Se rompen los reportes?**
**NO.** Las queries ya usan `CASE WHEN fecha_salida IS NULL THEN fecha_ingreso`, así que siguen funcionando exactamente igual.

### **¿Tengo que hacer backup?**
**Recomendado pero no crítico.** El cambio es seguro, pero siempre es buena práctica:
```bash
# Backup de la tabla salidas
mysqldump -u root -p estacionamiento salidas > backup_salidas_$(date +%Y%m%d).sql
```

### **¿Qué pasa con los nuevos registros?**
Los nuevos registros ya NO usarán `'0000-00-00'`. Si una salida no tiene fecha, se guardará como `NULL` automáticamente.

### **¿Tengo que hacer esto en Windows también?**
**Depende.** Si tu Windows tiene MySQL 5.7+ con modo estricto, sí. Si es MySQL 5.6 o anterior, funciona sin cambios. Pero es **recomendable** hacerlo para mantener consistencia.

---

## 🛠️ **Troubleshooting**

### **Error: "Access denied for user"**
**Causa:** No tienes permisos en MySQL  
**Solución:**
```bash
# Dar permisos al usuario
mysql -u root -p
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'tu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### **Error: "Table 'salidas' doesn't exist"**
**Causa:** No estás en la BD correcta  
**Solución:**
```sql
USE estacionamiento;
SHOW TABLES; -- Verificar que salidas existe
```

### **Error: "Can't connect to MySQL server"**
**Causa:** MySQL no está corriendo  
**Solución:**
```bash
# Ubuntu
sudo service mysql start
# O
sudo systemctl start mysql
```

### **Reportes siguen dando error después del fix**
**Causa 1:** Caché del navegador  
**Solución:** Ctrl + F5 para refrescar

**Causa 2:** Archivo PHP no se actualizó  
**Solución:** Volver a ejecutar `actualizar_queries_php_mysql57.php`

**Causa 3:** Hay otro campo con '0000-00-00'  
**Solución:**
```sql
-- Buscar otros campos problemáticos
SELECT * FROM ingresos WHERE fecha_ingreso = '0000-00-00 00:00:00' LIMIT 10;
-- Si aparecen, actualizar también:
UPDATE ingresos SET fecha_ingreso = NULL WHERE fecha_ingreso = '0000-00-00 00:00:00';
```

---

## 📚 **Referencias**

### **Documentación Oficial MySQL:**
- [MySQL 5.7: SQL Mode](https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html)
- [Date and Time Types](https://dev.mysql.com/doc/refman/5.7/en/datetime.html)

### **Archivos del Sistema Afectados:**
- `api/api_resumen_ejecutivo.php` - Resumen ejecutivo mensual
- `api/api_consulta_fechas.php` - Consulta por rango de fechas
- `api/api_cierre_caja.php` - Cierre de caja diario

---

## ✅ **Checklist Final**

Después de aplicar el fix, verifica:

```
[ ] Paso 1: Ejecutado fix_fechas_cero_mysql57.php
[ ] Paso 2: Ejecutado actualizar_queries_php_mysql57.php
[ ] Paso 3: Resumen Ejecutivo carga sin errores
[ ] Paso 4: Consulta por Fechas funciona
[ ] Paso 5: Cierre de Caja funciona
[ ] Paso 6: Totales coinciden con los históricos
[ ] Paso 7: Eliminados scripts de fix por seguridad
```

---

## 🎉 **¡Listo!**

Tu sistema ahora es **100% compatible con MySQL 5.7+** sin perder datos históricos ni romper reportes.

**Beneficios:**
- ✅ Compatible con versiones modernas de MySQL
- ✅ Código más limpio y mantenible
- ✅ Sin warnings ni errores en logs
- ✅ Estándar SQL correcto (NULL vs '0000-00-00')

---

**¿Necesitas ayuda?** Revisa la sección de Troubleshooting arriba o contacta soporte.

_Última actualización: 11 de Octubre, 2025_

