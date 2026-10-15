/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Módulo de Base de Datos SQLite (database.js)
 * ============================================================================
 * Arquitectura: Node.js (v20+ LTS) / Electron (v30+) / SQLite3
 * Persistencia: Directorio estándar 'userData' del sistema operativo
 * Características: WAL mode, claves foráneas, soporte multi-regional y transacciones.
 * ============================================================================
 */

const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const sqlite3 = require('sqlite3').verbose();

// ----------------------------------------------------------------------------
// 1. RESOLUCIÓN DE RUTA PERSISTENTE DE LA BASE DE DATOS
// ----------------------------------------------------------------------------
function resolveDatabasePath() {
  // 1.1 Si se define variable de entorno personalizada (útil para tests e integración continua)
  if (process.env.DB_PATH) {
    const customDir = path.dirname(process.env.DB_PATH);
    if (!fs.existsSync(customDir)) {
      fs.mkdirSync(customDir, { recursive: true });
    }
    return process.env.DB_PATH;
  }

  // 1.2 Si estamos dentro del entorno de ejecución de Electron
  try {
    const electron = require('electron');
    const app = electron.app || (electron.remote && electron.remote.app);
    if (app && typeof app.getPath === 'function') {
      const userDataDir = app.getPath('userData');
      if (!fs.existsSync(userDataDir)) {
        fs.mkdirSync(userDataDir, { recursive: true });
      }
      const targetDb = path.join(userDataDir, 'sistema_estacionamiento.sqlite');

      // Si no existe la base de datos en userData (primera ejecución o nuevo equipo), copiar la base inicial poblada
      if (!fs.existsSync(targetDb)) {
        try {
          const bundledDb = process.resourcesPath
            ? path.join(process.resourcesPath, 'initial_data', 'sistema_estacionamiento.sqlite')
            : null;
          const localSeedDb = path.join(__dirname, 'data', 'sistema_estacionamiento.sqlite');
          const legacyDb = path.join(app.getPath('appData'), 'sistema-estacionamiento', 'sistema_estacionamiento.sqlite');

          let sourceDbToCopy = null;
          if (bundledDb && fs.existsSync(bundledDb)) {
            sourceDbToCopy = bundledDb;
          } else if (fs.existsSync(localSeedDb)) {
            sourceDbToCopy = localSeedDb;
          } else if (fs.existsSync(legacyDb)) {
            sourceDbToCopy = legacyDb;
          }

          if (sourceDbToCopy) {
            // Eliminar posibles residuos WAL/SHM que corrompan el archivo nuevo
            const wal = targetDb + '-wal';
            const shm = targetDb + '-shm';
            if (fs.existsSync(wal)) fs.unlinkSync(wal);
            if (fs.existsSync(shm)) fs.unlinkSync(shm);

            fs.copyFileSync(sourceDbToCopy, targetDb);
            console.log(`[SQLite] Base de datos inicial copiada desde ${sourceDbToCopy} hacia: ${targetDb}`);
          }
        } catch (copyErr) {
          console.warn('[SQLite Advertencia] No se pudo inicializar copia de base de datos:', copyErr.message);
        }
      }

      return targetDb;
    }
  } catch (err) {
    // No estamos en el runtime directo de Electron (ejecución CLI / tests unitarios)
  }

  // 1.3 Entorno independiente Node.js (Fallback seguro en directorio local ./data)
  const fallbackDir = path.join(__dirname, 'data');
  if (!fs.existsSync(fallbackDir)) {
    fs.mkdirSync(fallbackDir, { recursive: true });
  }
  return path.join(fallbackDir, 'sistema_estacionamiento.sqlite');
}

let DB_FILE_PATH = resolveDatabasePath();

// ----------------------------------------------------------------------------
// 2. CONEXIÓN Y PROMESIFICACIÓN ASÍNCRONA DE SQLITE
// ----------------------------------------------------------------------------
let dbInstance = null;

function getDbConnection() {
  if (dbInstance) return dbInstance;

  DB_FILE_PATH = resolveDatabasePath();
  dbInstance = new sqlite3.Database(DB_FILE_PATH, (err) => {
    if (err) {
      console.error(`[SQLite Error] Error al abrir la base de datos en: ${DB_FILE_PATH}`, err.message);
    } else {
      console.log(`[SQLite Conectado] Archivo de persistencia: ${DB_FILE_PATH}`);
    }
  });

  return dbInstance;
}

// Helpers para transformar callbacks de sqlite3 en promesas modernas (async/await)
function run(sql, params = []) {
  const db = getDbConnection();
  return new Promise((resolve, reject) => {
    db.run(sql, params, function (err) {
      if (err) {
        console.error(`[SQLite run error] SQL: ${sql}`, err);
        return reject(err);
      }
      resolve({ lastID: this.lastID, changes: this.changes });
    });
  });
}

function get(sql, params = []) {
  const db = getDbConnection();
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) {
        console.error(`[SQLite get error] SQL: ${sql}`, err);
        return reject(err);
      }
      resolve(row);
    });
  });
}

function all(sql, params = []) {
  const db = getDbConnection();
  return new Promise((resolve, reject) => {
    db.all(sql, params, (err, rows) => {
      if (err) {
        console.error(`[SQLite all error] SQL: ${sql}`, err);
        return reject(err);
      }
      resolve(rows || []);
    });
  });
}

function exec(sql) {
  const db = getDbConnection();
  return new Promise((resolve, reject) => {
    db.exec(sql, (err) => {
      if (err) {
        console.error(`[SQLite exec error]`, err);
        return reject(err);
      }
      resolve();
    });
  });
}

// Ejecutor de transacciones ACID atómicas
async function withTransaction(callback) {
  await run('BEGIN IMMEDIATE TRANSACTION');
  try {
    const result = await callback({ run, get, all, exec });
    await run('COMMIT');
    return result;
  } catch (err) {
    await run('ROLLBACK');
    throw err;
  }
}

// Helper para hashing seguro de contraseñas (SHA-256 con salt)
function hashPassword(password, salt = 'los_rios_salt_2026') {
  return crypto.createHash('sha256').update(password + salt).digest('hex');
}

