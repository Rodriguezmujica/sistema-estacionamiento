// --- CONFIGURACIÓN DE RUTAS ---
if (typeof getBasePath === 'undefined') {
  window.getBasePath = () => {
    const path = window.location.pathname;
    const baseMatch = path.match(/^(.*?sistemaEstacionamiento)/);
    return baseMatch ? baseMatch[1] : '';
  };
}

// BASE_PATH se declara en main.js, solo lo usamos aquí
// No declarar para evitar conflictos de redeclaración

function formatDateInputLocal(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// === SISTEMA DE TOASTS ===
function showToast(type, message, duration = 5000) {
  const toastElement = document.getElementById(`toast-${type}`);
  const toastBody = document.getElementById(`toast-${type}-body`);
  
  if (!toastElement || !toastBody) {
    console.error('Toast element not found:', type);
    return;
  }
  
  // Convertir saltos de línea a <br>
  const formattedMessage = message.replace(/\n/g, '<br>');
  toastBody.innerHTML = formattedMessage;
  
  // Crear instancia del toast
  const toast = new bootstrap.Toast(toastElement, {
    autohide: true,
    delay: duration
  });
  
  // Mostrar el toast
  toast.show();
}

// Funciones específicas para cada tipo de toast
function showSuccess(message, duration = 4000) {
  showToast('success', message, duration);
}

function showError(message, duration = 6000) {
  showToast('error', message, duration);
}

function showWarning(message, duration = 5000) {
  showToast('warning', message, duration);
}

function showInfo(message, duration = 4000) {
  showToast('info', message, duration);
}

document.addEventListener('DOMContentLoaded', function() {
  const modalClienteElement = document.getElementById('modalCliente');
  const modalCliente = new bootstrap.Modal(modalClienteElement);

  const modalServicioElement = document.getElementById('modalServicio');
  const modalServicio = new bootstrap.Modal(modalServicioElement);

  const modalUsuarioElement = document.getElementById('modalUsuario');
  if (modalUsuarioElement) {
    const modalUsuario = new bootstrap.Modal(modalUsuarioElement);
  }

  // Variable global para almacenar los servicios cargados
  let serviciosCargados = [];

  // Carga inicial
  cargarDatosAdmin();
  cargarPrecios();
  cargarUsuarios();
  inicializarConsultaFechasAdmin();
// ============================================
// CONSULTA POR FECHAS (Configuración)
// ============================================

let adminServiciosDetalle = [];
let adminUltimaConsulta = null;
let adminUltimasCategorias = [];

function inicializarConsultaFechasAdmin() {
  const fechaDesde = document.getElementById('admin-fecha-desde');
  const fechaHasta = document.getElementById('admin-fecha-hasta');
  const btnConsultar = document.getElementById('admin-btn-consultar-fechas');
  const btnExportExcel = document.getElementById('admin-btn-exportar-excel');
  const btnExportPDF = document.getElementById('admin-btn-exportar-pdf');

  if (!fechaDesde || !fechaHasta || !btnConsultar) {
    return;
  }

  establecerFechasPorDefectoAdmin();
  btnConsultar.addEventListener('click', consultarPorFechasAdmin);

  if (btnExportExcel) {
    btnExportExcel.addEventListener('click', exportarConsultaExcelAdmin);
  }
  if (btnExportPDF) {
    btnExportPDF.addEventListener('click', exportarConsultaPDFAdmin);
  }
}

function establecerFechasPorDefectoAdmin() {
  const fechaDesdeEl = document.getElementById('admin-fecha-desde');
  const fechaHastaEl = document.getElementById('admin-fecha-hasta');

  if (!fechaDesdeEl || !fechaHastaEl) return;

  const hoy = new Date();
  const hace7Dias = new Date(hoy);
  hace7Dias.setDate(hoy.getDate() - 7);

  fechaHastaEl.value = formatDateInputLocal(hoy);
  fechaDesdeEl.value = formatDateInputLocal(hace7Dias);
}

async function consultarPorFechasAdmin() {
  const fechaDesde = document.getElementById('admin-fecha-desde').value;
  const fechaHasta = document.getElementById('admin-fecha-hasta').value;
  const resumen = document.getElementById('admin-resumen-fechas');
  const tbody = document.getElementById('admin-tabla-fechas-body');
  const btn = document.getElementById('admin-btn-consultar-fechas');
  const exportContainer = document.getElementById('admin-export-buttons');
  const btnExportExcel = document.getElementById('admin-btn-exportar-excel');
  const btnExportPDF = document.getElementById('admin-btn-exportar-pdf');

  if (!fechaDesde || !fechaHasta) {
    showWarning('Seleccione ambas fechas antes de consultar.');
    return;
  }

  if (new Date(fechaDesde) > new Date(fechaHasta)) {
    showWarning('La fecha "Desde" no puede ser mayor que la fecha "Hasta".');
    return;
  }

  const originalBtnHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';

  tbody.innerHTML = `
    <tr>
      <td colspan="5" class="text-center text-muted py-4">
        <i class="fas fa-spinner fa-spin"></i> Consultando...
      </td>
    </tr>`;

  if (exportContainer) {
    exportContainer.style.display = 'none';
  }
  if (btnExportExcel) btnExportExcel.disabled = true;
  if (btnExportPDF) btnExportPDF.disabled = true;
  adminUltimaConsulta = null;
  adminUltimasCategorias = [];
  adminServiciosDetalle = [];

  try {
    const params = new URLSearchParams({
      fecha_desde: fechaDesde,
      fecha_hasta: fechaHasta
    });
    const response = await fetch(`${BASE_PATH}/api/api_consulta_fechas.php?${params.toString()}`);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Error al consultar');
    }

    const resumenFechas = data.resumen || { total_servicios: 0, total_ingresos: 0 };
    document.getElementById('admin-total-servicios-fecha').textContent = resumenFechas.total_servicios.toLocaleString('es-CL');
    document.getElementById('admin-total-ingresos-fecha').textContent = resumenFechas.total_ingresos.toLocaleString('es-CL');
    resumen.style.display = 'flex';

    adminServiciosDetalle = data.servicios_detalle || [];
    adminUltimaConsulta = {
      fechaDesde,
      fechaHasta,
      resumen: data.resumen || { total_servicios: 0, total_ingresos: 0 }
    };
    adminUltimasCategorias = data.categorias || [];
    cargarCategoriasAdmin(adminUltimasCategorias);

    if (adminServiciosDetalle.length > 0 && exportContainer) {
      exportContainer.style.display = 'flex';
      if (btnExportExcel) btnExportExcel.disabled = false;
      if (btnExportPDF) btnExportPDF.disabled = false;
    }
  } catch (error) {
    console.error('Error consultando fechas:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-danger py-4">
          <i class="fas fa-exclamation-triangle"></i> Error: ${error.message}
        </td>
      </tr>`;
    showError('No se pudo completar la consulta: ' + error.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }
}

function cargarCategoriasAdmin(categorias) {
  const tbody = document.getElementById('admin-tabla-fechas-body');

  if (!categorias || categorias.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-4">
          <i class="fas fa-info-circle"></i> No se encontraron servicios en el rango seleccionado
        </td>
      </tr>`;
    document.getElementById('admin-detalles-container').style.display = 'none';
    return;
  }

  tbody.innerHTML = categorias.map(categoria => {
    const categoriaParam = JSON.stringify(categoria.categoria);
    return `
    <tr>
      <td><span class="badge bg-primary me-2">${obtenerIconoCategoriaAdmin(categoria.categoria)}</span><strong>${categoria.categoria}</strong></td>
      <td><small class="text-muted">${categoria.tipos_servicios}</small></td>
      <td class="text-center"><span class="badge bg-info">${categoria.cantidad_servicios}</span></td>
      <td class="text-end"><strong>$${parseInt(categoria.total_categoria).toLocaleString('es-CL')}</strong></td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-primary" onclick='verDetallesCategoriaAdmin(${categoriaParam})'>
          <i class="fas fa-eye"></i> Ver
        </button>
      </td>
    </tr>
  `;
  }).join('');
}

function obtenerIconoCategoriaAdmin(categoria) {
  const iconos = {
    'Lavados': '🧽',
    'Estacionamiento x Minuto': '🅿️',
    'Errores de Ingreso': '❌',
    'Motos': '🏍️',
    'Promociones': '🎁',
    'Otros Servicios': '🔧'
  };
  return iconos[categoria] || '📋';
}

function verDetallesCategoriaAdmin(categoria) {
  const detalles = adminServiciosDetalle.filter(servicio => servicio.categoria === categoria);
  const tbody = document.getElementById('admin-tabla-detalles-body');
  const container = document.getElementById('admin-detalles-container');

  if (!detalles || detalles.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted">No hay servicios para esta categoría</td>
      </tr>`;
  } else {
    tbody.innerHTML = detalles.map((servicio, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${servicio.patente}</td>
        <td>${new Date(servicio.fecha_salida_real || servicio.fecha_salida).toLocaleString('es-CL')}</td>
        <td>${servicio.nombre_servicio}</td>
        <td class="text-end">$${parseInt(servicio.total).toLocaleString('es-CL')}</td>
      </tr>
    `).join('');
  }

  container.style.display = 'block';
  container.scrollIntoView({ behavior: 'smooth' });
}

// Hacer accesibles las funciones desde los atributos inline
window.verDetallesCategoriaAdmin = verDetallesCategoriaAdmin;
window.exportarConsultaExcelAdmin = exportarConsultaExcelAdmin;
window.exportarConsultaPDFAdmin = exportarConsultaPDFAdmin;

function exportarConsultaExcelAdmin() {
  if (!adminUltimaConsulta || adminServiciosDetalle.length === 0) {
    showWarning('Realice primero una consulta para exportar los datos.');
    return;
  }

  const headers = ['Patente', 'Servicio', 'Categoría', 'Fecha Ingreso', 'Fecha Salida', 'Método de Pago', 'Total'];
  const rows = adminServiciosDetalle.map(servicio => [
    servicio.patente || 'N/A',
    servicio.nombre_servicio || 'N/A',
    servicio.categoria || 'N/A',
    servicio.fecha_ingreso ? new Date(servicio.fecha_ingreso).toLocaleString('es-CL') : 'N/A',
    servicio.fecha_salida ? new Date(servicio.fecha_salida).toLocaleString('es-CL') : 'N/A',
    servicio.metodo_pago || 'N/A',
    servicio.total || 0
  ]);

  const csvLines = [headers, ...rows].map(row => row.map(value => {
    const val = value === null || value === undefined ? '' : value.toString();
    return `"${val.replace(/"/g, '""')}"`;
  }).join(','));

  const csvContent = csvLines.join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const enlace = document.createElement('a');
  enlace.href = url;
  enlace.download = `consulta_${adminUltimaConsulta.fechaDesde}_${adminUltimaConsulta.fechaHasta}.csv`;
  document.body.appendChild(enlace);
  enlace.click();
  document.body.removeChild(enlace);
  URL.revokeObjectURL(url);
}

function exportarConsultaPDFAdmin() {
  if (!adminUltimaConsulta || adminServiciosDetalle.length === 0) {
    showWarning('Realice primero una consulta para exportar los datos.');
    return;
  }

  if (!window.jspdf || !window.jspdf.jsPDF || typeof window.jspdf.jsPDF !== 'function') {
    showError('Biblioteca jsPDF no disponible. Verifique la conexión a internet.');
    return;
  }

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'landscape' });

  doc.setFontSize(14);
  doc.text('Consulta por Rango de Fechas - Administración', 14, 20);
  doc.setFontSize(10);
  doc.text(`Periodo: ${adminUltimaConsulta.fechaDesde} al ${adminUltimaConsulta.fechaHasta}`, 14, 34);
  doc.text(`Total servicios: ${adminUltimaConsulta.resumen.total_servicios.toLocaleString('es-CL')} | Ingresos: $${adminUltimaConsulta.resumen.total_ingresos.toLocaleString('es-CL')}`, 14, 46);

  const bodyServicios = adminServiciosDetalle.map(servicio => [
    servicio.patente || 'N/A',
    servicio.nombre_servicio || 'N/A',
    servicio.categoria || 'N/A',
    servicio.fecha_salida ? new Date(servicio.fecha_salida).toLocaleDateString('es-CL') : 'N/A',
    servicio.metodo_pago || 'N/A',
    `$${parseInt(servicio.total || 0).toLocaleString('es-CL')}`
  ]);

  doc.autoTable({
    head: [['Patente', 'Servicio', 'Categoría', 'Fecha Salida', 'Método', 'Total']],
    body: bodyServicios,
    startY: 60,
    styles: { fontSize: 8 },
    headStyles: { fillColor: [33, 150, 243] }
  });

  if (adminUltimasCategorias.length > 0) {
    doc.addPage('landscape');
    doc.setFontSize(12);
    doc.text('Resumen por Categoría', 14, 20);

    const bodyCategorias = adminUltimasCategorias.map(cat => [
      cat.categoria,
      cat.cantidad_servicios,
      `$${parseInt(cat.total_categoria || 0).toLocaleString('es-CL')}`,
      cat.tipos_servicios
    ]);

    doc.autoTable({
      head: [['Categoría', 'Cantidad', 'Total', 'Tipos de Servicios']],
      body: bodyCategorias,
      startY: 30,
      styles: { fontSize: 9 },
      headStyles: { fillColor: [255, 152, 0] }
    });
  }

  doc.save(`consulta_${adminUltimaConsulta.fechaDesde}_${adminUltimaConsulta.fechaHasta}.pdf`);
}


  // Actualización periódica
  setInterval(cargarDatosAdmin, 60000); // Cada 60 segundos

  // Evento para abrir modal en modo "Agregar"
  modalClienteElement.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const idCliente = button ? button.getAttribute('data-id') : null;

    // 🔧 CORRECCIÓN: Limpiar el formulario si el botón que abre el modal no es de edición (no tiene data-id).
    // Si es edición, llamar a la función para llenar los datos.
    if (!idCliente) {
      // Modo Agregar
      const form = document.getElementById('form-cliente-mensual');
      form.reset();
      document.getElementById('cliente-id-mensual').value = '';
      document.getElementById('titulo-modal-cliente').innerText = 'Agregar Nuevo Cliente Mensual';
    } else {
      // Modo Editar: Llenar el formulario con los datos del cliente.
      document.getElementById('titulo-modal-cliente').innerText = 'Editar Cliente Mensual';
      llenarFormularioCliente(idCliente);
    }
  });

  // Evento para guardar cliente (nuevo o editado)
  document.getElementById('btn-guardar-cliente').addEventListener('click', guardarClienteMensual);

  // Eventos para el modal de servicios
  modalServicioElement.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const esEditar = button.getAttribute('data-id');
    const form = document.getElementById('form-servicio');
    form.reset();
    document.getElementById('servicio-id').value = '';

    if (esEditar) {
      // El modo editar se maneja en la función editarServicio
    } else {
      document.getElementById('titulo-modal-servicio').innerText = 'Agregar Nuevo Servicio';
    }
  });
  document.querySelector('#modalServicio .btn-primary').addEventListener('click', guardarServicio);

  // Eventos para el modal de usuarios
  if (modalUsuarioElement) {
    modalUsuarioElement.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      
      // Solo limpiar si NO es edición (si no tiene data-editar)
      if (!modalUsuarioElement.hasAttribute('data-editar')) {
        const form = document.getElementById('form-usuario');
        form.reset();
        document.getElementById('usuario-id').value = '';
        document.getElementById('password-help').style.display = 'none';
        document.getElementById('usuario-password').required = true;
        document.getElementById('titulo-modal-usuario').innerText = 'Agregar Nuevo Usuario';
      }
      // Quitar el atributo después de usarlo
      modalUsuarioElement.removeAttribute('data-editar');
    });
    document.querySelector('#modalUsuario .btn-primary').addEventListener('click', guardarUsuario);
  }

  // Eventos para los filtros
  document.getElementById('buscar-cliente').addEventListener('input', aplicarFiltros);
  document.getElementById('filtro-estado').addEventListener('change', aplicarFiltros);
  document.getElementById('filtro-fecha-desde').addEventListener('change', aplicarFiltros);
  document.getElementById('filtro-fecha-hasta').addEventListener('change', aplicarFiltros);
  document.getElementById('btn-limpiar-filtros').addEventListener('click', () => {
    document.getElementById('buscar-cliente').value = '';
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-fecha-desde').value = '';
    document.getElementById('filtro-fecha-hasta').value = '';
    aplicarFiltros();
  });

  // Evento para guardar precios
  const formPrecios = document.getElementById('form-precios');
  if (formPrecios) {
    formPrecios.addEventListener('submit', guardarPrecios);
  }

  // Evento para guardar meta mensual
  const formMeta = document.getElementById('form-meta');
  if (formMeta) {
    formMeta.addEventListener('submit', guardarMetaMensual);
  }

  // Evento para confirmar y guardar meta desde el modal
  const btnConfirmarMeta = document.getElementById('btn-confirmar-meta-si');
  if (btnConfirmarMeta) {
    btnConfirmarMeta.addEventListener('click', confirmarYGuardarMeta);
  }

  // Evento para confirmar y procesar pago de cliente desde el modal
  const btnConfirmarPagoCliente = document.getElementById('btn-confirmar-pago-cliente-si');
  if (btnConfirmarPagoCliente) {
    btnConfirmarPagoCliente.addEventListener('click', confirmarYProcesarPagoCliente);
  }
});

