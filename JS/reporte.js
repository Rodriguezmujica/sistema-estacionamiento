document.addEventListener('DOMContentLoaded', function() {
  cargarReportesUnificados();
  // Establecer fechas por defecto (últimos 7 días)
  establecerFechasPorDefecto();
  
  // Establecer fecha de cierre de caja (hoy por defecto)
  const fechaCierre = document.getElementById('fecha-cierre');
  if (fechaCierre) {
    fechaCierre.value = new Date().toISOString().split('T')[0];
  }
  
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
  let fechaDesde = document.getElementById('fecha-desde').value;
  let fechaHasta = document.getElementById('fecha-hasta').value;
  
  // Si el usuario no elige fechas, tomamos el día anterior
  if (!fechaDesde || !fechaHasta) {
    const ayer = new Date();
    ayer.setDate(ayer.getDate() - 1);
    
    const yyyy = ayer.getFullYear();
    const mm = String(ayer.getMonth() + 1).padStart(2, '0');
    const dd = String(ayer.getDate()).padStart(2, '0');
    
    fechaDesde = `${yyyy}-${mm}-${dd}`;
    fechaHasta = `${yyyy}-${mm}-${dd}`;
  }
  
  if (new Date(fechaDesde) > new Date(fechaHasta)) {
    alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
    return;
  }
  
  // Mostrar loading
  const tbody = document.getElementById('tabla-fechas-body');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Consultando...</td></tr>';
  
  console.log(`🔍 Consultando desde: ${fechaDesde} hasta: ${fechaHasta}`);
  
  // Realizar consulta
  fetch(`../api/api_consulta_fechas.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`)
    .then(response => {
      console.log('📡 Status de respuesta:', response.status);
      return response.text();
    })
    .then(responseText => {
      console.log('📄 Respuesta completa del servidor:', responseText);
      
      // Parsear JSON
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (jsonError) {
        const jsonMatch = responseText.match(/\{[\s\S]*\}/);
        if (!jsonMatch) {
          throw new Error('No se encontró JSON válido en la respuesta. Respuesta: ' + responseText.substring(0, 200));
        }
        data = JSON.parse(jsonMatch[0]);
      }
      
      console.log('📊 Datos JSON parseados:', data);
      
      if (data.success) {
        // DEBUG: Mostrar información
        if (data.debug) {
          console.log('🔍 DEBUG INFO:', data.debug);
          console.log(`📅 Rango consultado: ${data.debug.query_range}`);
          console.log(`📊 Servicios encontrados: ${data.debug.total_encontrados}`);
          console.log(`📂 Categorías: ${data.debug.categorias_count}`);
          
          if (data.debug.estacionamiento_info) {
            console.log(`🚗 Estacionamiento info:`, data.debug.estacionamiento_info);
          }
        }
        
        // ✅ VALIDACIÓN: Los números ahora son correctos
        console.log(`✅ Consulta exitosa: ${data.resumen.total_servicios} servicios, $${data.resumen.total_ingresos.toLocaleString()}`);
        
        // Mostrar resumen
        document.getElementById('total-servicios-fecha').textContent = data.resumen.total_servicios;
        document.getElementById('total-ingresos-fecha').textContent = data.resumen.total_ingresos.toLocaleString('es-CL');
        document.getElementById('resumen-fechas').style.display = 'block';
        
        // Mostrar categorías
        cargarCategorias(data.categorias, data.servicios_detalle);
      } else {
        console.error('❌ Error del servidor:', data.error);
        if (data.debug) {
          console.error('🔍 Debug del error:', data.debug);
        }
        alert('Error del servidor: ' + data.error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error en la consulta: ' + data.error + '</td></tr>';
      }
    })
    .catch(error => {
      console.error('❌ Error completo:', error);
      alert('Error al consultar: ' + error.message);
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error en la consulta: ' + error.message + '</td></tr>';
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
        <td>${new Date(servicio.fecha_salida_real || servicio.fecha_salida).toLocaleString('es-CL')}</td>
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

// ============================================
// CIERRE DE CAJA
// ============================================

let datoCierreCajaActual = null; // Variable global para guardar datos del cierre

async function generarCierreCaja() {
  const fechaCierre = document.getElementById('fecha-cierre').value;
  
  if (!fechaCierre) {
    alert('Por favor, seleccione una fecha para el cierre de caja');
    return;
  }
  
  try {
    const response = await fetch(`../api/api_cierre_caja.php?fecha=${fechaCierre}`);
    const result = await response.json();
    
    if (result.success) {
      datoCierreCajaActual = result; // Guardar para imprimir
      mostrarCierreCaja(result);
    } else {
      alert('Error al generar cierre de caja: ' + result.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error de conexión: ' + error.message);
  }
}

function mostrarCierreCaja(data) {
  // Mostrar contenedor
  const contenedor = document.getElementById('contenedor-cierre-caja');
  contenedor.classList.remove('d-none');
  
  // Actualizar título con fecha
  const fechaFormateada = new Date(data.fecha + 'T00:00:00').toLocaleDateString('es-CL', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  document.getElementById('fecha-cierre-titulo').textContent = 
    fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);
  
  // Resumen general
  document.getElementById('cierre-total-servicios').textContent = data.resumen.total_servicios;
  document.getElementById('cierre-total-ingresos').textContent = 
    '$' + parseInt(data.resumen.total_ingresos).toLocaleString('es-CL');
  
  // Desglose por método de pago
  const tbodyPago = document.getElementById('tabla-desglose-pago');
  const desglose = data.desglose_pago;
  
  let html = '';
  
  // Efectivo Manual
  if (desglose.efectivo_manual.total > 0) {
    html += `
      <tr>
        <td><strong>💵 Efectivo (Pago Manual)</strong></td>
        <td class="text-end"><strong>$${parseInt(desglose.efectivo_manual.total).toLocaleString('es-CL')}</strong></td>
        <td class="text-muted text-end">${desglose.efectivo_manual.cantidad} servicios</td>
      </tr>
    `;
  }
  
  // TUU Efectivo
  if (desglose.tuu_efectivo.total > 0) {
    html += `
      <tr>
        <td><strong>💵 Efectivo (TUU - Boleta Oficial)</strong></td>
        <td class="text-end"><strong>$${parseInt(desglose.tuu_efectivo.total).toLocaleString('es-CL')}</strong></td>
        <td class="text-muted text-end">${desglose.tuu_efectivo.cantidad} servicios</td>
      </tr>
    `;
  }
  
  // TUU Débito
  if (desglose.tuu_debito.total > 0) {
    html += `
      <tr>
        <td><strong>💳 Débito (TUU)</strong></td>
        <td class="text-end"><strong>$${parseInt(desglose.tuu_debito.total).toLocaleString('es-CL')}</strong></td>
        <td class="text-muted text-end">${desglose.tuu_debito.cantidad} servicios</td>
      </tr>
    `;
  }
  
  // TUU Crédito
  if (desglose.tuu_credito.total > 0) {
    html += `
      <tr>
        <td><strong>💳 Crédito (TUU)</strong></td>
        <td class="text-end"><strong>$${parseInt(desglose.tuu_credito.total).toLocaleString('es-CL')}</strong></td>
        <td class="text-muted text-end">${desglose.tuu_credito.cantidad} servicios</td>
      </tr>
    `;
  }
  
  // Transferencia
  if (desglose.transferencia.total > 0) {
    html += `
      <tr>
        <td><strong>🏦 Transferencia</strong></td>
        <td class="text-end"><strong>$${parseInt(desglose.transferencia.total).toLocaleString('es-CL')}</strong></td>
        <td class="text-muted text-end">${desglose.transferencia.cantidad} servicios</td>
      </tr>
    `;
  }
  
  tbodyPago.innerHTML = html;
  
  // Calcular totales
  const efectivoEnCaja = desglose.efectivo_manual.total + desglose.tuu_efectivo.total;
  const pagosElectronicos = desglose.tuu_debito.total + desglose.tuu_credito.total + desglose.transferencia.total;
  
  document.getElementById('efectivo-en-caja').textContent = 
    '$' + parseInt(efectivoEnCaja).toLocaleString('es-CL');
  
  document.getElementById('pagos-electronicos').textContent = 
    '$' + parseInt(pagosElectronicos).toLocaleString('es-CL');
  
  // Desglose por categorías
  const tbodyCategorias = document.getElementById('tabla-categorias-cierre');
  tbodyCategorias.innerHTML = data.categorias.map(cat => `
    <tr>
      <td><strong>${cat.categoria}</strong></td>
      <td class="text-end"><strong>$${parseInt(cat.total).toLocaleString('es-CL')}</strong></td>
      <td class="text-muted text-end">${cat.cantidad} servicios</td>
    </tr>
  `).join('');
  
  // Scroll al contenedor
  contenedor.scrollIntoView({ behavior: 'smooth' });
}

async function imprimirCierreCaja() {
  if (!datoCierreCajaActual) {
    alert('Primero debe generar el cierre de caja');
    return;
  }
  
  const desglose = datoCierreCajaActual.desglose_pago;
  
  // 🆕 INTENTAR CON NUEVO SERVICIO PRIMERO
  if (typeof PrintService !== 'undefined') {
    try {
      console.log('🆕 Imprimiendo cierre de caja con nuevo servicio...');
      console.log('📊 Datos del cierre:', datoCierreCajaActual);
      
      // Buscar totales de categorías específicas
      const totalEstacionamiento = datoCierreCajaActual.categorias.find(c => c.categoria.includes('Estacionamiento'))?.total || 0;
      const totalLavado = datoCierreCajaActual.categorias.find(c => c.categoria.includes('Lavado'))?.total || 0;
      const totalEfectivo = (desglose.efectivo_manual.total || 0) + (desglose.tuu_efectivo.total || 0);

      // Preparar datos para el nuevo servicio
      const datosCierre = {
        fecha: datoCierreCajaActual.fecha,
        hora: new Date().toLocaleTimeString('es-AR'),
        usuario: 'Usuario', // Ajustar si tienes la info del usuario
        efectivo_caja: totalEfectivo,
        total_electronico: (desglose.tuu_debito.total || 0) + (desglose.tuu_credito.total || 0) + (desglose.transferencia.total || 0),
        total_estacionamiento: totalEstacionamiento,
        total_lavado: totalLavado,
        total_general: datoCierreCajaActual.resumen.total_ingresos || 0,
        desglose_pago: datoCierreCajaActual.desglose_pago,
        desglose_categorias: datoCierreCajaActual.categorias
      };
      
      console.log('📝 Datos preparados para impresión:', datosCierre);
      
      const resultado = await PrintService.imprimirCierreCaja(datosCierre);
      
      console.log('📄 Resultado de impresión:', resultado);
      
      if (resultado.success) {
        alert('✅ Cierre de caja impreso correctamente');
        return;
      } else {
        console.warn('⚠️ Nuevo servicio respondió con error:', resultado.message);
        throw new Error('Fallback al método antiguo: ' + resultado.message);
      }
    } catch (errorNuevo) {
      console.error('❌ Error en nuevo servicio:', errorNuevo);
      console.warn('🔄 Usando método antiguo de impresión...');
    }
  } else {
    console.warn('⚠️ PrintService no está definido, usando método antiguo directamente');
  }
  
  // 🔄 FALLBACK: Método antiguo
  const formData = new FormData();
  formData.append('fecha', datoCierreCajaActual.fecha);
  formData.append('total_servicios', datoCierreCajaActual.resumen.total_servicios);
  formData.append('total_ingresos', datoCierreCajaActual.resumen.total_ingresos);
  formData.append('efectivo_manual', desglose.efectivo_manual.total);
  formData.append('tuu_efectivo', desglose.tuu_efectivo.total);
  formData.append('tuu_debito', desglose.tuu_debito.total);
  formData.append('tuu_credito', desglose.tuu_credito.total);
  formData.append('transferencia', desglose.transferencia.total);
  formData.append('categorias', JSON.stringify(datoCierreCajaActual.categorias));
  
  try {
    const response = await fetch('../ImpresionTermica/cierre_caja.php', {
      method: 'POST',
      body: formData
    });
    
    const resultado = await response.text();
    
    if (resultado.trim() === '1') {
      alert('✅ Cierre de caja impreso correctamente en la impresora térmica');
    } else {
      alert('⚠️ El cierre se generó pero hubo un problema con la impresión.\n\nVerifica que la impresora esté encendida y conectada.');
    }
  } catch (error) {
    console.error('Error imprimiendo:', error);
    alert('❌ Error al conectar con la impresora térmica.\n\nAsegúrate de que el servicio de impresión esté activo en el puerto 8080.');
  }
}
