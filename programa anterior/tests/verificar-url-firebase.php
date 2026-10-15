<?php
/**
 * Verificar URL de Firebase
 * Muestra la información completa de tu proyecto Firebase
 */

require_once 'firebase-config.php';

echo "<h2>🔥 Información de tu Proyecto Firebase</h2>";

// Obtener configuración Firebase
$firebaseConfig = [
    'apiKey' => 'AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg',
    'authDomain' => 'sistemaestacionamiento-46735.firebaseapp.com',
    'databaseURL' => 'https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com',
    'projectId' => 'sistemaestacionamiento-46735',
    'storageBucket' => 'sistemaestacionamiento-46735.firebasestorage.app',
    'messagingSenderId' => '570161231939',
    'appId' => '1:570161231939:web:50a5f88fcd65e98fa03cf6'
];

echo "<h3>📋 Configuración para Haulmer:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>URL del Webhook:</strong><br>";
echo "<code style='background: #e9ecef; padding: 5px; border-radius: 3px;'>" . $firebaseConfig['databaseURL'] . "/tuu_webhook_notifications</code><br><br>";

echo "<strong>Método HTTP:</strong> POST<br>";
echo "<strong>Content-Type:</strong> application/json<br>";
echo "<strong>Autenticación:</strong> Ninguna (pública)<br>";
echo "<strong>Notificaciones:</strong> success, failed, pending<br>";
echo "</div>";

echo "<h3>🔧 Configuración JSON para Haulmer:</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo json_encode([
    'webhook_url' => $firebaseConfig['databaseURL'] . '/tuu_webhook_notifications',
    'method' => 'POST',
    'content_type' => 'application/json',
    'authentication' => 'none',
    'notifications' => ['success', 'failed', 'pending'],
    'project_id' => $firebaseConfig['projectId'],
    'database_url' => $firebaseConfig['databaseURL']
], JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h3>📱 Formato de Datos Esperado:</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo json_encode([
    'transaction_id' => 'EST-123456-1234567890',
    'status' => 'success',
    'amount' => 2000,
    'patente' => 'ABC123',
    'fecha_pago' => '2025-10-22 15:30:00',
    'timestamp' => time()
], JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h3>✅ Verificación de Conexión:</h3>";

try {
    // Verificar si Firebase está configurado
    if (isset($database) && $database !== null) {
        // Probar conexión a Firebase
        $testData = [
            'test' => true,
            'timestamp' => time(),
            'message' => 'Conexión exitosa desde PHP'
        ];
        
        $firebase_ref = $database->getReference('test_connection/' . uniqid());
        $firebase_ref->set($testData);
        
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Conexión a Firebase exitosa</strong><br>";
        echo "Tu URL está funcionando correctamente";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ <strong>Firebase no inicializado</strong><br>";
        echo "La URL es correcta, pero Firebase no está configurado en este archivo";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>📞 Mensaje para Haulmer:</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Asunto:</strong> Configuración de Webhook TUU usando Firebase<br><br>";
echo "<strong>Mensaje:</strong><br>";
echo "Hola equipo de Haulmer,<br><br>";
echo "Necesito configurar el webhook de TUU para mi sistema de estacionamiento local.<br>";
echo "Como no tengo dominio público, propongo usar Firebase como intermediario.<br><br>";
echo "<strong>Configuración solicitada:</strong><br>";
echo "• Firebase URL: <code>" . $firebaseConfig['databaseURL'] . "/tuu_webhook_notifications</code><br>";
echo "• Método: POST<br>";
echo "• Formato: JSON<br>";
echo "• Notificaciones: pagos exitosos y fallidos<br><br>";
echo "¿Es posible configurar TUU para que envíe notificaciones directamente a Firebase?<br><br>";
echo "Gracias por su ayuda.";
echo "</div>";
?>