function cargarDatosAdmin() {
  cargarClientesMensuales();
  cargarServicios();
  cargarUsuarios();
}

// Selector de mes en resumen ejecutivo
const selectorMesResumen = document.getElementById('selector-mes-resumen');
if (selectorMesResumen) {
  llenarSelectorMeses();
  selectorMesResumen.addEventListener('change', cargarResumenEjecutivo);
  cargarResumenEjecutivo(); // Cargar al inicio
}

// ============================================
// GESTIÓN DE USUARIOS (SOLO ADMIN)
// ============================================

let todosLosUsuarios = [];

function cargarUsuarios() {
  const tablaUsuarios = document.getElementById('tabla-usuarios');
  if (!tablaUsuarios) return; // Si no existe la tabla, no hacer nada

  fetch(`${BASE_PATH}/api/api_usuarios.php`)
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        throw new Error(data.error);
      }
      todosLosUsuarios = data.data;
      renderizarTablaUsuarios(todosLosUsuarios);
    })
    .catch(error => {
      console.error('Error cargando usuarios:', error);
      tablaUsuarios.innerHTML = 
        `<tr><td colspan="4" class="text-center text-danger py-4">Error al cargar usuarios: ${error.message}</td></tr>`;
    });
}

function renderizarTablaUsuarios(usuarios) {
  const tbody = document.getElementById('tabla-usuarios');
  if (!tbody) return;

  if (usuarios.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = usuarios.map(usuario => {
    const rolBadge = usuario.rol === 'admin' 
      ? '<span class="badge bg-primary">Administrador</span>' 
      : '<span class="badge bg-secondary">Operador</span>';

    return `
      <tr>
        <td>#${usuario.id}</td>
        <td><strong>${usuario.usuario}</strong></td>
        <td>${rolBadge}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" data-id="${usuario.id}" onclick="editarUsuario(this)">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(${usuario.id})">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function editarUsuario(button) {
  const id = button.getAttribute('data-id');
  const usuario = todosLosUsuarios.find(u => u.id == id);

  if (usuario) {
    // Marcar el modal como edición ANTES de abrirlo
    const modalElement = document.getElementById('modalUsuario');
    modalElement.setAttribute('data-editar', 'true');
    
    // Llenar el formulario
    document.getElementById('titulo-modal-usuario').innerText = 'Editar Usuario';
    document.getElementById('usuario-id').value = usuario.id;
    document.getElementById('usuario-nombre').value = usuario.usuario;
    
    // 🔧 CORRECCIÓN: Asegurarse de que el rol se seleccione correctamente.
    // El rol en la BD puede ser 'cajero' pero en el select es 'operador'.
    const rolSelect = document.getElementById('usuario-rol');
    const rolValue = (usuario.rol === 'cajero') ? 'operador' : usuario.rol;
    rolSelect.value = rolValue;

    document.getElementById('usuario-password').value = '';
    document.getElementById('usuario-password').required = false;
    document.getElementById('password-help').style.display = 'block';

    // Abrir el modal
    new bootstrap.Modal(modalElement).show();
  }
}

async function guardarUsuario() {
  const idValue = document.getElementById('usuario-id').value;
  const password = document.getElementById('usuario-password').value;
  
  // Convertir el ID correctamente: si está vacío o es cadena vacía, no lo incluyas
  const usuarioData = {
    usuario: document.getElementById('usuario-nombre').value.trim(),
    rol: document.getElementById('usuario-rol').value,
  };

  // Solo agregar id si existe (modo edición)
  if (idValue && idValue.trim() !== '') {
    usuarioData.id = parseInt(idValue);
  }

  // Solo agregar password si tiene valor (para crear nuevo o cambiar contraseña)
  if (password && password.trim() !== '') {
    usuarioData.password = password.trim();
  }

  // Validaciones
  if (!usuarioData.usuario) {
    showWarning('Por favor, ingrese el nombre de usuario.');
    return;
  }

  // Si es un nuevo usuario (sin id), la contraseña es obligatoria
  if (!usuarioData.id && !usuarioData.password) {
    showWarning('Por favor, ingrese una contraseña para el nuevo usuario.');
    return;
  }

  // 🐛 DEBUG temporal
  console.log('🔍 DEBUG - Datos a enviar:', usuarioData);
  console.log('🔍 DEBUG - ID original del input:', idValue);
  console.log('🔍 DEBUG - ¿Es edición?', !!usuarioData.id);

  try {
    const response = await fetch(`${BASE_PATH}/api/api_usuarios.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(usuarioData)
    });
    const result = await response.json();
    if (result.success) {
      showSuccess(result.message);
      bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
      cargarUsuarios();
      // Limpiar el formulario
      document.getElementById('form-usuario').reset();
      document.getElementById('usuario-id').value = '';
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    showError('Error al guardar el usuario: ' + error.message);
    console.error('Error completo:', error);
  }
}

async function eliminarUsuario(id) {
  if (!confirm('¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.')) return;

  try {
    const response = await fetch(`${BASE_PATH}/api/api_usuarios.php?id=${id}`, { method: 'DELETE' });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      cargarUsuarios();
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al eliminar el usuario: ' + error.message);
  }
}

// ============================================
// OPTIMIZACIÓN Y MANTENIMIENTO
// ============================================

async function optimizarSistema() {
  if (!confirm('¿Desea ejecutar la optimización del sistema? Esto puede tomar unos minutos.')) {
    return;
  }
  
  try {
    const response = await fetch(`${BASE_PATH}/optimizar-mysql-windows7.php`);
    const result = await response.text();
    
    // Mostrar resultado en modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">🚀 Optimización del Sistema</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div style="max-height: 400px; overflow-y: auto;">
              ${result.replace(/\n/g, '<br>')}
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Limpiar modal después de cerrar
    modal.addEventListener('hidden.bs.modal', () => {
      document.body.removeChild(modal);
    });
    
  } catch (error) {
    console.error('Error optimizando sistema:', error);
    alert('❌ Error al ejecutar optimización: ' + error.message);
  }
}

async function respaldarDatos() {
  if (!confirm('¿Desea crear un respaldo de la base de datos? Esto puede tomar unos minutos.')) {
    return;
  }
  
  try {
    const response = await fetch(`${BASE_PATH}/api/api_respaldo.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        tipo: 'completo',
        incluir_datos: true
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(`✅ Respaldo creado exitosamente\n\nArchivo: ${result.archivo}\nTamaño: ${result.tamaño}\nUbicación: ${result.ubicacion}`);
    } else {
      throw new Error(result.error);
    }
    
  } catch (error) {
    console.error('Error creando respaldo:', error);
    alert('❌ Error al crear respaldo: ' + error.message);
  }
}

async function exportarReporte() {
  const fechaInicio = prompt('Fecha de inicio (YYYY-MM-DD):', formatDateInputLocal(new Date()));
  if (!fechaInicio) return;
  
  const fechaFin = prompt('Fecha de fin (YYYY-MM-DD):', formatDateInputLocal(new Date()));
  if (!fechaFin) return;
  
  try {
    const response = await fetch(`${BASE_PATH}/api/api_exportar_reporte.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        formato: 'txt'
      })
    });
    
    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `reporte_${fechaInicio}_${fechaFin}.txt`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      
      alert('✅ Reporte exportado exitosamente');
    } else {
      throw new Error('Error al generar reporte');
    }
    
  } catch (error) {
    console.error('Error exportando reporte:', error);
    alert('❌ Error al exportar reporte: ' + error.message);
  }
}

