// Script dedicado solo para el modal de modificar ticket
console.log('🔧 Script modal-modificar-ticket.js cargado');

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
  
  console.log('Elementos encontrados:', {
    modal: !!modal,
    btnBuscar: !!btnBuscar,
    inputPatente: !!inputPatente,
    inputTipoActual: !!inputTipoActual,
    selectNuevoTipo: !!selectNuevoTipo,
    btnGuardar: !!btnGuardar
  });
  
  // Función para cargar servicios
  function cargarServicios() {
    console.log('📡 Cargando todos los servicios...');
    fetch('./api/api_todos_servicios.php')
      .then(res => {
        console.log('✅ Respuesta servicios:', res.status);
        return res.json();
      })
      .then(servicios => {
        console.log('📦 Servicios recibidos:', servicios);
        if (selectNuevoTipo && Array.isArray(servicios)) {
          selectNuevoTipo.innerHTML = '<option value="">Seleccionar servicio...</option>';
          
          // Separar servicios por categoría
          const estacionamiento = servicios.filter(s => s.nombre_servicio.toLowerCase().includes('estacionamiento'));
          const lavado = servicios.filter(s => !s.nombre_servicio.toLowerCase().includes('estacionamiento'));
          
          // Agregar grupo de Estacionamiento
          if (estacionamiento.length > 0) {
            const optgroupEst = document.createElement('optgroup');
            optgroupEst.label = '🚗 Estacionamiento';
            estacionamiento.forEach(s => {
              const option = document.createElement('option');
              option.value = s.idtipo_ingresos;
              option.textContent = `${s.nombre_servicio} ($${s.precio || 0})`;
              optgroupEst.appendChild(option);
            });
            selectNuevoTipo.appendChild(optgroupEst);
          }
          
          // Agregar grupo de Lavado
          if (lavado.length > 0) {
            const optgroupLav = document.createElement('optgroup');
            optgroupLav.label = '🧼 Lavado';
            lavado.forEach(s => {
              const option = document.createElement('option');
              option.value = s.idtipo_ingresos;
              option.textContent = `${s.nombre_servicio} ($${s.precio || 0})`;
              optgroupLav.appendChild(option);
            });
            selectNuevoTipo.appendChild(optgroupLav);
          }
          
          console.log('✅ Servicios cargados en el select (con categorías)');
        }
      })
      .catch(err => {
        console.error('❌ Error cargando servicios:', err);
        mostrarAlerta('❌ Error al cargar servicios', 'danger');
      });
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
      fetch('./api/api_reporte.php')
        .then(res => res.json())
        .then(data => {
          console.log('📊 Datos de API:', data);
          const ticket = data.find(t => t.patente && t.patente.trim().toUpperCase() === patente);
          console.log('🎫 Ticket encontrado:', ticket);
          
          if (ticket) {
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
      const idServicio = selectNuevoTipo.value;
      
      if (!patente || !idServicio) {
        mostrarAlerta('⚠️ Por favor complete todos los campos', 'warning');
        return;
      }
      
      console.log('📤 Enviando:', { patente, idServicio });
      
      fetch('./api/modificar_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ 
          patente: patente,
          id_nuevo_servicio: idServicio
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
            }, 1500); // Esperar 1.5 seg para que se vea la alerta
            
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

