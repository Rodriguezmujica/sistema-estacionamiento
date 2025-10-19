/**
 * modal-lavado.js
 * Maneja la lógica del modal avanzado para registrar o modificar lavados.
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
  const modalLavadoElement = document.getElementById('modalLavado');
  if (!modalLavadoElement) return;

  console.log('✅ Módulo Modal Lavado inicializado.');

  let serviciosLavadoModal = [];
  const formLavadoModal = document.getElementById('form-lavado-modal');
  const tipoLavadoSelect = document.getElementById('tipo-lavado-modal');
  const precioExtraInput = document.getElementById('precio-extra-modal');
  const motivosCheckboxes = document.querySelectorAll('.motivo-extra');

  // Cargar servicios cuando se abre el modal
  modalLavadoElement.addEventListener('show.bs.modal', () => {
    const esModificacion = formLavadoModal.hasAttribute('data-modificacion');
    if (!esModificacion) {
      resetModal();
      cargarServiciosEnModal();
    }
  });

  // Event listeners para calcular el precio
  if (tipoLavadoSelect) tipoLavadoSelect.addEventListener('change', calcularPrecioTotalModal);
  if (precioExtraInput) precioExtraInput.addEventListener('input', calcularPrecioTotalModal);
  motivosCheckboxes.forEach(checkbox => checkbox.addEventListener('change', calcularPrecioTotalModal));

  // Envío del formulario
  if (formLavadoModal) {
    formLavadoModal.addEventListener('submit', (e) => {
      e.preventDefault();
      enviarFormularioLavadoModal();
    });
  }

  // --- CONFIGURACIÓN DE RUTAS ---
  const getBasePath = () => {
    const path = window.location.pathname;
    const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
    return baseMatch ? baseMatch[1] : '';
  };
  const BASE_PATH = getBasePath();

  // --- FUNCIONES AUXILIARES ---

  function cargarServiciosEnModal() {
    return fetch(`${BASE_PATH}/api/api_servicios_lavado.php`)
      .then(response => response.json())
      .then(data => {
        if (!data.success) {
          throw new Error(data.error || 'La API de servicios devolvió un error.');
        }
        serviciosLavadoModal = data.data;
        if (tipoLavadoSelect) {
          tipoLavadoSelect.innerHTML = '<option value="">Seleccionar servicio...</option>';
          // Filtrar solo los servicios activos
          const serviciosActivos = serviciosLavadoModal.filter(s => parseInt(s.activo) === 1);
          serviciosActivos.forEach(servicio => {
            const option = document.createElement('option');
            option.value = servicio.idtipo_ingresos;
            option.textContent = `${servicio.nombre_servicio} ($${parseInt(servicio.precio).toLocaleString('es-CL')})`;
            option.setAttribute('data-precio', servicio.precio);
            tipoLavadoSelect.appendChild(option);
          });
        }
      })
      .catch(error => {
        console.error('Error al cargar servicios:', error);
        mostrarToastBonito('Error al cargar servicios de lavado', 'error');
      });
  }

  function calcularPrecioTotalModal() {
    const precioBaseResumen = document.getElementById('precio-base-resumen');
    const precioExtraResumen = document.getElementById('precio-extra-resumen');
    const precioTotalResumen = document.getElementById('precio-total-resumen');

    const servicioId = tipoLavadoSelect.value;
    let precioBase = 0;

    if (servicioId && serviciosLavadoModal.length > 0) {
      const servicioSeleccionado = serviciosLavadoModal.find(s => s.idtipo_ingresos == servicioId);
      precioBase = servicioSeleccionado ? parseFloat(servicioSeleccionado.precio) : 0;
    }

    const precioExtra = parseFloat(precioExtraInput.value) || 0;
    const precioTotal = precioBase + precioExtra;

    if (precioBaseResumen) precioBaseResumen.textContent = `$${precioBase.toLocaleString('es-CL')}`;
    if (precioExtraResumen) precioExtraResumen.textContent = `$${precioExtra.toLocaleString('es-CL')}`;
    if (precioTotalResumen) precioTotalResumen.textContent = `$${precioTotal.toLocaleString('es-CL')}`;
  }

  function enviarFormularioLavadoModal() {
    const patente = document.getElementById('patente-lavado-modal').value.trim().toUpperCase();
    const tipoLavado = tipoLavadoSelect.value;
    const nombreCliente = document.getElementById('nombre-cliente-lavado-modal').value.trim();
    const precioExtra = parseFloat(precioExtraInput.value) || 0;
    const descripcion = document.getElementById('descripcion-extra-modal').value.trim();

    const motivos = Array.from(document.querySelectorAll('.motivo-extra:checked')).map(cb => cb.value);

    if (!patente || !tipoLavado) {
      mostrarAlerta('Patente y tipo de lavado son obligatorios', 'warning');
      return;
    }

    const servicioSeleccionado = serviciosLavadoModal.find(s => s.idtipo_ingresos == tipoLavado);
    const precioBase = servicioSeleccionado ? parseFloat(servicioSeleccionado.precio) : 0;
    const precioTotal = precioBase + precioExtra;

    const esModificacion = formLavadoModal.getAttribute('data-modificacion') === 'true';
    const idIngreso = formLavadoModal.getAttribute('data-id-ingreso');

    const resumen = `
      Resumen del ${esModificacion ? 'lavado modificado' : 'lavado'}:
      • Patente: ${patente}
      • Servicio: ${servicioSeleccionado?.nombre_servicio || 'N/A'}
      • Total: $${precioTotal.toLocaleString('es-CL')}
    `;

    if (confirm(`${resumen}\n\n¿Confirmar la operación?`)) {
      const formData = new FormData();
      formData.append('patente', patente);
      formData.append('id_servicio', tipoLavado);
      formData.append('nombre_cliente', nombreCliente);
      formData.append('precio_extra', precioExtra);
      formData.append('motivos_extra', JSON.stringify(motivos));
      formData.append('descripcion_extra', descripcion);

      const apiUrl = esModificacion ? './api/modificar-lavado.php' : './api/registrar-lavado.php';
      if (esModificacion && idIngreso) {
        formData.append('id_ingreso', idIngreso);
      }

      fetch(apiUrl, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(async (data) => { // Convertir a función asíncrona
        if (data.success) {
          const mensajeExito = esModificacion ? '✅ Ticket modificado a lavado' : '✅ Lavado registrado';
          mostrarToastBonito(mensajeExito, 'success');
          
          // 🖨️ Llamar a la nueva función de impresión robusta
          imprimirTicketLavadoConFallback(data, patente, servicioSeleccionado, precioTotal, esModificacion);

          const modalInstance = bootstrap.Modal.getInstance(modalLavadoElement);
          if (modalInstance) modalInstance.hide();
          
          resetModal();
          if (!esModificacion) {
            const formIngreso = document.getElementById('form-ingreso');
            if (formIngreso) formIngreso.reset();
          }
          
          if (typeof cargarReportesUnificados === 'function') {
            cargarReportesUnificados();
          }
        } else {
          mostrarToastBonito(`❌ Error: ${data.error || 'No se pudo completar la operación'}`, 'error');
        }
      })
      .catch(error => {
        console.error('Error en formulario de lavado:', error);
        mostrarToastBonito(`❌ Error de conexión: ${error.message}`, 'error');
      });
    }
  }

  /**
   * Imprime el ticket de lavado usando el nuevo servicio, con fallback al antiguo.
   * Lógica copiada y adaptada de ingreso.js para máxima compatibilidad.
   */
  async function imprimirTicketLavadoConFallback(data, patente, servicio, total, esModificacion = false) {
    console.log('🖨️ Intentando imprimir ticket de lavado...');
    
    // 🔧 VALIDAR QUE idIngreso SEA VÁLIDO (igual que ingreso.js)
    const idParaImpresion = data.id_ingreso || data.data?.id_ingreso;
    const codigoParaImpresion = idParaImpresion && idParaImpresion !== 'undefined' ? idParaImpresion.toString() : Date.now().toString();
    
    const nombreServicio = servicio?.nombre_servicio || 'Lavado';
    const nombreCliente = document.getElementById('nombre-cliente-lavado-modal')?.value.trim() || '';

    try {
      console.log('📝 Datos para impresión de lavado:', {
        patente,
        idParaImpresion,
        codigoParaImpresion,
        nombreServicio,
        nombreCliente,
        total
      });

      // 🆕 INTENTAR USAR EL NUEVO SERVICIO DE IMPRESIÓN PRIMERO (igual que ingreso.js)
      if (typeof PrintService !== 'undefined') {
        console.log('🆕 Usando nuevo servicio de impresión para lavado...');
        const fechaActual = new Date();
        
        // Intentar con el método específico de lavado si existe
        let resultado;
        if (typeof PrintService.imprimirTicketLavado === 'function') {
          resultado = await PrintService.imprimirTicketLavado(
            codigoParaImpresion,
            patente || 'SIN-PATENTE',
            nombreServicio || 'Lavado',
            total,
            fechaActual.toLocaleString('es-AR')
          );
        } else {
          // Fallback al método de ingreso si no existe el específico de lavado
          resultado = await PrintService.imprimirTicketIngreso(
            codigoParaImpresion,
            patente || 'SIN-PATENTE',
            nombreServicio || 'Lavado',
            fechaActual.toLocaleDateString('es-AR'),
            fechaActual.toLocaleTimeString('es-AR')
          );
        }
        
        if (resultado.success) {
          console.log('✅ Ticket de lavado impreso con nuevo servicio.');
          mostrarToastBonito('🎫 Ticket de lavado impreso correctamente', 'success');
          return; // Salir si funcionó
        } else {
          console.warn('⚠️ Nuevo servicio falló, intentando método antiguo...');
        }
      }

      // 🔄 FALLBACK: Usar método antiguo si el nuevo no está disponible o falló (igual que ingreso.js)
      console.log('📄 Usando método de impresión antiguo (ticket.php) para lavado...');
      const response = await fetch('./ImpresionTermica/ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          patente: patente || 'SIN-PATENTE',
          tipo_ingreso: codigoParaImpresion, // 🔧 USAR CÓDIGO VALIDADO
          servicio_cliente: nombreServicio || 'Lavado',
          nombre_cliente: nombreCliente || '',
          hora_ingreso: new Date().toLocaleTimeString('es-CL')
        })
      });

      const resultado = await response.text();
      console.log('📄 Respuesta de impresión de lavado:', resultado);
      
      if (resultado.trim() === '1') {
        console.log('✅ Ticket de lavado enviado a la impresora.');
        mostrarToastBonito('🎫 Ticket de lavado impreso correctamente', 'success');
        // No mostramos nada adicional, el éxito ya se notificó
      } else {
        console.warn('⚠️ La impresora respondió, pero hubo un problema con el ticket de lavado:', resultado);
        mostrarToastBonito('Lavado registrado, pero la impresión del ticket falló.', 'warning');
      }

    } catch (error) {
      console.error('❌ Error de conexión con el servicio de impresión de lavado:', error);
      mostrarToastBonito('Lavado registrado, pero el servicio de impresión no está disponible.', 'warning');
    }
  }

  function resetModal() {
    if (formLavadoModal) {
      // No usar form.reset() porque borra la patente prellenada.
      // Limpiamos los campos manualmente, excepto la patente.
      document.getElementById('tipo-lavado-modal').value = '';
      document.getElementById('nombre-cliente-lavado-modal').value = '';
      document.getElementById('precio-extra-modal').value = '0';
      document.getElementById('descripcion-extra-modal').value = '';
      document.querySelectorAll('.motivo-extra:checked').forEach(cb => cb.checked = false);

      formLavadoModal.removeAttribute('data-modificacion');
      formLavadoModal.removeAttribute('data-id-ingreso');
    }
    
    const precioBaseResumen = document.getElementById('precio-base-resumen');
    const precioExtraResumen = document.getElementById('precio-extra-resumen');
    const precioTotalResumen = document.getElementById('precio-total-resumen');

    if (precioBaseResumen) precioBaseResumen.textContent = '$0';
    if (precioExtraResumen) precioExtraResumen.textContent = '$0';
    if (precioTotalResumen) precioTotalResumen.textContent = '$0';
  }
});