// ============================================
// GESTIÓN DE CLIENTES MENSUALES
// ============================================

let todosLosClientes = []; // Guardamos la lista completa de clientes aquí

function cargarClientesMensuales() {
  fetch(`${BASE_PATH}/api/api_clientes_mensuales.php`)
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        throw new Error(data.error);
      }
      todosLosClientes = data.data;
      aplicarFiltros(); // Dibuja la tabla con los filtros actuales
      actualizarEstadisticasMensuales(todosLosClientes); // Las estadísticas siempre usan el total
    })
    .catch(error => {
      console.error('Error cargando clientes:', error);
      document.getElementById('tabla-clientes').innerHTML = 
        `<tr><td colspan="8" class="text-center text-danger py-4">Error al cargar clientes: ${error.message}</td></tr>`;
    });
}

function aplicarFiltros() {
  const textoBusqueda = document.getElementById('buscar-cliente').value.toLowerCase();
  const estadoFiltro = document.getElementById('filtro-estado').value;
  const fechaDesde = document.getElementById('filtro-fecha-desde').value;
  const fechaHasta = document.getElementById('filtro-fecha-hasta').value;

  const clientesFiltrados = todosLosClientes.filter(cliente => {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const vencimiento = new Date(cliente.fecha_proximo_vencimiento + 'T00:00:00');
    const estadoCliente = vencimiento >= hoy ? 'activo' : 'vencido';

    const coincideTexto = textoBusqueda === '' || 
                          cliente.patente.toLowerCase().includes(textoBusqueda) || 
                          cliente.nombre_cliente.toLowerCase().includes(textoBusqueda);
    
    const coincideEstado = estadoFiltro === '' || estadoCliente === estadoFiltro;

    const coincideFecha = (fechaDesde === '' || cliente.fecha_proximo_vencimiento >= fechaDesde) &&
                          (fechaHasta === '' || cliente.fecha_proximo_vencimiento <= fechaHasta);

    return coincideTexto && coincideEstado && coincideFecha;
  });

  renderizarTablaClientes(clientesFiltrados);
}

