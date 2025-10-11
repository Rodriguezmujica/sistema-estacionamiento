/**
 * modal-lavado.js
 * Maneja la lógica del modal avanzado para registrar o modificar lavados.
 */

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

  // --- FUNCIONES AUXILIARES ---

  function cargarServiciosEnModal() {
    return fetch('./api/api_servicios_lavado.php')
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
        mostrarAlerta('Error al cargar servicios de lavado', 'danger');
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
      .then(data => {
        if (data.success) {
          const mensaje = esModificacion ? '✅ Ticket modificado a lavado' : '✅ Lavado registrado';
          mostrarAlerta(mensaje, 'success');
          
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
          mostrarAlerta(`❌ Error: ${data.error || 'No se pudo completar la operación'}`, 'danger');
        }
      })
      .catch(error => {
        console.error('Error en formulario de lavado:', error);
        mostrarAlerta(`❌ Error de conexión: ${error.message}`, 'danger');
      });
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