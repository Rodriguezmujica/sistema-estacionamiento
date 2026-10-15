# 🔧 Comandos para Ejecutar en MÁQUINA DE PRODUCCIÓN (Linux Mint)

## ⚠️ IMPORTANTE
Ejecuta estos comandos en la **máquina donde está conectada la impresora**, NO en la de desarrollo.

---

## 📋 Paso 1: Copiar el Archivo a Producción

En la máquina de producción (Linux Mint), navega al directorio del sistema:

```bash
cd /var/www/html/sistemaEstacionamiento
```

Si el sistema está en otra ubicación, ajusta la ruta.

---

## 🔍 Paso 2: Ejecutar el Script de Configuración

```bash
# Dar permisos (si es necesario)
chmod +x print-service-php/configurar_impresora_produccion.sh

# Ejecutar el script
./print-service-php/configurar_impresora_produccion.sh
```

---

## 📝 Paso 3: Comandos Adicionales

Ejecuta estos comandos y **copia toda la salida**:

```bash
# Ver todas las impresoras en CUPS
lpstat -a

# Ver dispositivos USB
lsusb

# Ver impresoras USB específicamente
lpinfo -v | grep -i usb

# Verificar permisos de dispositivos
ls -l /dev/usb/lp* 2>/dev/null || echo "No hay /dev/usb/lp*"
ls -l /dev/lp* 2>/dev/null || echo "No hay /dev/lp*"

# Ver cuál es la impresora por defecto
lpstat -d

# Verificar si la impresora funciona
lpstat -p 2>/dev/null
```

---

## 📤 Paso 4: Enviar la Información

**Copia y pega aquí toda la salida** de los comandos anteriores para que pueda:

1. Identificar el dispositivo exacto (`/dev/usb/lp0`, `/dev/usb/lp1`, etc.)
2. Configurar los permisos necesarios
3. Actualizar el código con la ruta correcta

---

## 🧪 Paso 5: Prueba Rápida (Después de Configurar)

Una vez que configure el código, prueba con:

```bash
# Probar impresión desde línea de comandos
curl -X POST http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php \
  -H "Content-Type: application/json" \
  -d '{
    "tipo": "test",
    "datos": {"mensaje": "Prueba desde Producción"},
    "impresora": "POSESTACIONAMIENTO"
  }'
```

---

## 💡 Tips

- Si la impresora ya imprimió la hoja de prueba en Linux, significa que **está instalada y funcionando**
- El URI que mencionaste `USB//star/BSC10%20(ESP-001)` es del driver Star
- Probablemente el dispositivo físico esté en `/dev/usb/lp0` o `/dev/usb/lp1`
- El driver Star generalmente crea la entrada en CUPS automáticamente

**🎯 Necesito que ejecutes los comandos en PRODUCCIÓN y me envíes la salida completa.**

