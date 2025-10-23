#!/bin/bash
# Script de instalación para PC1 (Antix)
# Sistema de Estacionamiento Los Ríos

echo "🖥️ Instalando Sistema Híbrido en PC1 (Antix)..."

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_message() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Verificar si existe el directorio web
if [ ! -d "/var/www/html" ]; then
    print_error "Directorio /var/www/html no existe"
    exit 1
fi

# Crear directorio del sistema
sudo mkdir -p /var/www/html/sistemaEstacionamiento

# Copiar archivos compartidos
print_message "Copiando archivos compartidos..."
sudo cp -r ../COMPARTIDOS/* /var/www/html/sistemaEstacionamiento/

# Copiar archivos específicos de PC1
print_message "Copiando archivos específicos de PC1..."
sudo cp -r ./* /var/www/html/sistemaEstacionamiento/

# Establecer permisos
print_message "Estableciendo permisos..."
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento

# Crear archivo de configuración
print_message "Creando archivo de configuración..."
sudo tee /var/www/html/sistemaEstacionamiento/sistema-hibrido-config.json > /dev/null << EOF
{
  "instalacion": {
    "fecha": "$(date)",
    "sistema": "PC1_ANTIX",
    "version": "1.0.0"
  },
  "sincronizacion": {
    "intervalo_automatico": 3000,
    "timeout_firebase": 10000,
    "timeout_mysql": 5000,
    "reintentos_maximos": 3
  },
  "pc": {
    "tipo": "PC1_ANTIX",
    "es_servidor_principal": true,
    "tiene_impresora": false
  },
  "firebase": {
    "proyecto": "sistemaestacionamiento-46735",
    "configurado": true
  }
}
EOF

# Verificar instalación
if [ -f "/var/www/html/sistemaEstacionamiento/sistema-hibrido.js" ]; then
    print_message "Sistema híbrido instalado correctamente en PC1"
else
    print_error "Error en la instalación"
    exit 1
fi

print_message "¡Instalación en PC1 completada exitosamente!"
print_warning "Recuerda configurar Firebase Console antes de usar el sistema"
