# Servicio de Impresión Local - Star BSC10

Servicio Node.js para manejar impresión térmica en impresora Star BSC10 desde aplicación web cliente-servidor.

## 📋 Requisitos

- Node.js v16 o superior
- Windows 10/11
- Driver de impresora Star BSC10 instalado
- Impresora conectada vía USB

## 🚀 Instalación

### 1. Instalar Node.js
```bash
# Descargar desde https://nodejs.org/
# Verificar instalación:
node --version
npm --version
```

### 2. Instalar dependencias
```bash
cd print-service
npm install
```

### 3. Instalar dependencia adicional para servicio de Windows (opcional)
```bash
npm install -g node-windows
npm link node-windows
```

## ▶️ Uso

### Modo Desarrollo (manual)
```bash
npm start
# o con auto-reload:
npm run dev
```

El servicio estará disponible en `http://localhost:3000`

### Modo Producción (como servicio de Windows)

**Instalar servicio (ejecutar como Administrador):**
```bash
node install-service.js
```

El servicio se instalará como "PrintServiceEstacionamiento" y se iniciará automáticamente.

**Desinstalar servicio:**
```bash
node uninstall-service.js
```

**Administrar servicio:**
1. Abrir `services.msc`
2. Buscar "PrintServiceEstacionamiento"
3. Iniciar/Detener/Reiniciar según necesites

## 🧪 Pruebas

```bash
# Probar que todo funciona
npm test

# O manualmente:
node test-print.js
```

## 📡 API Endpoints

### GET /
Verifica estado del servicio
```json
{
  "status": "online",
  "message": "Servicio de impresión activo",
  "version": "1.0.0"
}
```

### GET /printers
Lista impresoras USB conectadas
```json
{
  "success": true,
  "message": "Se encontraron 1 impresora(s)",
  "printers": [
    {
      "id": 0,
      "vendorId": 1305,
      "productId": 0003
    }
  ]
}
```

### POST /print
Imprime un ticket

**Body:**
```json
{
  "tipo": "ingreso|salida|lavado|cierre_caja|test",
  "datos": {
    // Datos específicos según el tipo
  }
}
```

**Ejemplos:**

#### Ticket de Ingreso
```json
{
  "tipo": "ingreso",
  "datos": {
    "ticket_id": "12345",
    "patente": "ABC123",
    "tipo_vehiculo": "Auto",
    "fecha_ingreso": "2025-10-13",
    "hora_ingreso": "10:30:00"
  }
}
```

#### Ticket de Salida
```json
{
  "tipo": "salida",
  "datos": {
    "ticket_id": "12345",
    "patente": "ABC123",
    "fecha_ingreso": "2025-10-13 10:30:00",
    "fecha_salida": "2025-10-13 14:45:00",
    "tiempo_estadia": "4h 15min",
    "monto": "500",
    "metodo_pago": "Efectivo"
  }
}
```

#### Test
```json
{
  "tipo": "test",
  "datos": {
    "mensaje": "Prueba de impresión"
  }
}
```

## 🔧 Integración con tu Sistema PHP

### Opción 1: Desde JavaScript (Recomendado)

```javascript
async function imprimirTicket(tipo, datos) {
    try {
        const response = await fetch('http://localhost:3000/print', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ tipo, datos })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Ticket impreso');
        } else {
            console.error('❌ Error:', result.message);
        }
    } catch (error) {
        console.error('❌ No se pudo conectar al servicio de impresión');
    }
}

// Ejemplo de uso
imprimirTicket('ingreso', {
    ticket_id: '12345',
    patente: 'ABC123',
    tipo_vehiculo: 'Auto',
    fecha_ingreso: '2025-10-13',
    hora_ingreso: '10:30:00'
});
```

### Opción 2: Desde PHP (si el servidor también es local)

```php
<?php
function imprimirTicket($tipo, $datos) {
    $url = 'http://localhost:3000/print';
    
    $postData = json_encode([
        'tipo' => $tipo,
        'datos' => $datos
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    
    return ['success' => false, 'message' => 'Error de conexión'];
}
?>
```

## 🔍 Solución de Problemas

### Impresora no detectada
1. Verificar que esté conectada y encendida
2. Verificar driver instalado en Windows
3. Revisar que aparezca en "Dispositivos e impresoras"
4. Probar desconectar y reconectar USB

### Error "LIBUSB_ERROR_ACCESS"
- Windows está bloqueando el acceso USB
- Ejecutar Node.js como Administrador (solo para pruebas)
- O instalar como servicio de Windows (recomendado)

### Error "Cannot find module"
```bash
npm install
```

### Puerto 3000 ya en uso
Editar `server.js` y cambiar:
```javascript
const PORT = 3001; // Cambiar puerto
```

## 📝 Logs

Los logs del servicio se guardan en:
```
C:\ProgramData\node-windows\PrintServiceEstacionamiento\
```

## 🔐 Seguridad

⚠️ **IMPORTANTE:** Este servicio escucha en localhost:3000 sin autenticación.
- Solo es accesible desde la misma PC
- No exponer a internet sin implementar autenticación
- Para uso en red local, implementar token de autenticación

## 📞 Soporte

Si tienes problemas:
1. Verifica los logs del servicio
2. Ejecuta `npm test` para diagnóstico
3. Revisa la conexión USB de la impresora

