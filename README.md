# 🚗 ParkFlow — Sistema de Gestión de Estacionamiento y Lavado de Autos

[![Electron](https://img.shields.io/badge/Electron-31.7.7-47848F?logo=electron&logoColor=white)](https://www.electronjs.org/)
[![React](https://img.shields.io/badge/React-19.3.1-61DAFB?logo=react&logoColor=black)](https://react.dev/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.3.3-38B2AC?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![Vite](https://img.shields.io/badge/Vite-8.3.4-646CFF?logo=vite&logoColor=white)](https://vitejs.dev/)
[![Node.js](https://img.shields.io/badge/Node.js-%3E%3D20.0.0-339933?logo=node.js&logoColor=white)](https://nodejs.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**ParkFlow** es una aplicación de escritorio integral (SaaS Desktop) diseñada para el control operativo, facturación, servicios de lavado automotriz y administración de clientes abonados en estacionamientos de alta rotación.

El proyecto ha evolucionado desde una arquitectura legada distribuida (PHP + Apache + MySQL en entorno híbrido Linux/Windows 7) hacia una **solución de escritorio autónoma, moderna y de alto rendimiento**, basada en **Electron, React 19, Tailwind CSS v4 y SQLite3**.

---

## 🌟 Características Principales

### ⏱️ Control de Estacionamiento en Tiempo Real
- **Ingreso Rápido**: Detección y validación de patentes en tiempo real (formatos Chile y España).
- **Asignación Automática de Abonados**: Si la patente pertenece a un cliente mensual con suscripción vigente, el sistema lo identifica automáticamente y asigna tarifa exenta ($0).
- **Control de Estadía al Minuto**: Cálculo matemático exacto de minutos transcurridos, aplicación de tarifa base por minuto y cobro de tarifa mínima configurable.
- **Prevención de Duplicados**: Bloqueo preventivo de reingreso para vehículos que ya figuran dentro del recinto.

### 🧼 Tablero Operativo de Lavados (Wash Module)
- **Control de Estados**: Visualización en tiempo real del flujo de vehículos (En Espera, En Lavado, Listo para Entrega, Finalizado).
- **Servicios y Extras**: Agregado dinámico de ítems adicionales (aspirado profundo, desmanchado de tapiz, lavado de motor, recargos por barro o pelo de mascota).
- **Cronómetro de Servicio**: Seguimiento visual del tiempo transcurrido por cada vehículo en la estación de lavado.

### 💳 Motor de Cobro y Ley de Redondeo
- **Múltiples Métodos de Pago**: Efectivo, Tarjeta / POS, Transferencia bancaria y cuenta corriente de abonados.
- **Cumplimiento Estricto de la Ley de Redondeo de Chile (Ley 20.956)**: Ajuste legal automático al múltiplo de $10 en pagos con efectivo ($1-$5 redondea a $0, $6-$9 redondea a $10), manteniendo exactitud decimal en pagos con tarjeta.
- **Perfil Multimoneda**: Soporte y formateo nativo para Pesos Chilenos (CLP) y Euros (EUR).

### 👥 Administración de Clientes Abonados (Mensuales)
- **Gestión de Planes**: Registro de clientes con datos de contacto, tarifa pactada y múltiples patentes asociadas.
- **Semáforo de Vencimientos**: Detección visual automática de estados (Vigente / Por Vencer / Vencido).
- **Renovación en 1 Clic**: Actualización inmediata del ciclo de facturación y generación del comprobante respectivo.

### 📊 Cuadratura Diaria y Reportes Ejecutivos (Cierre Z)
- **Arqueo Z de Caja**: Desglose financiero exacto discriminando efectivo físico en gaveta, terminales POS/tarjetas y transferencias electrónicas.
- **KPIs en Tiempo Real**: Total facturado hoy, proyección mensual considerando días hábiles (L-V), ticket promedio y porcentaje de ocupación actual.
- **Historial Filtrable**: Búsqueda avanzada por fecha, rango de días, patente o tipo de servicio con exportación de datos.

### 🖨️ Tickets Térmicos Virtuales y Re-impresión
- **Modal de Ticket Virtual**: Previsualización estilo papel térmico (ESC/POS) de tickets de ingreso, comprobantes de cobro y resumen de Cierre Z sin requerir hardware físico para pruebas.
- **Reimpresión Rápida**: Búsqueda de tickets anteriores por folio o patente para reimpresión inmediata.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Shell / Runtime** | [Electron 31](https://www.electronjs.org/) | Ejecución de escritorio multiplataforma con aislamiento de contexto y single-instance lock. |
| **Frontend** | [React 19](https://react.dev/) | Interfaz declarativa, modular y reactiva con Hooks y Context API. |
| **Estilos** | [Tailwind CSS v4](https://tailwindcss.com/) | Diseño moderno, responsivo y modo oscuro/claro de alto contraste. |
| **Iconografía** | [Lucide React](https://lucide.dev/) | Iconos SVG consistentes y ligeros. |
| **Bundler** | [Vite 8](https://vitejs.dev/) | Compilación ultrarrápida y recarga en caliente (HMR). |
| **Base de Datos** | [SQLite3](https://www.sqlite.org/) | Persistencia embebida en disco, modo WAL (*Write-Ahead Logging*), claves foráneas e integridad ACID. |
| **Empaquetado** | [electron-builder](https://www.electron.build/) | Generación de instaladores NSIS y ejecutables portables para Windows x64. |

---

## 📁 Estructura del Proyecto

A continuación se detalla la estructura activa de la aplicación moderna:

```
sistemaEstacionamiento/
├── package.json                   # Dependencias, scripts y configuración de electron-builder
├── vite.config.mjs                # Configuración de empaquetado Vite + React
├── tailwind.config.js             # Configuración de estilos y fuentes
├── main.js                        # Proceso Principal de Electron (Ventanas, IPC y Single-Instance)
├── preload.js                     # Context Bridge seguro (window.electronAPI)
├── database.js                    # Capa DAO SQLite (Esquema, índices, migraciones y transacciones)
│
├── src/                           # Código Fuente de la Interfaz (React 19)
│   ├── main.jsx                   # Punto de entrada del cliente React
│   ├── App.jsx                    # Contenedor raíz y control de pestañas/modales
│   ├── index.css                  # Directivas de Tailwind CSS v4 y variables de diseño
│   ├── context/
│   │   └── AppContext.jsx         # Estado global de la aplicación (operaciones, filtros, reloj)
│   └── components/
│       ├── Navbar.jsx             # Barra superior con reloj, tarifas y acciones rápidas
│       ├── NavigationTabs.jsx     # Selector de módulos (Operaciones, Lavados, Reportes, Admin)
│       ├── Dashboard.jsx          # Vista principal de estacionamiento
│       ├── QuickEntryForm.jsx     # Formulario de ingreso rápido por patente
│       ├── ParkedVehiclesTable.jsx# Tabla de vehículos en recinto con cálculo en vivo
│       ├── CheckoutModal.jsx      # Modal de cobro con cálculo de vuelto y selección de pago
│       ├── VirtualTicketModal.jsx # Previsualización de ticket térmico
│       ├── CashClosingModal.jsx   # Modal de arqueo y cierre diario de caja
│       ├── ModifyTicketModal.jsx  # Corrección de tickets de ingreso
│       ├── ReprintModal.jsx       # Búsqueda y reimpresión de folios anteriores
│       ├── WashModule/            # Sub-componentes del tablero de lavados
│       ├── ReportsModule/         # Sub-componentes de estadísticas, KPIs y tabla Z
│       └── AdminModule/           # Pestañas de abonados, catálogo de servicios, precios y usuarios
│
├── ipc/                           # Controladores IPC de Electron
│   └── ipcHandlers.js             # Enrutamiento de eventos entre el renderer y SQLite
├── services/                      # Lógica de Negocio y Utilidades
│   ├── calculationEngine.js       # Motor matemático de estadía y ley de redondeo
│   ├── parkingService.js          # Control de entradas y salidas
│   ├── clienteService.js          # Control de abonados y planes
│   └── currencyHelper.js          # Formateo internacional de moneda
├── scripts/
│   └── migrar-sql-a-sqlite.js     # Script de migración masiva desde MySQL dump (estacionamiento.sql)
├── tests/                         # Suite de pruebas automatizadas en Node.js
│   ├── test_fase2_backend.js      # Pruebas de lógica, cobro, abonados y redondeo
│   ├── test_fase3_3_reportes.js   # Pruebas de KPIs financieros y cierre Z
│   └── test_fase3_4_admin.js      # Pruebas de catálogo, blindaje de IDs y usuarios
├── data/                          # Almacén local de bases de datos SQLite de desarrollo y plantillas
└── build/                         # Recursos gráficos e íconos de la aplicación (.ico)
```

---

## 🚀 Instalación y Puesta en Marcha

### Prerrequisitos
- **Node.js**: Versión `20.0.0` o superior.
- **npm**: Versión `10.0.0` o superior.
- **Sistema Operativo**: Windows 10/11 (o Windows 7 SP1 con soporte Node LTS), Linux o macOS.

### 1. Clonar el Repositorio
```bash
git clone https://github.com/Rodriguezmujica/sistema-estacionamiento.git
cd sistema-estacionamiento
```

### 2. Instalar Dependencias
```bash
npm install
```

### 3. Ejecutar en Modo Desarrollo (HMR)
Inicia concurrentemente el servidor Vite en el puerto `5173` y la ventana de Electron conectada:
```bash
npm run dev
```

### 4. Compilar y Probar en Producción Local
```bash
# Compilar bundle de React con Vite:
npm run build

# Iniciar la aplicación Electron cargando los archivos locales compilados (dist/index.html):
npm run start
```

### 5. Empaquetar y Distribuir (Windows)
```bash
# Generar instalador ejecutable NSIS (salida en carpeta release/):
npm run dist:installer

# Generar versión portátil (.exe único, sin instalación):
npm run dist:portable
```

---

## 🧪 Pruebas Automatizadas

El proyecto cuenta con suites de pruebas unitarias y de integración que verifican las reglas financieras, el esquema de datos y el motor de cobro:

```bash
# Probar conexión e inicialización de SQLite:
npm run test:db

# Ejecutar pruebas integrales del backend y motor de cobro:
node tests/test_fase2_backend.js

# Ejecutar pruebas del módulo de reportes y arqueo de caja Z:
node tests/test_fase3_3_reportes.js

# Ejecutar pruebas del panel administrativo y catálogo:
node tests/test_fase3_4_admin.js
```

---

## 🔄 Migración de Datos (MySQL a SQLite)

Si dispones de un volcado histórico de base de datos MySQL (`estacionamiento.sql`), puedes migrarlo íntegramente a SQLite mediante el script de streaming optimizado:

```bash
npm run migrate:sql
```
Este script:
1. Procesa `estacionamiento.sql` mediante Streams con consumo de memoria constante O(1).
2. Convierte las tablas `ingresos`, `salidas`, `clientes`, `tipo_ingreso`, `precios` y `usuarios`.
3. Normaliza las marcas temporales a formato Unix Epoch (`ms`) compatible con el motor de cálculo.
4. Genera la base de datos lista en `data/sistema_estacionamiento.sqlite` y la sincroniza con el `userData` de la app.

---

## ⚠️ Reglas Críticas de Negocio

> [!IMPORTANT]
> **IDs Reservados en el Catálogo de Servicios (`tipo_ingreso`):**
> - **ID 17:** Reservado exclusivamente para **Plan Mensual** (Precio base: $0).
> - **ID 18:** Reservado exclusivamente para **Estacionamiento por Minuto** (Tarifa dinámica según tiempo transcurrido).
> - **ID 19:** Reservado exclusivamente para **Error de Ingreso** (Tarifa simbólica de $1 o 0.01 € para anulación contable de registros erróneos).
> 
> *Estos identificadores están blindados en el código y no deben reasignarse ni eliminarse.*

> [!NOTE]
> **Ley de Redondeo de Chile (Ley N° 20.956):**
> Todo cobro en efectivo en pesos chilenos debe redondearse al múltiplo de $10 más cercano:
> - Dígitos finales entre 1 y 5 $\rightarrow$ redondeo hacia abajo a 0.
> - Dígitos finales entre 6 y 9 $\rightarrow$ redondeo hacia arriba a 10.
> - Los cobros efectuados mediante tarjeta o transferencia se liquidan por el monto exacto sin redondeo.

---

## 📦 Auditoría del Repositorio y Componentes Obsoletos

Debido a que el proyecto funcionó originalmente como un sistema web distribuido en PHP antes de ser reconstruido como aplicación de escritorio, existen múltiples carpetas y archivos en el repositorio que **ya no se utilizan en la versión actual (ParkFlow Desktop)**:

| Carpeta / Archivo | Estado Actual | Motivo / Reemplazo Moderno |
| :--- | :---: | :--- |
| `api/` (*.php) | **Obsoleto** | Reemplazado por los controladores IPC en `ipc/ipcHandlers.js` y la base de datos SQLite embebida. |
| `secciones/` (*.php, *.html) | **Obsoleto** | Reemplazado por las vistas declarativas en React 19 (`src/components/`). |
| `JS/` (vanilla / jQuery) | **Obsoleto** | Reemplazado por la arquitectura de componentes y estado reactivo en `src/`. |
| `scss/` | **Obsoleto** | Reemplazado por la compilación moderna de utilidades con Tailwind CSS v4. |
| `config/` (`conexion.php`, etc.) | **Obsoleto** | Ya no se requiere servidor web Apache ni conector MySQLi; SQLite es autónomo. |
| `SISTEMA-HIBRIDO/` | **Obsoleto** | Diseñado para la antigua sincronización física entre un PC con Linux antiX y un PC con Windows 7. |
| `firebase/` & `firebase-security-rules/` | **Obsoleto** | Se utilizaba para sincronizar el servidor y el cliente mediante RTDB/FCM; la app actual es local-first. |
| `print-service/` y `print-service-php/` | **Obsoleto** | Microservicios de impresión antiguos reemplazados por el modal de tickets virtuales y drivers nativos de Electron. |
| `ImpresionTermica/` | **Obsoleto** | Librería PHP Mike42/ESC-POS que requería runtime PHP y puertos locales. |
| `ENTREGA_JEFE/` y `programa anterior/` | **Obsoleto / Histórico** | Copias de seguridad de versiones anteriores a la migración. |
| `ARCHIVOS_COPIAR_USB.txt` / `INSTRUCCIONES_USB.md` | **Obsoleto** | Instructivos antiguos para traspasar archivos manualmente entre máquinas. |
| `estacionamiento.sql` (24 MB) | **Solo Referencia** | Volcado de MySQL legado. Ya fue convertido a SQLite (`data/sistema_estacionamiento.sqlite`). |
| `*.php` en raíz (`index.php`, `login.php`, etc.) | **Obsoleto** | Servidor PHP ya no interviene en la ejecución. |

> [!TIP]
> **Recomendación de Limpieza:**  
> Se recomienda archivar o eliminar estos archivos legados en una rama histórica (ej: `legacy-php-hybrid`) para mantener el repositorio principal limpio, liviano y enfocado exclusivamente en la aplicación Electron + React.

---

## 📄 Licencia

Este proyecto se encuentra bajo la Licencia [MIT](LICENSE).

---

## 👥 Créditos

Desarrollado para la gestión y modernización operativa de estacionamientos y servicios de lavado.  
**Valdivia, Región de Los Ríos, Chile.**
