/**
 * Script.js
 * 
 * Orquestador principal de la aplicación.
 * Este archivo NO debe contener lógica específica de una sección.
 * Su única función es detectar en qué página estamos y cargar
 * los módulos de JavaScript correspondientes.
 * 
 * Módulos disponibles:
 * - main.js: Lógica global (reloj, alertas). Se carga siempre.
 * - ingreso.js: Para el formulario de ingreso de vehículos.
 * - cobro.js: Para la sección de cobro de salidas.
 * - modal-lavado.js: Lógica del modal de registro de lavados.
 * - modal-modificar-ticket.js: Lógica del modal para modificar tickets.
 * - reporte.js: Para la página de reportes.
 * - lavados.js: Para la página de gestión de lavados.
 * - admin.js: Para el panel de administración.
 */

document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 Aplicación inicializada.');
  // Este es un buen lugar para inicializar módulos que se usan en múltiples páginas,
  // pero es mejor hacerlo dentro de cada script específico para mantener el orden.
});
