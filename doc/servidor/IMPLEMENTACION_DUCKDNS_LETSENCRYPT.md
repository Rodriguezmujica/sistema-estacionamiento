# 🚀 Implementación: DuckDNS + Let's Encrypt para Acceso Remoto

**Sistema:** Estacionamiento Los Ríos  
**Objetivo:** Acceso local (oficina) + Acceso remoto (internet)  
**Costo:** $0 (Totalmente Gratis)

---

## 📋 REQUISITOS PREVIOS

Antes de comenzar, necesitas:

- [✅] Servidor Ubuntu instalado (puede ser 18.04, 20.04, 22.04 o superior)
- [✅] Conexión a internet con IP pública
- [✅] Acceso al router (admin/contraseña)
- [✅] Correo electrónico válido (para Let's Encrypt)
- [✅] Acceso SSH al servidor Ubuntu

---

## 🎯 PARTE 1: PREPARAR UBUNTU SERVER

### **Paso 1.1: Actualizar el Sistema**

```bash
# Conectarse al servidor por SSH
ssh usuario@192.168.1.X

# Actualizar paquetes
sudo apt update
sudo apt upgrade -y
```

### **Paso 1.2: Instalar Apache y PHP**

```bash
# Instalar Apache, PHP y extensiones necesarias
sudo apt install apache2 php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml -y

# Habilitar Apache para que inicie automáticamente
sudo systemctl enable apache2
sudo systemctl start apache2

# Verificar que Apache esté corriendo
sudo systemctl status apache2
# Debe decir "active (running)"
```

### **Paso 1.3: Copiar tu Sistema al Servidor**

```bash
# Crear directorio temporal
mkdir ~/temp_sistema
cd ~/temp_sistema

# Opción A: Si usas USB
# Copiar archivos del USB a este directorio

# Opción B: Si usas SCP desde otra PC
# En tu PC Windows (desde PowerShell):
# scp -r C:\xampp\htdocs\sistemaEstacionamiento usuario@192.168.1.X:~/temp_sistema/

# Copiar al directorio web
sudo cp -r sistemaEstacionamiento /var/www/html/

# Dar permisos correctos
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento

# Dar permisos de escritura a carpetas específicas (si las hay)
sudo chmod -R 775 /var/www/html/sistemaEstacionamiento/uploads  # Si tienes carpeta uploads
```

### **Paso 1.4: Configurar Base de Datos**

```bash
# Si no tienes MySQL instalado:
sudo apt install mysql-server -y

# Acceder a MySQL
sudo mysql

# Crear base de datos y usuario
CREATE DATABASE estacionamiento;
CREATE USER 'estacionamiento_user'@'localhost' IDENTIFIED BY 'TU_CONTRASEÑA_SEGURA';
GRANT ALL PRIVILEGES ON estacionamiento.* TO 'estacionamiento_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar tu base de datos
mysql -u estacionamiento_user -p estacionamiento < ~/temp_sistema/estacionamiento.sql
```

### **Paso 1.5: Configurar Archivo de Conexión**

```bash
# Editar archivo de conexión
sudo nano /var/www/html/sistemaEstacionamiento/conexion.php
```

Actualizar con los datos correctos:
```php
<?php
$servidor = "localhost";
$usuario = "estacionamiento_user";
$password = "TU_CONTRASEÑA_SEGURA";
$basededatos = "estacionamiento";
// ... resto del archivo
?>
```

### **Paso 1.6: Probar Acceso Local**

Desde un navegador en la red local:
```
http://192.168.1.X/sistemaEstacionamiento/
```

**Si funciona, continúa al siguiente paso.** ✅

---

## 🌐 PARTE 2: CONFIGURAR DUCKDNS

### **Paso 2.1: Crear Cuenta en DuckDNS**

1. Ir a: **https://www.duckdns.org/**
2. Hacer clic en "sign in" (arriba a la derecha)
3. Elegir método (Google, GitHub, Reddit, etc.)
4. Iniciar sesión

### **Paso 2.2: Crear tu Dominio**

En la página principal de DuckDNS:

1. En el campo **"sub domain"**, escribir:
   ```
   estacionamiento-losrios
   ```
   
2. Hacer clic en **"add domain"**

3. Verás tu dominio creado:
   ```
   estacionamiento-losrios.duckdns.org
   ```

4. **IMPORTANTE:** Copiar tu **TOKEN** (aparece arriba, algo como: `a7c4d0ad-114b-43d6-867e-f3b25c4b59fe`)

### **Paso 2.3: Averiguar tu IP Pública**

En tu servidor Ubuntu:
```bash
curl ifconfig.me
```

Ejemplo de resultado: `201.123.45.67`

### **Paso 2.4: Configurar IP en DuckDNS (Primera Vez)**

En la página de DuckDNS:
1. Pegar tu IP pública en el campo junto a tu dominio
2. Hacer clic en **"update ip"**

Debe aparecer: **"OK"** en verde

### **Paso 2.5: Instalar Cliente DuckDNS en Ubuntu**

Esto actualizará tu IP automáticamente si cambia.

```bash
# Crear directorio
mkdir ~/duckdns
cd ~/duckdns

# Crear script
nano duck.sh
```

Pegar este contenido (reemplaza TU-TOKEN y TU-DOMINIO):
```bash
#!/bin/bash
echo url="https://www.duckdns.org/update?domains=estacionamiento-losrios&token=a7c4d0ad-114b-43d6-867e-f3b25c4b59fe&ip=" | curl -k -o ~/duckdns/duck.log -K -
```

**Reemplaza:**
- `estacionamiento-losrios` → Tu dominio (sin .duckdns.org)
- `a7c4d0ad...` → Tu token de DuckDNS

Guardar (Ctrl+X, luego Y, luego Enter)

```bash
# Dar permisos de ejecución
chmod 700 duck.sh

# Probar manualmente
./duck.sh

# Ver resultado
cat duck.log
# Debe decir: OK
```

### **Paso 2.6: Automatizar Actualización (Cron)**

```bash
# Editar crontab
crontab -e

# Si pregunta editor, elegir nano (opción 1)

# Agregar esta línea al final:
*/5 * * * * ~/duckdns/duck.sh >/dev/null 2>&1

# Guardar (Ctrl+X, Y, Enter)
```

Esto actualizará tu IP cada 5 minutos automáticamente.

### **Paso 2.7: Verificar que DuckDNS Funciona**

Desde cualquier PC con internet:
```bash
ping estacionamiento-losrios.duckdns.org
```

Debe responder con tu IP pública.

---

## 🔌 PARTE 3: CONFIGURAR ROUTER (Port Forwarding)

### **Paso 3.1: Acceder al Router**

1. Abrir navegador
2. Ir a la IP del router (usualmente):
   - `http://192.168.1.1`
   - `http://192.168.0.1`
   - `http://10.0.0.1`

3. Iniciar sesión con usuario/contraseña del router

**Si no tienes las credenciales:**
- Buscar en Google: "contraseña por defecto [MARCA_ROUTER]"
- Buscar etiqueta en el router físico
- Llamar a tu ISP

### **Paso 3.2: Configurar IP Estática para el Servidor (Opcional pero Recomendado)**

En el router, buscar sección:
- **"DHCP"** o **"LAN Settings"** o **"Reserva DHCP"**

Configurar:
```
MAC Address: [MAC del servidor Ubuntu]
IP Address: 192.168.1.100 (o la que prefieras)
```

### **Paso 3.3: Configurar Port Forwarding**

Buscar sección en el router:
- **"Port Forwarding"**
- **"Virtual Server"**
- **"NAT"**
- **"Reenvío de Puertos"**

Crear 2 reglas:

**Regla 1: HTTP**
```
Service Name: Apache-HTTP
External Port: 80
Internal Port: 80
Internal IP: 192.168.1.100 (IP del servidor Ubuntu)
Protocol: TCP
Enable: ✅
```

**Regla 2: HTTPS**
```
Service Name: Apache-HTTPS
External Port: 443
Internal Port: 443
Internal IP: 192.168.1.100 (IP del servidor Ubuntu)
Protocol: TCP
Enable: ✅
```

**Guardar cambios y reiniciar router si es necesario.**

### **Paso 3.4: Verificar Puertos Abiertos**

Desde un navegador (en internet, NO en la red local):

Ir a: **https://www.yougetsignal.com/tools/open-ports/**

```
Remote Address: estacionamiento-losrios.duckdns.org
Port Number: 80
```

Hacer clic en **"Check"**

Debe decir: **"Port 80 is open"** ✅

Repetir para puerto 443.

---

## 🔐 PARTE 4: CONFIGURAR APACHE PARA DUCKDNS

### **Paso 4.1: Crear VirtualHost**

```bash
# Crear archivo de configuración
sudo nano /etc/apache2/sites-available/estacionamiento.conf
```

Pegar este contenido (reemplaza el dominio):
```apache
<VirtualHost *:80>
    ServerName estacionamiento-losrios.duckdns.org
    ServerAdmin admin@estacionamiento-losrios.duckdns.org
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

Guardar (Ctrl+X, Y, Enter)

### **Paso 4.2: Habilitar Sitio y Módulos**

```bash
# Habilitar sitio
sudo a2ensite estacionamiento.conf

# Habilitar módulo rewrite (por si usas .htaccess)
sudo a2enmod rewrite

# Recargar Apache
sudo systemctl reload apache2
```

### **Paso 4.3: Probar Acceso desde Internet**

Desde un navegador (usando datos móviles o red diferente):
```
http://estacionamiento-losrios.duckdns.org/
```

**Debe cargar tu sistema.** ✅

---

## 🔒 PARTE 5: INSTALAR CERTIFICADO SSL (HTTPS)

### **Paso 5.1: Instalar Certbot**

```bash
# Instalar Certbot y plugin para Apache
sudo apt install certbot python3-certbot-apache -y
```

### **Paso 5.2: Obtener Certificado SSL**

```bash
sudo certbot --apache -d estacionamiento-losrios.duckdns.org
```

**Responder las preguntas:**

1. **Email address:**
   ```
   tu-email@gmail.com
   ```
   (Para notificaciones de renovación)

2. **Agree to Terms of Service:**
   ```
   A (Agree)
   ```

3. **Share email with EFF:**
   ```
   N (No, opcional)
   ```

4. **Redirect HTTP to HTTPS:**
   ```
   2 (Redirect - Recomendado)
   ```

**Debe decir:**
```
Congratulations! You have successfully enabled HTTPS on
https://estacionamiento-losrios.duckdns.org
```

### **Paso 5.3: Verificar Renovación Automática**

```bash
# Certbot configura renovación automática
# Verificar que funcione:
sudo certbot renew --dry-run

# Debe decir: "Congratulations, all simulated renewals succeeded"
```

El certificado se renovará automáticamente cada 90 días.

### **Paso 5.4: Probar HTTPS**

Desde navegador:
```
https://estacionamiento-losrios.duckdns.org/
```

Debe mostrar **candado verde** 🔒 en la barra de direcciones.

---

## 🛡️ PARTE 6: CONFIGURAR FIREWALL

### **Paso 6.1: Configurar UFW (Firewall Ubuntu)**

```bash
# Instalar UFW (si no está instalado)
sudo apt install ufw -y

# Permitir SSH (IMPORTANTE: hacer esto primero)
sudo ufw allow 22/tcp

# Permitir HTTP
sudo ufw allow 80/tcp

# Permitir HTTPS
sudo ufw allow 443/tcp

# Habilitar firewall
sudo ufw enable

# Verificar estado
sudo ufw status

# Debe mostrar:
# Status: active
# 22/tcp      ALLOW       Anywhere
# 80/tcp      ALLOW       Anywhere
# 443/tcp     ALLOW       Anywhere
```

### **Paso 6.2: Cambiar Puerto SSH (Opcional pero Recomendado)**

```bash
# Editar configuración SSH
sudo nano /etc/ssh/sshd_config

# Buscar línea:
# Port 22

# Cambiar a:
Port 2222

# Guardar (Ctrl+X, Y, Enter)

# Reiniciar SSH
sudo systemctl restart ssh

# Actualizar firewall
sudo ufw allow 2222/tcp
sudo ufw delete allow 22/tcp
```

**Ahora debes conectarte por SSH así:**
```bash
ssh -p 2222 usuario@192.168.1.X
```

---

## ✅ PARTE 7: PRUEBAS FINALES

### **Test 1: Acceso Local (Oficina)**

Desde PC en la oficina:
```
http://192.168.1.100/sistemaEstacionamiento/
o
https://estacionamiento-losrios.duckdns.org/
```

**Debe funcionar:** ✅
- Login ✅
- Dashboard ✅
- Impresión (desde PC Windows 7) ✅

### **Test 2: Acceso Remoto (Internet)**

Desde celular usando datos móviles (NO wifi de oficina):
```
https://estacionamiento-losrios.duckdns.org/
```

**Debe funcionar:** ✅
- Login ✅
- Dashboard ✅
- Reportes ✅
- Impresión ❌ (normal, impresora está en oficina)

### **Test 3: Certificado SSL**

Hacer clic en el **candado** 🔒 en la barra de direcciones.

Debe mostrar:
```
✅ Conexión segura
✅ Certificado válido
✅ Emitido por: Let's Encrypt
```

---

## 📱 PARTE 8: CONFIGURAR ACCESO MÓVIL

### **Para el Jefe (Smartphone/Tablet):**

#### **Android:**
1. Abrir Chrome
2. Ir a: `https://estacionamiento-losrios.duckdns.org/`
3. Tocar menú (⋮) → "Agregar a pantalla de inicio"
4. Nombrar: "Estacionamiento"
5. Listo! Ícono en pantalla como app

#### **iPhone/iPad:**
1. Abrir Safari
2. Ir a: `https://estacionamiento-losrios.duckdns.org/`
3. Tocar botón "Compartir" 📤
4. Tocar "Agregar a pantalla de inicio"
5. Nombrar: "Estacionamiento"
6. Listo! Ícono en pantalla como app

---

## 🔧 MANTENIMIENTO

### **Ver Logs de Acceso:**
```bash
sudo tail -f /var/log/apache2/estacionamiento_access.log
```

### **Ver Logs de Errores:**
```bash
sudo tail -f /var/log/apache2/estacionamiento_error.log
```

### **Renovar Certificado Manualmente (si es necesario):**
```bash
sudo certbot renew
sudo systemctl reload apache2
```

### **Ver Estado de DuckDNS:**
```bash
cat ~/duckdns/duck.log
# Debe decir: OK
```

### **Actualizar Sistema:**
```bash
sudo apt update
sudo apt upgrade -y
sudo systemctl restart apache2
```

---

## 🚨 SOLUCIÓN DE PROBLEMAS

### **Problema: No puedo acceder desde internet**

**Verificar:**
```bash
# 1. Verificar que Apache esté corriendo
sudo systemctl status apache2

# 2. Verificar que DuckDNS esté actualizado
cat ~/duckdns/duck.log

# 3. Verificar firewall
sudo ufw status

# 4. Verificar puerto abierto
# Ir a: https://www.yougetsignal.com/tools/open-ports/
# Probar puerto 80 y 443
```

### **Problema: Certificado SSL no funciona**

```bash
# Renovar certificado
sudo certbot renew --force-renewal
sudo systemctl reload apache2
```

### **Problema: IP pública cambió**

```bash
# Ejecutar manualmente DuckDNS
~/duckdns/duck.sh
cat ~/duckdns/duck.log
# Debe decir: OK
```

---

## 📊 RESUMEN FINAL

### **URLs de Acceso:**

**Local (Oficina):**
- `http://192.168.1.100/sistemaEstacionamiento/`
- `https://estacionamiento-losrios.duckdns.org/`

**Remoto (Internet):**
- `https://estacionamiento-losrios.duckdns.org/`

### **Funcionalidades:**

| Función | Local | Remoto |
|---------|-------|--------|
| Login | ✅ | ✅ |
| Dashboard | ✅ | ✅ |
| Registrar Ingreso | ✅ | ✅ |
| Cobrar Salida | ✅ | ✅ |
| Reportes | ✅ | ✅ |
| Cierre de Caja | ✅ | ✅ |
| **Impresión** | ✅ | ❌ |

### **Seguridad:**

- ✅ HTTPS habilitado (cifrado end-to-end)
- ✅ Certificado SSL válido
- ✅ Firewall configurado
- ✅ Login con usuario/contraseña
- ✅ Puerto SSH cambiado (opcional)

---

## 💰 COSTO TOTAL: $0

Todo es gratis:
- ✅ DuckDNS: Gratis
- ✅ Let's Encrypt: Gratis
- ✅ Ubuntu: Gratis
- ✅ Apache/PHP: Gratis

---

## 📞 PRÓXIMOS PASOS

1. ✅ Implementar siguiendo esta guía paso a paso
2. ✅ Probar acceso local
3. ✅ Probar acceso remoto
4. ✅ Dar URL al jefe
5. ✅ Agregar acceso directo en móvil del jefe

**¡Tu sistema estará disponible 24/7 desde cualquier lugar!** 🌍

---

¿Necesitas ayuda con algún paso específico?

