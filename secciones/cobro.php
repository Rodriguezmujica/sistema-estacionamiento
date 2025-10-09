<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Cobro de salidas - Estacionamiento Los Ríos">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../scss/main.css">
  <link rel="shortcut icon" href="../imagenes/Mi proyecto.png">
  <title>Cobro de Salidas | Estacionamiento Los Ríos</title>
</head>

<body class="bg-light">
  <!-- NAVEGACIÓN -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
        <img src="../imagenes/los rios.jpg" alt="Logo" height="35" class="me-2 rounded">
        Estacionamiento Los Ríos
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="./cobro.php"><i class="fas fa-money-bill-wave"></i> Cobro</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./lavados.html"><i class="fas fa-car-wash"></i> Servicios Lavado</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./reporte.html"><i class="fas fa-chart-bar"></i> Reportes</a>
          </li>
          <?php if ($rol === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link text-warning fw-bold" href="./admin.php"><i class="fas fa-cog"></i> Administración</a>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="d-flex align-items-center">
        <span class="text-white me-3 fecha-hora-dinamica"></span>
        <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Salir</a>
      </div>
    </div>
  </nav>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card border-success shadow-sm">
          <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-money-bill-wave"></i> Cobro de Salidas</h4>
          </div>
          <div class="card-body">
            <form id="form-cobro-salida" class="mb-3">
              <label for="patente-cobro" class="form-label"><i class="fas fa-car"></i> Patente del Vehículo</label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg text-uppercase" id="patente-cobro" maxlength="6" placeholder="ABC123" required autofocus>
                <button type="submit" class="btn btn-primary" id="btn-calcular-cobro">Calcular</button>
              </div>
            </form>

            <!-- Resultado del cálculo -->
            <div id="resultado-cobro" class="d-none"></div>

            <!-- Botones de pago -->
            <div class="d-grid gap-2 mt-3">
              <button class="btn btn-success btn-lg" id="btn-cobrar-ticket" disabled>
                <i class="fas fa-money-bill"></i> Cobrar en Efectivo
              </button>
              <button class="btn btn-info btn-lg" id="btn-pagar-tuu" disabled>
                <i class="fas fa-credit-card"></i> Pagar con TUU
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>
  <script src="../JS/cobro.js"></script>
  <script src="../JS/Script.js"></script>
</body>
</html>