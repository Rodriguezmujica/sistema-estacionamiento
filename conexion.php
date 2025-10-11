<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// ============================================
// CONFIGURACIÓN DE ZONA HORARIA
// ============================================
// Zona horaria de Chile - Maneja automáticamente horario de verano/invierno
date_default_timezone_set('America/Santiago');

$host = 'localhost';
$user = 'root';
$pass = ''; // Por defecto en XAMPP, la contraseña es vacía
$dbname = "estacionamiento"; // Usa $dbname aquí

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]));
}

// Configurar zona horaria de MySQL para que coincida con PHP
// Esto asegura que CURRENT_TIMESTAMP y NOW() usen la hora de Chile
$conn->query("SET time_zone = '-03:00'"); // Chile Standard Time (ajustar según temporada si es necesario)
// Nota: MySQL no maneja automáticamente DST, por eso usamos offset fijo
// Para DST automático, mejor manejar todo en PHP y guardar en UTC