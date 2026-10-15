# 🐧 Guía de Migración a Antix Linux

## ✅ Cambios Realizados

Se han **unificado TODOS los archivos** del sistema para usar un archivo de conexión centralizado con rutas absolutas que funcionan correctamente en Linux.

### 📊 Resumen de Archios Actualizados

- ✅ **28 archivos API** actualizados para usar `require_once __DIR__ . '/../conexion.php'`
- ✅ **1 archivo login.php** corregido (tenía un error de sintaxis)
- ✅ **conexion.php** optimizado para Linux y Windows

### 🔄 Qué Se Cambió

**ANTES (problemático en Linux):**
```php
// ❌ Esto causaba errores de rutas en Linux
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
```

**DESPUÉS (funciona en Windows Y Linux):**
```php
// ✅ Ahora todos los archivos usan conexión centralizada
require_once __DIR__ . '/../conexion.php';
```

---

## 🚀 Configuración en Antix Linux

### Paso 1: Instalar LAMP Stack

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache, MySQL y PHP
sudo apt install apache2 mariadb-server php php-mysql php-mysqli php-json php-curl -y

# Iniciar servicios
sudo systemctl start apache2
sudo systemctl start mariadb
sudo systemctl enable apache2
sudo systemctl enable mariadb
```

### Paso 2: Configurar MySQL

```bash
# Asegurar instalación de MySQL
sudo mysql_secure_installation
```

**Luego, crear usuario y base de datos:**

```bash
sudo mysql -u root -p
```

```sql
-- Crear base de datos
CREATE DATABASE estacionamiento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario específico (NO uses root en producción)
CREATE USER 'estacionamiento_user'@'localhost' IDENTIFIED BY 'tu_clave_segura_aqui';

-- Dar permisos
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'estacionamiento_user'@'localhost';
FLUSH PRIVILEGES;

-- Verificar
SHOW GRANTS FOR 'estacionamiento_user'@'localhost';

EXIT;
```

### Paso 3: Configurar las credenciales

**IMPORTANTE:** Ahora el sistema usa un archivo de configuración separado para las credenciales.

#### Opción A: Crear config.php (Recomendado)

```bash
# Copiar el archivo de ejemplo
cp config.php.example config.php

# Editar con nano (o tu editor preferido)
nano config.php
```

Dentro de `config.php`, cambia estas líneas:

```php
define('DB_USER', 'estacionamiento_user');  // Tu usuario MySQL
define('DB_PASS', 'TU_CONTRASEÑA_SEGURA');  // Tu contraseña
```

#### Opción B: Editar conexion.php directamente

Si no quieres usar `config.php`, edita `conexion.php` directamente:

```php
// Busca la sección "Para Linux (PRODUCCIÓN)" y cambia:
$pass = 'CAMBIAR_ESTA_CONTRASEÑA';  // ← Pon tu contraseña aquí
```

**✅ VENTAJA de usar config.php:**
- No se sube a Git (está en .gitignore)
- Más seguro para trabajo en equipo
- Fácil cambiar entre entornos

### Paso 4: Configurar Permisos en Linux

```bash
# Ir al directorio del proyecto
cd /var/www/html/sistemaEstacionamiento

# Dar permisos al servidor web
sudo chown -R www-data:www-data .
sudo chmod -R 755 .

# Permisos especiales para carpeta de logs
sudo chmod -R 777 logs/
```

### Paso 5: Importar Base de Datos

```bash
# Importar el archivo SQL
mysql -u estacionamiento_user -p estacionamiento < estacionamiento.sql
```

---

## 🔍 Verificación de Funcionamiento

### Verificar Conexión

Abre en el navegador:
```
http://localhost/sistemaEstacionamiento/diagnostico_conexion.php
```

### Verificar APIs

```bash
# Probar API de clientes
curl http://localhost/sistemaEstacionamiento/api/api_clientes.php

# Probar API de reporte
curl -X POST -d "patente=AB1234" http://localhost/sistemaEstacionamiento/api/api_reporte.php
```

---

## ⚠️ Diferencias Linux vs Windows

### 1. **Rutas de Archivos**
- **Windows:** `C:\xampp\htdocs\...`
- **Linux:** `/var/www/html/...`

✅ **SOLUCIONADO:** Ahora todos los archivos usan `__DIR__` que funciona en ambos sistemas.

### 2. **Case Sensitive (Mayúsculas/Minúsculas)**
⚠️ **IMPORTANTE:** Linux distingue entre mayúsculas y minúsculas:

```bash
# Estos son archivos DIFERENTES en Linux:
Conexion.php ≠ conexion.php
API/ ≠ api/
```

**Recomendación:** Usa siempre minúsculas para nombres de archivos y carpetas.

### 3. **Usuario MySQL**
- **Windows XAMPP:** Por defecto usa `root` sin contraseña
- **Linux:** **NUNCA uses root**. Crea un usuario específico.

✅ **SOLUCIONADO:** El archivo `conexion.php` tiene instrucciones claras.

### 4. **Permisos de Archivos**
Linux requiere configurar permisos:
- **Archivos PHP:** `644` (rw-r--r--)
- **Carpetas:** `755` (rwxr-xr-x)
- **Logs:** `777` (rwxrwxrwx)

```bash
# Aplicar permisos correctos
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod -R 777 logs/
```

---

## 🛠️ Solución de Problemas Comunes

### Error: "No such file or directory"
```
Warning: require_once(../conexion.php): failed to open stream
```

**Causa:** Rutas relativas incorrectas.

✅ **YA CORREGIDO:** Todos los archivos ahora usan `__DIR__` que siempre apunta al directorio correcto.

### Error: "Access denied for user 'root'@'localhost'"

**Causa:** Intentas usar el usuario root sin contraseña en Linux.

**Solución:** Edita `conexion.php` y usa el usuario creado en el Paso 2.

### Error: "Connection refused"

**Causa:** MySQL no está corriendo.

**Solución:**
```bash
sudo systemctl start mariadb
sudo systemctl status mariadb
```

### Error: "Table doesn't exist"

**Causa:** La base de datos no fue importada correctamente.

**Solución:**
```bash
mysql -u estacionamiento_user -p estacionamiento < estacionamiento.sql
```

---

## 📝 Checklist de Migración

- [ ] Instalar LAMP stack (Apache, MySQL, PHP)
- [ ] Crear base de datos `estacionamiento`
- [ ] Crear usuario `estacionamiento_user` con contraseña
- [ ] Importar archivo SQL
- [ ] Editar `conexion.php` con credenciales correctas
- [ ] Configurar permisos de archivos (`chown` y `chmod`)
- [ ] Probar `diagnostico_conexion.php` en navegador
- [ ] Probar APIs con `curl` o navegador
- [ ] Verificar que el login funcione
- [ ] Probar registro de ingresos y salidas

---

## 🎉 ¡Listo!

Ahora tu sistema está **100% compatible** con Linux gracias a:

1. ✅ Rutas absolutas con `__DIR__`
2. ✅ Conexión centralizada en un solo archivo
3. ✅ Manejo correcto de errores
4. ✅ Compatibilidad con ambas variables (`$conn` y `$conexion`)

---

## 📞 Soporte

Si encuentras algún error, verifica:

1. **Logs de Apache:**
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

2. **Logs de PHP:**
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

3. **Logs del Sistema:**
   ```bash
   cat logs/debug.log
   ```

---

**Última actualización:** Octubre 2025
**Sistema:** Compatible con Antix Linux, Ubuntu, Debian, y Windows

