/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Motor de Cálculo de Cobro y Blindaje Horario (calculationEngine.js)
 * ============================================================================
 * Lógica pura de cálculo financiero y temporal.
 * INMUNIDAD TOTAL A CAMBIOS DE HORA (DST / Verano-Invierno):
 * Todo cálculo se basa en marcas de tiempo numéricas absolutas en milisegundos (Unix Epoch).
 * ============================================================================
 */

const {
  aplicarReglaRedondeo,
  formatearMoneda,
  formatearFechaLocal,
  formatearHoraLocal,
  formatearFechaHoraLocal
} = require('./currencyHelper');

/**
 * Calcula los minutos transcurridos entre dos marcas de tiempo en milisegundos.
 * Garantiza al menos 1 minuto de estadía.
 *
 * @param {number} timestampInicioMs - Marca de tiempo de ingreso en ms
 * @param {number} [timestampFinMs=Date.now()] - Marca de tiempo de salida o cálculo en ms
 * @returns {{ minutos: number, milisegundos: number }}
 */
function calcularMinutosEstadia(timestampInicioMs, timestampFinMs = Date.now()) {
  const inicio = Number(timestampInicioMs);
  const fin = Number(timestampFinMs);

  if (isNaN(inicio) || inicio <= 0) {
    throw new Error('Timestamp de ingreso inválido o no numérico.');
  }

  const diferenciaMs = Math.max(0, fin - inicio);
  // Regla crítica: redondeo hacia arriba al minuto completo y asegurando mínimo 1 min
  const minutos = Math.max(1, Math.ceil(diferenciaMs / (1000 * 60)));

  return {
    minutos,
    milisegundos: diferenciaMs
  };
}

/**
 * Ejecuta el cálculo completo del cobro para un servicio dado.
 *
 * @param {Object} params
 * @param {Object} params.ingreso - Registro de ingreso con timestamp, patente, idtipo_ingreso, etc.
 * @param {Object} params.servicio - Registro de tipo_ingreso (idtipo_ingresos, nombre_servicio, precio, es_plan)
 * @param {Object} [params.lavadoPendiente] - Registro de lavados_pendientes (precio_extra, motivos_extra)
 * @param {Object} params.configRegional - Configuración regional activa (precios, moneda, redondeo)
 * @param {string} [params.metodoPago='EFECTIVO'] - Método de pago ('EFECTIVO', 'TARJETA', 'TRANSFERENCIA')
 * @param {number} [params.timestampCalculo=Date.now()] - Timestamp en milisegundos del momento del cálculo
 * @returns {Object} Detalle exhaustivo del cálculo de cobro
 */
