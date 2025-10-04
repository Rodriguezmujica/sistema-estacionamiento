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
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./reportes.html">
                <i class="fas fa-chart-bar"></i> Reportes
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./lavados.html">
                <i class="fas fa-car-wash"></i> Lavados
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" href="./admin.php">
                <i class="fas fa-cog"></i> Administración
              </a>
            </li>
          </ul>
          <div class="navbar-text">
            <i class="fas fa-user-shield"></i> Panel Admin
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
    </ul>

    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="adminTabsContent">
      
      <!-- ============ PESTAÑA: GESTIÓN DE CLIENTES ============ -->
      <div class="tab-pane fade show active" id="clientes-panel" role="tabpanel">
        
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
              <option value="pagado">✅ Pagado</option>
              <option value="pendiente">❌ Pendiente</option>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select" id="filtro-mes">
              <option value="">Todos los meses</option>
              <option value="2024-01">Enero 2024</option>
              <option value="2024-02">Febrero 2024</option>
              <option value="2024-03">Marzo 2024</option>
              <option value="2024-04">Abril 2024</option>
              <option value="2024-05">Mayo 2024</option>
              <option value="2024-06">Junio 2024</option>
              <option value="2024-07">Julio 2024</option>
              <option value="2024-08">Agosto 2024</option>
              <option value="2024-09">Septiembre 2024</option>
              <option value="2024-10">Octubre 2024</option>
              <option value="2024-11">Noviembre 2024</option>
              <option value="2024-12">Diciembre 2024</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" onclick="exportarClientes()">
              <i class="fas fa-download"></i> Exportar
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
                    <h6 class="card-title">Clientes al Día</h6>
                    <h3 id="clientes-pagados">0</h3>
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
                    <h6 class="card-title">Pagos Pendientes</h6>
                    <h3 id="clientes-pendientes">0</h3>
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
                    <h6 class="card-title">Total Servicios</h6>
                    <h3 id="total-servicios">0</h3>
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
                    <h6 class="card-title">Ingresos Mes</h6>
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
                    <th>Servicio</th>
                    <th>Fecha</th>
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
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarServicio">
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
                        <th>Última Actualización</th>
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
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Configuración
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Configuración del sistema -->
          <div class="col-md-6 mb-4">
            <div class="card">
              <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                  <i class="fas fa-cog"></i> Configuración del Sistema
                </h5>
              </div>
              <div class="card-body">
                <form id="form-sistema">
                  <div class="mb-3">
                    <label class="form-label">Nombre del Negocio</label>
                    <input type="text" class="form-control" id="nombre-negocio" value="Estacionamiento Los Ríos">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Horario de Atención</label>
                    <div class="row">
                      <div class="col-6">
                        <input type="time" class="form-control" id="hora-apertura" value="08:00">
                      </div>
                      <div class="col-6">
                        <input type="time" class="form-control" id="hora-cierre" value="20:00">
                      </div>
                    </div>
                  </div>
                  <div class="mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="fines-semana" checked>
                      <label class="form-check-label" for="fines-semana">
                        Abrir fines de semana
                      </label>
                    </div>
                  </div>
                  <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Guardar Configuración
                  </button>
                </form>
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
                  <div class="col-md-3 mb-2">
                    <button class="btn btn-info w-100" onclick="respaldarDatos()">
                      <i class="fas fa-database"></i> Respaldar Datos
                    </button>
                  </div>
                  <div class="col-md-3 mb-2">
                    <button class="btn btn-warning w-100" onclick="limpiarHistorial()">
                      <i class="fas fa-broom"></i> Limpiar Historial
                    </button>
                  </div>
                  <div class="col-md-3 mb-2">
                    <button class="btn btn-secondary w-100" onclick="exportarReporte()">
                      <i class="fas fa-file-export"></i> Exportar Reporte
                    </button>
                  </div>
                  <div class="col-md-3 mb-2">
                    <button class="btn btn-outline-danger w-100" onclick="reiniciarSistema()">
                      <i class="fas fa-power-off"></i> Reiniciar Sistema
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- ============ MODALES ============ -->
  
  <!-- Modal Agregar/Editar Servicio -->
  <div class="modal fade" id="modalAgregarServicio" tabindex="-1">
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

  <!-- Modal Editar Cliente -->
  <div class="modal fade" id="modalEditarCliente" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-edit"></i> Editar Cliente
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="form-cliente">
            <input type="hidden" id="cliente-id">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Patente</label>
                <input type="text" class="form-control" id="cliente-patente" readonly style="background-color: #f8f9fa;">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre del Cliente</label>
                <input type="text" class="form-control" id="cliente-nombre">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Tipo de Vehículo</label>
                <select class="form-select" id="cliente-vehiculo">
                  <option value="">Seleccionar...</option>
                  <option value="Auto">Auto</option>
                  <option value="Camioneta">Camioneta</option>
                  <option value="SUV">SUV</option>
                  <option value="Motocicleta">Motocicleta</option>
                  <option value="Furgón">Furgón</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Estado de Pago</label>
                <select class="form-select" id="cliente-estado">
                  <option value="pendiente">❌ Pendiente</option>
                  <option value="pagado">✅ Pagado</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Notas</label>
              <textarea class="form-control" id="cliente-notas" rows="2" 
                        placeholder="Notas adicionales sobre el cliente..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" onclick="guardarCliente()">
            <i class="fas fa-save"></i> Guardar Cambios
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/admin.js"></script>
</body>
</html>