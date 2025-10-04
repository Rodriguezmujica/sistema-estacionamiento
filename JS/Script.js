document.addEventListener('DOMContentLoaded', () => {
  
  // ========================================
  // 1. CONFIGURACIÓN Y VARIABLES GLOBALES
  // ========================================
  
  const CONFIG = {
    precioMinuto: 35,
    precioMinimo: 500,
    actualizacionInterval: 30000
  };
  
  let datosVehiculo = null;
  let intervalId = null;
  let ticketActual = null;
  let ticketCobroActual = null;

  // ========================================
  // 2. FUNCIONES UTILITARIAS
  // ========================================
  
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

  // Función para actualizar fecha y hora
  function actualizarFechaHora() {
    const ahora = new Date();
    const fechaHora = ahora.toLocaleString('es-CL', {
      timeZone: 'America/Santiago',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
    if (document.getElementById('fecha-hora')) {
      document.getElementById('fecha-hora').textContent = fechaHora;
    }
    if (document.getElementById('fecha-sistema')) {
      document.getElementById('fecha-sistema').textContent = fechaHora;
    }
  }

  // Función para cargar reporte (placeholder)
  function cargarReporte() {
    console.log('📊 Cargando reporte...');
  }

  // ========================================
  // 3. FORMULARIO DE INGRESOS
  // ========================================
  
  // Función para verificar si la patente ya existe
  function verificarPatenteDuplicada(patente) {
    fetch('./api/verificar-patente.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({ patente: patente })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.existe) {
        const registro = data.registro;
        const fechaIngreso = new Date(registro.fecha_ingreso).toLocaleString('es-CL');
        
        mostrarAlerta(`
          ⚠️ <strong>Patente duplicada:</strong><br>
          La patente <strong>${registro.patente}</strong> ya tiene un ingreso activo desde el ${fechaIngreso}<br>
          Servicio: <strong>${registro.servicio}</strong><br>
          <br>
          Debe procesar la salida antes de registrar un nuevo ingreso.
        `, 'warning');
        
        // Opcional: limpiar el campo o enfocarlo
        const patenteIngreso = document.getElementById('patente-ingreso');
        if (patenteIngreso) {
          patenteIngreso.value = '';
          patenteIngreso.focus();
        }
      }
    })
    .catch(error => {
      console.error('Error verificando patente:', error);
    });
  }

  // ========================================
  // 4. FUNCIONES DE LAVADOS (del archivo lavados.js)
  // ========================================
  
  // Cargar servicios de lavado
  function cargarServicios() {
    fetch('./api/api_servicios_lavado.php')
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
      })
      .then(servicios => {
        // Esta función se puede implementar más tarde si es necesaria
        console.log('Servicios cargados:', servicios);
      })
      .catch(error => {
        console.error('Error al cargar servicios:', error);
        // No mostrar alerta para evitar spam en la consola
      });
  }

  // Consultar historial de patente
  function consultarHistorial() {
    const patente = document.getElementById('patente-consulta').value.trim().toUpperCase();
    
    if (!patente) {
      mostrarAlerta('Ingresa una patente válida', 'warning');
      return;
    }
    
    // Mostrar loading
    const resultadoDiv = document.getElementById('resultado-consulta');
    const infoDiv = document.getElementById('info-vehiculo');
    if (resultadoDiv) {
      resultadoDiv.style.display = 'block';
    }
    if (infoDiv) {
      infoDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';
    }
    
    // Llamada real a la API
    fetch('./api/historial-lavados.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ patente: patente })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        if (data.total_lavados === 0) {
          if (infoDiv) {
            infoDiv.innerHTML = `
              <div class="text-center text-muted">
                <i class="fas fa-info-circle"></i> No se encontró historial de lavados para la patente ${data.patente}
              </div>
            `;
          }
        } else {
          const ultimo = data.ultimo_lavado;
          if (infoDiv) {
            infoDiv.innerHTML = `
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Patente:</strong> ${data.patente}</p>
                  <p><strong>Último lavado:</strong> ${new Date(ultimo.fecha).toLocaleDateString('es-CL')}</p>
                  <p><strong>Último precio:</strong> $${ultimo.precio.toLocaleString('es-CL')}</p>
                </div>
                <div class="col-md-6">
                  <p><strong>Último servicio:</strong> ${ultimo.servicio}</p>
                  <p><strong>Total lavados:</strong> ${data.total_lavados}</p>
                  <p><strong>Motivos extra:</strong> 
                    ${ultimo.motivos.length > 0 ? 
                      ultimo.motivos.map(motivo => `<span class="badge bg-warning badge-motivo">${motivo}</span>`).join(' ') :
                      '<span class="text-muted">Ninguno</span>'
                    }
                  </p>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-12">
                  <p><strong>Descripción:</strong> ${ultimo.descripcion || 'Sin descripción adicional'}</p>
                  ${ultimo.precio_extra > 0 ? `<p><strong>Precio extra:</strong> $${ultimo.precio_extra.toLocaleString('es-CL')}</p>` : ''}
                </div>
              </div>
            `;

            // Llenar tabla historial
            const tbody = document.querySelector('#tabla-historial tbody');
            if (tbody) {
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
          }
        }
      } else {
        if (infoDiv) {
          infoDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      if (infoDiv) {
        infoDiv.innerHTML = `<div class="alert alert-danger">Error al consultar historial: ${error.message}</div>`;
      }
    });
  }

  // Cargar lavados pendientes
  function cargarLavadosPendientes() {
    const pendientesDiv = document.getElementById('lavados-pendientes');
    if (!pendientesDiv) return;
    
    fetch('./api/api_reporte.php')
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
                  <div class="col-md-2">
                    <button class="btn btn-success btn-sm" onclick="cobrarLavado(${lavado.idautos_estacionados}, '${lavado.patente}')">
                      <i class="fas fa-money-bill"></i> Cobrar
                    </button>
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

  // Cargar historial reciente
  function cargarHistorialReciente() {
    const historialDiv = document.getElementById('historial-reciente');
    if (!historialDiv) return;
    
    const historialEjemplo = [
      {
        patente: 'ABC123',
        fecha: '2024-01-15 14:30',
        servicio: 'Lavado exterior camioneta fango 20',
        precio: 15000,
        motivos: ['hongos', 'barro'],
        cliente: 'Juan Pérez'
      },
      {
        patente: 'XYZ789',
        fecha: '2024-01-15 13:15',
        servicio: 'Lavado exterior básico',
        precio: 8000,
        motivos: [],
        cliente: 'María González'
      },
      {
        patente: 'DEF456',
        fecha: '2024-01-15 12:00',
        servicio: 'Lavado completo premium',
        precio: 25000,
        motivos: ['pelos', 'interior'],
        cliente: 'Carlos López'
      }
    ];
    
    if (historialEjemplo.length === 0) {
      historialDiv.innerHTML = '<div class="text-center text-muted">No hay lavados registrados</div>';
      return;
    }
    
    historialDiv.innerHTML = historialEjemplo.map(lavado => `
      <div class="card card-historial mb-3">
        <div class="card-body">
          <div class="row">
            <div class="col-md-2">
              <h6 class="card-title">${lavado.patente}</h6>
              <small class="text-muted">${lavado.fecha}</small>
            </div>
            <div class="col-md-3">
              <p class="mb-1"><strong>Servicio:</strong></p>
              <p class="mb-0">${lavado.servicio}</p>
            </div>
            <div class="col-md-2">
              <p class="mb-1"><strong>Precio:</strong></p>
              <p class="mb-0 text-success">$${lavado.precio.toLocaleString('es-CL')}</p>
            </div>
            <div class="col-md-3">
              <p class="mb-1"><strong>Motivos extra:</strong></p>
              <div>
                ${lavado.motivos.length > 0 ? 
                  lavado.motivos.map(motivo => `<span class="badge bg-warning badge-motivo">${motivo}</span>`).join(' ') :
                  '<span class="text-muted">Ninguno</span>'
                }
              </div>
            </div>
            <div class="col-md-2">
              <p class="mb-1"><strong>Cliente:</strong></p>
              <p class="mb-0">${lavado.cliente || 'No registrado'}</p>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  }

  // Función para cobrar un lavado
  function cobrarLavado(idIngreso, patente) {
    if (confirm(`¿Confirmar el cobro del lavado para la patente ${patente}?`)) {
      const formData = new FormData();
      formData.append('id_ingreso', idIngreso);
      formData.append('patente', patente);
      formData.append('metodo_pago', 'EFECTIVO');
      
      fetch('./api/cobrar-lavado.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          mostrarAlerta('✅ Lavado cobrado correctamente', 'success');
          cargarLavadosPendientes();
          cargarHistorialReciente();
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
    
    // Esta función se puede implementar más tarde
    console.log('Formulario de lavado enviado:', { patente, tipoLavado, nombreCliente, precioExtra, descripcion, motivos });
  }

  // ========================================
  // 5. COBRO DE SALIDAS
  // ========================================
  
  // Función para manejar el cobro de salidas
  function inicializarCobroSalidas() {
    const formCobroSalida = document.getElementById('form-cobro-salida');
    const inputPatenteCobro = document.getElementById('patente-cobro');
    const resultadoCobro = document.getElementById('resultado-cobro');
    const totalAPagar = document.getElementById('total-a-pagar');
    const btnCobrarTicket = document.getElementById('btn-cobrar-ticket');
    const btnPagarTuu = document.getElementById('btn-pagar-tuu');

    if (formCobroSalida) {
      formCobroSalida.addEventListener('submit', async function (e) {
        e.preventDefault();
        const patente = inputPatenteCobro.value.trim().toUpperCase();
        if (!patente) {
          mostrarAlerta('Ingrese una patente válida', 'warning');
          return;
        }

        // Llama a la API para calcular el cobro
        try {
          const response = await fetch('./api/calcular-cobro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ patente })
          });
          const data = await response.json();

          if (data.success) {
            // Guardar datos del ticket
            ticketCobroActual = data;
            
            // Mostrar información detallada
            let detalleHTML = `
              <div class="card mb-3">
                <div class="card-body">
                  <h5 class="card-title">📋 Detalles del Ticket</h5>
                  <p class="mb-1"><strong>Patente:</strong> ${data.patente}</p>
                  <p class="mb-1"><strong>Servicio:</strong> ${data.nombre_servicio}</p>
                  <p class="mb-1"><strong>Tipo de cobro:</strong> ${data.tipo_calculo}</p>
                  ${data.minutos > 0 ? `<p class="mb-1"><strong>Tiempo:</strong> ${data.minutos} minutos</p>` : ''}
                  ${data.nombre_cliente ? `<p class="mb-1"><strong>Cliente:</strong> ${data.nombre_cliente}</p>` : ''}
                  
                  ${data.precio_extra > 0 ? `
                    <div class="mt-3 p-2 bg-warning bg-opacity-10 rounded">
                      <h6 class="text-warning">💰 Cobros Adicionales</h6>
                      <p class="mb-1"><strong>Precio base:</strong> $${data.precio_base.toLocaleString('es-CL')}</p>
                      <p class="mb-1"><strong>Precio extra:</strong> $${data.precio_extra.toLocaleString('es-CL')}</p>
                      ${data.motivos_extra && data.motivos_extra.length > 0 ? `
                        <p class="mb-1"><strong>Motivos:</strong> 
                          ${data.motivos_extra.map(motivo => `<span class="badge bg-warning text-dark me-1">${motivo}</span>`).join('')}
                        </p>
                      ` : ''}
                      ${data.descripcion_extra ? `<p class="mb-1"><strong>Descripción:</strong> ${data.descripcion_extra}</p>` : ''}
                    </div>
                  ` : ''}
                  
                  <hr>
                  <h4 class="text-primary">Total a pagar: $${data.total.toLocaleString('es-CL')}</h4>
                </div>
              </div>
            `;
            
            if (resultadoCobro) {
              resultadoCobro.innerHTML = detalleHTML;
              resultadoCobro.classList.remove('d-none');
            }
            if (btnCobrarTicket) btnCobrarTicket.disabled = false;
            if (btnPagarTuu) btnPagarTuu.disabled = false;
          } else {
            ticketCobroActual = null;
            if (resultadoCobro) {
              resultadoCobro.innerHTML = `<div class="alert alert-danger">${data.error || 'No se pudo calcular el cobro'}</div>`;
              resultadoCobro.classList.remove('d-none');
            }
            if (btnCobrarTicket) btnCobrarTicket.disabled = true;
            if (btnPagarTuu) btnPagarTuu.disabled = true;
          }
        } catch (error) {
          mostrarAlerta('Error de conexión: ' + error.message, 'danger');
        }
      });
    }

    // Acción para el botón "Cobrar e imprimir ticket"
    if (btnCobrarTicket) {
      btnCobrarTicket.addEventListener('click', async function () {
        if (!ticketCobroActual) {
          mostrarAlerta('⚠️ No hay ticket para cobrar', 'warning');
          return;
        }
        
        console.log('💰 Procesando cobro...', ticketCobroActual);
        
        try {
          // 1. Primero registrar la salida en la base de datos
          const responseSalida = await fetch('./api/registrar-salida.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              id_ingreso: ticketCobroActual.id,
              patente: ticketCobroActual.patente,
              total: ticketCobroActual.total
            })
          });
          
          const dataSalida = await responseSalida.json();
          console.log('✅ Respuesta registro salida:', dataSalida);
          
          if (dataSalida.success) {
            // 2. Luego intentar imprimir el ticket (opcional)
            try {
              const responseImprimir = await fetch('http://localhost:8080/sistemaEstacionamiento/ImpresionTermica/ticketsalida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                  id_ingreso: ticketCobroActual.id,
                  hora_ingreso: ticketCobroActual.fecha_ingreso.split(' ')[1],
                  hora_egreso: new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                  total: ticketCobroActual.total,
                  patente: ticketCobroActual.patente
                })
              });
              
              const dataImprimir = await responseImprimir.text();
              console.log('🖨️ Respuesta impresión:', dataImprimir);
              
              if (dataImprimir.trim() === '1') {
                mostrarAlerta('✅ Cobro realizado y ticket impreso correctamente', 'success');
              } else {
                mostrarAlerta('✅ Cobro realizado correctamente (impresión falló)', 'warning');
              }
            } catch (errorImprimir) {
              console.error('❌ Error en impresión:', errorImprimir);
              mostrarAlerta('✅ Cobro realizado correctamente (impresión no disponible)', 'warning');
            }
            
            btnCobrarTicket.disabled = true;
            if (btnPagarTuu) btnPagarTuu.disabled = true;
            if (resultadoCobro) resultadoCobro.classList.add('d-none');
            if (formCobroSalida) formCobroSalida.reset();
            ticketCobroActual = null;
          } else {
            mostrarAlerta('❌ Error al registrar salida: ' + (dataSalida.error || 'Error desconocido'), 'danger');
          }
          
        } catch (error) {
          console.error('❌ Error:', error);
          mostrarAlerta('❌ Error al procesar cobro: ' + error.message, 'danger');
        }
      });
    }

    // Acción para el botón "Pagar con TUU"
    if (btnPagarTuu) {
      btnPagarTuu.addEventListener('click', async function () {
        if (!ticketCobroActual) {
          mostrarAlerta('⚠️ No hay ticket para cobrar', 'warning');
          return;
        }
        
        // Mostrar modal de confirmación
        const confirmar = confirm(`¿Procesar pago de $${ticketCobroActual.total} con TUU?\n\nPatente: ${ticketCobroActual.patente}\nServicio: ${ticketCobroActual.nombre_servicio}`);
        if (!confirmar) return;
        
        console.log('💳 Procesando pago con TUU...', ticketCobroActual);
        mostrarAlerta('⏳ Procesando pago con TUU... Por favor espere', 'info');
        
        // Deshabilitar botones mientras se procesa
        btnCobrarTicket.disabled = true;
        btnPagarTuu.disabled = true;
        
        try {
          const responseTUU = await fetch('./api/tuu-pago.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              id_ingreso: ticketCobroActual.id,
              patente: ticketCobroActual.patente,
              total: ticketCobroActual.total
            })
          });
          
          const dataTUU = await responseTUU.json();
          console.log('💳 Respuesta TUU:', dataTUU);
          
          if (dataTUU.success) {
            // Pago aprobado
            let mensaje = '✅ Pago aprobado con TUU';
            if (dataTUU.modo_prueba) {
              mensaje += ' (MODO PRUEBA)';
            }
            if (dataTUU.authorization_code) {
              mensaje += `\nCódigo: ${dataTUU.authorization_code}`;
            }
            if (dataTUU.card_type && dataTUU.card_last4) {
              mensaje += `\n${dataTUU.card_type} ****${dataTUU.card_last4}`;
            }
            
            // Intentar imprimir el ticket
            try {
              const responseImprimir = await fetch('http://localhost:8080/sistemaEstacionamiento/ImpresionTermica/ticketsalida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                  id_ingreso: ticketCobroActual.id,
                  hora_ingreso: ticketCobroActual.fecha_ingreso.split(' ')[1],
                  hora_egreso: new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                  total: ticketCobroActual.total,
                  patente: ticketCobroActual.patente
                })
              });
              console.log('🖨️ Ticket impreso');
            } catch (err) {
              console.warn('⚠️ No se pudo imprimir el ticket:', err);
            }
            
            mostrarAlerta(mensaje, 'success');
            if (resultadoCobro) resultadoCobro.classList.add('d-none');
            if (formCobroSalida) formCobroSalida.reset();
            ticketCobroActual = null;
            
          } else {
            // Pago rechazado
            mostrarAlerta('❌ Pago rechazado: ' + (dataTUU.error || 'Error desconocido'), 'danger');
            btnCobrarTicket.disabled = false;
            btnPagarTuu.disabled = false;
          }
          
        } catch (error) {
          console.error('❌ Error:', error);
          mostrarAlerta('❌ Error al procesar pago con TUU: ' + error.message, 'danger');
          btnCobrarTicket.disabled = false;
          btnPagarTuu.disabled = false;
        }
      });
    }
  }

  // ========================================
  // 6. MODALES Y FORMULARIOS AUXILIARES
  // ========================================
  
  // Mostrar modal de lavado al seleccionar "Lavado"
  function inicializarModalLavado() {
    const modalLavado = new bootstrap.Modal(document.getElementById('modalLavado'));
    const patenteLavadoModal = document.getElementById('patente-lavado-modal');
    const formLavadoModal = document.getElementById('form-lavado-modal');

    // Cuando se envía el modal, selecciona el servicio y cierra el modal
    if (formLavadoModal) {
      formLavadoModal.addEventListener('submit', function (e) {
        e.preventDefault();
        // Puedes guardar el servicio seleccionado en un hidden o manejarlo como necesites
        const tipoServicio = document.getElementById('tipo-servicio');
        if (tipoServicio) {
          tipoServicio.value = document.getElementById('servicio-lavado-modal').value;
        }
        modalLavado.hide();
      });
    }

    // Cargar servicios de lavado dinámicamente en el modal
    function cargarServiciosLavado() {
      fetch('./api/api_servicios_lavado.php')
        .then(res => res.json())
        .then(servicios => {
          const select = document.getElementById('servicio-lavado-modal');
          if (select && Array.isArray(servicios)) {
            select.innerHTML = '<option value="">Seleccionar...</option>';
            servicios.forEach(servicio => {
              select.innerHTML += `<option value="${servicio.nombre_servicio}">${servicio.nombre_servicio}</option>`;
            });
          }
        });
    }
    cargarServiciosLavado();
  }

  // --- MODAL MODIFICAR TICKET ---
  function inicializarModalModificarTicket() {
    const modalModificarTicket = document.getElementById('modalModificarTicket');
    const btnBuscarTicket = document.getElementById('btn-buscar-ticket');
    const patenteModificar = document.getElementById('patente-modificar');
    const tipoActual = document.getElementById('tipo-actual');
    const nuevoTipo = document.getElementById('nuevo-tipo');
    const btnGuardarCambio = document.getElementById('btn-guardar-cambio');

    // Cargar servicios de lavado en el select del modal
    function cargarServiciosModificarTicket() {
      console.log('Cargando servicios...');
      fetch('./api/api_servicios_lavado.php')
        .then(res => {
          console.log('Respuesta servicios recibida:', res.status);
          return res.json();
        })
        .then(servicios => {
          console.log('Servicios:', servicios);
          if (nuevoTipo && Array.isArray(servicios)) {
            nuevoTipo.innerHTML = '<option value="">Seleccionar...</option>';
            servicios.forEach(servicio => {
              nuevoTipo.innerHTML += `<option value="${servicio.idtipo_ingresos}">${servicio.nombre_servicio} ($${servicio.precio || '0'})</option>`;
            });
            console.log('Servicios cargados correctamente');
          } else {
            console.error('El elemento nuevoTipo no existe o servicios no es un array');
          }
        })
        .catch(error => {
          console.error('Error cargando servicios:', error);
          mostrarAlerta('Error al cargar servicios', 'danger');
        });
    }

    // Buscar ticket por patente
    if (btnBuscarTicket) {
      btnBuscarTicket.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Botón Buscar Ticket clickeado');
        
        const patente = patenteModificar.value.trim().toUpperCase();
        console.log('Patente ingresada:', patente);
        
        if (!patente) {
          mostrarAlerta('Ingrese una patente válida', 'warning');
          return;
        }

        // Buscar en api_reporte.php
        console.log('Buscando en API...');
        fetch('./api/api_reporte.php')
          .then(res => {
            console.log('Respuesta API status:', res.status);
            return res.json();
          })
          .then(data => {
            console.log('Datos recibidos de la API:', data);
            const registro = data.find(item => 
              item.patente && item.patente.trim().toUpperCase() === patente
            );
            console.log('Registro encontrado:', registro);
            
            if (registro) {
              if (tipoActual) tipoActual.value = registro.tipo_servicio || 'Sin tipo definido';
              if (btnGuardarCambio) btnGuardarCambio.disabled = false;
              mostrarAlerta('✅ Ticket encontrado', 'success');
            } else {
              if (tipoActual) tipoActual.value = '';
              if (btnGuardarCambio) btnGuardarCambio.disabled = true;
              mostrarAlerta('⚠️ No se encontró ticket activo para esa patente', 'warning');
            }
          })
          .catch(error => {
            console.error('Error completo:', error);
            mostrarAlerta('❌ Error al buscar ticket: ' + error.message, 'danger');
          });
      });
    } else {
      console.error('El botón btn-buscar-ticket NO existe en el DOM');
    }

    // Guardar cambio
    if (btnGuardarCambio) {
      btnGuardarCambio.addEventListener('click', function(e) {
        e.preventDefault();
        const patente = patenteModificar.value.trim().toUpperCase();
        const idNuevoServicio = nuevoTipo.value;
        
        if (!patente || !idNuevoServicio) {
          mostrarAlerta('Complete todos los campos', 'warning');
          return;
        }

        console.log('Guardando cambio:', { patente, idNuevoServicio });

        // Llamar a la API para modificar el ticket
        fetch('./api/modificar_ticket.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ 
            patente: patente, 
            id_nuevo_servicio: idNuevoServicio 
          })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              mostrarAlerta('✅ Ticket modificado correctamente', 'success');
              
              // Cerrar modal y limpiar
              const modal = bootstrap.Modal.getInstance(modalModificarTicket);
              if (modal) modal.hide();
              
              if (patenteModificar) patenteModificar.value = '';
              if (tipoActual) tipoActual.value = '';
              if (nuevoTipo) nuevoTipo.value = '';
              if (btnGuardarCambio) btnGuardarCambio.disabled = true;
              
              // Recargar el reporte si existe
              if (typeof cargarReporte === 'function') {
                cargarReporte();
              }
            } else {
              mostrarAlerta('❌ Error al modificar: ' + (data.error || 'Error desconocido'), 'danger');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('❌ Error de conexión: ' + error.message, 'danger');
          });
      });
    } else {
      console.error('El botón btn-guardar-cambio NO existe en el DOM');
    }

    // Cargar servicios al abrir el modal
    if (modalModificarTicket) {
      modalModificarTicket.addEventListener('show.bs.modal', function() {
        console.log('Modal abierto, cargando servicios...');
        cargarServiciosModificarTicket();
      });
    } else {
      console.error('El modal modalModificarTicket NO existe en el DOM');
    }
  }

  // ========================================
  // 7. INICIALIZACIÓN Y EVENT LISTENERS
  // ========================================
  
  // Inicializar fecha y hora
  actualizarFechaHora();
  setInterval(actualizarFechaHora, 1000);

  // --- FORMULARIO DE INGRESO ---
  const patenteIngreso = document.getElementById('patente-ingreso');
  const tipoServicio = document.getElementById('tipo-servicio');

  // Validar patente al perder el foco
  if (patenteIngreso) {
    patenteIngreso.addEventListener('blur', function() {
      const patente = this.value.trim().toUpperCase();
      
      if (patente.length >= 6) {
        verificarPatenteDuplicada(patente);
      }
    });
  }

  // --- FORMULARIO DE INGRESO ---
  const formIngreso = document.getElementById('form-ingreso');
  if (formIngreso) {
    console.log('✅ Formulario de ingreso encontrado, agregando event listener...');
    
    formIngreso.addEventListener('submit', async function(e) {
      console.log('🚀 Formulario enviado, previniendo comportamiento por defecto...');
      e.preventDefault();
      
      const patente = document.getElementById('patente-ingreso').value.trim().toUpperCase();
      const tipoServicio = document.getElementById('tipo-servicio').value;
      const nombreCliente = document.getElementById('nombre-cliente') ? document.getElementById('nombre-cliente').value.trim() : '';

      console.log('📋 Datos del formulario:', { patente, tipoServicio, nombreCliente });

      // Ajustar valor para la API según selección
      let tipo_servicio_db = tipoServicio;
      if (tipoServicio === 'Estacionamiento') {
        tipo_servicio_db = 'estacionamiento x minuto';
      }
      if (tipoServicio === 'Lavado') {
        tipo_servicio_db = 'Lavado';
      }

      console.log('🔄 Tipo de servicio mapeado:', tipo_servicio_db);

      if (!patente || !tipoServicio) {
        mostrarAlerta('Por favor complete todos los campos obligatorios', 'warning');
        return;
      }

      try {
        const datosIngreso = {
          patente: patente,
          tipo_servicio: tipo_servicio_db,
          nombre_cliente: nombreCliente
        };

        console.log('📤 Enviando datos a la API:', datosIngreso);

        const response = await fetch('api/registrar-ingreso.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams(datosIngreso)
        });

        const resultado = await response.json();
        console.log('📥 Respuesta de la API:', resultado);

        if (resultado.success) {
          mostrarAlerta('¡Ingreso registrado correctamente!', 'success');
          formIngreso.reset();
          cargarReporte();
        } else {
          mostrarAlerta('Error al registrar ingreso: ' + (resultado.error || ''), 'danger');
        }

      } catch (error) {
        console.error('💥 Error en el formulario:', error);
        mostrarAlerta('Error de conexión: ' + error.message, 'danger');
      }
    });
  } else {
    console.error('❌ No se encontró el formulario form-ingreso');
  }

  // Inicializar todas las funcionalidades
  cargarServicios();
  cargarLavadosPendientes();
  cargarHistorialReciente();
  inicializarCobroSalidas();
  inicializarModalLavado();
  inicializarModalModificarTicket();

  // Event listeners adicionales
  const btnConsultarHistorial = document.getElementById('btn-consultar-historial');
  if (btnConsultarHistorial) {
    btnConsultarHistorial.addEventListener('click', consultarHistorial);
  }

  const patenteConsulta = document.getElementById('patente-consulta');
  if (patenteConsulta) {
    patenteConsulta.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        consultarHistorial();
      }
    });
  }

  const formLavado = document.getElementById('form-lavado');
  if (formLavado) {
    formLavado.addEventListener('submit', manejarEnvioFormulario);
  }

}); // Cierre del DOMContentLoaded principal
