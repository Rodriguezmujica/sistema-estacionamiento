# 🐧 Guía de Migración de Windows (XAMPP) a Ubuntu

## ⚠️ ARCHIVOS QUE NO SE SUBIRÁN A GITHUB (están en .gitignore)

Estos archivos **NO** se descargarán automáticamente en Ubuntu:

### 📋 Archivos Críticos a Respaldar Manualmente:

1. **`conexion.php`** - Configuración de base de datos (CRÍTICO ⚠️)
2. **`gitpush.bat`** - Script de Windows (no necesario en Ubuntu)
3. Cualquier archivo `.bak`, `.backup` - Respaldos que hayas creado

---

## 📦 PROCESO DE MIGRACIÓN PASO A PASO

### **PASO 1: Preparar en Windows (tu PC actual)**

#### 1.1 Verificar que el código esté actualizado en GitHub
```bash
# Desde PowerShell en: C:\xampp\htdocs\sistemaEstacionamiento
git status
git add .
git commit -m "Preparando migración a Ubuntu"
git push origin main
```

#### 1.2 Respaldar archivos críticos (manual)
Copia estos archivos a un USB, correo o servicio en la nube:
- ✅ `conexion.php` (contiene credenciales de BD)
- ✅ Archivo SQL de la base de datos (exportar desde phpMyAdmin)

#### 1.3 Exportar la Base de Datos
```
1. Abre phpMyAdmin: http://localhost/phpmyadmin
2. Selecciona la base de datos "estacionamiento"
3. Clic en "Exportar"
4. Método: Rápido
5. Formato: SQL
6. Clic en "Continuar" para descargar
7. Guarda el archivo: estacionamiento_backup.sql
```

---

### **PASO 2: Preparar Ubuntu (tu nueva PC)**

#### 2.1 Instalar LAMP Stack (Linux + Apache + MySQL + PHP)
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache
sudo apt install apache2 -y

# Instalar MySQL
sudo apt install mysql-server -y

# Instalar PHP 8.1 (o versión que uses)
sudo apt install php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip -y

# Verificar versiones instaladas
apache2 -v
mysql --version
php -v
```

#### 2.2 Configurar MySQL
```bash
# Configurar seguridad de MySQL
sudo mysql_secure_installation

# Acceder a MySQL como root
sudo mysql

# Dentro de MySQL, crear usuario y base de datos:
CREATE DATABASE estacionamiento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'usuario_estacionamiento'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'usuario_estacionamiento'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 2.3 Configurar permisos del directorio web
```bash
# Ir al directorio web de Apache
cd /var/www/html

# Clonar el repositorio
sudo git clone https://github.com/TU_USUARIO/sistemaEstacionamiento.git

# Cambiar permisos (importante para que Apache pueda leer)
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
```

---

### **PASO 3: Configurar la Aplicación en Ubuntu**

#### 3.1 Crear archivo de conexión a la base de datos
```bash
# Ir al directorio del proyecto
cd /var/www/html/sistemaEstacionamiento

# Copiar el archivo de ejemplo
sudo cp conexion.php.example conexion.php

# Editar con nano (o vim si prefieres)
sudo nano conexion.php
```

Contenido de `conexion.php` (ajustar según tu configuración):
```php
<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Configuración de zona horaria
date_default_timezone_set('America/Santiago');

$host = 'localhost';
$user = 'usuario_estacionamiento';  // ⬅️ Usuario que creaste en MySQL
$pass = 'tu_contraseña_segura';     // ⬅️ Tu contraseña
$dbname = "estacionamiento";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conn->connect_error]));
}

// Configurar zona horaria de MySQL
$conn->query("SET time_zone = '-03:00'");
?>
```

Guardar: `Ctrl + O`, `Enter`, `Ctrl + X`

#### 3.2 Importar la base de datos
```bash
# Copiar el archivo SQL a la carpeta temporal
# (Primero pásalo por USB o descárgalo)

# Importar la base de datos
mysql -u usuario_estacionamiento -p estacionamiento < estacionamiento_backup.sql
# Te pedirá la contraseña
```

#### 3.3 Configurar Apache para el sitio
```bash
# Crear archivo de configuración del sitio
sudo nano /etc/apache2/sites-available/estacionamiento.conf
```

