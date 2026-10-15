/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Servicio de Clientes Abonados Mensuales (clienteService.js)
 * ============================================================================
 * CRUD completo de abonados mensuales, validación de patentes multi-región,
 * control de vencimientos y renovación automatizada de planes.
 * ============================================================================
 */

const db = require('../database');
const { formatearMoneda } = require('./currencyHelper');

/**
 * Normaliza y valida una matrícula o patente para formatos Chile o España.
 * Regla: Alfanumérica entre 6 y 8 caracteres, en mayúsculas.
 *
 * @param {string} patente
 * @returns {string} Patente normalizada
 */
function validarPatente(patente) {
  if (!patente || typeof patente !== 'string') {
    throw new Error('La matrícula/patente es obligatoria.');
  }

  // Eliminar espacios, guiones y caracteres no alfanuméricos
  const limpia = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

  if (limpia.length < 6 || limpia.length > 8) {
    throw new Error(`Patente inválida ('${limpia}'). Debe tener entre 6 y 8 caracteres alfanuméricos (formatos Chile / España).`);
  }

  return limpia;
}

/**
 * Evalúa si una fecha de vencimiento está activa o vencida respecto al día de hoy.
 *
 * @param {string} fechaVencimientoStr - Cadena en formato YYYY-MM-DD
 * @returns {{ esActivo: boolean, diasDiferencia: number, estado: 'ACTIVO' | 'VENCIDO' }}
 */
function evaluarEstadoAbono(fechaVencimientoStr) {
  if (!fechaVencimientoStr) {
    return { esActivo: false, diasDiferencia: 0, estado: 'VENCIDO' };
  }

  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);

  // Parsear fecha de vencimiento asegurando comparación a medianoche local
  const partes = fechaVencimientoStr.split('-');
  let fechaVenc;
  if (partes.length === 3) {
    fechaVenc = new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, parseInt(partes[2], 10));
  } else {
    fechaVenc = new Date(fechaVencimientoStr);
  }
  fechaVenc.setHours(0, 0, 0, 0);

  const diffMs = fechaVenc.getTime() - hoy.getTime();
  const diasDiferencia = Math.round(diffMs / (1000 * 60 * 60 * 24));
  const esActivo = diasDiferencia >= 0;

  return {
    esActivo,
    diasDiferencia,
    estado: esActivo ? 'ACTIVO' : 'VENCIDO'
  };
}

/**
 * Obtiene la lista de todos los clientes abonados con su estado de vigencia calculado.
 */
async function listarClientes() {
  const config = await db.getConfiguracionRegional();
  const clientes = await db.all('SELECT * FROM clientes ORDER BY nombres ASC');

  return clientes.map((c) => {
    const estadoInfo = evaluarEstadoAbono(c.fecha_vencimiento);
    return {
      ...c,
      ...estadoInfo,
      monto_plan_formateado: formatearMoneda(c.monto_plan, config)
    };
  });
}

/**
 * Obtiene un cliente por su identificador único.
 */
async function obtenerClientePorId(id) {
  const c = await db.get('SELECT * FROM clientes WHERE id = ?', [id]);
  if (!c) return null;

  const config = await db.getConfiguracionRegional();
  const estadoInfo = evaluarEstadoAbono(c.fecha_vencimiento);
  return {
    ...c,
    ...estadoInfo,
    monto_plan_formateado: formatearMoneda(c.monto_plan, config)
  };
}

/**
 * Busca un cliente por su patente y determina si su abono está activo.
 */
async function obtenerClientePorPatente(patente) {
  const normalizada = validarPatente(patente);
  const c = await db.get('SELECT * FROM clientes WHERE patente = ?', [normalizada]);
  if (!c) return null;

  const config = await db.getConfiguracionRegional();
  const estadoInfo = evaluarEstadoAbono(c.fecha_vencimiento);
  return {
    ...c,
    ...estadoInfo,
    monto_plan_formateado: formatearMoneda(c.monto_plan, config)
  };
}

/**
 * Registra un nuevo cliente abonado mensual.
 */
async function crearCliente({ patente, nombres, telefono, vehiculo, dia_pago = 5, fecha_vencimiento, monto_plan = 0 }) {
  const normalizada = validarPatente(patente);

  if (!nombres || nombres.trim().length === 0) {
    throw new Error('El nombre del cliente abonado es obligatorio.');
  }

  // Verificar si la patente ya está registrada en abonados
  const existente = await db.get('SELECT id FROM clientes WHERE patente = ?', [normalizada]);
  if (existente) {
    throw new Error(`Ya existe un cliente mensual registrado con la patente ${normalizada}.`);
  }

  // Si no se proporcionó fecha de vencimiento, generar vencimiento al mes siguiente
  let vencimiento = fecha_vencimiento;
  if (!vencimiento) {
    const hoy = new Date();
    hoy.setMonth(hoy.getMonth() + 1);
    vencimiento = hoy.toISOString().slice(0, 10);
  }

  const res = await db.run(`
    INSERT INTO clientes (patente, nombres, telefono, vehiculo, dia_pago, fecha_vencimiento, monto_plan)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `, [
    normalizada,
    nombres.trim(),
    telefono ? telefono.trim() : '',
    vehiculo ? vehiculo.trim() : '',
    parseInt(dia_pago, 10) || 5,
    vencimiento,
    parseFloat(monto_plan) || 0
  ]);

  return await obtenerClientePorId(res.lastID);
}

