<?php
/**
 * 🔥 SERVICIO DE FIREBASE STORAGE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo maneja todas las operaciones con Firebase Storage
 */

require_once __DIR__ . '/firebase-config.php';

class FirebaseStorageService {
    private $storageBucket;
    private $baseUrl;
    
    public function __construct() {
        $this->storageBucket = FIREBASE_STORAGE_BUCKET;
        $this->baseUrl = FIREBASE_STORAGE_URL;
    }
    
    /**
     * Subir archivo a Firebase Storage
     */
    public function uploadFile($localPath, $remotePath, $metadata = []) {
        if (!file_exists($localPath)) {
            return [
                'success' => false,
                'error' => 'Archivo local no encontrado: ' . $localPath
            ];
        }
        
        $fileContent = file_get_contents($localPath);
        $fileSize = filesize($localPath);
        $mimeType = mime_content_type($localPath);
        
        // URL de destino
        $url = $this->baseUrl . '/o/' . urlencode($remotePath);
        
        // Metadatos por defecto
        $defaultMetadata = [
            'name' => $remotePath,
            'contentType' => $mimeType,
            'size' => $fileSize,
            'uploaded_at' => date('c'),
            'uploaded_by' => 'sistema_estacionamiento'
        ];
        
        $finalMetadata = array_merge($defaultMetadata, $metadata);
        
        // Crear multipart form data
        $boundary = '----WebKitFormBoundary' . uniqid();
        $data = $this->createMultipartData($fileContent, $finalMetadata, $boundary);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Authorization: Bearer ' . getFirebaseAccessToken()
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'data' => $responseData,
                'download_url' => $this->getDownloadUrl($remotePath)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $httpCode,
                'response' => $response
            ];
        }
    }
    
    /**
     * Descargar archivo de Firebase Storage
     */
    public function downloadFile($remotePath, $localPath = null) {
        $downloadUrl = $this->getDownloadUrl($remotePath);
        
        if (!$localPath) {
            $localPath = sys_get_temp_dir() . '/' . basename($remotePath);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $fileContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            if (file_put_contents($localPath, $fileContent)) {
                return [
                    'success' => true,
                    'local_path' => $localPath,
                    'size' => strlen($fileContent)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo guardar el archivo localmente'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $httpCode
            ];
        }
    }
    
    /**
     * Eliminar archivo de Firebase Storage
     */
    public function deleteFile($remotePath) {
        $url = $this->baseUrl . '/o/' . urlencode($remotePath);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . getFirebaseAccessToken()
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'response' => $response
        ];
    }
    
    /**
     * Listar archivos en un directorio
     */
    public function listFiles($prefix = '', $maxResults = 100) {
        $url = $this->baseUrl . '/o';
        
        $params = [
            'prefix' => $prefix,
            'maxResults' => $maxResults
        ];
        
        $url .= '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . getFirebaseAccessToken()
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'data' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $httpCode,
                'response' => $response
            ];
        }
    }
    
    /**
     * Obtener metadatos de un archivo
     */
    public function getFileMetadata($remotePath) {
        $url = $this->baseUrl . '/o/' . urlencode($remotePath);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . getFirebaseAccessToken()
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'data' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $httpCode,
                'response' => $response
            ];
        }
    }
    
    /**
     * Crear multipart form data
     */
    private function createMultipartData($fileContent, $metadata, $boundary) {
        $data = '';
        
        // Agregar metadatos
        $data .= "--$boundary\r\n";
        $data .= "Content-Disposition: form-data; name=\"metadata\"\r\n";
        $data .= "Content-Type: application/json\r\n\r\n";
        $data .= json_encode($metadata) . "\r\n";
        
        // Agregar archivo
        $data .= "--$boundary\r\n";
        $data .= "Content-Disposition: form-data; name=\"file\"\r\n";
        $data .= "Content-Type: application/octet-stream\r\n\r\n";
        $data .= $fileContent . "\r\n";
        
        $data .= "--$boundary--\r\n";
        
        return $data;
    }
    
    /**
     * Obtener URL de descarga
     */
    private function getDownloadUrl($remotePath) {
        return $this->baseUrl . '/o/' . urlencode($remotePath) . '?alt=media';
    }
    
    /**
     * Generar nombre único para archivo
     */
    public function generateUniqueFileName($originalName, $prefix = '') {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $timestamp = date('Y-m-d_H-i-s');
        $random = uniqid();
        
        return $prefix . $name . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
}

// Funciones de conveniencia para uso global
function getFirebaseStorageService() {
    return new FirebaseStorageService();
}

// Funciones específicas para el sistema de estacionamiento
function uploadTicketImage($localPath, $ticketId) {
    $storage = getFirebaseStorageService();
    $remotePath = 'tickets/' . $ticketId . '/imagen_' . time() . '.jpg';
    
    $metadata = [
        'ticket_id' => $ticketId,
        'type' => 'ticket_image',
        'uploaded_at' => date('c')
    ];
    
    return $storage->uploadFile($localPath, $remotePath, $metadata);
}

function uploadLavadoImage($localPath, $servicioId) {
    $storage = getFirebaseStorageService();
    $remotePath = 'servicios_lavado/' . $servicioId . '/imagen_' . time() . '.jpg';
    
    $metadata = [
        'servicio_id' => $servicioId,
        'type' => 'lavado_image',
        'uploaded_at' => date('c')
    ];
    
    return $storage->uploadFile($localPath, $remotePath, $metadata);
}

function uploadReportePDF($localPath, $reporteId) {
    $storage = getFirebaseStorageService();
    $remotePath = 'reportes/' . $reporteId . '/reporte_' . date('Y-m-d') . '.pdf';
    
    $metadata = [
        'reporte_id' => $reporteId,
        'type' => 'reporte_pdf',
        'uploaded_at' => date('c')
    ];
    
    return $storage->uploadFile($localPath, $remotePath, $metadata);
}

function uploadBackupFile($localPath, $backupType = 'manual') {
    $storage = getFirebaseStorageService();
    $remotePath = 'backups/' . $backupType . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    $metadata = [
        'backup_type' => $backupType,
        'type' => 'backup_file',
        'uploaded_at' => date('c')
    ];
    
    return $storage->uploadFile($localPath, $remotePath, $metadata);
}

function listTicketImages($ticketId) {
    $storage = getFirebaseStorageService();
    return $storage->listFiles('tickets/' . $ticketId . '/');
}

function listLavadoImages($servicioId) {
    $storage = getFirebaseStorageService();
    return $storage->listFiles('servicios_lavado/' . $servicioId . '/');
}

function listReportes() {
    $storage = getFirebaseStorageService();
    return $storage->listFiles('reportes/');
}

function listBackups($backupType = null) {
    $storage = getFirebaseStorageService();
    $prefix = $backupType ? 'backups/' . $backupType . '/' : 'backups/';
    return $storage->listFiles($prefix);
}
?>
