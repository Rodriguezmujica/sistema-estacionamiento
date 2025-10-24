# 🔒 BUENAS PRÁCTICAS DE SEGURIDAD
## Sistema de Estacionamiento Los Ríos

---

## ⚠️ IMPORTANTE: NO COMPARTIR PÚBLICAMENTE

Este documento contiene información sensible. NO lo subas a GitHub público ni lo compartas.

---

## 1️⃣ NIVELES DE ACCESO RECOMENDADOS

### **Nivel 1: Administrador Total** 👑
- **Quién:** Dueño del negocio / Gerente
- **Acceso a:**
  - ✅ Panel de administración completo
  - ✅ Reportes financieros
  - ✅ Configuración de precios
  - ✅ Gestión de usuarios
  - ✅ Cierre de caja
  - ✅ Exportar datos
  - ✅ Acceso a servidor (ServidorLosRios)

### **Nivel 2: Operador/Cajero** 💼
- **Quién:** Empleados de confianza
- **Acceso a:**
  - ✅ Registrar ingresos/salidas
  - ✅ Cobrar servicios
  - ✅ Imprimir tickets
  - ✅ Ver últimos movimientos (solo del día)
  - ❌ NO cambiar precios
  - ❌ NO ver reportes completos
  - ❌ NO gestionar usuarios
  - ❌ NO acceso al servidor

### **Nivel 3: Solo Lectura** 📊
- **Quién:** Contadores, auditores
- **Acceso a:**
  - ✅ Ver reportes
  - ✅ Exportar datos
  - ❌ NO modificar nada
  - ❌ NO registrar movimientos

---

## 2️⃣ GESTIÓN DE CREDENCIALES

### **A. Servidor (ServidorLosRios)**

#### 🔴 MAL (NO HACER):
```
Usuario: ServidorLosRios
Contraseña: losrios733
```
**Problemas:**
- ❌ Contraseña débil
- ❌ Relacionada con el negocio (fácil de adivinar)
- ❌ Sin mayúsculas, números especiales
- ❌ Corta

#### 🟢 BIEN (RECOMENDADO):
```
Usuario: admin_losrios
Contraseña: LoSr!0s#2025$733_SecUR3
```
**Ventajas:**
- ✅ Contraseña fuerte (mayúsculas, minúsculas, números, símbolos)
- ✅ Más de 15 caracteres
- ✅ Difícil de adivinar
- ✅ Única para el servidor

### **B. Sistema Web (PHP)**

**Archivo: `config_seguridad.php` (CREAR)**

```php
<?php
/**
 * CONFIGURACIÓN DE SEGURIDAD
 * ⚠️ NO SUBIR A GITHUB PÚBLICO
 */

// Credenciales por nivel
$CREDENCIALES = [
    'admin' => [
        'nivel' => 'administrador',
        'password_hash' => password_hash('TuContraseñaSegura123!', PASSWORD_DEFAULT),
        'permisos' => ['todo']
    ],
    'cajero1' => [
        'nivel' => 'operador',
        'password_hash' => password_hash('CajeroPass2025!', PASSWORD_DEFAULT),
        'permisos' => ['registrar', 'cobrar', 'imprimir']
    ],
    'contador' => [
        'nivel' => 'lectura',
        'password_hash' => password_hash('ContadorView2025!', PASSWORD_DEFAULT),
        'permisos' => ['ver_reportes', 'exportar']
    ]
];

// NO uses contraseñas en texto plano
// SIEMPRE usa password_hash()
?>
```

---

## 3️⃣ DÓNDE GUARDAR CREDENCIALES

### **✅ CORRECTO:**

1. **Archivo separado NO en Git:**
   ```
   config_seguridad.php
   .env (archivo de entorno)
   ```

2. **Agregar a `.gitignore`:**
   ```
   config_seguridad.php
   .env
   *.secret
   credenciales.txt
   ```

3. **Usar variables de entorno:**
   ```php
   $db_pass = getenv('DB_PASSWORD');
   ```

### **❌ INCORRECTO:**

1. ❌ En el código fuente directamente
2. ❌ En archivos que se suben a GitHub
3. ❌ En comentarios del código
4. ❌ En archivos de texto sin protección
5. ❌ Compartidas por email/WhatsApp sin cifrar

---

## 4️⃣ PROTECCIÓN DEL SERVIDOR

### **A. Windows Server / Servidor local**

```
Usuario: admin_servidor
Contraseña: Cambiarla regularmente (cada 3 meses)
```

**Configuración recomendada:**
1. ✅ Contraseña de al menos 15 caracteres
2. ✅ Cambiar contraseña cada 90 días
3. ✅ Activar firewall
4. ✅ Solo permitir acceso desde IPs conocidas
5. ✅ Habilitar logs de acceso
6. ✅ Backup automático de base de datos

### **B. Acceso remoto (si aplica)**

```ini
# Configuración RDP / SSH
Puerto: NO usar 3389 (cambiar a 33890)
IP permitidas: Solo oficina y casa del admin
Autenticación: Usuario + Contraseña + 2FA
```

---

## 5️⃣ BASE DE DATOS

### **Actual (probablemente):**
```php
$user = 'root';
$pass = ''; // ⚠️ SIN CONTRASEÑA - MUY INSEGURO
```

### **Recomendado:**
```php
$user = 'estacionamiento_user';
$pass = 'Est@c10n@m13nt0#2025!LR';
```