// ----------------------------------------------------------------------------
// 3. INICIALIZACIÓN DE ESQUEMA DE TABLAS Y RESTRICCIONES
// ----------------------------------------------------------------------------
async function initDatabase() {
  getDbConnection();

  // Optimización de concurrencia y seguridad de integridad referencial
  await exec(`
    PRAGMA foreign_keys = ON;
    PRAGMA journal_mode = WAL;
    PRAGMA busy_timeout = 5000;
  `);

  // Creación de las 8 tablas requeridas
  await exec(`
    -- 1. Tabla de Usuarios del sistema (administradores y cajeros)
    CREATE TABLE IF NOT EXISTS usuarios (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      usuario TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      rol TEXT NOT NULL CHECK(rol IN ('admin', 'cajero')),
      fecha_creacion TEXT DEFAULT (datetime('now', 'localtime'))
    );

    -- 2. Tabla de Tipos de Ingreso / Catálogo de Servicios
    CREATE TABLE IF NOT EXISTS tipo_ingreso (
      idtipo_ingresos INTEGER PRIMARY KEY,
      nombre_servicio TEXT NOT NULL,
      precio REAL NOT NULL DEFAULT 0,
      es_plan INTEGER NOT NULL DEFAULT 0, -- 1 = suscripción abonado mensual, 0 = diario
      activo INTEGER NOT NULL DEFAULT 1   -- 1 = habilitado, 0 = deshabilitado
    );

    -- 3. Tabla de Ingresos de Vehículos
    CREATE TABLE IF NOT EXISTS ingresos (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      patente TEXT NOT NULL,
      fecha_ingreso TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
      timestamp INTEGER,
      idtipo_ingreso INTEGER NOT NULL,
      salida INTEGER NOT NULL DEFAULT 0,  -- 0 = en recinto, 1 = egresado y cobrado
      FOREIGN KEY (idtipo_ingreso) REFERENCES tipo_ingreso(idtipo_ingresos)
    );

    -- 4. Tabla de Salidas y Facturación de Cobros
    CREATE TABLE IF NOT EXISTS salidas (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      id_ingresos INTEGER NOT NULL,
      fecha_salida TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
      timestamp INTEGER,
      total REAL NOT NULL DEFAULT 0,
      metodo_pago TEXT NOT NULL,          -- EFECTIVO, TUU, TARJETA, TRANSFERENCIA
      tipo_pago TEXT NOT NULL DEFAULT 'manual', -- manual, tuu
      tiempo_estadia TEXT,                -- Tiempo transcurrido legible (ej: '45 min')
      monto_recibido REAL DEFAULT 0,
      vuelto REAL DEFAULT 0,
      auth_code TEXT,
      FOREIGN KEY (id_ingresos) REFERENCES ingresos(id)
    );

    -- 5. Tabla de Clientes Abonados Mensuales
    CREATE TABLE IF NOT EXISTS clientes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      patente TEXT NOT NULL UNIQUE,
      nombres TEXT NOT NULL,
      telefono TEXT,
      dia_pago INTEGER NOT NULL DEFAULT 5,
      fecha_vencimiento TEXT NOT NULL,
      monto_plan REAL NOT NULL DEFAULT 0
    );

    -- 6. Tabla de Configuración Regional (Chile / España)
    CREATE TABLE IF NOT EXISTS configuracion_regional (
      id INTEGER PRIMARY KEY CHECK (id = 1),
      moneda_simbolo TEXT NOT NULL DEFAULT '$',
      moneda_codigo TEXT NOT NULL DEFAULT 'CLP',
      decimales INTEGER NOT NULL DEFAULT 0,
      activar_redondeo_chile INTEGER NOT NULL DEFAULT 1, -- 1 = Múltiplo de 10 en efectivo, 0 = Desactivado
      precio_minuto REAL NOT NULL DEFAULT 35,
      precio_minimo REAL NOT NULL DEFAULT 500
    );

    -- 7. Tabla de Metas Mensuales
    CREATE TABLE IF NOT EXISTS metas_mensuales (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      mes INTEGER NOT NULL CHECK(mes BETWEEN 1 AND 12),
      anio INTEGER NOT NULL,
      meta_monto REAL NOT NULL DEFAULT 0,
      UNIQUE(mes, anio)
    );

    -- 8. Tabla de Servicios de Lavados Pendientes con Adicionales
    CREATE TABLE IF NOT EXISTS lavados_pendientes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      id_ingreso INTEGER NOT NULL,
      patente TEXT NOT NULL,
      motivos_extra TEXT,                 -- Array JSON o descripción de extras (ej: tapiz, barro)
      precio_extra REAL NOT NULL DEFAULT 0,
      FOREIGN KEY (id_ingreso) REFERENCES ingresos(id) ON DELETE CASCADE
    );

    -- Índices para optimizar búsquedas operativas frecuentes
    CREATE INDEX IF NOT EXISTS idx_ingresos_salida ON ingresos(salida);
    CREATE INDEX IF NOT EXISTS idx_ingresos_patente ON ingresos(patente);
    CREATE INDEX IF NOT EXISTS idx_clientes_patente ON clientes(patente);
  `);

  // Migraciones no destructivas verificando previamente las columnas existentes
  try {
    const colsIngresos = await all('PRAGMA table_info(ingresos)');
    if (!colsIngresos.some(c => c.name === 'timestamp')) {
      await run('ALTER TABLE ingresos ADD COLUMN timestamp INTEGER');
    }

    const colsSalidas = await all('PRAGMA table_info(salidas)');
    if (!colsSalidas.some(c => c.name === 'timestamp')) {
      await run('ALTER TABLE salidas ADD COLUMN timestamp INTEGER');
    }
    if (!colsSalidas.some(c => c.name === 'monto_recibido')) {
      await run('ALTER TABLE salidas ADD COLUMN monto_recibido REAL DEFAULT 0');
    }
    if (!colsSalidas.some(c => c.name === 'vuelto')) {
      await run('ALTER TABLE salidas ADD COLUMN vuelto REAL DEFAULT 0');
    }
    if (!colsSalidas.some(c => c.name === 'auth_code')) {
      await run('ALTER TABLE salidas ADD COLUMN auth_code TEXT');
    }

    // Migraciones seguras para tabla lavados_pendientes (Fase 3.2)
    const colsLavados = await all('PRAGMA table_info(lavados_pendientes)');
    if (!colsLavados.some(c => c.name === 'estado')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN estado TEXT DEFAULT 'espera'");
    }
    if (!colsLavados.some(c => c.name === 'observaciones')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN observaciones TEXT");
    }
    if (!colsLavados.some(c => c.name === 'vinculado_estadia')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN vinculado_estadia INTEGER DEFAULT 0");
    }
    if (!colsLavados.some(c => c.name === 'id_servicio_lavado')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN id_servicio_lavado INTEGER");
    }
    if (!colsLavados.some(c => c.name === 'servicio_nombre')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN servicio_nombre TEXT");
    }
    if (!colsLavados.some(c => c.name === 'precio_base')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN precio_base REAL DEFAULT 0");
    }
    if (!colsLavados.some(c => c.name === 'timestamp_ingreso')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN timestamp_ingreso INTEGER");
    }
    if (!colsLavados.some(c => c.name === 'timestamp_proceso')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN timestamp_proceso INTEGER");
    }
    if (!colsLavados.some(c => c.name === 'timestamp_listo')) {
      await run("ALTER TABLE lavados_pendientes ADD COLUMN timestamp_listo INTEGER");
    }

    // 1. Columnas vehiculo, rut y apellidos en clientes
    const colsClientes = await all('PRAGMA table_info(clientes)');
    if (!colsClientes.some(c => c.name === 'vehiculo')) {
      await run("ALTER TABLE clientes ADD COLUMN vehiculo TEXT DEFAULT ''");
    }
    if (!colsClientes.some(c => c.name === 'rut')) {
      await run("ALTER TABLE clientes ADD COLUMN rut TEXT DEFAULT ''");
    }
    if (!colsClientes.some(c => c.name === 'apellidos')) {
      await run("ALTER TABLE clientes ADD COLUMN apellidos TEXT DEFAULT ''");
    }

    // 2. Tabla de Precios de Estacionamiento (compatibilidad estricta con esquema legado y Fase 3.4)
    await exec(`
      CREATE TABLE IF NOT EXISTS precios (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        precio_minuto REAL NOT NULL DEFAULT 35,
        precio_minimo REAL NOT NULL DEFAULT 500,
        fecha_actualizacion TEXT DEFAULT (datetime('now', 'localtime'))
      );
    `);
    const rowPrecio = await get('SELECT id FROM precios WHERE id = 1');
    if (!rowPrecio) {
      const cfgActual = await get('SELECT precio_minuto, precio_minimo FROM configuracion_regional WHERE id = 1');
      await run(`
        INSERT INTO precios (id, precio_minuto, precio_minimo)
        VALUES (1, ?, ?)
      `, [cfgActual ? cfgActual.precio_minuto : 35, cfgActual ? cfgActual.precio_minimo : 500]);
    }

    // 3. Migración de usuarios para admitir rol 'operador' además de 'admin' y 'cajero'
    const sqlUsuarios = await get("SELECT sql FROM sqlite_master WHERE type='table' AND name='usuarios'");
    if (sqlUsuarios && !sqlUsuarios.sql.includes("'operador'")) {
      await exec(`
        CREATE TABLE usuarios_new (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          usuario TEXT NOT NULL UNIQUE,
          password_hash TEXT NOT NULL,
          rol TEXT NOT NULL CHECK(rol IN ('admin', 'cajero', 'operador')),
          fecha_creacion TEXT DEFAULT (datetime('now', 'localtime'))
        );
        INSERT INTO usuarios_new (id, usuario, password_hash, rol, fecha_creacion)
        SELECT id, usuario, password_hash, CASE WHEN lower(rol) IN ('admin', 'cajero', 'operador') THEN lower(rol) ELSE 'cajero' END, fecha_creacion FROM usuarios;
        DROP TABLE usuarios;
        ALTER TABLE usuarios_new RENAME TO usuarios;
      `);
    }
  } catch (e) {
    console.warn('[SQLite Migración advertencia]', e.message);
  }

  // Backfill seguro para registros antiguos sin timestamp
  try {
    await run(`UPDATE ingresos SET timestamp = CAST(strftime('%s', fecha_ingreso) AS INTEGER) * 1000 WHERE timestamp IS NULL`);
    await run(`UPDATE salidas SET timestamp = CAST(strftime('%s', fecha_salida) AS INTEGER) * 1000 WHERE timestamp IS NULL`);
  } catch (e) {}

  // Sembrar registros reservados iniciales
  await seedReservedServices();
  await initRegionalConfig();
  await initDefaultAdmin();

  return { success: true, dbPath: DB_FILE_PATH };
}

// ----------------------------------------------------------------------------
// 4. SERVICIOS RESERVADOS Y CONFIGURACIÓN REGIONAL
// ----------------------------------------------------------------------------
const PRESETS_REGIONALES = {
  chile: {
    moneda_simbolo: '$',
    moneda_codigo: 'CLP',
    decimales: 0,
    activar_redondeo_chile: 1,
    precio_minuto: 35,
    precio_minimo: 500
  },
  espana: {
    moneda_simbolo: '€',
    moneda_codigo: 'EUR',
    decimales: 2,
    activar_redondeo_chile: 0,
    precio_minuto: 0.05,
    precio_minimo: 1.50
  }
};

async function seedReservedServices() {
  // IDs Reservados según requerimiento estricto:
  // ID 17: Plan Mensual (precio 0, es_plan 1, activo 1)
  // ID 18: Estacionamiento por minuto (precio 0 dinámico, es_plan 0, activo 1)
  // ID 19: Error de ingreso (precio 1 simbólico, es_plan 0, activo 1)
  const servicios = [
    { id: 17, nombre: 'Plan Mensual', precio: 0, es_plan: 1, activo: 1 },
    { id: 18, nombre: 'Estacionamiento por minuto', precio: 0, es_plan: 0, activo: 1 },
    { id: 19, nombre: 'Error de ingreso', precio: 1, es_plan: 0, activo: 1 },
    // Catálogo inicial de servicios de lavado para demostración de portafolio
    { id: 1, nombre: 'Lavado Simple Exterior', precio: 7000, es_plan: 0, activo: 1 },
    { id: 2, nombre: 'Lavado Completo (Int/Ext)', precio: 12000, es_plan: 0, activo: 1 },
    { id: 3, nombre: 'Lavado Full SUV / Camioneta', precio: 16000, es_plan: 0, activo: 1 }
  ];

  for (const s of servicios) {
    await run(`
      INSERT INTO tipo_ingreso (idtipo_ingresos, nombre_servicio, precio, es_plan, activo)
      VALUES (?, ?, ?, ?, ?)
      ON CONFLICT(idtipo_ingresos) DO UPDATE SET
        nombre_servicio = excluded.nombre_servicio,
        es_plan = excluded.es_plan,
        activo = excluded.activo
    `, [s.id, s.nombre, s.precio, s.es_plan, s.activo]);
  }
}

async function initRegionalConfig() {
  const existing = await get('SELECT * FROM configuracion_regional WHERE id = 1');
  if (!existing) {
    const clp = PRESETS_REGIONALES.chile;
    await run(`
      INSERT INTO configuracion_regional 
      (id, moneda_simbolo, moneda_codigo, decimales, activar_redondeo_chile, precio_minuto, precio_minimo)
      VALUES (1, ?, ?, ?, ?, ?, ?)
    `, [clp.moneda_simbolo, clp.moneda_codigo, clp.decimales, clp.activar_redondeo_chile, clp.precio_minuto, clp.precio_minimo]);
  }
}

async function initDefaultAdmin() {
  const existingAdmin = await get('SELECT id FROM usuarios WHERE usuario = ?', ['admin']);
  if (!existingAdmin) {
    const defaultHash = hashPassword('admin123');
    await run(`
      INSERT INTO usuarios (usuario, password_hash, rol)
      VALUES (?, ?, ?)
    `, ['admin', defaultHash, 'admin']);
  }
}

