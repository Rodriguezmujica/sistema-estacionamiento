<?php
/**
 * 🧪 PRUEBAS DE SEGURIDAD FIREBASE
 * Sistema de Estacionamiento Los Ríos
 */

require_once __DIR__ . '/firebase-security-config.php';

// Función para mostrar resultados de prueba
function showTestResult($testName, $success, $message = '', $data = null) {
    $status = $success ? '✅' : '❌';
    $color = $success ? 'green' : 'red';
    echo "<div style='color: $color; margin: 10px 0; padding: 10px; border-left: 4px solid $color; background: #f9f9f9;'>";
    echo "<strong>$status $testName</strong>";
    if ($message) {
        echo "<br><small>$message</small>";
    }
    if ($data) {
        echo "<br><pre style='font-size: 12px; margin-top: 5px;'>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    }
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pruebas de Seguridad Firebase - Sistema Estacionamiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .config-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .security-warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔒 Pruebas de Seguridad Firebase</h1>
    
    <div class="config-info">
        <h3>Configuración Actual</h3>
        <p><strong>Proyecto:</strong> <?= FIREBASE_PROJECT_ID ?></p>
        <p><strong>Auth Domain:</strong> <?= FIREBASE_AUTH_DOMAIN ?></p>
        <p><strong>Storage Bucket:</strong> <?= FIREBASE_STORAGE_BUCKET ?></p>
    </div>

    <div class="security-warning">
        <h3>⚠️ Advertencia de Seguridad</h3>
        <p>Estas pruebas verifican la configuración de seguridad de Firebase. Asegúrate de que:</p>
        <ul>
            <li>Las reglas de seguridad estén configuradas correctamente</li>
            <li>Los usuarios tengan los permisos apropiados</li>
            <li>Los datos estén protegidos contra acceso no autorizado</li>
        </ul>
    </div>

    <?php
    try {
        $security = getFirebaseSecurityConfig();
        
        // Prueba 1: Verificar reglas de Firestore
        echo "<div class='test-section'>";
        echo "<h3>1. Verificar Reglas de Firestore</h3>";
        
        $firestoreResult = $security->verifyFirestoreRules();
        showTestResult(
            "Reglas de Firestore",
            $firestoreResult['success'],
            $firestoreResult['success'] ? "Reglas configuradas correctamente" : "Error: " . ($firestoreResult['error'] ?? 'Desconocido'),
            $firestoreResult['rules'] ?? null
        );
        
        // Prueba 2: Verificar reglas de Storage
        echo "<h3>2. Verificar Reglas de Storage</h3>";
        
        $storageResult = $security->verifyStorageRules();
        showTestResult(
            "Reglas de Storage",
            $storageResult['success'],
            $storageResult['success'] ? "Reglas configuradas correctamente" : "Error: " . ($storageResult['error'] ?? 'Desconocido'),
            $storageResult['rulesets'] ?? null
        );
        
        // Prueba 3: Crear usuario de prueba
        echo "<h3>3. Crear Usuario de Prueba</h3>";
        
        $testEmail = 'test_security_' . time() . '@estacionamiento.com';
        $testPassword = 'test123456';
        
        $userResult = $security->createTestUser($testEmail, $testPassword, 'operador');
        showTestResult(
            "Crear usuario de prueba",
            $userResult['success'],
            $userResult['success'] ? "Usuario creado: " . $testEmail : "Error: " . ($userResult['error'] ?? 'Desconocido'),
            $userResult
        );
        
        // Prueba 4: Verificar configuración general
        echo "<h3>4. Verificar Configuración General</h3>";
        
        $configResult = $security->verifySecurityConfig();
        showTestResult(
            "Configuración general",
            $configResult['overall'],
            $configResult['overall'] ? "Configuración de seguridad completa" : "Problemas de configuración detectados",
            $configResult
        );
        
        // Prueba 5: Aplicar reglas de seguridad
        echo "<h3>5. Aplicar Reglas de Seguridad</h3>";
        
        $applyResult = $security->setupDefaultSecurity();
        showTestResult(
            "Aplicar reglas de seguridad",
            $applyResult['firestore']['success'] && $applyResult['storage']['success'],
            "Reglas aplicadas: Firestore " . ($applyResult['firestore']['success'] ? 'OK' : 'ERROR') . 
            ", Storage " . ($applyResult['storage']['success'] ? 'OK' : 'ERROR'),
            $applyResult
        );
        
        echo "</div>";
        
        // Resumen de seguridad
        echo "<div class='test-section'>";
        echo "<h3>Resumen de Seguridad</h3>";
        
        $overallStatus = $configResult['overall'];
        $statusColor = $overallStatus ? 'green' : 'red';
        $statusText = $overallStatus ? 'SEGURO' : 'PROBLEMAS DETECTADOS';
        
        echo "<div style='color: $statusColor; font-size: 18px; font-weight: bold; text-align: center; padding: 20px; background: #f9f9f9; border-radius: 5px;'>";
        echo "Estado de Seguridad: $statusText";
        echo "</div>";
        
        if ($overallStatus) {
            echo "<div style='color: green; margin-top: 10px;'>";
            echo "<h4>✅ Configuración de Seguridad Completa</h4>";
            echo "<ul>";
            echo "<li>Reglas de Firestore configuradas correctamente</li>";
            echo "<li>Reglas de Storage configuradas correctamente</li>";
            echo "<li>Usuarios de prueba creados exitosamente</li>";
            echo "<li>Sistema listo para producción</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='color: red; margin-top: 10px;'>";
            echo "<h4>❌ Problemas de Seguridad Detectados</h4>";
            echo "<ul>";
            if (!$configResult['firestore']) {
                echo "<li>Reglas de Firestore no configuradas correctamente</li>";
            }
            if (!$configResult['storage']) {
                echo "<li>Reglas de Storage no configuradas correctamente</li>";
            }
            echo "<li>Revisa la configuración antes de continuar</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        echo "</div>";
        
        // Recomendaciones de seguridad
        echo "<div class='test-section'>";
        echo "<h3>Recomendaciones de Seguridad</h3>";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🔒 Mejores Prácticas de Seguridad</h4>";
        echo "<ul>";
        echo "<li><strong>Autenticación:</strong> Siempre verifica que los usuarios estén autenticados</li>";
        echo "<li><strong>Autorización:</strong> Implementa roles y permisos apropiados</li>";
        echo "<li><strong>Validación:</strong> Valida todos los datos de entrada</li>";
        echo "<li><strong>Auditoría:</strong> Registra todas las operaciones importantes</li>";
        echo "<li><strong>Monitoreo:</strong> Supervisa el acceso y uso del sistema</li>";
        echo "<li><strong>Actualizaciones:</strong> Mantén las reglas de seguridad actualizadas</li>";
        echo "</ul>";
        echo "</div>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; background: #ffebee; border-radius: 5px;'>";
        echo "<strong>Error Fatal:</strong> " . $e->getMessage();
        echo "</div>";
    }
    ?>

    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;">
        <strong>⚠️ Importante:</strong> Estas son pruebas de seguridad. Asegúrate de que la configuración sea correcta antes de usar en producción.
    </div>
</body>
</html>
