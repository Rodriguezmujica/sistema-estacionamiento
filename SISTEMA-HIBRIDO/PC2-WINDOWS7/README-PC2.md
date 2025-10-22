# 💻 PC2-WINDOWS7 (PC de Producción)

## 📋 Archivos para PC2

Esta carpeta contiene archivos específicos para PC2 (Windows 7 - PC de Producción).

## ✅ Archivos incluidos:

- ✅ **Sistema híbrido completo** (con impresión)
- ✅ **APIs de sincronización**
- ✅ **Sistema de impresión** para impresora USB
- ✅ **Configuración de Firebase**

## 🚀 Instalación en PC2 (Windows 7):

### 1. Copiar archivos compartidos:
```cmd
# En Windows 7
xcopy "C:\ruta\SISTEMA-HIBRIDO\COMPARTIDOS\*" "C:\xampp\htdocs\sistemaEstacionamiento\" /E /I /Y
```

### 2. Copiar archivos específicos de PC2:
```cmd
# En Windows 7
xcopy "C:\ruta\SISTEMA-HIBRIDO\PC2-WINDOWS7\*" "C:\xampp\htdocs\sistemaEstacionamiento\" /E /I /Y
```

### 3. Verificar XAMPP:
```cmd
# En Windows 7
# Asegúrate de que XAMPP esté funcionando
# Apache y MySQL deben estar activos
```

## 🔧 Configuración específica:

- **Tipo de PC:** PC2_WINDOWS7
- **Es servidor principal:** No
- **Tiene impresora:** Sí (USB)
- **Prioridad de sincronización:** Baja (5 segundos)
- **Modo offline:** Disponible

## 📊 Funcionalidades:

- ✅ **Base de datos MySQL** local
- ✅ **Sincronización** con Firebase
- ✅ **APIs de sincronización**
- ✅ **Sistema de impresión** USB
- ✅ **Modo offline** con batería

## 🖨️ Sistema de Impresión:

- **Impresora:** USB conectada
- **Archivo:** printing-manager.js
- **Cola de impresión:** Automática
- **Formato:** Tickets térmicos

## 🔄 Sincronización:

- **Intervalo:** 5 segundos
- **Prioridad:** Baja
- **Modo:** PC de producción
- **Datos:** Tickets, servicios, usuarios
- **Offline:** Cola local

## 🔋 Modo Offline:

- **Funcionamiento:** Sin internet
- **Datos:** Se guardan localmente
- **Sincronización:** Automática al volver internet
- **Batería:** Funciona durante cortes de luz

## 📞 Soporte:

Si tienes problemas:
1. Revisa los logs del sistema
2. Verifica la conexión a Firebase
3. Comprueba la impresora USB
4. Consulta la documentación completa

---

**PC2 configurada correctamente** ✅