Contenido:
```apache
<VirtualHost *:80>
    ServerAdmin admin@localhost
    DocumentRoot /var/www/html/sistemaEstacionamiento
    ServerName localhost

    <Directory /var/www/html/sistemaEstacionamiento>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/estacionamiento_error.log
    CustomLog ${APACHE_LOG_DIR}/estacionamiento_access.log combined
</VirtualHost>
```

```bash
# Habilitar el sitio y módulos necesarios
sudo a2ensite estacionamiento.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 3.4 Configurar PHP (si es necesario)
```bash
# Ver configuración actual de PHP
php -i | grep "max_execution_time"
php -i | grep "upload_max_filesize"

# Si necesitas cambiar valores:
sudo nano /etc/php/8.1/apache2/php.ini

# Buscar y modificar:
# max_execution_time = 300
# upload_max_filesize = 50M
# post_max_size = 50M

# Reiniciar Apache
sudo systemctl restart apache2
```

---

### **PASO 4: Verificación Final**

#### 4.1 Probar la aplicación
```bash
# Abrir navegador en Ubuntu
http://localhost
```

#### 4.2 Verificar logs si hay errores
```bash
# Ver logs de Apache
sudo tail -f /var/log/apache2/estacionamiento_error.log

# Ver logs de PHP
sudo tail -f /var/log/apache2/error.log
```

#### 4.3 Verificar permisos de escritura (para impresión, uploads, etc.)
```bash
# Si hay carpetas que necesitan escritura:
sudo chmod -R 775 /var/www/html/sistemaEstacionamiento/ImpresionTermica
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento/ImpresionTermica
```

---

## 🔧 DIFERENCIAS IMPORTANTES: Windows vs Ubuntu

| Aspecto | Windows (XAMPP) | Ubuntu (LAMP) |
|---------|----------------|---------------|
| **Directorio web** | `C:\xampp\htdocs\` | `/var/www/html/` |
| **Usuario Apache** | `Sistema` | `www-data` |
| **MySQL root password** | Vacío por defecto | Debes configurarlo |
| **Editor de archivos** | Notepad++, VS Code | `nano`, `vim`, VS Code |
| **Permisos** | No tan estrictos | **MUY IMPORTANTES** |
| **Impresoras** | Windows Print Connector | CUPS (necesita configuración diferente) |

---

## ⚠️ NOTA IMPORTANTE SOBRE IMPRESIÓN TÉRMICA

La librería que usas (`Mike42\Escpos`) en Windows usa `WindowsPrintConnector`.

En Ubuntu deberás cambiar a:
- **`CupsPrintConnector`** (para impresoras compartidas por CUPS)
- **`NetworkPrintConnector`** (si la impresora tiene IP)
- **`FilePrintConnector`** (para imprimir en archivo y luego enviar)

Archivo a modificar: `ImpresionTermica/ticketsalida.php` (líneas 20-36)

---

## 📝 CHECKLIST DE MIGRACIÓN

- [ ] Código actualizado en GitHub
- [ ] Base de datos exportada (.sql)
- [ ] Archivo `conexion.php` respaldado
- [ ] LAMP instalado en Ubuntu
- [ ] MySQL configurado con usuario y BD
- [ ] Repositorio clonado
- [ ] Permisos configurados (www-data)
- [ ] Archivo `conexion.php` creado con credenciales correctas
- [ ] Base de datos importada
- [ ] Apache configurado y reiniciado
- [ ] Aplicación accesible desde navegador
- [ ] Login funcional
- [ ] Módulos de cobro probados
- [ ] Impresión configurada (si aplica)

---

## 🆘 PROBLEMAS COMUNES

### Error: "Access denied for user"
```bash
# Verificar usuario en MySQL
sudo mysql -u root -p
SELECT user, host FROM mysql.user;
```

### Error: "Permission denied"
```bash
# Arreglar permisos
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
```

### No se ve la página (404)
```bash
# Verificar que Apache esté corriendo
sudo systemctl status apache2

# Verificar configuración del sitio
sudo apache2ctl -t
```

---

## 🎯 RECOMENDACIÓN FINAL

1. **Haz primero una prueba en una máquina virtual con Ubuntu** antes de migrar en producción
2. **Mantén tu XAMPP funcionando** hasta que Ubuntu esté 100% operativo
3. **Documenta las contraseñas** de MySQL en un lugar seguro
4. **Haz backups regulares** de la base de datos

---

¿Dudas? Consulta la documentación oficial:
- Apache: https://httpd.apache.org/docs/
- PHP: https://www.php.net/docs.php
- MySQL: https://dev.mysql.com/doc/

