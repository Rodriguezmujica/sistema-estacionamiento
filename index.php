<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sistema de gestión de estacionamiento y lavado de autos Los Ríos">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="./scss/main.css">
  <link rel="shortcut icon" href="./imagenes/Mi proyecto.png">
  <title>Dashboard | Estacionamiento Los Ríos</title>
</head>

<body class="bg-light">
  <!-- NAVEGACIÓN -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="./index.php">
        <img src="./imagenes/los rios.jpg" alt="Logo" height="35" class="me-2 rounded">
        Estacionamiento Los Ríos
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link active" href="./index.php">
              <i class="fas fa-home"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./secciones/lavados.html">
              <i class="fas fa-car-wash"></i> Servicios Lavado
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./secciones/reporte.html">
              <i class="fas fa-chart-bar"></i> Reportes
            </a>
          </li>
          <?php if ($rol === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link text-warning fw-bold" href="./secciones/admin.php">
              <i class="fas fa-cog"></i> Administración
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3">
          <i class="fas fa-dollar-sign"></i> $35/min
        </span>
        <span class="text-white me-3" id="fecha-hora"></span>
        <a href="./logout.php" class="btn btn-outline-light btn-sm">
          <i class="fas fa-sign-out-alt"></i> Salir
        </a>
      </div>
    </div>
  </nav>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="container-fluid py-4">
    
    <!-- REGISTRO DE INGRESOS Y COBRO DE SALIDAS -->
    <div class="row g-4 mb-4">
      
      <!-- INGRESOS -->
      <div class="col-lg-6">
        <div class="card border-primary shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
              <i class="fas fa-plus-circle"></i> Registro de Ingresos
            </h4>
          </div>
          <div class="card-body">
            <form id="form-ingreso">
              <div class="mb-3">
                <label for="patente-ingreso" class="form-label">
                  <i class="fas fa-car"></i> Patente del Vehículo
                </label>
                <input type="text" class="form-control form-control-lg text-uppercase" 
                       id="patente-ingreso" maxlength="6" placeholder="ABC123" required>
              </div>

              <div class="mb-3">
                <label for="tipo-servicio" class="form-label">
                  <i class="fas fa-cog"></i> Tipo de Servicio
                </label>
                <select class="form-select" id="tipo-servicio" required>
                  <option value="">Seleccionar servicio...</option>
                  <option value="18">Estacionamiento x minuto</option>
                  <option value="lavado">Lavado</option>
                </select>
              </div>

              <div class="mb-3 d-none" id="cliente-info">
                <label for="nombre-cliente" class="form-label">
                  <i class="fas fa-user"></i> Nombre del Cliente (Opcional)
                </label>
                <input type="text" class="form-control" id="nombre-cliente" 
                       placeholder="Nombre del cliente">
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100">
                <i id="btnRegistrarIngreso" class="fas fa-ticket-alt"></i> Registrar Ingreso
              </button>
            </form>

            <!-- Estadísticas rápidas -->
            <div class="row mt-4 text-center">
              <div class="col-6">
                <div class="card bg-light">
                  <div class="card-body py-2">
                    <h5 class="mb-1" id="total-hoy">0</h5>
                    <small class="text-muted">Servicios Hoy</small>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="card bg-light">
                  <div class="card-body py-2">
                    <h5 class="mb-1" id="ingresos-hoy">$0</h5>
                    <small class="text-muted">Ingresos Hoy</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SALIDAS -->
      <div class="col-lg-6">
        <div class="card border-success shadow-sm">
          <div class="card-header bg-success text-white">
            <h4 class="mb-0">
              <i class="fas fa-money-bill-wave"></i> Cobro de Salidas
            </h4>
          </div>
          <div class="card-body">
            <form id="form-cobro-salida" class="mb-3">
              <label for="patente-cobro" class="form-label"><i class="fas fa-car"></i> Patente del Vehículo</label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg text-uppercase" id="patente-cobro" maxlength="6" placeholder="ABC123" required>
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

    <!-- ACCIONES RÁPIDAS -->
    <div class="card border-warning shadow-sm">
      <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
          <i class="fas fa-bolt"></i> Acciones Rápidas
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-3">
            <a href="./secciones/reporte.html" class="btn btn-outline-primary w-100">
              <i class="fas fa-chart-bar"></i> Informe Diario
            </a>
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-warning w-100" data-bs-toggle="modal" 
                    data-bs-target="#modalModificarTicket">
              <i class="fas fa-edit"></i> Modificar Ticket
            </button>
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-info w-100" id="btn-refresh">
              <i class="fas fa-sync-alt"></i> Actualizar Datos
            </button>
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-danger w-100" id="btn-emergencia">
              <i class="fas fa-exclamation-triangle"></i> Emergencia
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- MODALES -->
  
  <!-- Modal Lavado -->
  <!-- Modal Lavado COMPLETO - REEMPLAZAR TODO EL MODAL EXISTENTE -->
<div class="modal fade" id="modalLavado" tabindex="-1">
  <div class="modal-dialog modal-xl"> <!-- Cambiado a modal-xl para más espacio -->
    <div class="modal-content">
      <form id="form-lavado-modal">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">
            <i class="fas fa-plus-circle"></i> Registrar Nuevo Servicio de Lavado
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
          <div class="row g-3">
            <!-- Patente -->
            <div class="col-md-3">
              <label for="patente-lavado-modal" class="form-label">
                <i class="fas fa-car"></i> Patente
              </label>
              <input type="text" class="form-control text-uppercase" id="patente-lavado-modal" 
                     maxlength="6" placeholder="ABC123" required disabled>
              <div class="form-text">
                <i class="fas fa-lock text-muted"></i> Prellenada desde el dashboard
              </div>
            </div>

            <!-- Tipo de Lavado -->
            <div class="col-md-3">
              <label for="tipo-lavado-modal" class="form-label">
                <i class="fas fa-soap"></i> Tipo de Lavado
              </label>
              <select class="form-select" id="tipo-lavado-modal" required>
                <option value="">Seleccionar servicio...</option>
              </select>
            </div>

            <!-- Cliente -->
            <div class="col-md-3">
              <label for="nombre-cliente-lavado-modal" class="form-label">
                <i class="fas fa-user"></i> Cliente (Opcional)
              </label>
              <input type="text" class="form-control" id="nombre-cliente-lavado-modal" 
                     placeholder="Nombre del cliente">
            </div>

            <!-- Precio Extra -->
            <div class="col-md-3">
              <label for="precio-extra-modal" class="form-label">
                <i class="fas fa-dollar-sign"></i> Precio Extra
              </label>
              <input type="number" class="form-control" id="precio-extra-modal" min="0" value="0">
            </div>
          </div>
          
          <!-- Motivos de cobro extra -->
          <div class="mt-4">
            <label class="form-label fw-bold">
              <i class="fas fa-exclamation-triangle text-warning"></i> Motivos de Cobro Extra
            </label>
            <div class="row g-2">
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-hongos-modal" value="hongos">
                  <label class="form-check-label" for="motivo-hongos-modal">
                    <i class="fas fa-circle text-warning"></i> Hongos
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-pelos-modal" value="pelos">
                  <label class="form-check-label" for="motivo-pelos-modal">
                    <i class="fas fa-circle" style="color: #8B4513;"></i> Pelos de perros
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-barro-modal" value="barro">
                  <label class="form-check-label" for="motivo-barro-modal">
                    <i class="fas fa-circle text-secondary"></i> Barro excesivo
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-grasa-modal" value="grasa">
                  <label class="form-check-label" for="motivo-grasa-modal">
                    <i class="fas fa-circle text-dark"></i> Grasa/aceite
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-insectos-modal" value="insectos">
                  <label class="form-check-label" for="motivo-insectos-modal">
                    <i class="fas fa-circle text-success"></i> Insectos
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-pintura-modal" value="pintura">
                  <label class="form-check-label" for="motivo-pintura-modal">
                    <i class="fas fa-circle text-danger"></i> Pintura dañada
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-interior-modal" value="interior">
                  <label class="form-check-label" for="motivo-interior-modal">
                    <i class="fas fa-circle text-info"></i> Interior sucio
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input motivo-extra" type="checkbox" id="motivo-otro-modal" value="otro">
                  <label class="form-check-label" for="motivo-otro-modal">
                    <i class="fas fa-circle text-muted"></i> Otro
                  </label>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Descripción adicional -->
          <div class="mt-3">
            <label for="descripcion-extra-modal" class="form-label">
              <i class="fas fa-comment-alt"></i> Descripción Adicional (Opcional)
            </label>
            <textarea class="form-control" id="descripcion-extra-modal" rows="3" 
                      placeholder="Describe cualquier detalle adicional sobre el estado del vehículo..."></textarea>
          </div>

          <!-- Resumen de precios -->
          <div class="mt-4">
            <div class="card bg-light">
              <div class="card-body">
                <h6 class="card-title">
                  <i class="fas fa-calculator"></i> Resumen de Precios
                </h6>
                <div class="row">
                  <div class="col-md-4">
                    <small class="text-muted">Precio base:</small><br>
                    <strong id="precio-base-resumen">$0</strong>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted">Precio extra:</small><br>
                    <strong id="precio-extra-resumen">$0</strong>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted">Total:</small><br>
                    <strong class="text-success" id="precio-total-resumen">$0</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-check"></i> Registrar Lavado Completo
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

  <!-- Modal Modificar Ticket -->
  <div class="modal fade" id="modalModificarTicket" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="form-modificar-ticket">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">
              <i class="fas fa-edit"></i> Modificar Ticket
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="patente-modificar" class="form-label">Patente</label>
                <input type="text" class="form-control text-uppercase" id="patente-modificar" 
                       maxlength="6" placeholder="ABC123" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-info w-100" id="btn-buscar-ticket">
                  Buscar Ticket
                </button>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tipo actual</label>
                <input type="text" class="form-control" id="tipo-actual" readonly>
              </div>
              <div class="col-md-6">
                <label for="nuevo-tipo" class="form-label">Nuevo tipo de servicio</label>
                <select class="form-select" id="nuevo-tipo" required>
                  <option value="">Seleccionar...</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-warning" id="btn-guardar-cambio" disabled>
              Guardar Cambio
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <h5><i class="fas fa-car"></i> Estacionamiento Los Ríos</h5>
          <p>Sistema de gestión integral para estacionamiento y servicios de lavado.</p>
        </div>
        <div class="col-md-4">
          <h5><i class="fas fa-phone"></i> Contacto</h5>
          <p>Teléfono: [TU_TELEFONO]<br>Valdivia, Los Ríos</p>
        </div>
        <div class="col-md-4">
          <h5><i class="fas fa-cog"></i> Sistema</h5>
          <p>Versión: 2.0<br>Actualización: <span id="fecha-sistema"></span></p>
        </div>
      </div>
      <hr>
      <div class="text-center">
        <p class="mb-0">&copy; 2024 Estacionamiento Los Ríos</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Scripts de la aplicación -->
  <!-- main.js siempre primero -->
  <script src="./JS/main.js"></script> 
  
  <!-- Módulos específicos para esta página -->
  <script src="./JS/ingreso.js"></script>
  <script src="./JS/reporte.js"></script>
  <script src="./JS/cobro.js"></script>
  <script src="./JS/modal-lavado.js"></script>
  <script src="./JS/modal-modificar-ticket.js"></script>
  
  <!-- El Script.js ahora es solo el orquestador -->
  <script src="./JS/Script.js"></script>
</body>
</html>
