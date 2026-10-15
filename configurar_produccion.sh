#!/bin/bash
# Script para configurar la impresora Star BSC10 en producción

echo "═══════════════════════════════════════════════"
echo "  🔧 CONFIGURACIÓN DE IMPRESORA STAR BSC10"
echo "═══════════════════════════════════════════════"
echo ""

# Verificar que el script se ejecuta como root o con sudo
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Este script requiere permisos de administrador"
    echo "Ejecuta: sudo ./configurar_produccion.sh"
    exit 1
fi

echo "📋 Verificando configuración actual..."
echo ""

# 1. Verificar que existe el dispositivo
if [ -e /dev/usb/lp0 ]; then
    echo "✅ Dispositivo /dev/usb/lp0 encontrado"
    ls -l /dev/usb/lp0
else
    echo "❌ No se encontró /dev/usb/lp0"
    exit 1
fi

echo ""
echo "───────────────────────────────────────────────"
echo ""

# 2. Verificar usuario de Apache/PHP
echo "📊 Usuario de Apache/PHP:"
if id www-data &>/dev/null; then
    USUARIO_APACHE="www-data"
    echo "  Usuario: $USUARIO_APACHE"
elif id apache &>/dev/null; then
    USUARIO_APACHE="apache"
    echo "  Usuario: $USUARIO_APACHE"
else
    echo "  ⚠️  No se encontró usuario www-data ni apache"
    read -p "¿Cuál es el usuario del servidor web? " USUARIO_APACHE
fi

echo ""
echo "───────────────────────────────────────────────"
echo ""

# 3. Mostrar grupos actuales del usuario
if [ ! -z "$USUARIO_APACHE" ]; then
    echo "📊 Grupos del usuario $USUARIO_APACHE:"
    groups $USUARIO_APACHE
    echo ""
fi

# 4. Dar permisos
echo "🔧 Aplicando permisos..."
echo ""

# Método 1: Agregar al grupo lp
echo "  1. Agregando $USUARIO_APACHE al grupo lp..."
usermod -a -G lp $USUARIO_APACHE 2>/dev/null

# Método 2: Cambiar permisos del dispositivo
echo "  2. Cambiando permisos de /dev/usb/lp0 a 666..."
chmod 666 /dev/usb/lp0 2>/dev/null

echo ""
echo "───────────────────────────────────────────────"
echo ""

# 5. Verificar permisos
echo "✅ Verificando permisos aplicados:"
ls -l /dev/usb/lp0
echo ""
echo "Grupos actualizados de $USUARIO_APACHE:"
groups $USUARIO_APACHE

echo ""
echo "───────────────────────────────────────────────"
echo ""

# 6. Reiniciar Apache
echo "🔄 Reiniciando Apache..."
if systemctl restart apache2 2>/dev/null; then
    echo "  ✅ Apache reiniciado con systemctl"
elif service apache2 restart 2>/dev/null; then
    echo "  ✅ Apache reiniciado con service"
else
    echo "  ⚠️  No se pudo reiniciar Apache automáticamente"
    echo "  Ejecuta manualmente: sudo systemctl restart apache2"
fi

echo ""
echo "═══════════════════════════════════════════════"
echo "  ✅ CONFIGURACIÓN COMPLETADA"
echo "═══════════════════════════════════════════════"
echo ""
echo "Próximos pasos:"
echo "1. Probar la impresión desde el navegador"
echo "2. Verificar logs si hay problemas:"
echo "   tail -f /var/log/apache2/error.log"
echo ""
echo "Comando de prueba:"
echo "curl -X POST http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"tipo\":\"test\",\"datos\":{\"mensaje\":\"Test\"},\"impresora\":\"POSESTACIONAMIENTO\"}'"
echo ""

