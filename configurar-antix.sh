#!/bin/bash
# 🔄 SCRIPT DE CONFIGURACIÓN AUTOMÁTICA PARA ANTIX
# Este script configura automáticamente la conexión a Windows 7

echo "🔌 Configurando Antix para conectar a Windows 7..."
echo "=================================================="

# Verificar que estamos en el directorio correcto
if [ ! -f "index.php" ]; then
    echo "❌ Error: No se encontró index.php. Ejecuta este script desde el directorio del sistema."
    exit 1
fi

echo "✅ Directorio correcto detectado"

# 1. Hacer backup del archivo actual
echo "📁 Creando backup de conexion.php actual..."
if [ -f "conexion.php" ]; then
    cp conexion.php conexion.php.backup.$(date +%Y%m%d_%H%M%S)
    echo "✅ Backup creado: conexion.php.backup.$(date +%Y%m%d_%H%M%S)"
else
    echo "⚠️ No se encontró conexion.php actual"
fi

# 2. Verificar que existe el archivo de configuración para Antix
if [ ! -f "conexion-antix.php" ]; then
    echo "❌ Error: No se encontró conexion-antix.php"
    echo "💡 Asegúrate de que el archivo conexion-antix.php esté en este directorio"
    exit 1
fi

# 3. Reemplazar conexion.php con la versión para Antix
echo "🔄 Reemplazando conexion.php con configuración para Antix..."
cp conexion-antix.php conexion.php
echo "✅ conexion.php reemplazado con configuración para Antix"

# 4. Verificar que el reemplazo fue exitoso
if [ -f "conexion.php" ]; then
    echo "✅ Archivo conexion.php actualizado correctamente"
    
    # Verificar que contiene la configuración correcta
    if grep -q "192.168.3.101" conexion.php; then
        echo "✅ Configuración de Windows 7 detectada en conexion.php"
    else
        echo "❌ Error: La configuración no se aplicó correctamente"
        exit 1
    fi
else
    echo "❌ Error: No se pudo crear conexion.php"
    exit 1
fi

# 5. Configurar permisos
echo "🔧 Configurando permisos..."
chmod 644 conexion.php
echo "✅ Permisos configurados"

# 6. Mostrar información de configuración
echo ""
echo "🎉 Configuración completada exitosamente!"
echo "=========================================="
echo "📋 Configuración aplicada:"
echo "   Host: 192.168.3.101 (Windows 7)"
echo "   Usuario: antix"
echo "   Base de datos: estacionamiento"
echo "   Puerto: 3306"
echo ""

# 7. Instrucciones para probar
echo "🧪 Próximos pasos:"
echo "1. Ejecutar: http://tu-ip/test-antix.php"
echo "2. Verificar que todas las pruebas sean exitosas"
echo "3. Probar el sistema normalmente"
echo ""

# 8. Opción para revertir
echo "🔄 Para revertir a configuración local:"
echo "   cp conexion.php.backup.* conexion.php"
echo ""

echo "✅ Script completado"
