// Script dedicado solo para el modal de modificar ticket
console.log('🔧 Script modal-modificar-ticket.js cargado');

// --- CONFIGURACIÓN DE RUTAS ---
if (typeof getBasePath === 'undefined') {
  window.getBasePath = () => {
    const path = window.location.pathname;
    const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
    return baseMatch ? baseMatch[1] : '';
  };
}

// Usar la función global definida en main.js
// No declarar BASE_PATH aquí para evitar conflictos

// Función para mostrar alertas elegantes
function mostrarAlerta(mensaje, tipo = 'info') {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
  alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
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
  }, 4000);
}

document.addEventListener('DOMContentLoaded', function() {
  console.log('✅ DOM listo para modal modificar ticket');
  
  // Obtener elementos del modal
  const modal = document.getElementById('modalModificarTicket');
  const btnBuscar = document.getElementById('btn-buscar-ticket');
  const inputPatente = document.getElementById('patente-modificar');
  const inputTipoActual = document.getElementById('tipo-actual');
  const selectNuevoTipo = document.getElementById('nuevo-tipo');
  const btnGuardar = document.getElementById('btn-guardar-cambio');
  let ticketEncontrado = null; // Variable para guardar el ticket actual
  
  console.log('Elementos encontrados:', {
    modal: !!modal,
    btnBuscar: !!btnBuscar,
    inputPatente: !!inputPatente,
    inputTipoActual: !!inputTipoActual,
    selectNuevoTipo: !!selectNuevoTipo,
    btnGuardar: !!btnGuardar
  });
  
  // Función para cargar solo las 3 opciones permitidas
  function cargarServicios() {
    console.log('📡 Cargando opciones de modificación...');
    
    if (selectNuevoTipo) {
      // Opciones fijas según especificación
      selectNuevoTipo.innerHTML = `
        <option value="">Seleccionar servicio...</option>
        <option value="18">🅿️ Estacionamiento por minuto</option>
        <option value="19">❌ Error de ingreso</option>
        <option value="lavado">🧽 Lavado</option>
      `;
      
      console.log('✅ Opciones cargadas en el select');
    }
  }
  
  // Cargar servicios cuando se abre el modal
  if (modal) {
    modal.addEventListener('show.bs.modal', function() {
      console.log('🔓 Modal abierto');
      cargarServicios();
    });
  }
  
  // Buscar ticket
  if (btnBuscar) {
    btnBuscar.addEventListener('click', function(e) {
      e.preventDefault();
      console.log('🔍 Botón buscar clickeado');
      
      const patente = inputPatente.value.trim().toUpperCase();
      console.log('🚗 Patente:', patente);
      
      if (!patente) {
        mostrarAlerta('⚠️ Por favor ingrese una patente válida', 'warning');
        return;
      }
      
      // Buscar en la API
      fetch(`${window.getBasePathValue()}/api/api_reporte.php`)
        .then(res => res.json())
        .then(data => {
          console.log('📊 Datos de API:', data);
          const ticket = data.find(t => t.patente && t.patente.trim().toUpperCase() === patente);
          console.log('🎫 Ticket encontrado:', ticket);
          ticketEncontrado = ticket; // Guardamos el ticket
          
          if (ticket) {
            // Mostrar el tipo de servicio actual
            inputTipoActual.value = ticket.tipo_servicio || 'Sin tipo';
            btnGuardar.disabled = false;
            mostrarAlerta('✅ Ticket encontrado correctamente', 'success');

          } else {
            inputTipoActual.value = '';
            btnGuardar.disabled = true;
            mostrarAlerta('⚠️ No se encontró ticket activo para esa patente', 'warning');
          }
        })
        .catch(err => {
          console.error('❌ Error:', err);
          mostrarAlerta('❌ Error al buscar ticket: ' + err.message, 'danger');
        });
    });
  } else {
    console.error('❌ Botón buscar NO encontrado');
  }
  
  // Guardar cambio
  if (btnGuardar) {
    btnGuardar.addEventListener('click', function(e) {
      e.preventDefault();
      console.log('💾 Guardando cambio...');
      
      const patente = inputPatente.value.trim().toUpperCase();
      const valorSeleccionado = selectNuevoTipo.value;
      
      if (!patente || !valorSeleccionado) {
        mostrarAlerta('⚠️ Por favor complete todos los campos', 'warning');
        return;
      }

      // 🎯 LÓGICA DE REDIRECCIÓN SEGÚN ESPECIFICACIÓN
      // Si el ticket actual es estacionamiento (ID 18) o error de ingreso (ID 19)
      // Y el usuario selecciona "lavado", redirigir a lavados.html
      
      if (ticketEncontrado) {
        const ticketActualId = ticketEncontrado.idtipo_ingreso || ticketEncontrado.id_tipo_servicio;
        const esEstacionamientoOError = (ticketActualId == 18 || ticketActualId == 19);
        const seleccionoLavado = (valorSeleccionado === 'lavado');
        
        console.log('🔍 Validación:', {
          ticketActualId,
          esEstacionamientoOError,
          seleccionoLavado
        });
        
        if (esEstacionamientoOError && seleccionoLavado) {
          mostrarAlerta('🧽 Redirigiendo a la sección de lavados para gestionar este servicio...', 'info');
          setTimeout(() => {
            window.location.href = `./secciones/lavados.html?patente=${patente}`;
          }, 2000);
          return;
        }
      }

      // Si seleccionó "lavado" pero no pudimos determinar el ticket actual
      if (valorSeleccionado === 'lavado') {
        mostrarAlerta('🧽 Redirigiendo a la sección de lavados...', 'info');
        setTimeout(() => {
          window.location.href = `./secciones/lavados.html?patente=${patente}`;
        }, 2000);
        return;
      }

      // Si llegamos aquí, es una modificación normal (estacionamiento ↔ error)
      // Procedemos a guardar en la base de datos
      console.log('📤 Enviando:', { patente, idServicio: valorSeleccionado });
      
      fetch(`${window.getBasePathValue()}/api/modificar_ticket.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ 
          patente: patente,
          id_nuevo_servicio: valorSeleccionado
        })
      })
        .then(res => res.json())
        .then(data => {
          console.log('📥 Respuesta completa:', data);
          if (data.success) {
            mostrarAlerta('✅ Ticket modificado correctamente', 'success');
            console.log('✅ Detalles:', data);
            
            // Cerrar modal y limpiar campos
            setTimeout(() => {
              const modalInstance = bootstrap.Modal.getInstance(modal);
              if (modalInstance) modalInstance.hide();
              inputPatente.value = '';
              inputTipoActual.value = '';
              selectNuevoTipo.value = '';
              btnGuardar.disabled = true;
              
              // Recargar la página para actualizar los datos
              if (typeof cargarReportesUnificados === 'function') {
                cargarReportesUnificados();
              }
            }, 1500);
            
          } else {
            console.error('❌ Error completo:', data);
            let errorMsg = data.error || 'Error desconocido';
            if (data.debug) {
              console.error('🐛 Debug info:', data.debug);
              errorMsg += '<br><small>Revisa la consola (F12) para más detalles</small>';
            }
            mostrarAlerta('❌ ' + errorMsg, 'danger');
          }
        })
        .catch(err => {
          console.error('❌ Error de red:', err);
          mostrarAlerta('❌ Error de conexión: ' + err.message, 'danger');
        });
    });
  } else {
    console.error('❌ Botón guardar NO encontrado');
  }
  
});
