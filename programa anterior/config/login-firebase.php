<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth-hybrid.php';

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$auth = getHybridAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    
    // Intentar autenticación híbrida
    $result = $auth->authenticate($usuario, $password);
    
    if ($result['success']) {
        // Login correcto
        $_SESSION['usuario'] = $result['user']['usuario'];
        $_SESSION['rol'] = $result['user']['rol'];
        $_SESSION['id_usuario'] = $result['user']['id'];
        
        // Guardar token de Firebase si está disponible
        if (isset($result['firebase_token'])) {
            $_SESSION['firebase_token'] = $result['firebase_token'];
        }
        
        // Guardar modo de autenticación usado
        $_SESSION['auth_mode'] = $auth->getCurrentMode();
        
        header("Location: index.php");
        exit();
    } else {
        $error = $result['error'] ?? "Credenciales incorrectas";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="./imagenes/Logo_sin_fondo.png">
  <title>Login - Estacionamiento Los Ríos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .login-container {
      max-width: 400px;
      margin: 80px auto;
      padding: 30px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .auth-mode {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 12px;
      padding: 5px 10px;
      border-radius: 15px;
      background: #e3f2fd;
      color: #1976d2;
    }
  </style>
</head>
<body>
  <div class="login-container position-relative">
    <div class="auth-mode">
      🔥 <?= $auth->getCurrentMode() ?>
    </div>
    
    <h3 class="text-center mb-4">Estacionamiento Los Ríos</h3>
    
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="mb-3">
        <label for="usuario" class="form-label">Usuario</label>
        <input type="text" class="form-control" id="usuario" name="usuario" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>
    
    <div class="mt-4 text-center">
      <small class="text-muted">
        Sistema de autenticación híbrido<br>
        <strong>Modo actual:</strong> <?= $auth->getCurrentMode() ?>
      </small>
    </div>
  </div>
</body>
</html>
