/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Script de Preload Seguro (preload.js) - FASE 2
 * ============================================================================
 * Arquitectura de Seguridad:
 * - Context Isolation: ACTIVADO (contextIsolation: true)
 * - Node Integration: DESACTIVADO (nodeIntegration: false)
 * - Exposición selectiva y fuertemente tipada vía contextBridge.
 * ============================================================================
 */

const { contextBridge, ipcRenderer } = require('electron');

// Exposición controlada del API segura hacia el Renderer Process (window.api)
contextBridge.exposeInMainWorld('api', {
  // --------------------------------------------------------------------------
  // 1. INFORMACIÓN DEL SISTEMA Y ENTORNO
  // --------------------------------------------------------------------------
  getAppInfo: () => ipcRenderer.invoke('app:getInfo'),

  // --------------------------------------------------------------------------
  // 2. CONFIGURACIÓN REGIONAL (MULTI-MONEDA CHILE / ESPAÑA)
  // --------------------------------------------------------------------------
  getConfig: () => ipcRenderer.invoke('config:get'),
  setConfigProfile: (perfil) => ipcRenderer.invoke('config:setProfile', perfil),
  updateConfig: (customConfig) => ipcRenderer.invoke('config:update', customConfig),
  formatCurrency: (monto) => ipcRenderer.invoke('currency:format', monto),

  config: {
    get: () => ipcRenderer.invoke('config:get'),
    setProfile: (perfil) => ipcRenderer.invoke('config:setProfile', perfil),
    update: (customConfig) => ipcRenderer.invoke('config:update', customConfig),
    formatCurrency: (monto) => ipcRenderer.invoke('currency:format', monto)
  },

  // --------------------------------------------------------------------------
  // 3. OPERACIONES DE NEGOCIO: REGISTRO DE INGRESOS, CÁLCULO Y SALIDAS
  // --------------------------------------------------------------------------
  // Registro de entrada (valida patente 6-8 caracteres, vincula abono ID 17, timestamp ms)
  registrarIngreso: (datos) => ipcRenderer.invoke('parking:registrarIngreso', datos),

  // Cálculo de cobro con blindaje horario DST (acepta idIngreso, patente u objeto { patente, idIngreso })
  calcularCobro: (criterio, metodoPago) => ipcRenderer.invoke('parking:calcularCobro', criterio, metodoPago),

  // Procesamiento de salida (Efectivo con vuelto, Tarjeta/POS simulado, Transferencia)
  registrarSalida: (datos) => ipcRenderer.invoke('parking:registrarSalida', datos),

  // Gestión de cargos extras para lavados
  agregarCargosExtras: (datos) => ipcRenderer.invoke('parking:agregarCargosExtras', datos),

  // Modificación de ticket de ingreso activo (patente y/o tipo de servicio)
  modificarIngreso: (datos) => ipcRenderer.invoke('parking:modificarIngreso', datos),

  // Reimpresión de tickets
  getUltimoIngreso: () => ipcRenderer.invoke('parking:getUltimoIngreso'),
  getUltimaSalida: () => ipcRenderer.invoke('parking:getUltimaSalida'),

  // Namespace dedicado a estacionamiento
  parking: {
    registrarIngreso: (datos) => ipcRenderer.invoke('parking:registrarIngreso', datos),
    calcularCobro: (criterio, metodoPago) => ipcRenderer.invoke('parking:calcularCobro', criterio, metodoPago),
    registrarSalida: (datos) => ipcRenderer.invoke('parking:registrarSalida', datos),
    agregarCargosExtras: (datos) => ipcRenderer.invoke('parking:agregarCargosExtras', datos),
    modificarIngreso: (datos) => ipcRenderer.invoke('parking:modificarIngreso', datos),
    getUltimoIngreso: () => ipcRenderer.invoke('parking:getUltimoIngreso'),
    getUltimaSalida: () => ipcRenderer.invoke('parking:getUltimaSalida'),
    getLavadosPendientes: () => ipcRenderer.invoke('parking:getLavadosPendientes')
  },

  // --------------------------------------------------------------------------
  // 3.1 MÓDULO DE LAVADOS (FASE 3.2): COLA, CAMBIOS DE ESTADO E HISTORIAL
  // --------------------------------------------------------------------------
  lavados: {
    listar: () => ipcRenderer.invoke('lavados:listar'),
    registrar: (datos) => ipcRenderer.invoke('lavados:registrar', datos),
    cambiarEstado: (idLavado, nuevoEstado) => ipcRenderer.invoke('lavados:cambiarEstado', idLavado, nuevoEstado),
    getHistorial: (patente) => ipcRenderer.invoke('lavados:getHistorial', patente)
  },
  getLavadosCola: () => ipcRenderer.invoke('lavados:listar'),
  registrarLavado: (datos) => ipcRenderer.invoke('lavados:registrar', datos),
  cambiarEstadoLavado: (idLavado, nuevoEstado) => ipcRenderer.invoke('lavados:cambiarEstado', idLavado, nuevoEstado),
  getHistorialVehiculo: (patente) => ipcRenderer.invoke('lavados:getHistorial', patente),

  // --------------------------------------------------------------------------
  // 4. CRUD DE CLIENTES ABONADOS MENSUALES
  // --------------------------------------------------------------------------
  getClientes: () => ipcRenderer.invoke('clientes:listar'),

  clientes: {
    listar: () => ipcRenderer.invoke('clientes:listar'),
    obtenerPorPatente: (patente) => ipcRenderer.invoke('clientes:obtenerPorPatente', patente),
    crear: (datosCliente) => ipcRenderer.invoke('clientes:crear', datosCliente),
    actualizar: (id, datosCliente) => ipcRenderer.invoke('clientes:actualizar', id, datosCliente),
    eliminar: (id) => ipcRenderer.invoke('clientes:eliminar', id),
    renovar: (id, meses, metodoPago) => ipcRenderer.invoke('clientes:renovar', id, meses, metodoPago)
  },

  // --------------------------------------------------------------------------
  // 5. CONSULTAS GENERALES DE DASHBOARD, CIERRE DE CAJA Y DEMO
  // --------------------------------------------------------------------------
  getResumen: () => ipcRenderer.invoke('db:getResumen'),
  getIngresosActivos: () => ipcRenderer.invoke('db:getIngresosActivos'),
  getLavadosPendientes: () => ipcRenderer.invoke('db:getLavadosPendientes'),
  getTiposIngreso: () => ipcRenderer.invoke('db:getTiposIngreso'),
  seedDemoData: () => ipcRenderer.invoke('db:seedDemo'),
  getCierreDiario: (fecha) => ipcRenderer.invoke('caja:getCierreDiario', fecha),
  caja: {
    getCierreDiario: (fecha) => ipcRenderer.invoke('caja:getCierreDiario', fecha)
  },

  // --------------------------------------------------------------------------
  // 6. MÓDULO DE REPORTES Y ANALÍTICAS FINANCIERAS (FASE 3.3)
  // --------------------------------------------------------------------------
  getMetricasReportes: () => ipcRenderer.invoke('reportes:getMetricas'),
  getReporteOperaciones: (filtros) => ipcRenderer.invoke('reportes:getOperaciones', filtros),
  getCierreZ: (fecha) => ipcRenderer.invoke('reportes:getCierreZ', fecha),
  reportes: {
    getMetricas: () => ipcRenderer.invoke('reportes:getMetricas'),
    getOperaciones: (filtros) => ipcRenderer.invoke('reportes:getOperaciones', filtros),
    getCierreZ: (fecha) => ipcRenderer.invoke('reportes:getCierreZ', fecha)
  },

  // --------------------------------------------------------------------------
  // 7. MÓDULO DE ADMINISTRACIÓN (FASE 3.4): CATÁLOGO, TARIFAS, METAS Y USUARIOS
  // --------------------------------------------------------------------------
  servicios: {
    listar: () => ipcRenderer.invoke('servicios:listar'),
    crear: (datos) => ipcRenderer.invoke('servicios:crear', datos),
    actualizar: (id, datos) => ipcRenderer.invoke('servicios:actualizar', id, datos),
    toggleActivo: (id) => ipcRenderer.invoke('servicios:toggleActivo', id)
  },
  tarifas: {
    get: () => ipcRenderer.invoke('tarifas:get'),
    guardar: (datos) => ipcRenderer.invoke('tarifas:guardar', datos)
  },
  metas: {
    get: (params) => ipcRenderer.invoke('metas:get', params),
    guardar: (datos) => ipcRenderer.invoke('metas:guardar', datos)
  },
  usuarios: {
    listar: () => ipcRenderer.invoke('usuarios:listar'),
    crear: (datos) => ipcRenderer.invoke('usuarios:crear', datos),
    resetPassword: (datos) => ipcRenderer.invoke('usuarios:resetPassword', datos),
    eliminar: (id) => ipcRenderer.invoke('usuarios:eliminar', id)
  },
  // Aliases de conveniencia
  getTodosServicios: () => ipcRenderer.invoke('servicios:listar'),
  crearServicio: (datos) => ipcRenderer.invoke('servicios:crear', datos),
  actualizarServicio: (id, datos) => ipcRenderer.invoke('servicios:actualizar', id, datos),
  toggleServicioActivo: (id) => ipcRenderer.invoke('servicios:toggleActivo', id),
  getTarifas: () => ipcRenderer.invoke('tarifas:get'),
  guardarTarifas: (datos) => ipcRenderer.invoke('tarifas:guardar', datos),
  getMetaMensual: (params) => ipcRenderer.invoke('metas:get', params),
  guardarMetaMensual: (datos) => ipcRenderer.invoke('metas:guardar', datos),
  getUsuarios: () => ipcRenderer.invoke('usuarios:listar'),
  crearUsuario: (datos) => ipcRenderer.invoke('usuarios:crear', datos),
  resetPasswordUsuario: (datos) => ipcRenderer.invoke('usuarios:resetPassword', datos),
  eliminarUsuario: (id) => ipcRenderer.invoke('usuarios:eliminar', id)
});
