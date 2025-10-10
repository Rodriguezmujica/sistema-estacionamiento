/**
 * cobro.js
 * Maneja la lógica de la sección de Cobro de Salidas.
 */

document.addEventListener('DOMContentLoaded', () => {
  const formCobroSalida = document.getElementById('form-cobro-salida');
  if (!formCobroSalida) return; // No ejecutar si no estamos en la página de cobro

  console.log('✅ Módulo de Cobro inicializado.');

  const inputPatenteCobro = document.getElementById('patente-cobro');
  const resultadoCobro = document.getElementById('resultado-cobro');
  const btnCobrarTicket = document.getElementById('btn-cobrar-ticket');
  const btnPagarTuu = document.getElementById('btn-pagar-tuu');
  
  // Inicializar modales
  let modalPagoManual = null;
  const modalPagoManualElement = document.getElementById('modalPagoManual');
  if (modalPagoManualElement) {
    modalPagoManual = new bootstrap.Modal(modalPagoManualElement);
  } else {
    console.error('❌ El elemento HTML del modal de pago manual (#modalPagoManual) no fue encontrado.');
  }
  
  let modalPagoTUU = null;
  const modalPagoTUUElement = document.getElementById('modalPagoTUU');
  if (modalPagoTUUElement) {
    modalPagoTUU = new bootstrap.Modal(modalPagoTUUElement);
  } else {
    console.error('❌ El elemento HTML del modal de pago TUU (#modalPagoTUU) no fue encontrado.');
  }
  
  let ticketCobroActual = null;

  // Buscar ticket al enviar formulario
  formCobroSalida.addEventListener('submit', async (e) => {
    e.preventDefault();
    const patente = inputPatenteCobro.value.trim().toUpperCase();
    if (patente.length < 6) {
      mostrarAlerta('Ingrese una patente válida', 'warning');
      return;
    }
    buscarTicketParaCobro(patente);
  });

  // Acción para abrir modal de pago manual
  if (btnCobrarTicket) {
    btnCobrarTicket.addEventListener('click', () => {
      if (!ticketCobroActual) {
        mostrarAlerta('⚠️ Primero debe buscar un ticket para cobrar.', 'warning');
        return;
      }
      
      const esErrorIngreso = ticketCobroActual.tipo_calculo === 'Error de ingreso' || ticketCobroActual.nombre_servicio === 'Error de ingreso';
      const totalFinal = esErrorIngreso ? 1 : ticketCobroActual.total;

      // Llenar datos del modal de pago manual
      document.getElementById('patente-modal-manual').textContent = ticketCobroActual.patente;
      document.getElementById('total-modal-manual').textContent = `$${totalFinal.toLocaleString('es-CL')}`;
      
      // Mostrar modal
      if (modalPagoManual) modalPagoManual.show();
    });
  }

  // Acción para pagar con TUU
  if (btnPagarTuu) {
    btnPagarTuu.addEventListener('click', () => {
      if (!ticketCobroActual) {
        mostrarAlerta('⚠️ Primero debe buscar un ticket para cobrar.', 'warning');
        return;
      }
      
      const esErrorIngreso = ticketCobroActual.tipo_calculo === 'Error de ingreso' || ticketCobroActual.nombre_servicio === 'Error de ingreso';
      const totalFinal = esErrorIngreso ? 1 : ticketCobroActual.total;

      // Llenar datos del modal y mostrarlo
      document.getElementById('patente-modal-tuu').textContent = ticketCobroActual.patente;
      document.getElementById('total-modal-tuu').textContent = `$${totalFinal.toLocaleString('es-CL')}`;
      document.getElementById('spinner-pago-tuu').classList.add('d-none'); // Ocultar spinner
      if (modalPagoTUU) modalPagoTUU.show();
    });
  }

  // --- FUNCIONES AUXILIARES ---

  async function buscarTicketParaCobro(patente) {
    try { 
      const response = await fetch('./api/calcular-cobro.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ patente })
      });
      const data = await response.json();

      if (data.success) {
        ticketCobroActual = data;
        mostrarDetallesTicket(data);
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
  }

  function mostrarDetallesTicket(data) {
    const esErrorIngreso = data.tipo_calculo === 'Error de ingreso' || data.nombre_servicio === 'Error de ingreso';
    const totalFinal = esErrorIngreso ? 1 : data.total;

    let detalleHTML = `
      <div class="card mb-3 ${esErrorIngreso ? 'border-warning' : ''}">
        <div class="card-body">
          <h5 class="card-title ${esErrorIngreso ? 'text-warning' : ''}">
            ${esErrorIngreso ? '⚠️ Error de Ingreso' : '📋 Detalles del Ticket'}
          </h5>
          <p class="mb-1"><strong>Patente:</strong> ${data.patente}</p>
          <p class="mb-1"><strong>Servicio:</strong> ${data.nombre_servicio}</p>
          ${!esErrorIngreso ? `
            <p class="mb-1"><strong>Tipo de cobro:</strong> ${data.tipo_calculo}</p>
            ${data.minutos > 0 ? `<p class="mb-1"><strong>Tiempo:</strong> ${data.minutos} minutos</p>` : ''}
          ` : `<p class="mb-1 text-muted">Este ingreso fue marcado como error y tendrá un cobro mínimo.</p>`}
          
          ${data.precio_extra > 0 && !esErrorIngreso ? `
            <div class="mt-3 p-2 bg-light rounded">
              <h6 class="text-dark">💰 Cobros Adicionales</h6>
              <p class="mb-1"><strong>Precio base:</strong> $${data.precio_base.toLocaleString('es-CL')}</p>
              <p class="mb-1"><strong>Precio extra:</strong> $${data.precio_extra.toLocaleString('es-CL')}</p>
            </div>
          ` : ''}
          
          <hr>
          <h4 class="${esErrorIngreso ? 'text-warning' : 'text-primary'}">Total a pagar: $${totalFinal.toLocaleString('es-CL')}</h4>
        </div>
      </div>
    `;
    resultadoCobro.innerHTML = detalleHTML;
    resultadoCobro.classList.remove('d-none');
  }

  async function procesarPago(metodo, opciones = {}) {
    if (!ticketCobroActual) {
      mostrarAlerta('⚠️ No hay ticket para cobrar', 'warning');
      return;
    }

    const esErrorIngreso = ticketCobroActual.tipo_calculo === 'Error de ingreso' || ticketCobroActual.nombre_servicio === 'Error de ingreso';
    const totalFinal = esErrorIngreso ? 1 : ticketCobroActual.total;

    if (metodo !== 'TUU') { // Para efectivo, el flujo es más directo
      mostrarAlerta(`⏳ Procesando pago con ${metodo}...`, 'info');
      btnCobrarTicket.disabled = true;
      btnPagarTuu.disabled = true;
    }

    try {
      let dataPago;
      if (metodo === 'TUU') {
        const responseTUU = await fetch('./api/tuu-pago.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            id_ingreso: ticketCobroActual.id,
            patente: ticketCobroActual.patente,
            total: totalFinal,
            metodo_tarjeta: opciones.metodoTarjeta || 'desconocido',
            tipo_documento: opciones.tipoDocumento || 'boleta',
            rut_cliente: opciones.rutCliente || '',
            toast_id: opciones.toastId || '' // Enviamos el ID del toast para actualizarlo
          })
        });
        dataPago = await responseTUU.json();
      } else { // EFECTIVO
        const responseSalida = await fetch('./api/registrar-salida.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            id_ingreso: ticketCobroActual.id,
            patente: ticketCobroActual.patente,
            total: totalFinal,
            metodo_pago: 'EFECTIVO'
          })
        });
        dataPago = await responseSalida.json();
      }

      if (dataPago.success) {
        if (metodo === 'TUU') {
          actualizarToast(opciones.toastId, `✅ Pago Aprobado para ${ticketCobroActual.patente}`, 'success');
        }
        await finalizarCobroExitoso(metodo, totalFinal, dataPago);
      } else {
        const mensajeError = `❌ Pago Rechazado para ${ticketCobroActual.patente}: ${dataPago.error || 'Error desconocido'}`;
        if (metodo === 'TUU') actualizarToast(opciones.toastId, mensajeError, 'danger');
        else mostrarAlerta(mensajeError, 'danger');
        btnCobrarTicket.disabled = false;
        btnPagarTuu.disabled = false;
      }
    } catch (error) {
      // Si el error es de parseo JSON, es muy probable que sea un error de PHP.
      if (error instanceof SyntaxError) {
        mostrarAlerta(`❌ Error en la respuesta del servidor. Revisa los logs de PHP para más detalles.`, 'danger');
        console.error("El servidor no devolvió un JSON válido. Probablemente un error de PHP.", error);
      } else {
        mostrarAlerta(`❌ Error al procesar pago con ${metodo}: ${error.message}`, 'danger');
      }
      btnCobrarTicket.disabled = false;
      btnPagarTuu.disabled = false;
      if (metodo === 'TUU') {
        actualizarToast(opciones.toastId, `❌ Error de Conexión para ${ticketCobroActual.patente}`, 'danger');
        if (modalPagoTUU) modalPagoTUU.hide();
      }
    }
  }
  
  // Event listener para confirmar pago manual
  const btnConfirmarPagoManual = document.getElementById('btn-confirmar-pago-manual');
  if (btnConfirmarPagoManual) {
    btnConfirmarPagoManual.addEventListener('click', () => {
      const motivoPagoManual = document.getElementById('motivo-pago-manual').value;
      const metodoPagoManual = document.getElementById('metodo-pago-manual').value;
      
      // Validar que se haya seleccionado un motivo
      if (!motivoPagoManual) {
        mostrarAlerta('Por favor, seleccione un motivo para el pago manual.', 'warning');
        return;
      }
      
      // Ocultar el modal inmediatamente
      if (modalPagoManual) modalPagoManual.hide();
      
      // Procesar el pago manual
      procesarPagoManual(metodoPagoManual, motivoPagoManual);
    });
  }
  
  // Event listeners para los botones dentro del modal TUU
  document.querySelectorAll('#modalPagoTUU [data-metodo]').forEach(button => {
    button.addEventListener('click', (e) => {
      const metodoTarjeta = e.currentTarget.getAttribute('data-metodo');
      const tipoDocumento = document.querySelector('input[name="tipoDocumento"]:checked').value;
      let rutCliente = null;

      // Validar y obtener RUT si es factura
      if (tipoDocumento === 'factura') {
        rutCliente = document.getElementById('rut-factura').value.trim();
        if (!rutCliente) {
          mostrarAlerta('Por favor, ingrese el RUT para la factura.', 'warning');
          return; // Detiene el proceso si el RUT es requerido y está vacío
        }
        // Validar formato del RUT (ej: 12345678-9)
        if (!validarFormatoRut(rutCliente)) {
          mostrarAlerta('El formato del RUT no es válido. Debe ser como en el ejemplo: 12345678-9.', 'warning');
          document.getElementById('rut-factura').focus();
          return;
        }
      }

      // Ocultar el modal inmediatamente
      if (modalPagoTUU) modalPagoTUU.hide();

      // Crear un ID único para la notificación "toast"
      const toastId = `toast-${Date.now()}`;
      const mensajeToast = `Esperando pago para patente <strong>${ticketCobroActual.patente}</strong> en la máquina TUU...`;
      crearToast(toastId, mensajeToast);

      // Llama a la función de procesamiento de pago
      procesarPago('TUU', { metodoTarjeta, tipoDocumento, rutCliente, toastId }); 
    });
  });

  async function procesarPagoManual(metodoPago, motivoManual) {
    if (!ticketCobroActual) {
      mostrarAlerta('⚠️ No hay ticket para cobrar', 'warning');
      return;
    }

    const esErrorIngreso = ticketCobroActual.tipo_calculo === 'Error de ingreso' || ticketCobroActual.nombre_servicio === 'Error de ingreso';
    const totalFinal = esErrorIngreso ? 1 : ticketCobroActual.total;

    mostrarAlerta(`⏳ Procesando pago manual...`, 'info');
    btnCobrarTicket.disabled = true;
    btnPagarTuu.disabled = true;

    try {
      const response = await fetch('./api/pago-manual.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          id_ingreso: ticketCobroActual.id,
          patente: ticketCobroActual.patente,
          total: totalFinal,
          metodo_pago: metodoPago,
          motivo_manual: motivoManual
        })
      });

      const dataPago = await response.json();

      if (dataPago.success) {
        mostrarAlerta(`✅ Pago manual de $${totalFinal.toLocaleString('es-CL')} registrado correctamente.`, 'success');
        await finalizarCobroExitoso('MANUAL', totalFinal, dataPago);
      } else {
        mostrarAlerta(`❌ Error al procesar pago manual: ${dataPago.error || 'Error desconocido'}`, 'danger');
        btnCobrarTicket.disabled = false;
        btnPagarTuu.disabled = false;
      }
    } catch (error) {
      if (error instanceof SyntaxError) {
        mostrarAlerta(`❌ Error en la respuesta del servidor. Revisa los logs de PHP.`, 'danger');
        console.error("El servidor no devolvió un JSON válido:", error);
      } else {
        mostrarAlerta(`❌ Error al procesar pago manual: ${error.message}`, 'danger');
      }
      btnCobrarTicket.disabled = false;
      btnPagarTuu.disabled = false;
    }
  }

  async function finalizarCobroExitoso(metodo, total, dataPago) {
    let mensaje = '';
    
    if (metodo === 'MANUAL') {
      mensaje = `✅ Pago Manual registrado: $${total.toLocaleString('es-CL')} (Comprobante Interno)`;
      mostrarAlerta(mensaje, 'success');
    } else if (metodo === 'TUU') {
      mensaje = `✅ Pago con TUU de $${total.toLocaleString('es-CL')} procesado correctamente.`;
      if (dataPago.modo_prueba) mensaje += ' (MODO PRUEBA)';
    } else {
      mensaje = `✅ Pago con ${metodo} de $${total.toLocaleString('es-CL')} procesado correctamente.`;
      mostrarAlerta(mensaje, 'success');
    }

    // Intentar imprimir ticket solo para pagos no manuales
    if (metodo !== 'MANUAL') {
      try {
        const responseImprimir = await fetch('http://localhost:8080/sistemaEstacionamiento/ImpresionTermica/ticketsalida.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            id_ingreso: ticketCobroActual.id,
            hora_ingreso: ticketCobroActual.fecha_ingreso.split(' ')[1],
            hora_egreso: new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
            total: total,
            patente: ticketCobroActual.patente,
            metodo_pago: metodo
          })
        });
        const dataImprimir = await responseImprimir.text();
        if (dataImprimir.trim() !== '1') {
          mostrarAlerta('El cobro fue exitoso, pero la impresión del ticket falló.', 'warning');
        }
      } catch (errorImprimir) {
        console.warn('⚠️ No se pudo imprimir el ticket:', errorImprimir);
        mostrarAlerta('El cobro fue exitoso, pero el servicio de impresión no está disponible.', 'warning');
      }
    } else {
      // Para pagos manuales, mostrar mensaje informativo
      mostrarAlerta('💡 Comprobante interno generado. No se imprimió ticket en impresora térmica.', 'info');
    }

    // Limpiar UI
    resetearCobro();
  }

  function resetearCobro() {
    ticketCobroActual = null;
    if (resultadoCobro) resultadoCobro.classList.add('d-none');
    if (formCobroSalida) formCobroSalida.reset();
    if (btnCobrarTicket) btnCobrarTicket.disabled = true;
    if (btnPagarTuu) btnPagarTuu.disabled = true;
    
    // Resetear modal de pago manual
    const motivoManual = document.getElementById('motivo-pago-manual');
    if (motivoManual) motivoManual.value = '';
    const metodoPagoManual = document.getElementById('metodo-pago-manual');
    if (metodoPagoManual) metodoPagoManual.value = 'EFECTIVO';
    
    // Resetear modal TUU
    document.querySelectorAll('#modalPagoTUU [data-metodo]').forEach(btn => btn.disabled = false);
    const spinner = document.getElementById('spinner-pago-tuu');
    if (spinner) spinner.classList.add('d-none');
    const docBoleta = document.getElementById('docBoleta');
    if (docBoleta) docBoleta.checked = true;
    
    // Ocultar y limpiar campo RUT
    const campoRut = document.getElementById('campo-rut-factura');
    if (campoRut) campoRut.classList.add('d-none');
    const inputRut = document.getElementById('rut-factura');
    if (inputRut) inputRut.value = '';

    if (inputPatenteCobro) inputPatenteCobro.focus();
  }

  // Lógica para mostrar/ocultar campo RUT en el modal
  document.querySelectorAll('input[name="tipoDocumento"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      document.getElementById('campo-rut-factura').classList.toggle('d-none', e.target.value !== 'factura');
    });
  });

  // Función para validar el formato básico de un RUT chileno
  function validarFormatoRut(rut) {
    const regex = /^[0-9]{7,8}-[0-9Kk]$/;
    return regex.test(rut);
  }

  // --- FUNCIONES PARA NOTIFICACIONES TOAST ---

  function crearToast(id, mensaje) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;

    const toastHTML = `
      <div id="${id}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
        <div class="toast-header">
          <i class="fas fa-credit-card me-2"></i>
          <strong class="me-auto">Pago con TUU</strong>
          <small>En progreso</small>
        </div>
        <div class="toast-body d-flex align-items-center">
          <div class="spinner-border spinner-border-sm me-2" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <span>${mensaje}</span>
        </div>
      </div>
    `;
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(id);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
  }

  function actualizarToast(id, mensaje, estado) {
    const toastElement = document.getElementById(id);
    if (!toastElement) return;

    const toastBody = toastElement.querySelector('.toast-body');
    toastBody.innerHTML = mensaje; // Reemplaza el spinner y el texto
    toastElement.classList.add(estado === 'success' ? 'bg-success-subtle' : 'bg-danger-subtle');
    
    // Ocultar el toast después de 10 segundos
    setTimeout(() => bootstrap.Toast.getInstance(toastElement)?.hide(), 10000);
  }
});