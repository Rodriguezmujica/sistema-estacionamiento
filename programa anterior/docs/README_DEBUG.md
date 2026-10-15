# 🔍 Sistema Completo de Depuración (Debugging)

## 📦 ¿Qué se ha creado?

Se ha implementado un **sistema completo de debugging/depuración** para tu sistema de estacionamiento. Ahora puedes:

- ✅ **Ver el estado** de todo el sistema en tiempo real
- ✅ **Registrar eventos** importantes automáticamente
- ✅ **Detectar errores** antes de que sean problemas grandes
- ✅ **Monitorear rendimiento** de consultas y operaciones
- ✅ **Rastrear acciones** de usuarios para auditorías

---

## 🎯 Inicio Rápido (3 minutos)

### 1. Abre el Tutorial Interactivo
```
http://localhost/sistemaEstacionamiento/tutorial_debug.html
```
👉 **Empieza aquí** - Tutorial visual y fácil de seguir

### 2. Prueba las Herramientas

#### 🔹 Panel de Diagnóstico
```
http://localhost/sistemaEstacionamiento/debug_panel.php
```
Ve el estado general del sistema: base de datos, archivos, impresoras, etc.

#### 🔹 Visor de Logs en Tiempo Real
```
http://localhost/sistemaEstacionamiento/ver_logs.php
```
Mira todos los eventos que pasan en tu sistema en vivo.

### 3. Agrega Logging a tu Código

```php
<?php
// En cualquier archivo PHP, agrega al inicio:
require_once 'debug_logger.php';

// Luego registra eventos:
DebugLogger::info("Usuario registró patente ABC123");
DebugLogger::error("No se pudo conectar a la impresora");
DebugLogger::sql("SELECT * FROM ingresos", 25.5); // 25.5ms
?>
```

---

## 📁 Archivos Creados

### Herramientas Principales

| Archivo | Descripción | Uso |
|---------|-------------|-----|
| **debug_panel.php** | Panel de diagnóstico completo | Verificar estado del sistema |
| **debug_logger.php** | Sistema de logging | Incluir en tus archivos PHP |
| **ver_logs.php** | Visor de logs en tiempo real | Ver eventos del sistema |

### Documentación y Tutoriales

| Archivo | Descripción |
|---------|-------------|
| **tutorial_debug.html** | Tutorial interactivo visual |
| **GUIA_DEBUG.md** | Guía completa de uso |
| **README_DEBUG.md** | Este archivo (resumen) |

### Ejemplos Prácticos

| Archivo | Descripción |
|---------|-------------|
| **ejemplos/calcular-cobro_CON_DEBUG.php** | Ejemplo con logging aplicado |

---

## 🚀 Cómo Empezar a Usar

### Opción 1: Ver el Tutorial (Recomendado para principiantes)
```
1. Abre: http://localhost/sistemaEstacionamiento/tutorial_debug.html
2. Sigue los pasos visuales
3. Practica con los ejemplos
```

### Opción 2: Leer la Guía Completa
```
1. Abre: GUIA_DEBUG.md
2. Lee los casos de uso
3. Aplica a tu código
```

### Opción 3: Ver Ejemplo Directo
```
1. Abre: ejemplos/calcular-cobro_CON_DEBUG.php
2. Compara con: api/calcular-cobro.php
3. Copia el patrón a tus archivos
```

---

## 💡 Casos de Uso Comunes

### 🔴 Problema: "No funciona pero no sé por qué"

**Solución:**
1. Abre `debug_panel.php` → Verifica que todo esté configurado
2. Abre `ver_logs.php` → Busca errores en rojo
3. Identifica el problema exacto

### 🟡 Problema: "El sistema está lento"

**Solución:**
1. Agrega logging con tiempos:
   ```php
   DebugLogger::sql("SELECT ...", $tiempo);
   ```
2. Abre `ver_logs.php` → Filtra por "SQL"
3. Identifica consultas lentas (>50ms)
4. Optimiza esas consultas

### 🟢 Problema: "Quiero saber qué hace cada usuario"

**Solución:**
1. Agrega logging en operaciones importantes:
   ```php
   DebugLogger::info("Usuario cobró patente ABC123", [
       'monto' => 5000,
       'metodo' => 'TUU'
   ]);
   ```
2. Abre `ver_logs.php` → Filtra por usuario
3. Ve todo el historial de acciones

---

## 📊 Tipos de Log Disponibles

