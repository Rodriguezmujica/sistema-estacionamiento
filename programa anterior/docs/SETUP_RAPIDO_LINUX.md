# 🚀 Setup Rápido para Antix Linux

## ⚡ Comandos Rápidos (Copy-Paste)

### 1. Crear Usuario MySQL

```bash
sudo mysql -u root -p
```

Dentro de MySQL:

```sql
CREATE USER 'estacionamiento_user'@'localhost' IDENTIFIED BY 'MiContraseña2024!';
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'estacionamiento_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Configurar Credenciales

```bash
cd /var/www/html/sistemaEstacionamiento
cp config.php.example config.php
nano config.php
```

**Cambiar estas 2 líneas:**
```php
define('DB_USER', 'estacionamiento_user');
define('DB_PASS', 'MiContraseña2024!');  // ← La que creaste arriba
```

Guardar: `Ctrl+O`, Enter, `Ctrl+X`

### 3. Importar Base de Datos

```bash
mysql -u estacionamiento_user -p estacionamiento < estacionamiento.sql
# Poner la contraseña cuando la pida
```

### 4. Permisos de Archivos

```bash
cd /var/www/html/sistemaEstacionamiento
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 logs/
```

### 5. Verificar que Funciona

```bash
# En el navegador:
http://localhost/sistemaEstacionamiento/verificar_unificacion.php
```

Deberías ver: **"🎉 ¡EXCELENTE! Sistema unificado correctamente"**

---

## 🔒 Generar Contraseña Segura

```bash
openssl rand -base64 16
```

Ejemplo de output: `xK9m#Lp2$vN8qR@5`

Usa esto como contraseña en el paso 1.

---

## ⚠️ Troubleshooting Rápido

### Error: "Access denied for user 'root'@'localhost'"

✅ **Solución:** Ya creaste `config.php` con las credenciales correctas? Revisa el paso 2.

### Error: "No such file or directory"

✅ **Solución:** Estás en el directorio correcto?
```bash
pwd  # Debe mostrar: /var/www/html/sistemaEstacionamiento
```

### Error: "Permission denied"

✅ **Solución:** Ejecuta el paso 4 (permisos)

### El sistema carga pero no se conecta a la BD

✅ **Solución:** 
```bash
# Verifica que MySQL esté corriendo
sudo systemctl status mariadb

# Si no está corriendo:
sudo systemctl start mariadb
```

---

## ✅ Checklist

- [ ] MySQL instalado y corriendo
- [ ] Usuario `estacionamiento_user` creado
- [ ] Archivo `config.php` creado y editado
- [ ] Base de datos importada
- [ ] Permisos configurados
- [ ] `verificar_unificacion.php` muestra OK

---

## 🎉 ¡Listo!

Tu sistema ahora funciona en Linux con contraseñas seguras.

**Próximos pasos:**
- Probar login: `http://localhost/sistemaEstacionamiento/login.php`
- Usuario: `admin` / Contraseña: `admin123`
- Cambiar la contraseña del admin desde el panel

