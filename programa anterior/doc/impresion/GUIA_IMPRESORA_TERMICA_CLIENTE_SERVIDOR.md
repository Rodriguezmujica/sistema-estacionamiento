# Guía de Instalación: Impresora Térmica Star BSC10 en Ambiente Cliente-Servidor

## 📋 Escenario
- **Servidor:** Apache/PHP en una máquina
- **Cliente (Producción):** PC donde se conecta físicamente la impresora Star BSC10
- **Objetivo:** Imprimir desde el navegador del cliente hacia la impresora local

---

## 🎯 SOLUCIÓN 1: Servicio Local de Impresión con Node.js (RECOMENDADA)

### Paso 1: Instalar Node.js en PC de Producción
1. Descargar Node.js desde https://nodejs.org/ (versión LTS)
2. Instalar con opciones por defecto

### Paso 2: Instalar Driver de Impresora Star BSC10
1. Descargar driver desde sitio oficial de Star Micronics
2. Conectar impresora vía USB
3. Instalar driver según sistema operativo
4. Verificar que aparece en "Dispositivos e Impresoras" de Windows

### Paso 3: Configurar Servicio Local
```bash
# En la PC de producción, crear carpeta
mkdir C:\PrintService
cd C:\PrintService

# Inicializar proyecto Node.js
npm init -y

# Instalar dependencias
npm install express cors escpos escpos-usb
```

### Paso 4: Crear el Servicio
Ver archivo: `print-service/server.js`

### Paso 5: Ejecutar como Servicio de Windows
```bash
# Instalar node-windows globalmente
npm install -g node-windows

# Ver archivo: install-service.js
```

### Paso 6: Configurar en tu Sistema PHP
- Modificar los archivos PHP para enviar datos a `http://localhost:3000/print`
- En lugar de imprimir directo, hacer petición AJAX/Fetch

---

## 🎯 SOLUCIÓN 2: Impresora Compartida en Red Windows

### Paso 1: Compartir Impresora en PC de Producción
1. Panel de Control → Dispositivos e Impresoras
2. Clic derecho en Star BSC10 → Propiedades
3. Pestaña "Compartir" → Compartir esta impresora
4. Nombre compartido: `StarBSC10`

### Paso 2: Dar Permisos
1. En la PC de producción, permitir compartir archivos e impresoras
2. Configurar firewall para permitir conexiones
3. Anotar nombre de la PC: `PC-PRODUCCION`

### Paso 3: Conectar desde Servidor
```php
<?php
// En el servidor PHP
$connector = new Escpos\PrintConnectors\WindowsPrintConnector("\\\\PC-PRODUCCION\\StarBSC10");
$printer = new Escpos\Printer($connector);
$printer->text("Test de impresión\n");
$printer->cut();
$printer->close();
?>
```

### Paso 4: Configurar Credenciales (si es necesario)
Si requiere autenticación, en el servidor crear credenciales:
```bash
net use \\PC-PRODUCCION\StarBSC10 /user:USUARIO PASSWORD
```

---

## 🎯 SOLUCIÓN 3: Adaptador de Red para Impresora

### Hardware Requerido
- Print Server USB to Ethernet (ej: TP-Link TL-PS110U)
- Costo aproximado: $30-50 USD

### Configuración
1. Conectar impresora al adaptador vía USB
2. Conectar adaptador a la red ethernet
3. Configurar IP estática en el adaptador
4. Desde PHP, usar la IP directamente:

```php
<?php
$connector = new Escpos\PrintConnectors\NetworkPrintConnector("192.168.1.X", 9100);
?>
```

---

## 📊 Comparación de Soluciones

| Característica | Servicio Local | Red Windows | Adaptador Red |
|---------------|---------------|-------------|---------------|
| **Complejidad** | Media | Baja | Baja |
| **Costo** | Gratis | Gratis | $30-50 USD |
| **Confiabilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Velocidad** | Rápida | Media | Rápida |
| **Mantenimiento** | Bajo | Medio | Bajo |
| **Compatibilidad** | Universal | Solo Windows | Universal |

---

## ✅ Recomendación Final

**Para tu caso (Star BSC10):** Usar **Solución 1: Servicio Local con Node.js**

**Razones:**
1. No depende de configuraciones de red complejas
2. Fácil de mantener y actualizar
3. Funciona en cualquier navegador
4. No requiere hardware adicional
5. Puedes manejar colas de impresión
6. Mejor control de errores

---

## 🚀 Próximos Pasos

1. ¿Qué solución prefieres implementar?
2. ¿La PC de producción y el servidor están en la misma red local?
3. ¿Tienes acceso administrativo en ambas máquinas?

Según tu respuesta, puedo generar los archivos específicos para tu implementación.

