# Guía de Instalación para Windows 7
## Servicio de Impresión para Star BSC10

---

## ⚠️ Importante: Windows 7 Limitaciones

Node.js versiones modernas NO son compatibles con Windows 7. Por eso usaremos **PHP** (que ya tienes instalado).

---

## 📋 Requisitos

- ✅ Windows 7 (que ya tienes)
- ✅ XAMPP instalado (que ya tienes)
- ✅ Apache corriendo
- ✅ Impresora Star BSC10 conectada por USB
- ✅ Driver de impresora instalado

---

## 🚀 Instalación Paso a Paso

### **PASO 1: Instalar Driver de Impresora**

1. Conectar impresora Star BSC10 vía USB
2. Descargar driver desde: https://www.starmicronics.com/support/downloads.aspx
3. Buscar "BSC10" para Windows 7
4. Instalar driver
5. Verificar en **Panel de Control → Dispositivos e Impresoras**
6. **IMPORTANTE:** Anotar el nombre exacto de la impresora (ej: "Star BSC10")

---

### **PASO 2: Verificar que Apache esté Corriendo**

1. Abrir XAMPP Control Panel
2. Iniciar **Apache** (debe aparecer en verde)
3. Verificar que PHP funciona: 
   - Abrir navegador
   - Ir a: `http://localhost/`
   - Debe aparecer el panel de XAMPP

---

### **PASO 3: Copiar Archivos del Servicio**

Los archivos ya están en tu proyecto. Solo verifica que existan:

```
C:\xampp\htdocs\sistemaEstacionamiento\
├── print-service-php\
│   └── imprimir.php          ← Servicio de impresión
└── JS\
    └── print-service-client-win7.js  ← Cliente JavaScript
```

Si no existe la carpeta `print-service-php`, créala y copia el archivo `imprimir.php`.

---

### **PASO 4: Configurar Nombre de Impresora**

1. Abrir archivo: `print-service-php\imprimir.php`
2. Buscar la línea (aprox línea 139):
   ```php
   $nombreImpresora = $input['impresora'] ?? 'Star BSC10';
   ```
3. Cambiar `'Star BSC10'` por el nombre EXACTO de tu impresora como aparece en Windows
   - Puede ser: `"Star Micronics BSC10"`, `"BSC10"`, etc.

4. Abrir archivo: `JS\print-service-client-win7.js`
5. Buscar la línea (aprox línea 9):
   ```javascript
   nombreImpresora: 'Star BSC10',
   ```
6. Cambiar por el mismo nombre

---

### **PASO 5: Probar el Servicio**

1. Abrir navegador
2. Ir a: `http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php?action=status`
3. Debe aparecer:
   ```json
   {
     "success": true,
     "status": "online",
     "message": "Servicio de impresión PHP activo",
     "version": "1.0.0"
   }
   ```

Si aparece esto, **¡el servicio está funcionando!** ✅

---

### **PASO 6: Probar Impresión**

Crear un archivo de prueba: `test-imprimir.html` en la raíz del proyecto:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Impresión</title>
    <script src="JS/print-service-client-win7.js"></script>
</head>
<body>
    <h1>Test de Impresión</h1>
    <button onclick="probarImpresion()">🖨️ Imprimir Prueba</button>
    
    <script>
        async function probarImpresion() {
            const resultado = await PrintService.imprimirTest('Prueba desde Windows 7');
            console.log(resultado);
            alert(resultado.message);
        }
    </script>
</body>
</html>
```

Abrir en navegador: `http://localhost/sistemaEstacionamiento/test-imprimir.html`

Hacer clic en el botón. **Debe imprimir un ticket de prueba.**

---

### **PASO 7: Integrar con tu Sistema**

En tus archivos HTML, agregar:

```html
<script src="JS/print-service-client-win7.js"></script>
```

En tu código JavaScript, usar:

```javascript
// Después de registrar un ingreso:
await PrintService.imprimirTicketIngreso(
    ticketId,
    patente,
    tipoVehiculo,
    fecha,
    hora
);

// Después de procesar un cobro:
await PrintService.imprimirTicketSalida({
    ticket_id: ticketId,
    patente: patente,
    fecha_ingreso: fechaIngreso,
    fecha_salida: fechaSalida,
    tiempo_estadia: tiempo,
    monto: monto,
    metodo_pago: metodoPago,
    fecha_pago: fechaPago
});
```

---

## 🔧 Solución de Problemas

### ❌ "Servicio no disponible"
**Solución:**
- Verificar que Apache esté corriendo en XAMPP
- Ir a `http://localhost/` para confirmar

### ❌ "Error al imprimir: Failed to open"
**Solución:**
- Verificar que el nombre de la impresora sea correcto
- Abrir `Panel de Control → Dispositivos e Impresoras`
- Copiar el nombre EXACTO de la impresora
- Actualizar en `imprimir.php` y `print-service-client-win7.js`

### ❌ "Class 'Mike42\Escpos\Printer' not found"
**Solución:**
- La librería escpos-php ya está en `ImpresionTermica/ticket/`
- Verificar que el `require_once` apunte correctamente
- En `imprimir.php`, verificar la línea:
  ```php
  require_once __DIR__ . '/../ImpresionTermica/ticket/autoload.php';
  ```

### ❌ Imprime pero sale en blanco
**Solución:**
- La impresora no está recibiendo comandos correctamente
- Verificar driver instalado
- Probar imprimir un documento de prueba desde Word/Notepad

---

## ✅ Ventajas de esta Solución

✅ **Compatible con Windows 7** - No necesita Node.js moderno  
✅ **Usa lo que ya tienes** - PHP y XAMPP  
✅ **Sin instalaciones adicionales** - Todo está incluido  
✅ **Fácil de depurar** - Es PHP, puedes ver errores fácilmente  
✅ **Sin servicios complejos** - Apache ya está corriendo  

---

## 📞 ¿Necesitas Ayuda?

Si tienes problemas:

1. Verificar logs de Apache: `C:\xampp\apache\logs\error.log`
2. Abrir consola del navegador (F12) para ver errores JavaScript
3. Probar el endpoint directamente: 
   ```
   http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php?action=status
   ```

---

## 🎯 Próximos Pasos

Una vez que funcione:
1. Integrar en tus páginas de ingreso/salida
2. Probar con diferentes tipos de tickets
3. Ajustar el formato de impresión si es necesario

¡Listo! 🎉