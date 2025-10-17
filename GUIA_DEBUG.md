# 🔍 Guía Completa de Depuración (Debugging)

## ¿Qué es el Debugging?

**Debugging** o **depuración** es el proceso de encontrar y corregir errores en tu código. Es como ser un detective que busca pistas para resolver problemas.

---

## 📁 Herramientas Creadas

### 1. **debug_panel.php** - Panel de Diagnóstico Completo

**¿Para qué sirve?**
- Verifica el estado general del sistema
- Revisa conexiones a la base de datos
- Comprueba permisos de archivos
- Detecta impresoras disponibles
- Mide el rendimiento

**¿Cómo usarlo?**
```
http://localhost/sistemaEstacionamiento/debug_panel.php
```

**¿Cuándo usarlo?**
- ✅ Cuando algo no funciona y no sabes por qué
- ✅ Después de instalar el sistema en un nuevo servidor
- ✅ Para verificar que todo esté configurado correctamente
- ✅ Para monitorear el rendimiento

---

### 2. **debug_logger.php** - Sistema de Registro de Eventos

**¿Para qué sirve?**
- Registra todo lo que pasa en tu sistema
- Guarda información de errores, advertencias e información
- Te ayuda a rastrear qué hizo cada usuario

**¿Cómo usarlo en tu código?**

```php
<?php
// 1. Incluir el logger al inicio de tu archivo
require_once 'debug_logger.php';

// 2. Registrar eventos importantes

// Información general
DebugLogger::info("Usuario ingresó patente ABC123");

// Advertencias
DebugLogger::warning("Impresora no responde, reintentando...");

// Errores
DebugLogger::error("No se pudo conectar a la base de datos");

// Consultas SQL
DebugLogger::sql("SELECT * FROM ingresos WHERE patente = 'ABC123'", 25.5); // 25.5ms

// Llamadas a APIs
DebugLogger::api("/api/calcular-cobro.php", "POST", ['patente' => 'ABC123']);

// Impresiones
DebugLogger::print_ticket("ABC123", true); // true = exitosa

// Debug general
DebugLogger::debug("Valor de variable X: " . $variable);
?>
```

**Ejemplo Práctico:**

```php
<?php
require_once '../debug_logger.php';

try {
    // Registrar inicio de operación
    DebugLogger::info("Iniciando cálculo de cobro");
    
    $patente = $_POST['patente'] ?? '';
    DebugLogger::debug("Patente recibida: $patente");
    
    // Tu código aquí...
    $monto = calcularMonto($patente);
    
    // Registrar éxito
    DebugLogger::info("Cobro calculado exitosamente", [
        'patente' => $patente,
        'monto' => $monto
    ]);
    
} catch (Exception $e) {
    // Registrar error
    DebugLogger::exception($e);
}
?>
```

---

### 3. **ver_logs.php** - Visor de Logs en Tiempo Real

**¿Para qué sirve?**
- Ver todos los eventos registrados
- Filtrar por tipo (errores, info, SQL, etc.)
- Actualización automática cada 3 segundos
- Estadísticas visuales

**¿Cómo usarlo?**
```
http://localhost/sistemaEstacionamiento/ver_logs.php
```

**Funciones principales:**
- 🔄 **Auto-refresh**: Actualiza automáticamente cada 3 segundos
- 🔍 **Filtros**: Muestra solo INFO, ERROR, WARNING, SQL, API, etc.
- 📊 **Estadísticas**: Contador de cada tipo de evento
- 🗑️ **Limpiar**: Eliminar logs antiguos

---

## 🎯 Casos de Uso Prácticos

### Caso 1: "No se imprime el ticket"

**Pasos de depuración:**

1. **Abrir el Panel de Debug:**
   ```
   http://localhost/sistemaEstacionamiento/debug_panel.php
   ```

2. **Verificar sección "Sistema de Impresión":**
   - ¿Existe el archivo ticket.php? ✅
   - ¿Está instalada la librería ESC/POS? ✅
   - ¿Se detectan impresoras? ✅

3. **Revisar los logs:**
   ```
   http://localhost/sistemaEstacionamiento/ver_logs.php
   ```
   - Filtrar por "PRINT"
   - Ver si hay errores de impresión

4. **Agregar más logging al código:**
   ```php
   // En ImpresionTermica/ticket.php
   DebugLogger::info("Intentando imprimir ticket para: $patente");
   DebugLogger::debug("Puerto seleccionado: $puerto");
   ```

---

### Caso 2: "El sistema está lento"

**Pasos de depuración:**

1. **Abrir Panel de Debug y revisar "Rendimiento":**
   - ¿Cuánto tiempo tarda la consulta SELECT? 
   - Debería ser < 50ms

2. **Agregar medición de tiempos:**
   ```php
   DebugLogger::measureTime("Calcular cobro", function() use ($patente) {
       return calcularMonto($patente);
   });
   ```

3. **Revisar logs de SQL:**
   - Filtrar por "SQL" en ver_logs.php
   - Ver qué consultas tardan más

---

### Caso 3: "Error desconocido en el cobro"

**Pasos:**

1. **Agregar logging detallado:**
   ```php
   // En api/calcular-cobro.php
   require_once '../debug_logger.php';
   
   DebugLogger::api("/api/calcular-cobro.php", "POST", $_POST);
   
   try {
       $patente = $_POST['patente'] ?? '';
       DebugLogger::debug("Buscando patente: $patente");
       
       $resultado = $conexion->query("SELECT ...");
       DebugLogger::sql("SELECT ...", $tiempo);
       
       if (!$resultado) {
           DebugLogger::error("Query falló: " . $conexion->error);
       }
       
   } catch (Exception $e) {
       DebugLogger::exception($e);
   }
   ```

