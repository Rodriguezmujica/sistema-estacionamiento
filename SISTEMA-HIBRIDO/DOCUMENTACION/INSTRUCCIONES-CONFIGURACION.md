# 🔧 Instrucciones de Configuración - Sistema Híbrido

## 🎯 Configuración Completada

¡Excelente! Ya tienes las credenciales reales de Firebase configuradas. Ahora sigue estos pasos para poner en funcionamiento el sistema híbrido.

## 📋 Pasos de Configuración

### 1. ✅ Verificar Configuración de Firebase

**Archivo actualizado:** `firebase-config.js`
- ✅ API Key configurada
- ✅ Project ID configurado
- ✅ Auth Domain configurado
- ✅ Storage Bucket configurado

### 2. 🧪 Probar la Configuración

**Abre en tu navegador:**
```
http://localhost/sistemaEstacionamiento/test-credenciales-reales.html
```

**Verifica que:**
- ✅ Firebase se inicialice correctamente
- ✅ Se detecte el tipo de PC (PC1_ANTIX o PC2_WINDOWS7)
- ✅ Firestore esté accesible
- ✅ Storage esté configurado
- ✅ Auth esté funcionando

### 3. 🔥 Configurar Firebase Console

**Ve a:** [Firebase Console](https://console.firebase.google.com/)

**Configura los siguientes servicios:**

#### 3.1 Authentication
1. Ve a "Authentication" → "Sign-in method"
2. Habilita "Email/Password"
3. Guarda los cambios

#### 3.2 Firestore Database
1. Ve a "Firestore Database"
2. Crea la base de datos
3. Selecciona "Comenzar en modo de prueba"
4. Elige ubicación: `southamerica-east1` (Chile)

#### 3.3 Storage
1. Ve a "Storage"
2. Haz clic en "Comenzar"
3. Acepta las reglas de seguridad por defecto
4. Elige la misma ubicación que Firestore

### 4. 📁 Instalar Archivos en Ambas PC

#### PC 1 (Antix - Servidor Principal)
```bash
# Copiar archivos a la carpeta del sistema
cp firebase-config.js /ruta/del/sistema/
cp firebase-sync.js /ruta/del/sistema/
cp pc-detector.js /ruta/del/sistema/
cp sistema-hibrido.js /ruta/del/sistema/
cp config-sistema-hibrido.js /ruta/del/sistema/
```

#### PC 2 (Windows 7 - PC de Producción)
```bash
# Copiar los mismos archivos
cp firebase-config.js /ruta/del/sistema/
cp firebase-sync.js /ruta/del/sistema/
cp pc-detector.js /ruta/del/sistema/
cp printing-manager.js /ruta/del/sistema/
cp sistema-hibrido.js /ruta/del/sistema/
cp config-sistema-hibrido.js /ruta/del/sistema/
```

### 5. 🔗 Integrar con el Sistema Existente

#### 5.1 Modificar index.php
Agrega al final del `<head>`:
```html
<script type="module" src="sistema-hibrido.js"></script>
```

#### 5.2 Modificar login.php
Agrega al final del `<head>`:
```html
<script type="module" src="sistema-hibrido.js"></script>
```

### 6. 🖨️ Configurar Impresión (Solo PC2)

#### 6.1 Crear archivo de verificación de impresora
**Crear:** `api/check-printer.php`
```php
<?php
header('Content-Type: application/json');

// Verificar si hay impresoras disponibles
$printers = shell_exec('wmic printer get name');
$hasPrinters = !empty(trim($printers));

echo json_encode([
    'available' => $hasPrinters,
    'printers' => $hasPrinters ? explode("\n", trim($printers)) : []
]);
?>
```

#### 6.2 Crear archivo de impresión
**Crear:** `api/print.php`
```php
<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'];
$type = $input['type'];

// Guardar en archivo temporal
$filename = 'temp_print_' . time() . '.txt';
file_put_contents($filename, $data);

// Imprimir
$result = shell_exec("print /D:USB001 \"$filename\"");

// Limpiar archivo temporal
unlink($filename);

echo json_encode([
    'success' => true,
    'result' => $result
]);
?>
```

### 7. 📊 Crear APIs de Sincronización

#### 7.1 API para obtener tickets
**Crear:** `api/get-tickets.php`
```php
<?php
require_once '../conexion.php';
header('Content-Type: application/json');

$sql = "SELECT * FROM tickets ORDER BY fecha_ingreso DESC";
$result = $conn->query($sql);

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}

echo json_encode(['tickets' => $tickets]);
?>
```

#### 7.2 API para obtener servicios de lavado
**Crear:** `api/get-servicios-lavado.php`
```php
<?php
require_once '../conexion.php';
header('Content-Type: application/json');

$sql = "SELECT * FROM servicios_lavado ORDER BY fecha_servicio DESC";
$result = $conn->query($sql);

$servicios = [];
while ($row = $result->fetch_assoc()) {
    $servicios[] = $row;
}

echo json_encode(['servicios' => $servicios]);
?>
```

#### 7.3 API para obtener usuarios
**Crear:** `api/get-usuarios.php`
```php
<?php
require_once '../conexion.php';
header('Content-Type: application/json');

$sql = "SELECT id, usuario, rol FROM usuarios";
$result = $conn->query($sql);

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

echo json_encode(['usuarios' => $usuarios]);
?>
```

### 8. 🧪 Probar el Sistema

#### 8.1 Prueba Básica
1. Abre `test-credenciales-reales.html` en ambas PC
2. Verifica que se detecte correctamente el tipo de PC
3. Verifica que Firebase esté funcionando

#### 8.2 Prueba de Sincronización
1. Registra un ticket en PC1
2. Verifica que aparezca en PC2
3. Registra un ticket en PC2
4. Verifica que aparezca en PC1

#### 8.3 Prueba de Impresión
1. En PC2, intenta imprimir un ticket
2. Verifica que la impresora funcione
3. En PC1, verifica que no pueda imprimir

#### 8.4 Prueba de Corte de Luz
1. Simula que PC1 se apaga
2. Verifica que PC2 tome el control
3. Registra tickets en PC2
4. Enciende PC1 y verifica sincronización

### 9. 🔧 Configuración Avanzada

#### 9.1 Configurar Reglas de Seguridad
**En Firebase Console:**
1. Ve a "Firestore Database" → "Rules"
2. Copia el contenido de `firebase-security-rules/firestore.rules`
3. Pega y publica las reglas

#### 9.2 Configurar Storage Rules
**En Firebase Console:**
1. Ve a "Storage" → "Rules"
2. Copia el contenido de `firebase-security-rules/storage.rules`
3. Pega y publica las reglas

### 10. 📱 Monitoreo del Sistema

#### 10.1 Interfaz de Monitoreo
Abre `test-sistema-hibrido.html` para:
- Ver estado del sistema en tiempo real
- Probar funcionalidades
- Ver logs del sistema
- Ejecutar diagnósticos

#### 10.2 Logs del Sistema
Los logs se muestran en:
- Consola del navegador
- Interfaz de monitoreo
- Firebase (si está configurado)

## 🚨 Troubleshooting

### Problema: Firebase no se inicializa
**Solución:**
1. Verifica que las credenciales sean correctas
2. Verifica que tengas conexión a internet
3. Revisa la consola del navegador para errores

### Problema: No se detecta el tipo de PC
**Solución:**
1. Verifica que el user agent sea correcto
2. Modifica la función `detectPC()` en `firebase-config.js`
3. Agrega tu hostname específico

### Problema: Sincronización no funciona
**Solución:**
1. Verifica que las APIs estén funcionando
2. Verifica que Firestore esté configurado
3. Revisa los logs del sistema

### Problema: Impresión no funciona
**Solución:**
1. Verifica que sea PC2 (Windows 7)
2. Verifica que la impresora esté conectada
3. Verifica que los archivos PHP estén creados

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs del sistema
2. Ejecuta las pruebas de diagnóstico
3. Verifica la configuración de Firebase
4. Consulta la documentación

---

**¡El sistema híbrido está listo para usar!** 🎉

Sigue estos pasos y tendrás un sistema robusto que funciona en ambas PC con sincronización automática.
