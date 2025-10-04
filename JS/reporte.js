document.addEventListener('DOMContentLoaded', function() {
  cargarReportesUnificados();
  actualizarFechaHora();
  
  // Actualizar cada 30 segundos
  setInterval(cargarReportesUnificados, 30000);
  setInterval(actualizarFechaHora, 1000);
});

function cargarReportesUnificados() {
  fetch('../api/api_reportes_unificados.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Total Diario
        document.getElementById('servicios-diario').textContent = data.diario.servicios;
        document.getElementById('ingresos-diario').textContent = data.diario.ingresos.toLocaleString('es-CL');
        
        // Mensual L-V
        document.getElementById('servicios-mensual-lv').textContent = data.mensual_lv.servicios;
        document.getElementById('ingresos-mensual-lv').textContent = data.mensual_lv.ingresos.toLocaleString('es-CL');
        
        // Mensual Completo
        document.getElementById('servicios-mensual-completo').textContent = data.mensual_completo.servicios;
        document.getElementById('ingresos-mensual-completo').textContent = data.mensual_completo.ingresos.toLocaleString('es-CL');
        
        // Tabla de servicios activos
        cargarTablaActivos(data.servicios_activos);
        
        // Últimos ingresos (usar los primeros 10 servicios activos como ejemplo)
        cargarUltimosIngresos(data.servicios_activos.slice(0, 10));
        
      } else {
        console.error('Error:', data.error);
      }
    })
    .catch(error => {
      console.error('Error cargando reportes:', error);
    });
}

function cargarTablaActivos(servicios) {
  const tbody = document.getElementById('tabla-reporte');
  
  if (servicios.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay servicios activos</td></tr>';
    return;
  }
  
  tbody.innerHTML = servicios.map(servicio => `
    <tr>
      <td>${servicio.patente}</td>
      <td>${servicio.cliente}</td>
      <td>${servicio.nombre_servicio}</td>
      <td>${new Date(servicio.fecha_ingreso).toLocaleString('es-CL')}</td>
      <td>
        <span class="badge ${servicio.lavado === 'Sí' ? 'bg-warning' : 'bg-secondary'}">
          ${servicio.lavado}
        </span>
      </td>
    </tr>
  `).join('');
}

function cargarUltimosIngresos(ingresos) {
  const tbody = document.querySelector('#tabla-ingresos tbody');
  
  if (!tbody) return; // Si no existe la tabla, salir
  
  if (ingresos.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay ingresos registrados</td></tr>';
    return;
  }
  
  tbody.innerHTML = ingresos.map((ingreso, index) => `
    <tr>
      <td>${index + 1}</td>
      <td>${ingreso.patente}</td>
      <td>${new Date(ingreso.fecha_ingreso).toLocaleString('es-CL')}</td>
      <td>${ingreso.nombre_servicio}</td>
    </tr>
  `).join('');
}

function actualizarFechaHora() {
  const ahora = new Date();
  const fechaHora = ahora.toLocaleString('es-CL', {
    day: '2-digit',
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  });
  
  const elemento = document.getElementById('fecha-hora');
  if (elemento) {
    elemento.textContent = fechaHora;
  }
}
