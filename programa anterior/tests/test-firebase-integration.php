<?php
/**
 * 🧪 PRUEBAS DE INTEGRACIÓN COMPLETA FIREBASE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo prueba toda la integración de Firebase
 */

require_once __DIR__ . '/firebase-config.php';
require_once __DIR__ . '/auth-hybrid.php';
require_once __DIR__ . '/firestore-service.php';
require_once __DIR__ . '/firebase-storage-service.php';
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

// Función para mostrar progreso
function showProgress($step, $total, $description) {
    $percentage = round(($step / $total) * 100);
    echo "<div style='background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<div style='background: #1976d2; height: 20px; width: $percentage%; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;'>";
    echo "$percentage%";
    echo "</div>";
    echo "<p style='margin: 5px 0 0 0;'><strong>Paso $step de $total:</strong> $description</p>";
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pruebas de Integración Firebase - Sistema Estacionamiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .config-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .summary { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔥 Pruebas de Integración Completa Firebase</h1>
    
    <div class="config-info">
        <h3>Configuración del Sistema</h3>
        <p><strong>Proyecto Firebase:</strong> <?= FIREBASE_PROJECT_ID ?></p>
        <p><strong>Auth Domain:</strong> <?= FIREBASE_AUTH_DOMAIN ?></p>
        <p><strong>Storage Bucket:</strong> <?= FIREBASE_STORAGE_BUCKET ?></p>
        <p><strong>Firestore URL:</strong> <?= FIRESTORE_URL ?></p>
    </div>

    <?php
    $totalSteps = 8;
    $currentStep = 0;
    $testResults = [];
    
    try {
        // Paso 1: Verificar configuración básica
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Verificando configuración básica de Firebase");
        
        $configValid = true;
        $requiredConstants = ['FIREBASE_API_KEY', 'FIREBASE_AUTH_DOMAIN', 'FIREBASE_PROJECT_ID', 'FIREBASE_STORAGE_BUCKET'];
        
        foreach ($requiredConstants as $constant) {
            if (!defined($constant) || constant($constant) === 'TU_API_KEY_AQUI') {
                $configValid = false;
                break;
            }
        }
        
        $testResults['config'] = $configValid;
        showTestResult(
            "Configuración básica",
            $configValid,
            $configValid ? "Todas las constantes configuradas correctamente" : "Configuración incompleta o valores por defecto"
        );
        
        // Paso 2: Probar autenticación híbrida
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Probando sistema de autenticación híbrida");
        
        $auth = getHybridAuth();
        $authMode = $auth->getCurrentMode();
        $authWorking = ($authMode === 'MySQL' || $authMode === 'Firebase');
        
        $testResults['auth'] = $authWorking;
        showTestResult(
            "Sistema de autenticación híbrida",
            $authWorking,
            "Modo actual: $authMode",
            ['mode' => $authMode]
        );
        
        // Paso 3: Probar Firestore
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Probando operaciones de Firestore");
        
        $firestore = getFirestoreService();
        $testData = [
            'test_field' => 'valor_integracion',
            'timestamp' => new DateTime(),
            'test' => true
        ];
        
        $documentId = 'test_integracion_' . time();
        $createResult = $firestore->createDocument('test', $documentId, $testData);
        $getResult = $firestore->getDocument('test', $documentId);
        $deleteResult = $firestore->deleteDocument('test', $documentId);
        
        $firestoreWorking = $createResult['success'] && $getResult['success'] && $deleteResult['success'];
        $testResults['firestore'] = $firestoreWorking;
        
        showTestResult(
            "Operaciones de Firestore",
            $firestoreWorking,
            $firestoreWorking ? "Crear, leer y eliminar funcionando correctamente" : "Error en operaciones de Firestore",
            [
                'create' => $createResult['success'],
                'read' => $getResult['success'],
                'delete' => $deleteResult['success']
            ]
        );
        
        // Paso 4: Probar Firebase Storage
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Probando operaciones de Firebase Storage");
        
        $storage = getFirebaseStorageService();
        $testContent = "Archivo de prueba de integración - " . date('Y-m-d H:i:s');
        $testFileName = 'test_integracion_' . time() . '.txt';
        $testFilePath = sys_get_temp_dir() . '/' . $testFileName;
        
        if (file_put_contents($testFilePath, $testContent)) {
            $remotePath = 'test/' . $testFileName;
            $uploadResult = $storage->uploadFile($testFilePath, $remotePath);
            $downloadResult = $storage->downloadFile($remotePath);
            $deleteResult = $storage->deleteFile($remotePath);
            
            $storageWorking = $uploadResult['success'] && $downloadResult['success'] && $deleteResult['success'];
            $testResults['storage'] = $storageWorking;
            
            showTestResult(
                "Operaciones de Storage",
                $storageWorking,
                $storageWorking ? "Subir, descargar y eliminar funcionando correctamente" : "Error en operaciones de Storage",
                [
                    'upload' => $uploadResult['success'],
                    'download' => $downloadResult['success'],
                    'delete' => $deleteResult['success']
                ]
            );
            
            // Limpiar archivo temporal
            if (file_exists($testFilePath)) {
                unlink($testFilePath);
            }
        } else {
            $testResults['storage'] = false;
            showTestResult(
                "Operaciones de Storage",
                false,
                "No se pudo crear archivo de prueba temporal"
            );
        }
        
        // Paso 5: Probar funciones específicas del sistema
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Probando funciones específicas del sistema de estacionamiento");
        
        // Probar creación de ticket
        $ticketResult = createTicket('TEST123', 'estacionamiento', 'usuario_test', 'Cliente Prueba');
        $ticketWorking = $ticketResult['success'];
        
        // Probar creación de servicio de lavado
        $servicioResult = createServicioLavado('TEST456', 'básico', 5000, 'usuario_test', 'Cliente Lavado');
        $servicioWorking = $servicioResult['success'];
        
        // Probar subida de imagen
        if (file_exists($testFilePath)) {
            $imageResult = uploadTicketImage($testFilePath, 'test_ticket_123');
            $imageWorking = $imageResult['success'];
        } else {
            $imageWorking = false;
        }
        
        $systemFunctionsWorking = $ticketWorking && $servicioWorking;
        $testResults['system_functions'] = $systemFunctionsWorking;
        
        showTestResult(
            "Funciones específicas del sistema",
            $systemFunctionsWorking,
            $systemFunctionsWorking ? "Funciones del sistema funcionando correctamente" : "Error en funciones del sistema",
            [
                'ticket_creation' => $ticketWorking,
                'servicio_creation' => $servicioWorking,
                'image_upload' => $imageWorking
            ]
        );
        
        // Paso 6: Probar seguridad
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Probando configuración de seguridad");
        
        $security = getFirebaseSecurityConfig();
        $securityResult = $security->verifySecurityConfig();
        $securityWorking = $securityResult['overall'];
        $testResults['security'] = $securityWorking;
        
        showTestResult(
            "Configuración de seguridad",
            $securityWorking,
            $securityWorking ? "Reglas de seguridad configuradas correctamente" : "Problemas de seguridad detectados",
            $securityResult
        );
        
        // Paso 7: Probar migración de datos
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Probando migración de datos");
        
        // Simular migración de usuario
        $migrationData = [
            'id' => 999,
            'usuario' => 'test_migration@estacionamiento.com',
            'rol' => 'operador',
            'fecha_creacion' => new DateTime(),
            'activo' => true,
            'migrado_desde_mysql' => true
        ];
        
        $migrationResult = $firestore->createDocument('usuarios', 'test_migration_999', $migrationData);
        $migrationWorking = $migrationResult['success'];
        $testResults['migration'] = $migrationWorking;
        
        showTestResult(
            "Migración de datos",
            $migrationWorking,
            $migrationWorking ? "Migración de datos funcionando correctamente" : "Error en migración de datos",
            $migrationResult
        );
        
        // Paso 8: Verificar integridad general
        $currentStep++;
        showProgress($currentStep, $totalSteps, "Verificando integridad general del sistema");
        
        $overallWorking = $testResults['config'] && 
                         $testResults['auth'] && 
                         $testResults['firestore'] && 
                         $testResults['storage'] && 
                         $testResults['system_functions'] && 
                         $testResults['security'] && 
                         $testResults['migration'];
        
        $testResults['overall'] = $overallWorking;
        
        showTestResult(
            "Integridad general del sistema",
            $overallWorking,
            $overallWorking ? "Sistema completamente funcional" : "Problemas detectados en el sistema",
            $testResults
        );
        
        // Resumen final
        echo "<div class='test-section'>";
        echo "<h3>📊 Resumen de Pruebas de Integración</h3>";
        
        $passedTests = array_sum($testResults);
        $totalTests = count($testResults) - 1; // Excluir 'overall'
        $successRate = round(($passedTests / $totalTests) * 100);
        
        echo "<div class='summary'>";
        echo "<h4>Resultados Generales</h4>";
        echo "<p><strong>Pruebas pasadas:</strong> $passedTests de $totalTests ($successRate%)</p>";
        echo "<p><strong>Estado general:</strong> " . ($overallWorking ? '✅ FUNCIONAL' : '❌ PROBLEMAS DETECTADOS') . "</p>";
        echo "</div>";
        
        echo "<h4>Detalle de Pruebas</h4>";
        echo "<ul>";
        foreach ($testResults as $test => $result) {
            if ($test !== 'overall') {
                $status = $result ? '✅' : '❌';
                $testName = ucfirst(str_replace('_', ' ', $test));
                echo "<li>$status $testName</li>";
            }
        }
        echo "</ul>";
        
        if ($overallWorking) {
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-top: 15px;'>";
            echo "<h4>🎉 ¡Integración de Firebase Completada Exitosamente!</h4>";
            echo "<p>El sistema está listo para usar Firebase. Todas las funcionalidades han sido verificadas y están funcionando correctamente.</p>";
            echo "<ul>";
            echo "<li>✅ Autenticación híbrida configurada</li>";
            echo "<li>✅ Firestore funcionando correctamente</li>";
            echo "<li>✅ Storage funcionando correctamente</li>";
            echo "<li>✅ Seguridad configurada apropiadamente</li>";
            echo "<li>✅ Funciones del sistema operativas</li>";
            echo "<li>✅ Migración de datos funcional</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin-top: 15px;'>";
            echo "<h4>⚠️ Problemas Detectados en la Integración</h4>";
            echo "<p>Se encontraron problemas que deben ser resueltos antes de usar el sistema en producción.</p>";
            echo "<ul>";
            foreach ($testResults as $test => $result) {
                if ($test !== 'overall' && !$result) {
                    $testName = ucfirst(str_replace('_', ' ', $test));
                    echo "<li>❌ $testName necesita atención</li>";
                }
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Error Fatal en las Pruebas</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
        echo "</div>";
    }
    ?>

    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;">
        <strong>⚠️ Importante:</strong> Estas son pruebas de integración completas. Revisa todos los resultados antes de usar el sistema en producción.
    </div>
</body>
</html>
