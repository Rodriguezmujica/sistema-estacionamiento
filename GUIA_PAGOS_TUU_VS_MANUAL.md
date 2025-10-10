# 📋 Guía: Sistema de Pagos (TUU vs Manual)

## 🎯 Objetivo

Este sistema permite dos formas de cobro:

1. **Pago con TUU (Boleta Oficial)** → Emite boleta legal vía sistema TUU
2. **Pago Manual (Comprobante Interno)** → Genera comprobante local sin conexión a TUU

---

## 📊 Comparación

| Característica | Pago TUU | Pago Manual |
|----------------|----------|-------------|
| **Boleta oficial** | ✅ Sí (SII) | ❌ No (comprobante interno) |
| **Requiere Internet** | ✅ Sí | ❌ No |
| **Requiere TUU funcionando** | ✅ Sí | ❌ No |
| **Registro en BD** | ✅ Sí | ✅ Sí |
| **Campo tipo_pago** | `'tuu'` | `'manual'` |
| **Auditoría SII** | ✅ Válido | ❌ No válido |
| **Impresión** | Voucher TUU | Comprobante local |

---

## 🔧 Instalación (Paso a Paso)

### 1. Actualizar Base de Datos

Ejecuta **UNA SOLA VEZ** desde tu navegador:

```
http://localhost/sistemaEstacionamiento/sql/ejecutar_agregar_tipo_pago.php
```

Esto agregará el campo `tipo_pago` a la tabla `salidas`.

### 2. Verificar Actualización

Verifica que el campo se agregó correctamente:

```sql
SHOW COLUMNS FROM salidas LIKE 'tipo_pago';
```

Deberías ver:
- Campo: `tipo_pago`
- Tipo: `enum('tuu','manual')`
- Default: `manual`

### 3. Eliminar Script de Instalación

**Por seguridad**, elimina este archivo después de ejecutarlo:
```
sql/ejecutar_agregar_tipo_pago.php
```

---

## 🚀 Uso del Sistema

### Cuándo usar Pago TUU (Boleta Oficial)

✅ **Usar siempre que sea posible**
- Cliente solicita boleta
- Sistema TUU está funcionando
- Hay conexión a Internet
- Es un cobro normal/legal

### Cuándo usar Pago Manual (Comprobante Interno)

✅ **Usar solo en casos especiales:**

1. **TUU está caído** → No hay otra opción
2. **Sin Internet** → No se puede conectar a TUU
3. **Ingreso por error** → No corresponde emitir boleta real
4. **Modo test/prueba** → Para simular cobros sin generar boletas oficiales
5. **Cliente no requiere boleta** → Y prefieres no generar documentos SII

⚠️ **IMPORTANTE:** Los pagos manuales NO generan boletas oficiales válidas para el SII.

---

## 📈 Reportes y Auditoría

### Consulta de Boletas Oficiales (TUU)

```sql
SELECT 
    id_ingresos,
    fecha_salida,
    total,
    metodo_pago,
    transaction_id
FROM salidas
WHERE tipo_pago = 'tuu'
ORDER BY fecha_salida DESC;
```

### Consulta de Comprobantes Internos

```sql
SELECT 
    id_ingresos,
    fecha_salida,
    total,
    metodo_pago
FROM salidas
WHERE tipo_pago = 'manual'
ORDER BY fecha_salida DESC;
```

### Resumen Diario

```sql
SELECT 
    DATE(fecha_salida) as fecha,
    tipo_pago,
    COUNT(*) as cantidad,
    SUM(total) as total_recaudado
FROM salidas
GROUP BY DATE(fecha_salida), tipo_pago
ORDER BY fecha DESC;
```

---

## 🛡️ Buenas Prácticas

### 1. **Preferir Siempre Pago TUU**

- Es la forma legal y correcta
- Genera respaldo oficial
- Facilita auditorías del SII

### 2. **Documentar Pagos Manuales**

- Registra el motivo: "TUU caído", "Sin Internet", "Prueba", etc.
- Mantén un registro manual de respaldo
- Considera emitir boleta después cuando TUU se recupere

### 3. **Monitoreo de Comprobantes**

```sql
-- Alertar si hay muchos pagos manuales en un día
SELECT 
    DATE(fecha_salida) as fecha,
    COUNT(*) as pagos_manuales
FROM salidas
WHERE tipo_pago = 'manual'
GROUP BY DATE(fecha_salida)
HAVING COUNT(*) > 10;  -- Más de 10 es sospechoso
```

### 4. **Backup y Auditoría**

- Respalda la BD diariamente
- Revisa semanalmente la proporción TUU vs Manual
- Investiga si hay patrones anormales (ej: muchos manuales seguidos)

### 5. **Comunicación con el Cliente**

- **Con boleta TUU:** "Aquí está su boleta oficial"
- **Con comprobante manual:** "Este es un comprobante interno. ¿Desea que le enviemos la boleta después?"

---

## 🔐 Seguridad

### Prevención de Fraude

1. **Auditoría regular:** Revisa que los pagos manuales tengan justificación
2. **Límites:** Considera establecer un límite de monto para pagos manuales
3. **Logs:** Registra quién procesa cada pago manual

### Control de Acceso (Opcional)

Puedes agregar un campo para requerir contraseña al usar pago manual:

```php
// En pago-manual.php
$password_admin = isset($_POST['password_admin']) ? $_POST['password_admin'] : '';

if ($password_admin !== 'TU_PASSWORD_SEGURO') {
    echo json_encode(['success' => false, 'error' => 'Autorización requerida']);
    exit;
}
```

---

## 📞 Soporte

### Problemas Comunes

**1. TUU no responde**
- Verificar conexión a Internet
- Verificar estado del servidor TUU
- Usar pago manual como fallback

**2. Comprobante no se imprime**
- Verificar configuración de impresora
- Verificar que el servicio esté activo
- Imprimir desde navegador como alternativa

**3. Error al actualizar BD**
- Verificar que MySQL esté corriendo
- Verificar permisos de usuario
- Revisar logs de errores

---

## 📝 Notas Finales

- Mantén siempre actualizado tu sistema
- Respalda la base de datos regularmente
- Documenta cualquier cambio o incidente
- Contacta a soporte técnico si algo no funciona

---

**Versión:** 1.0  
**Última actualización:** Octubre 2025  
**Autor:** Sistema Estacionamiento Los Ríos

