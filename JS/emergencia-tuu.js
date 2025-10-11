/**
 * ========================================
 * GESTIÓN DE EMERGENCIA Y CAMBIO DE TUU
 * ========================================
 * Permite cambiar entre máquinas TUU en caso de falla
 */

// Variables globales
let modalEmergenciaTUU;
let maquinasDisponibles = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modal
    const modalElement = document.getElementById('modalEmergenciaTUU');
    if (modalElement) {
        modalEmergenciaTUU = new bootstrap.Modal(modalElement);
    }

    // Cargar estado inicial de TUU
    cargarEstadoTUU();

    // Botón Emergencia
    const btnEmergencia = document.getElementById('btn-emergencia');
    if (btnEmergencia) {
        btnEmergencia.addEventListener('click', abrirModalEmergencia);
    }

    // Botón Refresh
    const btnRefresh = document.getElementById('btn-refresh');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', refrescarDashboard);
    }

    // Recargar estado TUU cada 30 segundos
    setInterval(cargarEstadoTUU, 30000);
});

/**
 * Carga el estado actual de la máquina TUU
 */
async function cargarEstadoTUU() {
    try {
        const response = await fetch('./api/api_config_tuu.php');
        const data = await response.json();

        if (data.success && data.activa) {
            actualizarIndicadorTUU(data.activa);
        }
    } catch (error) {
        console.error('Error al cargar estado TUU:', error);
        document.getElementById('nombre-maquina-tuu').textContent = 'Error';
        document.getElementById('badge-maquina-tuu').classList.remove('bg-info');
        document.getElementById('badge-maquina-tuu').classList.add('bg-danger');
    }
}

/**
 * Actualiza el indicador visual de la máquina TUU activa
 */
function actualizarIndicadorTUU(maquina) {
    const nombreSpan = document.getElementById('nombre-maquina-tuu');
    const badge = document.getElementById('badge-maquina-tuu');

    if (maquina.maquina === 'principal') {
        nombreSpan.textContent = '🟢 Principal';
        badge.classList.remove('bg-danger', 'bg-warning');
        badge.classList.add('bg-info');
        badge.title = maquina.nombre || 'TUU Principal';
    } else {
        nombreSpan.textContent = '🟡 Respaldo';
        badge.classList.remove('bg-info', 'bg-danger');
        badge.classList.add('bg-warning');
        badge.title = maquina.nombre || 'TUU Respaldo';
    }
}

/**
 * Abre el modal de emergencia para cambiar TUU
 */
async function abrirModalEmergencia() {
    // Mostrar loading
    const estadoDiv = document.getElementById('estado-tuu-actual');
    const selectorDiv = document.getElementById('selector-maquinas-tuu');
    
    estadoDiv.innerHTML = `
        <p><strong>Máquina Activa Actual:</strong></p>
        <div class="d-flex align-items-center">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            <span>Cargando...</span>
        </div>
    `;
    selectorDiv.classList.add('d-none');

    // Abrir modal
    modalEmergenciaTUU.show();

    try {
        const response = await fetch('./api/api_config_tuu.php');
        const data = await response.json();

        if (data.success) {
            maquinasDisponibles = data.maquinas;
            mostrarEstadoYOpciones(data.activa, data.maquinas);
        } else {
            estadoDiv.innerHTML = `
                <div class="alert alert-danger">
                    ❌ Error al cargar configuración de TUU
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar configuración TUU:', error);
        estadoDiv.innerHTML = `
            <div class="alert alert-danger">
                ❌ Error de conexión al cargar TUU
            </div>
        `;
    }
}

/**
 * Muestra el estado actual y las opciones disponibles
 */
function mostrarEstadoYOpciones(maquinaActiva, maquinas) {
    const estadoDiv = document.getElementById('estado-tuu-actual');
    const selectorDiv = document.getElementById('selector-maquinas-tuu');

    // Mostrar máquina activa
    const iconoActiva = maquinaActiva.maquina === 'principal' ? '🟢' : '🟡';
    estadoDiv.innerHTML = `
        <div class="alert alert-success">
            <strong>${iconoActiva} Máquina Activa:</strong><br>
            <span class="fs-5">${maquinaActiva.nombre || maquinaActiva.maquina}</span><br>
            <small class="text-muted">Serial: ${maquinaActiva.device_serial}</small>
        </div>
    `;

    // Mostrar opciones
    const listGroup = selectorDiv.querySelector('.list-group');
    listGroup.innerHTML = '';

    maquinas.forEach(maquina => {
        const esActiva = maquina.activa;
        const icono = maquina.maquina === 'principal' ? '🟢' : '🟡';
        const badgeClass = esActiva ? 'bg-success' : 'bg-secondary';
        const btnClass = esActiva ? 'btn-outline-secondary disabled' : 'btn-outline-primary';
        const btnTexto = esActiva ? 'En uso' : 'Cambiar a esta máquina';

        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${icono} ${maquina.nombre || maquina.maquina}</h6>
                    <small class="text-muted">Serial: ${maquina.device_serial}</small>
                </div>
                <div>
                    <span class="badge ${badgeClass} me-2">${esActiva ? 'ACTIVA' : 'Disponible'}</span>
                    <button class="btn btn-sm ${btnClass}" 
                            ${esActiva ? 'disabled' : ''}
                            onclick="cambiarMaquinaTUU('${maquina.maquina}')">
                        ${btnTexto}
                    </button>
                </div>
            </div>
        `;
        listGroup.appendChild(item);
    });

    selectorDiv.classList.remove('d-none');
}