/**
 * Cambia el perfil regional activo (Chile o España) con un solo clic.
 * @param {'chile' | 'espana' | Object} presetNameOrConfig
 */
async function setPerfilRegional(presetNameOrConfig) {
  let cfg;
  if (typeof presetNameOrConfig === 'string') {
    const key = presetNameOrConfig.toLowerCase();
    cfg = PRESETS_REGIONALES[key];
    if (!cfg) throw new Error(`Perfil regional '${presetNameOrConfig}' no reconocido. Opciones: 'chile', 'espana'.`);
  } else if (typeof presetNameOrConfig === 'object') {
    cfg = presetNameOrConfig;
  } else {
    throw new Error('Configuración regional inválida');
  }

  await run(`
    UPDATE configuracion_regional
    SET moneda_simbolo = ?,
        moneda_codigo = ?,
        decimales = ?,
        activar_redondeo_chile = ?,
        precio_minuto = ?,
        precio_minimo = ?
    WHERE id = 1
  `, [
    cfg.moneda_simbolo,
    cfg.moneda_codigo,
    cfg.decimales,
    cfg.activar_redondeo_chile,
    cfg.precio_minuto,
    cfg.precio_minimo
  ]);

  return await getConfiguracionRegional();
}

async function getConfiguracionRegional() {
  return await get('SELECT * FROM configuracion_regional WHERE id = 1');
}

// ----------------------------------------------------------------------------
// 5. REGLAS FINANCIERAS Y REDONDEO LEY CHILENA (LEY 20.956)
// ----------------------------------------------------------------------------
/**
 * Aplica el redondeo legal de la República de Chile para pagos en efectivo:
 * Las sumas terminadas entre $1 y $5 se redondean hacia abajo a la decena ($0).
 * Las sumas terminadas entre $6 y $9 se redondean hacia arriba a la decena ($10).
 * Para medios electrónicos (TUU, Tarjeta) o perfiles internacionales (España),
 * se respeta el número de decimales configurado sin truncamiento de decenas.
 */
function aplicarReglaRedondeo(montoBruto, configRegional, metodoPago = 'EFECTIVO') {
  if (configRegional.activar_redondeo_chile === 1 && metodoPago.toUpperCase() === 'EFECTIVO') {
    const entero = Math.round(montoBruto);
    const resto = entero % 10;
    if (resto >= 1 && resto <= 5) {
      return entero - resto;
    } else if (resto >= 6) {
      return entero + (10 - resto);
    }
    return entero;
  }

  const factor = Math.pow(10, configRegional.decimales);
  return Math.round(montoBruto * factor) / factor;
}

// Formateador visual con símbolo y decimales
function formatearMoneda(monto, configRegional) {
  const formatted = Number(monto).toLocaleString('es-CL', {
    minimumFractionDigits: configRegional.decimales,
    maximumFractionDigits: configRegional.decimales
  });
  return `${configRegional.moneda_simbolo} ${formatted}`;
}

