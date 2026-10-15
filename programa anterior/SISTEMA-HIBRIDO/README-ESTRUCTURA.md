# 📁 Estructura del Sistema Híbrido

## 🎯 Organización de Archivos

Esta carpeta contiene todos los archivos necesarios para el sistema híbrido, organizados por PC y funcionalidad.

## 📂 Estructura de Carpetas

```
SISTEMA-HIBRIDO/
├── 📁 PC1-ANTIX/           # Archivos específicos para PC1 (Antix - Servidor Principal)
├── 📁 PC2-WINDOWS7/        # Archivos específicos para PC2 (Windows 7 - PC de Producción)
├── 📁 COMPARTIDOS/         # Archivos que van en ambas PC
├── 📁 DOCUMENTACION/       # Documentación y guías
└── 📄 README-ESTRUCTURA.md # Este archivo
```

## 🖥️ PC1-ANTIX (Servidor Principal)

**Ubicación de instalación:** `/var/www/html/sistemaEstacionamiento/`

### Archivos específicos:
- ✅ **Sistema híbrido completo** (sin impresión)
- ✅ **APIs de sincronización**
- ✅ **Configuración de Firebase**
- ❌ **NO incluir** sistema de impresión

### Características:
- 🖥️ **Servidor principal** con base de datos MySQL
- 🌐 **VPN Tailscale** para acceso remoto
- 🔄 **Sincronización** con Firebase
- 📊 **Prioridad alta** en sincronización

## 💻 PC2-WINDOWS7 (PC de Producción)

**Ubicación de instalación:** `C:\xampp\htdocs\sistemaEstacionamiento\`

### Archivos específicos:
- ✅ **Sistema híbrido completo** (con impresión)
- ✅ **APIs de sincronización**
- ✅ **Sistema de impresión** para impresora USB
- ✅ **Configuración de Firebase**

### Características:
- 🖨️ **Impresora USB** conectada
- 🔋 **Batería** para funcionamiento offline
- 🔄 **Sincronización** con Firebase
- 📊 **Prioridad baja** en sincronización

## 🔄 COMPARTIDOS (Ambas PC)

### Archivos comunes:
- ✅ **Configuración de Firebase** (firebase-config.js, firebase-config.php)
- ✅ **Configuración del sistema** (config-sistema-hibrido.js)
- ✅ **Configuración de sincronización** (sync-config.js)
- ✅ **Sistema de sincronización** (firebase-sync.js)
- ✅ **Detección de PC** (pc-detector.js)
- ✅ **Sistema principal** (sistema-hibrido.js)
- ✅ **APIs de sincronización** (carpeta api/)
- ✅ **Reglas de seguridad** (carpeta firebase-security-rules/)

## 📚 DOCUMENTACION

### Archivos de documentación:
- ✅ **README-SISTEMA-HIBRIDO.md** - Documentación completa
- ✅ **INSTRUCCIONES-CONFIGURACION.md** - Guía de configuración
- ✅ **Scripts de instalación** (install-sistema-hibrido.sh, install-sistema-hibrido.bat)
- ✅ **Archivos de prueba** (test-sistema-completo.html)

## 🚀 Instalación Rápida

### Para PC1 (Antix):
```bash
# Copiar archivos de COMPARTIDOS + PC1-ANTIX
cp -r COMPARTIDOS/* /var/www/html/sistemaEstacionamiento/
cp -r PC1-ANTIX/* /var/www/html/sistemaEstacionamiento/
```

### Para PC2 (Windows 7):
```cmd
# Copiar archivos de COMPARTIDOS + PC2-WINDOWS7
xcopy COMPARTIDOS\* C:\xampp\htdocs\sistemaEstacionamiento\ /E /I /Y
xcopy PC2-WINDOWS7\* C:\xampp\htdocs\sistemaEstacionamiento\ /E /I /Y
```

## ⏱️ Tiempos de Sincronización

| Evento | Tiempo | Descripción |
|--------|--------|-------------|
| **Ingreso en Windows 7** | 5-8 segundos | Windows 7 → Firebase → Antix |
| **Modificación en Antix** | 5-8 segundos | Antix → Firebase → Windows 7 |
| **Modo offline** | Inmediato | Se sincroniza cuando vuelve internet |
| **Heartbeat** | 30 segundos | Verificación de PC activa |

## 🔧 Configuración

1. **Firebase Console:** Configurar Authentication, Firestore y Storage
2. **Base de datos:** Usar tablas `tickets` y `servicios_lavado`
3. **Sincronización:** Automática cada 5 segundos
4. **Impresión:** Solo en PC2 (Windows 7)

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs del sistema
2. Ejecuta las pruebas de diagnóstico
3. Verifica la configuración de Firebase
4. Consulta la documentación

---

**¡El sistema híbrido está listo para instalar!** 🎉