function renderizarTablaClientes(clientes) {
  const tbody = document.getElementById('tabla-clientes');
  
  if (clientes.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No se encontraron clientes con los filtros aplicados.</td></tr>';
    return;
  }
  
  tbody.innerHTML = clientes.map(cliente => {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const finPlan = new Date(cliente.fecha_proximo_vencimiento + 'T00:00:00');

    const esVencido = finPlan < hoy;
    const claseFila = esVencido ? 'table-danger' : '';
    const badgeEstado = esVencido 
      ? '<span class="badge bg-danger">❌ Vencido</span>'
      : '<span class="badge bg-success">✅ Activo</span>';
    
    return `
      <tr class="${claseFila}">
        <td>${badgeEstado}</td>
        <td><strong>${cliente.patente || 'N/A'}</strong></td>
        <td>${cliente.nombre_cliente}</td>
        <td>${cliente.tipo_vehiculo || 'N/A'}</td>
        <td>${cliente.fecha_inicio_plan ? new Date(cliente.fecha_inicio_plan + 'T00:00:00').toLocaleDateString('es-CL') : 'N/A'}</td>
        <td><strong>${finPlan.toLocaleDateString('es-CL')}</strong></td>
        <td><strong class="text-success">$${parseFloat(cliente.monto_plan).toLocaleString('es-CL')}</strong></td>
        <td>
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" data-id="${cliente.id}">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="eliminarClienteMensual(${cliente.id})">
            <i class="fas fa-trash"></i>
          </button>
          ${esVencido ? `<button class="btn btn-sm btn-success" onclick="pagarClienteMensual(${cliente.id})" title="Marcar como pagado">
            <i class="fas fa-check"></i> Pagar
          </button>` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

async function actualizarEstadisticasMensuales(clientes) {
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const mesActual = hoy.getMonth();
  const anioActual = hoy.getFullYear();

  let activos = 0;
  let vencidos = 0;
  let ingresosMes = 0;

  clientes.forEach(c => {
    const vencimiento = new Date(c.fecha_proximo_vencimiento + 'T00:00:00'); // Asegurar fecha local
    if (vencimiento >= hoy) {
      activos++;
    } else {
      vencidos++;
    }
  });

  // Obtener ingresos mensuales reales del mes actual desde la tabla salidas
  try {
    const primerDia = `${anioActual}-${String(mesActual + 1).padStart(2, '0')}-01`;
    const ultimoDia = formatDateInputLocal(new Date(anioActual, mesActual + 1, 0));
    
    const response = await fetch(`${BASE_PATH}/api/api_ingresos_mensuales.php?fecha_desde=${primerDia}&fecha_hasta=${ultimoDia}`);
    const data = await response.json();
    
    if (data.success) {
      ingresosMes = parseFloat(data.total || 0);
    }
  } catch (error) {
    console.error('Error al obtener ingresos mensuales:', error);
    // Si falla, usar método anterior como fallback
    clientes.forEach(c => {
      const inicioPlan = new Date(c.fecha_inicio_plan + 'T00:00:00');
      if (inicioPlan.getMonth() === mesActual && inicioPlan.getFullYear() === anioActual) {
        ingresosMes += parseFloat(c.monto_plan);
      }
    });
  }

  document.getElementById('clientes-activos').textContent = activos;
  document.getElementById('clientes-vencidos').textContent = vencidos;
  document.getElementById('total-clientes').textContent = clientes.length;
  document.getElementById('ingresos-mes').textContent = `$${ingresosMes.toLocaleString('es-CL')}`;
}

async function guardarClienteMensual() {
  const id = document.getElementById('cliente-id-mensual').value;
  const clienteData = {
    id: id ? parseInt(id) : null,
    patente: document.getElementById('cliente-patente-mensual').value.toUpperCase(),
    // Separar nombre y apellido si es posible, si no, enviar todo en nombres
    nombres: document.getElementById('cliente-nombre-mensual').value.split(' ')[0] || '',
    apellidos: document.getElementById('cliente-nombre-mensual').value.split(' ').slice(1).join(' ') || '',
    tipo_vehiculo: document.getElementById('cliente-vehiculo-mensual').value,
    monto_plan: document.getElementById('cliente-monto-mensual').value,
    dia_pago_mensual: document.getElementById('cliente-dia-pago-mensual').value,
    fecha_proximo_vencimiento: document.getElementById('cliente-vencimiento-mensual').value,
    notas: document.getElementById('cliente-notas-mensual').value,
  };

  if (!clienteData.patente || !clienteData.nombres || !clienteData.dia_pago_mensual || !clienteData.fecha_proximo_vencimiento || !clienteData.monto_plan) {
    showWarning('Por favor, complete todos los campos obligatorios.');
    return;
  }

  try {
    const response = await fetch(`${BASE_PATH}/api/api_clientes_mensuales.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(clienteData)
    });
    const result = await response.json();
    if (result.success) {
      showSuccess(result.message);
      bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
      cargarClientesMensuales(); // Recarga la lista completa
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    showError('Error al guardar el cliente: ' + error.message);
  }
}

/**
 * Llena el formulario del modal de cliente con los datos de un cliente existente.
 * @param {number} id - El ID del cliente a editar.
 */
