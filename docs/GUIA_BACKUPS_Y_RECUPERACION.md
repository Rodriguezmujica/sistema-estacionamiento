# 🔒 Guía de Backups y Recuperación ante Desastres

## 🎯 ¿Por Qué Se "Borró" la Base de Datos?

**IMPORTANTE:** Las bases de datos MySQL **NO se borran solas**.

### Causas Comunes:

1. **Corrupción por apagado forzado**
   - Windows se apagó sin cerrar MySQL correctamente
   - Corte de luz
   - Reinicio forzado

2. **Fallo de disco**
   - Sectores defectuosos
   - Disco lleno temporalmente

3. **Antivirus/Firewall**
   - Bloqueó archivos de MySQL

4. **Error humano**
   - Alguien borró la BD sin querer
   - Se ejecutó DROP DATABASE accidentalmente

**Los cambios en PHP/JavaScript NO PUEDEN borrar MySQL.** Es imposible.

---

## ✅ SISTEMA DE PROTECCIÓN PROFESIONAL

He creado 3 herramientas para ti:

### 1. 🔄 `backup_automatico.php`
Crea backups automáticos de la BD

### 2. 🔄 `restaurar_backup.php`
Restaura la BD en caso de emergencia

### 3. 🔔 `monitoreo_bd.php`
Verifica que la BD esté sana

---

## 📋 CONFIGURACIÓN PASO A PASO

### **Paso 1: Crear Backup Manual Ahora**

```bash
# En Windows, abre el navegador:
http://localhost/sistemaEstacionamiento/backup_automatico.php
```

Deberías ver:
```
✅ Backup creado exitosamente
📊 Tamaño: X MB
📁 Total de backups: 1
```

### **Paso 2: Programar Backups Automáticos**

#### **En Windows:**

1. **Abrir Programador de Tareas:**
   - Presiona `Windows + R`
   - Escribe: `taskschd.msc`
   - Enter

2. **Crear Nueva Tarea:**
   - Click "Crear tarea básica..."
   - Nombre: `Backup Estacionamiento`
   - Descripción: `Backup automático diario de la base de datos`

3. **Configurar Desencadenador:**
   - Frecuencia: **Diariamente**
   - Hora: **23:00** (11 PM)
   - Recurrente: **Cada 1 día**

4. **Configurar Acción:**
   - Acción: **Iniciar un programa**
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `C:\xampp\htdocs\sistemaEstacionamiento\backup_automatico.php`

5. **Finalizar:**
   - Marcar: "Abrir propiedades al hacer clic en Finalizar"
   - En propiedades, marcar: "Ejecutar aunque el usuario no haya iniciado sesión"

#### **En Linux (Antix):**

```bash
# Editar crontab
crontab -e

# Agregar esta línea (backup todos los días a las 2 AM):
0 2 * * * /usr/bin/php /var/www/html/sistemaEstacionamiento/backup_automatico.php >> /var/log/backup_estacionamiento.log 2>&1

# Guardar y salir
```

### **Paso 3: Verificar Monitoreo**

```bash
http://localhost/sistemaEstacionamiento/monitoreo_bd.php
```

Deberías ver JSON con:
```json
{
  "status": "OK",
  "checks": {
    "conexion": "OK",
    "base_datos": "OK",
    "tablas": "5/5"
  },
  "alertas": []
}
```

---

## 🆘 RECUPERACIÓN DE EMERGENCIA

### **Si la Base de Datos Desaparece:**

1. **Mantén la calma** 🧘
2. **NO TOQUES NADA** en MySQL
3. **Abre el restaurador:**
   ```
   http://localhost/sistemaEstacionamiento/restaurar_backup.php
   ```

4. **Selecciona el backup más reciente**
5. **Click en "Restaurar"**
6. **Confirma la advertencia**
7. **Espera 10-30 segundos**
8. **✅ Listo**

---

## 📊 BACKUPS: Qué Se Guarda

