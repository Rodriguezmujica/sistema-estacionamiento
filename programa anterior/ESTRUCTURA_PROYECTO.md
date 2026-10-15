# 📁 Estructura del Proyecto - Sistema de Estacionamiento

## 🎯 Archivos Principales (Raíz)
- `index.php` - Página principal del sistema
- `estacionamiento.sql` - Base de datos principal

## 📂 Carpetas Organizadas

### 🔧 `/config/` - Configuración
- `conexion.php` - Conexión principal a base de datos
- `conexion-antix.php` - Conexión para Antix Linux
- `conexion_linux.php` - Conexión para Linux
- `config-sistema-hibrido.js` - Configuración del sistema híbrido
- `sistema-hibrido.js` - Lógica del sistema híbrido
- `sync-config.js` - Sincronización de configuración
- `login.php`, `logout.php` - Autenticación
- `auth-hybrid.php` - Autenticación híbrida
- Archivos `.example` - Plantillas de configuración

### 🧪 `/tests/` - Pruebas y Diagnósticos
- `test_*.php` - Scripts de prueba
- `debug_*.php` - Scripts de depuración
- `diagnostico_*.php` - Diagnósticos del sistema
- `verificar_*.php` - Verificaciones
- `check_*.php` - Verificaciones de estado

### 🔧 `/maintenance/` - Mantenimiento y Reparación
- `fix_*.php` - Scripts de reparación
- `crear_*.php` - Scripts de creación
- `backup_*.php` - Scripts de respaldo
- `restaurar_*.php` - Scripts de restauración
- `optimizar_*.php` - Scripts de optimización
- `migrate_*.php` - Scripts de migración
- `limpiar_*.php` - Scripts de limpieza

### 🔥 `/firebase/` - Integración Firebase
- `firebase-*.js` - Scripts JavaScript de Firebase
- `firebase-*.php` - Scripts PHP de Firebase
- `firebase-*.html` - Páginas de monitoreo Firebase
- `firestore-service.php` - Servicio de Firestore

### 💳 `/tuu/` - Integración TUU
- `tuu-*.php` - APIs de TUU
- `tuu-*.js` - Scripts JavaScript de TUU
- `webhook-*.php` - Webhooks de TUU
- `integrate-*.php` - Integración TUU

### 📚 `/docs/` - Documentación
- `*.md` - Documentación en Markdown
- `*.html` - Documentación en HTML
- `*.txt` - Documentación en texto plano

### 📜 `/scripts/` - Scripts de Sistema
- `*.bat` - Scripts de Windows
- `*.sh` - Scripts de Linux/Unix
- `*.js` - Scripts de Node.js

### 🌐 `/api/` - APIs del Sistema
- APIs REST para todas las funcionalidades
- Endpoints para frontend y móvil

### 🎨 `/secciones/` - Páginas del Sistema
- `admin.php` - Panel de administración
- `reporte.php` - Reportes
- `lavados.php` - Gestión de lavados
- `cobro.php` - Sistema de cobro

### 💻 `/JS/` - JavaScript Frontend
- Scripts del lado del cliente
- Lógica de la interfaz de usuario

### 🎨 `/scss/` - Estilos
- Archivos SCSS compilados a CSS
- Estilos del sistema

### 🖨️ `/ImpresionTermica/` - Impresión
- Scripts de impresión térmica
- Generación de tickets

### 🗄️ `/sql/` - Base de Datos
- Scripts SQL
- Migraciones de base de datos

### 🔄 `/SISTEMA-HIBRIDO/` - Sistema Híbrido
- Archivos para el sistema híbrido Windows/Linux
- Documentación específica

## 🚀 Cómo Usar

### Para Desarrollo:
1. **Configuración**: Edita archivos en `/config/`
2. **Pruebas**: Ejecuta scripts en `/tests/`
3. **Mantenimiento**: Usa scripts en `/maintenance/`

### Para Producción:
1. **APIs**: Usa endpoints en `/api/`
2. **Frontend**: Páginas en `/secciones/`
3. **Scripts**: Automatización en `/scripts/`

## 📝 Notas
- Los archivos `.backup` son respaldos automáticos
- Los archivos `.example` son plantillas
- La carpeta `/logs/` contiene logs del sistema
- La carpeta `/backups_emergencia/` contiene respaldos de emergencia

---
*Estructura organizada para facilitar el mantenimiento y desarrollo del sistema.*
