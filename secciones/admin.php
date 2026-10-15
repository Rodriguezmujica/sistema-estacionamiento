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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../scss/main.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <title>Panel de Administración | Estacionamiento Los Ríos</title>
</head>

<body>
  <header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="../index.php">
          <i class="fas fa-car"></i> Estacionamiento Los Ríos
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <a class="nav-link" href="../index.php">
                <i class="fas fa-home"></i> Inicio
              </a>
            </li>   <li class="nav-item">
              <a class="nav-link" href="./lavados.php">
                <i class="fas fa-car-wash"></i> Lavados
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./reporte.php">
                <i class="fas fa-chart-bar"></i> Reportes
              </a>
            </li>
         
            <li class="nav-item">
              <a class="nav-link active" href="./admin.php">
                <i class="fas fa-cog"></i> Administración
              </a>
            </li>
          </ul>
          <div class="d-flex align-items-center">
            <span class="text-white me-3" id="fecha-hora"></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
              <i class="fas fa-sign-out-alt"></i> Salir
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main class="container-fluid py-4">
    <!-- Título principal -->
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-6 text-primary">
          <i class="fas fa-tools"></i> Panel de Administración
        </h1>
        <p class="lead text-muted">Gestiona clientes, servicios y configuraciones del sistema.</p>
      </div>
    </div>

    <!-- Pestañas principales -->
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes-panel" 
                type="button" role="tab">
          <i class="fas fa-users"></i> Gestión de Clientes
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="servicios-tab" data-bs-toggle="tab" data-bs-target="#servicios-panel" 
                type="button" role="tab">
          <i class="fas fa-list"></i> Gestión de Servicios
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="configuracion-tab" data-bs-toggle="tab" data-bs-target="#configuracion-panel" 
                type="button" role="tab">
          <i class="fas fa-cog"></i> Configuración
        </button>
      </li>
      <?php if ($rol == 'admin'): ?>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios-panel" 
                type="button" role="tab">
          <i class="fas fa-user-shield"></i> Gestión de Usuarios
        </button>
      </li>
      <?php endif; ?>
    </ul>

    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="adminTabsContent">
      
      <!-- ============ PESTAÑA: GESTIÓN DE CLIENTES ============ -->
      <div class="tab-pane fade show active" id="clientes-panel" role="tabpanel">
        
        <div class="row mb-4">
          <div class="col-12">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCliente">
              <i class="fas fa-plus"></i> Agregar Cliente Mensual
            </button>
          </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input type="text" class="form-control" id="buscar-cliente" placeholder="Buscar por patente o nombre...">
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select" id="filtro-estado">
              <option value="">Todos los estados</option>
              <option value="activo">✅ Activo</option>
              <option value="vencido">❌ Vencido</option>
            </select>
          </div>
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text">Vencimiento entre</span>
              <input type="date" class="form-control" id="filtro-fecha-desde">
              <span class="input-group-text">y</span>
              <input type="date" class="form-control" id="filtro-fecha-hasta">
            </div>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" id="btn-limpiar-filtros">
              <i class="fas fa-times"></i> Limpiar
            </button>
          </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card bg-success text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h6 class="card-title">Planes Activos</h6>
                    <h3 id="clientes-activos">0</h3>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-check-circle fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-danger text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h6 class="card-title">Planes Vencidos</h6>
                    <h3 id="clientes-vencidos">0</h3>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-exclamation-circle fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-primary text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h6 class="card-title">Total Clientes</h6>
                    <h3 id="total-clientes">0</h3>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-car fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-warning text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h6 class="card-title">Ingresos del Mes</h6>
                    <h3 id="ingresos-mes">$0</h3>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-dollar-sign fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de clientes -->
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">
              <i class="fas fa-table"></i> Registro de Clientes Mensual
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-dark">
                  <tr>
                    <th>Estado</th>
                    <th>Patente</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Inicio Plan</th>
                    <th>Próximo Vencimiento</th>
                    <th>Total</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="tabla-clientes">
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                      <i class="fas fa-spinner fa-spin"></i> Cargando clientes...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ PESTAÑA: GESTIÓN DE SERVICIOS ============ -->
      <div class="tab-pane fade" id="servicios-panel" role="tabpanel">
        
        <!-- Botón agregar servicio -->
        <div class="row mb-4">
          <div class="col-12">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalServicio">
              <i class="fas fa-plus"></i> Agregar Nuevo Servicio
            </button>
          </div>
        </div>

        <!-- Lista de servicios -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header bg-light">
                <h5 class="mb-0">
                  <i class="fas fa-list"></i> Servicios Disponibles
                </h5>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-dark">
                      <tr>
                        <th>ID</th>
                        <th>Nombre del Servicio</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="tabla-servicios">
                      <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                          <i class="fas fa-spinner fa-spin"></i> Cargando servicios...
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ PESTAÑA: CONFIGURACIÓN ============ -->
      <div class="tab-pane fade" id="configuracion-panel" role="tabpanel">
        
        <div class="row">
          <!-- Configuración de precios -->
          <div class="col-md-6 mb-4">
            <div class="card">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                  <i class="fas fa-dollar-sign"></i> Configuración de Precios
                </h5>
              </div>
              <div class="card-body">
                <form id="form-precios">
                  <div class="mb-3">
                    <label class="form-label">Precio por Minuto (Estacionamiento)</label>
                    <div class="input-group">
                      <span class="input-group-text">$</span>
                      <input type="number" class="form-control" id="precio-minuto" value="35">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Precio Mínimo</label>
                    <div class="input-group">
                      <span class="input-group-text">$</span>
                      <input type="number" class="form-control" id="precio-minimo" value="500">
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary" id="btn-guardar-precios">
                    <i class="fas fa-save"></i> Guardar Configuración
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Configuración de Meta Mensual -->
          <div class="col-md-6 mb-4">
            <div class="card">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                  <i class="fas fa-bullseye"></i> Meta Mensual
                </h5>
              </div>
              <div class="card-body">
                <form id="form-meta">
                  <div class="mb-3">
                    <label class="form-label">Meta de Ingresos del Mes</label>
                    <div class="input-group">
                      <span class="input-group-text">$</span>
                      <input type="number" class="form-control" id="meta-mensual" placeholder="6750000" step="1">
                    </div>
                    <small class="text-muted">Ejemplo: 6750000 = $6.750.000 (puedes ingresar cualquier monto)</small>
                  </div>
                  
                  <div class="mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="solo-dias-laborales" checked>
                      <label class="form-check-label" for="solo-dias-laborales">
                        <i class="fas fa-briefcase"></i> Solo contar días laborales (Lun-Vie)
                      </label>
                    </div>
                    <small class="text-muted d-block">Excluye sábados y domingos del cálculo</small>
                  </div>
                  
                  <div class="mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="incluir-mensuales">
                      <label class="form-check-label" for="incluir-mensuales">
                        <i class="fas fa-calendar-alt"></i> Incluir clientes mensuales
                      </label>
                    </div>
                    <small class="text-muted d-block">Suma los planes mensuales a la meta</small>
                  </div>
                  
                  <button type="submit" class="btn btn-info" id="btn-guardar-meta">
                    <i class="fas fa-save"></i> Guardar Meta
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- RESUMEN EJECUTIVO MENSUAL -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card border-primary">
              <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                  <h4 class="mb-0">
                    <i class="fas fa-chart-line"></i> Resumen Ejecutivo Mensual
                  </h4>
                  <div>
                    <select class="form-select form-select-sm d-inline-block" id="selector-mes-resumen" style="width: auto;">
                      <!-- Se llena con JavaScript -->
                    </select>
                  </div>
                </div>
              </div>
              <div class="card-body">
                
                <!-- Loading -->
                <div id="resumen-loading" class="text-center py-5">
                  <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                  <p class="mt-3 text-muted">Cargando resumen ejecutivo...</p>
                </div>
                
                <!-- Contenido del resumen (oculto inicialmente) -->
                <div id="resumen-contenido" class="d-none">
                  
                  <!-- KPIs Principales -->
                  <div class="row text-center mb-4">
                    <div class="col-md-3">
                      <div class="card bg-success text-white">
                        <div class="card-body">
                          <h6 class="card-title">💰 Ingresos del Mes</h6>
                          <h3 class="mb-0" id="kpi-ingresos-mes">$0</h3>
                          <small id="kpi-variacion" class="d-block mt-2"></small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-info text-white">
                        <div class="card-body">
                          <h6 class="card-title">🚗 Vehículos Atendidos</h6>
                          <h3 class="mb-0" id="kpi-vehiculos">0</h3>
                          <small class="d-block mt-2">Este mes</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-warning text-dark">
                        <div class="card-body">
                          <h6 class="card-title">📊 Ticket Promedio</h6>
                          <h3 class="mb-0" id="kpi-ticket-promedio">$0</h3>
                          <small class="d-block mt-2">Por servicio</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-primary text-white">
                        <div class="card-body">
                          <h6 class="card-title">👥 Clientes Mensuales</h6>
                          <h3 class="mb-0" id="kpi-mensuales">$0</h3>
                          <small class="d-block mt-2" id="kpi-mensuales-count">0 clientes</small>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Meta del Mes (solo visible para admin) -->
                  <div class="row mb-4" id="seccion-meta">
                    <div class="col-12">
                      <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                          <h5 class="mb-0"><i class="fas fa-bullseye"></i> Meta del Mes</h5>
                        </div>
                        <div class="card-body">
                          <div class="row align-items-center">
                            <div class="col-md-8">
                              <h6>Progreso de la Meta:</h6>
                              <div class="progress" style="height: 30px;">
                                <div id="barra-meta" class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%">
                                  <span id="texto-barra-meta">0%</span>
                                </div>
                              </div>
                              <div class="mt-2">
                                <small class="text-muted">
                                  <i class="fas fa-info-circle"></i>
                                  <span id="info-meta"></span>
                                </small>
                              </div>
                            </div>
                            <div class="col-md-4 text-end">
                              <p class="mb-1"><strong>Meta Base:</strong> <span id="meta-monto">$0</span></p>
                              <p class="mb-1"><strong>Logrado:</strong> <span id="meta-logrado">$0</span></p>
                              <p class="mb-0"><strong>Estado:</strong> <span id="meta-falta">$0</span></p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Top 5 Servicios y Desglose de Pagos -->
                  <div class="row mb-4 g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                      <div class="card h-100 border-success">
                        <div class="card-header bg-success text-white">
                          <h6 class="mb-0"><i class="fas fa-briefcase"></i> Top 5 (Lun-Vie)</h6>
                        </div>
                        <div class="card-body">
                          <div id="top-servicios-lv" class="list-group list-group-flush">
                            <div class="text-center text-muted py-3">
                              <i class="fas fa-spinner fa-spin"></i> Cargando datos laborales...
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                      <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-dark">
                          <h6 class="mb-0"><i class="fas fa-sun"></i> Top 5 (Solo Sábados)</h6>
                        </div>
                        <div class="card-body">
                          <div id="top-servicios-sab" class="list-group list-group-flush">
                            <div class="text-center text-muted py-3">
                              <i class="fas fa-spinner fa-spin"></i> Cargando datos de sábados...
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 col-lg-6">
                      <div class="card h-100">
                        <div class="card-header bg-light">
                          <h6 class="mb-0"><i class="fas fa-credit-card"></i> Desglose por Método de Pago</h6>
                        </div>
                        <div class="card-body">
                          <div id="desglose-pagos">
                            <div class="text-center text-muted py-3">
                              <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Gráfico de Ingresos por Día -->
                  <div class="row mb-4">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header bg-light">
                          <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Ingresos por Día del Mes</h6>
                        </div>
                        <div class="card-body">
                          <canvas id="grafico-ingresos-mes" style="max-height: 300px;"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Consulta por rango de fechas -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card border-info shadow-sm">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                  <i class="fas fa-calendar-alt"></i> Consulta por Rango de Fechas
                </h5>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-md-4">
                    <label for="admin-fecha-desde" class="form-label">
                      <i class="fas fa-calendar-plus"></i> Desde:
                    </label>
                    <input type="date" class="form-control" id="admin-fecha-desde">
                  </div>
                  <div class="col-md-4">
                    <label for="admin-fecha-hasta" class="form-label">
                      <i class="fas fa-calendar-minus"></i> Hasta:
                    </label>
                    <input type="date" class="form-control" id="admin-fecha-hasta">
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="admin-btn-consultar-fechas">
                      <i class="fas fa-search"></i> Consultar
                    </button>
                  </div>
                </div>

                <div class="row g-3 mb-3" id="admin-resumen-fechas" style="display: none;">
                  <div class="col-md-6">
                    <div class="alert alert-success mb-0">
                      <i class="fas fa-chart-line"></i>
                      <strong>Total Servicios:</strong> <span id="admin-total-servicios-fecha">0</span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="alert alert-success mb-0">
                      <i class="fas fa-dollar-sign"></i>
                      <strong>Total Ingresos:</strong> $<span id="admin-total-ingresos-fecha">0</span>
                    </div>
                  </div>
                </div>

                <div class="row g-3 mb-3" id="admin-export-buttons" style="display: none;">
                  <div class="col-md-6">
                    <button class="btn btn-success w-100" id="admin-btn-exportar-excel" disabled>
                      <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                  </div>
                  <div class="col-md-6">
                    <button class="btn btn-danger w-100" id="admin-btn-exportar-pdf" disabled>
                      <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-striped mb-0">
                    <thead class="table-secondary">
                      <tr>
                        <th><i class="fas fa-tags"></i> Categoría</th>
                        <th><i class="fas fa-list"></i> Tipos de Servicios</th>
                        <th><i class="fas fa-chart-bar"></i> Cantidad</th>
                        <th><i class="fas fa-dollar-sign"></i> Total</th>
                        <th><i class="fas fa-eye"></i> Ver Detalles</th>
                      </tr>
                    </thead>
                    <tbody id="admin-tabla-fechas-body">
                      <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                          <i class="fas fa-info-circle"></i> Selecciona un rango de fechas para consultar
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div id="admin-detalles-container" style="display: none;" class="mt-3">
                  <h6><i class="fas fa-list-alt"></i> Detalles de Servicios</h6>
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                      <thead class="table-dark">
                        <tr>
                          <th>#</th>
                          <th><i class="fas fa-car"></i> Patente</th>
                          <th><i class="fas fa-calendar"></i> Fecha Salida</th>
                          <th><i class="fas fa-cog"></i> Servicio</th>
                          <th><i class="fas fa-dollar-sign"></i> Total</th>
                        </tr>
                      </thead>
                      <tbody id="admin-tabla-detalles-body">
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Acciones del sistema -->
        <div class="row">
          <div class="col-12">
            <div class="card border-warning">
              <div class="card-header bg-warning">
                <h5 class="mb-0">
                  <i class="fas fa-exclamation-triangle"></i> Acciones del Sistema
                </h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-4 mb-2">
                    <button class="btn btn-info w-100" onclick="respaldarDatos()">
                      <i class="fas fa-database"></i> Respaldar Datos
                    </button>
                  </div>
                  <div class="col-md-4 mb-2">
                    <button class="btn btn-success w-100" onclick="optimizarSistema()">
                      <i class="fas fa-tachometer-alt"></i> Optimización
                    </button>
                  </div>
                  <div class="col-md-4 mb-2">
                    <button class="btn btn-secondary w-100" onclick="exportarReporte()">
                      <i class="fas fa-file-export"></i> Exportar Reporte
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ============ PESTAÑA: GESTIÓN DE USUARIOS (SOLO ADMIN) ============ -->
      <?php if ($rol == 'admin'): ?>
      <div class="tab-pane fade" id="usuarios-panel" role="tabpanel">
        
        <!-- Botón agregar usuario -->
        <div class="row mb-4">
          <div class="col-12">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUsuario">
              <i class="fas fa-user-plus"></i> Agregar Usuario
            </button>
          </div>
        </div>

        <!-- Lista de usuarios -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header bg-light">
                <h5 class="mb-0">
                  <i class="fas fa-users-cog"></i> Usuarios del Sistema
                </h5>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-dark">
                      <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="tabla-usuarios">
                      <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                          <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- ============ MODALES ============ -->
  
  <!-- Modal Agregar/Editar Servicio -->
  <div class="modal fade" id="modalServicio" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-plus"></i> <span id="titulo-modal-servicio">Agregar Nuevo Servicio</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="form-servicio">
            <input type="hidden" id="servicio-id">
            <div class="mb-3">
              <label class="form-label">Nombre del Servicio *</label>
              <input type="text" class="form-control" id="servicio-nombre" required 
                     placeholder="Ej: Lavado exterior básico">
            </div>
            <div class="mb-3">
              <label class="form-label">Precio *</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control" id="servicio-precio" required min="0">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" id="servicio-descripcion" rows="3" 
                        placeholder="Descripción opcional del servicio"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" onclick="guardarServicio()">
            <i class="fas fa-save"></i> Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Agregar/Editar Cliente Mensual -->
  <div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="titulo-modal-cliente"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="form-cliente-mensual">
            <input type="hidden" id="cliente-id-mensual">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Patente</label>
                <input type="text" class="form-control text-uppercase" id="cliente-patente-mensual" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre del Cliente</label>
                <input type="text" class="form-control" id="cliente-nombre-mensual" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Tipo de Vehículo</label>
                <input type="text" class="form-control" id="cliente-vehiculo-mensual" placeholder="Ej: Auto, Camioneta">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Monto del Plan</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" class="form-control" id="cliente-monto-mensual" required min="0">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Día de Pago Mensual (1-31)</label>
                <input type="number" class="form-control" id="cliente-dia-pago-mensual" required min="1" max="31" value="5">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Fecha Próximo Vencimiento</label>
                <input type="date" class="form-control" id="cliente-vencimiento-mensual" required>
                <div class="form-text">
                  Esta fecha se actualiza manualmente al recibir un pago para el siguiente mes.
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Notas</label>
              <textarea class="form-control" id="cliente-notas-mensual" rows="2" 
                        placeholder="Notas adicionales sobre el cliente..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btn-guardar-cliente">
            <i class="fas fa-save"></i> Guardar Cambios
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal Agregar/Editar Usuario -->
  <div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-edit"></i> <span id="titulo-modal-usuario">Agregar Nuevo Usuario</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="form-usuario">
            <input type="hidden" id="usuario-id">
            <div class="mb-3">
              <label class="form-label">Nombre de Usuario *</label>
              <input type="text" class="form-control" id="usuario-nombre" required autocomplete="off">
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña *</label>
              <input type="password" class="form-control" id="usuario-password" autocomplete="new-password">
              <small class="form-text text-muted" id="password-help">Dejar en blanco para no cambiar la contraseña existente.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Rol *</label>
              <select class="form-select" id="usuario-rol" required>
                <option value="operador">Operador</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" onclick="guardarUsuario()">
            <i class="fas fa-save"></i> Guardar Usuario
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmación para Pago de Cliente Mensual -->
  <div class="modal fade" id="modalConfirmarPagoCliente" tabindex="-1" aria-labelledby="modalConfirmarPagoClienteLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalConfirmarPagoClienteLabel">
            <i class="fas fa-check-circle"></i> Confirmar Pago de Cliente Mensual
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>¿Está seguro de que desea marcar como pagado a este cliente?</p>
          <div class="alert alert-info">
            <strong>Cliente:</strong> <span id="confirm-pago-cliente-nombre"></span><br>
            <strong>Patente:</strong> <span id="confirm-pago-cliente-patente"></span><br>
            <strong>Monto del Plan:</strong> <span id="confirm-pago-cliente-monto"></span><br>
            <strong>Fecha Actual de Vencimiento:</strong> <span id="confirm-pago-cliente-vencimiento-actual"></span>
          </div>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Nota:</strong> Esto actualizará la fecha de vencimiento al próximo mes.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button type="button" class="btn btn-success" id="btn-confirmar-pago-cliente-si">
            <i class="fas fa-check"></i> Sí, Confirmar Pago
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmación para Meta Mensual -->
  <div class="modal fade" id="modalConfirmarMeta" tabindex="-1" aria-labelledby="modalConfirmarMetaLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="modalConfirmarMetaLabel">
            <i class="fas fa-bullseye"></i> Confirmar Meta Mensual
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>¿Está seguro de que desea guardar la siguiente meta?</p>
          <div class="alert alert-info">
            <strong id="confirm-meta-periodo"></strong><br>
            <strong>Meta:</strong> <span id="confirm-meta-monto"></span><br>
            <strong>Solo días laborales:</strong> <span id="confirm-solo-laborales"></span><br>
            <strong>Incluir mensuales:</strong> <span id="confirm-incluir-mensuales"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button type="button" class="btn btn-info" id="btn-confirmar-meta-si">
            <i class="fas fa-check"></i> Sí, Guardar Meta
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <!-- Toast de éxito -->
    <div id="toast-success" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-success text-white">
        <i class="fas fa-check-circle me-2"></i>
        <strong class="me-auto">Éxito</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toast-success-body">
        <!-- Mensaje dinámico -->
      </div>
    </div>

    <!-- Toast de error -->
    <div id="toast-error" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-danger text-white">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong class="me-auto">Error</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toast-error-body">
        <!-- Mensaje dinámico -->
      </div>
    </div>

    <!-- Toast de advertencia -->
    <div id="toast-warning" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-warning text-dark">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong class="me-auto">Advertencia</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toast-warning-body">
        <!-- Mensaje dinámico -->
      </div>
    </div>

    <!-- Toast de información -->
    <div id="toast-info" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-info text-white">
        <i class="fas fa-info-circle me-2"></i>
        <strong class="me-auto">Información</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toast-info-body">
        <!-- Mensaje dinámico -->
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.min.js"></script>
  <script src="../JS/admin.js"></script>
</body>
</html>