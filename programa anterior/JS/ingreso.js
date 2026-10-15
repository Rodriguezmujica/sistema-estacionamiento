/**
 * ingreso.js
 * Maneja la lógica del formulario de ingreso de vehículos.
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
  const formIngreso = document.getElementById('form-ingreso');
  if (!formIngreso) return; // No ejecutar si el formulario no está en la página

  console.log('✅ Módulo de Ingreso inicializado.');

  const patenteIngreso = document.getElementById('patente-ingreso');
  const servicioIdSelect = document.getElementById('tipo-servicio');
  const nombreClienteInput = document.getElementById('nombre-cliente');
  
  // Inicializar el modal una sola vez para reutilizar la instancia
  const modalLavadoElement = document.getElementById('modalLavado');
  const modalLavado = modalLavadoElement ? new bootstrap.Modal(modalLavadoElement) : null;

  // 1. Validar patente duplicada al salir del campo
  if (patenteIngreso) {
    patenteIngreso.addEventListener('blur', function() {
      const patente = this.value.trim().toUpperCase();
      if (patente) {
        verificarPatenteDuplicada(patente);
      }
    });
  }

  // 2. Manejar el envío del formulario
  formIngreso.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const patente = patenteIngreso.value.trim().toUpperCase();
    const servicioId = servicioIdSelect.value;

    if (!patente || !servicioId) {
      mostrarAlerta('Por favor complete la patente y el tipo de servicio.', 'warning');
      return;
    }

    // Validar longitud de patente (máximo 6 caracteres)
    if (patente.length > 6) {
      mostrarAlerta('La patente no puede tener más de 6 caracteres.', 'warning');
      patenteIngreso.focus();
      return;
    }

    // Validar que solo contenga letras y números
    if (!/^[A-Z0-9]+$/.test(patente)) {
      mostrarAlerta('La patente solo puede contener letras y números.', 'warning');
      patenteIngreso.focus();
      return;
    }

    // Si es servicio de lavado, abrir modal completo
    if (servicioId === 'lavado') {
      abrirModalDeLavado(patente);
    } else {
      // Para estacionamiento x minuto
      registrarIngresoSimple(patente, servicioId);
    }
  });

  // --- CONFIGURACIÓN DE RUTAS ---
  // Detectar la ruta base automáticamente
  if (typeof getBasePath === 'undefined') {
    window.getBasePath = () => {
      const path = window.location.pathname;
      const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
      return baseMatch ? baseMatch[1] : '';
    };
  }

  // Usar la variable global ya declarada en main.js
  // No redeclarar BASE_PATH para evitar conflictos

  // --- FUNCIONES AUXILIARES ---

  function verificarPatenteDuplicada(patente) {
    return fetch(`${BASE_PATH}/api/verificar-patente.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ patente })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.existe) {
        const registro = data.registro;
        const fechaIngreso = new Date(registro.fecha_ingreso).toLocaleString('es-CL');
        mostrarAlerta(`
          ⚠️ <strong>Patente duplicada:</strong><br>
          La patente <strong>${registro.patente}</strong> ya tiene un ingreso activo desde el ${fechaIngreso}.<br>
          Servicio: <strong>${registro.servicio}</strong>
        `, 'warning');
        patenteIngreso.focus();
        return true; // La patente existe
      }
      return false; // La patente no existe
    })
    .catch(error => {
      console.error('Error verificando patente:', error);
      return false; // Asumir que no existe en caso de error para no bloquear
    });
  }

  function abrirModalDeLavado(patente) {
    console.log('🚗 Servicio de lavado seleccionado, abriendo modal...');
    
    verificarPatenteDuplicada(patente).then(existe => {
        if (existe) return;

        // Precargar datos en el modal
        const patenteModal = document.getElementById('patente-lavado-modal');
        const clienteModal = document.getElementById('nombre-cliente-lavado-modal');
        if (patenteModal) {
          patenteModal.disabled = false; // Habilitar temporalmente
          patenteModal.value = patente;  // Asignar valor
          patenteModal.disabled = true;  // Volver a deshabilitar
        }
        if (clienteModal && nombreClienteInput) clienteModal.value = nombreClienteInput.value.trim();
        
        // Mostrar modal
        if (modalLavado) modalLavado.show();
    });
  }

  function registrarIngresoSimple(patente, servicioId) {
    console.log('🅿️ Registrando ingreso simple (ej. Estacionamiento)...');
    
    const nombreCliente = nombreClienteInput ? nombreClienteInput.value.trim() : '';
    
    console.log('📝 Datos a enviar:', { patente, servicioId, nombreCliente });
    
    const formData = new FormData();
    formData.append('patente', patente);
    formData.append('tipo_servicio', servicioId); // Usar el ID directamente
    formData.append('nombre_cliente', nombreCliente);

    fetch(`${BASE_PATH}/api/registrar-ingreso.php`, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        mostrarToastBonito('✅ Ingreso registrado correctamente.', 'success');
        formIngreso.reset();
        patenteIngreso.focus();
        imprimirTicketIngreso(data.id_ingreso, patente, servicioId, nombreCliente);
        // Opcional: actualizar alguna tabla de reportes si está visible
        if (typeof cargarReportesUnificados === 'function') {
          cargarReportesUnificados();
        }
      } else {
        mostrarToastBonito(`❌ Error: ${data.error || 'No se pudo registrar el ingreso.'}`, 'error');
      }
    })
    .catch(error => {
      console.error('Error en registro simple:', error);
      mostrarAlerta('❌ Error de conexión al registrar el ingreso.', 'danger');
    });
  }

  async function imprimirTicketIngreso(idIngreso, patente, servicioId, cliente) {
    console.log('🖨️ Intentando imprimir ticket de ingreso...');
    try {
      // Obtenemos el nombre del servicio para imprimirlo
      let servicioTexto = 'Estacionamiento'; // Valor por defecto
      
      if (servicioIdSelect && servicioIdSelect.selectedIndex >= 0) {
        const selectedOption = servicioIdSelect.options[servicioIdSelect.selectedIndex];
        if (selectedOption && selectedOption.text && selectedOption.text !== 'Seleccionar servicio...') {
          servicioTexto = selectedOption.text;
        }
      }
      
      // Mapear valores específicos si es necesario
      if (servicioId === '18') {
        servicioTexto = 'Estacionamiento por minuto';
      } else if (servicioId === 'lavado') {
        servicioTexto = 'Lavado';
      }

      // 🔧 VALIDAR QUE idIngreso SEA VÁLIDO
      const codigoParaImpresion = idIngreso && idIngreso !== 'undefined' ? idIngreso.toString() : Date.now().toString();
      
      console.log('📝 Datos para impresión:', {
        patente,
        idIngreso,
        codigoParaImpresion,
        servicioTexto,
        cliente
      });

      // 🆕 INTENTAR USAR EL NUEVO SERVICIO DE IMPRESIÓN PRIMERO
      if (typeof PrintService !== 'undefined') {
        console.log('🆕 Usando nuevo servicio de impresión...');
        const fechaActual = new Date();
        const resultado = await PrintService.imprimirTicketIngreso(
          codigoParaImpresion,
          patente || 'SIN-PATENTE',
          servicioTexto || 'Estacionamiento',
          fechaActual.toLocaleDateString('es-AR'),
          fechaActual.toLocaleTimeString('es-AR')
        );
        
        if (resultado.success) {
          console.log('✅ Ticket de ingreso enviado a imprimir con nuevo servicio.');
          return; // Salir si funcionó
        } else {
          console.warn('⚠️ Nuevo servicio falló, intentando método antiguo...');
        }
      }

      // 🔄 FALLBACK: Usar método antiguo si el nuevo no está disponible o falló
      console.log('📄 Usando método de impresión antiguo (ticket.php)...');
      const response = await fetch('./ImpresionTermica/ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          patente: patente || 'SIN-PATENTE',
          tipo_ingreso: codigoParaImpresion, // 🔧 USAR CÓDIGO VALIDADO
          servicio_cliente: servicioTexto || 'Estacionamiento',
          nombre_cliente: cliente || '',
          hora_ingreso: new Date().toLocaleTimeString('es-CL')
        })
      });

      const resultado = await response.text();
      console.log('📄 Respuesta de impresión:', resultado);
      
      if (resultado.trim() === '1') {
        console.log('✅ Ticket de ingreso enviado a la impresora.');
        // No mostramos nada, el éxito ya se notificó al registrar.
      } else {
        console.warn('⚠️ La impresora respondió, pero hubo un problema:', resultado);
        // No mostramos alerta, es solo una advertencia.
      }
    } catch (error) {
      console.error('❌ Error de conexión con el servicio de impresión:', error);
      // No mostramos alerta, el ingreso fue exitoso.
    }
  }
});