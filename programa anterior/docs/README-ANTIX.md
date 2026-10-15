# 🔌 CONFIGURACIÓN ANTIX → WINDOWS 7

## 📋 Instrucciones para configurar Antix

### 1. **Reemplazar archivo de conexión:**
```bash
# Hacer backup del archivo actual
cp conexion.php conexion.php.backup

# Reemplazar con la versión para Antix
cp conexion-antix.php conexion.php
```

### 2. **Verificar conectividad:**
```bash
# Ejecutar prueba de conectividad
http://tu-ip-antix/test-antix.php
```

### 3. **Configuración de red:**
- **IP Windows 7:** 192.168.3.101
- **Puerto MySQL:** 3306
- **Usuario:** antix
- **Contraseña:** 733

## 🔧 Configuración en Windows 7

### 1. **Crear usuario MySQL:**
```sql
-- En MySQL de Windows 7, ejecutar:
CREATE USER 'antix'@'%' IDENTIFIED BY '733';
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'antix'@'%';
FLUSH PRIVILEGES;
```

### 2. **Configurar firewall:**
- Abrir puerto 3306 en Windows Firewall
- Permitir conexiones desde Antix

### 3. **Configurar MySQL:**
```ini
# En my.ini de MySQL, verificar:
bind-address = 0.0.0.0
```

## 🧪 Pruebas

### 1. **Prueba de red:**
```bash
ping 192.168.3.101
```

### 2. **Prueba de MySQL:**
```bash
mysql -h 192.168.3.101 -u antix -p733 estacionamiento
```

### 3. **Prueba web:**
```
http://antix-ip/test-antix.php
```

## ✅ Verificación final

Si todas las pruebas son exitosas:
1. El sistema funcionará normalmente
2. Los datos se guardarán en Windows 7
3. Antix solo será cliente de consulta

## 🆘 Solución de problemas

### Error de conexión:
- Verificar que Windows 7 esté encendido
- Verificar que MySQL esté corriendo
- Verificar firewall y puertos

### Error de permisos:
- Verificar usuario MySQL 'antix'
- Verificar privilegios en base de datos

### Error de red:
- Verificar conectividad entre máquinas
- Verificar IPs y configuración de red
