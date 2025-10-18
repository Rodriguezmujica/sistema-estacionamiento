/**
 * ingreso.js
 * Maneja la lógica del formulario de ingreso de vehículos.
 */

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
  const getBasePath = () => {
    const path = window.location.pathname;
    const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
    return baseMatch ? baseMatch[1] : '';
  };
  const BASE_PATH = getBasePath();

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
        mostrarAlerta('✅ Ingreso registrado correctamente.', 'success');
        formIngreso.reset();
        patenteIngreso.focus();
        imprimirTicketIngreso(data.id_ingreso, patente, servicioId, nombreCliente);
        // Opcional: actualizar alguna tabla de reportes si está visible
        if (typeof cargarReportesUnificados === 'function') {
          cargarReportesUnificados();
        }
      } else {
        mostrarAlerta(`❌ Error: ${data.error || 'No se pudo registrar el ingreso.'}`, 'danger');
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
      const servicioTexto = servicioIdSelect.options[servicioIdSelect.selectedIndex].text;

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
          console.log('✅ Ticket impreso con nuevo servicio.');
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
      } else {
        console.warn('⚠️ La impresora respondió, pero hubo un problema:', resultado);
        mostrarAlerta('Ingreso registrado, pero la impresión del ticket falló.', 'warning');
      }
    } catch (error) {
      console.error('❌ Error de conexión con el servicio de impresión:', error);
      mostrarAlerta('Ingreso registrado, pero el servicio de impresión no está disponible.', 'warning');
    }
  }
});