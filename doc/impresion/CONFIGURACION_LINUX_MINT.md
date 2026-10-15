# Configuración de Impresora Star BSC10 en Linux Mint

## 🎯 Objetivo
Migrar el sistema de impresión desde Windows 7 a Linux Mint manteniendo la funcionalidad completa.

---

## 📋 Requisitos Previos

1. ✅ Linux Mint instalado
2. ✅ Impresora Star BSC10 conectada vía USB
3. ✅ Driver oficial de Star Micronics instalado (archivo .sh)

---

## 🔧 Paso 1: Verificar Instalación del Driver

```bash
# Verificar que el driver se instaló correctamente
lsusb | grep -i star
lsusb | grep -i printer

# Si existe el comando starprint
which starprint
starprint --help
```

---

## 🔍 Paso 2: Detectar el Dispositivo de Impresora

Ejecuta el script de detección:

```bash
cd /var/www/html/sistemaEstacionamiento
php print-service-php/detectar_impresora_linux.php
```

Este script te mostrará:
- ✅ Dispositivos USB detectados
- 📄 Impresoras registradas en CUPS
- 📌 Ubicación exacta del dispositivo
- 🔧 Comandos para dar permisos

---

## 🛠️ Paso 3: Configurar Permisos

### Opción A: Si encontraste un dispositivo específico (ejemplo: /dev/usb/lp0)

```bash
# Dar permisos de lectura y escritura
sudo chmod 666 /dev/usb/lp0

# Hacer permanente (agregar regla udev)
sudo nano /etc/udev/rules.d/99-impresora-estacionamiento.rules
```

Agregar esta línea:

```
SUBSYSTEM=="usb", ATTRS{idVendor}=="0519", ATTRS{idProduct}=="0001", MODE="0666", GROUP="users"
```

```bash
# Recargar reglas udev
sudo udevadm control --reload-rules
sudo udevadm trigger
```

### Opción B: Si no encuentras dispositivo específico

Puede que necesites reiniciar con la impresora conectada:

```bash
sudo reboot
```

Después del reinicio, ejecutar nuevamente el detector:

```bash
php print-service-php/detectar_impresora_linux.php
```

---

## 🔧 Paso 4: Configurar CUPS (Alternativa)

Si quieres usar CUPS (Common Unix Printing System):

```bash
# Activar interfaz web de CUPS
sudo cupsctl WebInterface=yes

# Abrir en navegador
# http://localhost:631
```

En CUPS:
1. Ir a "Administración" → "Agregar impresora"
2. Seleccionar "Star BSC10" o "Impresora USB"
3. Nombre: `POSESTACIONAMIENTO`
4. Compartir impresora: NO
5. Imprimir página de prueba

---

## 🔄 Paso 5: Modificar el Código (Ya está hecho)

El archivo `print-service-php/imprimir.php` ya fue modificado para:
- ✅ Detectar automáticamente el sistema operativo (Windows/Linux)
- ✅ Usar `FilePrintConnector` en Linux
- ✅ Buscar automáticamente el dispositivo correcto

**No necesitas modificar nada más.**

---

## 🧪 Paso 6: Probar la Impresión

### Desde el navegador:
1. Abrir el sistema en: `http://localhost/sistemaEstacionamiento`
2. Ir a una funcionalidad que imprima (Registrar Ingreso, etc.)
3. Verificar que imprima correctamente

### Desde línea de comandos (prueba rápida):

```bash
# Probar endpoint directamente
curl -X POST http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php \
  -H "Content-Type: application/json" \
  -d '{
    "tipo": "test",
    "datos": {"mensaje": "Prueba desde Linux Mint"},
    "impresora": "POSESTACIONAMIENTO"
  }'
```

---

## 📊 Paso 7: Verificar Logs

Si algo no funciona, revisa los logs:

```bash
# Logs de PHP
tail -f /var/log/apache2/error.log

# Logs del sistema (para ver conexiones USB)
dmesg | grep -i usb
dmesg | tail -50
```

---

## ⚙️ Configuración Avanzada

### Cambiar la URL en el JavaScript (si es necesario)

Si cambiaste la ubicación del sistema, edita:

```javascript
// JS/print-service-client-win7.js (aunque diga win7, funciona en Linux también)
baseURL: 'http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php',
```

---

## 🐛 Solución de Problemas

### Problema 1: "Permission denied"
```bash
# Dar permisos
sudo chmod 666 /dev/usb/lp0

# Verificar usuario PHP (Apache)
ps aux | grep php
ps aux | grep apache2
```

### Problema 2: "No such device"
- Desconectar y reconectar la impresora
- Ejecutar `dmesg | tail` para ver eventos USB
- Verificar que el driver se instaló: `lsusb | grep star`

### Problema 3: "No se encuentra la impresora"
Ejecuta de nuevo el detector:
```bash
php print-service-php/detectar_impresora_linux.php
```

Y anota el dispositivo encontrado. Luego edita `imprimir.php` y agrega esa ruta manualmente en el array `$dispositivos_posibles`.

### Problema 4: Impresión funciona pero con errores
Revisa los tipos de ticket. Algunos pueden tener problemas con caracteres especiales en Linux.

---

## ✅ Verificación Final

1. ✅ Impresora conectada vía USB
2. ✅ Driver instalado y funcionando
3. ✅ Dispositivo con permisos (chmod 666)
4. ✅ Script de detección encuentra la impresora
5. ✅ Prueba de impresión exitosa
6. ✅ Sistema funcionando en producción

---

## 📚 Referencias

- [Documentación oficial Star Micronics](https://www.starmicronics.com/)
- [Documentación CUPS](https://www.cups.org/)
- [Mike42 Escpos (librería de impresión)](https://github.com/mike42/escpos-php)

---

## 💡 Nota Importante

El sistema ahora es **cross-platform** y funcionará automáticamente en:
- ✅ Windows 7/10/11
- ✅ Linux Mint/Ubuntu/Debian
- ✅ Cualquier sistema con PHP

La detección es automática según el sistema operativo.

