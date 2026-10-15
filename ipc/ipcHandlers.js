/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Manejadores de Comunicación Inter-Proceso IPC (ipcHandlers.js)
 * ============================================================================
 * Conecta los eventos emitidos por el proceso Renderer (vía preload.js)
 * con la capa de servicios y controladores de backend en Node.js/SQLite.
 * ============================================================================
 */

const { app, ipcMain } = require('electron');
const db = require('../database');
const parkingService = require('../services/parkingService');
const clienteService = require('../services/clienteService');
const calculationEngine = require('../services/calculationEngine');
const currencyHelper = require('../services/currencyHelper');

/**
 * Registra todos los manejadores IPC con control de excepciones y respuestas estructuradas.
 */
function registerAllIpcHandlers() {
  console.log('[IPC] Registrando manejadores de negocio de la Fase 2...');

  // ==========================================================================
  // 1. INFORMACIÓN DEL SISTEMA Y ENTORNO
  // ==========================================================================
  ipcMain.handle('app:getInfo', async () => {
    return {
      version: app.getVersion(),
      name: app.getName(),
      platform: process.platform,
      arch: process.arch,
      electronVersion: process.versions.electron,
      nodeVersion: process.versions.node,
      userDataPath: app.getPath('userData'),
      dbPath: db.DB_FILE_PATH,
      timestampActual: Date.now(),
      zonaHorariaLocal: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
  });

  // ==========================================================================
  // 2. CONFIGURACIÓN REGIONAL (MULTI-MONEDA CHILE / ESPAÑA)
  // ==========================================================================
  // Consulta de configuración activa
  ipcMain.handle('config:get', async () => {
    return await db.getConfiguracionRegional();
  });
  // Alias de compatibilidad Fase 1
  ipcMain.handle('db:getConfig', async () => {
    return await db.getConfiguracionRegional();
  });

  // Alternar perfil regional ('chile' o 'espana')
  ipcMain.handle('config:setProfile', async (event, perfil) => {
    try {
      const config = await db.setPerfilRegional(perfil);
      console.log(`[IPC] Perfil regional cambiado exitosamente a: ${perfil}`);
      return { success: true, perfil, config };
    } catch (err) {
      console.error('[IPC Error] config:setProfile:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:setConfigProfile', async (event, perfil) => {
    const res = await db.setPerfilRegional(perfil);
    return { success: true, config: res };
  });

  // Actualización personalizada de tarifas y opciones regionales
  ipcMain.handle('config:update', async (event, customConfig) => {
    try {
      const config = await db.setPerfilRegional(customConfig);
      return { success: true, config };
    } catch (err) {
      console.error('[IPC Error] config:update:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:updateConfig', async (event, customConfig) => {
    const config = await db.setPerfilRegional(customConfig);
    return { success: true, config };
  });

  // Helper de formateo monetario bajo demanda
  ipcMain.handle('currency:format', async (event, monto) => {
    const config = await db.getConfiguracionRegional();
    return currencyHelper.formatearMoneda(monto, config);
  });

  // ==========================================================================
  // 3. OPERACIONES DE NEGOCIO: REGISTRO DE INGRESOS
  // ==========================================================================
  // Valida patente (6-8 chars), vincula abono activo (ID 17) y guarda timestamp ms
  ipcMain.handle('parking:registrarIngreso', async (event, datos) => {
    try {
      return await parkingService.registrarIngreso(datos);
    } catch (err) {
      console.error('[IPC Error] parking:registrarIngreso:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:registrarIngreso', async (event, datos) => {
    return await parkingService.registrarIngreso(datos);
  });

  // ==========================================================================
  // 4. MOTOR DE CÁLCULO DE COBRO Y BLINDAJE HORARIO (DST-IMMUNE)
  // ==========================================================================
  // Busca último ingreso activo por patente o por ID y calcula estadía y cobro
  ipcMain.handle('parking:calcularCobro', async (event, criterio, metodoPago = 'EFECTIVO') => {
    try {
      // El criterio puede ser un número (idIngreso), string (patente) u objeto { idIngreso, patente }
      const params = typeof criterio === 'object' 
        ? criterio 
        : (typeof criterio === 'number' ? { idIngreso: criterio } : { patente: String(criterio) });
      return await parkingService.calcularCobro(params, metodoPago);
    } catch (err) {
      console.error('[IPC Error] parking:calcularCobro:', err.message);
      throw err;
    }
  });
  // Alias de compatibilidad con Fase 1 (recibe idIngreso, metodoPago)
  ipcMain.handle('db:calcularCobro', async (event, idIngreso, metodoPago) => {
    return await parkingService.calcularCobro({ idIngreso }, metodoPago || 'EFECTIVO');
  });

  // ==========================================================================
  // 5. PROCESAMIENTO DE SALIDA Y MÉTODOS DE PAGO
  // ==========================================================================
  // Soporta EFECTIVO (con vuelto), TRANSFERENCIA y TARJETA/POS (simulado)
  ipcMain.handle('parking:registrarSalida', async (event, datos) => {
    try {
      return await parkingService.registrarSalida(datos);
    } catch (err) {
      console.error('[IPC Error] parking:registrarSalida:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:registrarSalida', async (event, datos) => {
    return await parkingService.registrarSalida(datos);
  });

  // ==========================================================================
  // 6. GESTIÓN DE CARGOS EXTRAS EN LAVADOS
  // ==========================================================================
  ipcMain.handle('parking:agregarCargosExtras', async (event, datos) => {
    try {
      return await parkingService.agregarCargosExtras(datos);
    } catch (err) {
      console.error('[IPC Error] parking:agregarCargosExtras:', err.message);
      throw err;
    }
  });

  ipcMain.handle('parking:getLavadosPendientes', async () => {
    try {
      return await db.getLavadosPendientes();
    } catch (err) {
      console.error('[IPC Error] parking:getLavadosPendientes:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:getLavadosPendientes', async () => {
    return await db.getLavadosPendientes();
  });

  // ==========================================================================
  // 6.1 MÓDULO DE LAVADOS (FASE 3.2): COLA, CAMBIO DE FASE Y REGISTRO
  // ==========================================================================
  ipcMain.handle('lavados:listar', async () => {
    try {
      return await db.getLavadosPendientes();
    } catch (err) {
      console.error('[IPC Error] lavados:listar:', err.message);
      throw err;
    }
  });

  ipcMain.handle('lavados:cambiarEstado', async (event, idLavado, nuevoEstado) => {
    try {
      return await db.cambiarEstadoLavado(idLavado, nuevoEstado);
    } catch (err) {
      console.error('[IPC Error] lavados:cambiarEstado:', err.message);
      throw err;
    }
  });

  ipcMain.handle('lavados:getHistorial', async (event, patente) => {
    try {
      return await db.getHistorialVehiculo(patente);
    } catch (err) {
      console.error('[IPC Error] lavados:getHistorial:', err.message);
      throw err;
    }
  });

  ipcMain.handle('lavados:registrar', async (event, datos) => {
    try {
      return await db.registrarLavadoCompleto(datos);
    } catch (err) {
      console.error('[IPC Error] lavados:registrar:', err.message);
      throw err;
    }
  });

  // Modificación de tickets de ingreso activos (corrección de patente o cambio de servicio)
  ipcMain.handle('parking:modificarIngreso', async (event, datos) => {
    try {
      return await parkingService.modificarIngreso(datos);
    } catch (err) {
      console.error('[IPC Error] parking:modificarIngreso:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:modificarIngreso', async (event, datos) => {
    return await parkingService.modificarIngreso(datos);
  });

  // Reimpresión de tickets: Último ingreso registrado
  ipcMain.handle('parking:getUltimoIngreso', async () => {
    try {
      return await parkingService.getUltimoIngreso();
    } catch (err) {
      console.error('[IPC Error] parking:getUltimoIngreso:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:getUltimoIngreso', async () => {
    return await parkingService.getUltimoIngreso();
  });

  // Reimpresión de tickets: Última salida/cobro registrado
  ipcMain.handle('parking:getUltimaSalida', async () => {
    try {
      return await parkingService.getUltimaSalida();
    } catch (err) {
      console.error('[IPC Error] parking:getUltimaSalida:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:getUltimaSalida', async () => {
    return await parkingService.getUltimaSalida();
  });

  // ==========================================================================
  // 7. CRUD DE CLIENTES ABONADOS MENSUALES
  // ==========================================================================
  ipcMain.handle('clientes:listar', async () => {
    try {
      return await clienteService.listarClientes();
    } catch (err) {
      console.error('[IPC Error] clientes:listar:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:getClientes', async () => {
    return await clienteService.listarClientes();
  });

  ipcMain.handle('clientes:obtenerPorPatente', async (event, patente) => {
    try {
      return await clienteService.obtenerClientePorPatente(patente);
    } catch (err) {
      console.error('[IPC Error] clientes:obtenerPorPatente:', err.message);
      throw err;
    }
  });

  ipcMain.handle('clientes:crear', async (event, datosCliente) => {
    try {
      const nuevo = await clienteService.crearCliente(datosCliente);
      console.log(`[IPC] Cliente mensual creado: ${nuevo.nombres} (${nuevo.patente})`);
      return { success: true, cliente: nuevo };
    } catch (err) {
      console.error('[IPC Error] clientes:crear:', err.message);
      throw err;
    }
  });

  ipcMain.handle('clientes:actualizar', async (event, id, datosCliente) => {
    try {
      const actualizado = await clienteService.actualizarCliente(id, datosCliente);
      return { success: true, cliente: actualizado };
    } catch (err) {
      console.error('[IPC Error] clientes:actualizar:', err.message);
      throw err;
    }
  });

  ipcMain.handle('clientes:eliminar', async (event, id) => {
    try {
      return await clienteService.eliminarCliente(id);
    } catch (err) {
      console.error('[IPC Error] clientes:eliminar:', err.message);
      throw err;
    }
  });

  ipcMain.handle('clientes:renovar', async (event, id, meses = 1, metodoPago = 'EFECTIVO') => {
    try {
      return await clienteService.renovarAbono(id, meses, metodoPago);
    } catch (err) {
      console.error('[IPC Error] clientes:renovar:', err.message);
      throw err;
    }
  });

  // ==========================================================================
  // 8. CONSULTAS OPERATIVAS GENERALES Y DEMO
  // ==========================================================================
  ipcMain.handle('db:getResumen', async () => {
    try {
      return await db.getResumenDashboard();
    } catch (err) {
      console.error('[IPC Error] db:getResumen:', err.message);
      throw err;
    }
  });

  ipcMain.handle('db:getIngresosActivos', async () => {
    try {
      return await db.getIngresosActivos();
    } catch (err) {
      console.error('[IPC Error] db:getIngresosActivos:', err.message);
      throw err;
    }
  });

  ipcMain.handle('db:getTiposIngreso', async () => {
    try {
      return await db.getTiposIngreso();
    } catch (err) {
      console.error('[IPC Error] db:getTiposIngreso:', err.message);
      throw err;
    }
  });

  ipcMain.handle('db:seedDemo', async () => {
    try {
      const resultado = await db.seedDemoData();
      console.log('[IPC] Semilla de datos demo ejecutada exitosamente.');
      return resultado;
    } catch (err) {
      console.error('[IPC Error] db:seedDemo:', err.message);
      throw err;
    }
  });

  ipcMain.handle('caja:getCierreDiario', async (event, fecha) => {
    try {
      return await db.getCierreCajaDiario(fecha);
    } catch (err) {
      console.error('[IPC Error] caja:getCierreDiario:', err.message);
      throw err;
    }
  });
  ipcMain.handle('db:getCierreDiario', async (event, fecha) => {
    return await db.getCierreCajaDiario(fecha);
  });

  // ==========================================================================
  // 9. MÓDULO DE REPORTES Y ANALÍTICAS FINANCIERAS (FASE 3.3)
  // ==========================================================================
  ipcMain.handle('reportes:getMetricas', async () => {
    try {
      return await db.getMetricasReportes();
    } catch (err) {
      console.error('[IPC Error] reportes:getMetricas:', err.message);
      throw err;
    }
  });

  ipcMain.handle('reportes:getOperaciones', async (event, filtros) => {
    try {
      return await db.getReporteOperaciones(filtros);
    } catch (err) {
      console.error('[IPC Error] reportes:getOperaciones:', err.message);
      throw err;
    }
  });

  ipcMain.handle('reportes:getCierreZ', async (event, fecha) => {
    try {
      return await db.getCierreCajaDiario(fecha);
    } catch (err) {
      console.error('[IPC Error] reportes:getCierreZ:', err.message);
      throw err;
    }
  });

  // ==========================================================================
  // 10. MÓDULO DE ADMINISTRACIÓN (FASE 3.4): CATÁLOGO, TARIFAS, METAS Y USUARIOS
  // ==========================================================================
  // 10.1 Catálogo de Servicios (tipo_ingreso)
  ipcMain.handle('servicios:listar', async () => {
    try {
      return await db.getTodosTiposIngreso();
    } catch (err) {
      console.error('[IPC Error] servicios:listar:', err.message);
      throw err;
    }
  });

  ipcMain.handle('servicios:crear', async (event, datos) => {
    try {
      return await db.crearTipoIngreso(datos);
    } catch (err) {
      console.error('[IPC Error] servicios:crear:', err.message);
      throw err;
    }
  });

  ipcMain.handle('servicios:actualizar', async (event, id, datos) => {
    try {
      return await db.actualizarTipoIngreso(id, datos);
    } catch (err) {
      console.error('[IPC Error] servicios:actualizar:', err.message);
      throw err;
    }
  });

  ipcMain.handle('servicios:toggleActivo', async (event, id) => {
    try {
      return await db.toggleActivoTipoIngreso(id);
    } catch (err) {
      console.error('[IPC Error] servicios:toggleActivo:', err.message);
      throw err;
    }
  });

  // 10.2 Tarifas de Estacionamiento (precios / configuracion_regional)
  ipcMain.handle('tarifas:get', async () => {
    try {
      return await db.getTarifas();
    } catch (err) {
      console.error('[IPC Error] tarifas:get:', err.message);
      throw err;
    }
  });

  ipcMain.handle('tarifas:guardar', async (event, datos) => {
    try {
      return await db.guardarTarifas(datos);
    } catch (err) {
      console.error('[IPC Error] tarifas:guardar:', err.message);
      throw err;
    }
  });

  // 10.3 Metas Mensuales y Resumen Ejecutivo
  ipcMain.handle('metas:get', async (event, params) => {
    try {
      return await db.getMetaMensualResumen(params);
    } catch (err) {
      console.error('[IPC Error] metas:get:', err.message);
      throw err;
    }
  });

  ipcMain.handle('metas:guardar', async (event, datos) => {
    try {
      return await db.guardarMetaMensual(datos);
    } catch (err) {
      console.error('[IPC Error] metas:guardar:', err.message);
      throw err;
    }
  });

  // 10.4 Usuarios del Sistema (usuarios con hash seguro)
  ipcMain.handle('usuarios:listar', async () => {
    try {
      return await db.getUsuarios();
    } catch (err) {
      console.error('[IPC Error] usuarios:listar:', err.message);
      throw err;
    }
  });

  ipcMain.handle('usuarios:crear', async (event, datos) => {
    try {
      return await db.crearUsuario(datos);
    } catch (err) {
      console.error('[IPC Error] usuarios:crear:', err.message);
      throw err;
    }
  });

  ipcMain.handle('usuarios:resetPassword', async (event, datos) => {
    try {
      return await db.resetPasswordUsuario(datos);
    } catch (err) {
      console.error('[IPC Error] usuarios:resetPassword:', err.message);
      throw err;
    }
  });

  ipcMain.handle('usuarios:eliminar', async (event, id) => {
    try {
      return await db.eliminarUsuario(id);
    } catch (err) {
      console.error('[IPC Error] usuarios:eliminar:', err.message);
      throw err;
    }
  });

  console.log('[IPC] Todos los manejadores IPC registrados correctamente.');
}

module.exports = {
  registerAllIpcHandlers
};
