/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Servicio Central de Operaciones de Estacionamiento (parkingService.js)
 * ============================================================================
 * Orquesta ingresos, cálculo con blindaje horario, procesamiento de cobro,
 * métodos de pago (Efectivo con vuelto, Tarjeta/POS simulado, Transferencia)
 * y gestión de servicios adicionales de lavado.
 * ============================================================================
 */

const db = require('../database');
const { validarPatente, obtenerClientePorPatente } = require('./clienteService');
const { calcularCobroServicio } = require('./calculationEngine');
const {
  formatearMoneda,
  formatearFechaLocal,
  formatearHoraLocal,
  formatearFechaHoraLocal
} = require('./currencyHelper');

/**
 * Registra el ingreso de un vehículo al recinto.
 * 
 * Reglas de negocio:
 * 1. Valida patente alfanumérica flexible entre 6 y 8 caracteres (Chile/España).
 * 2. Verifica si el vehículo ya está dentro del recinto (salida = 0).
 * 3. Comprueba si la patente corresponde a un abonado mensual activo; si es así,
 *    se asocia automáticamente al servicio ID 17 (Plan Mensual).
 * 4. Guarda el ingreso con timestamp numérico en milisegundos (Date.now()) y salida = 0.
 * 5. Si es lavado con extras, registra en lavados_pendientes dentro de una transacción.
 *
 * @param {Object} params
 * @param {string} params.patente - Matrícula o patente del vehículo
 * @param {number} [params.idtipo_ingreso] - ID del servicio seleccionado (opcional)
 * @param {string[]|string} [params.motivos_extra] - Conceptos adicionales de lavado
 * @param {number} [params.precio_extra] - Recargo monetario por adicionales
 * @returns {Promise<Object>} Resultado del registro con detalles del ticket
 */
async function registrarIngreso({ patente, idtipo_ingreso, motivos_extra, precio_extra = 0 }) {
  // 1. Normalizar y validar patente flexible
  const patenteNormalizada = validarPatente(patente);

  // 2. Verificar si la patente ya posee un ingreso activo en el recinto
  const ingresoExistente = await db.get(`
    SELECT id, fecha_ingreso, timestamp FROM ingresos 
    WHERE patente = ? AND salida = 0
  `, [patenteNormalizada]);

  if (ingresoExistente) {
    throw new Error(`El vehículo con patente ${patenteNormalizada} ya se encuentra dentro del recinto (Ticket ID: ${ingresoExistente.id}).`);
  }

  // 3. Verificar si pertenece a un Cliente Mensual con abono activo
  let tipoFinal = idtipo_ingreso ? parseInt(idtipo_ingreso, 10) : 18;
  const clienteAbonado = await obtenerClientePorPatente(patenteNormalizada);

  let esClienteMensual = false;
  if (clienteAbonado && clienteAbonado.esActivo) {
    esClienteMensual = true;
    // Si no se eligió un servicio de lavado explícito, se asigna automáticamente Plan Mensual (ID 17)
    if (!idtipo_ingreso || parseInt(idtipo_ingreso, 10) === 18) {
      tipoFinal = 17;
    }
  }

  // 4. Registrar con timestamp numérico en milisegundos (Date.now())
  const timestampAhora = Date.now();
  const fechaIngresoISO = new Date(timestampAhora).toISOString();

  return await db.withTransaction(async (tx) => {
    const res = await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES (?, ?, ?, ?, 0)
    `, [patenteNormalizada, fechaIngresoISO, timestampAhora, tipoFinal]);

    const idIngreso = res.lastID;

    // 5. Registrar extras de lavado si corresponde
    const extraNum = parseFloat(precio_extra) || 0;
    if (extraNum > 0) {
      const extraJson = typeof motivos_extra === 'string' 
        ? motivos_extra 
        : JSON.stringify(motivos_extra || []);

      await tx.run(`
        INSERT INTO lavados_pendientes (id_ingreso, patente, motivos_extra, precio_extra)
        VALUES (?, ?, ?, ?)
      `, [idIngreso, patenteNormalizada, extraJson, extraNum]);
    }

    const servicioInfo = await db.get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [tipoFinal]);

    return {
      success: true,
      idIngreso,
      patente: patenteNormalizada,
      idtipo_ingreso: tipoFinal,
      nombre_servicio: servicioInfo?.nombre_servicio || 'Estacionamiento',
      timestamp: timestampAhora,
      fecha_ingreso: fechaIngresoISO,
      fechaLocal: formatearFechaLocal(timestampAhora),
      horaLocal: formatearHoraLocal(timestampAhora),
      esClienteMensual,
      mensaje: esClienteMensual 
        ? `Ingreso registrado como Cliente Abonado Activo (${clienteAbonado.nombres}).` 
        : `Ingreso registrado exitosamente (Folio #${idIngreso}).`
    };
  });
}

