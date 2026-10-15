/**
 * ============================================================================
 * TEST AUTOMATIZADO: PASO 3.4 - MÓDULO DE ADMINISTRACIÓN (BACKEND & SQLITE)
 * ============================================================================
 */

const assert = require('assert');
const path = require('path');
const fs = require('fs');

// Asignar base de datos aislada para testing
const testDbPath = path.join(__dirname, '..', 'data', 'test_fase3_4_admin.sqlite');
if (fs.existsSync(testDbPath)) {
  fs.unlinkSync(testDbPath);
}
process.env.DB_PATH = testDbPath;

const db = require('../database');
const clienteService = require('../services/clienteService');

async function runTests() {
  console.log('============================================================');
  console.log(' TEST AUTOMATIZADO: PASO 3.4 - MÓDULO DE ADMINISTRACIÓN');
  console.log('============================================================\n');

  await db.initDatabase();
  await db.seedDemoData();

  // --------------------------------------------------------------------------
  // TEST 1: Abonados / Clientes Mensuales (CRUD + Renovación con Cobro)
  // --------------------------------------------------------------------------
  console.log('--- TEST 1: Abonados / Clientes Mensuales ---');
  // 1.1 Crear abonado con vehículo
  const nuevoCliente = await clienteService.crearCliente({
    patente: 'KLRX45',
    nombres: 'Carlos Santana Soto',
    telefono: '+56 9 9988 7766',
    vehiculo: 'Nissan Qashqai 2022',
    dia_pago: 10,
    fecha_vencimiento: '2026-10-15',
    monto_plan: 48000
  });

  assert.strictEqual(nuevoCliente.patente, 'KLRX45');
  assert.strictEqual(nuevoCliente.vehiculo, 'Nissan Qashqai 2022');
  console.log('✔ Cliente creado con vehículo:', nuevoCliente.nombres, `(${nuevoCliente.vehiculo})`);

  // 1.2 Listar clientes
  const listaClientes = await clienteService.listarClientes();
  assert.ok(listaClientes.length >= 3);
  console.log('✔ Listado de clientes abonados:', listaClientes.length, 'registros.');

  // 1.3 Renovar Mes (Adelanta fecha y registra cobro en salidas)
  const salidasAntes = await db.get('SELECT COUNT(*) as count FROM salidas WHERE tiempo_estadia = "Renovación Abono Mensual"');
  const renovacion = await clienteService.renovarAbono(nuevoCliente.id, 1, 'EFECTIVO');
  assert.strictEqual(renovacion.success, true);
  
  const clientePostRenovacion = await clienteService.obtenerClientePorId(nuevoCliente.id);
  assert.strictEqual(clientePostRenovacion.fecha_vencimiento, '2026-11-15');
  
  const salidasDespues = await db.get('SELECT COUNT(*) as count FROM salidas WHERE tiempo_estadia = "Renovación Abono Mensual"');
  assert.strictEqual(salidasDespues.count, salidasAntes.count + 1);
  console.log('✔ Renovación de mes completada y cobro registrado en salidas (Nuevo vencimiento: 2026-11-15).');

  // 1.4 Actualizar cliente
  const clienteEditado = await clienteService.actualizarCliente(nuevoCliente.id, {
    vehiculo: 'Nissan Qashqai 2023 Turbo',
    telefono: '+56 9 1122 3344'
  });
  assert.strictEqual(clienteEditado.vehiculo, 'Nissan Qashqai 2023 Turbo');
  console.log('✔ Actualización de cliente exitosa.');

  // --------------------------------------------------------------------------
  // TEST 2: Catálogo de Servicios (tipo_ingreso)
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 2: Catálogo de Servicios ---');
  const catalogo = await db.getTodosTiposIngreso();
  assert.ok(catalogo.length >= 6);

  // Verificar IDs reservados
  const id17 = catalogo.find(s => s.idtipo_ingresos === 17);
  const id18 = catalogo.find(s => s.idtipo_ingresos === 18);
  const id19 = catalogo.find(s => s.idtipo_ingresos === 19);
  assert.ok(id17 && id17.esReservado);
  assert.ok(id18 && id18.esReservado);
  assert.ok(id19 && id19.esReservado);
  console.log('✔ Identificadores reservados (17: Plan, 18: Estacionamiento, 19: Error) verificados.');

  // Crear nuevo servicio
  const nuevoServicio = await db.crearTipoIngreso({
    nombre_servicio: 'Pulido de Ópticos & Encerado',
    precio: 25000,
    es_plan: 0
  });
  assert.strictEqual(nuevoServicio.nombre_servicio, 'Pulido de Ópticos & Encerado');
  assert.strictEqual(nuevoServicio.precio, 25000);
  console.log('✔ Nuevo servicio creado:', nuevoServicio.nombre_servicio, `(ID ${nuevoServicio.idtipo_ingresos})`);

  // Actualizar precio
  const servicioEditado = await db.actualizarTipoIngreso(nuevoServicio.idtipo_ingresos, {
    precio: 28000
  });
  assert.strictEqual(servicioEditado.precio, 28000);
  console.log('✔ Modificación de precio de servicio confirmada ($28.000).');

  // Toggle Activo
  const servicioInactivo = await db.toggleActivoTipoIngreso(nuevoServicio.idtipo_ingresos);
  assert.strictEqual(servicioInactivo.activo, 0);
  const servicioReactivado = await db.toggleActivoTipoIngreso(nuevoServicio.idtipo_ingresos);
  assert.strictEqual(servicioReactivado.activo, 1);
  console.log('✔ Toggle activar/desactivar servicio verificado.');

  // Protección de IDs reservados
  let errorReservado = false;
  try {
    await db.toggleActivoTipoIngreso(17);
  } catch (e) {
    errorReservado = true;
  }
  assert.ok(errorReservado, 'Debería fallar al intentar desactivar un ID reservado');
  console.log('✔ Blindaje de IDs reservados validado (no se pueden desactivar).');

  // --------------------------------------------------------------------------
  // TEST 3: Tarifas de Estacionamiento y Configuración Regional
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 3: Tarifas de Estacionamiento & Precios ---');
  const tarifasGuardadas = await db.guardarTarifas({
    precio_minuto: 40,
    precio_minimo: 600
  });
  assert.strictEqual(tarifasGuardadas.precio_minuto, 40);
  assert.strictEqual(tarifasGuardadas.precio_minimo, 600);

  // Verificar que la tabla precios y configuracion_regional están en sincronía
  const rowPrecios = await db.get('SELECT * FROM precios WHERE id = 1');
  const rowConfig = await db.get('SELECT precio_minuto, precio_minimo FROM configuracion_regional WHERE id = 1');
  assert.strictEqual(rowPrecios.precio_minuto, 40);
  assert.strictEqual(rowConfig.precio_minuto, 40);
  console.log('✔ Tarifas guardadas y sincronizadas en precios y configuracion_regional.');

  // --------------------------------------------------------------------------
  // TEST 4: Metas Mensuales & Resumen Ejecutivo
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 4: Metas Mensuales & Resumen Ejecutivo ---');
  const mesActual = new Date().getMonth() + 1;
  const anioActual = new Date().getFullYear();

  await db.guardarMetaMensual({
    mes: mesActual,
    anio: anioActual,
    meta_monto: 2500000
  });

  const resumenMeta = await db.getMetaMensualResumen({
    mes: mesActual,
    anio: anioActual,
    soloHabiles: false,
    sumarPlanes: true
  });

  assert.strictEqual(resumenMeta.metaMonto, 2500000);
  assert.ok(resumenMeta.totalFacturado >= 0);
  assert.ok(typeof resumenMeta.ticketPromedio === 'number');
  assert.ok(typeof resumenMeta.porcentajeAlcanzado === 'number');
  console.log(`✔ Meta económica del mes: ${resumenMeta.metaMontoFormateado}`);
  console.log(`  Facturado real: ${resumenMeta.totalFacturadoFormateado} (${resumenMeta.porcentajeReal}% alcanzado)`);
  console.log(`  Ticket Promedio: ${resumenMeta.ticketPromedioFormateado}`);

  // --------------------------------------------------------------------------
  // TEST 5: Usuarios del Sistema (Roles & Hash Seguro)
  // --------------------------------------------------------------------------
  console.log('\n--- TEST 5: Usuarios del Sistema ---');
  // 5.1 Crear Operador
  const nuevoOperador = await db.crearUsuario({
    usuario: 'operador_noche',
    password: 'PasswordSegura2026',
    rol: 'operador'
  });
  assert.strictEqual(nuevoOperador.usuario, 'operador_noche');
  assert.strictEqual(nuevoOperador.rol, 'operador');
  console.log('✔ Nuevo operador creado exitosamente:', nuevoOperador.usuario, `(${nuevoOperador.rolNombre})`);

  // 5.2 Restablecer contraseña con hash
  const passReset = await db.resetPasswordUsuario({
    id: nuevoOperador.id,
    newPassword: 'NuevaPassword456'
  });
  assert.strictEqual(passReset.success, true);
  console.log('✔ Restablecimiento de contraseña con hash seguro completado.');

  // 5.3 Proteger último administrador
  const adminUser = await db.get('SELECT id FROM usuarios WHERE rol = "admin" LIMIT 1');
  let falloBorrarAdmin = false;
  try {
    await db.eliminarUsuario(adminUser.id);
  } catch (e) {
    falloBorrarAdmin = true;
  }
  assert.ok(falloBorrarAdmin, 'Debe impedir borrar al único administrador');
  console.log('✔ Protección del último administrador del sistema confirmada.');

  // 5.4 Eliminar operador
  const delOperador = await db.eliminarUsuario(nuevoOperador.id);
  assert.strictEqual(delOperador.success, true);
  console.log('✔ Eliminación de usuario operador completada.');

  await db.closeDatabase();

  // Limpiar archivo temporal de prueba
  if (fs.existsSync(testDbPath)) {
    try { fs.unlinkSync(testDbPath); } catch (e) {}
  }

  console.log('\n============================================================');
  console.log(' TODOS LOS TESTS DEL PASO 3.4 HAN PASADO CON ÉXITO');
  console.log('============================================================');
}

runTests().catch(err => {
  console.error('\n❌ ERROR EN LOS TESTS:', err);
  process.exit(1);
});
