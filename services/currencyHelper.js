/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Helper de Moneda, Redondeo y Formateo Internacional (currencyHelper.js)
 * ============================================================================
 * Manejo multi-moneda (Chile CLP / España EUR), Ley de Redondeo 20.956 (Chile),
 * redondeo a centavos estándar y formateo regionalizado con Intl.NumberFormat.
 * ============================================================================
 */

/**
 * Aplica la Ley de Redondeo de Chile (Ley N° 20.956) para pagos en EFECTIVO.
 * Regla:
 * - El último dígito terminado entre 1 y 5 pesos se redondea hacia abajo a la decena ($0).
 * - El último dígito terminado entre 6 y 9 pesos se redondea hacia arriba a la decena ($10).
 * - Si termina en 0, se mantiene igual.
 *
 * @param {number} monto - Monto numérico en CLP
 * @returns {number} Monto redondeado a la decena más cercana según la ley
 */
function redondearLeyChile(monto) {
  const entero = Math.round(Number(monto) || 0);
  const residuo = entero % 10;

  if (residuo >= 1 && residuo <= 5) {
    return entero - residuo;
  } else if (residuo >= 6) {
    return entero + (10 - residuo);
  }
  return entero;
}

/**
 * Redondeo matemático estándar para monedas fraccionarias (ej. Euros, Dólares)
 * o medios de pago electrónicos no sujetos a redondeo legal de efectivo.
 *
 * @param {number} monto - Monto a redondear
 * @param {number} decimales - Cantidad de cifras decimales (por defecto 2)
 * @returns {number} Monto redondeado
 */
function redondearEstandar(monto, decimales = 2) {
  const factor = Math.pow(10, decimales);
  return Math.round((Number(monto) + Number.EPSILON) * factor) / factor;
}

/**
 * Aplica la regla de redondeo correspondiente según la configuración regional
 * activa y el método de pago seleccionado.
 *
 * @param {number} montoBruto - Monto calculado sin redondear
 * @param {Object} config - Objeto de configuración regional (activar_redondeo_chile, decimales, etc.)
 * @param {string} metodoPago - 'EFECTIVO', 'TRANSFERENCIA', 'TARJETA', etc.
 * @returns {number} Monto final aplicable
 */
function aplicarReglaRedondeo(montoBruto, config, metodoPago = 'EFECTIVO') {
  const esEfectivo = String(metodoPago).trim().toUpperCase() === 'EFECTIVO';
  const activarChile = Boolean(config && (config.activar_redondeo_chile === 1 || config.activar_redondeo_chile === true));

  if (activarChile && esEfectivo) {
    return redondearLeyChile(montoBruto);
  }

  const decimales = config && typeof config.decimales === 'number' ? config.decimales : (config && config.moneda_codigo === 'CLP' ? 0 : 2);
  return redondearEstandar(montoBruto, decimales);
}

/**
 * Helper con Intl.NumberFormat para formatear cualquier monto monetario
 * según el código de moneda activo (CLP $, EUR €, etc.).
 *
 * @param {number} monto - Monto a formatear
 * @param {Object} config - Configuración regional con moneda_codigo y decimales
 * @returns {string} Cadena formateada con símbolo monetario (ej: "$ 1.500" o "1,50 €")
 */
function formatearMoneda(monto, config = {}) {
  const codigo = (config.moneda_codigo || 'CLP').toUpperCase();
  const locale = codigo === 'CLP' ? 'es-CL' : 'es-ES';
  const decDefecto = codigo === 'CLP' ? 0 : 2;
  const decimales = typeof config.decimales === 'number' ? config.decimales : decDefecto;

  try {
    const formatter = new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: codigo,
      minimumFractionDigits: decimales,
      maximumFractionDigits: decimales
    });
    return formatter.format(Number(monto) || 0);
  } catch (err) {
    // Fallback defensivo si el entorno no soporta el código de moneda
    const simbolo = config.moneda_simbolo || (codigo === 'EUR' ? '€' : '$');
    const valor = redondearEstandar(monto, decimales).toFixed(decimales);
    return `${simbolo} ${valor}`;
  }
}

/**
 * Formatea una marca de tiempo en la fecha local del sistema operativo del usuario.
 * @param {number|string|Date} timestampOrDate - Marca de tiempo en ms o fecha
 * @param {Intl.DateTimeFormatOptions} [options] - Opciones opcionales de formato
 * @returns {string} Fecha formateada en el huso horario local del usuario
 */
function formatearFechaLocal(timestampOrDate, options = {}) {
  if (!timestampOrDate) return '---';
  const fecha = new Date(timestampOrDate);
  if (isNaN(fecha.getTime())) return 'Fecha inválida';

  const defaultOptions = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    ...options
  };

  return fecha.toLocaleDateString(undefined, defaultOptions);
}

/**
 * Formatea una marca de tiempo en la hora local del sistema operativo del usuario.
 * @param {number|string|Date} timestampOrDate - Marca de tiempo en ms o fecha
 * @param {Intl.DateTimeFormatOptions} [options] - Opciones opcionales de formato
 * @returns {string} Hora formateada en el huso horario local del usuario (HH:mm:ss)
 */
function formatearHoraLocal(timestampOrDate, options = {}) {
  if (!timestampOrDate) return '---';
  const fecha = new Date(timestampOrDate);
  if (isNaN(fecha.getTime())) return 'Hora inválida';

  const defaultOptions = {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
    ...options
  };

  return fecha.toLocaleTimeString(undefined, defaultOptions);
}

/**
 * Retorna fecha y hora legible combinada en el huso local
 * @param {number|string|Date} timestampOrDate
 * @returns {string} Ej: "14/10/2026, 19:15:00"
 */
function formatearFechaHoraLocal(timestampOrDate) {
  if (!timestampOrDate) return '---';
  const fecha = new Date(timestampOrDate);
  if (isNaN(fecha.getTime())) return 'Fecha/Hora inválida';
  return `${formatearFechaLocal(fecha)} ${formatearHoraLocal(fecha)}`;
}

module.exports = {
  redondearLeyChile,
  redondearEstandar,
  aplicarReglaRedondeo,
  formatearMoneda,
  formatearFechaLocal,
  formatearHoraLocal,
  formatearFechaHoraLocal
};
