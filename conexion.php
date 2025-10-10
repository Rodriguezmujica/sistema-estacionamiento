<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

$host = 'localhost';
$user = 'root';
$pass = ''; // Por defecto en XAMPP, la contraseña es vacía
$dbname = "estacionamiento"; // Usa $dbname aquí

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]));
}