2. **Revisar los logs en ver_logs.php**

3. **Ver exactamente dónde falla**

---

## 🛠️ Cómo Agregar Logging a Archivos Existentes

### Ejemplo: Agregar a `registrar-ingreso.php`

```php
<?php
// 1. Agregar al inicio del archivo
require_once '../debug_logger.php';

// 2. Registrar la petición
DebugLogger::api("/api/registrar-ingreso.php", $_SERVER['REQUEST_METHOD'], $_POST);

// 3. Antes de operaciones críticas
DebugLogger::info("Registrando ingreso de vehículo");

// Tu código actual...
$patente = $_POST['patente'] ?? '';
DebugLogger::debug("Datos recibidos", [
    'patente' => $patente,
    'tipo_servicio' => $_POST['tipo_servicio'] ?? ''
]);

try {
    // Operación de base de datos
    $inicio = microtime(true);
    $stmt = $conexion->prepare("INSERT INTO ...");
    // ...
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);
    DebugLogger::sql("INSERT INTO ingresos", $tiempo);
    
    // Éxito
    DebugLogger::info("Ingreso registrado exitosamente", [
        'id_ingreso' => $conexion->insert_id,
        'patente' => $patente
    ]);
    
} catch (Exception $e) {
    DebugLogger::exception($e);
    DebugLogger::error("Falló registro de ingreso: " . $e->getMessage());
}
?>
```

---

## 📊 Interpretando los Logs

### Tipos de Logs:

| Tipo | Color | Uso |
|------|-------|-----|
| **INFO** | 🔵 Azul | Eventos normales (ingreso registrado, cobro calculado) |
| **WARNING** | 🟡 Amarillo | Advertencias (impresora no responde, campo vacío) |
| **ERROR** | 🔴 Rojo | Errores críticos (fallo en BD, archivo no existe) |
| **DEBUG** | ⚪ Gris | Información técnica detallada |
| **SQL** | 🟢 Verde | Consultas a la base de datos |
| **API** | 🔵 Azul | Llamadas a endpoints/APIs |
| **PRINT** | 🟣 Púrpura | Operaciones de impresión |

### Ejemplo de Log:

```
[2025-10-17 14:30:25] [INFO] [192.168.1.100] [admin] Usuario ingresó patente ABC123
[2025-10-17 14:30:25] [SQL] [192.168.1.100] [admin] Query: SELECT * FROM ingresos WHERE patente = 'ABC123' (Tiempo: 15.5ms)
[2025-10-17 14:30:26] [PRINT] [192.168.1.100] [admin] Impresión EXITOSA para patente: ABC123
```

**Interpretación:**
- ✅ Todo funcionó correctamente
- ✅ La consulta SQL fue rápida (15.5ms)
- ✅ La impresión fue exitosa

---

## 🚨 Señales de Alerta

### ⚠️ Problemas Comunes:

1. **Muchos errores en logs:**
   ```
   [ERROR] No se pudo conectar a la base de datos
   ```
   → Revisar `conexion.php`

2. **Consultas SQL muy lentas:**
   ```
   [SQL] Query: SELECT ... (Tiempo: 2500ms)
   ```
   → Optimizar consulta o agregar índices

3. **Impresiones fallidas:**
   ```
   [PRINT] Impresión FALLIDA para patente: ABC123
   ```
   → Revisar conexión con impresora

4. **Muchos WARNING:**
   ```
   [WARNING] Campo patente vacío
   ```
   → Agregar validación en formularios

---

## 🔧 Modo Debug en Producción

### Para desarrollo (mostrar errores):
```php
// Al inicio de tu archivo
error_reporting(E_ALL);
ini_set('display_errors', 1);
DebugLogger::enable();
```

### Para producción (ocultar errores):
```php
// Al inicio de tu archivo
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
DebugLogger::enable(); // Aún registra logs, pero no los muestra
```

---

## 💡 Tips y Mejores Prácticas

1. **Siempre registra operaciones críticas:**
   - Ingresos de vehículos
   - Cobros realizados
   - Impresiones de tickets
   - Errores de base de datos

2. **Usa el tipo correcto de log:**
   - `INFO` → Operaciones normales
   - `WARNING` → Algo anormal pero no crítico
   - `ERROR` → Algo falló y necesita atención

3. **Agrega contexto:**
   ```php
   // ❌ Malo
   DebugLogger::error("Falló");
   
   // ✅ Bueno
   DebugLogger::error("Falló registro de ingreso", [
       'patente' => $patente,
       'error' => $conexion->error
   ]);
   ```

4. **Revisa los logs regularmente:**
   - Una vez al día en producción
   - Busca patrones de errores
   - Identifica problemas antes de que sean críticos

5. **Limpia logs antiguos:**
   - Los logs crecen rápido
   - Usa el botón "Eliminar" en ver_logs.php
   - O configura rotación automática

---

## 🎓 Resumen Rápido

| Necesitas... | Usa... |
|--------------|--------|
| Ver si todo está bien configurado | `debug_panel.php` |
| Registrar eventos en tu código | `debug_logger.php` |
| Ver qué está pasando en tiempo real | `ver_logs.php` |
| Encontrar un error | Agregar logging + ver logs |
| Medir rendimiento | `DebugLogger::measureTime()` |

---

## 📞 ¿Necesitas Más Ayuda?

1. **Revisa los ejemplos** en esta guía
2. **Abre ver_logs.php** y mira los logs en tiempo real
3. **Agrega más logging** donde sospechas que está el problema
4. **Usa debug_panel.php** para verificar la configuración

---

¡Ahora tienes un sistema completo de depuración para encontrar cualquier problema en tu código! 🎉