/**
 * Busca el último ingreso activo de un vehículo por ID o por patente.
 *
 * @param {Object} criterio - { idIngreso?: number, patente?: string }
 * @returns {Promise<Object>} Registro del ingreso con información del servicio y extras
 */
async function buscarUltimoIngresoActivo(criterio) {
  let ingreso = null;

  if (criterio.idIngreso) {
    ingreso = await db.get(`
      SELECT i.*, t.nombre_servicio, t.precio as precio_base_servicio, t.es_plan
      FROM ingresos i
      JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
      WHERE i.id = ? AND i.salida = 0
    `, [criterio.idIngreso]);
  } else if (criterio.patente) {
    const patenteNormalizada = validarPatente(criterio.patente);
    ingreso = await db.get(`
      SELECT i.*, t.nombre_servicio, t.precio as precio_base_servicio, t.es_plan
      FROM ingresos i
      JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
      WHERE i.patente = ? AND i.salida = 0
      ORDER BY i.id DESC
      LIMIT 1
    `, [patenteNormalizada]);
  } else {
    throw new Error('Debe proporcionar un ID de ingreso o una patente para calcular el cobro.');
  }

  if (!ingreso) {
    const desc = criterio.idIngreso ? `con ID ${criterio.idIngreso}` : `para la patente ${criterio.patente}`;
    throw new Error(`No se encontró ningún ingreso activo ${desc}.`);
  }

  // Buscar cargos extras de lavados si existen
  const lavadoPendiente = await db.get(`
    SELECT * FROM lavados_pendientes WHERE id_ingreso = ?
  `, [ingreso.id]);

  return { ingreso, lavadoPendiente };
}

/**
 * Calcula el cobro en tiempo real para un vehículo en el recinto.
 * Inmune a DST mediante timestamps absolutos en milisegundos.
 *
 * @param {Object} criterio - { idIngreso?: number, patente?: string }
 * @param {string} [metodoPago='EFECTIVO'] - Método de pago a simular
 * @returns {Promise<Object>} Resultado detallado del cálculo
 */
async function calcularCobro(criterio, metodoPago = 'EFECTIVO') {
  const { ingreso, lavadoPendiente } = await buscarUltimoIngresoActivo(criterio);
  const configRegional = await db.getConfiguracionRegional();

  const servicio = {
    idtipo_ingresos: ingreso.idtipo_ingreso,
    nombre_servicio: ingreso.nombre_servicio,
    precio: ingreso.precio_base_servicio,
    es_plan: ingreso.es_plan
  };

  return calcularCobroServicio({
    ingreso,
    servicio,
    lavadoPendiente,
    configRegional,
    metodoPago,
    timestampCalculo: Date.now()
  });
}

