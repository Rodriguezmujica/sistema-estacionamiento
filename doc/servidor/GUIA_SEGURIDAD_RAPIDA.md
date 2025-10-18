# 🔒 GUÍA RÁPIDA DE SEGURIDAD
## Para implementar YA en tu sistema

---

## ⚡ ACCIÓN INMEDIATA (Haz esto HOY)

### **1. Cambia la contraseña del servidor** 

**Actual:**
```
Usuario: ServidorLosRios
Contraseña: losrios733  ⚠️ DÉBIL
```

**Nueva (recomendada):**
```
Usuario: AdminLosRios  
Contraseña: LoSr!0s#2025$Sec_733  ✅ FUERTE
```

**Cómo cambiar en Windows:**
1. `Ctrl + Alt + Supr` → Cambiar contraseña
2. O: Panel de Control → Cuentas de Usuario → Cambiar contraseña

---

### **2. Crea usuario específico para la base de datos**

**NO uses `root` sin contraseña** ⚠️

**Ejecuta esto en phpMyAdmin (SQL):**

```sql
-- Crear usuario específico
CREATE USER 'estacionamiento_app'@'localhost' 
IDENTIFIED BY 'Est@c_L0sR!0s_2025#Segur@';

-- Dar permisos SOLO a tu base de datos
GRANT ALL PRIVILEGES ON estacionamiento.* 
TO 'estacionamiento_app'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;
```

---

### **3. Actualiza tu `conexion.php`**

**Reemplaza las líneas 11-14 por:**

```php
$host = '127.0.0.1';
$user = 'estacionamiento_app';  // ← Usuario nuevo
$pass = 'Est@c_L0sR!0s_2025#Segur@';  // ← Contraseña nueva
$dbname = "estacionamiento";
```

✅ **Prueba que funcione** antes de continuar

---

### **4. Protege `conexion.php`**

Ya está en `.gitignore` ✅, pero verifica:

```bash
# En tu terminal/cmd, ve a la carpeta del proyecto:
cd C:\xampp\htdocs\sistemaEstacionamiento

# Verifica que .gitignore contiene "conexion.php"
type .gitignore | findstr conexion.php
```

Si dice "conexion.php" → ✅ Está protegido

---

## 📝 PLAN DE MEJORA (Próximas semanas)

### **Semana 1: Configuración básica**
- [x] Cambiar contraseña del servidor ✅
- [x] Crear usuario de base de datos ✅  
- [x] Actualizar conexion.php ✅
- [ ] Crear archivo `config.private.php` con credenciales
- [ ] Instalar gestor de contraseñas (KeePass / Bitwarden)

### **Semana 2: Sistema de usuarios**
- [ ] Crear tabla `usuarios` en la base de datos
- [ ] Implementar niveles de acceso (admin, cajero, contador)
- [ ] Hashear contraseñas con `password_hash()`
- [ ] Agregar control de sesiones con timeout

### **Semana 3: Seguridad avanzada**
- [ ] Activar HTTPS (certificado SSL)
- [ ] Implementar logs de acceso
- [ ] Configurar backup automático
- [ ] Documentar procedimientos

---

## 🎯 REGLAS DE ORO (SIEMPRE)

1. ✅ **Contraseñas fuertes**: Mínimo 12 caracteres, mayúsculas, minúsculas, números, símbolos
2. ✅ **Cambiar cada 3 meses**: Contraseñas de admin
3. ✅ **Nunca compartir por WhatsApp**: Usa gestor de contraseñas
4. ✅ **Un usuario por persona**: No compartir cuentas
5. ✅ **Backup semanal**: De la base de datos
6. ✅ **Logs activos**: Para saber quién hizo qué
7. ✅ **HTTPS siempre**: Cuando esté en internet

---

## 🚫 LO QUE NUNCA DEBES HACER

1. ❌ Subir `conexion.php` a GitHub
2. ❌ Compartir contraseñas por email/WhatsApp
3. ❌ Usar `root` sin contraseña
4. ❌ Usar la misma contraseña para todo
5. ❌ Dejar sesiones abiertas en computadoras públicas
6. ❌ Dar acceso admin a todos los empleados
7. ❌ Ignorar actualizaciones de seguridad

---

## 📊 NIVELES DE ACCESO PARA TU NEGOCIO

### **👑 Administrador (TÚ)**
```
Usuario: admin_losrios
Contraseña: [FUERTE, en gestor de contraseñas]
Acceso: TODO
```

### **💼 Cajero/Operador (Empleados)**
```
Usuario: cajero1, cajero2, etc.
Contraseña: [Individual para cada uno]
Acceso: Solo registrar/cobrar
```

### **📊 Contador (Si aplica)**
```
Usuario: contador
Contraseña: [Fuerte]
Acceso: Solo ver reportes
```

---

## 🛡️ CHECKLIST DE SEGURIDAD MENSUAL

**Cada mes, verifica:**

- [ ] Todas las contraseñas siguen siendo seguras
- [ ] No hay usuarios innecesarios activos
- [ ] Backup de base de datos existe y funciona
- [ ] Logs no muestran accesos sospechosos
- [ ] Sistema operativo actualizado
- [ ] Antivirus actualizado

---

## 🆘 EN CASO DE PROBLEMA

### **Si olvidaste tu contraseña:**
1. Accede al servidor físicamente
2. Reinicia MySQL en modo `--skip-grant-tables`
3. Resetea la contraseña
4. Reinicia MySQL normalmente

### **Si sospechas acceso no autorizado:**
1. Cambia TODAS las contraseñas inmediatamente
2. Revisa logs de acceso
3. Haz backup de la base de datos
4. Verifica que no haya datos alterados

---

## 📱 HERRAMIENTAS RECOMENDADAS

### **Gestor de Contraseñas:**
- **KeePass** (gratis, local) ⭐
- **Bitwarden** (gratis, sincronización)
- **LastPass** (gratis)

### **Backup:**
- phpMyAdmin (export manual)
- Automatizar con script

### **Monitoreo:**
- Sistema de logs que creamos (debug_logger.php)
- ver_logs.php para revisar

---

## 💡 TIP FINAL

**Usa un gestor de contraseñas YA**. Es la mejor inversión de 30 minutos que puedes hacer para tu seguridad.

Ejemplo: **KeePass**
1. Descarga: https://keepass.info/
2. Crea una base de datos de contraseñas
3. Guarda todas tus credenciales ahí
4. Solo recuerda UNA contraseña maestra (la de KeePass)

---

**Fecha de creación:** [HOY]
**Próxima revisión:** [EN 30 DÍAS]

---

✅ **Recuerda: La seguridad no es un gasto, es una inversión**