// ----------------------------------------------------------------------------
// 6. FUNCIÓN DE SEMILLA / DATOS DEMO PARA PORTAFOLIO
// ----------------------------------------------------------------------------
async function seedDemoData() {
  const config = await getConfiguracionRegional();

  return await withTransaction(async (tx) => {
    // 6.1 Asegurar clientes abonados de prueba (2 clientes)
    const clientesDemo = [
      {
        patente: 'CFGH12',
        nombres: 'Juan Carlos Pérez Miranda',
        telefono: '+56 9 8765 4321',
        dia_pago: 5,
        fecha_vencimiento: '2026-11-05',
        monto_plan: config.moneda_codigo === 'EUR' ? 45.00 : 45000
      },
      {
        patente: 'LJRP90',
        nombres: 'María Ignacia Soto Valenzuela',
        telefono: '+56 9 9123 4567',
        dia_pago: 15,
        fecha_vencimiento: '2026-10-25',
        monto_plan: config.moneda_codigo === 'EUR' ? 50.00 : 50000
      }
    ];

    for (const c of clientesDemo) {
      await tx.run(`
        INSERT INTO clientes (patente, nombres, telefono, dia_pago, fecha_vencimiento, monto_plan)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT(patente) DO UPDATE SET
          nombres = excluded.nombres,
          telefono = excluded.telefono,
          dia_pago = excluded.dia_pago,
          fecha_vencimiento = excluded.fecha_vencimiento,
          monto_plan = excluded.monto_plan
      `, [c.patente, c.nombres, c.telefono, c.dia_pago, c.fecha_vencimiento, c.monto_plan]);
    }

    // 6.2 Limpiar ingresos demo previos que no hayan sido cerrados para evitar duplicación
    await tx.run(`
      DELETE FROM lavados_pendientes WHERE patente IN ('AA1001', 'BB2002', 'CFGH12', 'LAVA88', 'KLRX45')
    `);
    await tx.run(`
      DELETE FROM ingresos WHERE patente IN ('AA1001', 'BB2002', 'CFGH12', 'LAVA88', 'KLRX45') AND salida = 0
    `);

    // 6.3 Insertar Vehículos Estacionados activos (salida = 0) con timestamps exactos en ms
    const ahoraMs = Date.now();

    // Auto 1: Estacionamiento por minuto (entró hace 55 min) - Se le vinculará un lavado terminado
    const tsAuto1 = ahoraMs - (55 * 60 * 1000);
    const resAuto1 = await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES ('AA1001', datetime('now', 'localtime', '-55 minutes'), ?, 18, 0)
    `, [tsAuto1]);

    // Auto 2: Estacionamiento por minuto (entró hace 130 min)
    const tsAuto2 = ahoraMs - (130 * 60 * 1000);
    await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES ('BB2002', datetime('now', 'localtime', '-130 minutes'), ?, 18, 0)
    `, [tsAuto2]);

    // Auto 3: Cliente con Plan Mensual (patente CFGH12, entró hace 30 min)
    const tsAuto3 = ahoraMs - (30 * 60 * 1000);
    await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES ('CFGH12', datetime('now', 'localtime', '-30 minutes'), ?, 17, 0)
    `, [tsAuto3]);

    // 6.4 Insertar Vehículos en Bahías de Lavado (Demostración de los 3 Estados)

    // BAHÍA 1: "En Espera" - Auto KLRX45 (Lavado Simple Exterior, ID 1)
    const tsAutoEspera = ahoraMs - (12 * 60 * 1000);
    const resAutoEspera = await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES ('KLRX45', datetime('now', 'localtime', '-12 minutes'), ?, 1, 0)
    `, [tsAutoEspera]);

    const extraEspera = config.moneda_codigo === 'EUR' ? 3.00 : 3000;
    const tagsEspera = JSON.stringify(['Barro excesivo', 'Insectos adheridos']);
    await tx.run(`
      INSERT INTO lavados_pendientes (
        id_ingreso, patente, motivos_extra, precio_extra, estado, 
        observaciones, vinculado_estadia, id_servicio_lavado, 
        servicio_nombre, precio_base, timestamp_ingreso
      ) VALUES (?, 'KLRX45', ?, ?, 'espera', 'Cliente solicita especial atención en llantas y parabrisas.', 0, 1, 'Lavado Simple Exterior', ?, ?)
    `, [resAutoEspera.lastID, tagsEspera, extraEspera, config.moneda_codigo === 'EUR' ? 7.00 : 7000, tsAutoEspera]);

    // BAHÍA 2: "En Proceso" - Auto LAVA88 (Lavado Completo Int/Ext, ID 2)
    const tsAutoProceso = ahoraMs - (35 * 60 * 1000);
    const tsInicioProceso = ahoraMs - (18 * 60 * 1000);
    const resAutoProceso = await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
      VALUES ('LAVA88', datetime('now', 'localtime', '-35 minutes'), ?, 2, 0)
    `, [tsAutoProceso]);

    const extraProceso = config.moneda_codigo === 'EUR' ? 5.00 : 5000;
    const tagsProceso = JSON.stringify(['Aspirado de tapiz profundo', 'Pelos de mascota']);
    await tx.run(`
      INSERT INTO lavados_pendientes (
        id_ingreso, patente, motivos_extra, precio_extra, estado, 
        observaciones, vinculado_estadia, id_servicio_lavado, 
        servicio_nombre, precio_base, timestamp_ingreso, timestamp_proceso
      ) VALUES (?, 'LAVA88', ?, ?, 'proceso', 'Detalle de pelos de mascota en plazas traseras. Sin rayones previos.', 0, 2, 'Lavado Completo (Int/Ext)', ?, ?, ?)
    `, [resAutoProceso.lastID, tagsProceso, extraProceso, config.moneda_codigo === 'EUR' ? 12.00 : 12000, tsAutoProceso, tsInicioProceso]);

    // BAHÍA 3: "Finalizado / Por Entregar" - Auto AA1001 (Vinculado a estadía de estacionamiento!)
    const tsInicioListo = ahoraMs - (40 * 60 * 1000);
    const tsFinListo = ahoraMs - (8 * 60 * 1000);
    const tagsListo = JSON.stringify(['Daño en pintura']);
    await tx.run(`
      INSERT INTO lavados_pendientes (
        id_ingreso, patente, motivos_extra, precio_extra, estado, 
        observaciones, vinculado_estadia, id_servicio_lavado, 
        servicio_nombre, precio_base, timestamp_ingreso, timestamp_proceso, timestamp_listo
      ) VALUES (?, 'AA1001', ?, 0, 'listo', 'Abolladura y raspón preexistente en puerta izquierda registrado.', 1, 3, 'Lavado Full SUV / Camioneta', ?, ?, ?, ?)
    `, [resAuto1.lastID, tagsListo, config.moneda_codigo === 'EUR' ? 16.00 : 16000, tsAuto1, tsInicioListo, tsFinListo]);

    // 6.5 Insertar Meta Mensual de Demostración para el mes actual
    const fechaActual = new Date();
    const mes = fechaActual.getMonth() + 1;
    const anio = fechaActual.getFullYear();
    const metaMonto = config.moneda_codigo === 'EUR' ? 1800.00 : 1850000;

    await tx.run(`
      INSERT INTO metas_mensuales (mes, anio, meta_monto)
      VALUES (?, ?, ?)
      ON CONFLICT(mes, anio) DO UPDATE SET meta_monto = excluded.meta_monto
    `, [mes, anio, metaMonto]);

    // 6.6 Insertar Operaciones Históricas de Demostración (para reportes y filtros por fechas)
    const totalSalidas = await tx.get('SELECT COUNT(*) as count FROM salidas');
    if (totalSalidas.count < 5) {
      const demoHistorical = [
        {
          patente: 'HXPR22',
          idTipo: 18, // Estacionamiento por minuto
          diasAtras: 0,
          horasAtras: 3,
          minutosEstadia: 75,
          total: config.moneda_codigo === 'EUR' ? 3.50 : 2650,
          metodo: 'EFECTIVO'
        },
        {
          patente: 'BDJW90',
          idTipo: 1, // Lavado Simple Exterior
          diasAtras: 1,
          horasAtras: 26,
          minutosEstadia: 45,
          total: config.moneda_codigo === 'EUR' ? 7.00 : 7000,
          metodo: 'TARJETA'
        },
        {
          patente: 'CPTR55',
          idTipo: 18, // Estacionamiento por minuto
          diasAtras: 2,
          horasAtras: 52,
          minutosEstadia: 120,
          total: config.moneda_codigo === 'EUR' ? 4.20 : 4200,
          metodo: 'TRANSFERENCIA'
        },
        {
          patente: 'GFKT14',
          idTipo: 2, // Lavado Completo (Int/Ext)
          diasAtras: 3,
          horasAtras: 74,
          minutosEstadia: 80,
          total: config.moneda_codigo === 'EUR' ? 12.00 : 12000,
          metodo: 'TARJETA'
        },
        {
          patente: 'MKLP88',
          idTipo: 3, // Lavado Full SUV
          diasAtras: 5,
          horasAtras: 122,
          minutosEstadia: 95,
          total: config.moneda_codigo === 'EUR' ? 16.00 : 16000,
          metodo: 'EFECTIVO'
        }
      ];

      for (const item of demoHistorical) {
        const tsIngreso = ahoraMs - ((item.horasAtras * 60 + item.minutosEstadia) * 60 * 1000);
        const tsSalida = ahoraMs - (item.horasAtras * 60 * 1000);
        const fechaIngresoStr = new Date(tsIngreso).toISOString();
        const fechaSalidaStr = new Date(tsSalida).toISOString();

        const insIng = await tx.run(`
          INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
          VALUES (?, ?, ?, ?, 1)
        `, [item.patente, fechaIngresoStr, tsIngreso, item.idTipo]);

        await tx.run(`
          INSERT INTO salidas (
            id_ingresos, fecha_salida, timestamp, total, metodo_pago, tipo_pago,
            tiempo_estadia, monto_recibido, vuelto
          ) VALUES (?, ?, ?, ?, ?, 'manual', ?, ?, 0)
        `, [
          insIng.lastID,
          fechaSalidaStr,
          tsSalida,
          item.total,
          item.metodo,
          `${item.minutosEstadia} min`,
          item.total
        ]);
      }
    }

    return {
      success: true,
      mensaje: 'Datos demo sembrados con éxito (3 vehículos estacionados, 1 lavado pendiente y 2 clientes abonados).'
    };
  });
}

// ----------------------------------------------------------------------------
// 7. OPERACIONES DE NEGOCIO (INGRESOS, SALIDAS Y CÁLCULOS)
// ----------------------------------------------------------------------------

/**
 * Consulta el resumen de estado en tiempo real para el Dashboard
 */
async function getResumenDashboard() {
  const config = await getConfiguracionRegional();

  const totalEstacionados = await get(`
    SELECT COUNT(*) as total FROM ingresos WHERE salida = 0
  `);

  const totalLavadosPendientes = await get(`
    SELECT COUNT(*) as total FROM lavados_pendientes
  `);

  const totalClientes = await get(`
    SELECT COUNT(*) as total FROM clientes
  `);

  const recaudacionHoy = await get(`
    SELECT COALESCE(SUM(total), 0) as total 
    FROM salidas 
    WHERE date(fecha_salida) = date('now', 'localtime')
  `);

  return {
    autosEstacionados: totalEstacionados.total,
    lavadosPendientes: totalLavadosPendientes.total,
    clientesAbonados: totalClientes.total,
    recaudadoHoy: recaudacionHoy.total,
    recaudadoHoyFormateado: formatearMoneda(recaudacionHoy.total, config),
    config
  };
}

/**
 * Obtiene la lista detallada de vehículos actualmente en el estacionamiento (salida = 0)
 */
async function getIngresosActivos() {
  const config = await getConfiguracionRegional();

  const filas = await all(`
    SELECT 
      i.id,
      i.patente,
      i.fecha_ingreso,
      i.timestamp,
      i.idtipo_ingreso,
      t.nombre_servicio,
      t.precio as precio_base_servicio,
      t.es_plan,
      lp.id as id_lavado_pendiente,
      lp.motivos_extra,
      lp.precio_extra
    FROM ingresos i
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    LEFT JOIN lavados_pendientes lp ON lp.id_ingreso = i.id
    WHERE i.salida = 0
    ORDER BY i.id DESC
  `);

  const ahoraMs = Date.now();

  return filas.map((item) => {
    const ts = Number(item.timestamp) || new Date(item.fecha_ingreso).getTime();
    const minutos = Math.max(1, Math.ceil((ahoraMs - ts) / (1000 * 60)));
    let totalEstimado = 0;

    if (item.idtipo_ingreso === 17 || item.es_plan === 1) {
      // Plan mensual: $0
      totalEstimado = 0;
    } else if (item.idtipo_ingreso === 18) {
      // Estacionamiento por minuto
      const base = minutos * config.precio_minuto;
      totalEstimado = Math.max(base, config.precio_minimo);
    } else if (item.idtipo_ingreso === 19) {
      // Error de ingreso simbólico (0.01 si EUR o 1 si CLP)
      totalEstimado = config.moneda_codigo === 'EUR' ? 0.01 : 1;
    } else {
      // Servicio de lavado
      totalEstimado = (item.precio_base_servicio || 0) + (item.precio_extra || 0);
    }

    const totalRedondeado = item.idtipo_ingreso === 19 
      ? totalEstimado 
      : (item.idtipo_ingreso === 17 || item.es_plan === 1 ? 0 : aplicarReglaRedondeo(totalEstimado, config, 'EFECTIVO'));

    // Visualización en zona horaria local del sistema operativo
    const fechaObj = new Date(ts);
    const horaLocal = isNaN(fechaObj.getTime()) ? '' : fechaObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const fechaLocal = isNaN(fechaObj.getTime()) ? '' : fechaObj.toLocaleDateString();

    return {
      ...item,
      minutos,
      horaLocal,
      fechaLocal,
      totalEstimado: totalRedondeado,
      totalEstimadoFormateado: formatearMoneda(totalRedondeado, config)
    };
  });
}

/**
 * Registra un nuevo ingreso de vehículo en el estacionamiento.
 */
async function registrarIngreso({ patente, idtipo_ingreso, motivos_extra, precio_extra }) {
  if (!patente || patente.trim().length === 0) {
    throw new Error('La patente es requerida');
  }

  const patenteNormalizada = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

  // Validar si la patente ya se encuentra dentro del recinto
  const existente = await get(`
    SELECT id FROM ingresos WHERE patente = ? AND salida = 0
  `, [patenteNormalizada]);

  if (existente) {
    throw new Error(`El vehículo con patente ${patenteNormalizada} ya tiene un ingreso activo registrado (ID: ${existente.id}).`);
  }

  // Verificar si la patente pertenece a un Cliente Abonado con Plan Mensual vigente
  let tipoFinal = idtipo_ingreso ? parseInt(idtipo_ingreso, 10) : 18;
  const clienteAbonado = await get(`
    SELECT id, fecha_vencimiento FROM clientes WHERE patente = ?
  `, [patenteNormalizada]);

  if (clienteAbonado && !idtipo_ingreso) {
    tipoFinal = 17; // Se asigna automáticamente Plan Mensual si no se especificó otro servicio
  }

  return await withTransaction(async (tx) => {
    const res = await tx.run(`
      INSERT INTO ingresos (patente, fecha_ingreso, idtipo_ingreso, salida)
      VALUES (?, datetime('now', 'localtime'), ?, 0)
    `, [patenteNormalizada, tipoFinal]);

    const idIngreso = res.lastID;

    // Si es un servicio con adicionales de lavado, registrar en lavados_pendientes
    if (precio_extra && parseFloat(precio_extra) > 0) {
      const extraJson = typeof motivos_extra === 'string' ? motivos_extra : JSON.stringify(motivos_extra || []);
      await tx.run(`
        INSERT INTO lavados_pendientes (id_ingreso, patente, motivos_extra, precio_extra)
        VALUES (?, ?, ?, ?)
      `, [idIngreso, patenteNormalizada, extraJson, parseFloat(precio_extra)]);
    }

    return {
      success: true,
      idIngreso,
      patente: patenteNormalizada,
      idtipo_ingreso: tipoFinal
    };
  });
}

/**
 * Calcula con precisión contable el valor a pagar por un vehículo.
 */
async function calcularCobro(idIngreso, metodoPago = 'EFECTIVO') {
  const config = await getConfiguracionRegional();

  const ingreso = await get(`
    SELECT 
      i.id,
      i.patente,
      i.fecha_ingreso,
      i.idtipo_ingreso,
      t.nombre_servicio,
      t.precio as precio_base_servicio,
      t.es_plan,
      lp.id as id_lavado_pendiente,
      lp.motivos_extra,
      lp.precio_extra,
      CAST((strftime('%s', 'now', 'localtime') - strftime('%s', i.fecha_ingreso)) / 60 AS INTEGER) as minutos_transcurridos
    FROM ingresos i
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    LEFT JOIN lavados_pendientes lp ON lp.id_ingreso = i.id
    WHERE i.id = ?
  `, [idIngreso]);

  if (!ingreso) {
    throw new Error(`Ingreso con ID ${idIngreso} no encontrado.`);
  }

  const minutos = Math.max(1, ingreso.minutos_transcurridos || 1);
  let totalCalculado = 0;
  let desglose = '';

  if (ingreso.idtipo_ingreso === 17 || ingreso.es_plan === 1) {
    // 1. Plan mensual: $0
    totalCalculado = 0;
    desglose = 'Abonado Mensual (Exento de cobro por tiempo)';
  } else if (ingreso.idtipo_ingreso === 18) {
    // 2. Estacionamiento por minuto
    const subtotal = minutos * config.precio_minuto;
    if (subtotal < config.precio_minimo) {
      totalCalculado = config.precio_minimo;
      desglose = `Tarifa Mínima Aplicada (${formatearMoneda(config.precio_minimo, config)}) por ${minutos} min`;
    } else {
      totalCalculado = subtotal;
      desglose = `${minutos} min x ${formatearMoneda(config.precio_minuto, config)}/min`;
    }
  } else if (ingreso.idtipo_ingreso === 19) {
    // 3. Error de ingreso
    totalCalculado = 1;
    desglose = 'Anulación administrativa por error de ingreso ($1)';
  } else {
    // 4. Lavado u otro servicio
    const base = ingreso.precio_base_servicio || 0;
    const extra = ingreso.precio_extra || 0;
    totalCalculado = base + extra;
    desglose = `${ingreso.nombre_servicio} (${formatearMoneda(base, config)}) + Extras (${formatearMoneda(extra, config)})`;
  }

  // Redondeo legal chileno o precisión según método de pago
  const totalFinal = aplicarReglaRedondeo(totalCalculado, config, metodoPago);

  return {
    idIngreso: ingreso.id,
    patente: ingreso.patente,
    fecha_ingreso: ingreso.fecha_ingreso,
    minutos,
    tiempoLegible: `${minutos} min`,
    servicio: ingreso.nombre_servicio,
    idtipo_ingreso: ingreso.idtipo_ingreso,
    metodoPago,
    totalBruto: totalCalculado,
    totalFinal,
    totalFinalFormateado: formatearMoneda(totalFinal, config),
    desglose,
    config
  };
}

/**
 * Registra la salida definitiva del vehículo, procesa el cobro en una transacción ACID,
 * actualiza el estado en ingresos y limpia los lavados pendientes.
 */
async function registrarSalida({ id_ingreso, metodo_pago = 'EFECTIVO', tipo_pago = 'manual' }) {
  const calculo = await calcularCobro(id_ingreso, metodo_pago);

  return await withTransaction(async (tx) => {
    // 1. Registrar el comprobante de salida
    const resSalida = await tx.run(`
      INSERT INTO salidas (id_ingresos, fecha_salida, total, metodo_pago, tipo_pago, tiempo_estadia)
      VALUES (?, datetime('now', 'localtime'), ?, ?, ?, ?)
    `, [id_ingreso, calculo.totalFinal, metodo_pago.toUpperCase(), tipo_pago, calculo.tiempoLegible]);

    // 2. Marcar el vehículo como egresado (salida = 1)
    await tx.run(`
      UPDATE ingresos SET salida = 1 WHERE id = ?
    `, [id_ingreso]);

    // 3. Remover de la cola de lavados pendientes si correspondía
    await tx.run(`
      DELETE FROM lavados_pendientes WHERE id_ingreso = ?
    `, [id_ingreso]);

    return {
      success: true,
      idSalida: resSalida.lastID,
      patente: calculo.patente,
      total: calculo.totalFinal,
      totalFormateado: calculo.totalFinalFormateado,
      metodo_pago: metodo_pago.toUpperCase(),
      tipo_pago,
      tiempo_estadia: calculo.tiempoLegible
    };
  });
}

/**
 * Consultas auxiliares para la interfaz
 */
async function getTiposIngreso() {
  return await all('SELECT * FROM tipo_ingreso WHERE activo = 1 ORDER BY idtipo_ingresos ASC');
}

async function getClientes() {
  return await all('SELECT * FROM clientes ORDER BY nombres ASC');
}

/**
 * Obtiene la lista completa de vehículos en cola y bahías de lavado
 */
async function getLavadosPendientes() {
  const config = await getConfiguracionRegional();
  const rows = await all(`
    SELECT 
      lp.id as id_lavado,
      lp.id_ingreso,
      lp.patente,
      lp.motivos_extra,
      COALESCE(lp.precio_extra, 0) as precio_extra,
      COALESCE(lp.estado, 'espera') as estado,
      COALESCE(lp.observaciones, '') as observaciones,
      COALESCE(lp.vinculado_estadia, 0) as vinculado_estadia,
      lp.id_servicio_lavado,
      COALESCE(lp.servicio_nombre, t.nombre_servicio) as nombre_servicio,
      COALESCE(lp.precio_base, t.precio, 0) as precio_base_servicio,
      COALESCE(lp.timestamp_ingreso, i.timestamp) as timestamp_ingreso,
      lp.timestamp_proceso,
      lp.timestamp_listo,
      i.fecha_ingreso,
      i.timestamp as timestamp_vehiculo,
      i.idtipo_ingreso as idtipo_ingreso_vehiculo,
      t_veh.nombre_servicio as servicio_vehiculo_principal
    FROM lavados_pendientes lp
    JOIN ingresos i ON lp.id_ingreso = i.id
    LEFT JOIN tipo_ingreso t ON COALESCE(lp.id_servicio_lavado, i.idtipo_ingreso) = t.idtipo_ingresos
    LEFT JOIN tipo_ingreso t_veh ON i.idtipo_ingreso = t_veh.idtipo_ingresos
    WHERE i.salida = 0
    ORDER BY lp.id DESC
  `);

  return rows.map((item) => {
    let parsedMotivos = [];
    if (item.motivos_extra) {
      try {
        parsedMotivos = typeof item.motivos_extra === 'string' ? JSON.parse(item.motivos_extra) : item.motivos_extra;
      } catch (e) {
        parsedMotivos = [item.motivos_extra];
      }
    }
    const precioBase = Number(item.precio_base_servicio) || 0;
    const precioExtra = Number(item.precio_extra) || 0;
    const totalEstimado = precioBase + precioExtra;

    const tsIngreso = Number(item.timestamp_ingreso) || Date.now();
    const fechaObj = new Date(tsIngreso);
    const horaIngresoLocal = isNaN(fechaObj.getTime()) ? '' : fechaObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    return {
      ...item,
      id: item.id_ingreso, // Compatible con CheckoutModal
      motivos_extra_list: Array.isArray(parsedMotivos) ? parsedMotivos : [parsedMotivos],
      precio_base_formateado: formatearMoneda(precioBase, config),
      precio_extra_formateado: formatearMoneda(precioExtra, config),
      totalEstimado,
      totalEstimadoFormateado: formatearMoneda(totalEstimado, config),
      horaIngresoLocal
    };
  });
}

/**
 * Cambia el estado de una bahía de lavado ('espera' | 'proceso' | 'listo')
 */
async function cambiarEstadoLavado(idLavado, nuevoEstado) {
  const estadosValidos = ['espera', 'proceso', 'listo'];
  const estadoNormalizado = String(nuevoEstado).toLowerCase().trim();
  if (!estadosValidos.includes(estadoNormalizado)) {
    throw new Error(`Estado '${nuevoEstado}' inválido. Opciones: espera, proceso, listo.`);
  }

  const lavado = await get('SELECT * FROM lavados_pendientes WHERE id = ?', [idLavado]);
  if (!lavado) {
    throw new Error(`Servicio de lavado #${idLavado} no encontrado.`);
  }

  const ahora = Date.now();
  let updateSql = 'UPDATE lavados_pendientes SET estado = ?';
  const params = [estadoNormalizado];

  if (estadoNormalizado === 'proceso') {
    updateSql += ', timestamp_proceso = COALESCE(timestamp_proceso, ?)';
    params.push(ahora);
  } else if (estadoNormalizado === 'listo') {
    updateSql += ', timestamp_listo = COALESCE(timestamp_listo, ?)';
    params.push(ahora);
  }
  updateSql += ' WHERE id = ?';
  params.push(idLavado);

  await run(updateSql, params);

  return { success: true, idLavado, nuevoEstado: estadoNormalizado };
}