/**
 * Procesa la salida definitiva de un vehículo y su método de pago.
 *
 * Métodos de pago soportados:
 * - EFECTIVO: Calcula y valida vuelto/cambio automáticamente.
 * - TRANSFERENCIA: Registro inmediato con comprobante opcional.
 * - TARJETA / POS: Simulador para portafolio con código de autorización bancaria.
 *
 * Ejecuta en transacción atómica:
 * 1. Inserta en salidas con timestamp numérico.
 * 2. Actualiza ingresos (salida = 1).
 * 3. Limpia lavados_pendientes.
 *
 * @param {Object} params
 * @param {number} [params.id_ingreso] - ID del ticket de entrada
 * @param {string} [params.patente] - Patente alternativa para ubicar el ingreso
 * @param {string} [params.metodo_pago='EFECTIVO'] - 'EFECTIVO' | 'TARJETA' | 'TRANSFERENCIA'
 * @param {number} [params.monto_recibido] - Monto entregado en efectivo por el cliente
 * @param {string} [params.tipo_pago='manual'] - 'manual' | 'pos_simulado'
 * @param {string} [params.comprobante] - Código o nota de transferencia/comprobante
 * @returns {Promise<Object>} Resumen completo del comprobante de salida
 */
async function registrarSalida({
  id_ingreso,
  patente,
  metodo_pago = 'EFECTIVO',
  monto_recibido,
  tipo_pago = 'manual',
  comprobante = ''
}) {
  const metodoNormalizado = String(metodo_pago).trim().toUpperCase();
  const calculo = await calcularCobro({ idIngreso: id_ingreso, patente }, metodoNormalizado);
  const configRegional = calculo.configRegional;
  const timestampSalida = Date.now();
  const fechaSalidaISO = new Date(timestampSalida).toISOString();

  let vuelto = 0;
  let montoRecibidoFinal = calculo.totalFinal;
  let authCode = null;
  let detalleMetodo = {};

  // 1. Manejo de método EFECTIVO y cálculo de vuelto
  if (metodoNormalizado === 'EFECTIVO') {
    if (monto_recibido !== undefined && monto_recibido !== null && monto_recibido !== '') {
      montoRecibidoFinal = parseFloat(monto_recibido) || 0;
      if (montoRecibidoFinal < calculo.totalFinal) {
        throw new Error(
          `Monto insuficiente: El total a pagar es ${calculo.totalFinalFormateado} y se recibieron ${formatearMoneda(montoRecibidoFinal, configRegional)}.`
        );
      }
      vuelto = montoRecibidoFinal - calculo.totalFinal;
      // Si está en Chile y efectivo, el vuelto también se redondeará coherentemente
      if (configRegional.activar_redondeo_chile) {
        vuelto = Math.round(vuelto);
      }
    } else {
      // Pago exacto
      montoRecibidoFinal = calculo.totalFinal;
      vuelto = 0;
    }

    detalleMetodo = {
      tipo: 'EFECTIVO',
      montoRecibido: montoRecibidoFinal,
      montoRecibidoFormateado: formatearMoneda(montoRecibidoFinal, configRegional),
      vuelto,
      vueltoFormateado: formatearMoneda(vuelto, configRegional)
    };
  }
  // 2. Manejo de método TARJETA / POS (Simulador de Portafolio)
  else if (metodoNormalizado === 'TARJETA' || metodoNormalizado === 'POS') {
    tipo_pago = 'pos_simulado';
    authCode = `AUTH-${Math.random().toString(36).substring(2, 8).toUpperCase()}`;
    const ultimos4 = String(Math.floor(1000 + Math.random() * 9000));
    const transaccionId = `TX-${Date.now()}`;

    detalleMetodo = {
      tipo: 'TARJETA_POS',
      operacion: 'VENTA_APROBADA',
      codigoAutorizacion: authCode,
      transaccionId,
      ultimos4Digitos: ultimos4,
      marcaTarjeta: 'Visa/Mastercard',
      mensajeSimulador: 'Transacción aprobada satisfactoriamente por Terminal POS.'
    };
  }
  // 3. Manejo de método TRANSFERENCIA
  else if (metodoNormalizado === 'TRANSFERENCIA') {
    authCode = comprobante ? `TRANSF-${comprobante}` : `TRANSF-${Date.now().toString().slice(-6)}`;
    detalleMetodo = {
      tipo: 'TRANSFERENCIA',
      comprobante: authCode
    };
  } else {
    detalleMetodo = { tipo: metodoNormalizado };
  }

  // Ejecución transaccional ACID en SQLite
  return await db.withTransaction(async (tx) => {
    // A. Insertar registro en tabla salidas
    const resSalida = await tx.run(`
      INSERT INTO salidas (
        id_ingresos,
        fecha_salida,
        timestamp,
        total,
        metodo_pago,
        tipo_pago,
        tiempo_estadia,
        monto_recibido,
        vuelto,
        auth_code
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `, [
      calculo.idIngreso,
      fechaSalidaISO,
      timestampSalida,
      calculo.totalFinal,
      metodoNormalizado,
      tipo_pago,
      calculo.tiempoEstadiaLegible,
      montoRecibidoFinal,
      vuelto,
      authCode
    ]);

    const idSalida = resSalida.lastID;

    // B. Marcar el ingreso como finalizado (salida = 1)
    await tx.run(`
      UPDATE ingresos SET salida = 1 WHERE id = ?
    `, [calculo.idIngreso]);

    // C. Limpiar registro de lavados pendientes si existía
    await tx.run(`
      DELETE FROM lavados_pendientes WHERE id_ingreso = ?
    `, [calculo.idIngreso]);

    return {
      success: true,
      idSalida,
      idIngreso: calculo.idIngreso,
      patente: calculo.patente,
      servicio: calculo.nombre_servicio,
      tiempo_estadia: calculo.tiempoEstadiaLegible,
      minutos: calculo.minutosEstadia,
      total: calculo.totalFinal,
      totalFormateado: calculo.totalFinalFormateado,
      totalBruto: calculo.totalBruto,
      metodo_pago: metodoNormalizado,
      tipo_pago,
      vuelto,
      vueltoFormateado: formatearMoneda(vuelto, configRegional),
      monto_recibido: montoRecibidoFinal,
      montoRecibido: montoRecibidoFinal,
      monto_recibidoFormateado: formatearMoneda(montoRecibidoFinal, configRegional),
      montoRecibidoFormateado: formatearMoneda(montoRecibidoFinal, configRegional),
      timestampSalida,
      fechaSalidaLocal: formatearFechaLocal(timestampSalida),
      horaSalidaLocal: formatearHoraLocal(timestampSalida),
      fechaHoraSalida: formatearFechaHoraLocal(timestampSalida),
      detallePago: detalleMetodo,
      configRegional
    };
  });
}

