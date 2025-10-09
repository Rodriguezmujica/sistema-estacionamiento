document.addEventListener('DOMContentLoaded', function() {
  cargarReportesUnificados();
  if (document.getElementById('fecha-hora')) {
    actualizarFechaHora();
    setInterval(actualizarFechaHora, 1000);
  }
  
  // Establecer fechas por defecto (últimos 7 días)
  establecerFechasPorDefecto();
  
  // Actualizar cada 30 segundos
  setInterval(cargarReportesUnificados, 30000);
});

function establecerFechasPorDefecto() {
  // Solo establecer fechas si estamos en la página de reportes
  const fechaDesdeEl = document.getElementById('fecha-desde');
  const fechaHastaEl = document.getElementById('fecha-hasta');
  
  if (!fechaDesdeEl || !fechaHastaEl) {
    return; // No estamos en la página de reportes
  }
  
  const hoy = new Date();
  const hace7Dias = new Date(hoy);
  hace7Dias.setDate(hoy.getDate() - 7);
  
  // Formatear fechas para input type="date"
  const fechaHasta = hoy.toISOString().split('T')[0];
  const fechaDesde = hace7Dias.toISOString().split('T')[0];
  
  fechaDesdeEl.value = fechaDesde;
  fechaHastaEl.value = fechaHasta;
}

function cargarReportesUnificados() {
  // Determinar la ruta correcta según la página actual
  const isReportesPage = window.location.pathname.includes('reporte.html');
  const apiPath = isReportesPage ? '../api/api_reportes_unificados.php' : './api/api_reportes_unificados.php';
  
  fetch(apiPath)
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Datos recibidos:', data);
      if (data.success) {
        // --- Actualizar estadísticas del Dashboard (index.php) ---
        const totalHoyEl = document.getElementById('total-hoy');
        const ingresosHoyEl = document.getElementById('ingresos-hoy');
        if (totalHoyEl) totalHoyEl.textContent = data.diario.servicios;
        if (ingresosHoyEl) ingresosHoyEl.textContent = '$' + data.diario.ingresos.toLocaleString('es-CL');

        // --- Actualizar estadísticas de la página de Reportes (reporte.html) ---
        const serviciosDiarioEl = document.getElementById('servicios-diario');
        const ingresosDiarioEl = document.getElementById('ingresos-diario');
        if (serviciosDiarioEl) serviciosDiarioEl.textContent = data.diario.servicios;
        if (ingresosDiarioEl) ingresosDiarioEl.textContent = data.diario.ingresos.toLocaleString('es-CL');
        
        const serviciosMensualLvEl = document.getElementById('servicios-mensual-lv');
        const ingresosMensualLvEl = document.getElementById('ingresos-mensual-lv');
        if (serviciosMensualLvEl) serviciosMensualLvEl.textContent = data.mensual_lv.servicios;
        if (ingresosMensualLvEl) ingresosMensualLvEl.textContent = data.mensual_lv.ingresos.toLocaleString('es-CL');
        
        const serviciosMensualCompletoEl = document.getElementById('servicios-mensual-completo');
        const ingresosMensualCompletoEl = document.getElementById('ingresos-mensual-completo');
        if (serviciosMensualCompletoEl) serviciosMensualCompletoEl.textContent = data.mensual_completo.servicios;
        if (ingresosMensualCompletoEl) ingresosMensualCompletoEl.textContent = data.mensual_completo.ingresos.toLocaleString('es-CL');
        
        // --- Actualizar tablas (si existen en la página) ---
        const tablaReporteEl = document.querySelector('#tabla-reporte tbody');
        if (tablaReporteEl) {
          cargarTablaActivos(data.servicios_activos);
        }
        
        // Ya no necesitamos cargar últimos ingresos aquí
        
      } else {
        console.error('Error:', data.error);
      }
    })
    .catch(error => {
      console.error('Error cargando reportes:', error);
      // Mostrar mensaje de error en la página
      const tbody = document.querySelector('#tabla-reporte tbody');
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error cargando datos: ' + error.message + '</td></tr>';
      }
      
      const tablaIngresos = document.getElementById('tabla-ingresos-body');
      if (tablaIngresos) {
        tablaIngresos.innerHTML = '<tr><td colspan="4" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error cargando datos</td></tr>';
      }
    });
}

