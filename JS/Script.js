document.addEventListener('DOMContentLoaded', () => {
  // --- CONFIGURACIÓN ---
  const CONFIG = {
    precioMinuto: 35,
    precioMinimo: 500, // Precio mínimo de estacionamiento
    actualizacionInterval: 30000 // Actualizar cada 30 segundos
  };
  let datosVehiculo = null;
  let intervalId = null;
  let ticketActual = null;

  // --- FUNCIONES DE REPORTE Y TABLAS ---
  function cargarReporte() {
    fetch('http://localhost:8080/sistemaEstacionamiento/api/api_reporte.php')
      .then(response => response.json())
      .then(data => {
        const tabla = document.getElementById('tabla-reporte');
        if (tabla) {
          tabla.innerHTML = '';
          data.forEach(registro => {
            tabla.innerHTML += `
              <tr>
                <td>${registro.patente}</td>
                <td>${registro.cliente}</td>
                <td>${registro.tipo_servicio}</td>
                <td>${registro.fecha_ingreso}</td>
                <td>${registro.lavado}</td>
              </tr>
            `;
          });
        }
      })
      .catch(error => console.error('Error al cargar el reporte:', error));
  }
  cargarReporte();
  setInterval(cargarReporte, 60000);

  // Últimos ingresos en reporte
  const tablaIngresos = document.querySelector('#tabla-ingresos tbody');
  if (tablaIngresos) {
    fetch('../api/ultimos-ingresos.php')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          tablaIngresos.innerHTML = '';
          data.ingresos.forEach((ing, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${idx + 1}</td>
              <td>${ing.patente}</td>
              <td>${ing.fecha_ingreso}</td>
              <td>${ing.nombre_servicio}</td>
            `;
            tablaIngresos.appendChild(tr);
          });
        } else {
          tablaIngresos.innerHTML = '<tr><td colspan="4">No hay datos</td></tr>';
        }
      })
      .catch(() => {
        tablaIngresos.innerHTML = '<tr><td colspan="4">Error al cargar datos</td></tr>';
      });
  }

  // --- FECHA Y HORA ---
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
  actualizarFechaHora();
  setInterval(actualizarFechaHora, 1000);

  // --- FORMULARIO DE INGRESO ---
  const formIngreso = document.getElementById('form-ingreso');
  if (formIngreso) {
    formIngreso.addEventListener('submit', async function(e) {
      e.preventDefault();
      const patente = document.getElementById('patente-ingreso').value.trim().toUpperCase();
      const tipoServicio = document.getElementById('tipo-servicio').value;
      const nombreCliente = document.getElementById('nombre-cliente') ? document.getElementById('nombre-cliente').value.trim() : '';

      // Ajustar valor para la API según selección
      let tipo_servicio_db = tipoServicio;
      if (tipoServicio === 'Estacionamiento') {
        tipo_servicio_db = 'estacionamiento x minuto';
      }
      if (tipoServicio === 'Lavado') {
        tipo_servicio_db = 'Lavado'; // Ajusta si en tu BD el nombre es diferente
      }

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

        const response = await fetch('api/registrar-ingreso.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams(datosIngreso)
        });

        const resultado = await response.json();

        if (resultado.success) {
          mostrarAlerta('¡Ingreso registrado correctamente!', 'success');
          formIngreso.reset();
          cargarReporte();
        } else {
          mostrarAlerta('Error al registrar ingreso: ' + (resultado.error || ''), 'danger');
        }

      } catch (error) {
        mostrarAlerta('Error de conexión: ' + error.message, 'danger');
      }
    });
  }

  // --- FUNCIONES DE ALERTA ---
  function mostrarAlerta(mensaje, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
      ${mensaje}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => {
      if (alertDiv) {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
      }
    }, 3000);
  }

  // --- COBRO DE SALIDAS ---
  const formCobroSalida = document.getElementById('form-cobro-salida');
  const inputPatenteCobro = document.getElementById('patente-cobro');
  const resultadoCobro = document.getElementById('resultado-cobro');
  const totalAPagar = document.getElementById('total-a-pagar');
  const btnCobrarTicket = document.getElementById('btn-cobrar-ticket');
  const btnPagarTuu = document.getElementById('btn-pagar-tuu');

  // Variable para guardar los datos del ticket actual
  let ticketCobroActual = null;

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
          
          resultadoCobro.innerHTML = detalleHTML;
          resultadoCobro.classList.remove('d-none');
          btnCobrarTicket.disabled = false;
          btnPagarTuu.disabled = false;
        } else {
          ticketCobroActual = null;
          resultadoCobro.innerHTML = `<div class="alert alert-danger">${data.error || 'No se pudo calcular el cobro'}</div>`;
          resultadoCobro.classList.remove('d-none');
          btnCobrarTicket.disabled = true;
          btnPagarTuu.disabled = true;
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
          btnPagarTuu.disabled = true;
          resultadoCobro.classList.add('d-none');
          formCobroSalida.reset();
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
          resultadoCobro.classList.add('d-none');
          formCobroSalida.reset();
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

  // Mostrar modal de lavado al seleccionar "Lavado"
  const tipoServicio = document.getElementById('tipo-servicio');
  const modalLavado = new bootstrap.Modal(document.getElementById('modalLavado'));
  const patenteIngreso = document.getElementById('patente-ingreso');
  const patenteLavadoModal = document.getElementById('patente-lavado-modal');
  const formLavadoModal = document.getElementById('form-lavado-modal');

  if (tipoServicio) {
    tipoServicio.addEventListener('change', function () {
      if (this.value === 'Lavado') {
        // Pasa la patente al modal
        patenteLavadoModal.value = patenteIngreso.value.trim().toUpperCase();
        modalLavado.show();
      }
    });
  }

  // Cuando se envía el modal, selecciona el servicio y cierra el modal
  if (formLavadoModal) {
    formLavadoModal.addEventListener('submit', function (e) {
      e.preventDefault();
      // Puedes guardar el servicio seleccionado en un hidden o manejarlo como necesites
      tipoServicio.value = document.getElementById('servicio-lavado-modal').value;
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

  // --- MODAL MODIFICAR TICKET ---
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
            tipoActual.value = registro.tipo_servicio || 'Sin tipo definido';
            btnGuardarCambio.disabled = false;
            mostrarAlerta('✅ Ticket encontrado', 'success');
          } else {
            tipoActual.value = '';
            btnGuardarCambio.disabled = true;
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
            
            patenteModificar.value = '';
            tipoActual.value = '';
            nuevoTipo.value = '';
            btnGuardarCambio.disabled = true;
            
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

});