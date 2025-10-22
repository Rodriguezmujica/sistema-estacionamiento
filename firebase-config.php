<?php
/**
 * 🔥 CONFIGURACIÓN DE FIREBASE PARA PHP
 * Sistema de Estacionamiento Los Ríos
 * 
 * IMPORTANTE: Reemplaza los valores de configuración con los de tu proyecto Firebase
 */

// Configuración de Firebase
define('FIREBASE_API_KEY', 'AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg');
define('FIREBASE_AUTH_DOMAIN', 'sistemaestacionamiento-46735.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'sistemaestacionamiento-46735');
define('FIREBASE_STORAGE_BUCKET', 'sistemaestacionamiento-46735.firebasestorage.app');
define('FIREBASE_MESSAGING_SENDER_ID', '570161231939');
define('FIREBASE_APP_ID', '1:570161231939:web:50a5f88fcd65e98fa03cf6');

// URL base de Firebase
define('FIREBASE_BASE_URL', 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID);

// Configuración de autenticación
define('FIREBASE_AUTH_URL', 'https://identitytoolkit.googleapis.com/v1/accounts');

// Configuración de Firestore
define('FIRESTORE_URL', 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/databases/(default)/documents');

// Configuración de Storage
define('FIREBASE_STORAGE_URL', 'https://firebasestorage.googleapis.com/v0/b/' . FIREBASE_STORAGE_BUCKET);

// Función para obtener el token de acceso (para autenticación del servidor)
function getFirebaseAccessToken() {
    // En un entorno de producción, deberías usar Service Account
    // Por ahora, usaremos la API key para desarrollo
    return FIREBASE_API_KEY;
}

// Función para hacer peticiones a Firebase
function makeFirebaseRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . getFirebaseAccessToken()
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'data' => json_decode($response, true),
        'status' => $httpCode
    ];
}

// Función para autenticar usuario con Firebase
function authenticateUserWithFirebase($email, $password) {
    $url = FIREBASE_AUTH_URL . ':signInWithPassword?key=' . FIREBASE_API_KEY;
    
    $data = [
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true
    ];
    
    return makeFirebaseRequest($url, 'POST', $data);
}

// Función para obtener datos de Firestore
function getFirestoreDocument($collection, $document) {
    $url = FIRESTORE_URL . '/' . $collection . '/' . $document;
    return makeFirebaseRequest($url);
}

// Función para crear documento en Firestore
function createFirestoreDocument($collection, $data) {
    $url = FIRESTORE_URL . '/' . $collection;
    return makeFirebaseRequest($url, 'POST', $data);
}

// Función para actualizar documento en Firestore
function updateFirestoreDocument($collection, $document, $data) {
    $url = FIRESTORE_URL . '/' . $collection . '/' . $document;
    return makeFirebaseRequest($url, 'PATCH', $data);
}

// Función para eliminar documento de Firestore
function deleteFirestoreDocument($collection, $document) {
    $url = FIRESTORE_URL . '/' . $collection . '/' . $document;
    return makeFirebaseRequest($url, 'DELETE');
}
?>
