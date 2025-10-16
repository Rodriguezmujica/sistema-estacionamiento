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

  // Actualización periódica
  setInterval(cargarDatosAdmin, 60000); // Cada 60 segundos

  // Evento para abrir modal en modo "Agregar"
  modalClienteElement.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const esEditar = button.getAttribute('data-id');

    const form = document.getElementById('form-cliente-mensual');
    form.reset();
    document.getElementById('cliente-id-mensual').value = '';

    if (esEditar) {
      // Modo Editar (se maneja en la función editarClienteMensual)
    } else {
      // Modo Agregar
      document.getElementById('titulo-modal-cliente').innerText = 'Agregar Nuevo Cliente Mensual';
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
});

function cargarDatosAdmin() {
  cargarClientesMensuales();
  cargarServicios();
  cargarUsuarios();
}

// ============================================
// GESTIÓN DE USUARIOS (SOLO ADMIN)
// ============================================

let todosLosUsuarios = [];

function cargarUsuarios() {
  const tablaUsuarios = document.getElementById('tabla-usuarios');
  if (!tablaUsuarios) return; // Si no existe la tabla, no hacer nada

  fetch('/sistemaEstacionamiento/api/api_usuarios.php')
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
    alert('Por favor, ingrese el nombre de usuario.');
    return;
  }

  // Si es un nuevo usuario (sin id), la contraseña es obligatoria
  if (!usuarioData.id && !usuarioData.password) {
    alert('Por favor, ingrese una contraseña para el nuevo usuario.');
    return;
  }

  // 🐛 DEBUG temporal
  console.log('🔍 DEBUG - Datos a enviar:', usuarioData);
  console.log('🔍 DEBUG - ID original del input:', idValue);
  console.log('🔍 DEBUG - ¿Es edición?', !!usuarioData.id);

  try {
    const response = await fetch('/sistemaEstacionamiento/api/api_usuarios.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(usuarioData)
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
      cargarUsuarios();
      // Limpiar el formulario
      document.getElementById('form-usuario').reset();
      document.getElementById('usuario-id').value = '';
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al guardar el usuario: ' + error.message);
    console.error('Error completo:', error);
  }
}

