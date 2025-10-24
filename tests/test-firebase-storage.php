<?php
/**
 * 🧪 PRUEBAS DE FIREBASE STORAGE
 * Sistema de Estacionamiento Los Ríos
 */

require_once __DIR__ . '/firebase-storage-service.php';

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
    <title>Pruebas de Firebase Storage - Sistema Estacionamiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .config-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .file-upload { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔥 Pruebas de Firebase Storage</h1>
    
    <div class="config-info">
        <h3>Configuración Actual</h3>
        <p><strong>Storage Bucket:</strong> <?= FIREBASE_STORAGE_BUCKET ?></p>
        <p><strong>Base URL:</strong> <?= FIREBASE_STORAGE_URL ?></p>
    </div>

    <div class="file-upload">
        <h3>Subir Archivo de Prueba</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="test_file" accept=".jpg,.jpeg,.png,.pdf,.txt">
            <button type="submit" name="upload_test">Subir Archivo</button>
        </form>
    </div>

    <?php
    try {
        $storage = getFirebaseStorageService();
        
        // Procesar subida de archivo si se envió
        if (isset($_POST['upload_test']) && isset($_FILES['test_file'])) {
            $file = $_FILES['test_file'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $tempPath = $file['tmp_name'];
                $originalName = $file['name'];
                $uniqueName = $storage->generateUniqueFileName($originalName, 'test_');
                $remotePath = 'test/' . $uniqueName;
                
                $metadata = [
                    'original_name' => $originalName,
                    'type' => 'test_file',
                    'uploaded_at' => date('c'),
                    'uploaded_by' => 'test_user'
                ];
                
                $result = $storage->uploadFile($tempPath, $remotePath, $metadata);
                
                if ($result['success']) {
                    showTestResult(
                        "Subir archivo de prueba",
                        true,
                        "Archivo subido exitosamente: " . $originalName,
                        $result['data'] ?? null
                    );
                    
                    // Guardar información para pruebas posteriores
                    $_SESSION['test_file_path'] = $remotePath;
                    $_SESSION['test_file_name'] = $originalName;
                } else {
                    showTestResult(
                        "Subir archivo de prueba",
                        false,
                        "Error: " . ($result['error'] ?? 'Desconocido'),
                        $result
                    );
                }
            } else {
                showTestResult(
                    "Subir archivo de prueba",
                    false,
                    "Error en la subida del archivo: " . $file['error']
                );
            }
        }
        
        // Prueba 1: Crear archivo de prueba temporal
        echo "<div class='test-section'>";
        echo "<h3>1. Crear Archivo de Prueba</h3>";
        
        $testContent = "Este es un archivo de prueba creado el " . date('Y-m-d H:i:s');
        $testFileName = 'test_' . time() . '.txt';
        $testFilePath = sys_get_temp_dir() . '/' . $testFileName;
        
        if (file_put_contents($testFilePath, $testContent)) {
            showTestResult(
                "Crear archivo temporal",
                true,
                "Archivo creado: " . $testFilePath
            );
        } else {
            showTestResult(
                "Crear archivo temporal",
                false,
                "No se pudo crear el archivo temporal"
            );
        }
        
        // Prueba 2: Subir archivo de prueba
        echo "<h3>2. Subir Archivo de Prueba</h3>";
        
        if (file_exists($testFilePath)) {
            $remotePath = 'test/' . $testFileName;
            $metadata = [
                'type' => 'test_file',
                'created_at' => date('c'),
                'test' => true
            ];
            
            $result = $storage->uploadFile($testFilePath, $remotePath, $metadata);
            showTestResult(
                "Subir archivo",
                $result['success'],
                $result['success'] ? "Archivo subido exitosamente" : "Error: " . ($result['error'] ?? 'Desconocido'),
                $result['data'] ?? null
            );
            
            if ($result['success']) {
                $_SESSION['test_file_path'] = $remotePath;
            }
        } else {
            showTestResult(
                "Subir archivo",
                false,
                "Archivo de prueba no encontrado"
            );
        }
        
        // Prueba 3: Listar archivos
        echo "<h3>3. Listar Archivos</h3>";
        
        $listResult = $storage->listFiles('test/', 10);
        showTestResult(
            "Listar archivos",
            $listResult['success'],
            $listResult['success'] ? "Archivos listados exitosamente" : "Error: " . ($listResult['error'] ?? 'Desconocido'),
            $listResult['data'] ?? null
        );
        
        // Prueba 4: Obtener metadatos
        echo "<h3>4. Obtener Metadatos</h3>";
        
        if (isset($_SESSION['test_file_path'])) {
            $metadataResult = $storage->getFileMetadata($_SESSION['test_file_path']);
            showTestResult(
                "Obtener metadatos",
                $metadataResult['success'],
                $metadataResult['success'] ? "Metadatos obtenidos exitosamente" : "Error: " . ($metadataResult['error'] ?? 'Desconocido'),
                $metadataResult['data'] ?? null
            );
        } else {
            showTestResult(
                "Obtener metadatos",
                false,
                "No hay archivo de prueba disponible"
            );
        }
        
        // Prueba 5: Descargar archivo
        echo "<h3>5. Descargar Archivo</h3>";
        
        if (isset($_SESSION['test_file_path'])) {
            $downloadPath = sys_get_temp_dir() . '/downloaded_' . basename($_SESSION['test_file_path']);
            $downloadResult = $storage->downloadFile($_SESSION['test_file_path'], $downloadPath);
            
            if ($downloadResult['success']) {
                $downloadedContent = file_get_contents($downloadPath);
                $matches = ($downloadedContent === $testContent);
                
                showTestResult(
                    "Descargar archivo",
                    $downloadResult['success'] && $matches,
                    $downloadResult['success'] ? 
                        ($matches ? "Archivo descargado y verificado correctamente" : "Archivo descargado pero contenido no coincide") :
                        "Error: " . ($downloadResult['error'] ?? 'Desconocido'),
                    $downloadResult
                );
                
                // Limpiar archivo descargado
                if (file_exists($downloadPath)) {
                    unlink($downloadPath);
                }
            } else {
                showTestResult(
                    "Descargar archivo",
                    false,
                    "Error: " . ($downloadResult['error'] ?? 'Desconocido'),
                    $downloadResult
                );
            }
        } else {
            showTestResult(
                "Descargar archivo",
                false,
                "No hay archivo de prueba disponible"
            );
        }
        
        // Prueba 6: Funciones específicas del sistema
        echo "<h3>6. Funciones Específicas del Sistema</h3>";
        
        // Probar subida de imagen de ticket
        if (file_exists($testFilePath)) {
            $ticketImageResult = uploadTicketImage($testFilePath, 'test_ticket_123');
            showTestResult(
                "Subir imagen de ticket",
                $ticketImageResult['success'],
                $ticketImageResult['success'] ? "Imagen de ticket subida exitosamente" : "Error: " . ($ticketImageResult['error'] ?? 'Desconocido'),
                $ticketImageResult['data'] ?? null
            );
        }
        
        // Probar subida de imagen de lavado
        if (file_exists($testFilePath)) {
            $lavadoImageResult = uploadLavadoImage($testFilePath, 'test_servicio_456');
            showTestResult(
                "Subir imagen de lavado",
                $lavadoImageResult['success'],
                $lavadoImageResult['success'] ? "Imagen de lavado subida exitosamente" : "Error: " . ($lavadoImageResult['error'] ?? 'Desconocido'),
                $lavadoImageResult['data'] ?? null
            );
        }
        
        // Probar subida de backup
        if (file_exists($testFilePath)) {
            $backupResult = uploadBackupFile($testFilePath, 'test');
            showTestResult(
                "Subir archivo de backup",
                $backupResult['success'],
                $backupResult['success'] ? "Backup subido exitosamente" : "Error: " . ($backupResult['error'] ?? 'Desconocido'),
                $backupResult['data'] ?? null
            );
        }
        
        // Probar listado de backups
        $backupsResult = listBackups();
        showTestResult(
            "Listar backups",
            $backupsResult['success'],
            $backupsResult['success'] ? "Backups listados exitosamente" : "Error: " . ($backupsResult['error'] ?? 'Desconocido'),
            $backupsResult['data'] ?? null
        );
        
        // Prueba 7: Eliminar archivo de prueba
        echo "<h3>7. Eliminar Archivo de Prueba</h3>";
        
        if (isset($_SESSION['test_file_path'])) {
            $deleteResult = $storage->deleteFile($_SESSION['test_file_path']);
            showTestResult(
                "Eliminar archivo",
                $deleteResult['success'],
                $deleteResult['success'] ? "Archivo eliminado exitosamente" : "Error: " . ($deleteResult['error'] ?? 'Desconocido'),
                $deleteResult
            );
            
            // Limpiar sesión
            unset($_SESSION['test_file_path']);
            unset($_SESSION['test_file_name']);
        } else {
            showTestResult(
                "Eliminar archivo",
                false,
                "No hay archivo de prueba disponible"
            );
        }
        
        echo "</div>";
        
        // Limpiar archivo temporal
        if (file_exists($testFilePath)) {
            unlink($testFilePath);
        }
        
        // Resumen de pruebas
        echo "<div class='test-section'>";
        echo "<h3>Resumen de Pruebas</h3>";
        echo "<p>Las pruebas de Firebase Storage han sido completadas. Revisa los resultados arriba para verificar que todo funcione correctamente.</p>";
        echo "<p><strong>Nota:</strong> Si alguna prueba falla, verifica que:</p>";
        echo "<ul>";
        echo "<li>La configuración de Firebase Storage sea correcta</li>";
        echo "<li>El bucket de Storage esté configurado</li>";
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
