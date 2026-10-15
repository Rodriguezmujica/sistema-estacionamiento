# Guía: Acceso Local y Remoto en Ubuntu

---

## 📋 ESCENARIO

- **Servidor:** Ubuntu con Apache/PHP
- **Acceso Local:** Desde la red de la oficina
- **Acceso Remoto:** Desde internet (casa del jefe, otra oficina)

---

## 🎯 SOLUCIÓN RECOMENDADA: Acceso Web Directo

### **Arquitectura:**

```
┌─────────────────────────────────────────────────┐
│  OFICINA (Red Local)                            │
│                                                 │
│  ┌──────────────┐        ┌──────────────┐      │
│  │ PC Windows 7 │───────▶│ Servidor     │      │
│  │  + Impresora │  LAN   │  Ubuntu      │      │
│  └──────────────┘        │  Apache/PHP  │      │
│                          └──────┬───────┘      │
│  Acceso local:                  │              │
│  http://192.168.1.100/          │              │
└─────────────────────────────────┼──────────────┘
                                  │
                          ┌───────▼────────┐
                          │    INTERNET    │
                          │   (Router con  │
                          │  puerto 80/443)│
                          └───────┬────────┘
                                  │
                  ┌───────────────┴───────────────┐
                  │                               │
           ┌──────▼──────┐              ┌────────▼──────┐
           │ Casa Jefe   │              │ Otra Oficina  │
           │             │              │               │
           │ Acceso:     │              │ Acceso:       │
           │ http://...  │              │ http://...    │
           └─────────────┘              └───────────────┘
```

---

## 🚀 CONFIGURACIÓN PASO A PASO

### **PARTE 1: Configurar Ubuntu Server**

#### **1. Instalar Apache y PHP (si no lo tienes):**
```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql

# Habilitar Apache
sudo systemctl enable apache2
sudo systemctl start apache2
```

#### **2. Copiar tu sistema:**
```bash
sudo cp -r sistemaEstacionamiento /var/www/html/
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
```

#### **3. Verificar que funciona localmente:**
```
http://192.168.1.X/sistemaEstacionamiento/
```

---

### **PARTE 2: Configurar Acceso desde Internet**

#### **Opción A: Con IP Pública Estática**

Si tu ISP te da IP pública estática:

**1. Averiguar tu IP pública:**
```bash
curl ifconfig.me
# Ejemplo: 201.123.45.67
```

