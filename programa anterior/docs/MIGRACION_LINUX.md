# 🐧 GUÍA DE MIGRACIÓN A LINUX
## Sistema de Estacionamiento Los Ríos

---

## 📋 CHECKLIST COMPLETO

- [ ] Exportar base de datos de Windows
- [ ] Preparar servidor Linux
- [ ] Instalar LAMP Stack (Linux + Apache + MySQL + PHP)
- [ ] Configurar usuario de base de datos
- [ ] Subir archivos PHP
- [ ] Configurar permisos
- [ ] Actualizar archivo de conexión
- [ ] Probar sistema
- [ ] Configurar backup automático

---

## 1️⃣ EXPORTAR BASE DE DATOS (En Windows)

### **Paso 1: Exportar desde phpMyAdmin**

1. Abre: `http://localhost:8080/phpmyadmin`
2. Selecciona la base de datos `estacionamiento`
3. Haz clic en **"Exportar"**
4. Selecciona:
   - Método: **Rápido**
   - Formato: **SQL**
5. Haz clic en **"Continuar"**
6. Se descarga: `estacionamiento.sql`

### **Paso 2: Backup de archivos**

Copia toda la carpeta:
```
C:\xampp\htdocs\sistemaEstacionamiento\
```

A un USB o súbela a Google Drive/Dropbox

---

## 2️⃣ PREPARAR SERVIDOR LINUX

### **Si usas Ubuntu/Debian:**

```bash
# Actualizar sistema
sudo apt update
sudo apt upgrade -y

# Instalar LAMP Stack
sudo apt install apache2 -y
sudo apt install mysql-server -y
sudo apt install php libapache2-mod-php php-mysql -y

# Instalar extensiones PHP necesarias
sudo apt install php-mbstring php-xml php-curl php-zip php-gd -y

# Verificar instalación
php -v
mysql --version
apache2 -v
```

### **Activar módulos de Apache:**

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 3️⃣ CONFIGURAR MYSQL EN LINUX

### **Paso 1: Acceder a MySQL**

```bash
sudo mysql
```

### **Paso 2: Crear base de datos**

```sql
-- Crear la base de datos
CREATE DATABASE estacionamiento 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Crear usuario (NO uses root)
CREATE USER 'estacionamiento_user'@'localhost' 
IDENTIFIED BY 'TuClaveSegura123!';

-- Dar permisos
GRANT ALL PRIVILEGES ON estacionamiento.* 
TO 'estacionamiento_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Salir
exit;
```

### **Paso 3: Importar base de datos**

```bash
# Importar el archivo .sql
mysql -u estacionamiento_user -p estacionamiento < /ruta/a/estacionamiento.sql

# Ejemplo:
mysql -u estacionamiento_user -p estacionamiento < /home/usuario/estacionamiento.sql
```

Te pedirá la contraseña que creaste.

---

## 4️⃣ SUBIR ARCHIVOS AL SERVIDOR

### **Ubicación típica en Linux:**

```
/var/www/html/sistemaEstacionamiento/
```

### **Opciones para subir archivos:**

#### **Opción A: FTP/SFTP**
Usa FileZilla o WinSCP desde Windows

#### **Opción B: Git**
```bash
# En el servidor Linux
cd /var/www/html
git clone tu-repositorio-url sistemaEstacionamiento
```

#### **Opción C: Copiar manualmente**
```bash
# Si tienes acceso físico al servidor
cp -r /ruta/origen/* /var/www/html/sistemaEstacionamiento/
```

---

## 5️⃣ CONFIGURAR PERMISOS EN LINUX

**MUY IMPORTANTE** ⚠️

```bash
# Ir a la carpeta del proyecto
cd /var/www/html/sistemaEstacionamiento

# Establecer propietario correcto
sudo chown -R www-data:www-data .

# Permisos para archivos
sudo find . -type f -exec chmod 644 {} \;

# Permisos para carpetas
sudo find . -type d -exec chmod 755 {} \;

# Permisos especiales para carpetas de escritura
sudo chmod -R 775 logs/
sudo chmod -R 775 ImpresionTermica/

# Si tienes carpeta de uploads
# sudo chmod -R 775 uploads/
```

