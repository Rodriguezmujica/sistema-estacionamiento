/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Suite de Pruebas Automatizadas de Backend - FASE 2 (test_fase2_backend.js)
 * ============================================================================
 */

const assert = require('assert');
const path = require('path');
const fs = require('fs');

// Configurar ruta aislada de BD para pruebas
const testDbDir = path.join(__dirname, '..', 'data');
if (!fs.existsSync(testDbDir)) fs.mkdirSync(testDbDir, { recursive: true });
process.env.DB_PATH = path.join(testDbDir, 'test_fase2.sqlite');

// Eliminar base de prueba previa si existiera
if (fs.existsSync(process.env.DB_PATH)) {
  fs.unlinkSync(process.env.DB_PATH);
}

const db = require('../database');
const currencyHelper = require('../services/currencyHelper');
const calculationEngine = require('../services/calculationEngine');
const clienteService = require('../services/clienteService');
const parkingService = require('../services/parkingService');

async function runTests() {
  console.log('============================================================');
  console.log(' INICIANDO VERIFICACIÓN DE FASE 2: CONTROLADORES Y LÓGICA');
  console.log('============================================================\n');

  // Inicializar base de datos
  await db.initDatabase();
  console.log('✔ Base de datos SQLite inicializada y esquema migrado.');

  // --------------------------------------------------------------------------
  // TEST 1: Helper de Monedas y Ley de Redondeo
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 1: Reglas de Moneda y Redondeo ---');
  // Ley de redondeo de Chile: 1-5 a 0, 6-9 a 10
  assert.strictEqual(currencyHelper.redondearLeyChile(500), 500);
  assert.strictEqual(currencyHelper.redondearLeyChile(501), 500);
  assert.strictEqual(currencyHelper.redondearLeyChile(505), 500);
  assert.strictEqual(currencyHelper.redondearLeyChile(506), 510);
  assert.strictEqual(currencyHelper.redondearLeyChile(509), 510);
  assert.strictEqual(currencyHelper.redondearLeyChile(514), 510);
  assert.strictEqual(currencyHelper.redondearLeyChile(517), 520);
  console.log('✔ Ley de Redondeo de Chile (20.956) verificada.');

  // Redondeo estándar (España / Euros)
  assert.strictEqual(currencyHelper.redondearEstandar(1.544, 2), 1.54);
  assert.strictEqual(currencyHelper.redondearEstandar(1.546, 2), 1.55);
  console.log('✔ Redondeo estándar a 2 decimales para Euros verificado.');

  // Intl.NumberFormat
  const fmtCLP = currencyHelper.formatearMoneda(1500, { moneda_codigo: 'CLP', decimales: 0 });
  const fmtEUR = currencyHelper.formatearMoneda(15.5, { moneda_codigo: 'EUR', decimales: 2 });
  console.log(`✔ Formateo Intl.NumberFormat CLP: "${fmtCLP}"`);
  console.log(`✔ Formateo Intl.NumberFormat EUR: "${fmtEUR}"`);
  assert(fmtCLP.includes('1.500') || fmtCLP.includes('1500'));
  assert(fmtEUR.includes('15,50') || fmtEUR.includes('15.50'));

  // --------------------------------------------------------------------------
  // TEST 2: Validación de Patentes Flexible (Chile / España)
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 2: Validación de Matrículas / Patentes ---');
  assert.strictEqual(clienteService.validarPatente('aa1001'), 'AA1001'); // Chile 6 chars
  assert.strictEqual(clienteService.validarPatente('BB-CC-12'), 'BBCC12'); // Chile con guiones
  assert.strictEqual(clienteService.validarPatente('1234bbb'), '1234BBB'); // España 7 chars
  assert.strictEqual(clienteService.validarPatente('M 1234 AB'), 'M1234AB'); // España provincial 7 chars
  assert.strictEqual(clienteService.validarPatente('12345678'), '12345678'); // 8 chars max

  let errorPatenteCorta = false;
  try { clienteService.validarPatente('ABC'); } catch (e) { errorPatenteCorta = true; }
  assert(errorPatenteCorta, 'Debe rechazar patente menor a 6 caracteres');

  let errorPatenteLarga = false;
  try { clienteService.validarPatente('ABCDEFGHIJ'); } catch (e) { errorPatenteLarga = true; }
  assert(errorPatenteLarga, 'Debe rechazar patente mayor a 8 caracteres');
  console.log('✔ Validación de patentes (Chile y España) superada.');

  // --------------------------------------------------------------------------
  // TEST 3: CRUD de Clientes Abonados Mensuales
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 3: CRUD de Clientes Abonados Mensuales ---');
  const nuevoCliente = await clienteService.crearCliente({
    patente: 'CLIE01',
    nombres: 'Roberto González Lagos',
    telefono: '+56 9 1122 3344',
    dia_pago: 10,
    monto_plan: 45000
  });
  assert(nuevoCliente.id > 0);
  assert.strictEqual(nuevoCliente.patente, 'CLIE01');
  assert.strictEqual(nuevoCliente.estado, 'ACTIVO');
  assert.strictEqual(nuevoCliente.esActivo, true);
  console.log('✔ Cliente creado con estado ACTIVO:', nuevoCliente.nombres);

  // Cliente vencido
  const clienteVencido = await clienteService.crearCliente({
    patente: 'VENC99',
    nombres: 'Cliente Vencido Test',
    fecha_vencimiento: '2020-01-01',
    monto_plan: 40000
  });
  assert.strictEqual(clienteVencido.estado, 'VENCIDO');
  assert.strictEqual(clienteVencido.esActivo, false);
  console.log('✔ Detección de cliente VENCIDO confirmada.');

  // Renovación de abono
  const renovacion = await clienteService.renovarAbono(clienteVencido.id, 1);
  assert.strictEqual(renovacion.cliente.estado, 'ACTIVO');
  console.log('✔ Renovación de abono vencido exitosa, nuevo estado:', renovacion.cliente.estado);

  // Listar clientes
  const listaClientes = await clienteService.listarClientes();
  assert(listaClientes.length >= 2);
  console.log(`✔ Listado de clientes abonados: ${listaClientes.length} registros encontrados.`);

  // --------------------------------------------------------------------------
  // TEST 4: Registro de Ingresos con Timestamp en ms y Detección de Abonados
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 4: Registro de Ingreso & Blindaje Horario ---');
  // 4.1 Ingreso regular de estacionamiento
  const t0 = Date.now();
  const ingEst = await parkingService.registrarIngreso({ patente: 'CH1001' });
  assert(ingEst.idIngreso > 0);
  assert(ingEst.timestamp >= t0);
  assert.strictEqual(ingEst.idtipo_ingreso, 18);
  assert.strictEqual(ingEst.esClienteMensual, false);
  console.log(`✔ Ingreso regular registrado (Patente: ${ingEst.patente}, Timestamp: ${ingEst.timestamp} ms).`);

  // 4.2 Ingreso automático como Cliente Abonado Activo (CLIE01)
  const ingAbonado = await parkingService.registrarIngreso({ patente: 'CLIE01' });
  assert.strictEqual(ingAbonado.idtipo_ingreso, 17, 'Debe asociarse automáticamente con ID 17');
  assert.strictEqual(ingAbonado.esClienteMensual, true);
  console.log(`✔ Detección automática de Abonado Activo confirmada (ID tipo servicio asignado: ${ingAbonado.idtipo_ingreso}).`);

  // 4.3 Prevención de doble ingreso
  let dobleIngresoError = false;
  try {
    await parkingService.registrarIngreso({ patente: 'CH1001' });
  } catch (err) {
    dobleIngresoError = true;
  }
  assert(dobleIngresoError, 'Debe rechazar ingresar un vehículo que ya se encuentra en recinto');
  console.log('✔ Prevención de duplicación de ingreso activo validada.');

  // --------------------------------------------------------------------------
  // TEST 5: Motor de Cálculo y Blindaje Horario (DST Immunity)
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 5: Motor de Cálculo de Cobro & Blindaje Horario ---');
  // Prueba unitaria determinista del motor de minutos
  const tBase = 1700000000000;
  // 0 ms de diferencia -> debe retornar mínimo 1 minuto
  assert.strictEqual(calculationEngine.calcularMinutosEstadia(tBase, tBase).minutos, 1);
  // 10 ms de diferencia -> debe retornar mínimo 1 minuto
  assert.strictEqual(calculationEngine.calcularMinutosEstadia(tBase, tBase + 10).minutos, 1);
  // Exactamente 45 minutos
  assert.strictEqual(calculationEngine.calcularMinutosEstadia(tBase, tBase + (45 * 60 * 1000)).minutos, 45);
  // 45 minutos y 1 segundo -> Math.ceil redondea al 46
  assert.strictEqual(calculationEngine.calcularMinutosEstadia(tBase, tBase + (45 * 60 * 1000) + 1000).minutos, 46);
  console.log('✔ Motor matemático de estadía (Math.ceil y mínimo 1 min) validado.');

  // Simular ingreso de hace 73 minutos en la base de datos
  const ahora = Date.now();
  const ts73MinAtras = ahora - (73 * 60 * 1000);
  await db.run('UPDATE ingresos SET timestamp = ? WHERE id = ?', [ts73MinAtras, ingEst.idIngreso]);

  const calculoEst = await parkingService.calcularCobro({ idIngreso: ingEst.idIngreso }, 'EFECTIVO');
  assert(calculoEst.minutosEstadia >= 73 && calculoEst.minutosEstadia <= 74);
  console.log(`✔ Estadía calculada: ${calculoEst.minutosEstadia} min.`);
  console.log(`✔ Total Bruto: $${calculoEst.totalBruto} -> Total Final Efectivo: ${calculoEst.totalFinalFormateado} (${calculoEst.desglose})`);
  // En Chile CLP: tarifa 35/min -> si minutos = 73 (2555 -> 2550), si 74 (2590 -> 2590)
  assert.strictEqual(calculoEst.totalFinal, currencyHelper.redondearLeyChile(calculoEst.minutosEstadia * 35));

  // Cobro con Tarjeta (no aplica redondeo a decenas)
  const calculoTarjeta = await parkingService.calcularCobro({ idIngreso: ingEst.idIngreso }, 'TARJETA');
  assert.strictEqual(calculoTarjeta.totalFinal, calculoEst.minutosEstadia * 35);
  console.log(`✔ Cobro con Tarjeta sin redondeo a decenas: ${calculoTarjeta.totalFinalFormateado}`);

  // Cobro Abonado Mensual (ID 17) -> Total = 0
  const calculoAbonado = await parkingService.calcularCobro({ idIngreso: ingAbonado.idIngreso }, 'EFECTIVO');
  assert.strictEqual(calculoAbonado.totalFinal, 0);
  console.log(`✔ Cobro de Plan Mensual (ID 17): $${calculoAbonado.totalFinal} (Total = 0 verificado).`);

  // Error de Ingreso (ID 19) -> Total = 1 (CLP) o 0.01 (EUR)
  const ingError = await parkingService.registrarIngreso({ patente: 'ERR100', idtipo_ingreso: 19 });
  const calculoErrorCLP = await parkingService.calcularCobro({ idIngreso: ingError.idIngreso }, 'EFECTIVO');
  assert.strictEqual(calculoErrorCLP.totalFinal, 1);
  console.log(`✔ Cobro de Error de Ingreso (ID 19) en Chile: $${calculoErrorCLP.totalFinal}`);

  // --------------------------------------------------------------------------
  // TEST 6: Multimoneda y Perfil España (Euros)
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 6: Switcher Multi-Moneda (Perfil España) ---');
  await db.setPerfilRegional('espana');
  const configEspana = await db.getConfiguracionRegional();
  assert.strictEqual(configEspana.moneda_codigo, 'EUR');
  assert.strictEqual(configEspana.activar_redondeo_chile, 0);

  // En España, Error de ingreso debe ser 0.01
  const calculoErrorEUR = await parkingService.calcularCobro({ idIngreso: ingError.idIngreso }, 'EFECTIVO');
  assert.strictEqual(calculoErrorEUR.totalFinal, 0.01);
  console.log(`✔ Cobro de Error de Ingreso en Perfil España: ${calculoErrorEUR.totalFinalFormateado}`);

  // Restaurar perfil Chile
  await db.setPerfilRegional('chile');
  console.log('✔ Perfil regional restaurado a Chile.');

  // --------------------------------------------------------------------------
  // TEST 7: Procesamiento de Salida y Métodos de Pago
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 7: Procesamiento de Salida y Métodos de Pago ---');
  // 7.1 Pago en Efectivo con cálculo de Vuelto
  const totalEsperado = calculoEst.totalFinal;
  const vueltoEsperado = 3000 - totalEsperado;
  const salidaEfectivo = await parkingService.registrarSalida({
    id_ingreso: ingEst.idIngreso,
    metodo_pago: 'EFECTIVO',
    monto_recibido: 3000
  });
  assert.strictEqual(salidaEfectivo.success, true);
  assert.strictEqual(salidaEfectivo.total, totalEsperado);
  assert.strictEqual(salidaEfectivo.vuelto, vueltoEsperado);
  console.log(`✔ Salida en EFECTIVO procesada. Total: ${salidaEfectivo.totalFormateado}, Recibido: ${salidaEfectivo.monto_recibidoFormateado}, Vuelto: ${salidaEfectivo.vueltoFormateado}`);

  // 7.2 Pago de Abonado Mensual
  const salidaAbonado = await parkingService.registrarSalida({
    id_ingreso: ingAbonado.idIngreso,
    metodo_pago: 'EFECTIVO'
  });
  assert.strictEqual(salidaAbonado.total, 0);
  console.log(`✔ Salida de Abonado Mensual procesada por $0.`);

  // 7.3 Lavado con Extras y Pago con TARJETA / POS Simulado
  const ingLavado = await parkingService.registrarIngreso({
    patente: 'LAV999',
    idtipo_ingreso: 2, // Lavado Completo
    motivos_extra: ['Aspirado profundo tapiz'],
    precio_extra: 5000
  });

  const salidaTarjeta = await parkingService.registrarSalida({
    id_ingreso: ingLavado.idIngreso,
    metodo_pago: 'TARJETA'
  });
  assert.strictEqual(salidaTarjeta.success, true);
  assert.strictEqual(salidaTarjeta.tipo_pago, 'pos_simulado');
  assert(salidaTarjeta.detallePago.codigoAutorizacion.startsWith('AUTH-'));
  console.log(`✔ Salida con TARJETA/POS simulado procesada. Código Auth: ${salidaTarjeta.detallePago.codigoAutorizacion}`);

  // Verificar que lavados_pendientes fue limpiado tras la salida
  const lavadosRestantes = await db.all('SELECT * FROM lavados_pendientes WHERE id_ingreso = ?', [ingLavado.idIngreso]);
  assert.strictEqual(lavadosRestantes.length, 0);
  console.log('✔ Cola de lavados_pendientes limpiada automáticamente al egresar.');

  // Cerrar conexión
  await db.closeDatabase();

  // Limpiar archivo de prueba
  if (fs.existsSync(process.env.DB_PATH)) {
    fs.unlinkSync(process.env.DB_PATH);
  }

  console.log('\n============================================================');
  console.log(' TODOS LOS TESTS DE LA FASE 2 HAN PASADO SATISFACTORIAMENTE ');
  console.log('============================================================');
}

runTests().catch((err) => {
  console.error('\n❌ ERROR EN PRUEBAS:', err);
  process.exit(1);
});
