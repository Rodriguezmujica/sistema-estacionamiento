# 🚀 Guía de Instalación - Sistema de Estacionamiento Los Ríos

**Versión:** 2.0  
**Fecha:** 24 de octubre, 2025  
**Desarrollador:** Luis Miguel Rodriguez  
**Sistema:** Multiplataforma (Linux Mint / Windows)

---

## 📋 Requisitos del Sistema

### **Servidor Linux Mint (Producción):**
- Linux Mint 21 o superior
- 4GB RAM mínimo (recomendado)
- 20GB espacio libre en disco
- Conexión a internet para VPN Tailscale

### **Servidor Windows (Alternativa/Backup):**
- Windows 10/11 o superior
- 4GB RAM mínimo
- 20GB espacio libre en disco
- Conexión a internet para VPN Tailscale

### **Software Requerido:**
- **Linux:** LAMP Stack (Apache, MySQL, PHP 7.4+)
- **Windows:** XAMPP 7.4.32 o superior
- Navegador web moderno (Chrome, Firefox, Edge)
- Impresora térmica Star TSP143 (o compatible)

---

## 🐧 Instalación en Linux Mint (Producción)

### **Paso 1: Instalar LAMP Stack**

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache
sudo apt install apache2 -y

# Instalar MySQL
sudo apt install mysql-server mysql-client -y

# Instalar PHP y extensiones necesarias
sudo apt install php php-mysql php-mbstring php-curl php-json php-xml php-zip -y

# Iniciar y habilitar servicios
sudo systemctl start apache2
sudo systemctl enable apache2
sudo systemctl start mysql
sudo systemctl enable mysql
```

### **Paso 2: Configurar MySQL**

```bash
# Seguridad de MySQL
sudo mysql_secure_installation

# Crear usuario y base de datos
sudo mysql -u root -p << EOF
CREATE DATABASE estacionamiento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'estacionamiento'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'estacionamiento'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
```

### **Paso 3: Instalar Sistema**

```bash
# Copiar sistema a directorio web
sudo cp -r sistemaEstacionamiento /var/www/html/

# Dar permisos correctos
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento

# Habilitar mod_rewrite (para URLs amigables)
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### **Paso 4: Configurar Base de Datos**

```bash
# Importar estructura de base de datos
cd /var/www/html/sistemaEstacionamiento
sudo mysql -u root -p estacionamiento < estacionamiento.sql

# O usar phpMyAdmin si lo instalaste:
# Ir a: http://localhost/phpmyadmin
```

### **Paso 5: Configurar Conexión**

```bash
# Editar archivo de configuración
sudo nano /var/www/html/sistemaEstacionamiento/config/conexion.php
```

**Configuración:**
```php
$host = 'localhost';
$dbname = 'estacionamiento';
$username = 'estacionamiento';
$password = 'tu_contraseña_segura';
$port = 3306;
```

### **Paso 6: Configurar Impresora Térmica**

```bash
# Instalar utilidades CUPS
sudo apt install cups cups-client -y

# Agregar impresora
sudo lpadmin -p POSESTACIONAMIENTO -E -v usb://Star/TSP143 -m drv:///sample.drv/generic.ppd

# Probar impresión
echo "Test" | lp -d POSESTACIONAMIENTO
```

### **Paso 7: Instalar Tailscale (VPN Remota)**

```bash
# Instalar Tailscale
curl -fsSL https://tailscale.com/install.sh | sh

# Iniciar Tailscale
sudo tailscale up

# Seguir instrucciones para autenticarse
# Copiar URL que aparece en terminal
```

---

## 🪟 Instalación en Windows 10/11

### **Paso 1: Instalar XAMPP**

1. **Descargar XAMPP:**
   - Ir a: https://www.apachefriends.org/download.html
   - Descargar XAMPP para Windows (versión 7.4.32 o superior)

2. **Instalar XAMPP:**
   - Ejecutar el instalador como administrador
   - Seleccionar componentes: **Apache**, **MySQL**, **PHP**, **phpMyAdmin**
   - Instalar en: `C:\xampp\`
   - ⚠️ **Importante:** No instalar en `Program Files`

3. **Iniciar servicios:**
   - Abrir XAMPP Control Panel
   - Iniciar **Apache** y **MySQL**
   - Verificar que ambos estén en estado "Running" (verde)

### **Paso 2: Configurar Base de Datos**

1. **Abrir phpMyAdmin:**
   - Ir a: http://localhost/phpmyadmin
   - Usuario: `root`
   - Contraseña: (dejar vacío o la que configuraste)

2. **Crear base de datos:**
   - Clic en "Nuevo" o "New"
   - Nombre: `estacionamiento`
   - Cotejamiento: `utf8mb4_unicode_ci`
   - Clic en "Crear"

3. **Importar estructura:**
   - Seleccionar base de datos `estacionamiento`
   - Ir a pestaña "Importar" o "Import"
   - Seleccionar archivo: `estacionamiento.sql`
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
   - Instalar drivers de impresora térmica Star TSP143 (o compatible)
   - Configurar como impresora predeterminada
   - Nombre de impresora: `POSESTACIONAMIENTO`

2. **Probar impresión:**
   - Ir a: http://localhost/sistemaEstacionamiento/
   - Probar impresión de ticket de prueba

### **Paso 5: Instalar Tailscale (VPN Remota)**

1. **Descargar Tailscale:**
   - Ir a: https://tailscale.com/download
   - Descargar para Windows

2. **Instalar y configurar:**
   - Ejecutar instalador
   - Iniciar Tailscale y seguir instrucciones
   - Autenticarse con cuenta de Tailscale

---

## 🔧 Configuración Final (Ambas Plataformas)

### **Paso 1: Configurar TUU (Terminal de Pagos)**

1. **Configurar terminal:**
   - Conectar terminal TUU al equipo
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
   - Contraseña: (ver archivo CREDENCIALES_SISTEMA.md)

2. **Configurar precios:**
   - Ir a: Configuración → Precios
   - Establecer precio por minuto
   - Configurar precios de servicios de lavado

### **Paso 3: Configurar Respaldo Automático**

**Linux Mint:**
```bash
# Crear script de respaldo
sudo nano /usr/local/bin/backup-estacionamiento.sh