/**
 * Obtiene el historial de visitas anteriores de un vehículo (ingresos pasados, servicios y observaciones)
 */
async function getHistorialVehiculo(patente) {
  if (!patente) return [];
  const cleanPatente = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
  const config = await getConfiguracionRegional();

  const rows = await all(`
    SELECT 
      i.id as id_ingreso,
      i.patente,
      i.fecha_ingreso,
      i.timestamp as timestamp_ingreso,
      i.salida,
      s.id as id_salida,
      s.fecha_salida,
      s.timestamp as timestamp_salida,
      s.total,
      s.metodo_pago,
      s.tiempo_estadia,
      t.nombre_servicio,
      lp.motivos_extra,
      lp.precio_extra,
      lp.observaciones
    FROM ingresos i
    LEFT JOIN salidas s ON s.id_ingresos = i.id
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    LEFT JOIN lavados_pendientes lp ON lp.id_ingreso = i.id
    WHERE i.patente = ?
    ORDER BY i.id DESC
    LIMIT 8
  `, [cleanPatente]);

  return rows.map((r) => {
    let extrasList = [];
    if (r.motivos_extra) {
      try {
        extrasList = typeof r.motivos_extra === 'string' ? JSON.parse(r.motivos_extra) : r.motivos_extra;
      } catch (e) {
        extrasList = [r.motivos_extra];
      }
    }
    const tsIngreso = Number(r.timestamp_ingreso) || new Date(r.fecha_ingreso).getTime();
    const tsSalida = r.timestamp_salida ? Number(r.timestamp_salida) : (r.fecha_salida ? new Date(r.fecha_salida).getTime() : null);

    const fIngreso = new Date(tsIngreso);
    const fSalida = tsSalida ? new Date(tsSalida) : null;

    return {
      idIngreso: r.id_ingreso,
      patente: r.patente,
      servicio: r.nombre_servicio,
      fechaIngresoLocal: isNaN(fIngreso.getTime()) ? r.fecha_ingreso : fIngreso.toLocaleString(),
      fechaSalidaLocal: fSalida ? fSalida.toLocaleString() : (r.salida === 0 ? 'En recinto activo' : 'Completado'),
      tiempoEstadia: r.tiempo_estadia || 'N/A',
      total: r.total || 0,
      totalFormateado: r.total ? formatearMoneda(r.total, config) : '-',
      metodoPago: r.metodo_pago || 'Pendiente',
      observaciones: r.observaciones || '',
      motivos_extra_list: Array.isArray(extrasList) ? extrasList : [],
      estaEnRecinto: r.salida === 0
    };
  });
}

