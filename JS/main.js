/**
 * main.js
 * Contiene la lógica compartida y utilidades para toda la aplicación.
 */

// Función para mostrar alertas reutilizable
function mostrarAlerta(mensaje, tipo = 'info') {
  const alertContainer = document.getElementById('alert-container') || document.querySelector('main') || document.body;
  
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${tipo} alert-dismissible fade show m-3`;
  alertDiv.style.position = 'fixed';
  alertDiv.style.top = '20px';
  alertDiv.style.right = '20px';
  alertDiv.style.zIndex = '1056'; // Encima de los modales de Bootstrap
  alertDiv.innerHTML = `
    ${mensaje}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  alertContainer.appendChild(alertDiv);
  
  setTimeout(() => {
    const alertInstance = bootstrap.Alert.getOrCreateInstance(alertDiv);
    if (alertInstance) {
      alertInstance.close();
    }
  }, 5000);
}

// Función para actualizar fecha y hora
function actualizarFechaHora() {
  const ahora = new Date();
  const fechaHora = ahora.toLocaleString('es-CL', { timeZone: 'America/Santiago', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' });
  
  const elementosFecha = document.querySelectorAll('.fecha-hora-dinamica');
  elementosFecha.forEach(el => el.textContent = fechaHora);
}

// Inicialización global
document.addEventListener('DOMContentLoaded', () => {
  actualizarFechaHora();
  setInterval(actualizarFechaHora, 1000);
});