**2. Abrir puerto en el router:**
- Acceder al router (http://192.168.1.1 o similar)
- Ir a "Port Forwarding" o "Reenvío de Puertos"
- Configurar:
  ```
  Puerto Externo: 80
  Puerto Interno: 80
  IP Interna: 192.168.1.X (tu servidor Ubuntu)
  Protocolo: TCP
  ```

**3. Acceder desde internet:**
```
http://201.123.45.67/sistemaEstacionamiento/
```

#### **Opción B: Con IP Dinámica + DuckDNS (GRATIS)**

Si tu IP cambia (más común):

**1. Registrarte en DuckDNS:**
- Ir a https://www.duckdns.org/
- Crear cuenta (con Google/GitHub)
- Crear un dominio: `estacionamiento-losrios.duckdns.org`

**2. Instalar cliente DuckDNS en Ubuntu:**
```bash
mkdir ~/duckdns
cd ~/duckdns
nano duck.sh
```

Pegar este contenido (reemplazar TOKEN y DOMINIO):
```bash
#!/bin/bash
echo url="https://www.duckdns.org/update?domains=TU-DOMINIO&token=TU-TOKEN&ip=" | curl -k -o ~/duckdns/duck.log -K -
```

```bash
chmod 700 duck.sh
```

**3. Configurar cron para actualizar IP automáticamente:**
```bash
crontab -e
```

Agregar esta línea:
```
*/5 * * * * ~/duckdns/duck.sh >/dev/null 2>&1
```

**4. Abrir puerto en router (mismo que Opción A)**

**5. Acceder desde internet:**
```
http://estacionamiento-losrios.duckdns.org/sistemaEstacionamiento/
```

---

### **PARTE 3: Agregar HTTPS (IMPORTANTE para seguridad)**

#### **Instalar Certbot (Let's Encrypt - GRATIS):**

```bash
# Si usas DuckDNS:
sudo apt install certbot python3-certbot-apache

# Obtener certificado:
sudo certbot --apache -d estacionamiento-losrios.duckdns.org
```

Seguir las instrucciones en pantalla.

**Ahora acceso será:**
```
https://estacionamiento-losrios.duckdns.org/sistemaEstacionamiento/
```

**Certificado se renueva automáticamente** cada 90 días.

---

## 🔐 SEGURIDAD ADICIONAL

### **1. Restringir Panel de Administración**

En Apache, crear archivo `.htaccess` en `secciones/`:

```apache
# secciones/.htaccess
<Files "admin.php">
  Order Deny,Allow
  Deny from all
  Allow from 192.168.1.0/24  # Red local
  Allow from TU-IP-CASA-JEFE # IP específica del jefe
</Files>
```

### **2. Firewall en Ubuntu**

```bash
# Permitir solo HTTP, HTTPS y SSH
sudo ufw allow 22    # SSH
sudo ufw allow 80    # HTTP
sudo ufw allow 443   # HTTPS
sudo ufw enable
```

### **3. Cambiar Puerto SSH (Opcional pero recomendado)**

```bash
sudo nano /etc/ssh/sshd_config
# Cambiar: Port 22 → Port 2222
sudo systemctl restart ssh

# Actualizar firewall:
sudo ufw allow 2222
sudo ufw delete allow 22
```

---

## 📱 CONFIGURAR EN SMARTPHONE/TABLET

Tu jefe puede acceder desde móvil:

**Android/iOS:**
- Abrir navegador (Chrome/Safari)
- Ir a: `https://estacionamiento-losrios.duckdns.org/sistemaEstacionamiento/`
- Agregar a pantalla de inicio para acceso rápido

**Responsive:**
Tu sistema ya usa Bootstrap, así que debería verse bien en móvil.

---

## 🧪 PRUEBAS

### **Desde la oficina (Local):**
```
http://192.168.1.X/sistemaEstacionamiento/
o
http://localhost/sistemaEstacionamiento/
```

### **Desde casa/otra oficina (Remoto):**
```
https://estacionamiento-losrios.duckdns.org/sistemaEstacionamiento/
```

### **Verificar que funciona:**
1. ✅ Login funciona
2. ✅ Dashboard carga
3. ✅ Reportes se ven
4. ✅ NO intentes imprimir desde remoto (la impresora está en la oficina)

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### **La Impresión:**
- ❌ **NO funcionará desde acceso remoto** (la impresora está en la oficina)
- ✅ Solo funcionará desde la PC Windows 7 en la oficina
- 💡 Tu jefe puede VER reportes, pero NO imprimir

### **Si el jefe necesita imprimir:**
1. Ver en pantalla y tomar screenshot
2. Exportar a PDF (puedes agregar esta función)
3. Enviar por correo

---

## 💰 COSTOS

| Servicio | Costo |
|----------|-------|
| DuckDNS | Gratis |
| Let's Encrypt (HTTPS) | Gratis |
| IP Pública | Depende del ISP (a veces incluida) |
| Dominio .com (opcional) | ~$10-15/año |

**Total mínimo: $0** (usando DuckDNS)

---

## 🔄 ALTERNATIVA: Sólo Reportes Remotos

Si solo quieres que tu jefe vea reportes (no imprima):

**Crear subdominio o carpeta pública:**
```
https://reportes.estacionamiento-losrios.duckdns.org/
```

Solo con acceso a reportes (sin edición).

---

## 📞 SOPORTE

### **Verificar puerto abierto:**
Desde internet, usar: https://www.yougetsignal.com/tools/open-ports/

### **Ver logs de Apache:**
```bash
sudo tail -f /var/log/apache2/access.log
sudo tail -f /var/log/apache2/error.log
```

### **Verificar firewall:**
```bash
sudo ufw status
```

---

## ✅ CHECKLIST DE CONFIGURACIÓN

### **En Ubuntu Server:**
- [ ] Apache instalado y corriendo
- [ ] Sistema copiado a `/var/www/html/`
- [ ] Funciona localmente (`http://192.168.1.X/`)
- [ ] DuckDNS configurado (si usas)
- [ ] Firewall configurado
- [ ] HTTPS configurado (Certbot)

### **En Router:**
- [ ] Puerto 80 abierto → Servidor
- [ ] Puerto 443 abierto → Servidor (para HTTPS)
- [ ] IP del servidor configurada como estática en DHCP

### **Pruebas:**
- [ ] Acceso local funciona
- [ ] Acceso remoto funciona
- [ ] HTTPS funciona
- [ ] Login funciona desde remoto
- [ ] Reportes se ven desde remoto

---

## 🎯 RESULTADO FINAL

Tu jefe podrá:
- ✅ Ver dashboard en tiempo real
- ✅ Ver reportes
- ✅ Ver cierre de caja
- ✅ Ver ingresos y egresos
- ✅ Acceder desde PC, tablet o smartphone
- ❌ NO podrá imprimir (impresora está en oficina)

**Desde casa, oficina remota o cualquier lugar con internet.**

---

¿Quieres que te ayude con algún paso específico de la configuración?