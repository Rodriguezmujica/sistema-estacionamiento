# 🔐 Configuración de Contraseñas para Linux

## 🎯 ¿Qué se hizo?

Tu sistema ahora:
- ✅ **Detecta automáticamente** si está en Windows o Linux
- ✅ **Usa contraseñas seguras** en Linux (obligatorio)
- ✅ **Sigue funcionando** en Windows sin cambios
- ✅ **Archivos de configuración separados** (no se suben a Git)

---

## 🚨 IMPORTANTE: En Linux NO puedes usar contraseña vacía

**Antes (solo funcionaba en Windows):**
```php
$pass = '';  // ❌ Esto NO funciona en Linux
```

**Ahora (funciona en ambos):**
```php
// El sistema detecta automáticamente:
// - Windows → usa 'root' sin contraseña
// - Linux → usa 'estacionamiento_user' con contraseña
```

---

## 🔧 Configuración en Antix Linux

### Método 1: Con config.php (⭐ Recomendado)

```bash
# 1. Copiar archivo de ejemplo
cp config.php.example config.php

# 2. Editar
nano config.php
```

Dentro, cambiar:
```php
define('DB_USER', 'estacionamiento_user');  // ← Usuario MySQL
define('DB_PASS', 'MiPassword123!');        // ← Tu contraseña
```

**✅ Ventajas:**
- No se sube a Git (está en `.gitignore`)
- Más seguro
- Fácil cambiar entre entornos

### Método 2: Editando conexion.php directamente

```bash
nano conexion.php
```

Buscar la línea 45:
```php
$pass = 'CAMBIAR_ESTA_CONTRASEÑA';  // ← Cambiar por tu contraseña real
```

---

## 📊 Resumen de Cambios

### Archivos Actualizados

| Tipo | Cantidad | Estado |
|------|----------|--------|
| Archivos API | 28 | ✅ Actualizados |
| Scripts SQL | 12 | ✅ Actualizados |
| Archivos raíz | 1 | ✅ Corregido |
| **TOTAL** | **41 archivos** | ✅ **100% Listo** |

### Nuevos Archivos Creados

1. ✅ `config.php.example` - Plantilla de configuración
2. ✅ `SETUP_RAPIDO_LINUX.md` - Guía rápida
3. ✅ `CONFIGURAR_CONTRASEÑAS.md` - Este archivo
4. ✅ `.gitignore` actualizado

---

## 🧪 Cómo Probar

### En tu navegador:

```
http://localhost/sistemaEstacionamiento/verificar_unificacion.php
```

Deberías ver un **reporte completo** con:
- ✅ Conexión a base de datos exitosa
- ✅ X archivos correctos de Y totales
- 🎉 ¡EXCELENTE! Sistema unificado correctamente

---

## 🔑 Generar Contraseña Segura

En Linux:
```bash
openssl rand -base64 16
```

Ejemplo de salida: `kL9m#Xp2$vN8qR@5`

Copia ese texto y úsalo como contraseña en MySQL y en `config.php`

---

## 📝 Pasos Completos

### 1️⃣ Crear usuario en MySQL

```bash
sudo mysql -u root -p
```

```sql
CREATE USER 'estacionamiento_user'@'localhost' 
IDENTIFIED BY 'kL9m#Xp2$vN8qR@5';  -- ← Tu contraseña aquí

GRANT ALL PRIVILEGES ON estacionamiento.* 
TO 'estacionamiento_user'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

### 2️⃣ Configurar el sistema

**Opción A: Con config.php**
```bash
cp config.php.example config.php
nano config.php
# Cambiar DB_USER y DB_PASS
```

**Opción B: Editar conexion.php**
```bash
nano conexion.php
# Ir a línea 45 y cambiar la contraseña
```

### 3️⃣ Verificar

```bash
# En el navegador:
http://localhost/sistemaEstacionamiento/verificar_unificacion.php
```

---

## ⚠️ Troubleshooting

### Error: "Access denied for user"

**Causa:** Contraseña incorrecta o usuario no existe.

**Solución:**
```bash
# Verificar que el usuario existe
sudo mysql -u root -p
```
```sql
SELECT User, Host FROM mysql.user WHERE User = 'estacionamiento_user';
```

Si no aparece, crear el usuario (paso 1).

### Error: "CAMBIAR_ESTA_CONTRASEÑA"

**Causa:** Estás en Linux y no creaste `config.php`.

**Solución:** Seguir el paso 2 (Opción A).

### Funciona en Windows pero no en Linux

**Causa:** Linux requiere contraseña obligatoriamente.

**Solución:** No puedes usar contraseña vacía en Linux. Debes:
1. Crear usuario con contraseña (paso 1)
2. Configurar el sistema (paso 2)

---

## 🎉 ¡Listo!

Ahora tu sistema:
- ✅ Funciona en **Windows** (XAMPP)
- ✅ Funciona en **Linux** (Antix, Ubuntu, Debian)
- ✅ Usa **contraseñas seguras**
- ✅ **41 archivos** unificados
- ✅ **Configuración separada** (más segura)

---

## 📚 Documentación Relacionada

- **`SETUP_RAPIDO_LINUX.md`** - Comandos copy-paste para setup rápido
- **`GUIA_MIGRACION_ANTIX_LINUX.md`** - Guía completa y detallada
- **`RESUMEN_CAMBIOS_UNIFICACION.md`** - Lista de todos los cambios
- **`config.php.example`** - Plantilla de configuración

---

**Última actualización:** Octubre 2025  
**Compatible con:** Windows (XAMPP), Antix Linux, Ubuntu, Debian

