#!/bin/bash
# Script de instalación del Sistema Híbrido
# Sistema de Estacionamiento Los Ríos

echo "🔄 Instalando Sistema Híbrido..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_message() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# Detectar sistema operativo
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
    print_info "Sistema Linux detectado (Antix)"
elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
    OS="windows"
    print_info "Sistema Windows detectado"
else
    OS="unknown"
    print_warning "Sistema operativo no reconocido"
fi

# Función para instalar en Linux (Antix)
install_linux() {
    print_info "Instalando en Linux (Antix)..."
    
    # Verificar si existe el directorio web
    if [ ! -d "/var/www/html" ]; then
        print_error "Directorio /var/www/html no existe"
        exit 1
    fi
    
    # Crear directorio del sistema
    sudo mkdir -p /var/www/html/sistemaEstacionamiento
    
    # Copiar archivos del sistema híbrido
    print_info "Copiando archivos del sistema híbrido..."
    
    # Archivos de configuración
    sudo cp firebase-config.js /var/www/html/sistemaEstacionamiento/
    sudo cp firebase-config.php /var/www/html/sistemaEstacionamiento/
    sudo cp config-sistema-hibrido.js /var/www/html/sistemaEstacionamiento/
    sudo cp sync-config.js /var/www/html/sistemaEstacionamiento/
    
    # Archivos del sistema híbrido
    sudo cp firebase-sync.js /var/www/html/sistemaEstacionamiento/
    sudo cp pc-detector.js /var/www/html/sistemaEstacionamiento/
    sudo cp sistema-hibrido.js /var/www/html/sistemaEstacionamiento/
    
    # Crear directorio de APIs
    sudo mkdir -p /var/www/html/sistemaEstacionamiento/api
    sudo cp api/*.php /var/www/html/sistemaEstacionamiento/api/
    
    # Crear directorio de reglas de seguridad
    sudo mkdir -p /var/www/html/sistemaEstacionamiento/firebase-security-rules
    sudo cp firebase-security-rules/* /var/www/html/sistemaEstacionamiento/firebase-security-rules/
    
    # Archivos de prueba
    sudo cp test-sistema-completo.html /var/www/html/sistemaEstacionamiento/
    sudo cp README-SISTEMA-HIBRIDO.md /var/www/html/sistemaEstacionamiento/
    
    # Establecer permisos
    sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
    sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
    
    print_message "Instalación en Linux completada"
}

# Función para instalar en Windows
install_windows() {
    print_info "Instalando en Windows..."
    
    # Verificar si existe XAMPP
    if [ ! -d "C:/xampp/htdocs" ]; then
        print_error "XAMPP no encontrado en C:/xampp/htdocs"
        exit 1
    fi
    
    # Crear directorio del sistema
    mkdir -p "C:/xampp/htdocs/sistemaEstacionamiento"
    
    # Copiar archivos del sistema híbrido
    print_info "Copiando archivos del sistema híbrido..."
    
    # Archivos de configuración
    cp firebase-config.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp firebase-config.php "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp config-sistema-hibrido.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp sync-config.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    
    # Archivos del sistema híbrido
    cp firebase-sync.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp pc-detector.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp printing-manager.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp sistema-hibrido.js "C:/xampp/htdocs/sistemaEstacionamiento/"
    
    # Crear directorio de APIs
    mkdir -p "C:/xampp/htdocs/sistemaEstacionamiento/api"
    cp api/*.php "C:/xampp/htdocs/sistemaEstacionamiento/api/"
    
    # Crear directorio de reglas de seguridad
    mkdir -p "C:/xampp/htdocs/sistemaEstacionamiento/firebase-security-rules"
    cp firebase-security-rules/* "C:/xampp/htdocs/sistemaEstacionamiento/firebase-security-rules/"
    
    # Archivos de prueba
    cp test-sistema-completo.html "C:/xampp/htdocs/sistemaEstacionamiento/"
    cp README-SISTEMA-HIBRIDO.md "C:/xampp/htdocs/sistemaEstacionamiento/"
    
    print_message "Instalación en Windows completada"
}

# Función para crear archivo de configuración
create_config() {
    print_info "Creando archivo de configuración..."
    
    cat > sistema-hibrido-config.json << EOF
{
  "instalacion": {
    "fecha": "$(date)",
    "sistema": "$OS",
    "version": "1.0.0"
  },
  "sincronizacion": {
    "intervalo_automatico": 5000,
    "timeout_firebase": 10000,
    "timeout_mysql": 5000,
    "reintentos_maximos": 3
  },
  "pc": {
    "tipo": "$(if [ "$OS" = "linux" ]; then echo "PC1_ANTIX"; else echo "PC2_WINDOWS7"; fi)",
    "es_servidor_principal": $(if [ "$OS" = "linux" ]; then echo "true"; else echo "false"; fi),
    "tiene_impresora": $(if [ "$OS" = "linux" ]; then echo "false"; else echo "true"; fi)
  },
  "firebase": {
    "proyecto": "sistemaestacionamiento-46735",
    "configurado": true
  }
}
EOF
    
    print_message "Archivo de configuración creado"
}

# Función para verificar instalación
verify_installation() {
    print_info "Verificando instalación..."
    
    if [ "$OS" = "linux" ]; then
        if [ -f "/var/www/html/sistemaEstacionamiento/sistema-hibrido.js" ]; then
            print_message "Sistema híbrido instalado correctamente en Linux"
        else
            print_error "Error en la instalación de Linux"
            exit 1
        fi
    else
        if [ -f "C:/xampp/htdocs/sistemaEstacionamiento/sistema-hibrido.js" ]; then
            print_message "Sistema híbrido instalado correctamente en Windows"
        else
            print_error "Error en la instalación de Windows"
            exit 1
        fi
    fi
}

# Función para mostrar instrucciones
show_instructions() {
    print_info "Instrucciones de uso:"
    echo ""
    echo "1. Configurar Firebase Console:"
    echo "   - Ve a https://console.firebase.google.com/"
    echo "   - Configura Authentication, Firestore y Storage"
    echo ""
    echo "2. Probar el sistema:"
    if [ "$OS" = "linux" ]; then
        echo "   - Abre: http://localhost/sistemaEstacionamiento/test-sistema-completo.html"
    else
        echo "   - Abre: http://localhost:8080/sistemaEstacionamiento/test-sistema-completo.html"
    fi
    echo ""
    echo "3. Tiempos de sincronización:"
    echo "   - Sincronización automática: cada 5 segundos"
    echo "   - Tiempo de respuesta: 1-3 segundos"
    echo "   - Modo offline: sincroniza cuando vuelve internet"
    echo ""
    echo "4. Monitoreo:"
    echo "   - Logs en consola del navegador"
    echo "   - Estado en tiempo real en la interfaz de prueba"
    echo ""
}

# Ejecutar instalación
main() {
    echo "🚀 Iniciando instalación del Sistema Híbrido..."
    echo ""
    
    if [ "$OS" = "linux" ]; then
        install_linux
    elif [ "$OS" = "windows" ]; then
        install_windows
    else
        print_error "Sistema operativo no soportado"
        exit 1
    fi
    
    create_config
    verify_installation
    show_instructions
    
    print_message "¡Instalación completada exitosamente!"
}

# Ejecutar función principal
main "$@"