function cargarTablaActivos(servicios) {
  const tbody = document.querySelector('#tabla-reporte tbody');
  
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

function consultarPorFechas() {
  const fechaDesde = document.getElementById('fecha-desde').value;
  const fechaHasta = document.getElementById('fecha-hasta').value;
  
  if (!fechaDesde || !fechaHasta) {
    alert('Por favor selecciona ambas fechas');
    return;
  }
  
  if (new Date(fechaDesde) > new Date(fechaHasta)) {
    alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
    return;
  }
  
  // Mostrar loading
  const tbody = document.getElementById('tabla-fechas-body');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Consultando...</td></tr>';
  
  // Realizar consulta
  fetch(`../api/api_consulta_fechas.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`)
    .then(response => response.json())
    .then(data => {
      console.log('Consulta por fechas:', data);
      if (data.success) {
        // Mostrar resumen
        document.getElementById('total-servicios-fecha').textContent = data.resumen.total_servicios;
        document.getElementById('total-ingresos-fecha').textContent = data.resumen.total_ingresos.toLocaleString('es-CL');
        document.getElementById('resumen-fechas').style.display = 'block';
        
        // Mostrar categorías
        cargarCategorias(data.categorias, data.servicios_detalle);
      } else {
        alert('Error: ' + data.error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error en la consulta</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al consultar: ' + error.message);
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error en la consulta</td></tr>';
    });
}

function cargarCategorias(categorias, serviciosDetalle) {
  const tbody = document.getElementById('tabla-fechas-body');
  
  if (categorias.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No se encontraron servicios en el rango de fechas seleccionado</td></tr>';
    return;
  }
  
  tbody.innerHTML = categorias.map((categoria, index) => {
    const icono = obtenerIconoCategoria(categoria.categoria);
    return `
      <tr>
        <td>
          <span class="badge bg-primary me-2">${icono}</span>
          <strong>${categoria.categoria}</strong>
        </td>
        <td>
          <small class="text-muted">${categoria.tipos_servicios}</small>
        </td>
        <td class="text-center">
          <span class="badge bg-info">${categoria.cantidad_servicios}</span>
        </td>
        <td class="text-end">
          <strong>$${parseInt(categoria.total_categoria).toLocaleString('es-CL')}</strong>
        </td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-primary" onclick="verDetallesCategoria('${categoria.categoria}')">
            <i class="fas fa-eye"></i> Ver
          </button>
        </td>
      </tr>
    `;
  }).join('');
  
  // Guardar servicios detalle para usar en verDetallesCategoria
  window.serviciosDetalle = serviciosDetalle;
}

function obtenerIconoCategoria(categoria) {
  const iconos = {
    'Lavados': '🧽',
    'Estacionamiento x Minuto': '🅿️',
    'Errores de Ingreso': '❌',
    'Motos': '🏍️',
    'Promociones': '🎁',
    'Otros Servicios': '🔧'
  };
  return iconos[categoria] || '📋';
}

function verDetallesCategoria(categoria) {
  const serviciosFiltrados = window.serviciosDetalle.filter(s => s.categoria === categoria);
  
  const tbody = document.getElementById('tabla-detalles-body');
  const container = document.getElementById('detalles-container');
  
  if (serviciosFiltrados.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay servicios en esta categoría</td></tr>';
  } else {
    tbody.innerHTML = serviciosFiltrados.map((servicio, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${servicio.patente}</td>
        <td>${new Date(servicio.fecha_salida).toLocaleString('es-CL')}</td>
        <td>${servicio.nombre_servicio}</td>
        <td class="text-end">$${parseInt(servicio.total).toLocaleString('es-CL')}</td>
      </tr>
    `).join('');
  }
  
  // Mostrar el contenedor de detalles
  container.style.display = 'block';
  
  // Scroll hacia los detalles
  container.scrollIntoView({ behavior: 'smooth' });
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