async function eliminarUsuario(id) {
  if (!confirm('¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.')) return;

  try {
    const response = await fetch(`/sistemaEstacionamiento/api/api_usuarios.php?id=${id}`, { method: 'DELETE' });
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

// Selector de mes en resumen ejecutivo
const selectorMesResumen = document.getElementById('selector-mes-resumen');
if (selectorMesResumen) {
  llenarSelectorMeses();
  selectorMesResumen.addEventListener('change', cargarResumenEjecutivo);
  cargarResumenEjecutivo(); // Cargar al inicio
}

// ============================================
// GESTIÓN DE CLIENTES MENSUALES
// ============================================

let todosLosClientes = []; // Guardamos la lista completa de clientes aquí

function cargarClientesMensuales() {
  fetch('/sistemaEstacionamiento/api/api_clientes_mensuales.php')
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
        <td><strong>${cliente.patente}</strong></td>
        <td>${cliente.nombre_cliente}</td>
        <td>${cliente.tipo_vehiculo || 'N/A'}</td>
        <td>${cliente.fecha_inicio_plan ? new Date(cliente.fecha_inicio_plan + 'T00:00:00').toLocaleDateString('es-CL') : 'N/A'}</td>
        <td><strong>${finPlan.toLocaleDateString('es-CL')}</strong></td>
        <td><strong class="text-success">$${parseFloat(cliente.monto_plan).toLocaleString('es-CL')}</strong></td>
        <td>
          <button class="btn btn-sm btn-outline-primary" data-id="${cliente.id}" onclick="editarClienteMensual(this)">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="eliminarClienteMensual(${cliente.id})">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function actualizarEstadisticasMensuales(clientes) {
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

    const inicioPlan = new Date(c.fecha_inicio_plan + 'T00:00:00'); // Usamos la fecha de inicio para calcular ingresos del mes
    if (inicioPlan.getMonth() === mesActual && inicioPlan.getFullYear() === anioActual) {
      ingresosMes += parseFloat(c.monto_plan);
    }
  });

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
    alert('Por favor, complete todos los campos obligatorios.');
    return;
  }

  try {
    const response = await fetch('/sistemaEstacionamiento/api/api_clientes_mensuales.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(clienteData)
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
      cargarClientesMensuales(); // Recarga la lista completa
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('Error al guardar el cliente: ' + error.message);
  }
}

async function editarClienteMensual(button) {
  const id = button.getAttribute('data-id');
  try {
    const response = await fetch('/sistemaEstacionamiento/api/api_clientes_mensuales.php');
    const result = await response.json();
    if (!result.success) throw new Error(result.error);

    const cliente = result.data.find(c => c.id == id);
    if (cliente) {
      document.getElementById('titulo-modal-cliente').innerText = 'Editar Cliente Mensual';
      document.getElementById('cliente-id-mensual').value = cliente.id;
      document.getElementById('cliente-patente-mensual').value = cliente.patente;
      document.getElementById('cliente-nombre-mensual').value = cliente.nombre_cliente; // nombre_cliente es CONCAT(nombres, ' ', apellidos)
      document.getElementById('cliente-vehiculo-mensual').value = cliente.tipo_vehiculo;
      document.getElementById('cliente-monto-mensual').value = cliente.monto_plan;
      document.getElementById('cliente-dia-pago-mensual').value = cliente.dia_pago_mensual;
      document.getElementById('cliente-vencimiento-mensual').value = cliente.fecha_proximo_vencimiento;
      document.getElementById('cliente-notas-mensual').value = cliente.notas;
      
      new bootstrap.Modal(document.getElementById('modalCliente')).show();
    } else {
      alert('No se encontró el cliente para editar.');
    }
  } catch (error) {
    alert('Error al cargar datos del cliente: ' + error.message);
  }
}

async function eliminarClienteMensual(id) {
  if (!confirm('¿Está seguro de que desea eliminar este cliente mensual? Esta acción no se puede deshacer.')) {
    return;
  }

  try {
    const response = await fetch(`/sistemaEstacionamiento/api/api_clientes_mensuales.php?id=${id}`, {
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

// ============================================
// GESTIÓN DE SERVICIOS (Lavados, etc.)
// ============================================

function cargarServicios() {
  fetch('/sistemaEstacionamiento/api/api_servicios_lavado.php?todos=1') // Pedimos TODOS los servicios (activos e inactivos)
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
    alert('Por favor, complete el nombre y el precio del servicio.');
    return;
  }

  try {
    const response = await fetch('/sistemaEstacionamiento/api/api_servicios_lavado.php', {
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
    const response = await fetch('/sistemaEstacionamiento/api/api_servicios_lavado.php', {
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
    const response = await fetch(`/sistemaEstacionamiento/api/api_servicios_lavado.php?id=${id}`, {
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
    const response = await fetch('/sistemaEstacionamiento/api/api_precios.php');
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
  event.preventDefault();
  
  const precioMinuto = parseInt(document.getElementById('precio-minuto').value);
  const precioMinimo = parseInt(document.getElementById('precio-minimo').value);
  
  // Validaciones
  if (!precioMinuto || precioMinuto < 1) {
    alert('El precio por minuto debe ser mayor a 0');
    return;
  }
  
  if (precioMinimo < 0) {
    alert('El precio mínimo no puede ser negativo');
    return;
  }
  
  if (precioMinimo > 0 && precioMinimo < precioMinuto) {
    alert(`El precio mínimo ($${precioMinimo}) debe ser mayor o igual al precio por minuto ($${precioMinuto})`);
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
    const response = await fetch('/sistemaEstacionamiento/api/api_precios.php', {
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
  const selector = document.getElementById('selector-mes-resumen');
  if (!selector) return;
  
  const [mes, anio] = selector.value.split('-');
  
  // Mostrar loading
  document.getElementById('resumen-loading').classList.remove('d-none');
  document.getElementById('resumen-contenido').classList.add('d-none');
  
  try {
    const response = await fetch(`/sistemaEstacionamiento/api/api_resumen_ejecutivo.php?mes=${mes}&anio=${anio}`);
    const result = await response.json();
    
    if (result.success) {
      renderizarResumenEjecutivo(result.data);
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    console.error('Error cargando resumen ejecutivo:', error);
    alert('Error al cargar resumen: ' + error.message);
  }
}

function renderizarResumenEjecutivo(data) {
  // Ocultar loading, mostrar contenido
  document.getElementById('resumen-loading').classList.add('d-none');
  document.getElementById('resumen-contenido').classList.remove('d-none');
  
  // 1. KPIs PRINCIPALES
  document.getElementById('kpi-ingresos-mes').textContent = 
    '$' + parseInt(data.total_ingresos).toLocaleString('es-CL');
  
  document.getElementById('kpi-vehiculos').textContent = 
    data.total_servicios.toLocaleString('es-CL');
  
  const ticketPromedio = data.total_servicios > 0 ? data.total_ingresos / data.total_servicios : 0;
  document.getElementById('kpi-ticket-promedio').textContent = 
    '$' + parseInt(ticketPromedio).toLocaleString('es-CL');
  
  document.getElementById('kpi-mensuales').textContent = 
    '$' + parseInt(data.total_mensuales).toLocaleString('es-CL');
  
  document.getElementById('kpi-mensuales-count').textContent = 
    data.total_clientes_mensuales + ' clientes';
  
  // Variación vs mes anterior
  const variacion = data.variacion_porcentaje;
  const iconoVariacion = variacion >= 0 ? '📈' : '📉';
  const colorVariacion = variacion >= 0 ? 'text-success' : 'text-danger';
  const signoVariacion = variacion >= 0 ? '+' : '';
  document.getElementById('kpi-variacion').innerHTML = 
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
  document.getElementById('meta-mensual').value = parseInt(meta.monto);
  document.getElementById('solo-dias-laborales').checked = meta.solo_dias_laborales === 1;
  document.getElementById('incluir-mensuales').checked = meta.incluir_mensuales === 1;
  
  // 3. TOP 5 SERVICIOS
  renderizarTop5Servicios(data.top_servicios);
  
  // 4. DESGLOSE DE PAGOS
  renderizarDesglosePagos(data.desglose_pagos);
  
  // 5. GRÁFICO POR DÍA
  renderizarGraficoIngresos(data.ingresos_por_dia);
}

function renderizarTop5Servicios(topServicios) {
  const container = document.getElementById('top-servicios');
  
  if (topServicios.length === 0) {
    container.innerHTML = '<p class="text-muted text-center py-3">No hay datos para este mes</p>';
    return;
  }
  
  container.innerHTML = topServicios.map((servicio, index) => {
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

async function guardarMetaMensual(event) {
  event.preventDefault();
  
  const mesAnio = document.getElementById('selector-mes-resumen').value.split('-');
  const metaMonto = parseInt(document.getElementById('meta-mensual').value);
  const soloDiasLaborales = document.getElementById('solo-dias-laborales').checked ? 1 : 0;
  const incluirMensuales = document.getElementById('incluir-mensuales').checked ? 1 : 0;
  
  if (!metaMonto || metaMonto < 0) {
    alert('Ingrese una meta válida');
    return;
  }
  
  const confirmar = confirm(
    `¿Confirmar meta para ${document.getElementById('selector-mes-resumen').selectedOptions[0].text}?\n\n` +
    `Meta: $${metaMonto.toLocaleString('es-CL')}\n` +
    `Solo días laborales: ${soloDiasLaborales ? 'Sí (Lun-Vie)' : 'No (Todos los días)'}\n` +
    `Incluir mensuales: ${incluirMensuales ? 'Sí' : 'No'}`
  );
  
  if (!confirmar) return;
  
  try {
    const response = await fetch('/sistemaEstacionamiento/api/api_resumen_ejecutivo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        mes: parseInt(mesAnio[0]),
        anio: parseInt(mesAnio[1]),
        meta_monto: metaMonto,
        solo_dias_laborales: soloDiasLaborales,
        incluir_mensuales: incluirMensuales
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('✅ Meta guardada correctamente');
      cargarResumenEjecutivo(); // Recargar para ver cambios
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    alert('❌ Error al guardar meta: ' + error.message);
  }
}

// Funciones de exportación (por ahora solo alertas, implementar después)
function exportarResumenPDF() {
  alert('📥 Funcionalidad de exportar a PDF en desarrollo.\n\nPróximamente podrás descargar el resumen ejecutivo en PDF.');
}

function exportarResumenExcel() {
  alert('📥 Funcionalidad de exportar a Excel en desarrollo.\n\nPróximamente podrás descargar el resumen ejecutivo en Excel.');
}