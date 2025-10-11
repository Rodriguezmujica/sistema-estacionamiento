// Variables globales
let serviciosDisponibles = [];
let ticketExistenteData = null; // Guarda información del ticket existente

// Función para mostrar alertas
function mostrarAlerta(mensaje, tipo = 'info') {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    ${mensaje}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  // Insertar al inicio del main
  const main = document.querySelector('main');
  main.insertBefore(alertDiv, main.firstChild);
  
  // Auto-remover después de 5 segundos
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove();
    }
  }, 5000);
}

// Verificar si viene una patente desde el modal de modificar
function verificarPatenteURL() {
  const urlParams = new URLSearchParams(window.location.search);
  const patenteURL = urlParams.get('patente');
  
  if (patenteURL) {
    document.getElementById('patente-lavado').value = patenteURL;
    verificarTicketExistente(patenteURL);
    
    // Scroll al formulario
    document.querySelector('#form-lavado').scrollIntoView({ behavior: 'smooth' });
  }
}

// Verificar si existe un ticket activo para la patente
function verificarTicketExistente(patente) {
  if (!patente) {
    patente = document.getElementById('patente-lavado').value.trim().toUpperCase();
  }
  
  if (!patente) {
    mostrarAlerta('Ingrese una patente para verificar', 'warning');
    return;
  }
  
  console.log('🔍 Verificando ticket para patente:', patente);
  
  fetch('../api/verificar-patente.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ patente })
  })
    .then(res => res.json())
    .then(data => {
      console.log('📡 Respuesta de verificar-patente:', data);
      
      const alertaDiv = document.getElementById('alerta-ticket-existente');
      const mensajeSpan = document.getElementById('mensaje-ticket-existente');
      
      if (data.success && data.existe) {
        ticketExistenteData = data.registro;
        console.log('✅ Ticket existente guardado:', ticketExistenteData);
        const esLavado = data.registro.servicio.toLowerCase().includes('lavado');
        
        if (esLavado) {
          alertaDiv.className = 'alert alert-danger mb-3';
          mensajeSpan.innerHTML = `
            <strong>⚠️ ¡ATENCIÓN! Esta patente ya tiene un lavado activo:</strong> ${data.registro.servicio}<br>
            <small>Fecha ingreso: ${new Date(data.registro.fecha_ingreso).toLocaleString('es-CL')}</small><br>
            <strong class="text-danger">⚠️ Si continúa, SE MODIFICARÁ este lavado (NO se creará uno nuevo)</strong>
          `;
        } else {
          alertaDiv.className = 'alert alert-info mb-3';
          mensajeSpan.innerHTML = `
            <strong>ℹ️ Ticket existente encontrado:</strong> ${data.registro.servicio}<br>
            <small>Fecha ingreso: ${new Date(data.registro.fecha_ingreso).toLocaleString('es-CL')}</small><br>
            <em class="text-success">✅ Se modificará este ticket a servicio de lavado</em>
          `;
        }
        
        alertaDiv.classList.remove('d-none');
      } else {
        ticketExistenteData = null;
        console.log('ℹ️ No hay ticket existente');
        alertaDiv.className = 'alert alert-success mb-3';
        mensajeSpan.innerHTML = `
          <strong>No hay tickets activos</strong><br>
          <em>✅ Se registrará como nuevo ingreso de lavado</em>
        `;
        alertaDiv.classList.remove('d-none');
      }
    })
    .catch(error => {
      console.error('Error verificando patente:', error);
      mostrarAlerta('Error al verificar ticket: ' + error.message, 'danger');
    });
}

// Cargar servicios de lavado
function cargarServicios() {
  fetch('../api/api_servicios_lavado.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        throw new Error(data.error || 'La API de servicios devolvió un error.');
      }
      
      serviciosDisponibles = data.data;
      const select = document.getElementById('tipo-lavado');
      select.innerHTML = '<option value="">Seleccionar servicio...</option>';
      
      // Filtrar solo los servicios activos para mostrarlos en la lista
      const serviciosActivos = serviciosDisponibles.filter(s => parseInt(s.activo) === 1);

      serviciosActivos.forEach(servicio => {
        const option = document.createElement('option');
        option.value = servicio.idtipo_ingresos;
        const precio = parseFloat(servicio.precio) || 0;
        option.textContent = `${servicio.nombre_servicio} ($${precio.toLocaleString('es-CL')})`;
        select.appendChild(option);
      });
    })
    .catch(error => {
      console.error('Error al cargar servicios:', error);
      mostrarAlerta(`Error al cargar servicios de lavado: ${error.message}`, 'danger');
    });
}