/**
 * Gestión de cargos extras para servicios de lavado activos en el recinto.
 */
async function agregarCargosExtras({ id_ingreso, motivos_extra, precio_extra }) {
  const ingreso = await db.get('SELECT id, patente FROM ingresos WHERE id = ? AND salida = 0', [id_ingreso]);
  if (!ingreso) {
    throw new Error(`No se encontró un vehículo activo con el ID ${id_ingreso}.`);
  }

  const extraNum = parseFloat(precio_extra) || 0;
  const extraJson = typeof motivos_extra === 'string' ? motivos_extra : JSON.stringify(motivos_extra || []);

  const existente = await db.get('SELECT id FROM lavados_pendientes WHERE id_ingreso = ?', [id_ingreso]);

  if (existente) {
    await db.run(`
      UPDATE lavados_pendientes
      SET motivos_extra = ?, precio_extra = ?
      WHERE id_ingreso = ?
    `, [extraJson, extraNum, id_ingreso]);
  } else {
    await db.run(`
      INSERT INTO lavados_pendientes (id_ingreso, patente, motivos_extra, precio_extra)
      VALUES (?, ?, ?, ?)
    `, [id_ingreso, ingreso.patente, extraJson, extraNum]);
  }

  return {
    success: true,
    id_ingreso,
    patente: ingreso.patente,
    precio_extra: extraNum,
    motivos_extra: extraJson
  };
}

