<?php
/**
 * 🧪 PRUEBAS DE FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo permite probar la configuración de Firebase
 */

require_once __DIR__ . '/firebase-config.php';
require_once __DIR__ . '/auth-hybrid.php';

// Función para mostrar resultados de prueba
function showTestResult($testName, $success, $message = '') {
    $status = $success ? '✅' : '❌';
    $color = $success ? 'green' : 'red';
    echo "<div style='color: $color; margin: 10px 0;'>";
    echo "<strong>$status $testName</strong>";
    if ($message) {
        echo "<br><small>$message</small>";
    }
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas de Firebase - Sistema Estacionamiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .config-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>🔥 Pruebas de Configuración de Firebase</h1>
    
    <div class="config-info">
        <h3>Configuración Actual</h3>
        <p><strong>Proyecto:</strong> <?= FIREBASE_PROJECT_ID ?></p>
        <p><strong>Auth Domain:</strong> <?= FIREBASE_AUTH_DOMAIN ?></p>
        <p><strong>Storage Bucket:</strong> <?= FIREBASE_STORAGE_BUCKET ?></p>
    </div>

    <div class="test-section">
        <h3>1. Verificación de Configuración</h3>
        <?php
        $configValid = true;
        
        // Verificar que las constantes estén definidas
        $requiredConstants = [
            'FIREBASE_API_KEY',
            'FIREBASE_AUTH_DOMAIN', 
            'FIREBASE_PROJECT_ID',
            'FIREBASE_STORAGE_BUCKET'
        ];
        
        foreach ($requiredConstants as $constant) {
            if (defined($constant) && constant($constant) !== 'TU_API_KEY_AQUI') {
                showTestResult("Constante $constant", true, constant($constant));
            } else {
                showTestResult("Constante $constant", false, "No configurada o valor por defecto");
                $configValid = false;
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h3>2. Prueba de Conexión a Firebase</h3>
        <?php
        if ($configValid) {
            // Probar conexión básica
            $testUrl = FIREBASE_BASE_URL;
            $response = makeFirebaseRequest($testUrl);
            
            if ($response['status'] === 200) {
                showTestResult("Conexión a Firebase", true, "Conexión exitosa");
            } else {
                showTestResult("Conexión a Firebase", false, "Error: " . $response['status']);
            }
        } else {
            showTestResult("Conexión a Firebase", false, "Configuración incompleta");
        }
        ?>
    </div>

    <div class="test-section">
        <h3>3. Prueba de Autenticación Híbrida</h3>
        <?php
        try {
            $auth = getHybridAuth();
            $currentMode = $auth->getCurrentMode();
            showTestResult("Sistema de Autenticación Híbrido", true, "Modo actual: $currentMode");
        } catch (Exception $e) {
            showTestResult("Sistema de Autenticación Híbrido", false, "Error: " . $e->getMessage());
        }
        ?>
    </div>

    <div class="test-section">
        <h3>4. Prueba de Firestore (Crear Documento de Prueba)</h3>
        <?php
        if ($configValid) {
            $testData = [
                'fields' => [
                    'test' => ['stringValue' => 'prueba'],
                    'timestamp' => ['timestampValue' => date('c')]
                ]
            ];
            
            $response = createFirestoreDocument('test', $testData);
            
            if ($response['status'] === 200) {
                showTestResult("Crear documento en Firestore", true, "Documento de prueba creado");
            } else {
                showTestResult("Crear documento en Firestore", false, "Error: " . json_encode($response));
            }
        } else {
            showTestResult("Crear documento en Firestore", false, "Configuración incompleta");
        }
        ?>
    </div>

    <div class="test-section">
        <h3>5. Instrucciones para Completar la Configuración</h3>
        <ol>
            <li>Ve a <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a></li>
            <li>Crea un nuevo proyecto o selecciona uno existente</li>
            <li>Habilita Authentication (Email/Password)</li>
            <li>Habilita Firestore Database</li>
            <li>Habilita Storage</li>
            <li>Obtén la configuración del proyecto</li>
            <li>Actualiza los archivos <code>firebase-config.js</code> y <code>firebase-config.php</code></li>
            <li>Recarga esta página para verificar la configuración</li>
        </ol>
    </div>

    <div class="test-section">
        <h3>6. Próximos Pasos</h3>
        <ul>
            <li>✅ Configuración básica de Firebase</li>
            <li>🔄 Migrar sistema de autenticación</li>
            <li>🔄 Migrar base de datos MySQL a Firestore</li>
            <li>🔄 Implementar Storage para archivos</li>
            <li>🔄 Configurar reglas de seguridad</li>
            <li>🔄 Probar integración completa</li>
        </ul>
    </div>

    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;">
        <strong>⚠️ Importante:</strong> Esta es una versión de prueba. No uses credenciales reales en este entorno de desarrollo.
    </div>
</body>
</html>
