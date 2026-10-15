#!/bin/bash
# Script para instalar PHP GD con soporte completo en Linux Mint/Ubuntu
# Uso: sudo bash scripts/instalar_gd_linux.sh

echo "═══════════════════════════════════════════════════════════════"
echo "🔧 INSTALACIÓN DE PHP GD PARA LINUX MINT"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Por favor ejecuta con sudo${NC}"
    echo "   sudo bash scripts/instalar_gd_linux.sh"
    exit 1
fi

echo -e "${BLUE}📦 Paso 1: Actualizando lista de paquetes...${NC}"
apt-get update -qq

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error al actualizar paquetes${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Lista de paquetes actualizada${NC}"

echo ""
echo -e "${BLUE}📦 Paso 2: Instalando librerías del sistema necesarias...${NC}"
apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libgd-dev \
    zlib1g-dev \
    libfreetype6-dev \
    libwebp-dev 2>&1 | grep -E "(Setting up|Removing|is already)" || true

echo ""
echo -e "${BLUE}📦 Paso 3: Desinstalando php-gd anterior (si existe)...${NC}"
apt-get purge -y php*-gd 2>&1 | grep -E "(Removing|Setting)" || true

echo ""
echo -e "${BLUE}📦 Paso 4: Instalando PHP GD con soporte completo...${NC}"
apt-get install -y php-gd 2>&1 | grep -E "(Setting up|Removing)" || true

# Detectar versión de PHP
PHP_VERSION=$(php -v 2>/dev/null | head -n1 | grep -oP '\d+\.\d+' | head -n1)

if [ ! -z "$PHP_VERSION" ]; then
    echo ""
    echo -e "${BLUE}📦 Paso 5: Instalando php${PHP_VERSION}-gd específico...${NC}"
    apt-get install -y php${PHP_VERSION}-gd 2>&1 | grep -E "(Setting up|Removing|is already)" || true
else
    echo -e "${YELLOW}⚠️ No se pudo detectar la versión de PHP${NC}"
fi

echo ""
echo -e "${BLUE}🔄 Paso 6: Reiniciando Apache...${NC}"
systemctl restart apache2

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Apache reiniciado${NC}"
else
    echo -e "${YELLOW}⚠️ No se pudo reiniciar Apache (puede que no esté instalado)${NC}"
fi

echo ""
echo -e "${BLUE}🔍 Paso 7: Verificando instalación...${NC}"
echo ""

if php -m 2>/dev/null | grep -i gd > /dev/null; then
    echo -e "${GREEN}✅ GD está instalado${NC}"
else
    echo -e "${RED}❌ GD NO está instalado${NC}"
    exit 1
fi

echo ""
echo "📊 Información de GD:"
php -r "print_r(gd_info());" 2>/dev/null

echo ""
echo -e "${YELLOW}⚠️ Verificando soporte PNG...${NC}"
PNG_SUPPORT=$(php -r "echo gd_info()['PNG Support'] ? '1' : '0';" 2>/dev/null)

if [ "$PNG_SUPPORT" = "1" ]; then
    echo -e "${GREEN}✅ PNG Support: HABILITADO${NC}"
else
    echo -e "${RED}❌ PNG Support: DESHABILITADO${NC}"
    echo ""
    echo "Ejecuta manualmente:"
    echo "  apt-get install --reinstall php-gd"
    echo "  apt-get install libpng-dev libjpeg-dev"
    echo "  systemctl restart apache2"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ INSTALACIÓN COMPLETADA EXITOSAMENTE${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "🧪 Siguiente paso: Ejecuta el diagnóstico"
echo "   php tests/test_gd_logo.php"
echo ""
echo "💡 Si el logo aún no funciona:"
echo "   1. Verifica los logs: sudo tail -f /var/log/apache2/error.log"
echo "   2. Asegúrate de que el archivo de logo existe"
echo "   3. Verifica permisos del archivo: ls -la imagenes/image.jpg"
echo ""

