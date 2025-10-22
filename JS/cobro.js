/**
 * cobro.js
 * Maneja la lógica de la sección de Cobro de Salidas.
 */

/**
 * Muestra una notificación toast bonita (función auxiliar para impresión)
 */
function mostrarToastBonito(mensaje, tipo = 'info') {
    // Si existe SweetAlert2
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: tipo === 'success' ? 'success' : tipo === 'error' ? 'error' : tipo === 'warning' ? 'warning' : 'info',
            title: mensaje,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        return;
    }

    // Si existe Bootstrap toast
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        // Crear elemento toast
        const toastHTML = `
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="2500">
                <div class="toast-header bg-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : tipo === 'warning' ? 'warning' : 'info'} text-white">
                    <i class="fa fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-triangle' : tipo === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    <strong class="me-auto">${tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : tipo === 'warning' ? 'Advertencia' : 'Información'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${mensaje}</div>
            </div>
        `;
        
        // Crear contenedor si no existe
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        // Agregar toast y mostrar
        container.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = container.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 2500
        });
        toast.show();
        
        // Eliminar después de ocultar
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
        return;
    }

    // Fallback: Crear toast personalizado simple (NUNCA alertas bloqueantes)
    const toast = document.createElement('div');
    toast.className = `toast-personalizado toast-${tipo}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        padding: 12px 16px;
        background: ${tipo === 'success' ? '#d4edda' : tipo === 'error' ? '#f8d7da' : tipo === 'warning' ? '#fff3cd' : '#d1ecf1'};
        color: ${tipo === 'success' ? '#155724' : tipo === 'error' ? '#721c24' : tipo === 'warning' ? '#856404' : '#0c5460'};
        border: 1px solid ${tipo === 'success' ? '#c3e6cb' : tipo === 'error' ? '#f5c6cb' : tipo === 'warning' ? '#ffeaa7' : '#bee5eb'};
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 14px;
        transform: translateX(100%);
        transition: transform 0.3s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: space-between;
    `;
    
    const icon = tipo === 'success' ? '✅' : tipo === 'error' ? '❌' : tipo === 'warning' ? '⚠️' : 'ℹ️';
    toast.innerHTML = `
        <div style="display: flex; align-items: center;">
            <span style="margin-right: 8px; font-size: 16px;">${icon}</span>
            <span>${mensaje}</span>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 18px; cursor: pointer; margin-left: 12px; color: inherit; opacity: 0.7;">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto ocultar después de 3 segundos
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, 3000);
}

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
    if (!patente) {
      mostrarAlerta('Por favor, ingrese una patente', 'warning');
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
      
      // Establecer valores predeterminados para agilizar
      const motivoManual = document.getElementById('motivo-pago-manual');
      if (motivoManual) motivoManual.value = 'Pago en efectivo';
      const metodoPagoManual = document.getElementById('metodo-pago-manual');
      if (metodoPagoManual) metodoPagoManual.value = 'EFECTIVO';
      
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
      
      // Asegurar que el botón TUU esté en estado normal al abrir el modal
      const btnConfirmarTUU = document.getElementById('btn-confirmar-pago-tuu');
      if (btnConfirmarTUU) {
        btnConfirmarTUU.disabled = false;
        btnConfirmarTUU.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar y Pagar con TUU';
      }
      
      if (modalPagoTUU) modalPagoTUU.show();
    });
  }

  // --- CONFIGURACIÓN DE RUTAS ---
  if (typeof getBasePath === 'undefined') {
    window.getBasePath = () => {
      const path = window.location.pathname;
      const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
      return baseMatch ? baseMatch[1] : '';
    };
  }

  // Usar window.BASE_PATH para evitar conflictos de redeclaración
  if (typeof window.BASE_PATH === 'undefined') {
    window.BASE_PATH = getBasePath();
  }

  // Usar la variable global
  const BASE_PATH = window.BASE_PATH;

  // --- FUNCIONES AUXILIARES ---
  
  /**
   * Redondea monto según la ley chilena
   * En Chile no existe moneda de 5 pesos desde 1991
   * Reglas oficiales del Banco Central de Chile:
   * - 1-4 pesos: redondea hacia abajo (al 0)
   * - 5-9 pesos: redondea hacia arriba (al 10)
   */
  function redondearSegunLeyChilena(monto) {
    const montoCentavos = parseInt(monto);
    const unidades = montoCentavos % 10;
    
    if (unidades >= 1 && unidades <= 4) {
      // Redondea hacia abajo (al 0)
      return montoCentavos - unidades;
    } else if (unidades >= 5 && unidades <= 9) {
      // Redondea hacia arriba (al 10)
      return montoCentavos + (10 - unidades);
    } else {
      // Ya está en múltiplo de 10
      return montoCentavos;
    }
  }

  async function buscarTicketParaCobro(patente) {
    try { 
      // Ocultar botón de confirmación manual al buscar nuevo ticket
      ocultarBotonConfirmacionManual();
      
      const response = await fetch(`${BASE_PATH}/api/calcular-cobro.php`, {
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
    
    // Mostrar advertencia si hay múltiples pendientes
    let advertenciaHTML = '';
    if (data.total_pendientes && data.total_pendientes > 1) {
      advertenciaHTML = `
        <div class="alert alert-warning mb-3">
          <strong>⚠️ Advertencia:</strong> Esta patente tiene ${data.total_pendientes} registros pendientes.<br>
          <small>Se cobrará el más reciente. Si hay duplicados, considere cobrarlos o eliminarlos desde Administración.</small>
        </div>
      `;
    }

    let detalleHTML = advertenciaHTML + `
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
              <p class="mb-1"><strong>Precio base:</strong> $${parseInt(data.precio_base).toLocaleString('es-CL')}</p>
              <p class="mb-1"><strong>Precio extra:</strong> $${parseInt(data.precio_extra).toLocaleString('es-CL')}</p>
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
        // Log para debuggear qué datos se envían
        const datosParaEnviar = {
          id_ingreso: ticketCobroActual.id,
          patente: ticketCobroActual.patente,
          total: totalFinal,
          metodo_tarjeta: opciones.metodoTarjeta || 'desconocido',
          tipo_documento: opciones.tipoDocumento || 'boleta',
          rut_cliente: opciones.rutCliente || '',
          toast_id: opciones.toastId || ''
        };
        console.log('🔍 Datos que se envían a tuu-pago.php:', datosParaEnviar);
        console.log('🔍 ticketCobroActual completo:', ticketCobroActual);
        console.log('🔍 BASE_PATH:', BASE_PATH);
        console.log('🔍 URL completa:', `${BASE_PATH}/api/tuu-pago.php`);
        
        const responseTUU = await fetch(`${BASE_PATH}/api/tuu-pago.php`, {
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

        // Verificar si la respuesta es válida antes de parsear JSON
        if (!responseTUU.ok) {
          const errorText = await responseTUU.text();
          console.error('Error TUU server response:', responseTUU.status, errorText);
          throw new Error(`Error del servidor TUU (${responseTUU.status}): ${errorText}`);
        }

        const responseText = await responseTUU.text();
        console.log('Respuesta TUU raw:', responseText);
        
        try {
          dataPago = JSON.parse(responseText);
        } catch (parseError) {
          console.error('Error parsing JSON from TUU:', parseError, 'Raw response:', responseText);
          throw new Error(`Error al procesar respuesta de TUU: ${parseError.message}`);
        }
      } else { // EFECTIVO
        const responseSalida = await fetch(`${BASE_PATH}/api/registrar-salida.php`, {
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
        // Para TUU, verificar si es un error de conexión o pago pendiente
        console.log('🔍 Debug TUU - dataPago completo:', dataPago);
        console.log('🔍 Debug TUU - dataPago.status:', dataPago.status);
        console.log('🔍 Debug TUU - dataPago.details?.status:', dataPago.details?.status);
        console.log('🔍 Debug TUU - dataPago.red_local:', dataPago.red_local);
        
        const isPending = dataPago.status === 'pending' || dataPago.details?.status === 'pending' || dataPago.red_local;
        console.log('🔍 Debug TUU - isPending:', isPending);
        console.log('🔍 Debug TUU - metodo === TUU:', metodo === 'TUU');
        console.log('🔍 Debug TUU - condición completa:', metodo === 'TUU' && isPending);
        
        if (metodo === 'TUU' && isPending) {
          // Obtener el transaction_id correcto
          const tuuTransactionId = dataPago.transaction_id || dataPago.details?.transaction_id;
          console.log('🔍 TUU Transaction ID recibido:', tuuTransactionId, 'Data completo:', dataPago);
          
          if (!tuuTransactionId) {
            console.error('❌ No se recibió transaction_id de TUU');
            actualizarToast(opciones.toastId, 'Error: No se obtuvo ID de transacción de TUU', 'danger');
            return;
          }
          
          // Mostrar botón de confirmación manual inmediatamente cuando hay pending
          mostrarBotonConfirmacionManual(tuuTransactionId, ticketCobroActual.patente, ticketCobroActual.total);
          
          // Mostrar toast informativo para estado pending
          actualizarToast(opciones.toastId, `⏳ Pago en Proceso: ${ticketCobroActual.patente} - $${ticketCobroActual.total}`, 'info');
          
          // Iniciar verificación de estado en red local
          iniciarVerificacionEstadoTUU(opciones.toastId, tuuTransactionId);
          return; // No mostrar error si es pending
        } else {
          const mensajeError = `❌ Pago Rechazado: ${dataPago.error || 'Error desconocido'}`;
          const detalleError = dataPago.details?.error_code ? ` (${dataPago.details.error_code})` : '';
          
          if (metodo === 'TUU') {
            actualizarToast(opciones.toastId, mensajeError + detalleError, 'danger');
            mostrarAlerta(mensajeError + detalleError, 'danger');
          } else {
            mostrarAlerta(mensajeError, 'danger');
          }
        }
        
        btnCobrarTicket.disabled = false;
        btnPagarTuu.disabled = false;
        
        // Resetear botón TUU si hay error en pago TUU
        if (metodo === 'TUU') {
          const btnConfirmarTUU = document.getElementById('btn-confirmar-pago-tuu');
          if (btnConfirmarTUU) {
            btnConfirmarTUU.disabled = false;
            btnConfirmarTUU.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar y Pagar con TUU';
          }
          const spinner = document.getElementById('spinner-pago-tuu');
          if (spinner) spinner.classList.add('d-none');
        }
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
      
      // Resetear botón TUU en caso de error de conexión
      if (metodo === 'TUU') {
        actualizarToast(opciones.toastId, `❌ Error de Conexión para ${ticketCobroActual.patente}`, 'danger');
        if (modalPagoTUU) modalPagoTUU.hide();
        
        const btnConfirmarTUU = document.getElementById('btn-confirmar-pago-tuu');
        if (btnConfirmarTUU) {
          btnConfirmarTUU.disabled = false;
          btnConfirmarTUU.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar y Pagar con TUU';
        }
        const spinner = document.getElementById('spinner-pago-tuu');
        if (spinner) spinner.classList.add('d-none');
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
  
  // Event listener para confirmar pago con TUU
  const btnConfirmarPagoTUU = document.getElementById('btn-confirmar-pago-tuu');
  
  if (btnConfirmarPagoTUU) {
    btnConfirmarPagoTUU.addEventListener('click', () => {
      // Validar que ticketCobroActual esté definido
      if (!ticketCobroActual) {
        console.error('❌ ticketCobroActual no está definido');
        mostrarAlerta('Error: No hay datos de ticket para procesar el pago', 'danger');
        return;
      }
      
      console.log('🔍 ticketCobroActual:', ticketCobroActual);
      
      // Obtener método de pago seleccionado (TUU maneja automáticamente débito/crédito)
      const metodoTarjetaElement = document.querySelector('input[name="metodoTarjeta"]:checked');
      
      // Si no hay selección (porque está oculto), usar 'auto' para que TUU decida
      let metodoTarjeta = 'auto';
      if (metodoTarjetaElement) {
        metodoTarjeta = metodoTarjetaElement.value;
      }
      
      // Obtener tipo de documento
      const tipoDocumentoElement = document.querySelector('input[name="tipoDocumento"]:checked');
      if (!tipoDocumentoElement) {
        mostrarAlerta('Por favor, seleccione un tipo de documento (boleta o factura).', 'warning');
        return;
      }
      const tipoDocumento = tipoDocumentoElement.value;
      let rutCliente = null;

      // Validar y obtener RUT si es factura
      if (tipoDocumento === 'factura') {
        rutCliente = document.getElementById('rut-factura').value.trim();
        if (!rutCliente) {
          mostrarAlerta('Por favor, ingrese el RUT para la factura.', 'warning');
          return;
        }
        // Validar formato del RUT (ej: 12345678-9)
        if (!validarFormatoRut(rutCliente)) {
          mostrarAlerta('El formato del RUT no es válido. Debe ser como en el ejemplo: 12345678-9.', 'warning');
          document.getElementById('rut-factura').focus();
          return;
        }
      }

      // Mostrar spinner y deshabilitar botón
      document.getElementById('spinner-pago-tuu').classList.remove('d-none');
      btnConfirmarPagoTUU.disabled = true;
      btnConfirmarPagoTUU.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

      // Ocultar el modal después de un momento
      setTimeout(() => {
        if (modalPagoTUU) modalPagoTUU.hide();
      }, 500);

      // Crear un ID único para la notificación "toast"
      const toastId = `toast-${Date.now()}`;
      const mensajeToast = `⏳ Esperando pago para patente <strong>${ticketCobroActual.patente}</strong> en la máquina TUU (red local)...`;
      crearToast(toastId, mensajeToast);

      // Debug: verificar valores antes de enviar a TUU
      console.log('🔍 Valores que se enviarán a TUU:', {
        metodoTarjeta,
        tipoDocumento,
        rutCliente,
        patente: ticketCobroActual.patente,
        total: ticketCobroActual.total
      });

      // Llama a la función de procesamiento de pago
      procesarPago('TUU', { metodoTarjeta, tipoDocumento, rutCliente, toastId }); 
    });
  }

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
      const response = await fetch(`${BASE_PATH}/api/pago-manual.php`, {
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

    // Intentar imprimir comprobante interno solo para pagos MANUALES
    if (metodo === 'MANUAL') {
      try {
        // 🆕 INTENTAR CON NUEVO SERVICIO PRIMERO
        if (typeof PrintService !== 'undefined') {
          console.log('🆕 Imprimiendo ticket de salida con nuevo servicio...');
          const ahora = new Date();
          
          // Calcular tiempo transcurrido
          const fechaIngreso = new Date(ticketCobroActual.fecha_ingreso);
          const minutosTranscurridos = Math.floor((ahora - fechaIngreso) / 60000);
          const horas = Math.floor(minutosTranscurridos / 60);
          const minutos = minutosTranscurridos % 60;
          const tiempoTexto = horas > 0 ? `${horas}h ${minutos}min` : `${minutos}min`;
          
          const resultado = await PrintService.imprimirTicketSalida({
            ticket_id: ticketCobroActual.id,
            patente: ticketCobroActual.patente,
            fecha_ingreso: ticketCobroActual.fecha_ingreso,
            fecha_salida: ahora.toLocaleString('es-AR'),
            tiempo_estadia: tiempoTexto,
            monto: total,
            metodo_pago: metodo,
            fecha_pago: ahora.toLocaleString('es-AR')
          });
          
          if (resultado.success) {
            console.log('✅ Ticket de salida impreso con nuevo servicio.');
            // Mostrar toast bonito de éxito
            mostrarToastBonito('🎫 Comprobante impreso correctamente', 'success');
          } else {
            console.warn('⚠️ Nuevo servicio falló, intentando método antiguo...');
            throw new Error('Fallback al método antiguo');
          }
        } else {
          throw new Error('PrintService no disponible');
        }
      } catch (errorNuevo) {
        // 🔄 FALLBACK: Método antiguo
        try {
          console.log('📄 Usando método de impresión antiguo (ticketsalida.php)...');
          const responseImprimir = await fetch('../ImpresionTermica/ticketsalida.php', {
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
          if (dataImprimir.trim() === '1') {
            // Mostrar toast bonito de éxito para método antiguo también
            mostrarToastBonito('🎫 Comprobante impreso correctamente', 'success');
          }
        } catch (errorImprimir) {
          console.warn('⚠️ No se pudo imprimir el comprobante:', errorImprimir);
        }
      } finally {
        // Limpiar UI independientemente del resultado de la impresión
        resetearCobro();
      }
    } else {
      // Para pagos con TUU (incluyendo efectivo), el POS imprime el voucher.
      console.log(`ℹ️ Pago con ${metodo}. La impresión la maneja el terminal POS.`);
    }

    // Limpiar UI para pagos TUU (los manuales se limpian en el finally)
    if (metodo !== 'MANUAL') resetearCobro();
  }

  function resetearCobro() {
    ticketCobroActual = null;
    if (resultadoCobro) resultadoCobro.classList.add('d-none');
    if (formCobroSalida) formCobroSalida.reset();
    if (btnCobrarTicket) btnCobrarTicket.disabled = true;
    if (btnPagarTuu) btnPagarTuu.disabled = true;
    
    // Ocultar botón de confirmación manual
    ocultarBotonConfirmacionManual();
    
    // Resetear modal de pago manual
    const motivoManual = document.getElementById('motivo-pago-manual');
    if (motivoManual) motivoManual.value = 'Pago en efectivo'; // Valor predeterminado para agilizar
    const metodoPagoManual = document.getElementById('metodo-pago-manual');
    if (metodoPagoManual) metodoPagoManual.value = 'EFECTIVO';
    
    // Resetear modal TUU
    const btnConfirmarTUU = document.getElementById('btn-confirmar-pago-tuu');
    if (btnConfirmarTUU) {
      btnConfirmarTUU.disabled = false;
      btnConfirmarTUU.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar y Pagar con TUU';
    }
    
    const spinner = document.getElementById('spinner-pago-tuu');
    if (spinner) spinner.classList.add('d-none');
    
    // Resetear radio buttons de método de pago a débito (ya no hay efectivo en TUU)
    const metodoDebito = document.getElementById('metodoDebitoTUU');
    if (metodoDebito) metodoDebito.checked = true;
    
    // Resetear tipo de documento a boleta
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

  function actualizarToastConHTML(id, mensajeHTML, estado) {
    const toastElement = document.getElementById(id);
    if (!toastElement) return;

    const toastBody = toastElement.querySelector('.toast-body');
    toastBody.innerHTML = mensajeHTML; // Permite HTML en el mensaje
    toastElement.classList.add(estado === 'success' ? 'bg-success-subtle' : 'bg-warning-subtle');
  }

  // 🔄 MÓDULO DE VERIFICACIÓN DE ESTADO TUU PARA RED LOCAL
  let verificacionActiva = false;
  let timeoutVerificacion = null;

  async function iniciarVerificacionEstadoTUU(toastId, transactionId) {
    if (verificacionActiva) {
      console.log('🔄 Ya hay una verificación activa de TUU');
      return;
    }

    verificacionActiva = true;
    const tiempoInicio = Date.now();
    const timeoutMaximo = 120000; // 2 minutos máximo
    const intervaloVerificacion = 3000; // Verificar cada 3 segundos

    actualizarToast(toastId, `⏳ Verificando pago para ${ticketCobroActual.patente}...`, 'info');

    console.log(`🔄 Iniciando verificación de estado TUU para transacción: ${transactionId}`);

    const verificarEstado = async () => {
      try {
        // Usar nuestro endpoint optimizado para red local
        const response = await fetch(`${BASE_PATH}/tuu-status-websocket.php?action=check_status&transaction_id=${encodeURIComponent(transactionId)}`);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success && data.status === 'completed') {
          // ✅ Pago confirmado
          console.log('✅ Pago TUU confirmado:', data.data);
          
          actualizarToast(toastId, `✅ ¡Pago confirmado! Cliente ${data.data.patente || ticketCobroActual.patente} pagó exitosamente`, 'success');
          
          // Forzar actualización del estado
          await finalizarCobroExitoso('TUU', ticketCobroActual.total, data.data);
          
          verificacionActiva = false;
          if (timeoutVerificacion) clearTimeout(timeoutVerificacion);
          return true;
          
        } else if (data.success && data.status === 'pending') {
          // ⏳ Pago aún pendiente, continuar verificando
          const tiempoTranscurrido = Date.now() - tiempoInicio;
          
          if (tiempoTranscurrido >= timeoutMaximo) {
            // ⏰ Timeout alcanzado - ofrecer confirmación manual
            const mensajeTimeout = `⏰ Timeout: No se pudo confirmar el pago para ${ticketCobroActual.patente}<br><br>
                                   <button class="btn btn-sm btn-success" onclick="confirmarPagoManualTUU('${transactionId}', '${ticketCobroActual.patente}', '${toastId}')">
                                     <i class="fas fa-check"></i> Confirmar Pago Manual
                                   </button>`;
            actualizarToastConHTML(toastId, mensajeTimeout, 'warning');
            verificacionActiva = false;
            return false;
          }
          
          // Programar siguiente verificación
          timeoutVerificacion = setTimeout(verificarEstado, intervaloVerificacion);
          
          // Actualizar mensaje con tiempo restante
          const tiempoRestante = Math.max(0, Math.floor((timeoutMaximo - tiempoTranscurrido) / 1000));
          actualizarToast(toastId, `⏳ Esperando confirmación... (${tiempoRestante}s restantes)`, 'info');
          
        } else {
          throw new Error(data.error || 'Error desconocido en verificación');
        }
        
      } catch (error) {
        console.error('❌ Error verificando estado TUU:', error);
        
        const tiempoTranscurrido = Date.now() - tiempoInicio;
        if (tiempoTranscurrido >= timeoutMaximo) {
          actualizarToast(toastId, `❌ Error verificando pago: ${error.message}`, 'danger');
          verificacionActiva = false;
          return false;
        }
        
        // Reintentar en caso de error de conexión
        timeoutVerificacion = setTimeout(verificarEstado, intervaloVerificacion);
      }
    };

    // Iniciar primera verificación
    verificarEstado();
  }

  // 🔄 VERIFICACIÓN PERIÓDICA DE PAGOS PENDIENTES (para múltiples transacciones)
  let ultimaVerificacionPagos = Date.now();

  async function verificarPagosPendientes() {
    try {
      const response = await fetch(`${BASE_PATH}/tuu-status-websocket.php?action=poll_status&last_check=${Math.floor(ultimaVerificacionPagos / 1000)}`);
      
      if (response.ok) {
        const data = await response.json();
        
        if (data.success && data.nuevos_pagos && data.nuevos_pagos.length > 0) {
          console.log('💰 Nuevos pagos TUU detectados:', data.nuevos_pagos);
          
          // Notificar pagos nuevos encontrados
          data.nuevos_pagos.forEach(pago => {
            if (pago.status === 'completed') {
              mostrarToastBonito(`✅ Pago TUU confirmado: ${pago.patente} - $${pago.total.toLocaleString('es-CL')}`, 'success');
            }
          });
        }
        
        ultimaVerificacionPagos = Date.now();
      }
    } catch (error) {
      console.log('Info: Verificación de pagos pendientes falló (normal en red local):', error.message);
    }
  }

  // Iniciar verificación periódica cada 10 segundos (opcional)
  setInterval(verificarPagosPendientes, 10000);
});

// 🔧 Función global para confirmar pago manual de TUU
async function confirmarPagoManualTUU(transactionId, patente, toastId) {
  try {
    console.log('🔧 Confirmando pago manual TUU:', { transactionId, patente });
    
    const response = await fetch(`${BASE_PATH}/tuu-status-websocket.php?action=confirm_manual_payment`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        transaction_id: transactionId,
        patente: patente
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();
    
    if (data.success && data.status === 'completed') {
      console.log('✅ Pago manual confirmado:', data.data);
      actualizarToast(toastId, `✅ ¡Pago confirmado manualmente! Cliente ${patente} pagó exitosamente`, 'success');
      
      // Intentar finalizar el cobro
      if (typeof finalizarCobroExitoso === 'function') {
        // Buscar el ticket actual (necesitamos acceso a ticketCobroActual)
        const ticketActual = obtenerTicketActual();
        if (ticketActual) {
          await finalizarCobroExitoso('TUU', ticketActual.total, data.data);
        }
      }
      
      // Recargar la página después de un breve delay
      setTimeout(() => {
        window.location.reload();
      }, 2000);
      
    } else {
      throw new Error(data.error || 'Error desconocido al confirmar pago manual');
    }
    
  } catch (error) {
    console.error('❌ Error confirmando pago manual:', error);
    actualizarToast(toastId, `❌ Error al confirmar pago: ${error.message}`, 'danger');
  }
}

// Función auxiliar para obtener el ticket actual (si está disponible)
function obtenerTicketActual() {
  // Esta función depende de cómo esté estructurada tu aplicación
  // Puede necesitar ajustes según tu implementación específica
  if (typeof ticketCobroActual !== 'undefined') {
    return ticketCobroActual;
  }
  
  // Intentar obtener desde el DOM o variables globales
  return null;
}

// Variables globales para el botón de confirmación manual
let transactionIdActual = null;
let patenteActual = null;
let totalActual = null;

/**
 * Muestra el botón de confirmación manual para pagos TUU pendientes
 */
function mostrarBotonConfirmacionManual(transactionId, patente, total) {
  transactionIdActual = transactionId;
  patenteActual = patente;
  totalActual = total;
  
  const container = document.getElementById('confirmar-pago-manual-container');
  if (container) {
    container.classList.remove('d-none');
    console.log('🔧 Botón de confirmación manual mostrado para:', patente, 'Total:', total);
  }
}

/**
 * Oculta el botón de confirmación manual
 */
function ocultarBotonConfirmacionManual() {
  const container = document.getElementById('confirmar-pago-manual-container');
  if (container) {
    container.classList.add('d-none');
  }
  transactionIdActual = null;
  patenteActual = null;
  totalActual = null;
}

// Event listener para el botón de confirmación manual
document.addEventListener('DOMContentLoaded', () => {
  const btnConfirmarPagoManualTUU = document.getElementById('btn-confirmar-pago-manual-tuu');
  
  if (btnConfirmarPagoManualTUU) {
    btnConfirmarPagoManualTUU.addEventListener('click', async () => {
      if (!transactionIdActual || !patenteActual || !totalActual) {
        mostrarAlerta('Error: No hay datos de transacción para confirmar', 'danger');
        return;
      }

      if (!confirm(`¿Confirmar pago de ${patenteActual} por ${new Intl.NumberFormat('es-CL', { 
        style: 'currency', 
        currency: 'CLP' 
      }).format(totalActual)}?`)) {
        return;
      }

      try {
        // Mostrar loading en el botón
        const boton = btnConfirmarPagoManualTUU;
        const textoOriginal = boton.innerHTML;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirmando...';
        boton.disabled = true;

        console.log('🔧 Confirmando pago manual TUU:', { 
          transactionId: transactionIdActual, 
          patente: patenteActual, 
          total: totalActual 
        });

        // Hacer la petición de confirmación usando la función existente
        const response = await fetch(`${BASE_PATH}/tuu-status-websocket.php?action=confirm_manual_payment`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            transaction_id: transactionIdActual,
            patente: patenteActual
          })
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.status === 'completed') {
          console.log('✅ Pago manual confirmado:', data.data);
          mostrarAlerta(`✅ ¡Pago confirmado exitosamente para ${patenteActual}!`, 'success');
          
          // Ocultar botón
          ocultarBotonConfirmacionManual();
          
          // Finalizar el cobro usando la función existente
          if (typeof finalizarCobroExitoso === 'function') {
            await finalizarCobroExitoso('TUU', totalActual, data.data);
          }

          // Recargar la página después de un breve delay
          setTimeout(() => {
            window.location.reload();
          }, 1500);

        } else {
          throw new Error(data.error || 'Error desconocido al confirmar pago');
        }

      } catch (error) {
        console.error('❌ Error confirmando pago manual:', error);
        mostrarAlerta(`❌ Error al confirmar pago: ${error.message}`, 'danger');
        
        // Restaurar botón
        const boton = btnConfirmarPagoManualTUU;
        boton.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Pago TUU Manualmente';
        boton.disabled = false;
      }
    });
  }
});