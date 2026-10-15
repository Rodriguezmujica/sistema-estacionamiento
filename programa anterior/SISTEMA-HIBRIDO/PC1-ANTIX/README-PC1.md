# 🖥️ PC1-ANTIX (Servidor Principal)

## 📋 Archivos para PC1

Esta carpeta contiene archivos específicos para PC1 (Antix - Servidor Principal).

## ✅ Archivos incluidos:

- ✅ **Sistema híbrido completo** (sin impresión)
- ✅ **APIs de sincronización**
- ✅ **Configuración de Firebase**
- ❌ **NO incluir** sistema de impresión

## 🚀 Instalación en PC1 (Antix):

### 1. Copiar archivos compartidos:
```bash
# En Antix
cp -r /ruta/SISTEMA-HIBRIDO/COMPARTIDOS/* /var/www/html/sistemaEstacionamiento/
```

### 2. Copiar archivos específicos de PC1:
```bash
# En Antix
cp -r /ruta/SISTEMA-HIBRIDO/PC1-ANTIX/* /var/www/html/sistemaEstacionamiento/
```

### 3. Establecer permisos:
```bash
# En Antix
chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
chmod -R 755 /var/www/html/sistemaEstacionamiento
```

## 🔧 Configuración específica:

- **Tipo de PC:** PC1_ANTIX
- **Es servidor principal:** Sí
- **Tiene impresora:** No
- **Prioridad de sincronización:** Alta (3 segundos)
- **VPN Tailscale:** Configurado

## 📊 Funcionalidades:

- ✅ **Base de datos MySQL** principal
- ✅ **Sincronización** con Firebase
- ✅ **APIs de sincronización**
- ✅ **Acceso remoto** via Tailscale
- ❌ **Impresión** (no disponible)

## 🔄 Sincronización:

- **Intervalo:** 3 segundos
- **Prioridad:** Alta
- **Modo:** Servidor principal
- **Datos:** Tickets, servicios, usuarios

## 📞 Soporte:

Si tienes problemas:
1. Revisa los logs del sistema
2. Verifica la conexión a Firebase
3. Comprueba la configuración de MySQL
4. Consulta la documentación completa

---

**PC1 configurada correctamente** ✅
