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
  <link rel="shortcut icon" href="./imagenes/Logo_sin_fondo.png">
  <title>Dashboard | Estacionamiento Los Ríos</title>
</head>

<body class="bg-light">
  <!-- NAVEGACIÓN -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="./index.php">
        <img src="./imagenes/Logo_sin_fondo.png" alt="Logo" height="40" class="me-2">
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
            <a class="nav-link" href="./secciones/lavados.php">
              <i class="fas fa-car-wash"></i> Servicios Lavado
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./secciones/reporte.php">
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
        <span class="badge bg-success fs-6 me-3" id="badge-precio-minuto">
          <i class="fas fa-dollar-sign"></i> $35/min
        </span>
        <span class="badge bg-info fs-6 me-3" id="badge-maquina-tuu" title="Máquina TUU activa">
          <i class="fas fa-desktop"></i> <span id="nombre-maquina-tuu">Cargando...</span>
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
                  <option value="18">Estacionamiento por minuto</option>
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
                <i id="btnRegistrarIngreso" class="fas fa-ticket-alt"></i> Registrar Ingreso    <!-- ... tu contenido HTML ... -->
              </button>
            </form>

            <!-- Estadísticas rápidas -->
            <div class="row mt-4 text-center">
              <div class="col-6">
                <div class="card bg-light">
                  <div class="card-body py-2 position-relative">
                    <button type="button" class="btn btn-link p-1 position-absolute" 
                            style="top: 5px; right: 5px; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"
                            id="toggle-servicios-hoy" title="Mostrar/ocultar">
                      <i class="fas fa-eye text-muted" style="font-size: 12px;"></i>
                    </button>
                    <h5 class="mb-1" id="total-hoy">0</h5>
                    <small class="text-muted">Servicios Hoy</small>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="card bg-light">
                  <div class="card-body py-2 position-relative">
                    <button type="button" class="btn btn-link p-1 position-absolute" 
                            style="top: 5px; right: 5px; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"
                            id="toggle-ingresos-hoy" title="Mostrar/ocultar">
                      <i class="fas fa-eye text-muted" style="font-size: 12px;"></i>
                    </button>
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
              <button class="btn btn-warning btn-lg" id="btn-cobrar-ticket" disabled>
                <i class="fas fa-file-invoice"></i> Pago Manual (Comprobante Interno)
              </button>
              <small class="text-muted text-center">
                <i class="fas fa-exclamation-circle"></i> Usar solo si TUU está caído o no hay Internet
              </small>
              <button class="btn btn-success btn-lg" id="btn-pagar-tuu" disabled>
                <i class="fas fa-receipt"></i> Pagar con TUU (Boleta Oficial)
              </button>
            </div>

            <!-- Botón de confirmación manual (oculto por defecto) -->
            <div class="d-grid gap-2 mt-3 d-none" id="confirmar-pago-manual-container">
              <button class="btn btn-info btn-lg" id="btn-confirmar-pago-manual-tuu">
                <i class="fas fa-check-circle"></i> Confirmar Pago TUU Manualmente
              </button>
              <small class="text-muted text-center">
                <i class="fas fa-info-circle"></i> Usar si el pago se procesó en TUU pero no se confirmó automáticamente
              </small>
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
            <a href="./secciones/reporte.php" class="btn btn-outline-primary w-100">
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
            <button class="btn btn-outline-info w-100" id="btn-imprimir-ticket" data-bs-toggle="modal" data-bs-target="#modalImprimirTicket">
              <i class="fas fa-print"></i> Imprimir Último Ticket
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

  <!-- Modal Pago Manual -->
  <div class="modal fade" id="modalPagoManual" tabindex="-1" aria-labelledby="modalPagoManualLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title" id="modalPagoManualLabel">
            <i class="fas fa-exclamation-triangle"></i> Confirmar Pago Manual
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong>⚠️ Atención:</strong> Este método genera un comprobante INTERNO, NO una boleta oficial.
          </div>

          <div class="text-center mb-3">
            <p class="mb-1"><strong>Patente:</strong> <span id="patente-modal-manual"></span></p>
            <p class="mb-0"><strong>Total:</strong> <span id="total-modal-manual" class="fs-4 text-primary"></span></p>
          </div>

          <div class="mb-3">
            <label for="motivo-pago-manual" class="form-label">
              <i class="fas fa-clipboard-list"></i> ¿Por qué usar pago manual?
            </label>
            <select class="form-select" id="motivo-pago-manual" required>
              <option value="Pago en efectivo" selected>💵 Pago en efectivo</option>
              <option value="">Seleccione otro motivo...</option>
              <option value="TUU caído">🔴 TUU está caído</option>
              <option value="Sin Internet">📡 Sin conexión a Internet</option>
              <option value="Ingreso por error">⚠️ Ingreso por error</option>
              <option value="Modo test">🧪 Modo test/prueba</option>
              <option value="Cliente no requiere boleta">👤 Cliente no requiere boleta</option>
              <option value="Otro">📝 Otro motivo</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="metodo-pago-manual" class="form-label">
              <i class="fas fa-money-bill-wave"></i> Método de pago
            </label>
            <select class="form-select" id="metodo-pago-manual">
              <option value="EFECTIVO">💵 Efectivo</option>
              <option value="TRANSFERENCIA">🏦 Transferencia</option>
              <option value="TARJETA">💳 Tarjeta (sin TUU)</option>
            </select>
          </div>

          <div class="alert alert-info">
            <small>
              <strong>Importante:</strong>
              <ul class="mb-0 small">
                <li>Este comprobante NO tiene validez tributaria</li>
                <li>Se registrará en el sistema para auditoría</li>
                <li>Se puede imprimir comprobante interno</li>
              </ul>
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button type="button" class="btn btn-warning" id="btn-confirmar-pago-manual">
            <i class="fas fa-check"></i> Confirmar Pago Manual
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Emergencia TUU -->
  <div class="modal fade" id="modalEmergenciaTUU" tabindex="-1" aria-labelledby="modalEmergenciaTUULabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="modalEmergenciaTUULabel">
            <i class="fas fa-exclamation-triangle"></i> Cambiar Máquina TUU
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong>⚠️ Función de Emergencia</strong><br>
            Úsala solo si la máquina TUU principal no está funcionando.
          </div>

          <div id="estado-tuu-actual" class="mb-3">
            <p><strong>Máquina Activa Actual:</strong></p>
            <div class="d-flex align-items-center">
              <span class="spinner-border spinner-border-sm me-2" role="status"></span>
              <span>Cargando...</span>
            </div>
          </div>

          <div id="selector-maquinas-tuu" class="d-none">
            <p><strong>Selecciona la máquina a usar:</strong></p>
            <div class="list-group">
              <!-- Se llenará dinámicamente -->
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Pago TUU -->
  <div class="modal fade" id="modalPagoTUU" tabindex="-1" aria-labelledby="modalPagoTUULabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalPagoTUULabel">
            <i class="fas fa-receipt"></i> Pagar con TUU (Boleta Oficial)
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Información del cobro -->
          <div class="card bg-light mb-3">
            <div class="card-body text-center">
              <p class="mb-1 text-muted">Patente:</p>
              <h4 class="fw-bold text-dark" id="patente-modal-tuu">ABC-123</h4>
              <p class="mb-1 text-muted">Total a pagar:</p>
              <h2 class="text-success fw-bold" id="total-modal-tuu">$5.000</h2>
            </div>
          </div>

          <!-- Método de Pago (OCULTO - TUU maneja automáticamente débito/crédito) -->
          <div class="mb-3" style="display: none;">
            <label class="form-label fw-bold">
              <i class="fas fa-credit-card"></i> Tipo de Tarjeta:
            </label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="metodoTarjeta" id="metodoCreditoTUU" value="credito" autocomplete="off">
              <label class="btn btn-outline-primary" for="metodoCreditoTUU">
                <i class="fas fa-credit-card"></i> Crédito
              </label>

              <input type="radio" class="btn-check" name="metodoTarjeta" id="metodoDebitoTUU" value="debito" autocomplete="off" checked>
              <label class="btn btn-outline-info" for="metodoDebitoTUU">
                <i class="fas fa-wallet"></i> Débito
              </label>
            </div>
            <small class="text-muted d-block mt-2">
              <i class="fas fa-info-circle"></i> La máquina TUU procesará el pago con tarjeta y emitirá boleta oficial
            </small>
          </div>

          <!-- Información simplificada -->
          <div class="alert alert-success mb-3">
            <i class="fas fa-credit-card"></i> <strong>Pago con TUU</strong><br>
            <small>La máquina TUU detecta automáticamente si es tarjeta de débito o crédito y procesa el pago correspondiente.</small>
          </div>
          
          <!-- Alerta sobre efectivo -->
          <div class="alert alert-info">
            <i class="fas fa-money-bill-wave"></i> <strong>¿Cliente paga en efectivo?</strong><br>
            <small>Usa el botón <strong>"Pago Manual"</strong> (amarillo) para registrar pagos en efectivo. Este genera un comprobante interno sin boleta oficial.</small>
          </div>

          <hr>

          <!-- Tipo de Documento -->
          <div class="mb-3">
            <label class="form-label fw-bold">
              <i class="fas fa-file-invoice"></i> Tipo de Documento:
            </label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="tipoDocumento" id="docBoleta" value="boleta" autocomplete="off" checked>
              <label class="btn btn-outline-secondary" for="docBoleta">
                <i class="fas fa-receipt"></i> Boleta
              </label>

              <input type="radio" class="btn-check" name="tipoDocumento" id="docFactura" value="factura" autocomplete="off">
              <label class="btn btn-outline-warning" for="docFactura">
                <i class="fas fa-file-invoice-dollar"></i> Factura
              </label>
            </div>
          </div>

          <!-- Campo RUT para Factura -->
          <div class="mb-3 d-none" id="campo-rut-factura">
            <label for="rut-factura" class="form-label">
              <i class="fas fa-id-card"></i> RUT del Cliente:
            </label>
            <input type="text" class="form-control" id="rut-factura" placeholder="Ej: 12345678-9">
            <small class="text-muted">
              <i class="fas fa-info-circle"></i> TUU buscará automáticamente los datos del cliente en el SII
            </small>
          </div>

          <hr>

          <!-- Botón de pago -->
          <div class="d-grid gap-2">
            <button type="button" class="btn btn-success btn-lg" id="btn-confirmar-pago-tuu">
              <i class="fas fa-check-circle"></i> Confirmar y Pagar con TUU
            </button>
          </div>

          <!-- Spinner (oculto por defecto) -->
          <div id="spinner-pago-tuu" class="d-none text-center mt-3">
            <div class="spinner-border text-success" role="status">
              <span class="visually-hidden">Procesando...</span>
            </div>
            <p class="mt-2 text-success fw-bold">
              <i class="fas fa-wifi"></i> Enviando solicitud a la máquina TUU...
            </p>
            <p class="text-muted">Por favor, complete el pago en el dispositivo TUU.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Confirmar Lavado -->
  <div class="modal fade" id="modalConfirmarLavado" tabindex="-1" aria-labelledby="modalConfirmarLavadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow-lg">
        <div class="modal-header" id="confirmarLavadoHeader">
          <h5 class="modal-title" id="confirmarLavadoTitulo">
            <!-- Título dinámico -->
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="confirmarLavadoBody">
          <!-- El resumen se insertará aquí dinámicamente -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Cancelar
          </button>
          <button type="button" class="btn btn-success" id="btn-confirmar-lavado-accion">
            <i class="fas fa-check me-1"></i> Confirmar Operación
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Imprimir Último Ticket -->
  <div class="modal fade" id="modalImprimirTicket" tabindex="-1" aria-labelledby="modalImprimirTicketLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="modalImprimirTicketLabel">
            <i class="fas fa-print"></i> Imprimir Último Ticket
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-center mb-4">
            <p class="text-muted">Selecciona qué tipo de ticket deseas imprimir:</p>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card h-100 border-primary">
                <div class="card-body text-center">
                  <i class="fas fa-ticket-alt fa-3x text-primary mb-3"></i>
                  <h5 class="card-title">Último Ingreso</h5>
                  <p class="card-text text-muted">Imprime el ticket de ingreso más reciente</p>
                  <div id="ultimo-ingreso-info" class="small text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button type="button" class="btn btn-primary w-100" id="btn-imprimir-ingreso" disabled>
                    <i class="fas fa-print"></i> Imprimir Ingreso
                  </button>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="card h-100 border-success">
                <div class="card-body text-center">
                  <i class="fas fa-receipt fa-3x text-success mb-3"></i>
                  <h5 class="card-title">Última Salida</h5>
                  <p class="card-text text-muted">Imprime el ticket de salida más reciente</p>
                  <div id="ultima-salida-info" class="small text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button type="button" class="btn btn-success w-100" id="btn-imprimir-salida" disabled>
                    <i class="fas fa-print"></i> Imprimir Salida
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
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
  <!-- main.js siempre primero para funciones globales como el reloj y las alertas -->
  <script src="JS/main.js"></script> 
  
  <!-- 🖨️ Servicio de impresión térmica (Windows 7 compatible) -->
  <script src="JS/print-service-client-win7.js"></script>

  <!-- Módulos específicos para el Dashboard -->
  <script src="JS/ingreso.js"></script>
  <script src="JS/cobro.js"></script>
  <script src="JS/reporte.js"></script>
  <script src="JS/modal-lavado.js"></script>
  <script src="JS/modal-modificar-ticket.js"></script>
  <script src="JS/emergencia-tuu.js"></script>
  
  <!-- Script para imprimir último ticket -->
  <script>
    // Variables globales para almacenar datos
    let ultimoIngreso = null;
    let ultimaSalida = null;

    // Cargar datos cuando se abre el modal
    document.getElementById('modalImprimirTicket').addEventListener('show.bs.modal', function () {
      cargarDatosTickets();
    });

    async function cargarDatosTickets() {
      try {
        // Cargar último ingreso y última salida en paralelo
        const [ingresoResponse, salidaResponse] = await Promise.all([
          fetch(`${BASE_PATH}/api/ultimo-ingreso.php`),
          fetch(`${BASE_PATH}/api/ultima-salida.php`)
        ]);

        const ingresoData = await ingresoResponse.json();
        const salidaData = await salidaResponse.json();

        // Procesar último ingreso
        if (ingresoData.success && ingresoData.ingreso) {
          ultimoIngreso = ingresoData.ingreso;
          const info = document.getElementById('ultimo-ingreso-info');
          info.innerHTML = `
            <strong>Patente:</strong> ${ultimoIngreso.patente}<br>
            <strong>Servicio:</strong> ${ultimoIngreso.nombre_servicio}<br>
            <strong>Fecha:</strong> ${ultimoIngreso.fecha_ingreso}
          `;
          document.getElementById('btn-imprimir-ingreso').disabled = false;
        } else {
          document.getElementById('ultimo-ingreso-info').innerHTML = 
            '<span class="text-danger">No hay ingresos registrados</span>';
        }

        // Procesar última salida
        if (salidaData.success && salidaData.salida) {
          ultimaSalida = salidaData.salida;
          const info = document.getElementById('ultima-salida-info');
          info.innerHTML = `
            <strong>Patente:</strong> ${ultimaSalida.patente}<br>
            <strong>Total:</strong> $${ultimaSalida.total}<br>
            <strong>Fecha:</strong> ${ultimaSalida.fecha_salida}
          `;
          document.getElementById('btn-imprimir-salida').disabled = false;
        } else {
          document.getElementById('ultima-salida-info').innerHTML = 
            '<span class="text-danger">No hay salidas registradas</span>';
        }

      } catch (error) {
        console.error('Error cargando datos:', error);
        showError('Error al cargar los datos de tickets');
      }
    }

    // Event listeners para los botones de impresión
    document.getElementById('btn-imprimir-ingreso').addEventListener('click', function() {
      if (ultimoIngreso) {
        imprimirTicket('ingreso', ultimoIngreso.idautos_estacionados);
      }
    });

    document.getElementById('btn-imprimir-salida').addEventListener('click', function() {
      if (ultimaSalida) {
        imprimirTicket('salida', ultimaSalida.id_ingresos);
      }
    });

    async function imprimirTicket(tipo, id) {
      try {
        const response = await fetch(`${BASE_PATH}/api/imprimir-ticket.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `tipo=${tipo}&id=${id}`
        });

        const result = await response.json();

        if (result.success) {
          showSuccess(`✅ ${result.message}`);
          // Cerrar el modal
          bootstrap.Modal.getInstance(document.getElementById('modalImprimirTicket')).hide();
        } else {
          showError(`❌ Error: ${result.error}`);
        }

      } catch (error) {
        console.error('Error imprimiendo ticket:', error);
        showError('❌ Error al imprimir el ticket: ' + error.message);
      }
    }

    // Funciones de toast (reutilizar las de admin.js si están disponibles)
    function showSuccess(message) {
      if (typeof showToast === 'function') {
        showToast('success', message);
      } else {
        alert(message);
      }
    }

    function showError(message) {
      if (typeof showToast === 'function') {
        showToast('error', message);
      } else {
        alert(message);
      }
    }
  </script>
</body>
</html>