/**
 * Modifica los datos de un ticket de ingreso activo (salida = 0):
 * - Permite corregir la patente en caso de error de tipeo.
 * - Permite cambiar el tipo de servicio (ej: Estacionamiento a Lavado).
 *
 * @param {Object} params
 * @param {number} params.idIngreso - ID del registro en tabla ingresos
 * @param {string} params.patente - Nueva patente corregida
 * @param {number} params.idtipo_ingreso - Nuevo ID del servicio
 * @returns {Promise<Object>} Resultado de la modificación
 */
async function modificarIngreso({ idIngreso, patente, idtipo_ingreso }) {
  if (!idIngreso) {
    throw new Error('El ID de ticket/ingreso es obligatorio para realizar modificaciones.');
  }

  // 1. Verificar existencia y estado en recinto (salida = 0)
  const ingresoActual = await db.get(`
    SELECT i.*, t.nombre_servicio 
    FROM ingresos i
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    WHERE i.id = ? AND i.salida = 0
  `, [parseInt(idIngreso, 10)]);

  if (!ingresoActual) {
    throw new Error(`No se encontró un vehículo activo en recinto con Folio #${idIngreso}.`);
  }

  // 2. Normalizar y validar patente (6-8 caracteres estándar)
  const patenteNormalizada = validarPatente(patente);

  // 3. Validar duplicidad: que no exista otro vehículo activo con la misma patente
  const duplicado = await db.get(`
    SELECT id FROM ingresos 
    WHERE patente = ? AND salida = 0 AND id != ?
  `, [patenteNormalizada, parseInt(idIngreso, 10)]);

  if (duplicado) {
    throw new Error(`Ya existe otro vehículo activo dentro del recinto con la patente ${patenteNormalizada} (Folio #${duplicado.id}).`);
  }

  // 4. Validar el nuevo tipo de ingreso
  const tipoFinal = parseInt(idtipo_ingreso, 10);
  const tipoServicio = await db.get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [tipoFinal]);
  if (!tipoServicio) {
    throw new Error(`El servicio seleccionado (ID ${idtipo_ingreso}) no es válido.`);
  }

  // 5. Aplicar cambios en SQLite bajo transacción ACID
  return await db.withTransaction(async (tx) => {
    await tx.run(`
      UPDATE ingresos 
      SET patente = ?, idtipo_ingreso = ?
      WHERE id = ? AND salida = 0
    `, [patenteNormalizada, tipoFinal, parseInt(idIngreso, 10)]);

    // Actualizar también la patente en cola de lavados pendientes si existía
    await tx.run(`
      UPDATE lavados_pendientes 
      SET patente = ?
      WHERE id_ingreso = ?
    `, [patenteNormalizada, parseInt(idIngreso, 10)]);

    return {
      success: true,
      idIngreso: parseInt(idIngreso, 10),
      patenteAnterior: ingresoActual.patente,
      patente: patenteNormalizada,
      servicioAnterior: ingresoActual.nombre_servicio,
      idtipo_ingreso: tipoFinal,
      nombre_servicio: tipoServicio.nombre_servicio,
      mensaje: `Ticket #${idIngreso} actualizado: ${patenteNormalizada} - ${tipoServicio.nombre_servicio}`
    };
  });
}

/**
 * Consulta el último vehículo ingresado al recinto para reimpresión de ticket de entrada.
 * @returns {Promise<Object>} Datos formateados del ticket de ingreso
 */
