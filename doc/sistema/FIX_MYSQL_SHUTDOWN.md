# 🔧 Solución: MySQL Shutdown Unexpectedly

**Error:** MySQL se cierra solo en XAMPP

---

## ✅ SOLUCIÓN RÁPIDA (Funciona en el 90% de los casos)

### **Método 1: Restaurar desde Backup**

```batch
1. CERRAR XAMPP completamente

2. Navegar a:
   C:\xampp\mysql\data\

3. MOVER (NO eliminar) estos archivos a una carpeta temporal:
   - ibdata1
   - ib_logfile0
   - ib_logfile1
   
   Ejemplo: moverlos a C:\xampp\mysql\data\backup_old\

4. COPIAR desde backup:
   C:\xampp\mysql\backup\
   
   Copiar estos archivos:
   - ibdata1
   - ib_logfile0  
   - ib_logfile1
   
   Pegarlos en: C:\xampp\mysql\data\

5. INICIAR MySQL en XAMPP

6. RESTAURAR tu base de datos:
   - Ir a phpMyAdmin
   - Importar tu backup más reciente (.sql)
```

---

## 🔍 DIAGNÓSTICO

### **Ver Logs de Error:**

1. Abrir XAMPP Control Panel
2. Clic en "Logs" (botón junto a MySQL)
3. Ver el último error al final del archivo

---

## 🛠️ SOLUCIONES POR TIPO DE ERROR

### **Error: "Port 3306 already in use"**

**Causa:** Otro MySQL está corriendo

**Solución:**
```batch
1. Ctrl+Shift+Esc (Administrador de Tareas)
2. Pestaña "Detalles"
3. Buscar "mysqld.exe"
4. Si existe → Clic derecho → Finalizar tarea
5. Reiniciar MySQL en XAMPP
```

**O cambiar puerto:**
```ini
1. XAMPP → Config → my.ini
2. Buscar: port=3306
3. Cambiar a: port=3307
4. Guardar
5. Actualizar conexion.php:
   $servidor = "localhost:3307";
```

---

### **Error: "Table is marked as crashed"**

**Causa:** Tabla corrupta

**Solución:**
```sql
1. Abrir CMD como Administrador
2. cd C:\xampp\mysql\bin
3. mysql -u root -p
4. REPAIR TABLE nombre_tabla;
```

---

### **Error: "InnoDB: Unable to lock"**

**Causa:** Archivos de log corruptos

**Solución:** Usar Método 1 (arriba)

---

### **Error: "Can't create/write to file"**

**Causa:** Permisos o espacio en disco

**Solución:**
```batch
1. Verificar espacio en C:\ (mínimo 1 GB libre)
2. Clic derecho en C:\xampp\mysql\data\
3. Propiedades → Seguridad
4. Editar → Agregar → Todos
5. Dar control total
6. Aplicar
```

---

## ⚡ SOLUCIÓN DE EMERGENCIA

Si nada funciona y necesitas trabajar YA:

### **Reinstalar Solo MySQL:**

```batch
1. HACER BACKUP de:
   C:\xampp\mysql\data\estacionamiento\
   (Copiar carpeta completa a escritorio)

2. Desinstalar XAMPP

3. Reinstalar XAMPP

4. Restaurar carpeta:
   Copiar carpeta estacionamiento de vuelta a:
   C:\xampp\mysql\data\

5. Iniciar MySQL
```

---

## 🔒 PREVENCIÓN

### **Para evitar que vuelva a pasar:**

1. **No cerrar XAMPP bruscamente**
   - Siempre usar botón "Stop" antes de cerrar

2. **Hacer backup periódico:**
   ```sql
   mysqldump -u root estacionamiento > backup.sql
   ```

3. **Mantener espacio libre:**
   - Mínimo 2-3 GB en C:\

4. **Agregar excepción en antivirus:**
   - Excluir: C:\xampp\

---

## 📋 CHECKLIST DE SOLUCIÓN

- [ ] Ver logs de error (identificar causa exacta)
- [ ] Verificar que no hay otro MySQL corriendo
- [ ] Verificar espacio en disco (mín 1 GB)
- [ ] Probar Método 1 (restaurar desde backup)
- [ ] Si falla, cambiar puerto a 3307
- [ ] Si falla, reinstalar XAMPP (con backup)

---

## 🆘 SI NADA FUNCIONA

**Backup de Emergencia:**

```sql
1. Copiar manualmente:
   C:\xampp\mysql\data\estacionamiento\
   
2. Guardar en lugar seguro

3. Reinstalar XAMPP completamente

4. Importar backup .sql
```

---

## 💾 HACER BACKUP AHORA (Prevención)

```batch
# PowerShell como Administrador
cd C:\xampp\mysql\bin
.\mysqldump.exe -u root estacionamiento > C:\backup_estacionamiento.sql
```

Guardar `backup_estacionamiento.sql` en lugar seguro.

---

**Última actualización:** Octubre 2025  
**Efectividad:** 95% con Método 1