**Cómo crear usuario seguro en MySQL:**
```sql
-- 1. Crear usuario específico (NO usar root)
CREATE USER 'estacionamiento_user'@'localhost' 
IDENTIFIED BY 'Est@c10n@m13nt0#2025!LR';

-- 2. Dar permisos SOLO a la base de datos necesaria
GRANT ALL PRIVILEGES ON estacionamiento.* 
TO 'estacionamiento_user'@'localhost';

-- 3. Aplicar cambios
FLUSH PRIVILEGES;
```

---

## 6️⃣ ARCHIVO DE CONFIGURACIÓN SEGURO

**Crear: `config.private.php`**

```php
<?php
/**
 * CONFIGURACIÓN PRIVADA
 * ⚠️ NO SUBIR A GITHUB
 * ⚠️ MANTENER FUERA DE LA CARPETA PÚBLICA
 */

// Credenciales de Base de Datos
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'estacionamiento_user');
define('DB_PASS', 'TuContraseñaSegura123!');
define('DB_NAME', 'estacionamiento');

// Credenciales del Servidor
define('SERVER_USER', 'admin_servidor');
// NO guardes la contraseña aquí, solo el usuario

// Configuración de sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SESSION_SECRET', 'clave_secreta_aleatoria_' . bin2hex(random_bytes(32)));

// Otras configuraciones sensibles
define('ENCRYPTION_KEY', 'otra_clave_secreta_para_cifrado');
?>
```

**Incluirlo en tus archivos:**
```php
<?php
require_once '../config.private.php'; // Fuera de htdocs

// Usar las constantes
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
?>
```

---

## 7️⃣ CHECKLIST DE SEGURIDAD

### **Sistema Web:**
- [ ] Contraseñas hasheadas (no en texto plano)
- [ ] Archivo de configuración privado
- [ ] .gitignore actualizado
- [ ] Sesiones con timeout
- [ ] Validación de entrada de usuario
- [ ] Protección contra SQL injection
- [ ] Protección contra XSS
- [ ] HTTPS habilitado (certificado SSL)

### **Base de Datos:**
- [ ] Usuario específico (no root)
- [ ] Contraseña fuerte
- [ ] Permisos limitados
- [ ] Backup automático diario
- [ ] Logs de acceso habilitados

### **Servidor:**
- [ ] Contraseña de admin fuerte
- [ ] Firewall activo
- [ ] Actualizaciones automáticas
- [ ] Antivirus actualizado
- [ ] Backup completo semanal

---

## 8️⃣ ROTACIÓN DE CREDENCIALES

### **Calendario recomendado:**

| Credencial | Frecuencia de cambio |
|------------|---------------------|
| Contraseña admin sistema | Cada 3 meses |
| Contraseña servidor | Cada 3 meses |
| Contraseña base de datos | Cada 6 meses |
| Contraseñas empleados | Cada 6 meses o al terminar contrato |

---

## 9️⃣ DOCUMENTACIÓN DE ACCESOS

**Crear archivo: `ACCESOS.private.md`** (NO subir a Git)

```markdown
# DOCUMENTACIÓN DE ACCESOS
Última actualización: [FECHA]

## SERVIDOR
- Nombre: ServidorLosRios
- IP: [TU_IP]
- Usuario: admin_servidor
- Contraseña: [GUARDADA EN GESTOR DE CONTRASEÑAS]
- Última actualización: [FECHA]

## BASE DE DATOS
- Host: localhost
- Puerto: 3306
- Base de datos: estacionamiento
- Usuario: estacionamiento_user
- Contraseña: [GUARDADA EN GESTOR DE CONTRASEÑAS]

## USUARIOS DEL SISTEMA
| Usuario | Nivel | Última actualización |
|---------|-------|---------------------|
| admin | Administrador | 01/01/2025 |
| cajero1 | Operador | 01/01/2025 |
| cajero2 | Operador | 01/01/2025 |
```

---

## 🔟 GESTOR DE CONTRASEÑAS (RECOMENDADO)

En lugar de guardar contraseñas en archivos, usa un gestor:

**Opciones gratuitas:**
- **KeePass** (local, seguro)
- **Bitwarden** (gratis, sincronización)
- **LastPass Free**

**Ventajas:**
- ✅ Contraseñas cifradas
- ✅ Generador de contraseñas fuertes
- ✅ Autocompletar seguro
- ✅ Compartir con equipo de forma segura

---

## 🎯 RESUMEN EJECUTIVO

### **LO QUE DEBES HACER YA:**

1. ✅ Cambiar contraseña del servidor a algo fuerte
2. ✅ Crear usuario específico para la base de datos (no usar root)
3. ✅ Crear archivo `config.private.php` con credenciales
4. ✅ Agregar `config.private.php` a `.gitignore`
5. ✅ Usar `password_hash()` para contraseñas de usuarios
6. ✅ Instalar un gestor de contraseñas

### **LO QUE NO DEBES HACER NUNCA:**

1. ❌ Subir contraseñas a GitHub
2. ❌ Compartir contraseñas por WhatsApp/Email sin cifrar
3. ❌ Usar contraseñas débiles
4. ❌ Usar la misma contraseña para todo
5. ❌ Dejar contraseñas en texto plano en el código
6. ❌ Compartir cuenta de admin con empleados

---

## 📞 CONTACTO DE EMERGENCIA

En caso de brecha de seguridad:
1. Cambiar TODAS las contraseñas inmediatamente
2. Revisar logs de acceso
3. Hacer backup de la base de datos
4. Notificar al equipo
5. Documentar el incidente

---

**Última actualización:** [FECHA DE HOY]
**Próxima revisión:** [3 MESES DESPUÉS]