/**
 * Actualiza los datos de un cliente abonado existente.
 */
async function actualizarCliente(id, { patente, nombres, telefono, vehiculo, dia_pago, fecha_vencimiento, monto_plan }) {
  const existente = await db.get('SELECT id, patente FROM clientes WHERE id = ?', [id]);
  if (!existente) {
    throw new Error(`Cliente abonado con ID ${id} no encontrado.`);
  }

  let normalizada = existente.patente;
  if (patente) {
    normalizada = validarPatente(patente);
    // Verificar que la nueva patente no pertenezca a otro cliente
    const conflicto = await db.get('SELECT id FROM clientes WHERE patente = ? AND id != ?', [normalizada, id]);
    if (conflicto) {
      throw new Error(`La patente ${normalizada} ya está en uso por otro cliente (ID ${conflicto.id}).`);
    }
  }

  await db.run(`
    UPDATE clientes
    SET patente = ?,
        nombres = COALESCE(?, nombres),
        telefono = COALESCE(?, telefono),
        vehiculo = COALESCE(?, vehiculo),
        dia_pago = COALESCE(?, dia_pago),
        fecha_vencimiento = COALESCE(?, fecha_vencimiento),
        monto_plan = COALESCE(?, monto_plan)
    WHERE id = ?
  `, [
    normalizada,
    nombres ? nombres.trim() : null,
    telefono !== undefined ? telefono.trim() : null,
    vehiculo !== undefined ? vehiculo.trim() : null,
    dia_pago !== undefined ? parseInt(dia_pago, 10) : null,
    fecha_vencimiento || null,
    monto_plan !== undefined ? parseFloat(monto_plan) : null,
    id
  ]);

  return await obtenerClientePorId(id);
}

/**
 * Elimina un cliente abonado por su ID.
 */
async function eliminarCliente(id) {
  const cliente = await obtenerClientePorId(id);
  if (!cliente) {
    throw new Error(`Cliente con ID ${id} no encontrado.`);
  }

  await db.run('DELETE FROM clientes WHERE id = ?', [id]);
  return { success: true, clienteEliminado: cliente };
}

/**
 * Renueva el abono mensual extendiendo la fecha de vencimiento en N meses (por defecto 1 mes)
 * y registra el cobro en las operaciones de salida del sistema.
 */
async function renovarAbono(id, meses = 1, metodoPago = 'EFECTIVO') {
  const cliente = await db.get('SELECT * FROM clientes WHERE id = ?', [id]);
  if (!cliente) {
    throw new Error(`Cliente con ID ${id} no encontrado.`);
  }

  const baseDate = new Date();
  let nuevaFecha;

  // Si la fecha actual de vencimiento es posterior a hoy, extender a partir de ella
  const fechaVencActual = new Date(cliente.fecha_vencimiento);
  if (!isNaN(fechaVencActual.getTime()) && fechaVencActual > baseDate) {
    nuevaFecha = new Date(fechaVencActual);
  } else {
    nuevaFecha = new Date(baseDate);
  }

  nuevaFecha.setMonth(nuevaFecha.getMonth() + (parseInt(meses, 10) || 1));
  const nuevaFechaStr = nuevaFecha.toISOString().slice(0, 10);

  // Actualizar fecha de vencimiento en la tabla clientes
  await db.run('UPDATE clientes SET fecha_vencimiento = ? WHERE id = ?', [nuevaFechaStr, id]);

  // Registrar cobro en ingresos y salidas si el monto es mayor a 0
  const monto = parseFloat(cliente.monto_plan) || 0;
  if (monto > 0) {
    const ahoraMs = Date.now();
    const ahoraStr = new Date(ahoraMs).toISOString();

    const insIng = await db.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES (?, ?, ?, 17, 1)
    `, [cliente.patente, ahoraStr, ahoraMs]);

    await db.run(`
      INSERT INTO salidas (
        id_ingresos, fecha_salida, timestamp, total, metodo_pago, tipo_pago,
        tiempo_estadia, monto_recibido, vuelto
      ) VALUES (?, ?, ?, ?, ?, 'manual', 'Renovación Abono Mensual', ?, 0)
    `, [insIng.lastID, ahoraStr, ahoraMs, monto, metodoPago || 'EFECTIVO', monto]);
  }

  const clienteActualizado = await obtenerClientePorId(id);
  return {
    success: true,
    mensaje: `Abono de ${clienteActualizado.nombres} renovado exitosamente hasta el ${nuevaFechaStr}. Cobro registrado (${clienteActualizado.monto_plan_formateado}).`,
    cliente: clienteActualizado
  };
}

module.exports = {
  validarPatente,
  evaluarEstadoAbono,
  listarClientes,
  obtenerClientePorId,
  obtenerClientePorPatente,
  crearCliente,
  actualizarCliente,
  eliminarCliente,
  renovarAbono
};
