document.addEventListener('DOMContentLoaded', function() {
  cargarClientes();
  cargarServicios();
  cargarEstadisticas();
  
  // Actualizar cada 30 segundos
  setInterval(() => {
    cargarClientes();
    cargarEstadisticas();
  }, 30000);
});

// ============ GESTIÓN DE CLIENTES ============
function cargarClientes() {
  fetch('../api/api_reporte.php')
    .then(response => response.json())
    .then(data => {
      const tbody = document.getElementById('tabla-clientes');
      
      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay clientes registrados</td></tr>';
        return;
      }
      
      tbody.innerHTML = data.map(cliente => {
        const estadoPago = cliente.fecha_salida ? 'pagado' : 'pendiente';
        const badgeEstado = estadoPago === 'pagado' 
          ? '<span class="badge bg-success">✅ Pagado</span>' 
          : '<span class="badge bg-danger">❌ Pendiente</span>';
        
        const tipoVehiculo = determinarTipoVehiculo(cliente.patente);
        const fechaMostrar = new Date(cliente.fecha_ingreso).toLocaleDateString('es-CL');
        const total = cliente.total ? `$${cliente.total.toLocaleString('es-CL')}` : 'Sin cobrar';
        
        return `
          <tr class="${estadoPago === 'pendiente' ? 'table-warning' : ''}">
            <td>${badgeEstado}</td>
            <td><strong>${cliente.patente}</strong></td>
            <td>${cliente.nombre_cliente || 'Sin nombre'}</td>
            <td>
              <i class="fas fa-car text-muted"></i> ${tipoVehiculo}
            </td>
            <td><small>${cliente.tipo_servicio}</small></td>
            <td><small>${fechaMostrar}</small></td>
            <td><strong class="${estadoPago === 'pagado' ? 'text-success' : 'text-warning'}">${total}</strong></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(${cliente.idautos_estacionados})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(${cliente.idautos_estacionados})">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    })
    .catch(error => {
      console.error('Error cargando clientes:', error);
      document.getElementById('tabla-clientes').innerHTML = 
        '<tr><td colspan="8" class="text-center text-danger py-4">Error cargando datos</td></tr>';
    });
}

function determinarTipoVehiculo(patente) {
  // Lógica simple para determinar tipo de vehículo por patente
  if (patente.includes('MC') || patente.includes('MT')) return 'Motocicleta';
  if (patente.includes('CM') || patente.includes('TR')) return 'Camioneta';
  return 'Auto';
}

function cargarEstadisticas() {
  fetch('../api/api_reporte.php')
    .then(response => response.json())
    .then(data => {
      const pagados = data.filter(c => c.fecha_salida).length;
      const pendientes = data.filter(c => !c.fecha_salida).length;
      const totalServicios = data.length;
      const ingresosMes = data.reduce((sum, c) => sum + (parseFloat(c.total) || 0), 0);
      
      document.getElementById('clientes-pagados').textContent = pagados;
      document.getElementById('clientes-pendientes').textContent = pendientes;
      document.getElementById('total-servicios').textContent = totalServicios;
      document.getElementById('ingresos-mes').textContent = `$${ingresosMes.toLocaleString('es-CL')}`;
    })
    .catch(error => console.error('Error cargando estadísticas:', error));
}

// ============ GESTIÓN DE SERVICIOS ============
function cargarServicios() {
  fetch('../api/api_servicios_lavado.php')
    .then(response => response.json())
    .then(servicios => {
      const tbody = document.getElementById('tabla-servicios');
      
      if (servicios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay servicios registrados</td></tr>';
        return;
      }
      
      tbody.innerHTML = servicios.map(servicio => `
        <tr>
          <td><strong>#${servicio.idtipo_ingresos}</strong></td>
          <td>${servicio.nombre_servicio}</td>
          <td><strong class="text-success">$${parseFloat(servicio.precio).toLocaleString('es-CL')}</strong></td>
          <td><span class="badge bg-success">Activo</span></td>
          <td><small class="text-muted">Hoy</small></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" onclick="editarServicio(${servicio.idtipo_ingresos})">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(${servicio.idtipo_ingresos})">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    })
    .catch(error => {
      console.error('Error cargando servicios:', error);
      document.getElementById('tabla-servicios').innerHTML = 
        '<tr><td colspan="6" class="text-center text-danger py-4">Error cargando servicios</td></tr>';
    });
}

// ============ FUNCIONES DE MODAL ============
function editarCliente(id) {
  // Implementar edición de cliente
  const modal = new bootstrap.Modal(document.getElementById('modalEditarCliente'));
  modal.show();
}

function editarServicio(id) {
  // Implementar edición de servicio
  const modal = new bootstrap.Modal(document.getElementById('modalAgregarServicio'));
  document.getElementById('titulo-modal-servicio').textContent = 'Editar Servicio';
  modal.show();
}

function guardarServicio() {
  // Implementar guardado de servicio
  alert('Función guardarServicio() - Por implementar');
}

function guardarCliente() {
  // Implementar guardado de cliente
  alert('Función guardarCliente() - Por implementar');
}

// ============ FUNCIONES DE CONFIGURACIÓN ============
function exportarClientes() {
  alert('Exportando clientes...');
}

function respaldarDatos() {
  alert('Respaldando datos...');
}

function limpiarHistorial() {
  if (confirm('¿Está seguro de limpiar el historial? Esta acción no se puede deshacer.')) {
    alert('Limpiando historial...');
  }
}

function exportarReporte() {
  alert('Exportando reporte...');
}

function reiniciarSistema() {
  if (confirm('¿Está seguro de reiniciar el sistema?')) {
    alert('Reiniciando sistema...');
  }
}