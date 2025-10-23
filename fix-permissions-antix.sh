#!/bin/bash
# 🔧 SCRIPT DE CORRECCIÓN DE PERMISOS PARA ANTIX
# Ejecutar como: chmod +x fix-permissions-antix.sh && ./fix-permissions-antix.sh

echo "🔧 Corrigiendo permisos para Sistema de Estacionamiento en Antix..."
echo "================================================================"

# Verificar que estamos en el directorio correcto
if [ ! -f "index.php" ]; then
    echo "❌ Error: No se encontró index.php. Ejecuta este script desde el directorio del sistema."
    exit 1
fi

echo "✅ Directorio correcto detectado"

# 1. Cambiar permisos de directorios principales
echo "📁 Cambiando permisos de directorios..."
chmod 0757 .
chmod 0757 api
chmod 0757 secciones
chmod 0757 JS
chmod 0757 logs
chmod 0757 imagenes
chmod 0757 scss

echo "✅ Permisos de directorios actualizados"

# 2. Cambiar permisos de archivos PHP
echo "📄 Cambiando permisos de archivos PHP..."
find . -name "*.php" -type f -exec chmod 0644 {} \;

echo "✅ Permisos de archivos PHP actualizados"

# 3. Cambiar permisos de archivos JS y CSS
echo "🎨 Cambiando permisos de archivos JS/CSS..."
find . -name "*.js" -type f -exec chmod 0644 {} \;
find . -name "*.css" -type f -exec chmod 0644 {} \;

echo "✅ Permisos de archivos JS/CSS actualizados"

# 4. Archivos de configuración sensibles (más restrictivos)
echo "🔒 Configurando permisos de archivos sensibles..."
if [ -f "config-sensible.php" ]; then
    chmod 0600 config-sensible.php
    echo "✅ config-sensible.php protegido"
fi

if [ -f "conexion.php" ]; then
    chmod 0644 conexion.php
    echo "✅ conexion.php configurado"
fi

# 5. Verificar usuario del servidor web
echo "👤 Verificando usuario del servidor web..."
WEB_USER=$(ps aux | grep -E "(apache|nginx|httpd)" | grep -v grep | head -1 | awk '{print $1}')
if [ ! -z "$WEB_USER" ]; then
    echo "✅ Usuario del servidor web: $WEB_USER"
    
    # Cambiar propietario si es necesario
    echo "🔄 Cambiando propietario de archivos..."
    sudo chown -R $WEB_USER:$WEB_USER .
    echo "✅ Propietario actualizado a $WEB_USER"
else
    echo "⚠️ No se pudo detectar usuario del servidor web"
fi

# 6. Verificar permisos finales
echo "🔍 Verificando permisos finales..."
echo "Directorio principal: $(ls -ld . | awk '{print $1}')"
echo "Directorio api: $(ls -ld api | awk '{print $1}')"
echo "Directorio secciones: $(ls -ld secciones | awk '{print $1}')"
echo "Directorio JS: $(ls -ld JS | awk '{print $1}')"

# 7. Prueba de escritura
echo "🧪 Probando escritura..."
TEST_FILE="test_write_permissions.tmp"
if touch $TEST_FILE 2>/dev/null; then
    echo "✅ Escritura funcionando correctamente"
    rm -f $TEST_FILE
else
    echo "❌ Error: No se puede escribir en el directorio"
    echo "💡 Intenta ejecutar: sudo chmod 0777 ."
fi

echo ""
echo "🎉 Corrección de permisos completada!"
echo ""
echo "📋 Próximos pasos:"
echo "1. Ejecuta nuevamente debug-admin-antix.php"
echo "2. Verifica que la sección 5 muestre 'Escribible'"
echo "3. Prueba admin.php nuevamente"
echo ""
echo "🔧 Si aún hay problemas, ejecuta:"
echo "   sudo chmod 0777 ."
echo "   sudo chown -R www-data:www-data ."