function llenarFormularioCliente(id) {
  // Usamos la lista de clientes ya cargada en `todosLosClientes` para no hacer otra llamada a la API.
  const cliente = todosLosClientes.find(c => c.id == id);

  if (cliente) {
    document.getElementById('cliente-id-mensual').value = cliente.id;
    document.getElementById('cliente-patente-mensual').value = cliente.patente;
    document.getElementById('cliente-nombre-mensual').value = cliente.nombre_cliente;
    document.getElementById('cliente-vehiculo-mensual').value = cliente.tipo_vehiculo;
    document.getElementById('cliente-monto-mensual').value = cliente.monto_plan;
    document.getElementById('cliente-dia-pago-mensual').value = cliente.dia_pago_mensual;
    document.getElementById('cliente-vencimiento-mensual').value = cliente.fecha_proximo_vencimiento;
    document.getElementById('cliente-notas-mensual').value = cliente.notas;
  } else {
    showError('No se encontró el cliente para editar.');
    // Opcional: cerrar el modal si no se encuentra el cliente
    bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
  }
}

async function eliminarClienteMensual(id) {
  if (!confirm('¿Está seguro de que desea eliminar este cliente mensual? Esta acción no se puede deshacer.')) {
    return;
  }

  try {
    const response = await fetch(`${BASE_PATH}/api/api_clientes_mensuales.php?id=${id}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      cargarClientesMensuales(); // Recarga la lista completa
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al eliminar el cliente: ' + error.message);
  }
}

// Variable global para almacenar el ID del cliente pendiente de pago
let idClientePagoPendiente = null;

async function pagarClienteMensual(id) {
  // Convertir el ID a número para asegurar comparación correcta
  const idNumero = parseInt(id);
  
  if (isNaN(idNumero)) {
    showError('ID de cliente inválido');
    return;
  }
  
  let cliente = null;
  
  // Primero intentar buscar en la lista cargada
  if (todosLosClientes && todosLosClientes.length > 0) {
    cliente = todosLosClientes.find(c => {
      const cId = parseInt(c.id) || parseInt(c.idclientes) || 0;
      return cId === idNumero;
    });
  }
  
  // Si no se encuentra, obtener directamente de la API
  if (!cliente) {
    try {
      const response = await fetch(`${BASE_PATH}/api/api_clientes_mensuales.php?id=${idNumero}`);
      const data = await response.json();
      
      if (data.success && data.data) {
        // La API devuelve un objeto cuando se busca por ID, o un array cuando se buscan todos
        cliente = Array.isArray(data.data) ? data.data[0] : data.data;
      } else {
        console.error('Error de la API:', data.error);
      }
    } catch (error) {
      console.error('Error al obtener cliente de la API:', error);
    }
  }
  
  if (!cliente) {
    showError('No se encontró la información del cliente. Por favor, recarga la página e intenta nuevamente.');
    return;
  }
  
  // Guardar el ID para usar después de la confirmación
  idClientePagoPendiente = idNumero;
  
  // Llenar el modal de confirmación con los datos del cliente
  document.getElementById('confirm-pago-cliente-nombre').textContent = cliente.nombre_cliente || cliente.nombres || 'N/A';
  document.getElementById('confirm-pago-cliente-patente').textContent = cliente.patente || 'N/A';
  document.getElementById('confirm-pago-cliente-monto').textContent = `$${parseFloat(cliente.monto_plan || 0).toLocaleString('es-CL')}`;
  
  // Formatear fecha de vencimiento actual
  const fechaVencimiento = cliente.fecha_proximo_vencimiento 
    ? new Date(cliente.fecha_proximo_vencimiento + 'T00:00:00').toLocaleDateString('es-CL')
    : 'N/A';
  document.getElementById('confirm-pago-cliente-vencimiento-actual').textContent = fechaVencimiento;
  
  // Mostrar el modal de confirmación
  const modalConfirmar = new bootstrap.Modal(document.getElementById('modalConfirmarPagoCliente'));
  modalConfirmar.show();
}

// Función que se ejecuta cuando el usuario confirma el pago en el modal
async function confirmarYProcesarPagoCliente() {
  if (!idClientePagoPendiente) {
    showError('No hay cliente seleccionado para procesar el pago');
    return;
  }
  
  // Cerrar el modal
  const modalConfirmar = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarPagoCliente'));
  if (modalConfirmar) {
    modalConfirmar.hide();
  }
  
  // Deshabilitar el botón mientras se procesa
  const btnConfirmar = document.getElementById('btn-confirmar-pago-cliente-si');
  const textoOriginal = btnConfirmar.innerHTML;
  btnConfirmar.disabled = true;
  btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
  
  try {
    const response = await fetch(`${BASE_PATH}/api/api_clientes_mensuales.php`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        id: idClientePagoPendiente,
        action: 'pagar'
      })
    });
    
    const result = await response.json();
    if (result.success) {
      showSuccess('✅ Cliente marcado como pagado. La fecha de vencimiento se ha actualizado.');
      cargarClientesMensuales(); // Recargar la tabla para mostrar el cambio
      idClientePagoPendiente = null; // Limpiar ID
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    showError('Error al procesar el pago: ' + error.message);
  } finally {
    // Restaurar el botón
    btnConfirmar.disabled = false;
    btnConfirmar.innerHTML = textoOriginal;
  }
}

// ============================================
// GESTIÓN DE SERVICIOS (Lavados, etc.)
// ============================================

function cargarServicios() {
  fetch(`${BASE_PATH}/api/api_servicios_lavado.php?todos=1`) // Pedimos TODOS los servicios (activos e inactivos)
    .then(response => response.json())
    .then(data => {
      if (!data.success) throw new Error(data.error);
      serviciosCargados = data.data; // Guardar para editar

      const tbody = document.getElementById('tabla-servicios');
      
      if (serviciosCargados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay servicios registrados</td></tr>';
        return;
      }
      
      tbody.innerHTML = serviciosCargados.map(servicio => {
        const esActivo = parseInt(servicio.activo) === 1;
        const badgeClass = esActivo ? 'bg-success' : 'bg-secondary';
        const badgeIcon = esActivo ? '✅' : '⚪';
        const badgeTexto = esActivo ? 'Activo' : 'Inactivo';
        const btnToggleClass = esActivo ? 'btn-warning' : 'btn-success';
        const btnToggleIcon = esActivo ? 'fa-toggle-off' : 'fa-toggle-on';
        const btnToggleTexto = esActivo ? 'Desactivar' : 'Activar';
        
        return `
        <tr>
          <td><strong>#${servicio.idtipo_ingresos}</strong></td>
          <td>${servicio.nombre_servicio}</td>
          <td><strong class="text-success">$${parseFloat(servicio.precio).toLocaleString('es-CL')}</strong></td>
          <td>
            <button class="btn btn-sm ${btnToggleClass}" 
                    onclick="toggleEstadoServicio(${servicio.idtipo_ingresos}, ${esActivo ? 0 : 1})"
                    title="${btnToggleTexto}">
              <i class="fas ${btnToggleIcon}"></i> ${badgeIcon} ${badgeTexto}
            </button>
          </td>
          <td><small class="text-muted">${servicio.descripcion || 'N/A'}</small></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-id="${servicio.idtipo_ingresos}" onclick="editarServicio(this)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(${servicio.idtipo_ingresos})">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
      }).join('');
    })
    .catch(error => {
      console.error('Error cargando servicios:', error);
      document.getElementById('tabla-servicios').innerHTML = 
        `<tr><td colspan="6" class="text-center text-danger py-4">Error cargando servicios: ${error.message}</td></tr>`;
    });
}

function editarServicio(button) {
  const id = button.getAttribute('data-id');
  const servicio = serviciosCargados.find(s => s.idtipo_ingresos == id);

  if (servicio) {
    document.getElementById('titulo-modal-servicio').innerText = 'Editar Servicio';
    document.getElementById('servicio-id').value = servicio.idtipo_ingresos;
    document.getElementById('servicio-nombre').value = servicio.nombre_servicio;
    document.getElementById('servicio-precio').value = servicio.precio;
    document.getElementById('servicio-descripcion').value = servicio.descripcion || '';

    new bootstrap.Modal(document.getElementById('modalServicio')).show();
  } else {
    alert('No se encontró el servicio para editar.');
  }
}