/**
 * Registra una orden de admisión a lavado completa (admite vincular a estadía o servicio independiente)
 */
async function registrarLavadoCompleto({
  patente,
  id_servicio,
  motivos_extra = [],
  precio_extra = 0,
  observaciones = '',
  vincularEstadia = false
}) {
  if (!patente) throw new Error('La matrícula / patente es obligatoria.');
  const patenteNormalizada = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
  const config = await getConfiguracionRegional();

  const servicio = await get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [Number(id_servicio)]);
  if (!servicio) throw new Error('Servicio de lavado seleccionado no existe en el catálogo.');

  const ahora = Date.now();
  const extraNum = Math.max(0, parseFloat(precio_extra) || 0);
  const extraJson = typeof motivos_extra === 'string' ? motivos_extra : JSON.stringify(motivos_extra || []);

  return await withTransaction(async (tx) => {
    let idIngresoFinal;
    let estadiaVinculada = 0;

    // Verificar si ya se encuentra dentro del recinto
    const vehiculoActivo = await tx.get(
      'SELECT id, idtipo_ingreso FROM ingresos WHERE patente = ? AND salida = 0 ORDER BY id DESC LIMIT 1',
      [patenteNormalizada]
    );

    if (vincularEstadia && vehiculoActivo) {
      // Caso 1: Se vincula al ticket de estacionamiento activo existente
      idIngresoFinal = vehiculoActivo.id;
      estadiaVinculada = 1;
    } else {
      // Caso 2: Servicio independiente
      const resIngreso = await tx.run(
        `INSERT INTO ingresos (patente, fecha_ingreso, timestamp, idtipo_ingreso, salida)
         VALUES (?, datetime('now', 'localtime'), ?, ?, 0)`,
        [patenteNormalizada, ahora, servicio.idtipo_ingresos]
      );
      idIngresoFinal = resIngreso.lastID;
    }

    // Insertar en la cola de lavados pendientes
    const resLavado = await tx.run(
      `INSERT INTO lavados_pendientes (
         id_ingreso, patente, motivos_extra, precio_extra, estado, 
         observaciones, vinculado_estadia, id_servicio_lavado, 
         servicio_nombre, precio_base, timestamp_ingreso
       ) VALUES (?, ?, ?, ?, 'espera', ?, ?, ?, ?, ?, ?)`,
      [
        idIngresoFinal,
        patenteNormalizada,
        extraJson,
        extraNum,
        observaciones ? observaciones.trim() : '',
        estadiaVinculada,
        servicio.idtipo_ingresos,
        servicio.nombre_servicio,
        servicio.precio,
        ahora
      ]
    );

    return {
      success: true,
      idLavado: resLavado.lastID,
      idIngreso: idIngresoFinal,
      patente: patenteNormalizada,
      servicio: servicio.nombre_servicio,
      precio_base: servicio.precio,
      precio_extra: extraNum,
      observaciones,
      vinculado_estadia: estadiaVinculada,
      estado: 'espera',
      mensaje: estadiaVinculada 
        ? `Lavado vinculado a la estadía actual de ${patenteNormalizada}.`
        : `Ingreso a lavado registrado para ${patenteNormalizada}.`
    };
  });
}

/**
 * Cuadratura diaria de caja (Cierre Z) con desglose por método de pago y tipo de servicio
 */
async function getCierreCajaDiario(fechaParam) {
  const config = await getConfiguracionRegional();
  const filtroSql = fechaParam ? `date(?)` : `date('now', 'localtime')`;
  const params = fechaParam ? [fechaParam] : [];

  const totales = await get(`
    SELECT 
      COUNT(*) as totalTransacciones,
      COALESCE(SUM(total), 0) as totalRecaudado,
      COALESCE(SUM(CASE WHEN UPPER(metodo_pago) = 'EFECTIVO' THEN total ELSE 0 END), 0) as totalEfectivo,
      COALESCE(SUM(CASE WHEN UPPER(metodo_pago) IN ('TARJETA', 'POS', 'TUU') THEN total ELSE 0 END), 0) as totalTarjeta,
      COALESCE(SUM(CASE WHEN UPPER(metodo_pago) = 'TRANSFERENCIA' THEN total ELSE 0 END), 0) as totalTransferencia
    FROM salidas
    WHERE date(fecha_salida) = ${filtroSql}
  `, params);

  const porMetodo = await all(`
    SELECT 
      metodo_pago,
      COUNT(*) as cantidad,
      COALESCE(SUM(total), 0) as subtotal
    FROM salidas
    WHERE date(fecha_salida) = ${filtroSql}
    GROUP BY metodo_pago
    ORDER BY subtotal DESC
  `, params);

  const porServicio = await all(`
    SELECT 
      t.nombre_servicio,
      COUNT(s.id) as cantidad,
      COALESCE(SUM(s.total), 0) as subtotal
    FROM salidas s
    JOIN ingresos i ON s.id_ingresos = i.id
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    WHERE date(s.fecha_salida) = ${filtroSql}
    GROUP BY t.idtipo_ingresos, t.nombre_servicio
    ORDER BY subtotal DESC
  `, params);

  const vehiculosIngresadosHoy = await get(`
    SELECT COUNT(*) as total FROM ingresos WHERE date(fecha_ingreso) = ${filtroSql}
  `, params);

  const vehiculosActivos = await get(`
    SELECT COUNT(*) as total FROM ingresos WHERE salida = 0
  `);

  return {
    fecha: fechaParam || new Date().toISOString().split('T')[0],
    totalTransacciones: totales.totalTransacciones || 0,
    totalRecaudado: totales.totalRecaudado || 0,
    totalRecaudadoFormateado: formatearMoneda(totales.totalRecaudado || 0, config),
    totalEfectivo: totales.totalEfectivo || 0,
    totalEfectivoFormateado: formatearMoneda(totales.totalEfectivo || 0, config),
    totalTarjeta: totales.totalTarjeta || 0,
    totalTarjetaFormateado: formatearMoneda(totales.totalTarjeta || 0, config),
    totalTransferencia: totales.totalTransferencia || 0,
    totalTransferenciaFormateado: formatearMoneda(totales.totalTransferencia || 0, config),
    desgloseMetodos: porMetodo.map(m => ({
      ...m,
      subtotalFormateado: formatearMoneda(m.subtotal, config)
    })),
    desgloseServicios: porServicio.map(s => ({
      ...s,
      subtotalFormateado: formatearMoneda(s.subtotal, config)
    })),
    vehiculosIngresadosHoy: vehiculosIngresadosHoy.total || 0,
    vehiculosActivos: vehiculosActivos.total || 0,
    config
  };
}

/**
 * Consulta de Métricas Financieras Superiores para el Módulo de Reportes (KPI Cards)
 * - Facturación Diaria (Hoy y cantidad de operaciones)
 * - Mensual Hábiles (Lunes a Viernes del mes en curso)
 * - Mensual Total (Mes calendario completo)
 * - Ocupación Activa (Vehículos que permanecen en el recinto con salida = 0)
 */
async function getMetricasReportes() {
  const config = await getConfiguracionRegional();

  // 1. Facturación Diaria (Hoy)
  const diaria = await get(`
    SELECT 
      COALESCE(SUM(total), 0) as total,
      COUNT(*) as operaciones
    FROM salidas
    WHERE date(fecha_salida) = date('now', 'localtime')
  `);

  // 2. Mensual Hábiles (Lunes a Viernes del mes calendario en curso)
  // SQLite strftime('%w'): '0'=Domingo, '1'-'5'=Lunes a Viernes, '6'=Sábado
  const mensualHabiles = await get(`
    SELECT 
      COALESCE(SUM(total), 0) as total,
      COUNT(*) as operaciones
    FROM salidas
    WHERE strftime('%Y-%m', fecha_salida) = strftime('%Y-%m', 'now', 'localtime')
      AND strftime('%w', fecha_salida) IN ('1', '2', '3', '4', '5')
  `);

  // 3. Mensual Total (Mes calendario completo incluyendo fines de semana)
  const mensualTotal = await get(`
    SELECT 
      COALESCE(SUM(total), 0) as total,
      COUNT(*) as operaciones
    FROM salidas
    WHERE strftime('%Y-%m', fecha_salida) = strftime('%Y-%m', 'now', 'localtime')
  `);

  // 4. Ocupación Activa (Vehículos actualmente dentro del recinto)
  const ocupacion = await get(`
    SELECT COUNT(*) as total FROM ingresos WHERE salida = 0
  `);

  return {
    facturacionDiaria: {
      total: diaria?.total || 0,
      totalFormateado: formatearMoneda(diaria?.total || 0, config),
      operaciones: diaria?.operaciones || 0
    },
    mensualHabiles: {
      total: mensualHabiles?.total || 0,
      totalFormateado: formatearMoneda(mensualHabiles?.total || 0, config),
      operaciones: mensualHabiles?.operaciones || 0
    },
    mensualTotal: {
      total: mensualTotal?.total || 0,
      totalFormateado: formatearMoneda(mensualTotal?.total || 0, config),
      operaciones: mensualTotal?.operaciones || 0
    },
    ocupacionActiva: {
      total: ocupacion?.total || 0
    },
    config
  };
}

