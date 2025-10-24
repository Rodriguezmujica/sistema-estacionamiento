<?php
require_once __DIR__ . '/conexion.php';

$usuarios = [
    ['usuario' => 'admin', 'password' => 'admin123', 'rol' => 'admin'],
    ['usuario' => 'cajero', 'password' => 'cajero123', 'rol' => 'cajero']
];

foreach ($usuarios as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (usuario, password_hash, rol) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $u['usuario'], $hash, $u['rol']);
    if ($stmt->execute()) {
        echo "Usuario {$u['usuario']} creado correctamente<br>";
    } else {
        echo "Error creando {$u['usuario']}: " . $conn->error . "<br>";
    }
}
