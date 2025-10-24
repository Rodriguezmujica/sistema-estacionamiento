# 🚀 Guía de Instalación - Sistema de Estacionamiento Los Ríos

**Versión:** 1.0  
**Fecha:** 24 de octubre, 2025  
**Desarrollador:** Luis Miguel Rodriguez  

---

## 📋 Requisitos del Sistema

### **Servidor Principal (Windows 7/10/11):**
- Windows 7 SP1 o superior
- 4GB RAM mínimo (8GB recomendado)
- 10GB espacio libre en disco
- Conexión a internet para VPN Tailscale

### **Cliente (Antix Linux):**
- Antix Linux 19 o superior
- 2GB RAM mínimo
- 5GB espacio libre en disco
- Conexión de red al servidor

### **Software Requerido:**
- XAMPP 7.4.32 o superior
- MySQL 5.7 o superior
- PHP 7.4 o superior
- Navegador web moderno (Chrome, Firefox, Edge)

---

## 🖥️ Instalación en Servidor Windows

### **Paso 1: Instalar XAMPP**

1. **Descargar XAMPP:**
   - Ir a: https://www.apachefriends.org/download.html
   - Descargar XAMPP para Windows (versión 7.4.32 o superior)

2. **Instalar XAMPP:**
   - Ejecutar el instalador como administrador
   - Seleccionar componentes: **Apache**, **MySQL**, **PHP**, **phpMyAdmin**
   - Instalar en: `C:\xampp\`
   - ✅ **Importante:** No instalar en `Program Files`

3. **Iniciar servicios:**
   - Abrir XAMPP Control Panel
   - Iniciar **Apache** y **MySQL**
   - Verificar que ambos estén en estado "Running" (verde)

### **Paso 2: Configurar Base de Datos**

1. **Abrir phpMyAdmin:**
   - Ir a: http://localhost/phpmyadmin
   - Usuario: `root`
   - Contraseña: (dejar vacío)

2. **Crear base de datos:**
   - Clic en "Nuevo" o "New"
   - Nombre: `estacionamiento`
   - Cotejamiento: `utf8mb4_unicode_ci`
   - Clic en "Crear"

3. **Importar estructura:**
   - Seleccionar base de datos `estacionamiento`
   - Ir a pestaña "Importar" o "Import"
   - Seleccionar archivo: `database/estacionamiento.sql`
   - Clic en "Continuar" o "Go"

### **Paso 3: Instalar Sistema**

1. **Copiar archivos:**
   - Copiar toda la carpeta `sistemaEstacionamiento` a: `C:\xampp\htdocs\`
   - Ruta final: `C:\xampp\htdocs\sistemaEstacionamiento\`

2. **Configurar permisos:**
   - Clic derecho en carpeta → Propiedades → Seguridad
   - Dar permisos completos a "Usuarios" y "IIS_IUSRS"

3. **Configurar conexión:**
   - Editar archivo: `config/conexion.php`
   - Verificar configuración de base de datos:
   ```php
   $host = 'localhost';
   $dbname = 'estacionamiento';
   $username = 'root';
   $password = '';
   ```

### **Paso 4: Configurar Impresora Térmica**

1. **Instalar drivers:**
   - Instalar drivers de impresora térmica Star BSC10
   - Configurar como impresora predeterminada
   - Nombre de impresora: `POSESTACIONAMIENTO`

2. **Probar impresión:**
   - Ir a: http://localhost/sistemaEstacionamiento/
   - Probar impresión de ticket de prueba

---

## 🐧 Instalación en Cliente Antix Linux

### **Paso 1: Instalar Apache y PHP**

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache y PHP
sudo apt install apache2 php php-mysql php-curl php-json -y

# Iniciar servicios
sudo systemctl start apache2
sudo systemctl enable apache2
```

### **Paso 2: Configurar Cliente**

1. **Copiar archivos:**
   ```bash
   # Copiar sistema a directorio web
   sudo cp -r sistemaEstacionamiento /var/www/html/
   
   # Dar permisos
   sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
   sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
   ```

2. **Configurar conexión a servidor:**
   - Editar archivo: `config/conexion.php`
   - Cambiar configuración para conectar al servidor:
   ```php
   $host = '192.168.3.101';  // IP del servidor Windows
   $dbname = 'estacionamiento';
   $username = 'antix';
   $password = '733';
   $port = 3306;
   ```