/**
 * Consulta de Operaciones Históricas Filtradas y Desglose por Categoría
 * @param {Object} filtros { desde: 'YYYY-MM-DD', hasta: 'YYYY-MM-DD', patente: string }
 */
async function getReporteOperaciones(filtros = {}) {
  const config = await getConfiguracionRegional();
  const { desde, hasta, patente } = filtros;

  const condiciones = [];
  const params = [];

  if (desde) {
    condiciones.push("date(s.fecha_salida) >= date(?)");
    params.push(desde);
  }
  if (hasta) {
    condiciones.push("date(s.fecha_salida) <= date(?)");
    params.push(hasta);
  }
  if (patente && String(patente).trim()) {
    condiciones.push("UPPER(i.patente) LIKE ?");
    params.push(`%${String(patente).trim().toUpperCase()}%`);
  }

  const whereSql = condiciones.length > 0 ? `WHERE ${condiciones.join(' AND ')}` : '';

  // 1. Listado de operaciones individuales
  const operaciones = await all(`
    SELECT 
      s.id as folio,
      s.id_ingresos,
      s.fecha_salida,
      s.timestamp as timestamp_salida,
      i.patente,
      t.nombre_servicio,
      s.metodo_pago,
      s.total,
      s.tiempo_estadia,
      s.monto_recibido,
      s.vuelto
    FROM salidas s
    JOIN ingresos i ON s.id_ingresos = i.id
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    ${whereSql}
    ORDER BY s.id DESC
  `, params);

  // 2. Resumen agrupado por categoría de servicio
  const categorias = await all(`
    SELECT 
      t.nombre_servicio as categoria,
      COUNT(s.id) as cantidad,
      COALESCE(SUM(s.total), 0) as subtotal
    FROM salidas s
    JOIN ingresos i ON s.id_ingresos = i.id
    JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
    ${whereSql}
    GROUP BY t.idtipo_ingresos, t.nombre_servicio
    ORDER BY subtotal DESC
  `, params);

  // 3. Totales consolidados del período
  const totales = await get(`
    SELECT 
      COUNT(s.id) as totalOperaciones,
      COALESCE(SUM(s.total), 0) as totalRecaudado,
      COALESCE(SUM(CASE WHEN UPPER(s.metodo_pago) = 'EFECTIVO' THEN s.total ELSE 0 END), 0) as totalEfectivo,
      COALESCE(SUM(CASE WHEN UPPER(s.metodo_pago) IN ('TARJETA', 'POS', 'TUU') THEN s.total ELSE 0 END), 0) as totalTarjeta,
      COALESCE(SUM(CASE WHEN UPPER(s.metodo_pago) = 'TRANSFERENCIA' THEN s.total ELSE 0 END), 0) as totalTransferencia
    FROM salidas s
    JOIN ingresos i ON s.id_ingresos = i.id
    ${whereSql}
  `, params);

  const totalRec = totales?.totalRecaudado || 0;

  return {
    operaciones: operaciones.map(op => {
      const ts = op.timestamp_salida ? Number(op.timestamp_salida) : (op.fecha_salida ? new Date(op.fecha_salida).getTime() : Date.now());
      const d = new Date(ts);
      return {
        ...op,
        totalFormateado: formatearMoneda(op.total, config),
        fechaSalidaCorta: d.toLocaleDateString('es-CL'),
        horaSalidaCorta: d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        fechaHoraSalida: d.toLocaleString('es-CL')
      };
    }),
    categorias: categorias.map(c => ({
      ...c,
      subtotalFormateado: formatearMoneda(c.subtotal, config),
      porcentaje: totalRec > 0 ? Number(((c.subtotal / totalRec) * 100).toFixed(1)) : 0
    })),
    totales: {
      totalOperaciones: totales?.totalOperaciones || 0,
      totalRecaudado: totalRec,
      totalRecaudadoFormateado: formatearMoneda(totalRec, config),
      totalEfectivo: totales?.totalEfectivo || 0,
      totalEfectivoFormateado: formatearMoneda(totales?.totalEfectivo || 0, config),
      totalTarjeta: totales?.totalTarjeta || 0,
      totalTarjetaFormateado: formatearMoneda(totales?.totalTarjeta || 0, config),
      totalTransferencia: totales?.totalTransferencia || 0,
      totalTransferenciaFormateado: formatearMoneda(totales?.totalTransferencia || 0, config),
      totalElectronico: (totales?.totalTarjeta || 0) + (totales?.totalTransferencia || 0),
      totalElectronicoFormateado: formatearMoneda((totales?.totalTarjeta || 0) + (totales?.totalTransferencia || 0), config)
    },
    filtros,
    config
  };
}

// ----------------------------------------------------------------------------
// 8. ADMINISTRACIÓN: CATÁLOGO, TARIFAS, METAS Y USUARIOS (FASE 3.4)
// ----------------------------------------------------------------------------

/**
 * Consulta la lista completa de tipos de ingreso / catálogo de servicios (activos e inactivos)
 */
async function getTodosTiposIngreso() {
  const config = await getConfiguracionRegional();
  const rows = await all('SELECT * FROM tipo_ingreso ORDER BY idtipo_ingresos ASC');
  return rows.map(r => ({
    ...r,
    precioFormateado: formatearMoneda(r.precio, config),
    esReservado: [17, 18, 19].includes(r.idtipo_ingresos)
  }));
}

/**
 * Agrega un nuevo servicio al catálogo
 */
async function crearTipoIngreso({ nombre_servicio, precio, es_plan = 0 }) {
  if (!nombre_servicio || !nombre_servicio.trim()) {
    throw new Error('El nombre del servicio es obligatorio.');
  }

  // Encontrar el siguiente ID disponible (evitando pisar los reservados 17, 18, 19)
  const maxRow = await get('SELECT MAX(idtipo_ingresos) as maxId FROM tipo_ingreso WHERE idtipo_ingresos NOT IN (17, 18, 19)');
  let nuevoId = (maxRow && maxRow.maxId) ? maxRow.maxId + 1 : 1;
  if (nuevoId === 17 || nuevoId === 18 || nuevoId === 19) {
    nuevoId = 20;
  }

  const p = parseFloat(precio) || 0;
  await run(`
    INSERT INTO tipo_ingreso (idtipo_ingresos, nombre_servicio, precio, es_plan, activo)
    VALUES (?, ?, ?, ?, 1)
  `, [nuevoId, nombre_servicio.trim(), p, es_plan ? 1 : 0]);

  const config = await getConfiguracionRegional();
  const nuevo = await get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [nuevoId]);
  return {
    ...nuevo,
    precioFormateado: formatearMoneda(nuevo.precio, config),
    esReservado: false
  };
}

/**
 * Modifica los datos de un servicio (precio, nombre y/o estado activo)
 */
async function actualizarTipoIngreso(id, { nombre_servicio, precio, activo }) {
  const servicio = await get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [id]);
  if (!servicio) throw new Error(`Servicio con ID ${id} no encontrado.`);

  const nuevoNombre = (nombre_servicio !== undefined && nombre_servicio !== null) ? nombre_servicio.trim() : servicio.nombre_servicio;
  const nuevoPrecio = (precio !== undefined && precio !== null) ? parseFloat(precio) : servicio.precio;

  // Los IDs reservados no pueden desactivarse
  let nuevoActivo = (activo !== undefined && activo !== null) ? (activo ? 1 : 0) : servicio.activo;
  if ([17, 18, 19].includes(Number(id))) {
    nuevoActivo = 1;
  }

  await run(`
    UPDATE tipo_ingreso
    SET nombre_servicio = ?, precio = ?, activo = ?
    WHERE idtipo_ingresos = ?
  `, [nuevoNombre, nuevoPrecio, nuevoActivo, id]);

  const config = await getConfiguracionRegional();
  const actualizado = await get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [id]);
  return {
    ...actualizado,
    precioFormateado: formatearMoneda(actualizado.precio, config),
    esReservado: [17, 18, 19].includes(actualizado.idtipo_ingresos)
  };
}

/**
 * Alterna el estado activo/inactivo de un servicio
 */
async function toggleActivoTipoIngreso(id) {
  if ([17, 18, 19].includes(Number(id))) {
    throw new Error('Los servicios reservados del sistema (Plan Mensual, Estacionamiento y Error) no pueden ser desactivados.');
  }

  const servicio = await get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [id]);
  if (!servicio) throw new Error(`Servicio con ID ${id} no encontrado.`);

  const nuevoEstado = servicio.activo ? 0 : 1;
  await run('UPDATE tipo_ingreso SET activo = ? WHERE idtipo_ingresos = ?', [nuevoEstado, id]);

  const config = await getConfiguracionRegional();
  const actualizado = await get('SELECT * FROM tipo_ingreso WHERE idtipo_ingresos = ?', [id]);
  return {
    ...actualizado,
    precioFormateado: formatearMoneda(actualizado.precio, config),
    esReservado: false
  };
}

/**
 * Obtiene las tarifas de estacionamiento vigentes (desde tabla precios y configuracion_regional)
 */
async function getTarifas() {
  const config = await getConfiguracionRegional();
  const precioRow = await get('SELECT * FROM precios WHERE id = 1');
  return {
    precio_minuto: precioRow ? precioRow.precio_minuto : config.precio_minuto,
    precio_minimo: precioRow ? precioRow.precio_minimo : config.precio_minimo,
    moneda_simbolo: config.moneda_simbolo,
    moneda_codigo: config.moneda_codigo,
    decimales: config.decimales,
    activar_redondeo_chile: config.activar_redondeo_chile
  };
}

/**
 * Guarda las tarifas de estacionamiento (precio por minuto y tarifa mínima) en SQLite
 */
