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
  let ticketCobroActual = null;

  // Buscar ticket al enviar formulario
  formCobroSalida.addEventListener('submit', async (e) => {
    e.preventDefault();
    const patente = inputPatenteCobro.value.trim().toUpperCase();
    if (!patente) {
      mostrarAlerta('Ingrese una patente válida', 'warning');
      return;
    }
    buscarTicketParaCobro(patente);
  });

  // Acción para cobrar en efectivo
  if (btnCobrarTicket) {
    btnCobrarTicket.addEventListener('click', () => procesarPago('EFECTIVO'));
  }

  // Acción para pagar con TUU
  if (btnPagarTuu) {
    btnPagarTuu.addEventListener('click', () => procesarPago('TUU'));
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

  async function procesarPago(metodo) {
    if (!ticketCobroActual) {
      mostrarAlerta('⚠️ No hay ticket para cobrar', 'warning');
      return;
    }

    const esErrorIngreso = ticketCobroActual.tipo_calculo === 'Error de ingreso' || ticketCobroActual.nombre_servicio === 'Error de ingreso';
    const totalFinal = esErrorIngreso ? 1 : ticketCobroActual.total;

    if (metodo === 'TUU') {
      const confirmar = confirm(`¿Procesar pago de $${totalFinal.toLocaleString('es-CL')} con TUU para la patente ${ticketCobroActual.patente}?`);
      if (!confirmar) return;
    }

    mostrarAlerta(`⏳ Procesando pago con ${metodo}...`, 'info');
    btnCobrarTicket.disabled = true;
    btnPagarTuu.disabled = true;

    try {
      let dataPago;
      if (metodo === 'TUU') {
        const responseTUU = await fetch('./api/tuu-pago.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            id_ingreso: ticketCobroActual.id,
            patente: ticketCobroActual.patente,
            total: totalFinal
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
        await finalizarCobroExitoso(metodo, totalFinal, dataPago);
      } else {
        mostrarAlerta(`❌ Pago rechazado: ${dataPago.error || 'Error desconocido'}`, 'danger');
        btnCobrarTicket.disabled = false;
        btnPagarTuu.disabled = false;
      }
    } catch (error) {
      mostrarAlerta(`❌ Error al procesar pago con ${metodo}: ${error.message}`, 'danger');
      btnCobrarTicket.disabled = false;
      btnPagarTuu.disabled = false;
    }
  }

  async function finalizarCobroExitoso(metodo, total, dataPago) {
    let mensaje = `✅ Pago con ${metodo} de $${total.toLocaleString('es-CL')} procesado correctamente.`;
    if (metodo === 'TUU' && dataPago.modo_prueba) {
      mensaje += ' (MODO PRUEBA)';
    }
    mostrarAlerta(mensaje, 'success');

    // Intentar imprimir ticket
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

    // Limpiar UI
    resetearCobro();
  }

  function resetearCobro() {
    ticketCobroActual = null;
    if (resultadoCobro) resultadoCobro.classList.add('d-none');
    if (formCobroSalida) formCobroSalida.reset();
    if (btnCobrarTicket) btnCobrarTicket.disabled = true;
    if (btnPagarTuu) btnPagarTuu.disabled = true;
    if (inputPatenteCobro) inputPatenteCobro.focus();
  }
});