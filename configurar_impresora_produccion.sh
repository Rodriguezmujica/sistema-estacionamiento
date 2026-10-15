#!/bin/bash

echo "═══════════════════════════════════════════════════════"
echo "  🔧 CONFIGURADOR DE IMPRESORA STAR BSC10 - PRODUCCIÓN"
echo "═══════════════════════════════════════════════════════"
echo ""

echo "📋 1. Verificando impresoras conectadas..."
echo ""

# Ver impresoras en CUPS
echo "Impresoras registradas en CUPS:"
lpstat -p 2>/dev/null || echo "CUPS no disponible o sin impresoras"
echo ""

# Ver impresoras USB con información detallada
echo "📌 2. Dispositivos USB de impresora detectados:"
ls -la /dev/usb/lp* 2>/dev/null || echo "No se encontraron /dev/usb/lp*"
ls -la /dev/lp* 2>/dev/null || echo "No se encontraron /dev/lp*"
echo ""

# Ver dispositivos USB conectados
echo "🔌 3. Dispositivos USB conectados (lsusb):"
lsusb
echo ""

# Ver información específica de Star
echo "4. Buscando dispositivos Star específicamente:"
lsusb | grep -i star || echo "No se encontró dispositivo Star en USB"
echo ""

# Ver los últimos mensajes del sistema (últimos 50)
echo "📊 5. Últimos eventos del sistema (USB):"
dmesg | grep -i "usb\|printer" | tail -20
echo ""

# Ver información de CUPS
echo "6. Información de CUPS:"
lpstat -a 2>/dev/null || echo "No hay impresoras disponibles en CUPS"
echo ""

# Ver la impresora por defecto
echo "7. Impresora por defecto:"
lpstat -d 2>/dev/null || echo "No hay impresora por defecto"
echo ""

echo "═══════════════════════════════════════════════════════"
echo "  ✅ INFORMACIÓN NECESARIA PARA CONFIGURACIÓN"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "Por favor, envía la salida completa de este script."
echo "Esto me ayudará a configurar la ruta exacta del dispositivo."
echo ""
echo "Ejecuta también este comando adicional si tienes CUPS:"
echo "  lpinfo -v"
echo ""