async function getUltimoIngreso() {
  const configRegional = await db.getConfiguracionRegional();
  const row = await db.get(`
    SELECT 
      i.id as idIngreso,
      i.patente,
      i.fecha_ingreso,
      i.timestamp,
      i.idtipo_ingreso,
      i.salida,
      t.nombre_servicio,
      t.precio as precio_base_servicio,
      t.es_plan
    FROM ingresos i
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    ORDER BY i.id DESC
    LIMIT 1
  `);

  if (!row) {
    throw new Error('No hay registros de ingreso en el sistema.');
  }

  const ts = Number(row.timestamp) || new Date(row.fecha_ingreso).getTime();

  return {
    tipoTicket: 'ingreso',
    idIngreso: row.idIngreso,
    id: row.idIngreso,
    patente: row.patente,
    idtipo_ingreso: row.idtipo_ingreso,
    servicio: row.nombre_servicio,
    nombre_servicio: row.nombre_servicio,
    precio_base_servicio: row.precio_base_servicio,
    precioFormateado: formatearMoneda(row.precio_base_servicio, configRegional),
    es_plan: row.es_plan,
    salida: row.salida,
    timestamp: ts,
    fechaIngresoLocal: formatearFechaLocal(ts),
    horaIngresoLocal: formatearHoraLocal(ts),
    fechaHoraIngreso: formatearFechaHoraLocal(ts),
    configRegional
  };
}

/**
 * Consulta el último cobro/salida realizado para reimpresión de comprobante de pago.
 * @returns {Promise<Object>} Datos formateados del ticket de salida
 */
async function getUltimaSalida() {
  const configRegional = await db.getConfiguracionRegional();
  const row = await db.get(`
    SELECT 
      s.id as idSalida,
      s.id_ingresos as idIngreso,
      s.fecha_salida,
      s.timestamp as timestampSalida,
      s.total,
      s.metodo_pago,
      s.tipo_pago,
      s.tiempo_estadia,
      s.monto_recibido,
      s.vuelto,
      s.auth_code,
      i.patente,
      i.fecha_ingreso,
      i.timestamp as timestampIngreso,
      t.nombre_servicio
    FROM salidas s
    JOIN ingresos i ON s.id_ingresos = i.id
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    ORDER BY s.id DESC
    LIMIT 1
  `);

  if (!row) {
    throw new Error('No se han registrado cobros ni salidas en el sistema todavía.');
  }

  const tsSalida = Number(row.timestampSalida) || new Date(row.fecha_salida).getTime();
  const tsIngreso = Number(row.timestampIngreso) || new Date(row.fecha_ingreso).getTime();

  return {
    tipoTicket: 'salida',
    idSalida: row.idSalida,
    idIngreso: row.idIngreso,
    id: row.idSalida,
    patente: row.patente,
    servicio: row.nombre_servicio,
    nombre_servicio: row.nombre_servicio,
    tiempo_estadia: row.tiempo_estadia,
    total: row.total,
    totalFormateado: formatearMoneda(row.total, configRegional),
    metodo_pago: row.metodo_pago,
    tipo_pago: row.tipo_pago,
    vuelto: row.vuelto,
    vueltoFormateado: formatearMoneda(row.vuelto || 0, configRegional),
    monto_recibido: row.monto_recibido,
    montoRecibido: row.monto_recibido,
    monto_recibidoFormateado: formatearMoneda(row.monto_recibido || row.total, configRegional),
    montoRecibidoFormateado: formatearMoneda(row.monto_recibido || row.total, configRegional),
    timestampSalida: tsSalida,
    fechaSalidaLocal: formatearFechaLocal(tsSalida),
    horaSalidaLocal: formatearHoraLocal(tsSalida),
    fechaHoraSalida: formatearFechaHoraLocal(tsSalida),
    fechaIngresoLocal: formatearFechaLocal(tsIngreso),
    horaIngresoLocal: formatearHoraLocal(tsIngreso),
    authCode: row.auth_code,
    configRegional
  };
}

module.exports = {
  registrarIngreso,
  buscarUltimoIngresoActivo,
  calcularCobro,
  registrarSalida,
  agregarCargosExtras,
  modificarIngreso,
  getUltimoIngreso,
  getUltimaSalida
};