```php
// Información general (operaciones normales)
DebugLogger::info("Usuario ingresó patente ABC123");

// Advertencias (algo raro pero no crítico)
DebugLogger::warning("Impresora no responde");

// Errores (algo falló)
DebugLogger::error("No se pudo conectar a la BD");

// Consultas SQL (con tiempo de ejecución)
DebugLogger::sql("SELECT * FROM ingresos", 25.5);

// Llamadas a APIs
DebugLogger::api("/api/calcular-cobro.php", "POST", $_POST);

// Impresiones de tickets
DebugLogger::print_ticket("ABC123", true); // true = éxito

// Debug detallado
DebugLogger::debug("Valor de variable X", ['x' => $x]);

// Excepciones completas
try {
    // código
} catch (Exception $e) {
    DebugLogger::exception($e);
}
```

---

## 🎯 Ventajas del Sistema

### ✅ Ahorro de Tiempo
- **Antes:** "No funciona" → 2 horas buscando el error
- **Ahora:** "No funciona" → Abres logs → 5 minutos encontrando el error

### ✅ Mejor Rendimiento
- Detectas consultas SQL lentas
- Optimizas las partes críticas del código
- Sistema más rápido y eficiente

### ✅ Seguridad y Auditoría
- Historial completo de operaciones
- Detectas intentos de hacking
- Pruebas para disputas o reclamos

### ✅ Profesionalismo
- Sistema de nivel empresarial
- Fácil de mantener y mejorar
- Documentación automática de eventos

---

## 🔧 Configuración Avanzada

### Deshabilitar Logging en Producción (opcional)
```php
// En producción, si quieres deshabilitar logging:
DebugLogger::disable();
```

### Cambiar Ubicación de Logs
```php
// En debug_logger.php, línea 15:
private static $log_file = __DIR__ . '/logs/debug.log';
// Cambia a tu ubicación preferida
```

### Ajustar Tamaño Máximo de Logs
```php
// En debug_logger.php, línea 16:
private static $max_file_size = 5242880; // 5MB
// Aumenta o disminuye según necesites
```

---

## 📞 Preguntas Frecuentes

### ¿Afecta el rendimiento del sistema?
No significativamente. El logging es muy rápido (<1ms por evento).

### ¿Los logs ocupan mucho espacio?
Se rotan automáticamente al llegar a 5MB. Puedes limpiarlos desde `ver_logs.php`.

### ¿Puedo usar esto en producción?
Sí, está diseñado para producción. Solo asegúrate de limpiar logs viejos regularmente.

### ¿Necesito instalar algo?
No, todo está incluido y listo para usar.

### ¿Cómo veo logs antiguos?
Los logs rotados se guardan con extensión `.bak` en la carpeta `logs/`.

---

## 🎓 Próximos Pasos

1. **Hoy:**
   - [ ] Abre `tutorial_debug.html`
   - [ ] Prueba `debug_panel.php`
   - [ ] Mira `ver_logs.php`

2. **Esta Semana:**
   - [ ] Lee `GUIA_DEBUG.md` completa
   - [ ] Agrega logging a 3 archivos importantes
   - [ ] Monitorea logs diariamente

3. **Este Mes:**
   - [ ] Logging en todos los archivos críticos
   - [ ] Optimiza consultas lentas detectadas
   - [ ] Usa logs para auditorías

---

## 🌟 Resumen

**Tienes ahora:**
- 🔍 Panel de diagnóstico completo
- 📝 Sistema de logging profesional
- 📊 Visor de logs en tiempo real
- 📚 Documentación y tutoriales
- 💡 Ejemplos prácticos

**Puedes:**
- ✅ Detectar errores en segundos
- ✅ Monitorear rendimiento
- ✅ Rastrear acciones de usuarios
- ✅ Mejorar la calidad del código

---

## 📌 Enlaces Rápidos

- 🎓 **Tutorial:** [tutorial_debug.html](tutorial_debug.html)
- 🔍 **Panel:** [debug_panel.php](debug_panel.php)
- 📊 **Logs:** [ver_logs.php](ver_logs.php)
- 📖 **Guía:** [GUIA_DEBUG.md](GUIA_DEBUG.md)
- 💻 **Ejemplo:** [ejemplos/calcular-cobro_CON_DEBUG.php](ejemplos/calcular-cobro_CON_DEBUG.php)

---

**¡Listo para comenzar! 🚀**

Abre `tutorial_debug.html` y empieza a depurar como un profesional.