---

## 6️⃣ ACTUALIZAR ARCHIVO DE CONEXIÓN

### **Tu archivo actual en Windows:**
`conexion.php`

### **Para Linux, actualiza estos valores:**

```php
<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

date_default_timezone_set('America/Santiago');

// ============================================
// CONFIGURACIÓN PARA LINUX
// ============================================

$host = 'localhost';  // O '127.0.0.1'
$user = 'estacionamiento_user';  // ← Usuario que creaste
$pass = 'TuClaveSegura123!';  // ← Contraseña que pusiste
$dbname = "estacionamiento";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error);
    die(json_encode([
        'success' => false, 
        'error' => 'Error de conexión a la base de datos'
    ]));
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '-03:00'");

// Compatibilidad con código que usa $conexion
$conexion = $conn;
?>
```

---

## 7️⃣ CONFIGURAR APACHE (Virtual Host)

### **Crear archivo de configuración:**

```bash
sudo nano /etc/apache2/sites-available/estacionamiento.conf
```

### **Contenido del archivo:**

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    ServerAlias www.tu-dominio.com
    
    DocumentRoot /var/www/html/sistemaEstacionamiento
    
    <Directory /var/www/html/sistemaEstacionamiento>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/estacionamiento_error.log
    CustomLog ${APACHE_LOG_DIR}/estacionamiento_access.log combined
</VirtualHost>
```

### **Activar el sitio:**

```bash
# Activar sitio
sudo a2ensite estacionamiento.conf

# Desactivar sitio por defecto (opcional)
sudo a2dissite 000-default.conf

# Reiniciar Apache
sudo systemctl restart apache2
```

---

## 8️⃣ DIFERENCIAS IMPORTANTES LINUX VS WINDOWS

### **1. Rutas de archivos:**

❌ Windows:
```php
require_once 'C:\xampp\htdocs\conexion.php';
```

✅ Linux:
```php
require_once '/var/www/html/sistemaEstacionamiento/conexion.php';
// O mejor: usar rutas relativas
require_once __DIR__ . '/conexion.php';
```

### **2. Case Sensitive (Mayúsculas/Minúsculas):**

❌ En Windows esto funciona:
```php
require_once 'Conexion.php';  // Funciona
require_once 'conexion.php';  // También funciona
```

✅ En Linux NO:
```php
require_once 'Conexion.php';  // ❌ Error si el archivo se llama conexion.php
require_once 'conexion.php';  // ✅ Correcto
```

### **3. Permisos:**

Windows no tiene permisos tan estrictos como Linux.

En Linux DEBES configurar:
```bash
# Archivos: 644 (lectura para todos, escritura solo propietario)
# Carpetas: 755 (ejecutable para entrar)
# Logs/uploads: 775 (escritura para grupo www-data)
```

### **4. Nombres de archivos:**

❌ Windows acepta:
- Espacios: `Mi Archivo.php`
- Caracteres especiales: `Archivo#1.php`

✅ Linux mejor práctica:
- Sin espacios: `mi_archivo.php`
- Solo guiones bajos y medios: `mi-archivo.php`

---

## 9️⃣ PROBAR EL SISTEMA

### **Checklist de pruebas:**

```bash
# 1. Verificar que Apache está corriendo
sudo systemctl status apache2

# 2. Verificar que MySQL está corriendo
sudo systemctl status mysql

# 3. Verificar PHP
php -v

# 4. Verificar extensión GD (para logos)
php -m | grep gd

# 5. Ver logs de errores de Apache
sudo tail -f /var/log/apache2/error.log
```

### **Prueba desde el navegador:**

1. Abre: `http://tu-servidor-ip/`
2. O: `http://tu-dominio.com/`
3. Prueba login
4. Prueba registrar un ingreso
5. Prueba calcular un cobro
6. Verifica reportes

