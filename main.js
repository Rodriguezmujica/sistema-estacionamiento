/**
 * ============================================================================
 * SISTEMA DE GESTIÓN DE ESTACIONAMIENTO Y LAVADO DE AUTOS
 * Proceso Principal de Electron (main.js)
 * ============================================================================
 * Versión: Electron (v30+) / Node.js (v20+ LTS)
 * Arquitectura Segura: Context Isolation + Preload Bridge + IPC Handlers
 * Persistencia: SQLite en app.getPath('userData')
 * ============================================================================
 */

const { app, BrowserWindow, ipcMain, Menu } = require('electron');

// Establecer nombre canónico de la aplicación para estructurar la carpeta en AppData / userData
app.setName('ParkFlow');

const path = require('path');
const fs = require('fs');
const db = require('./database');

// Mantener referencia global para evitar que la ventana sea recolectada por el GC
let mainWindow = null;

// Asegurar instancia única para evitar colisiones de base de datos o múltiples ventanas
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore();
      mainWindow.focus();
    }
  });
}

/**
 * Crea y configura la ventana principal de la aplicación.
 */
function createMainWindow() {
  const iconPath = path.join(__dirname, 'build', 'icon.ico');

  mainWindow = new BrowserWindow({
    width: 1300,
    height: 850,
    minWidth: 1080,
    minHeight: 700,
    title: 'ParkFlow • Sistema de Gestión de Estacionamiento y Lavado de Autos',
    backgroundColor: '#0f172a', // Dark slate background moderno
    show: false, // Se muestra una vez cargado el contenido para evitar parpadeos
    icon: fs.existsSync(iconPath) ? iconPath : undefined,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: false,
      spellcheck: false
    }
  });

  // Cargar aplicación: Servidor Vite en desarrollo o dist/index.html compilado en producción
  const isDev = !app.isPackaged && !!process.env.VITE_DEV_SERVER_URL;
  const distHtmlPath = path.join(__dirname, 'dist', 'index.html');

  if (isDev) {
    console.log(`[Electron] Modo Desarrollo: Conectando a ${process.env.VITE_DEV_SERVER_URL}`);
    mainWindow.loadURL(process.env.VITE_DEV_SERVER_URL);
  } else if (fs.existsSync(distHtmlPath)) {
    console.log(`[Electron] Modo Producción: Cargando paquete local ${distHtmlPath}`);
    mainWindow.loadFile(distHtmlPath);
  } else {
    console.warn(`[Electron] Advertencia: No se encontró dist/index.html, recurriendo a index.html raíz`);
    mainWindow.loadFile(path.join(__dirname, 'index.html'));
  }

  // Mostrar la ventana cuando el renderizado inicial esté listo
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
  });

  // Liberar referencia al cerrar
  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  // Desactivar barra de menú por defecto para interfaz nativa limpia
  Menu.setApplicationMenu(null);
}

/**
 * Registro de Handlers IPC seguros para comunicación con el Renderer.
 */
const { registerAllIpcHandlers } = require('./ipc/ipcHandlers');

// ----------------------------------------------------------------------------
// CICLO DE VIDA DE LA APLICACIÓN ELECTRON
// ----------------------------------------------------------------------------
app.whenReady().then(async () => {
  console.log('------------------------------------------------------------');
  console.log('Iniciando Sistema de Gestión de Estacionamiento (Fase 2)...');
  console.log(`Electron: v${process.versions.electron} | Node.js: v${process.versions.node}`);
  console.log(`Ruta userData: ${app.getPath('userData')}`);
  console.log('------------------------------------------------------------');

  try {
    // 1. Inicializar esquema SQLite y servicios base
    await db.initDatabase();
    console.log('[SQLite] Esquema verificado y listo para operar.');

    // 2. Registrar Handlers IPC de la Fase 2
    registerAllIpcHandlers();

    // 3. Crear ventana principal
    createMainWindow();
  } catch (err) {
    console.error('[Inicialización Fallida] Error al arrancar la aplicación:', err);
    app.quit();
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
    }
  });
});

// Cerrar aplicación cuando todas las ventanas se cierren (salvo en macOS)
app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

// Cierre limpio de la base de datos al salir
app.on('before-quit', async (event) => {
  console.log('[App] Cerrando recursos antes de salir...');
  await db.closeDatabase();
});
