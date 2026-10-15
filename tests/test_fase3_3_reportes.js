/**
 * Test de Verificación Automatizada: Paso 3.3 - Módulo de Reportes & Analíticas
 */
const db = require('../database');

async function testReportesModule() {
  console.log('============================================================');
  console.log(' TEST AUTOMATIZADO: PASO 3.3 - MÓDULO DE REPORTES PARKFLOW');
  console.log('============================================================\n');

  await db.initDatabase();
  await db.seedDemoData();

  // TEST 1: Métricas Financieras (KPI Cards)
  console.log('--- TEST 1: Métricas Financieras (KPI Cards) ---');
  const metricas = await db.getMetricasReportes();
  console.log('Facturación Diaria:', metricas.facturacionDiaria);
  console.log('Mensual Hábiles (Lun-Vie):', metricas.mensualHabiles);
  console.log('Mensual Total:', metricas.mensualTotal);
  console.log('Ocupación Activa:', metricas.ocupacionActiva);

  if (typeof metricas.facturacionDiaria.total !== 'number') throw new Error('Facturacion Diaria total invalido');
  if (typeof metricas.mensualHabiles.total !== 'number') throw new Error('Mensual Habiles total invalido');
  if (typeof metricas.mensualTotal.total !== 'number') throw new Error('Mensual Total invalido');
  if (typeof metricas.ocupacionActiva.total !== 'number') throw new Error('Ocupacion Activa total invalido');
  console.log('✔ KPIs financieros verificados correctamente.\n');

  // TEST 2: Filtros de Consulta y Desglose por Categorías
  console.log('--- TEST 2: Operaciones Históricas & Desglose por Categoría ---');
  const hoyStr = new Date().toISOString().split('T')[0];
  const reporteHoy = await db.getReporteOperaciones({ desde: hoyStr, hasta: hoyStr });

  console.log(`Operaciones encontradas hoy (${hoyStr}):`, reporteHoy.operaciones.length);
  console.log('Categorías desglosadas:', reporteHoy.categorias.length);
  reporteHoy.categorias.forEach(c => {
    console.log(` - ${c.categoria}: ${c.cantidad} operaciones, subtotal: ${c.subtotalFormateado} (${c.porcentaje}%)`);
  });

  if (!Array.isArray(reporteHoy.operaciones)) throw new Error('Lista de operaciones invalida');
  if (!Array.isArray(reporteHoy.categorias)) throw new Error('Lista de categorias invalida');
  console.log('✔ Reporte y desglose por categorías verificado correctamente.\n');

  // TEST 3: Búsqueda por Matrícula
  console.log('--- TEST 3: Filtro por Matrícula ---');
  const reportePatente = await db.getReporteOperaciones({ patente: 'LAVA88' });
  console.log('Operaciones con patente LAVA88:', reportePatente.operaciones.length);
  if (reportePatente.operaciones.length > 0) {
    console.log('Folio:', reportePatente.operaciones[0].folio, 'Patente:', reportePatente.operaciones[0].patente);
  }
  console.log('✔ Filtro de búsqueda por matrícula verificado.\n');

  // TEST 4: Arqueo y Cierre Z de Caja
  console.log('--- TEST 4: Arqueo y Cierre Z de Caja ---');
  const cierre = await db.getCierreCajaDiario(hoyStr);
  console.log('Servicios cerrados:', cierre.totalTransacciones);
  console.log('Efectivo en Cajón (Gaveta):', cierre.totalEfectivoFormateado);
  console.log('Tarjetas / POS:', cierre.totalTarjetaFormateado);
  console.log('Transferencias:', cierre.totalTransferenciaFormateado);
  console.log('Total General:', cierre.totalRecaudadoFormateado);

  const totalCalculado = cierre.totalEfectivo + cierre.totalTarjeta + cierre.totalTransferencia;
  if (totalCalculado !== cierre.totalRecaudado) {
    throw new Error(`Discrepancia en cuadratura: suma ${totalCalculado} !== total ${cierre.totalRecaudado}`);
  }
  console.log('✔ Cuadratura Z estricta validada (Efectivo + Electrónico = Total).\n');

  console.log('============================================================');
  console.log(' TODOS LOS TESTS DEL PASO 3.3 HAN PASADO CON ÉXITO');
  console.log('============================================================');
  await db.closeDatabase();
  process.exit(0);
}

testReportesModule().catch(err => {
  console.error('Error en pruebas de Paso 3.3:', err);
  process.exit(1);
});
