<?php
/**
 * 🧪 PRUEBAS DE FIRESTORE
 * Sistema de Estacionamiento Los Ríos
 */

require_once __DIR__ . '/firestore-service.php';

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
    <title>Pruebas de Firestore - Sistema Estacionamiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .config-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔥 Pruebas de Firestore</h1>
    
    <div class="config-info">
        <h3>Configuración Actual</h3>
        <p><strong>Proyecto:</strong> <?= FIREBASE_PROJECT_ID ?></p>
        <p><strong>Base URL:</strong> <?= FIRESTORE_URL ?></p>
    </div>

    <?php
    try {
        $firestore = getFirestoreService();
        
        // Prueba 1: Crear documento de prueba
        echo "<div class='test-section'>";
        echo "<h3>1. Crear Documento de Prueba</h3>";
        
        $testData = [
            'test_field' => 'valor de prueba',
            'timestamp' => new DateTime(),
            'numero' => 123,
            'activo' => true
        ];
        
        $result = $firestore->createDocument('test', 'documento_prueba_' . time(), $testData);
        showTestResult(
            "Crear documento",
            $result['success'],
            $result['success'] ? "Documento creado exitosamente" : "Error: " . ($result['error'] ?? 'Desconocido'),
            $result['data'] ?? null
        );
        
        // Prueba 2: Obtener documento
        echo "<h3>2. Obtener Documento</h3>";
        
        $documentId = 'documento_prueba_' . time();
        $result = $firestore->getDocument('test', $documentId);
        showTestResult(
            "Obtener documento",
            $result['success'],
            $result['success'] ? "Documento obtenido exitosamente" : "Error: " . ($result['error'] ?? 'Desconocido'),
            $result['data'] ?? null
        );
        
        // Prueba 3: Crear ticket de estacionamiento
        echo "<h3>3. Crear Ticket de Estacionamiento</h3>";
        
        $ticketResult = createTicket('ABC123', 'estacionamiento', 'usuario_test', 'Cliente Prueba');
        showTestResult(
            "Crear ticket",
            $ticketResult['success'],
            $ticketResult['success'] ? "Ticket creado exitosamente" : "Error: " . ($ticketResult['error'] ?? 'Desconocido'),
            $ticketResult['data'] ?? null
        );
        
        // Prueba 4: Crear servicio de lavado
        echo "<h3>4. Crear Servicio de Lavado</h3>";
        
        $servicioResult = createServicioLavado('XYZ789', 'básico', 5000, 'usuario_test', 'Cliente Lavado');
        showTestResult(
            "Crear servicio de lavado",
            $servicioResult['success'],
            $servicioResult['success'] ? "Servicio creado exitosamente" : "Error: " . ($servicioResult['error'] ?? 'Desconocido'),
            $servicioResult['data'] ?? null
        );
        
        // Prueba 5: Listar documentos
        echo "<h3>5. Listar Documentos</h3>";
        
        $listResult = $firestore->listDocuments('test', 10);
        showTestResult(
            "Listar documentos",
            $listResult['success'],
            $listResult['success'] ? "Documentos listados exitosamente" : "Error: " . ($listResult['error'] ?? 'Desconocido'),
            $listResult['data'] ?? null
        );
        
        // Prueba 6: Buscar tickets por patente
        echo "<h3>6. Buscar Tickets por Patente</h3>";
        
        $searchResult = searchTicketsByPatente('ABC123');
        showTestResult(
            "Buscar tickets por patente",
            $searchResult['success'],
            $searchResult['success'] ? "Búsqueda completada" : "Error: " . ($searchResult['error'] ?? 'Desconocido'),
            $searchResult['data'] ?? null
        );
        
        // Prueba 7: Actualizar documento
        echo "<h3>7. Actualizar Documento</h3>";
        
        $updateData = [
            'test_field' => 'valor actualizado',
            'timestamp' => new DateTime(),
            'numero' => 456,
            'activo' => false
        ];
        
        $updateResult = $firestore->updateDocument('test', $documentId, $updateData);
        showTestResult(
            "Actualizar documento",
            $updateResult['success'],
            $updateResult['success'] ? "Documento actualizado exitosamente" : "Error: " . ($updateResult['error'] ?? 'Desconocido'),
            $updateResult['data'] ?? null
        );
        
        // Prueba 8: Eliminar documento
        echo "<h3>8. Eliminar Documento</h3>";
        
        $deleteResult = $firestore->deleteDocument('test', $documentId);
        showTestResult(
            "Eliminar documento",
            $deleteResult['success'],
            $deleteResult['success'] ? "Documento eliminado exitosamente" : "Error: " . ($deleteResult['error'] ?? 'Desconocido'),
            $deleteResult['data'] ?? null
        );
        
        echo "</div>";
        
        // Resumen de pruebas
        echo "<div class='test-section'>";
        echo "<h3>Resumen de Pruebas</h3>";
        echo "<p>Las pruebas de Firestore han sido completadas. Revisa los resultados arriba para verificar que todo funcione correctamente.</p>";
        echo "<p><strong>Nota:</strong> Si alguna prueba falla, verifica que:</p>";
        echo "<ul>";
        echo "<li>La configuración de Firebase sea correcta</li>";
        echo "<li>El proyecto Firebase esté activo</li>";
        echo "<li>Las reglas de seguridad permitan las operaciones</li>";
        echo "<li>La conexión a Internet esté funcionando</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; background: #ffebee; border-radius: 5px;'>";
        echo "<strong>Error Fatal:</strong> " . $e->getMessage();
        echo "</div>";
    }
    ?>

    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;">
        <strong>⚠️ Importante:</strong> Estas son pruebas de desarrollo. No uses datos reales en este entorno.
    </div>
</body>
</html>