### **Ubicación:**
```
C:\xampp\htdocs\sistemaEstacionamiento\backups\
```

### **Archivos:**
```
estacionamiento_backup_2025-01-15_10-30-00.sql.gz
estacionamiento_backup_2025-01-16_10-30-00.sql.gz
estacionamiento_backup_2025-01-17_10-30-00.sql.gz
```

### **Retención:**
- Se mantienen **30 días**
- Máximo **100 archivos**
- Se limpian automáticamente los antiguos

### **Compresión:**
- Archivos `.sql.gz` (comprimidos)
- Ahorra 70-80% de espacio

---

## 🔔 MONITOREO AUTOMÁTICO

### **Configurar Alertas (Opcional):**

Puedes programar que el monitoreo se ejecute cada hora y te envíe email si hay problemas:

```bash
# Windows Task Scheduler - cada hora
0 * * * * php C:\xampp\htdocs\sistemaEstacionamiento\monitoreo_bd.php
```

---

## 📝 LISTA DE VERIFICACIÓN ANTES DE IRTE

- [ ] Backups automáticos configurados (Task Scheduler)
- [ ] Probaste crear un backup manual
- [ ] Probaste restaurar un backup
- [ ] Monitoreo funcionando
- [ ] Carpeta `backups/` tiene al menos 3 backups
- [ ] Documentaste las credenciales en lugar seguro
- [ ] Capacitaste a alguien más sobre cómo restaurar

---

## 🌍 CONFIGURACIÓN PARA ESPAÑA (o Remoto)

### **Acceso Remoto Seguro:**

1. **VPN o SSH Tunnel** (recomendado)
2. **TeamViewer/AnyDesk** (para emergencias)
3. **Backup en la nube** (Dropbox, Google Drive)

### **Automatizar Backup a la Nube:**

```bash
# Copiar backups a Dropbox automáticamente
xcopy C:\xampp\htdocs\sistemaEstacionamiento\backups\*.gz "C:\Users\TuUsuario\Dropbox\BackupsEstacionamiento\" /D /Y
```

---

## ⚠️ REGLAS DE ORO

1. **NUNCA borres la carpeta `backups/`**
2. **SIEMPRE haz backup antes de actualizaciones grandes**
3. **VERIFICA los backups una vez al mes** (restaura uno de prueba)
4. **DOCUMENTA** las credenciales en lugar seguro
5. **CAPACITA** a alguien de confianza sobre cómo restaurar

---

## 🎓 ENTRENAMIENTO PARA TU REEMPLAZO

### **Guía para la persona que te reemplace:**

1. "Si la base de datos desaparece, no entres en pánico"
2. "Abre: `http://localhost/sistemaEstacionamiento/restaurar_backup.php`"
3. "Selecciona el backup más reciente"
4. "Click en Restaurar"
5. "Espera 30 segundos"
6. "Verifica que el sistema funcione"

**IMPRIME ESTA GUÍA** y déjala en el local.

---

## 📞 CONTACTO DE EMERGENCIA

**Antes de irte a España:**

1. Deja tu email/WhatsApp de emergencia
2. Configura acceso remoto (TeamViewer ID fijo)
3. Haz un video corto de cómo restaurar backup
4. Sube los backups a Google Drive/Dropbox

---

## ✅ RESUMEN

| Herramienta | Propósito | Frecuencia |
|-------------|-----------|------------|
| `backup_automatico.php` | Crear backup | Diario (automático) |
| `restaurar_backup.php` | Restaurar BD | Solo en emergencias |
| `monitoreo_bd.php` | Verificar salud | Cada hora (automático) |

---

**Con este sistema:**
- ✅ Backups automáticos diarios
- ✅ Recuperación en 30 segundos
- ✅ Monitoreo continuo
- ✅ Protección contra pérdida de datos
- ✅ Puedes irte tranquilo a España

---

**Creado:** Octubre 2025  
**Última actualización:** Hoy  
**Sistema:** Windows (XAMPP) + Linux (Antix)

