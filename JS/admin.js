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
  // Crear nueva API específica para clientes mensuales
  fetch('../api/api_clientes_mensuales.php')
    .then(response => response.json())
    .then(data => {
      const tbody = document.getElementById('tabla-clientes');
      
      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay clientes mensuales registrados</td></tr>';
        return;
      }
      
      tbody.innerHTML = data.map(cliente => {
        // Determinar estado basado en las fechas del plan
        const hoy = new Date();
        const inicioPlan = new Date(cliente.inicio_plan);
        const finPlan = new Date(cliente.fin_plan);
        
        let estadoPago = 'pendiente';
        if (hoy >= inicioPlan && hoy <= finPlan) {
          estadoPago = 'pagado'; // Plan activo
        } else if (hoy > finPlan) {
          estadoPago = 'vencido'; // Plan vencido
        }
        
        const badgeEstado = estadoPago === 'pagado' 
          ? '<span class="badge bg-success">✅ Activo</span>' 
          : estadoPago === 'vencido'
          ? '<span class="badge bg-danger">❌ Vencido</span>'
          : '<span class="badge bg-warning">⏳ Pendiente</span>';
        
        const fechaInicio = inicioPlan.toLocaleDateString('es-CL');
        const fechaFin = finPlan.toLocaleDateString('es-CL');
        
        return `
          <tr class="${estadoPago === 'vencido' ? 'table-danger' : estadoPago === 'pendiente' ? 'table-warning' : ''}">
            <td>${badgeEstado}</td>
            <td><strong>${cliente.patente}</strong></td>
            <td>${cliente.nombres} ${cliente.apellidos}</td>
            <td>
              <i class="fas fa-car text-muted"></i> ${cliente.fono || 'No especificado'}
            </td>
            <td><small>Plan Mensual</small></td>
            <td>
              <small>
                <strong>Inicio:</strong> ${fechaInicio}<br>
                <strong>Fin:</strong> ${fechaFin}
              </small>
            </td>
            <td><strong class="text-success">Mensual</strong></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="editarClienteMensual(${cliente.idclientes})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-info" onclick="renovarPlan(${cliente.idclientes})">
                <i class="fas fa-calendar-plus"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="eliminarClienteMensual(${cliente.idclientes})">
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
        '<tr><td colspan="8" class="text-center text-danger py-4">Error cargando clientes mensuales</td></tr>';
    });
}

function cargarEstadisticas() {
  fetch('../api/api_clientes_mensuales.php')
    .then(response => response.json())
    .then(data => {
      const hoy = new Date();
      
      const activos = data.filter(c => {
        const fin = new Date(c.fin_plan);
        return hoy <= fin;
      }).length;
      const vencidos = data.filter(c => {
        const fin = new Date(c.fin_plan);
        return hoy > fin;
      }).length;
      const totalClientes = data.length;
      const ingresosMes = data.reduce((sum, c) => sum + (parseFloat(c.total) || 0), 0);
      
      document.getElementById('clientes-activos').textContent = activos;
      document.getElementById('clientes-vencidos').textContent = vencidos;
      document.getElementById('total-clientes').textContent = totalClientes;
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