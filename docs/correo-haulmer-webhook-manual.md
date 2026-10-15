# 📧 Correo para Haulmer - Solicitud de Configuración de Webhook

## Versión Completa

**ASUNTO:** Solicitud de Configuración de Webhook TUU - Sistema de Estacionamiento

---

Estimado/a [Nombre del Contacto / Equipo de Integración],

Espero se encuentren bien. Me dirijo a ustedes para solicitar la configuración del webhook de notificaciones de pagos TUU para nuestro sistema de estacionamiento.

### INFORMACIÓN DEL COMERCIO:
- **Nombre del Comercio:** INVERSIONES ROSNER
- **Proyecto ID TUU:** 5bd29bb6-d922-4eac-ae36-37635b0b8cd9
- **Actividad:** Estacionamiento y Servicios de Lavado
- **Sistema:** Sistema de Gestión de Estacionamiento (Local)

### CONTEXTO:
Como mencionaron en el foro oficial, existe un webhook que notifica pagos exitosos y fallidos, el cual requiere configuración manual por el equipo de integradores.

Nuestro sistema opera en una red local sin dominio público, lo que dificulta la recepción directa de webhooks mediante HTTP tradicional. Por esta razón, proponemos utilizar Firebase Real-time Database como intermediario para recibir las notificaciones de pagos.

### CONFIGURACIÓN SOLICITADA:

**URL del Webhook:**
```
https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com/tuu_webhook_notifications.json
```

- **Método HTTP:** POST
- **Content-Type:** application/json
- **Autenticación:** No requiere (Firebase Real-time Database público para este endpoint)

**Notificaciones requeridas:**
- Pagos exitosos (success/approved/completed)
- Pagos fallidos (failed/rejected)
- Pagos pendientes (pending) - opcional pero recomendado

### FORMATO DE DATOS ESPERADO:

Nuestro sistema espera recibir las notificaciones en el siguiente formato:

```json
{
    "transaction_id": "EST-123456-1234567890",
    "status": "success",
    "amount": 2000,
    "patente": "ABC123",
    "fecha_pago": "2025-11-07 15:30:00",
    "timestamp": 1737384600,
    "comercio_id": "5bd29bb6-d922-4eac-ae36-37635b0b8cd9"
}
```

**CAMPOS REQUERIDOS:**
- `transaction_id`: ID único de la transacción TUU
- `status`: Estado del pago (success, failed, pending, approved, rejected, completed)
- `amount`: Monto de la transacción (número)
- `fecha_pago`: Fecha y hora del pago (formato: YYYY-MM-DD HH:MM:SS)

**CAMPOS OPCIONALES PERO RECOMENDADOS:**
- `patente`: Patente del vehículo (si está disponible)
- `timestamp`: Timestamp Unix de la transacción
- `comercio_id`: ID del comercio/proyecto TUU
- `message`: Mensaje descriptivo del estado
- `payment_method`: Método de pago utilizado

### CÓMO FUNCIONA LA INTEGRACIÓN:

1. Cliente realiza pago en TUU
2. TUU procesa el pago y genera transacción
3. TUU envía notificación a Firebase Real-time Database
4. Nuestro sistema local detecta la notificación en Firebase
5. Sistema procesa el pago y actualiza el estado del ticket
6. Se registra la salida del vehículo automáticamente (si el pago fue exitoso)

### VENTAJAS DE ESTA ARQUITECTURA:

- No requiere dominio público ni certificados SSL
- Funciona en red local sin exposición a internet
- Notificaciones en tiempo real mediante Firebase
- Escalable y confiable
- Permite sincronización entre múltiples sistemas locales

### VERIFICACIÓN:

Hemos verificado que Firebase Real-time Database está funcionando correctamente y puede recibir datos en la URL proporcionada. El sistema está listo para recibir las notificaciones una vez configurado el webhook.

### PREGUNTAS:

1. ¿Es posible configurar TUU para que envíe las notificaciones de webhook directamente a Firebase Real-time Database en la URL proporcionada?

2. Si Firebase Real-time Database no es compatible directamente, ¿existen otras alternativas que puedan sugerir para sistemas locales sin dominio público?

3. ¿Qué información adicional necesitan para proceder con la configuración?

4. ¿Cuál es el tiempo estimado para la configuración del webhook?

5. ¿Requieren alguna prueba o validación adicional antes de activar el webhook?

### INFORMACIÓN ADICIONAL:

- Firebase Project ID: sistemaestacionamiento-46735
- Firebase API Key: AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg
- Sistema operativo: Windows/Linux (híbrido)
- Base de datos local: MySQL
- Lenguaje del sistema: PHP y JavaScript

Estamos disponibles para coordinar la configuración y realizar las pruebas necesarias. Por favor, indíquennos cómo proceder.

Agradecemos de antemano su atención y quedamos a la espera de su respuesta.

---

Saludos cordiales,

**[Tu nombre]**  
[Tu cargo]  
[Nombre del negocio]  
[Email de contacto]  
[Teléfono de contacto]

---

## Versión Corta

**ASUNTO:** Solicitud de Configuración de Webhook TUU

Estimado/a [Nombre],

Solicito la configuración del webhook de notificaciones de pagos TUU para nuestro sistema de estacionamiento.

**INFORMACIÓN:**
- Comercio: INVERSIONES ROSNER
- Proyecto ID TUU: 5bd29bb6-d922-4eac-ae36-37635b0b8cd9

**CONFIGURACIÓN SOLICITADA:**
- URL del Webhook: `https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com/tuu_webhook_notifications.json`
- Método: POST
- Content-Type: application/json

**CONTEXTO:**
Nuestro sistema opera en red local sin dominio público, por lo que proponemos usar Firebase Real-time Database como intermediario. Hemos verificado que Firebase está funcionando correctamente y listo para recibir notificaciones.

**FORMATO ESPERADO:**
```json
{
    "transaction_id": "EST-123456-1234567890",
    "status": "success",
    "amount": 2000,
    "patente": "ABC123",
    "fecha_pago": "2025-11-07 15:30:00",
    "timestamp": 1737384600
}
```

**PREGUNTA:**
¿Es posible configurar TUU para enviar notificaciones a Firebase Real-time Database? Si no es compatible, ¿qué alternativas sugieren para sistemas locales?

Estamos disponibles para coordinar pruebas. ¿Qué información adicional necesitan?

Gracias por su atención.

Saludos,  
[Tu nombre]  
[Contacto]