---

## 🔟 CONFIGURAR SSL (HTTPS) - OPCIONAL PERO RECOMENDADO

```bash
# Instalar Certbot (Let's Encrypt gratuito)
sudo apt install certbot python3-certbot-apache -y

# Obtener certificado SSL
sudo certbot --apache -d tu-dominio.com -d www.tu-dominio.com

# Renovación automática (ya viene configurada)
sudo certbot renew --dry-run
```

---

## 1️⃣1️⃣ BACKUP AUTOMÁTICO EN LINUX

### **Crear script de backup:**

```bash
sudo nano /usr/local/bin/backup_estacionamiento.sh
```

### **Contenido del script:**

```bash
#!/bin/bash

# Configuración
DB_NAME="estacionamiento"
DB_USER="estacionamiento_user"
DB_PASS="TuClaveSegura123!"
BACKUP_DIR="/home/backups/estacionamiento"
DATE=$(date +"%Y%m%d_%H%M%S")

# Crear carpeta si no existe
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/sistemaEstacionamiento

# Eliminar backups antiguos (más de 7 días)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completado: $DATE"
```

### **Dar permisos de ejecución:**

```bash
sudo chmod +x /usr/local/bin/backup_estacionamiento.sh
```

### **Automatizar con cron (todos los días a las 2 AM):**

```bash
sudo crontab -e
```

Agregar esta línea:
```
0 2 * * * /usr/local/bin/backup_estacionamiento.sh >> /var/log/backup_estacionamiento.log 2>&1
```

---

## 🆘 TROUBLESHOOTING (Problemas Comunes)

### **Error: "No se puede conectar a la base de datos"**

```bash
# Verificar que MySQL esté corriendo
sudo systemctl status mysql

# Verificar credenciales
mysql -u estacionamiento_user -p

# Ver logs de MySQL
sudo tail -f /var/log/mysql/error.log
```

### **Error: "Permission denied"**

```bash
# Verificar permisos
ls -la /var/www/html/sistemaEstacionamiento/

# Corregir propietario
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento/
```

### **Error: "File not found"**

Verifica mayúsculas/minúsculas en nombres de archivos.

Linux distingue entre:
- `Conexion.php` ≠ `conexion.php`

---

## 📝 ARCHIVO DE CONEXIÓN - VERSIÓN FINAL LINUX

**Crea o actualiza: `conexion.php`**

```php
<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

date_default_timezone_set('America/Santiago');

// Configuración para Linux
$host = 'localhost';
$user = 'estacionamiento_user';
$pass = 'TuClaveSegura123!';  // ⚠️ CAMBIA ESTO
$dbname = "estacionamiento";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("Error de conexión DB: " . $conn->connect_error);
    die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']));
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '-03:00'");

// Compatibilidad
$conexion = $conn;
?>
```

---

## ✅ CHECKLIST FINAL

- [ ] Base de datos exportada e importada
- [ ] Archivos subidos al servidor
- [ ] Permisos configurados (755 carpetas, 644 archivos)
- [ ] conexion.php actualizado con credenciales Linux
- [ ] Apache configurado y corriendo
- [ ] MySQL configurado y corriendo
- [ ] Pruebas realizadas (login, registro, cobro)
- [ ] SSL/HTTPS configurado (opcional)
- [ ] Backup automático configurado
- [ ] Logs funcionando

---

## 🎯 RESUMEN

**Tu archivo de conexión es:** `conexion.php`

**Para Linux, cámbialo a:**
```php
$host = 'localhost';
$user = 'estacionamiento_user';  // ← No uses root
$pass = 'tu_clave_segura';       // ← Clave fuerte
$dbname = 'estacionamiento';
```

**Y asegúrate de:**
1. Crear el usuario en MySQL
2. Darle permisos solo a esa base de datos
3. Configurar permisos de archivos correctamente
4. Probar todo antes de poner en producción

---

¿Necesitas ayuda con algún paso específico? 🐧