async function guardarServicio() {
  const id = document.getElementById('servicio-id').value;
  const servicioData = {
    id: id ? parseInt(id) : null,
    nombre_servicio: document.getElementById('servicio-nombre').value,
    precio: document.getElementById('servicio-precio').value,
    descripcion: document.getElementById('servicio-descripcion').value,
  };

  if (!servicioData.nombre_servicio || !servicioData.precio) {
    showWarning('Por favor, complete el nombre y el precio del servicio.');
    return;
  }

  try {
    const response = await fetch(`${BASE_PATH}/api/api_servicios_lavado.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(servicioData)
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      bootstrap.Modal.getInstance(document.getElementById('modalServicio')).hide();
      cargarServicios();
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al guardar el servicio: ' + error.message);
  }
}

async function toggleEstadoServicio(id, nuevoEstado) {
  const accionTexto = nuevoEstado === 1 ? 'activar' : 'desactivar';
  const confirmMsg = nuevoEstado === 1 
    ? `¿Desea ACTIVAR el servicio #${id}?\n\nVolverá a aparecer en las listas de servicios.`
    : `¿Desea DESACTIVAR el servicio #${id}?\n\nDejará de aparecer en las listas, pero se mantendrá en reportes históricos.`;
  
  if (!confirm(confirmMsg)) {
    return;
  }

  try {
    const response = await fetch(`${BASE_PATH}/api/api_servicios_lavado.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id, activo: nuevoEstado })
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      cargarServicios(); // Recargar la tabla
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al cambiar estado del servicio: ' + error.message);
  }
}

async function eliminarServicio(id) {
  if (!confirm(`¿Está seguro de que desea DESACTIVAR el servicio con ID #${id}?\n\nEl servicio desaparecerá de las listas de nuevos ingresos, pero se mantendrá en los reportes históricos.`)) {
    return;
  }

  try {
    const response = await fetch(`${BASE_PATH}/api/api_servicios_lavado.php?id=${id}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      cargarServicios();
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al desactivar el servicio: ' + error.message);
  }
}

// ============================================
// GESTIÓN DE PRECIOS (Configuración)
// ============================================

async function cargarPrecios() {
  try {
    const response = await fetch(`${BASE_PATH}/api/api_precios.php`);
    const result = await response.json();
    
    if (result.success) {
      const precios = result.data;
      
      // Cargar valores en los inputs
      const inputPrecioMinuto = document.getElementById('precio-minuto');
      const inputPrecioMinimo = document.getElementById('precio-minimo');
      
      if (inputPrecioMinuto) {
        inputPrecioMinuto.value = precios.precio_minuto;
      }
      if (inputPrecioMinimo) {
        inputPrecioMinimo.value = precios.precio_minimo;
      }
      
      // Actualizar el badge en el navbar (si existe)
      const badgePrecio = document.querySelector('.badge.bg-success');
      if (badgePrecio && badgePrecio.textContent.includes('$')) {
        badgePrecio.innerHTML = `<i class="fas fa-dollar-sign"></i> $${precios.precio_minuto}/min`;
      }
      
      console.log('✅ Precios cargados:', precios);
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    console.error('Error al cargar precios:', error);
    alert('Error al cargar configuración de precios: ' + error.message);
  }
}

async function guardarPrecios(event) {
  event.preventDefault(); // Prevenir el envío del formulario

  const precioMinuto = parseInt(document.getElementById('precio-minuto').value);
  const precioMinimo = parseInt(document.getElementById('precio-minimo').value);
  
  // Validaciones
  if (!precioMinuto || precioMinuto < 1) {
    showWarning('El precio por minuto debe ser mayor a 0');
    return;
  }
  
  if (precioMinimo < 0) {
    showWarning('El precio mínimo no puede ser negativo');
    return;
  }
  
  if (precioMinimo > 0 && precioMinimo < precioMinuto) {
    showWarning(`El precio mínimo ($${precioMinimo}) debe ser mayor o igual al precio por minuto ($${precioMinuto})`);
    return;
  }
  
  // Confirmación
  const confirmar = confirm(
    `¿Confirmar cambio de precios?\n\n` +
    `Precio por minuto: $${precioMinuto}\n` +
    `Precio mínimo: $${precioMinimo}\n\n` +
    `Esto afectará todos los nuevos cobros de estacionamiento.`
  );
  
  if (!confirmar) return;
  
  try {
    const response = await fetch(`${BASE_PATH}/api/api_precios.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        precio_minuto: precioMinuto,
        precio_minimo: precioMinimo
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(`✅ ${result.message}\n\nPrecio por minuto: $${precioMinuto}\nPrecio mínimo: $${precioMinimo}`);
      
      // Actualizar el badge en el navbar
      const badgePrecio = document.querySelector('.badge.bg-success');
      if (badgePrecio && badgePrecio.textContent.includes('$')) {
        badgePrecio.innerHTML = `<i class="fas fa-dollar-sign"></i> $${precioMinuto}/min`;
      }
      
      // Recargar precios para confirmar
      cargarPrecios();
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('❌ Error al guardar precios: ' + error.message);
  }
}

// ============================================
// RESUMEN EJECUTIVO MENSUAL
// ============================================

let graficoIngresosMes = null; // Variable global para el gráfico

function llenarSelectorMeses() {
  const selector = document.getElementById('selector-mes-resumen');
  if (!selector) return;
  
  const mesActual = new Date().getMonth(); // 0-11
  const anioActual = new Date().getFullYear();
  
  selector.innerHTML = '';
  
  // Generar opciones para los últimos 12 meses
  for (let i = 0; i < 12; i++) {
    let mes = mesActual - i;
    let anio = anioActual;
    
    if (mes < 0) {
      mes += 12;
      anio--;
    }
    
    const fecha = new Date(anio, mes, 1);
    const nombreMes = fecha.toLocaleDateString('es-CL', { month: 'long', year: 'numeric' });
    const mesNum = mes + 1; // 1-12
    
    const option = document.createElement('option');
    option.value = `${mesNum}-${anio}`;
    option.textContent = nombreMes.charAt(0).toUpperCase() + nombreMes.slice(1);
    if (i === 0) option.selected = true; // Mes actual por defecto
    
    selector.appendChild(option);
  }
}

async function cargarResumenEjecutivo() {
  console.log('🔍 Iniciando cargarResumenEjecutivo');
  
  // Verificar si ya se está cargando
  if (window.cargandoResumen) {
    console.log('⚠️ Ya se está cargando el resumen, ignorando llamada duplicada');
    return;
  }
  
  // Marcar como cargando
  window.cargandoResumen = true;
  
  // Obtener BASE_PATH directamente de la URL actual - más confiable
  const currentPath = window.location.pathname;
  console.log('🔍 currentPath:', currentPath);
  const baseMatch = currentPath.match(/^(.*?sistemaEstacionamiento)/);
  console.log('🔍 baseMatch:', baseMatch);
  const currentBasePath = baseMatch ? baseMatch[1] : '/sistemaEstacionamiento';
  console.log('🔍 BASE_PATH actual:', typeof currentBasePath, currentBasePath);
  
  const selector = document.getElementById('selector-mes-resumen');
  if (!selector) {
    console.warn('Selector de mes no encontrado, reintentando en 100ms...');
    setTimeout(cargarResumenEjecutivo, 100);
    return;
  }
  
  // Verificar que todos los elementos necesarios existan
  const elementosRequeridos = [
    'resumen-loading',
    'resumen-contenido',
    'kpi-ingresos-mes',
    'kpi-vehiculos',
    'kpi-ticket-promedio',
    'kpi-mensuales',
    'kpi-variacion'
  ];
  
  for (const elementoId of elementosRequeridos) {
    if (!document.getElementById(elementoId)) {
      console.warn(`Elemento ${elementoId} no encontrado, reintentando en 100ms...`);
      setTimeout(cargarResumenEjecutivo, 100);
      return;
    }
  }
  
  const [mes, anio] = selector.value.split('-');
  
  // Mostrar loading
  document.getElementById('resumen-loading').classList.remove('d-none');
  document.getElementById('resumen-contenido').classList.add('d-none');
  
  try {
    // Temporalmente sin AbortController para debuggear
    console.log('🔧 Usando fetch sin AbortController para debuggear...');
    
      // Construir URL completa usando el valor dinámico
      let url;
      if (currentBasePath.startsWith('http')) {
        // Si ya es una URL completa
        url = `${currentBasePath}/api/api_resumen_ejecutivo.php?mes=${mes}&anio=${anio}`;
      } else {
        // Si es una ruta relativa, construir URL completa
        const protocol = window.location.protocol;
        const host = window.location.host;
        url = `${protocol}//${host}${currentBasePath}/api/api_resumen_ejecutivo.php?mes=${mes}&anio=${anio}`;
      }
      console.log('🌐 URL de la petición:', url);
    
    const response = await fetch(url);
    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Error HTTP ${response.status}: ${errorText}`);
    }
    
    const result = await response.json();
    
    if (result.success) {
      renderizarResumenEjecutivo(result.data);
    } else {
      throw new Error(result.error || 'Error desconocido del servidor');
    }
    
    // Reset del flag al finalizar exitosamente
    window.cargandoResumen = false;
  } catch (error) {
    console.error('Error cargando resumen ejecutivo:', error);
    console.error('Tipo de error:', typeof error);
    console.error('Mensaje de error:', error.message);
    console.error('Stack trace:', error.stack);
    
    // Mostrar error más específico
    let mensajeError = 'Error al cargar resumen: ';
    if (error.message.includes('Failed to fetch')) {
      mensajeError += 'No se pudo conectar al servidor. Verifica la conexión.';
    } else if (error.message.includes('HTTP')) {
      mensajeError += error.message;
    } else if (error.message) {
      mensajeError += error.message;
    } else {
      mensajeError += 'Error desconocido: ' + JSON.stringify(error);
    }
    
    alert(mensajeError);
    
    // Ocultar loading y mostrar mensaje de error
    const loading = document.getElementById('resumen-loading');
    const contenido = document.getElementById('resumen-contenido');
    if (loading) loading.classList.add('d-none');
    if (contenido) {
      contenido.classList.remove('d-none');
      contenido.innerHTML = `
        <div class="alert alert-danger text-center">
          <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
          <h5>Error al cargar resumen ejecutivo</h5>
          <p>${mensajeError}</p>
          <button class="btn btn-primary" onclick="cargarResumenEjecutivo()">
            <i class="fas fa-redo"></i> Reintentar
          </button>
        </div>
      `;
    }
    
    // Reset del flag en caso de error
    window.cargandoResumen = false;
  }
}

function renderizarResumenEjecutivo(data) {
  // Verificar que los elementos existan antes de usarlos
  const elementos = {
    loading: document.getElementById('resumen-loading'),
    contenido: document.getElementById('resumen-contenido'),
    kpiIngresos: document.getElementById('kpi-ingresos-mes'),
    kpiVehiculos: document.getElementById('kpi-vehiculos'),
    kpiTicketPromedio: document.getElementById('kpi-ticket-promedio'),
    kpiMensuales: document.getElementById('kpi-mensuales'),
    kpiVariacion: document.getElementById('kpi-variacion')
  };
  
  // Verificar que todos los elementos críticos existan
  for (const [nombre, elemento] of Object.entries(elementos)) {
    if (!elemento) {
      console.error(`Elemento crítico no encontrado: ${nombre}`);
      return;
    }
  }
  
  // Ocultar loading, mostrar contenido
  elementos.loading.classList.add('d-none');
  elementos.contenido.classList.remove('d-none');
  
  // 1. KPIs PRINCIPALES
  elementos.kpiIngresos.textContent = 
    '$' + parseInt(data.total_ingresos).toLocaleString('es-CL');
  
  elementos.kpiVehiculos.textContent = 
    data.total_servicios.toLocaleString('es-CL');
  
  const ticketPromedio = data.total_servicios > 0 ? data.total_ingresos / data.total_servicios : 0;
  elementos.kpiTicketPromedio.textContent = 
    '$' + parseInt(ticketPromedio).toLocaleString('es-CL');
  
  elementos.kpiMensuales.textContent = 
    '$' + parseInt(data.total_mensuales).toLocaleString('es-CL');
  
  const kpiMensualesCount = document.getElementById('kpi-mensuales-count');
  if (kpiMensualesCount) {
    kpiMensualesCount.textContent = data.total_clientes_mensuales + ' clientes';
  }
  
  // Variación vs mes anterior
  const variacion = data.variacion_porcentaje;
  const iconoVariacion = variacion >= 0 ? '📈' : '📉';
  const colorVariacion = variacion >= 0 ? 'text-success' : 'text-danger';
  const signoVariacion = variacion >= 0 ? '+' : '';
  elementos.kpiVariacion.innerHTML = 
    `<span class="${colorVariacion}">${iconoVariacion} ${signoVariacion}${variacion.toFixed(1)}% vs mes anterior</span>`;
  
  // 2. META DEL MES
  const meta = data.meta;
  document.getElementById('meta-monto').textContent = 
    '$' + parseInt(meta.monto).toLocaleString('es-CL');
  
  document.getElementById('meta-logrado').textContent = 
    '$' + parseInt(meta.total_para_meta).toLocaleString('es-CL');
  
  // 🎯 MOSTRAR METAS ALCANZADAS
  const metasAlcanzadas = meta.metas_alcanzadas || 0;
  if (metasAlcanzadas > 0) {
    // Generar iconos de metas (trofeos)
    let iconosMetas = '🎯'.repeat(metasAlcanzadas);
    document.getElementById('meta-falta').innerHTML = 
      `<span class="text-success">${iconosMetas} ${metasAlcanzadas} meta(s) alcanzada(s)!</span>`;
    
    // Mostrar progreso hacia la siguiente meta
    const metasSobrantes = meta.metas_sobrantes || 0;
    const porcentajeSobrante = meta.porcentaje_meta_sobrante || 0;
    if (metasSobrantes > 0) {
      document.getElementById('meta-falta').innerHTML += 
        `<br><small class="text-muted">+$${parseInt(metasSobrantes).toLocaleString('es-CL')} hacia siguiente meta (${porcentajeSobrante.toFixed(1)}%)</small>`;
    }
  } else {
    document.getElementById('meta-falta').innerHTML = 
      '<span class="text-danger">$' + parseInt(meta.falta).toLocaleString('es-CL') + '</span>';
  }
  
  const porcentajeMeta = meta.porcentaje_cumplido;
  const barraMeta = document.getElementById('barra-meta');
  barraMeta.style.width = Math.min(porcentajeMeta, 100) + '%';
  document.getElementById('texto-barra-meta').textContent = porcentajeMeta.toFixed(1) + '%';
  
  // Cambiar color de la barra según el progreso
  barraMeta.className = 'progress-bar progress-bar-striped progress-bar-animated';
  if (porcentajeMeta >= 100) {
    barraMeta.classList.add('bg-success');
  } else if (porcentajeMeta >= 70) {
    barraMeta.classList.add('bg-warning');
  } else {
    barraMeta.classList.add('bg-danger');
  }
  
  const infoMeta = meta.solo_dias_laborales ? 'Solo días laborales (Lun-Vie)' : 'Todos los días';
  const infoMensuales = meta.incluir_mensuales ? ' + Clientes mensuales' : '';
  document.getElementById('info-meta').textContent = infoMeta + infoMensuales;
  
  // Cargar meta en el formulario
  // 🛡️ Verificación defensiva para evitar errores si los elementos no están en el DOM
  const inputMeta = document.getElementById('meta-mensual');
  const checkDiasLaborales = document.getElementById('solo-dias-laborales');
  const checkIncluirMensuales = document.getElementById('incluir-mensuales');

  if (inputMeta) inputMeta.value = parseInt(meta.monto);
  if (checkDiasLaborales) checkDiasLaborales.checked = meta.solo_dias_laborales === 1;
  if (checkIncluirMensuales) checkIncluirMensuales.checked = meta.incluir_mensuales === 1;

  
  // 3. TOP 5 SERVICIOS (separados por segmento)
  const topServiciosLaborales = data.top_servicios_lunes_viernes || data.top_servicios || [];
  const topServiciosSabado = data.top_servicios_sabado || [];
  renderizarTop5Servicios(topServiciosLaborales, 'top-servicios-lv', 'Sin datos de lunes a viernes');
  renderizarTop5Servicios(topServiciosSabado, 'top-servicios-sab', 'Sin datos de sábados');
  
  // Compatibilidad: si existe el contenedor antiguo, rellenarlo con el top general
  if (document.getElementById('top-servicios')) {
    renderizarTop5Servicios(data.top_servicios || [], 'top-servicios', 'No hay datos para este mes');
  }
  
  // 4. DESGLOSE DE PAGOS
  renderizarDesglosePagos(data.desglose_pagos);
  
  // 5. GRÁFICO POR DÍA
  renderizarGraficoIngresos(data.ingresos_por_dia);
}

function renderizarTop5Servicios(topServicios, containerId, emptyMessage = 'No hay datos para este periodo') {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  if (!topServicios || topServicios.length === 0) {
    container.innerHTML = `<p class="text-muted text-center py-3">${emptyMessage}</p>`;
    return;
  }
  
  const listaServicios = topServicios.map((servicio, index) => {
    const medallas = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
    return `
      <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong>${medallas[index]} ${servicio.servicio}</strong>
            <small class="text-muted d-block">${servicio.cantidad} servicios</small>
          </div>
          <strong class="text-success">$${parseInt(servicio.total).toLocaleString('es-CL')}</strong>
        </div>
      </div>
    `;
  }).join('');

  const totalMonto = topServicios.reduce((sum, servicio) => sum + (parseFloat(servicio.total) || 0), 0);
  const totalServicios = topServicios.reduce((sum, servicio) => sum + (parseInt(servicio.cantidad) || 0), 0);

  container.innerHTML = `
    ${listaServicios}
    <div class="list-group-item text-end bg-light">
      <div><small>Total servicios: <strong>${totalServicios.toLocaleString('es-CL')}</strong></small></div>
      <div class="fw-bold text-primary">Total del mes: $${parseInt(totalMonto).toLocaleString('es-CL')}</div>
    </div>
  `;
}

function renderizarDesglosePagos(desglose) {
  const container = document.getElementById('desglose-pagos');
  
  const total = Object.values(desglose).reduce((sum, val) => sum + val, 0);
  
  if (total === 0) {
    container.innerHTML = '<p class="text-muted text-center py-3">No hay datos para este mes</p>';
    return;
  }
  
  const metodos = [
    { key: 'efectivo', label: '💵 Efectivo', color: 'success' },
    { key: 'tuu_oficial', label: '🧾 TUU (Boletas Oficiales)', color: 'primary' },
    { key: 'tarjetas', label: '💳 Tarjetas', color: 'info' },
    { key: 'transferencia', label: '🏦 Transferencias', color: 'warning' },
    { key: 'manual_comprobante', label: '📄 Comprobantes Manuales', color: 'secondary' }
  ];
  
  container.innerHTML = metodos.map(metodo => {
    const monto = desglose[metodo.key] || 0;
    if (monto === 0) return ''; // No mostrar si es 0
    
    const porcentaje = (monto / total) * 100;
    
    return `
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span>${metodo.label}</span>
          <strong class="text-${metodo.color}">$${parseInt(monto).toLocaleString('es-CL')}</strong>
        </div>
        <div class="progress" style="height: 20px;">
          <div class="progress-bar bg-${metodo.color}" style="width: ${porcentaje}%">
            ${porcentaje.toFixed(1)}%
          </div>
        </div>
      </div>
    `;
  }).join('');
}

let graficoIngresosMesInstance = null;

function renderizarGraficoIngresos(ingresosPorDia) {
  const canvas = document.getElementById('grafico-ingresos-mes');
  if (!canvas) return;
  
  // Destruir gráfico anterior si existe
  if (graficoIngresosMesInstance) {
    graficoIngresosMesInstance.destroy();
  }
  
  if (ingresosPorDia.length === 0) {
    canvas.parentElement.innerHTML = '<p class="text-muted text-center py-3">No hay datos para este mes</p>';
    return;
  }
  
  const ctx = canvas.getContext('2d');
  
  const labels = ingresosPorDia.map(dia => {
    const fecha = new Date(dia.fecha + 'T00:00:00');
    return fecha.getDate(); // Solo el número del día
  });
  
  const datos = ingresosPorDia.map(dia => dia.total);
  
  graficoIngresosMesInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Ingresos del Día',
        data: datos,
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return '$' + parseInt(context.parsed.y).toLocaleString('es-CL');
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '$' + parseInt(value).toLocaleString('es-CL');
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'Día del Mes'
          }
        }
      }
    }
  });
}

// Variable global para almacenar los datos de la meta antes de confirmar
let datosMetaPendiente = null;

async function guardarMetaMensual(event) {
  event.preventDefault();
  
  const mesAnio = document.getElementById('selector-mes-resumen').value.split('-');
  const metaMonto = parseInt(document.getElementById('meta-mensual').value);
  const soloDiasLaborales = document.getElementById('solo-dias-laborales').checked ? 1 : 0;
  const incluirMensuales = document.getElementById('incluir-mensuales').checked ? 1 : 0;
  
  if (!metaMonto || metaMonto < 0) {
    showError('Ingrese una meta válida');
    return;
  }
  
  // Guardar datos para usar después de la confirmación
  datosMetaPendiente = {
    mes: parseInt(mesAnio[0]),
    anio: parseInt(mesAnio[1]),
    meta_monto: metaMonto,
    solo_dias_laborales: soloDiasLaborales,
    incluir_mensuales: incluirMensuales
  };
  
  // Llenar el modal de confirmación
  const periodoTexto = document.getElementById('selector-mes-resumen').selectedOptions[0].text;
  document.getElementById('confirm-meta-periodo').textContent = `Período: ${periodoTexto}`;
  document.getElementById('confirm-meta-monto').textContent = `$${metaMonto.toLocaleString('es-CL')}`;
  document.getElementById('confirm-solo-laborales').textContent = soloDiasLaborales ? 'Sí (Lun-Vie)' : 'No (Todos los días)';
  document.getElementById('confirm-incluir-mensuales').textContent = incluirMensuales ? 'Sí' : 'No';
  
  // Mostrar el modal de confirmación
  const modalConfirmar = new bootstrap.Modal(document.getElementById('modalConfirmarMeta'));
  modalConfirmar.show();
}

// Función que se ejecuta cuando el usuario confirma en el modal
async function confirmarYGuardarMeta() {
  if (!datosMetaPendiente) {
    showError('No hay datos de meta para guardar');
    return;
  }
  
  // Cerrar el modal
  const modalConfirmar = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarMeta'));
  if (modalConfirmar) {
    modalConfirmar.hide();
  }
  
  // Deshabilitar el botón mientras se procesa
  const btnConfirmar = document.getElementById('btn-confirmar-meta-si');
  const textoOriginal = btnConfirmar.innerHTML;
  btnConfirmar.disabled = true;
  btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
  
  try {
    const response = await fetch(`${BASE_PATH}/api/api_meta.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datosMetaPendiente)
    });
    
    const result = await response.json();
    
    if (result.success) {
      showSuccess('✅ Meta guardada correctamente');
      cargarResumenEjecutivo(); // Recargar para ver cambios
      datosMetaPendiente = null; // Limpiar datos
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    showError('❌ Error al guardar meta: ' + error.message);
  } finally {
    // Restaurar el botón
    btnConfirmar.disabled = false;
    btnConfirmar.innerHTML = textoOriginal;
  }
}

// Funciones de exportación (por ahora solo alertas, implementar después)
function exportarResumenPDF() {
  alert('📥 Funcionalidad de exportar a PDF en desarrollo.\n\nPróximamente podrás descargar el resumen ejecutivo en PDF.');
}

function exportarResumenExcel() {
  alert('📥 Funcionalidad de exportar a Excel en desarrollo.\n\nPróximamente podrás descargar el resumen ejecutivo en Excel.');
}