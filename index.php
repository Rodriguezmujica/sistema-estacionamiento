<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$rol = $_SESSION['rol']; // guardamos el rol para usarlo en el menú
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="keywords" content="Estacionamiento,Lavado de autos,Los Ríos,Valdivia,Parking,Car wash,Servicios automotrices">
  <meta name="description" content="Sistema de gestión de estacionamiento y lavado de autos Los Ríos. Control de ingresos, salidas y servicios de lavado.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./scss/main.css">
  <link rel="shortcut icon" href="./imagenes/Mi proyecto.png">
  <title>Dashboard | Estacionamiento y Lavado Los Ríos</title>
</head>

<body class="bg-light">
  <!-- encabezado-->
  <header>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <!-- Logo alineado a la izquierda -->
    <a class="navbar-brand fw-bold d-flex align-items-center" href="./index.php">
      <img src="./imagenes/los rios.jpg" alt="Logo" height="35" class="me-2 rounded">
      Estacionamiento Los Ríos
    </a>

    <!-- Botón hamburguesa (responsive) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menú centrado -->
    <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
      <ul class="navbar-nav mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link text-white" href="./index.php">🏠 Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="./secciones/lavados.html">🧽 Servicios Lavado</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="./secciones/reporte.html">📊 Reporte</a>
        </li>

        <!-- 🔒 Sección solo visible para admin -->
        <?php if ($rol === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link text-warning fw-bold" href="./secciones/admin.php">⚙️ Administración</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Info + Logout alineados a la derecha -->
    <div class="d-flex align-items-center">
      <span class="navbar-text me-3">
        <span class="badge bg-success fs-6">💰 $35/min</span>
      </span>
      <span class="navbar-text text-white me-3">
        <span id="fecha-hora"></span>
      </span>
      <a href="./logout.php" class="btn btn-outline-light btn-sm">🚪 Cerrar sesión</a>
    </div>
  </div>
</nav>




</header>

  <!-- contenido principal -->

  <main class="container-fluid py-3">
    <!-- Dashboard Principal -->
    <div class="row g-3">

      <!-- COLUMNA IZQUIERDA: REGISTRO DE INGRESOS -->
      <div class="col-lg-6">
        <div class="card h-100 border-primary shadow-sm bg-gris-claro">
          <!-- Registro de Ingresos -->
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">🚪 Registro de Ingresos</h4>
          </div>
          <div class="card-body">

            <!-- Formulario de ingreso -->
            <form id="form-ingreso">
              <div class="mb-3">
                <label for="patente-ingreso" class="form-label text-gris-oscuro">
                  🚗 Patente del Vehículo
                </label>
                <input type="text" class="form-control form-control-lg text-uppercase"
                  id="patente-ingreso" maxlength="6" placeholder="ABC123" required>
              </div>

              <div class="mb-3">
                <label for="tipo-servicio" class="form-label text-gris-oscuro">
                  🔧 Tipo de Servicio
                </label>
                <select class="form-select" id="tipo-servicio" required>
                  <option value="">Seleccionar servicio...</option>
                  <option value="Estacionamiento">Estacionamiento por minuto</option>
                  <option value="Lavado">Lavado</option>
                </select>
              </div>

              <div class="mb-3 d-none" id="cliente-info">
                <label for="nombre-cliente" class="form-label text-gris-oscuro">
                  👤 Nombre del Cliente (Opcional)
                </label>
                <input type="text" class="form-control" id="nombre-cliente" placeholder="Nombre del cliente">
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                  🎫 Registrar Ingreso e Imprimir Ticket
                </button>
              </div>
            </form>

            <!-- Estadísticas rápidas -->
            <div class="mt-4">
              <div class="row text-center">
                <div class="col-6">
                  <div class="card bg-light border-0 shadow-sm">
                    <div class="card-body py-2">
                      <h5 class="card-title mb-1" id="total-hoy">0</h5>
                      <small class="text-muted">Servicios Hoy</small>
                    </div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="card bg-light border-0 shadow-sm">
                    <div class="card-body py-2">
                      <h5 class="card-title mb-1" id="ingresos-hoy">$0</h5>
                      <small class="text-muted">Ingresos Hoy</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUMNA DERECHA: COBRO DE SALIDAS -->
      <div class="col-lg-6">
        <div class="card h-100 border-success shadow-sm bg-gris-claro">
          <!-- Cobro de Salidas -->
          <div class="card-header bg-success text-white">
            <h4 class="mb-1 titulo-salidas">🏁 Cobro de Salidas</h4>
          </div>
          <div class="card-body">

            <!-- Campo patente y botón calcular -->
            <form id="form-cobro-salida" class="mb-3">
              <label for="patente-cobro" class="form-label text-gris-oscuro">
                🚗 Patente del Vehículo
              </label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg text-uppercase"
                  id="patente-cobro" maxlength="6" placeholder="ABC123" required>
                <button type="submit" class="btn btn-primary btn-lg" id="btn-calcular-cobro">
                  Calcular
                </button>
              </div>
            </form>

            <!-- Mostrar total a pagar -->
            <div id="resultado-cobro" class="alert alert-info d-none">
              <strong>Total a pagar: </strong>
              <span id="total-a-pagar">$0</span>
            </div>

            <!-- Botones de acción -->
            <div class="d-grid gap-2">
              <button class="btn btn-success btn-lg" id="btn-cobrar-ticket" disabled>
                💰 Cobrar e Imprimir Ticket
              </button>
              <button class="btn btn-info btn-lg" id="btn-pagar-tuu" disabled>
                💳 Pagar con TUU
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- FILA INFERIOR: ACCIONES RÁPIDAS -->
    <div class="row mt-3">
      <div class="col-12">
        <div class="card border-warning shadow-sm">
          <!-- Acciones Rápidas -->
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">⚡ Acciones Rápidas</h5>
          </div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-3">
                <a href="./secciones/reporte.html" class="btn btn-outline-primary w-100" id="btn-informe-diario">📊 Informe Diario</a>
              </div>
         <div class="col-md-3">
  <button class="btn btn-outline-warning w-100" id="btn-modificar-ticket" data-bs-toggle="modal" data-bs-target="#modalModificarTicket">
    ✏️ Modificar Ticket
  </button>
</div>
              <div class="col-md-3">
                <button class="btn btn-outline-info w-100" id="btn-refresh">
                  🔄 Actualizar Datos
                </button>
              </div>
              <div class="col-md-3">
                <button class="btn btn-outline-danger w-100" id="btn-emergencia">
                  🚨 Emergencia
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

 
    <!-- Modal Servicios de Lavado -->
    <div class="modal fade" id="modalLavado" tabindex="-1" aria-labelledby="modalLavadoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="form-lavado-modal">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title" id="modalLavadoLabel">Selecciona Servicio de Lavado</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Patente</label>
                <input type="text" class="form-control" id="patente-lavado-modal" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Servicio de Lavado</label>
                <select class="form-select" id="servicio-lavado-modal" required>
                  <option value="">Seleccionar...</option>
                  <option value="Lavado Exterior">Lavado Exterior</option>
                  <option value="Lavado Interior">Lavado Interior</option>
                  <option value="Lavado Completo">Lavado Completo</option>
                  <option value="Detailing">Detailing</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Aceptar</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Modificar Ticket -->
    <div class="modal fade" id="modalModificarTicket" tabindex="-1" aria-labelledby="modalModificarTicketLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="form-modificar-ticket">
            <div class="modal-header bg-warning text-dark">
              <h5 class="modal-title" id="modalModificarTicketLabel">✏️ Cambiar o Modificar Ticket</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="patente-modificar" class="form-label">Patente</label>
                  <input type="text" class="form-control text-uppercase" id="patente-modificar" maxlength="6" placeholder="ABC123" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label d-block">&nbsp;</label>
                  <button type="button" class="btn btn-info w-100" id="btn-buscar-ticket">Buscar Ticket</button>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tipo actual</label>
                  <input type="text" class="form-control" id="tipo-actual" readonly>
                </div>
                <div class="col-md-6">
                  <label for="nuevo-tipo" class="form-label">Nuevo tipo de servicio</label>
                  <select class="form-select" id="nuevo-tipo" required>
                    <option value="">Seleccionar...</option>
                    <!-- Este select se llenará dinámicamente desde api_servicios_lavado.php -->
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-warning" id="btn-guardar-cambio" disabled>Guardar Cambio</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <footer class="py-3 mt-5 footer-blanco">
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <h5 class="text-blanco">🚗 Estacionamiento Los Ríos</h5>
          <p>Sistema de gestión integral para estacionamiento y servicios de lavado de autos.</p>
        </div>
        <div class="col-md-4">
          <h5 class="text-blanco">📞 Contacto</h5>
          <p>Teléfono: [TU_TELEFONO]<br>
            Dirección: [TU_DIRECCION]<br>
            Valdivia, Los Ríos</p>
        </div>
        <div class="col-md-4">
          <h5 class="text-blanco">⚙️ Sistema</h5>
          <p>Versión: 2.0<br>
            Última actualización: <span id="fecha-sistema"></span></p>
        </div>
      </div>
      <hr class="hr-blanco">
      <div class="text-center">
        <p class="mb-0 text-blanco">&copy; 2024 Estacionamiento Los Ríos - Todos los derechos reservados</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
    crossorigin="anonymous"></script>
  <script src="./JS/Script.js"></script>
  <script src="./JS/modal-modificar-ticket.js"></script>
</body>
</html>
