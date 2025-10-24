# 🔐 Credenciales del Sistema - Estacionamiento Los Ríos

**⚠️ DOCUMENTO CONFIDENCIAL**  
**Solo para uso del personal autorizado**  
**Fecha:** 24 de octubre, 2025  

---

## 👤 Usuarios del Sistema Web

### **Administrador Principal:**
- **Usuario:** `admin`
- **Contraseña:** `admin321`
- **Permisos:** Acceso completo al sistema, configuración, reportes

### **Usuario Cajero:**
- **Usuario:** `cajero`
- **Contraseña:** `733`
- **Permisos:** Operaciones de caja, ingreso/salida de vehículos

---

## 🖥️ Acceso a Computadoras

### **Servidor Windows 7:**
- **Usuario:** `Valdivia`
- **Contraseña:** `1234`
- **Función:** Servidor principal con XAMPP y base de datos

### **Cliente Antix Linux:**
- **Usuario:** `ServidorLosRios`
- **Contraseña:** `losrios733`
- **Función:** Cliente de red para operaciones

---

## 🗄️ Base de Datos MySQL

### **Acceso Local (Windows):**
- **Host:** `localhost`
- **Puerto:** `3306`
- **Usuario:** `root`
- **Contraseña:** *(vacía)*

### **Acceso Remoto (desde Antix):**
- **Host:** `192.168.3.101`
- **Puerto:** `3306`
- **Usuario:** `antix`
- **Contraseña:** `733`

### **Acceso por IP Externa:**
- **Host:** `[IP_PUBLICA_SERVIDOR]`
- **Puerto:** `3306`
- **Usuario:** `antix`
- **Contraseña:** `733`

---

## 🌐 Servicios Externos

### **Correo Electrónico:**
- **Email:** `lavadolosrios@gmail.com`
- **Contraseña:** `Losrios733`
- **Uso:** Acceso a Firebase y Tailscale

### **Firebase (si se requiere):**
- **Acceso:** Usar credenciales del correo
- **Email:** `lavadolosrios@gmail.com`
- **Contraseña:** `Losrios733`

### **Tailscale VPN:**
- **Acceso:** Usar credenciales del correo
- **Email:** `lavadolosrios@gmail.com`
- **Contraseña:** `Losrios733`

---

## 🔧 Acceso a Servicios del Sistema

### **XAMPP Control Panel:**
- **Ruta:** `C:\xampp\xampp-control.exe`
- **Acceso:** Usuario Windows `Valdivia`

### **phpMyAdmin:**
- **URL:** `http://localhost/phpmyadmin`
- **Usuario:** `root`
- **Contraseña:** *(vacía)*

### **Sistema Web:**
- **URL Local:** `http://localhost/sistemaEstacionamiento/`
- **URL Red:** `http://192.168.3.101/sistemaEstacionamiento/`

---

## 🖨️ Configuración de Impresora

### **Impresora Térmica:**
- **Nombre:** `POSESTACIONAMIENTO`
- **Tipo:** Star BSC10
- **Puerto:** USB o Red (según configuración)

---

## 📱 Terminal TUU

### **Configuración:**
- **IP del Terminal:** Configurar según manual del dispositivo
- **Puerto:** Según configuración del terminal
- **Credenciales:** Según configuración del proveedor TUU

---

## 🔒 Contraseñas de Comandos (Antix Linux)

### **Sudo/Administrador:**
- **Contraseña:** `losrios733`
- **Uso:** Comandos administrativos en Linux

---

## 📋 Información de Red

### **Configuración de Red:**
- **Servidor Windows IP:** `192.168.3.101`
- **Cliente Antix IP:** `192.168.3.XXX` (asignada automáticamente)
- **Puerto Web:** `80` (HTTP) / `443` (HTTPS)
- **Puerto MySQL:** `3306`

---

## 🚨 Procedimientos de Emergencia

### **Si se olvida contraseña de Windows:**
1. Usar cuenta de administrador local
2. Cambiar contraseña desde Panel de Control
3. Notificar al desarrollador

### **Si se olvida contraseña de Antix:**
1. Reiniciar en modo recuperación
2. Cambiar contraseña desde terminal
3. Notificar al desarrollador

### **Si falla conexión a base de datos:**
1. Verificar que MySQL esté ejecutándose
2. Verificar configuración de red
3. Revisar archivo `config/conexion.php`

---

## 📞 Contacto de Soporte

### **Desarrollador:**
- **Nombre:** Luis Miguel Rodriguez
- **Soporte:** 3 meses incluido
- **Contacto:** [Agregar información de contacto]

### **Archivos de Configuración:**
- **Conexión BD:** `config/conexion.php`
- **Configuración TUU:** `config/tuu_config.php`
- **Logs del sistema:** `logs/`

---

## ⚠️ Recomendaciones de Seguridad

### **Cambios Inmediatos Recomendados:**
1. **Cambiar contraseñas por defecto** del sistema web
2. **Configurar respaldo** de base de datos
3. **Establecer política** de cambio de contraseñas
4. **Documentar** cualquier cambio de configuración

### **Mantenimiento Regular:**
- **Respaldo semanal** de base de datos
- **Verificación mensual** de accesos
- **Actualización** de contraseñas cada 3 meses

---

## 📝 Notas Adicionales

### **Configuración Especial:**
- El sistema está configurado para funcionar en red local
- Tailscale permite acceso remoto seguro
- Las impresoras deben estar conectadas al servidor Windows

### **Archivos Importantes:**
- **Base de datos:** `database/estacionamiento.sql`
- **Configuración:** `config/`
- **Documentación:** `docs/`
- **Respaldos:** `backups/`

---

**🔐 MANTENER ESTE DOCUMENTO EN LUGAR SEGURO**  
**❌ NO COMPARTIR CON PERSONAL NO AUTORIZADO**

*Documento generado el 24 de octubre, 2025*