# Agregar contenido:
#!/bin/bash
mysqldump -u root -pestacionamiento estacionamiento > /backups/backup_$(date +%Y%m%d_%H%M%S).sql

# Dar permisos de ejecución
sudo chmod +x /usr/local/bin/backup-estacionamiento.sh

# Agregar a crontab (respaldo semanal los domingos a las 2 AM)
sudo crontab -e
# Agregar línea:
0 2 * * 0 /usr/local/bin/backup-estacionamiento.sh
```

**Windows:**
- Abrir "Programador de tareas"
- Crear tarea básica
- Programar para ejecutar semanalmente
- Acción: `php C:\xampp\htdocs\sistemaEstacionamiento\maintenance\respaldo_automatico_semanal.php`

---

## 🧪 Pruebas del Sistema

### **Pruebas Básicas:**

1. **Acceso al sistema:**
   - Abrir navegador: http://localhost/sistemaEstacionamiento/
   - Verificar que carga sin errores

2. **Funcionalidades principales:**
   - ✅ Registro de ingreso de vehículo
   - ✅ Cálculo automático de tarifa
   - ✅ Proceso de cobro (efectivo/TUU)
   - ✅ Impresión de tickets
   - ✅ Servicios de lavado
   - ✅ Reportes y dashboard

3. **Acceso remoto (Tailscale):**
   - Conectar desde otro dispositivo
   - Acceder a: http://[IP_TAILSCALE]/sistemaEstacionamiento/
   - Verificar que funciona correctamente

---

## 🔒 Configuración de Seguridad

### **Recomendaciones:**

1. **Cambiar contraseñas por defecto:**
   - Base de datos MySQL
   - Usuario administrador del sistema (ver CREDENCIALES_SISTEMA.md)
   - Acceso a Tailscale

2. **Configurar firewall:**
   - **Linux:** `sudo ufw allow 80/tcp && sudo ufw allow 443/tcp`
   - **Windows:** Configurar Firewall de Windows

3. **Respaldo regular:**
   - Verificar respaldos automáticos
   - Probar restauración de datos
   - Guardar copias en ubicación segura

---

## 📞 Soporte Técnico

### **Información de Contacto:**
- **Desarrollador:** Luis Miguel Rodriguez
- **Soporte:** 3 meses incluido
- **Corrección de bugs:** Sin costo adicional

### **Archivos de Log:**
- **Linux Apache:** `/var/log/apache2/error.log`
- **Windows Apache:** `C:\xampp\apache\logs\`
- **MySQL:** `C:\xampp\mysql\data\` (Windows) o `/var/log/mysql/` (Linux)
- **Sistema:** `logs/` (dentro del proyecto)

### **Problemas Comunes:**

1. **Error de conexión a base de datos:**
   - Verificar que MySQL esté ejecutándose
   - Revisar configuración en `config/conexion.php`
   - Verificar usuarios y permisos

2. **Error de impresión:**
   - Verificar que la impresora esté conectada
   - Revisar drivers de impresora
   - Verificar nombre de impresora en configuración

3. **Error de acceso remoto:**
   - Verificar conexión Tailscale
   - Revisar configuración de red
   - Verificar firewall

4. **Error de permisos (Linux):**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
   sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
   ```

---

## ✅ Lista de Verificación Final

### **Instalación Básica:**
- [ ] LAMP/XAMPP instalado y funcionando
- [ ] Base de datos creada e importada
- [ ] Sistema copiado en directorio correcto
- [ ] Configuración de conexión verificada
- [ ] Permisos configurados correctamente

### **Hardware:**
- [ ] Impresora configurada y funcionando
- [ ] Terminal TUU configurado (si aplica)

### **Configuración:**
- [ ] Tailscale instalado y configurado
- [ ] Precios configurados en sistema
- [ ] Respaldo automático programado
- [ ] Contraseñas cambiadas

### **Pruebas:**
- [ ] Pruebas completadas exitosamente
- [ ] Acceso remoto funcionando
- [ ] Impresión funcionando
- [ ] Reportes generándose correctamente

---

## 🔄 Migración Entre Plataformas

Si necesitas migrar el sistema de una plataforma a otra:

1. **Exportar base de datos:**
   ```bash
   mysqldump -u usuario -p estacionamiento > backup.sql
   ```

2. **Copiar archivos del sistema** a la nueva ubicación

3. **Importar base de datos** en el nuevo servidor

4. **Actualizar configuración** si es necesario

5. **Probar todas las funcionalidades**

---

**¡Sistema listo para producción!** 🎉

*Desarrollado con dedicación y profesionalismo para Estacionamiento Los Ríos*