// Consultar historial de patente
function consultarHistorial() {
  console.log('🔍 Función consultarHistorial ejecutada');
  
  const patente = document.getElementById('patente-consulta').value.trim().toUpperCase();
  console.log('📋 Patente ingresada:', patente);
  
  if (!patente) {
    console.log('⚠️ Patente vacía, mostrando alerta');
    mostrarAlerta('Ingresa una patente válida', 'warning');
    return;
  }
  
  // Mostrar loading
  const resultadoDiv = document.getElementById('resultado-consulta');
  const infoDiv = document.getElementById('info-vehiculo');
  resultadoDiv.style.display = 'block';
  resultadoDiv.classList.remove('d-none');
  infoDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';
  console.log('🔄 Mostrando estado de carga');
  
  // Llamada real a la API
  console.log('🌐 Realizando llamada a la API...');
  fetch('../api/historial-lavados.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ patente: patente })
  })
  .then(response => {
    console.log('📡 Respuesta recibida:', response.status, response.statusText);
    return response.json();
  })
  .then(data => {
    console.log('📊 Datos recibidos:', data);
    if (data.success) {
      if (data.total_lavados === 0) {
        console.log('❌ No se encontraron lavados');
        infoDiv.innerHTML = `
          <div class="text-center text-muted">
            <i class="fas fa-info-circle"></i> No se encontró historial de lavados para la patente ${data.patente}
          </div>
        `;
      } else {
        console.log('✅ Lavados encontrados:', data.total_lavados);
        const ultimo = data.ultimo_lavado;
        infoDiv.innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <p><strong>Patente:</strong> ${data.patente}</p>
              <p><strong>Último lavado:</strong> ${new Date(ultimo.fecha).toLocaleDateString('es-CL')}</p>
              <p><strong>Total cobrado:</strong> $${(ultimo.total || (ultimo.precio + ultimo.precio_extra)).toLocaleString('es-CL')}</p>
            </div>
            <div class="col-md-6">
              <p><strong>Último servicio:</strong> ${ultimo.servicio}</p>
              <p><strong>Total lavados:</strong> ${data.total_lavados}</p>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-12">
              <p><strong>Descripción:</strong> ${ultimo.descripcion || 'Sin descripción adicional'}</p>
              ${ultimo.precio_extra > 0 ? `
                <div class="alert alert-info mt-2">
                  <strong>Desglose del precio:</strong><br>
                  • Precio base: $${parseInt(ultimo.precio).toLocaleString('es-CL')}<br>
                  • Precio extra: $${parseInt(ultimo.precio_extra).toLocaleString('es-CL')}<br>
                  • <strong>Total: $${parseInt(ultimo.total || (ultimo.precio + ultimo.precio_extra)).toLocaleString('es-CL')}</strong>
                </div>
              ` : ''}
            </div>
          </div>
        `;

        // Llenar tabla historial
        const tbody = document.querySelector('#tabla-historial tbody');
        tbody.innerHTML = '';

        data.historial.forEach(item => {
          tbody.innerHTML += `
            <tr>
              <td>${new Date(item.fecha_ingreso).toLocaleString('es-CL')}</td>
              <td>${item.tipo_servicio}</td>
              <td>$${parseInt(item.precio || 0).toLocaleString('es-CL')}</td>
              <td>$${parseInt(item.total || 0).toLocaleString('es-CL')}</td>
              <td>${item.descripcion_extra || '—'}</td>
            </tr>
          `;
        });
      }
    } else {
      console.log('❌ Error en la respuesta:', data.error);
      infoDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
    }
  })
  .catch(error => {
    console.error('💥 Error en la consulta:', error);
    infoDiv.innerHTML = `<div class="alert alert-danger">Error al consultar historial: ${error.message}</div>`;
  });
}

// Cargar lavados pendientes
function cargarLavadosPendientes() {
  const pendientesDiv = document.getElementById('lavados-pendientes');
  
  fetch('../api/api_reporte.php')
    .then(response => response.json())
    .then(data => {
      const lavadosPendientes = data.filter(item => item.lavado === 'Sí');
      
      if (lavadosPendientes.length === 0) {
        pendientesDiv.innerHTML = '<div class="text-center text-muted">No hay lavados pendientes de cobro</div>';
        return;
      }
      
      pendientesDiv.innerHTML = lavadosPendientes.map(lavado => {
        let motivos = [];
        if (lavado.motivos_extra) {
          try {
            let motivosStr = lavado.motivos_extra.replace(/\\"/g, '"').replace(/^"|"$/g, '');
            motivos = JSON.parse(motivosStr);
          } catch (e) {
            console.log('Error parseando motivos:', e);
            motivos = [];
          }
        }
        
        return `
          <div class="card card-historial mb-3 border-warning">
            <div class="card-body">
              <div class="row">
                <div class="col-md-2">
                  <h6 class="card-title">${lavado.patente}</h6>
                  <small class="text-muted">${new Date(lavado.fecha_ingreso).toLocaleString('es-CL')}</small>
                </div>
                <div class="col-md-3">
                  <p class="mb-1"><strong>Servicio:</strong></p>
                  <p class="mb-0">${lavado.tipo_servicio}</p>
                </div>
                <div class="col-md-2">
                  <p class="mb-1"><strong>Total:</strong></p>
                  <p class="mb-0 text-success">$${lavado.total.toLocaleString('es-CL')}</p>
                </div>
                <div class="col-md-3">
                  <p class="mb-1"><strong>Motivos extra:</strong></p>
                  <div>
                    ${Array.isArray(motivos) && motivos.length > 0 ? 
                      motivos.map(motivo => `<span class="badge bg-warning badge-motivo">${motivo}</span>`).join(' ') :
                      '<span class="text-muted">Ninguno</span>'
                    }
                  </div>
                </div>
              </div>
              ${lavado.descripcion_extra ? `<div class="row mt-2"><div class="col-12"><small class="text-muted"><strong>Descripción:</strong> ${lavado.descripcion_extra}</small></div></div>` : ''}
            </div>
          </div>
        `;
      }).join('');
    })
    .catch(error => {
      console.error('Error:', error);
      pendientesDiv.innerHTML = `<div class="alert alert-danger">Error al cargar lavados pendientes: ${error.message}</div>`;
    });
}

// Función para cobrar un lavado
function cobrarLavado(idIngreso, patente) {
  if (confirm(`¿Confirmar el cobro del lavado para la patente ${patente}?`)) {
    const formData = new FormData();
    formData.append('id_ingreso', idIngreso);
    formData.append('patente', patente);
    formData.append('metodo_pago', 'EFECTIVO');
    
    fetch('../api/cobrar-lavado.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        mostrarAlerta('✅ Lavado cobrado correctamente', 'success');
        cargarLavadosPendientes();
      } else {
        mostrarAlerta('❌ Error al cobrar lavado: ' + data.error, 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarAlerta('❌ Error al cobrar lavado: ' + error.message, 'danger');
    });
  }
}

// Manejar envío del formulario
function manejarEnvioFormulario(event) {
  event.preventDefault();
  
  const patente = document.getElementById('patente-lavado').value.trim().toUpperCase();
  const tipoLavado = document.getElementById('tipo-lavado').value;
  const nombreCliente = document.getElementById('nombre-cliente-lavado').value.trim();
  const precioExtra = parseFloat(document.getElementById('precio-extra').value) || 0;
  const descripcion = document.getElementById('descripcion-extra').value.trim();
  
  // Recopilar motivos seleccionados
  const motivos = [];
  const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
  checkboxes.forEach(checkbox => {
    motivos.push(checkbox.value);
  });
  
  if (!patente || !tipoLavado) {
    mostrarAlerta('Patente y tipo de lavado son obligatorios', 'warning');
    return;
  }
  
  const servicioSeleccionado = serviciosDisponibles.find(s => s.idtipo_ingresos == tipoLavado);
  const precioBase = servicioSeleccionado ? parseFloat(servicioSeleccionado.precio) : 0;
  const precioTotal = precioBase + precioExtra;
  
  // DEBUG: Log del estado actual
  console.log('🔍 Estado al enviar formulario:');
  console.log('ticketExistenteData:', ticketExistenteData);
  
  // Determinar si es modificación o registro nuevo
  const accion = ticketExistenteData ? 'modificar' : 'registrar';
  const textoAccion = ticketExistenteData ? 'modificación' : 'registro';
  
  const resumen = `
    ${ticketExistenteData ? '🔄 MODIFICACIÓN' : '✨ NUEVO REGISTRO'} del lavado:
    • Patente: ${patente}
    ${ticketExistenteData ? `• Ticket actual: ${ticketExistenteData.servicio}` : ''}
    • Nuevo servicio: ${servicioSeleccionado?.nombre_servicio || 'N/A'}
    • Precio base: $${precioBase.toLocaleString('es-CL')}
    • Precio extra: $${precioExtra.toLocaleString('es-CL')}
    • Total: $${precioTotal.toLocaleString('es-CL')}
    • Motivos extra: ${motivos.length > 0 ? motivos.join(', ') : 'Ninguno'}
    • Cliente: ${nombreCliente || 'No registrado'}
  `;
  
  if (confirm(`${resumen}\n\n¿Confirmar esta operación?`)) {
    const formData = new FormData();
    formData.append('patente', patente);
    formData.append('id_servicio', tipoLavado);
    formData.append('nombre_cliente', nombreCliente);
    formData.append('precio_extra', precioExtra);
    formData.append('motivos_extra', JSON.stringify(motivos));
    formData.append('descripcion_extra', descripcion);
    
    // Si hay ticket existente, agregar flag de modificación
    if (ticketExistenteData) {
      formData.append('modificar_ticket', '1');
      formData.append('id_ticket_existente', ticketExistenteData.id);
      console.log('🔄 Enviando como MODIFICACIÓN - ID:', ticketExistenteData.id);
    } else {
      console.log('✨ Enviando como NUEVO REGISTRO');
    }
    
    // Mostrar todos los datos que se envían
    console.log('📤 Datos enviados al servidor:');
    for (let [key, value] of formData.entries()) {
      console.log(`  ${key}:`, value);
    }
    
    fetch('../api/registrar-lavado.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      console.log('📥 Respuesta del servidor:', data);
      if (data.success) {
        const mensaje = ticketExistenteData 
          ? '✅ Ticket modificado a lavado correctamente' 
          : '✅ Lavado registrado correctamente';
        mostrarAlerta(mensaje, 'success');
        
        // Limpiar formulario y estado
        event.target.reset();
        document.getElementById('precio-extra').value = 0;
        document.getElementById('alerta-ticket-existente').classList.add('d-none');
        ticketExistenteData = null;
        
        cargarLavadosPendientes();
      } else {
        mostrarAlerta('❌ Error al procesar: ' + data.error, 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarAlerta('❌ Error de conexión: ' + error.message, 'danger');
    });
  }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 JavaScript cargado correctamente');
  console.log('🔍 Configurando event listeners...');
  
  cargarServicios();
  cargarLavadosPendientes();
  
  // Verificar si viene patente por URL (desde modal de modificar)
  verificarPatenteURL();
  
  const btnConsultar = document.getElementById('btn-consultar-historial');
  const inputPatente = document.getElementById('patente-consulta');
  const btnVerificarPatente = document.getElementById('btn-verificar-patente');
  
  console.log('🔘 Botón consultar encontrado:', !!btnConsultar);
  console.log('📝 Input patente encontrado:', !!inputPatente);
  
  if (btnConsultar) {
    btnConsultar.addEventListener('click', consultarHistorial);
    console.log('✅ Event listener agregado al botón consultar');
  } else {
    console.error('❌ No se encontró el botón btn-consultar-historial');
  }
  
  // Botón de verificar ticket existente
  if (btnVerificarPatente) {
    btnVerificarPatente.addEventListener('click', () => {
      verificarTicketExistente();
    });
  }
  
  // Verificación automática al cambiar la patente
  const inputPatenteLavado = document.getElementById('patente-lavado');
  let verificacionTimeout = null;
  
  if (inputPatenteLavado) {
    inputPatenteLavado.addEventListener('blur', function() {
      const patente = this.value.trim().toUpperCase();
      console.log('👁️ Blur event - Patente:', patente);
      if (patente) {
        // Cancelar timeout pendiente para evitar duplicados
        if (verificacionTimeout) {
          clearTimeout(verificacionTimeout);
          verificacionTimeout = null;
        }
        verificarTicketExistente(patente);
      }
    });
    
    // También verificar mientras escribe (después de una pausa)
    inputPatenteLavado.addEventListener('input', function() {
      const patente = this.value.trim().toUpperCase();
      
      // Cancelar timeout anterior
      if (verificacionTimeout) {
        clearTimeout(verificacionTimeout);
      }
      
      if (patente) {
        // Solo verificar si la patente cambió respecto a ticketExistenteData
        const patenteExistente = ticketExistenteData ? ticketExistenteData.patente : null;
        
        if (patente !== patenteExistente) {
          // Esperar un momento para que el usuario termine de escribir
          verificacionTimeout = setTimeout(() => {
            const patenteActual = this.value.trim().toUpperCase();
            if (patenteActual && patenteActual === patente) {
              console.log('⌨️ Input event (timeout) - Verificando:', patente);
              verificarTicketExistente(patente);
            }
          }, 800); // Aumentado a 800ms para dar más tiempo
        } else {
          console.log('⏭️ Patente sin cambios, saltando verificación');
        }
      } else {
        // Limpiar alerta si la patente está vacía
        ticketExistenteData = null;
        document.getElementById('alerta-ticket-existente').classList.add('d-none');
        console.log('🧹 Patente vacía, limpiando estado');
      }
    });
  }
  
  document.getElementById('form-lavado').addEventListener('submit', manejarEnvioFormulario);
  
  if (inputPatente) {
    inputPatente.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        consultarHistorial();
      }
    });
    console.log('✅ Event listener agregado al input patente');
  } else {
    console.error('❌ No se encontró el input patente-consulta');
  }
});