### **Paso 3: Configurar Acceso Remoto**

1. **Instalar Tailscale:**
   ```bash
   # Descargar e instalar Tailscale
   curl -fsSL https://tailscale.com/install.sh | sh
   
   # Iniciar Tailscale
   sudo tailscale up
   ```

2. **Configurar acceso:**
   - Seguir instrucciones de Tailscale
   - Conectar ambos dispositivos a la misma red VPN

---

## 🔧 Configuración Final

### **Paso 1: Configurar TUU (Terminal de Pagos)**

1. **Configurar terminal:**
   - Conectar terminal TUU al servidor
   - Configurar IP y puerto en terminal
   - Probar conexión desde sistema

2. **Configurar en sistema:**
   - Ir a: Administración → Configuración TUU
   - Ingresar datos del terminal
   - Probar transacción de prueba

### **Paso 2: Configurar Precios**

1. **Acceder a administración:**
   - Ir a: http://localhost/sistemaEstacionamiento/secciones/admin.php
   - Usuario: `admin`
   - Contraseña: `admin123`

2. **Configurar precios:**
   - Ir a: Configuración → Precios
   - Establecer precio por minuto
   - Configurar precios de servicios de lavado

### **Paso 3: Configurar Respaldo Automático**

1. **Configurar tarea programada (Windows):**
   - Abrir "Programador de tareas"
   - Crear tarea básica
   - Programar para ejecutar semanalmente
   - Acción: `php C:\xampp\htdocs\sistemaEstacionamiento\maintenance\respaldo_automatico_semanal.php`

---

## 🧪 Pruebas del Sistema

### **Pruebas Básicas:**

1. **Acceso al sistema:**
   - Servidor: http://localhost/sistemaEstacionamiento/
   - Cliente: http://[IP_CLIENTE]/sistemaEstacionamiento/

2. **Funcionalidades principales:**
   - ✅ Registro de ingreso de vehículo
   - ✅ Cálculo automático de tarifa
   - ✅ Proceso de cobro (efectivo/TUU)
   - ✅ Impresión de tickets
   - ✅ Servicios de lavado
   - ✅ Reportes y dashboard

3. **Pruebas de red:**
   - ✅ Conexión entre servidor y cliente
   - ✅ Sincronización de datos
   - ✅ Acceso remoto via Tailscale

---

## 🔒 Configuración de Seguridad

### **Recomendaciones:**

1. **Cambiar contraseñas por defecto:**
   - Base de datos MySQL
   - Usuario administrador del sistema
   - Acceso a Tailscale

2. **Configurar firewall:**
   - Abrir puerto 3306 solo para cliente
   - Configurar reglas de acceso

3. **Respaldo regular:**
   - Verificar respaldos automáticos
   - Probar restauración de datos

---

## 📞 Soporte Técnico

### **Información de Contacto:**
- **Desarrollador:** Luis Miguel Rodriguez
- **Soporte:** 3 meses incluido
- **Corrección de bugs:** Sin costo adicional

### **Archivos de Log:**
- **Apache:** `C:\xampp\apache\logs\`
- **MySQL:** `C:\xampp\mysql\data\`
- **Sistema:** `logs/` (dentro del proyecto)

### **Problemas Comunes:**

1. **Error de conexión a base de datos:**
   - Verificar que MySQL esté ejecutándose
   - Revisar configuración en `config/conexion.php`

2. **Error de impresión:**
   - Verificar que la impresora esté conectada
   - Revisar drivers de impresora

3. **Error de acceso remoto:**
   - Verificar conexión Tailscale
   - Revisar configuración de red

---

## ✅ Lista de Verificación Final

- [ ] XAMPP instalado y funcionando
- [ ] Base de datos creada e importada
- [ ] Sistema copiado en htdocs
- [ ] Impresora configurada y funcionando
- [ ] Cliente Antix configurado
- [ ] Conexión de red establecida
- [ ] Tailscale configurado
- [ ] TUU configurado y probado
- [ ] Precios configurados
- [ ] Respaldo automático programado
- [ ] Pruebas completadas
- [ ] Contraseñas cambiadas

---

**¡Sistema listo para producción!** 🎉

*Desarrollado con dedicación y profesionalismo para Estacionamiento Los Ríos*