async function guardarTarifas({ precio_minuto, precio_minimo }) {
  const pMinuto = parseFloat(precio_minuto);
  const pMinimo = parseFloat(precio_minimo);
  if (isNaN(pMinuto) || pMinuto < 0) throw new Error('El precio por minuto debe ser un número válido mayor o igual a 0.');
  if (isNaN(pMinimo) || pMinimo < 0) throw new Error('La tarifa mínima debe ser un número válido mayor o igual a 0.');

  await run(`
    UPDATE configuracion_regional
    SET precio_minuto = ?, precio_minimo = ?
    WHERE id = 1
  `, [pMinuto, pMinimo]);

  await run(`
    INSERT INTO precios (id, precio_minuto, precio_minimo, fecha_actualizacion)
    VALUES (1, ?, ?, datetime('now', 'localtime'))
    ON CONFLICT(id) DO UPDATE SET
      precio_minuto = excluded.precio_minuto,
      precio_minimo = excluded.precio_minimo,
      fecha_actualizacion = datetime('now', 'localtime')
  `, [pMinuto, pMinimo]);

  return await getTarifas();
}

/**
 * Obtiene el resumen de la meta mensual, facturación real del mes, ticket promedio y % alcanzado
 */
async function getMetaMensualResumen({ mes, anio, soloHabiles = false, sumarPlanes = true } = {}) {
  const fechaActual = new Date();
  const m = parseInt(mes, 10) || (fechaActual.getMonth() + 1);
  const y = parseInt(anio, 10) || fechaActual.getFullYear();
  const config = await getConfiguracionRegional();

  // 1. Meta configurada en la tabla metas_mensuales
  const metaRow = await get('SELECT meta_monto FROM metas_mensuales WHERE mes = ? AND anio = ?', [m, y]);
  const metaMonto = metaRow ? metaRow.meta_monto : 0;

  // 2. Query de salidas del mes
  const mesStr = String(m).padStart(2, '0');
  const anioStr = String(y);

  let whereClauses = [
    "strftime('%Y', s.fecha_salida) = ?",
    "strftime('%m', s.fecha_salida) = ?"
  ];
  let params = [anioStr, mesStr];

  if (soloHabiles) {
    // 1 a 5: lunes a viernes en SQLite strftime('%w', ...)
    whereClauses.push("CAST(strftime('%w', s.fecha_salida) AS INTEGER) BETWEEN 1 AND 5");
  }

  if (!sumarPlanes) {
    // Excluir idtipo_ingreso = 17 (Plan Mensual)
    whereClauses.push("i.idtipo_ingreso != 17");
  }

  const whereSql = 'WHERE ' + whereClauses.join(' AND ');

  const sqlResumen = `
    SELECT 
      COUNT(s.id) as totalTickets,
      COALESCE(SUM(s.total), 0) as totalFacturado
    FROM salidas s
    JOIN ingresos i ON s.id_ingresos = i.id
    ${whereSql}
  `;

  const datos = await get(sqlResumen, params);
  const totalFacturado = datos ? datos.totalFacturado : 0;
  const totalTickets = datos ? datos.totalTickets : 0;
  const ticketPromedio = totalTickets > 0 ? (totalFacturado / totalTickets) : 0;
  const porcentajeAlcanzado = metaMonto > 0 ? Math.min(Math.round((totalFacturado / metaMonto) * 1000) / 10, 100) : 0;
  const porcentajeReal = metaMonto > 0 ? Number(((totalFacturado / metaMonto) * 100).toFixed(1)) : 0;

  return {
    mes: m,
    anio: y,
    metaMonto,
    metaMontoFormateado: formatearMoneda(metaMonto, config),
    totalFacturado,
    totalFacturadoFormateado: formatearMoneda(totalFacturado, config),
    totalTickets,
    ticketPromedio,
    ticketPromedioFormateado: formatearMoneda(ticketPromedio, config),
    porcentajeAlcanzado,
    porcentajeReal,
    soloHabiles: Boolean(soloHabiles),
    sumarPlanes: Boolean(sumarPlanes),
    config
  };
}

/**
 * Guarda o actualiza la meta mensual en la tabla metas_mensuales
 */
async function guardarMetaMensual({ mes, anio, meta_monto, soloHabiles, sumarPlanes }) {
  const fechaActual = new Date();
  const m = parseInt(mes, 10) || (fechaActual.getMonth() + 1);
  const y = parseInt(anio, 10) || fechaActual.getFullYear();
  const monto = parseFloat(meta_monto) || 0;

  if (isNaN(m) || m < 1 || m > 12) throw new Error('Mes inválido (1-12).');
  if (isNaN(y) || y < 2000) throw new Error('Año inválido.');

  await run(`
    INSERT INTO metas_mensuales (mes, anio, meta_monto)
    VALUES (?, ?, ?)
    ON CONFLICT(mes, anio) DO UPDATE SET meta_monto = excluded.meta_monto
  `, [m, y, monto]);

  return await getMetaMensualResumen({ mes: m, anio: y, soloHabiles, sumarPlanes });
}

/**
 * Obtiene la lista de usuarios registrados
 */
async function getUsuarios() {
  const users = await all('SELECT id, usuario, rol, fecha_creacion FROM usuarios ORDER BY id ASC');
  return users.map(u => ({
    ...u,
    rolNombre: (u.rol === 'admin' || u.rol === 'administrador') ? 'Administrador' : 'Operador'
  }));
}

/**
 * Crea un nuevo usuario en la base de datos con hash seguro
 */
async function crearUsuario({ usuario, password, rol = 'operador' }) {
  if (!usuario || !usuario.trim()) throw new Error('El nombre de usuario es obligatorio.');
  if (!password || password.length < 4) throw new Error('La contraseña debe tener al menos 4 caracteres.');

  const rolNormalizado = (rol === 'admin' || rol === 'administrador') ? 'admin' : 'operador';
  const usuarioLimpio = usuario.trim().toLowerCase();

  const existente = await get('SELECT id FROM usuarios WHERE usuario = ?', [usuarioLimpio]);
  if (existente) throw new Error(`El usuario '${usuarioLimpio}' ya existe.`);

  const hash = hashPassword(password);
  const res = await run(`
    INSERT INTO usuarios (usuario, password_hash, rol, fecha_creacion)
    VALUES (?, ?, ?, datetime('now', 'localtime'))
  `, [usuarioLimpio, hash, rolNormalizado]);

  const nuevo = await get('SELECT id, usuario, rol, fecha_creacion FROM usuarios WHERE id = ?', [res.lastID]);
  return {
    ...nuevo,
    rolNombre: nuevo.rol === 'admin' ? 'Administrador' : 'Operador'
  };
}

/**
 * Restablece la contraseña de un usuario mediante hash seguro SHA-256
 */
async function resetPasswordUsuario({ id, newPassword }) {
  if (!newPassword || newPassword.length < 4) {
    throw new Error('La nueva contraseña debe tener al menos 4 caracteres.');
  }
  const usuario = await get('SELECT id, usuario FROM usuarios WHERE id = ?', [id]);
  if (!usuario) throw new Error(`Usuario con ID ${id} no encontrado.`);

  const hash = hashPassword(newPassword);
  await run('UPDATE usuarios SET password_hash = ? WHERE id = ?', [hash, id]);

  return { success: true, mensaje: `Contraseña restablecida exitosamente para '${usuario.usuario}'.` };
}

/**
 * Elimina un usuario, protegiendo al último administrador
 */
async function eliminarUsuario(id) {
  const usuario = await get('SELECT * FROM usuarios WHERE id = ?', [id]);
  if (!usuario) throw new Error(`Usuario con ID ${id} no encontrado.`);

  if (usuario.rol === 'admin') {
    const totalAdmins = await get("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'admin'");
    if (totalAdmins.count <= 1) {
      throw new Error('No es posible eliminar el único administrador del sistema.');
    }
  }

  await run('DELETE FROM usuarios WHERE id = ?', [id]);
  return { success: true, mensaje: `Usuario '${usuario.usuario}' eliminado correctamente.` };
}

// ----------------------------------------------------------------------------
// 9. CIERRE ORDENADO DE BASE DE DATOS
// ----------------------------------------------------------------------------
function closeDatabase() {
  return new Promise((resolve) => {
    if (dbInstance) {
      dbInstance.close((err) => {
        if (err) console.error('[SQLite] Error cerrando conexión:', err.message);
        else console.log('[SQLite] Conexión cerrada limpiamente.');
        dbInstance = null;
        resolve();
      });
    } else {
      resolve();
    }
  });
}

// ----------------------------------------------------------------------------
// 10. EXPORTACIÓN DEL MÓDULO
// ----------------------------------------------------------------------------
module.exports = {
  get DB_FILE_PATH() { return resolveDatabasePath(); },
  resolveDatabasePath,
  getDbConnection,
  run,
  get,
  all,
  exec,
  withTransaction,
  initDatabase,
  closeDatabase,
  seedReservedServices,
  seedDemoData,
  getConfiguracionRegional,
  setPerfilRegional,
  aplicarReglaRedondeo,
  formatearMoneda,
  getResumenDashboard,
  getIngresosActivos,
  registrarIngreso,
  calcularCobro,
  registrarSalida,
  getTiposIngreso,
  getClientes,
  getLavadosPendientes,
  cambiarEstadoLavado,
  getHistorialVehiculo,
  registrarLavadoCompleto,
  getCierreCajaDiario,
  getMetricasReportes,
  getReporteOperaciones,
  // Fase 3.4: Administración
  getTodosTiposIngreso,
  crearTipoIngreso,
  actualizarTipoIngreso,
  toggleActivoTipoIngreso,
  getTarifas,
  guardarTarifas,
  getMetaMensualResumen,
  guardarMetaMensual,
  getUsuarios,
  crearUsuario,
  resetPasswordUsuario,
  eliminarUsuario,
  PRESETS_REGIONALES
};

// Si se ejecuta directamente desde Node CLI (node database.js), inicializar y mostrar estado
if (require.main === module) {
  (async () => {
    console.log('[CLI] Inicializando base de datos...');
    await initDatabase();
    console.log('[CLI] Sembrando datos de demostración...');
    await seedDemoData();
    const resumen = await getResumenDashboard();
    console.log('[CLI] Resumen Dashboard:', JSON.stringify(resumen, null, 2));
    await closeDatabase();
    process.exit(0);
  })().catch((err) => {
    console.error('[CLI Error]', err);
    process.exit(1);
  });
}