/**
 * Cambia la máquina TUU activa
 */
async function cambiarMaquinaTUU(maquina) {
    const nombreMaquina = maquina === 'principal' ? 'Principal' : 'Respaldo';
    
    if (!confirm(`¿Estás seguro de cambiar a la máquina ${nombreMaquina}?`)) {
        return;
    }

    try {
        const response = await fetch('./api/api_config_tuu.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ maquina: maquina })
        });

        const data = await response.json();

        if (data.success) {
            alert(`✅ ${data.message}\n\nLos próximos pagos usarán esta máquina.`);
            
            // Actualizar indicador
            cargarEstadoTUU();
            
            // Cerrar modal
            modalEmergenciaTUU.hide();
            
            // Refrescar dashboard
            setTimeout(() => {
                refrescarDashboard();
            }, 500);
        } else {
            alert(`❌ Error: ${data.error}`);
        }
    } catch (error) {
        console.error('Error al cambiar máquina TUU:', error);
        alert('❌ Error de conexión al cambiar máquina TUU');
    }
}

/**
 * Refresca todos los datos del dashboard
 */
function refrescarDashboard() {
    const btnRefresh = document.getElementById('btn-refresh');
    const iconoRefresh = btnRefresh.querySelector('i');

    // Animación de rotación
    iconoRefresh.classList.add('fa-spin');
    btnRefresh.disabled = true;

    // Cargar estado TUU
    cargarEstadoTUU();

    // Recargar precio del navbar (si existe la función)
    if (typeof cargarPrecioNavbar === 'function') {
        cargarPrecioNavbar();
    }

    // Recargar estadísticas del día (si existe la función)
    if (typeof cargarEstadisticasDia === 'function') {
        cargarEstadisticasDia();
    }

    // Recargar últimos ingresos (si existe la función)
    if (typeof cargarUltimosIngresos === 'function') {
        cargarUltimosIngresos();
    }

    // Mostrar mensaje de éxito
    setTimeout(() => {
        iconoRefresh.classList.remove('fa-spin');
        btnRefresh.disabled = false;
        
        // Feedback visual
        const badge = document.createElement('span');
        badge.className = 'badge bg-success position-absolute';
        badge.style.cssText = 'top: -10px; right: -10px; animation: fadeOut 2s forwards;';
        badge.innerHTML = '<i class="fas fa-check"></i>';
        btnRefresh.style.position = 'relative';
        btnRefresh.appendChild(badge);
        
        setTimeout(() => badge.remove(), 2000);
    }, 1000);
}

// CSS para animación
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        0% { opacity: 1; transform: scale(1); }
        100% { opacity: 0; transform: scale(1.5); }
    }
    
    #badge-maquina-tuu {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    #badge-maquina-tuu:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
`;
document.head.appendChild(style);

// Hacer que el badge sea clickeable para abrir el modal
document.addEventListener('DOMContentLoaded', function() {
    const badgeTUU = document.getElementById('badge-maquina-tuu');
    if (badgeTUU) {
        badgeTUU.style.cursor = 'pointer';
        badgeTUU.addEventListener('click', abrirModalEmergencia);
        badgeTUU.title = 'Click para cambiar máquina TUU';
    }
});

