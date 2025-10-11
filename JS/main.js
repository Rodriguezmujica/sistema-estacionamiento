/**
 * main.js
 * Contiene funciones globales y de inicialización para todo el sistema.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Inicializar el reloj en todas las páginas que tengan el elemento #fecha-hora
  if (document.getElementById('fecha-hora')) {
    actualizarFechaHora();
    setInterval(actualizarFechaHora, 1000);
  }
  
  // Cargar y actualizar precio por minuto en el navbar
  cargarPrecioNavbar();
});

/**
 * Actualiza el elemento de fecha y hora en la UI.
 */
function actualizarFechaHora() {
  const ahora = new Date();
  const fechaHora = ahora.toLocaleString('es-CL', {
    timeZone: 'America/Santiago', // ✅ Zona horaria de Chile con DST automático
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false // Formato 24 horas para consistencia
  }).replace(',', ''); // Quita la coma entre fecha y hora

  const elemento = document.getElementById('fecha-hora');
  if (elemento) {
    elemento.textContent = fechaHora;
  }
  
  // También actualizar fecha-sistema si existe (en footer)
  const elementoSistema = document.getElementById('fecha-sistema');
  if (elementoSistema) {
    elementoSistema.textContent = ahora.toLocaleDateString('es-CL', {
      timeZone: 'America/Santiago'
    });
  }
}

/**
 * Muestra una alerta global usando Bootstrap.
 * @param {string} mensaje - El mensaje a mostrar.
 * @param {string} tipo - El tipo de alerta (e.g., 'success', 'warning', 'danger').
 */
function mostrarAlerta(mensaje, tipo = 'info') {
  // Implementación de la alerta (ya existe en otros archivos, se puede centralizar aquí)
  console.log(`ALERTA [${tipo}]: ${mensaje}`);
}

/**
 * Carga el precio por minuto desde la configuración y actualiza el badge del navbar
 */
async function cargarPrecioNavbar() {
  try {
    const response = await fetch('/sistemaEstacionamiento/api/api_precios.php');
    const result = await response.json();
    
    if (result.success) {
      const precioMinuto = result.data.precio_minuto;
      
      // Actualizar el badge en el navbar
      const badgePrecio = document.querySelector('.badge.bg-success');
      if (badgePrecio && badgePrecio.textContent.includes('$')) {
        badgePrecio.innerHTML = `<i class="fas fa-dollar-sign"></i> $${precioMinuto}/min`;
      }
      
      console.log('✅ Precio por minuto cargado: $' + precioMinuto);
    }
  } catch (error) {
    console.warn('⚠️ No se pudo cargar el precio desde configuración, usando valor por defecto');
    // Si falla, mantiene el valor por defecto del HTML
  }
}