function calcularCobroServicio({
  ingreso,
  servicio,
  lavadoPendiente = null,
  configRegional,
  metodoPago = 'EFECTIVO',
  timestampCalculo = Date.now()
}) {
  if (!ingreso) {
    throw new Error('El objeto de ingreso es requerido para calcular el cobro.');
  }

  // Resolver timestamp numérico de ingreso (con fallback a fecha_ingreso si es necesario)
  let timestampIngreso = Number(ingreso.timestamp);
  if (!timestampIngreso || isNaN(timestampIngreso)) {
    timestampIngreso = new Date(ingreso.fecha_ingreso).getTime();
  }

  const { minutos, milisegundos } = calcularMinutosEstadia(timestampIngreso, timestampCalculo);

  const idServicio = Number(servicio?.idtipo_ingresos || ingreso.idtipo_ingreso || 18);
  const esPlan = Boolean(servicio?.es_plan === 1 || idServicio === 17);
  const precioMinuto = Number(configRegional.precio_minuto) || 35;
  const precioMinimo = Number(configRegional.precio_minimo) || 500;
  const esEuros = configRegional.moneda_codigo === 'EUR' || configRegional.activar_redondeo_chile === 0;

  let totalBruto = 0;
  let desglose = '';
  let tarifaAplicada = 'estandar';

  // 1. Plan Mensual (ID 17 o es_plan = 1) -> Total = 0
  if (esPlan || idServicio === 17) {
    totalBruto = 0;
    desglose = 'Abonado Mensual (Suscripción activa, exento de cobro por tiempo)';
    tarifaAplicada = 'plan_mensual';
  }
  // 2. Estacionamiento por Minuto (ID 18) -> max(minutos * precio_minuto, precio_minimo) (+ lavado si está vinculado)
  else if (idServicio === 18) {
    const subtotal = minutos * precioMinuto;
    let stayMonto = subtotal;
    if (subtotal < precioMinimo) {
      stayMonto = precioMinimo;
      desglose = `Estadía (${formatearMoneda(precioMinimo, configRegional)}) por ${minutos} min`;
    } else {
      desglose = `Estadía (${minutos} min × ${formatearMoneda(precioMinuto, configRegional)}/min)`;
    }

    const washBase = Number(lavadoPendiente?.precio_base || 0);
    const washExtra = Number(lavadoPendiente?.precio_extra || 0);
    const washTotal = washBase + washExtra;

    if (washTotal > 0 || lavadoPendiente?.vinculado_estadia) {
      totalBruto = stayMonto + washTotal;
      if (washBase > 0) {
        desglose += ` + Lavado [${lavadoPendiente?.servicio_nombre || 'Servicio'}] (${formatearMoneda(washBase, configRegional)})`;
      }
      if (washExtra > 0) {
        desglose += ` + Extras (${formatearMoneda(washExtra, configRegional)})`;
      }
      tarifaAplicada = 'estadia_con_lavado';
    } else {
      totalBruto = stayMonto;
      tarifaAplicada = subtotal < precioMinimo ? 'tarifa_minima' : 'minuto_estandar';
    }
  }
  // 3. Error de Ingreso (ID 19) -> Simbólico: 1 CLP o 0.01 EUR
  else if (idServicio === 19) {
    totalBruto = esEuros ? 0.01 : 1;
    desglose = `Anulación por Error de Ingreso (${formatearMoneda(totalBruto, configRegional)})`;
    tarifaAplicada = 'error_ingreso';
  }
  // 4. Lavados y otros servicios automotrices
  else {
    const precioBase = Number(lavadoPendiente?.precio_base || servicio?.precio || 0);
    const precioExtra = Number(lavadoPendiente?.precio_extra || 0);
    totalBruto = precioBase + precioExtra;

    let extrasDesc = '';
    if (lavadoPendiente?.motivos_extra) {
      try {
        const parsed = JSON.parse(lavadoPendiente.motivos_extra);
        extrasDesc = Array.isArray(parsed) ? parsed.join(', ') : parsed;
      } catch (e) {
        extrasDesc = String(lavadoPendiente.motivos_extra);
      }
    }

    if (precioExtra > 0) {
      desglose = `${servicio?.nombre_servicio || 'Lavado'} (${formatearMoneda(precioBase, configRegional)}) + Extras [${extrasDesc}]: ${formatearMoneda(precioExtra, configRegional)}`;
    } else {
      desglose = `${servicio?.nombre_servicio || 'Lavado'} (${formatearMoneda(precioBase, configRegional)})`;
    }
    tarifaAplicada = 'servicio_lavado';
  }

  // Aplicar reglas de redondeo monetario (ID 19 se mantiene en su valor simbólico fijo $1 o 0.01€)
  let totalFinal = totalBruto;
  if (idServicio === 19) {
    totalFinal = totalBruto; // Preservar valor simbólico contable
  } else if (idServicio === 17 || esPlan) {
    totalFinal = 0;
  } else {
    totalFinal = aplicarReglaRedondeo(totalBruto, configRegional, metodoPago);
  }

  const diferenciaRedondeo = totalFinal - totalBruto;

  return {
    idIngreso: ingreso.id,
    patente: ingreso.patente,
    idtipo_ingreso: idServicio,
    nombre_servicio: servicio?.nombre_servicio || (idServicio === 18 ? 'Estacionamiento' : 'Servicio'),
    
    // Marcas de tiempo y fechas formateadas en huso local del SO
    timestampIngreso,
    timestampCalculo,
    fechaIngresoLocal: formatearFechaLocal(timestampIngreso),
    horaIngresoLocal: formatearHoraLocal(timestampIngreso),
    fechaCalculoLocal: formatearFechaLocal(timestampCalculo),
    horaCalculoLocal: formatearHoraLocal(timestampCalculo),

    // Métricas de estadía
    minutosEstadia: minutos,
    tiempoEstadiaLegible: `${minutos} min`,
    milisegundosEstadia: milisegundos,

    // Detalle financiero
    metodoPago: metodoPago.toUpperCase(),
    tarifaAplicada,
    desglose,
    totalBruto,
    totalFinal,
    diferenciaRedondeo,
    totalFinalFormateado: formatearMoneda(totalFinal, configRegional),
    redondeoChileAplicado: Boolean(configRegional.activar_redondeo_chile && metodoPago.toUpperCase() === 'EFECTIVO'),

    // Extras si aplican
    precioExtra: Number(lavadoPendiente?.precio_extra || 0),
    motivosExtra: lavadoPendiente?.motivos_extra || null,

    configRegional
  };
}

module.exports = {
  calcularMinutosEstadia,
  calcularCobroServicio
};
