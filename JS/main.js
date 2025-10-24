/**
 * main.js
 * Contiene funciones globales y de inicialización para todo el sistema.
 */

// --- CONFIGURACIÓN DE RUTAS ---
if (typeof getBasePath === 'undefined') {
  window.getBasePath = () => {
    const path = window.location.pathname;
    const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
    return baseMatch ? baseMatch[1] : '';
  };
}

// Función global para obtener BASE_PATH de forma segura
if (typeof window.getBasePathValue === 'undefined') {
  window.getBasePathValue = () => {
    if (typeof window.BASE_PATH === 'undefined') {
      window.BASE_PATH = getBasePath();
    }
    return window.BASE_PATH;
  };
}

// Usar la función global - declarar como var para evitar conflictos
var BASE_PATH = window.getBasePathValue();

document.addEventListener('DOMContentLoaded', () => {
  // Inicializar el reloj en todas las páginas que tengan el elemento #fecha-hora
  if (document.getElementById('fecha-hora')) {
    actualizarFechaHora();
    setInterval(actualizarFechaHora, 1000);
  }
  
  // Cargar y actualizar precio por minuto en el navbar
  cargarPrecioNavbar();
  
  // Inicializar funcionalidad de mostrar/ocultar estadísticas
  inicializarToggleEstadisticas();
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
    const response = await fetch(`${BASE_PATH}/api/api_precios.php`);
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

/**
 * Inicializa la funcionalidad de mostrar/ocultar estadísticas con iconos de ojos
 */
function inicializarToggleEstadisticas() {
  // Agregar estilos CSS para el hover
  const style = document.createElement('style');
  style.textContent = `
    #toggle-servicios-hoy:hover, #toggle-ingresos-hoy:hover {
      background-color: rgba(0, 0, 0, 0.1) !important;
      transform: scale(1.1);
      transition: all 0.2s ease;
    }
    #toggle-servicios-hoy:hover i, #toggle-ingresos-hoy:hover i {
      color: #007bff !important;
    }
  `;
  document.head.appendChild(style);

  // Toggle para servicios de hoy
  const toggleServicios = document.getElementById('toggle-servicios-hoy');
  const totalHoy = document.getElementById('total-hoy');
  
  if (toggleServicios && totalHoy) {
    // Por defecto, ocultar la información al cargar la página
    totalHoy.classList.add('d-none');
    const icono = toggleServicios.querySelector('i');
    icono.className = 'fas fa-eye-slash text-muted';
    icono.style.fontSize = '12px';
    toggleServicios.title = 'Mostrar información';
    
    toggleServicios.addEventListener('click', () => {
      const estaVisible = !totalHoy.classList.contains('d-none');
      
      if (estaVisible) {
        // Ocultar
        totalHoy.classList.add('d-none');
        icono.className = 'fas fa-eye-slash text-muted';
        icono.style.fontSize = '12px';
        toggleServicios.title = 'Mostrar información';
      } else {
        // Mostrar
        totalHoy.classList.remove('d-none');
        icono.className = 'fas fa-eye text-muted';
        icono.style.fontSize = '12px';
        toggleServicios.title = 'Ocultar información';
      }
    });
  }
  
  // Toggle para ingresos de hoy
  const toggleIngresos = document.getElementById('toggle-ingresos-hoy');
  const ingresosHoy = document.getElementById('ingresos-hoy');
  
  if (toggleIngresos && ingresosHoy) {
    // Por defecto, ocultar la información al cargar la página
    ingresosHoy.classList.add('d-none');
    const icono = toggleIngresos.querySelector('i');
    icono.className = 'fas fa-eye-slash text-muted';
    icono.style.fontSize = '12px';
    toggleIngresos.title = 'Mostrar información';
    
    toggleIngresos.addEventListener('click', () => {
      const estaVisible = !ingresosHoy.classList.contains('d-none');
      
      if (estaVisible) {
        // Ocultar
        ingresosHoy.classList.add('d-none');
        icono.className = 'fas fa-eye-slash text-muted';
        icono.style.fontSize = '12px';
        toggleIngresos.title = 'Mostrar información';
      } else {
        // Mostrar
        ingresosHoy.classList.remove('d-none');
        icono.className = 'fas fa-eye text-muted';
        icono.style.fontSize = '12px';
        toggleIngresos.title = 'Ocultar información';
      }
    });
  }
}