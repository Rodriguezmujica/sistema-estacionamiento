# 🔌 Guía de Integración con TUU

## 📋 Checklist de Integración

Cuando tengas acceso a la máquina TUU, sigue estos pasos:

### 1. Habilitar Modo Integración en TUU

Según la [documentación oficial de TUU](https://help.tuu.cl/productos/93c77KwpDi8NRzmV7v2nUy/habilitar-modo-integraci%C3%B3n-en-app-pago-desde-el-espacio-de-trabajo/6jL4QXvjNhSAaHGQ78iswa):

1. **Ingresa a espacio.haulmer.com** con tu cuenta titular
2. En la barra lateral selecciona **Pagos**
3. Ingresa en **Pagos Haulmer**
4. Selecciona el **dispositivo** que deseas configurar
5. En **HABILITAR ACCESOS** → activa **Modo Integración**
6. Haz clic en **Guardar** (botón superior derecho)

⚠️ **IMPORTANTE:** Espera unos momentos hasta que los cambios se apliquen al dispositivo.

### 2. Preparar la Base de Datos
```bash
# Ejecutar el script SQL para agregar campos necesarios
mysql -u root -p estacionamiento < sql/agregar_campos_tuu.sql
```

Este script agrega los siguientes campos a la tabla `salidas`:
- `metodo_pago` - 'EFECTIVO' o 'TUU'
- `transaction_id` - ID de transacción de TUU
- `authorization_code` - Código de autorización
- `card_type` - Tipo de tarjeta (VISA, MASTERCARD, etc.)
- `card_last4` - Últimos 4 dígitos

---

### 3. Obtener Credenciales desde el Espacio de Trabajo TUU

Una vez habilitado el Modo Integración, necesitas obtener las credenciales:

1. En **espacio.haulmer.com** → **Pagos** → **Pagos Haulmer**
2. Selecciona tu dispositivo
3. Busca las credenciales de integración (API Key, Token, etc.)
4. Copia estos datos

**Información que necesitas:**
- URL del endpoint de integración
- API Key o Token de acceso
- ID del comercio (Merchant ID)
- ID del terminal/dispositivo

### 4. Configurar Credenciales en el Sistema

Edita el archivo `api/tuu-pago.php` y actualiza las constantes (líneas 16-19):

```php
define('TUU_API_URL', 'URL_DEL_ENDPOINT_AQUI'); // URL desde espacio.haulmer.com
define('TUU_API_KEY', 'TU_API_KEY_AQUI'); // API Key desde el espacio
define('TUU_MERCHANT_ID', 'TU_MERCHANT_ID_AQUI'); // ID del comercio
define('TUU_MODO_PRUEBA', false); // Cambiar a false para producción
```

---

### 3. Adaptar la Comunicación con TUU

El archivo `api/tuu-pago.php` tiene una función `procesarPagoTUU()` (líneas 42-118) que debes adaptar según la documentación oficial de TUU.

**Información que probablemente necesites enviar:**
- Monto de la transacción
- ID único de transacción
- Descripción del pago
- Datos del comercio (Merchant ID)

**Información que probablemente recibas:**
- Estado del pago (aprobado/rechazado)
- ID de transacción
- Código de autorización
- Tipo y últimos dígitos de tarjeta

---

### 4. Probar en Modo Prueba

Antes de usar la máquina real:

1. Verifica que `TUU_MODO_PRUEBA = true` en `api/tuu-pago.php`
2. Haz una prueba de cobro:
   - Ve a "Cobro de Salidas"
   - Busca un ticket
   - Haz clic en "💳 Pagar con TUU"
3. Verifica en la consola (F12) los logs

**En modo prueba:**
- ✅ No se conecta a TUU real
- ✅ Simula pagos (90% aprobados, 10% rechazados)
- ✅ Registra en la BD con datos simulados
- ✅ Muestra "(MODO PRUEBA)" en las alertas

---

### 5. Activar Modo Producción

Cuando estés listo para usar la máquina real:

```php
// En api/tuu-pago.php línea 19
define('TUU_MODO_PRUEBA', false); // ← Cambiar a false
```

---

## 📡 Flujo de Integración Implementado

```
┌─────────────────┐
│  Cliente busca  │
│     ticket      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Calcula costo  │  ← api/calcular-cobro.php
│  ($35/min o     │
│  precio fijo)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Cliente elige:  │
│ ┌─────────────┐ │
│ │  EFECTIVO   │ │ ← api/registrar-salida.php
│ └─────────────┘ │
│ ┌─────────────┐ │
│ │     TUU     │ │ ← api/tuu-pago.php
│ └─────────────┘ │
└────────┬────────┘
         │
         ▼ (si TUU)
┌─────────────────┐
│ Procesa pago    │
│ con máquina TUU │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌────────┐
│Aprobado│ │Rechazado│
└───┬────┘ └───┬────┘
    │          │
    ▼          ▼
┌────────┐ ┌────────┐
│Registra│ │Muestra │
│ salida │ │ error  │
│en BD   │ │ al usr│
└───┬────┘ └────────┘
    │
    ▼
┌────────────────┐
│ Imprime ticket │
│   de salida    │
└────────────────┘
```

---

## 🧪 Cómo Probar

### Prueba en Modo Prueba (sin máquina TUU):

1. Ingresa un vehículo
2. Ve a "Cobro de Salidas"
3. Busca la patente
4. Haz clic en "💳 Pagar con TUU"
5. Confirma el pago
6. Verifica que aparezca:
   - "✅ Pago aprobado con TUU (MODO PRUEBA)"
   - Código de autorización
   - Datos de tarjeta simulados

### Prueba en Modo Producción (con máquina TUU):

1. Conecta la máquina TUU
2. Configura las credenciales
3. Cambia `TUU_MODO_PRUEBA` a `false`
4. Realiza una transacción de prueba
5. Verifica en la base de datos que se guardó correctamente

---

## 📊 Campos en Base de Datos

**Tabla `salidas`:**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `metodo_pago` | VARCHAR(50) | 'EFECTIVO' o 'TUU' |
| `transaction_id` | VARCHAR(100) | ID de transacción TUU |
| `authorization_code` | VARCHAR(100) | Código de autorización |
| `card_type` | VARCHAR(50) | Tipo de tarjeta |
| `card_last4` | VARCHAR(4) | Últimos 4 dígitos |

**Consulta de ejemplo:**
```sql
SELECT * FROM salidas 
WHERE metodo_pago = 'TUU' 
ORDER BY fecha_salida DESC 
LIMIT 10;
```

---

## 🔧 Solución de Problemas

### Problema: "Error de comunicación con TUU"
**Solución:**
1. Verifica que la URL de TUU sea correcta
2. Verifica que el API Key sea válido
3. Revisa los logs: `tail -f /var/log/apache2/error.log`

### Problema: "Pago rechazado"
**Posibles causas:**
- Tarjeta sin fondos
- Tarjeta bloqueada
- Error en los datos enviados
- Timeout de conexión

### Problema: "No se registra en la BD"
**Solución:**
1. Verifica que los campos existan: `DESCRIBE salidas;`
2. Revisa la consola del navegador (F12)
3. Verifica permisos de la base de datos

---

## 📞 Contacto TUU / Haulmer

Cuando tengas dudas sobre la integración:

- **Espacio de Trabajo**: https://espacio.haulmer.com
- **Centro de Ayuda**: https://help.tuu.cl
- **Documentación Modo Integración**: [Ver aquí](https://help.tuu.cl/productos/93c77KwpDi8NRzmV7v2nUy/habilitar-modo-integraci%C3%B3n-en-app-pago-desde-el-espacio-de-trabajo/6jL4QXvjNhSAaHGQ78iswa)
- **Sitio Oficial TUU**: https://tuu.cl
- **Contacto**: Desde el espacio de trabajo o centro de ayuda

---

## ✅ Checklist Final

Antes de poner en producción, verifica:

- [ ] Script SQL ejecutado
- [ ] Credenciales configuradas
- [ ] Función `procesarPagoTUU()` adaptada
- [ ] Pruebas en modo prueba exitosas
- [ ] Pruebas con máquina real exitosas
- [ ] `TUU_MODO_PRUEBA` en `false`
- [ ] Logs funcionando correctamente
- [ ] Tickets se imprimen correctamente

---

**Última actualización:** Octubre 2025
**Autor:** Sistema de Estacionamiento Los Ríos

