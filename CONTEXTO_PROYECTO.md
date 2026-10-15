# CONTEXTO_PROYECTO.md — Memoria Técnica Maestra

> **Sistema de Estacionamiento y Servicios de Lavado "Los Ríos"**  
> **Ubicación:** Valdivia, Región de Los Ríos, Chile  
> **Versión Activa:** 2.0 (Sistema Híbrido)  
> **Última Actualización Técnica:** Octubre 2026  
> **Propósito:** Documento de contexto y memoria técnica para sesiones de desarrollo asistidas por IA y mantenimiento de ingeniería de software.

---

## 1. Resumen Ejecutivo del Proyecto y su Objetivo

El **Sistema de Estacionamiento Los Ríos** es una solución integral de punto de venta (POS), control de accesos vehiculares, administración de servicios automotrices y facturación fiscal/interna desarrollada para operar en entornos comerciales de alta rotación vehicular.

### Objetivos Clave del Negocio:
1. **Control de Flujo de Estacionamiento:** Registro inmediato de entradas y salidas de vehículos mediante lectura de patentes (formato chileno de 6 caracteres), cálculo de estadía en minutos, aplicación de tarifas por minuto y tarifas mínimas configurables.
2. **Servicios de Lavado y Cargos Extras:** Gestión de órdenes de lavado de autos (simple, completo, tapiz, motor) con cola de espera y asignación de recargos extraordinarios (barro excesivo, interior sucio, etc.).
3. **Control de Clientes Mensuales (Abonados):** Registro de clientes bajo suscripción mensual fija con múltiples patentes asociadas, alertas de vencimiento y renovación mensual automatizada.
4. **Integración con Terminales de Pago TUU (Haulmer):** Cobro directo y automatizado con terminales POS inalámbricos TUU mediante la API Remote Payment v2, emitiendo Boletas Electrónicas (DTE 48) o Facturas (DTE 33 con validación de RUT).
5. **Cuadratura y Cierre Diario de Caja (Cierre Z):** Desglose financiero exacto entre pagos electrónicos oficiales (TUU Débito / TUU Crédito / TUU Efectivo) y comprobantes manuales de contingencia (Efectivo / Transferencia / Tarjeta manual).
6. **Resumen Ejecutivo y Cumplimiento de Metas:** Panel gerencial con KPIs financieros, seguimiento de metas mensuales de recaudación (ponderando días hábiles) y exportación de reportes en PDF y Excel.
7. **Arquitectura Híbrida de Alta Disponibilidad:** Capacidad de operar sincronizado en tiempo real entre una máquina servidor en Linux y un punto de venta en Windows 7 con impresora térmica, con tolerancia a caídas de red vía Firebase.

---

## 2. Stack Tecnológico Exacto y Comandos de Ejecución

### 2.1. Tecnologías Base

| Capa | Componente | Versión / Detalle |
| :--- | :--- | :--- |
| **Backend** | PHP | 7.4 / 8.0+ (Sintaxis compatible con PHP 5.7+ en Windows 7 y 8.x en Debian/antiX). Extensiones: `mysqli`, `curl`, `gd`, `mbstring`, `json`. |
| **Base de Datos** | MySQL / MariaDB | MySQL 5.7+ / MariaDB 10.3+. Charset `utf8mb4`, Collation `utf8mb4_unicode_ci`. Timezone forzada a `America/Santiago`. |
| **Servidor Web** | Apache | Apache 2.4 (vía XAMPP en Windows o `apache2` nativo en Linux). |
| **Frontend** | HTML5 / Vanilla JS | JavaScript ES5/ES6 compatible con motores de renderizado antiguos (Chromium legacy en Windows 7). |
| **Diseño / UI** | Bootstrap & FontAwesome | Bootstrap 5.3 (CDN), FontAwesome 6.4 (CDN), SCSS modular compilado a `scss/main.css`. |
| **Gráficos y PDFs** | Chart.js / jsPDF | `Chart.js` 4.4.0 (KPIs mensuales), `jspdf` 2.5.1 + `jspdf-autotable` 3.5.28 (Reportes). |
| **Impresión Térmica** | Mike42 ESC/POS | Librería PHP `mike42/escpos-php` para conexión directa por puerto USB en Windows (`WindowsPrintConnector`) o dispositivo de caracteres en Linux (`FilePrintConnector` a `/dev/usb/lp0`). |
| **Sincronización Nube** | Google Firebase | Firebase SDK v8/v9 (Firestore, Realtime Database, Cloud Messaging FCM para notificaciones entre PCs). |
| **Pagos POS** | API TUU Haulmer | Remote Payment API v2 (`https://integrations.payment.haulmer.com/RemotePayment/v2/`). |

### 2.2. Comandos de Compilación, Ejecución y Pruebas

#### En Windows (Entorno Local / XAMPP):
```powershell
# 1. Iniciar servicios Apache y MySQL desde el panel de control de XAMPP (C:\xampp\xampp-control.exe)
# 2. Acceder al dashboard del sistema en el navegador:
http://localhost/sistemaEstacionamiento/

# 3. Compilar cambios de estilos SCSS a CSS (si se dispone de Sass CLI):
sass scss/main.scss scss/main.css --no-source-map

# 4. Probar servicio de impresión local por línea de comandos:
php print-service-php/imprimir.php

# 5. Generar o verificar logo para impresora térmica:
php tests/test_imprimir_logo.php
```

#### En Linux (antiX / Ubuntu / Servidor Principal):
```bash
# Iniciar / Reiniciar servicios web y base de datos:
sudo systemctl restart apache2
sudo systemctl restart mysql   # o mariadb

# Aplicar permisos al puerto de la impresora USB Star BSC10:
sudo chmod 666 /dev/usb/lp0
sudo usermod -a -G lp www-data

# Ejecutar script de configuración de producción:
sudo ./configurar_produccion.sh

# Probar impresión con cURL:
curl -X POST http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php \
  -H "Content-Type: application/json" \
  -d '{"tipo":"test","datos":{"mensaje":"Prueba de impresion"},"impresora":"POSESTACIONAMIENTO"}'
```

---

## 3. Mapa de Arquitectura y Estructura de Directorios

```
sistemaEstacionamiento/
│
├── index.php                      # Dashboard principal (Ingreso rápido, checkout, atajos, reloj)
├── login.php                      # Formulario de autenticación con control de sesión
├── logout.php                     # Destrucción segura de sesión y redirección
├── conexion.php                   # Conector maestro MySQLi (auto-detección de SO, timezone Chile, doble alias)
├── estacionamiento.sql            # Dump maestro con la estructura e información de la base de datos
├── CONTEXTO_PROYECTO.md           # Este archivo (Memoria técnica maestra)
│
├── api/                           # Endpoints REST (retornan JSON para consumo AJAX / fetch)
│   ├── registrar-ingreso.php      # Registra nueva entrada de vehículo
│   ├── calcular-cobro.php         # Calcula minutos, aplica tarifa según servicio y redondeo chileno
│   ├── registrar-salida.php       # Cierra estadía, marca salida, archiva cobro y limpia extras
│   ├── pago-manual.php            # Registro de pago en efectivo/transferencia sin POS TUU
│   ├── tuu-pago.php               # Crea solicitud de cobro en POS TUU vía API Haulmer
│   ├── tuu-confirm-payment.php    # Confirma transacción procesada por terminal TUU
│   ├── api_config_tuu.php         # Consulta y cambio de máquina TUU activa
│   ├── api_cierre_caja.php        # Resumen diario y desglose por método/categoría para Cierre Z
│   ├── api_clientes_mensuales.php # CRUD de abonados mensuales y cálculo de vencimientos
│   ├── api_servicios_lavado.php   # CRUD de tarifas y servicios de lavado
│   ├── api_precios.php            # Obtención y actualización del precio por minuto y tarifa mínima
│   ├── api_meta.php               # Configuración de meta mensual de ingresos
│   ├── api_resumen_ejecutivo.php  # Métricas avanzadas de facturación mensual y comparativa
│   ├── api_reportes_unificados.php# Estadísticas del día y mes para el dashboard y reportes
│   ├── api_usuarios.php           # CRUD de operadores y administradores (BCrypt hashing)
│   ├── imprimir-ticket.php        # Endpoint para re-imprimir último ingreso o última salida
│   └── imprimir-cierre-caja.php   # Genera comando de impresión de reporte Z en impresora térmica
│
├── secciones/                     # Vistas modulares del sistema
│   ├── admin.php                  # Panel administrativo (Clientes, Servicios, Configuración, Usuarios)
│   ├── reporte.php                # Historial de ventas, filtros de fechas, cierre de caja y exportaciones
│   ├── lavados.php                # Tablero visual de servicios de lavado automotriz
│   └── cobro.php                  # Pantalla de cobro de salidas alternativa
│
├── JS/                            # Lógica del cliente (JavaScript Vanilla)
│   ├── main.js                    # Inicializador global (BASE_PATH dinámico, reloj Chile, badge de tarifa)
│   ├── ingreso.js                 # Control del formulario de ingreso y validación de patentes
│   ├── cobro.js                   # Búsqueda de vehículos, llamada a TUU, cobro manual y toasts
│   ├── admin.js                   # Control de pestañas de administración, modales y Chart.js
│   ├── reporte.js                 # Manejo de tablas de reportes, fechas y llamadas a cierre de caja
│   ├── modal-lavado.js            # Modal interactivo para agregar cargos y notas extras de lavado
│   ├── emergencia-tuu.js          # Modal de emergencia para cambiar terminal POS o forzar cobro manual
│   └── print-service-client-win7.js # Cliente JS para enviar tickets a imprimir vía fetch local
│
├── config/                        # Archivos de configuración y plantillas
│   ├── conexion.php               # Conector unificado secundario
│   ├── config-sensible.example.php# Plantilla para credenciales de TUU, Firebase y Base de Datos
│   ├── config_session.php         # Configuración segura de sesiones PHP
│   └── config_timezone.php        # Configuración forzada de zona horaria America/Santiago
│
├── ImpresionTermica/              # Librerías de impresión de bajo nivel
│   ├── ticket/                    # Código fuente de Mike42/Escpos-php
│   ├── geek.png / geek_fixed.png  # Logotipos monocromáticos aptos para impresión térmica
│   └── abrircaja.php              # Envía pulso eléctrico ESC/POS para abrir gaveta de dinero
│
├── print-service-php/             # Servicio de impresión nativo en PHP
│   ├── imprimir.php               # Recibe JSON y emite comandos ESC/POS a la impresora térmica
│   └── test_logo.php              # Diagnóstico y renderizado de imágenes para tickets
│
├── print-service/                 # Microservicio de impresión alternativo en Node.js
│   ├── server.js                  # Servidor Express (puerto 3000)
│   └── package.json               # Dependencias Node.js
│
├── SISTEMA-HIBRIDO/               # Paquetes y scripts de despliegue para arquitectura híbrida
│   ├── PC1-ANTIX/                 # Archivos específicos para servidor Linux
│   ├── PC2-WINDOWS7/              # Archivos específicos para estación Windows 7
│   ├── COMPARTIDOS/               # Archivos comunes de sincronización
│   └── DOCUMENTACION/             # Guías operativas para el operador
│
├── tuu/                           # Integración con TUU (Webhooks y Firebase)
├── firebase/                      # Archivos de configuración Firestore, RTDB y FCM
├── sql/                           # Migraciones y scripts de soporte a base de datos
├── maintenance/                   # Scripts de reparación, creación de tablas y respaldos
├── tests/                         # Scripts de diagnóstico rápido y pruebas unitarias/funcionales
└── scss/                          # Archivos de estilo fuente SCSS y main.css compilado
```

> [!NOTE]
> Las carpetas `ENTREGA_JEFE/`, `programa anterior/`, `backups_emergencia/` y `backup_archivos_actuales/` corresponden a copias históricas y respaldos de seguridad. **No deben ser modificadas** ni tomadas como código activo.

---

## 4. Módulos Implementados, Rutas/Flujos y Lógica de Negocio

### 4.1. Flujo de Ingreso de Vehículos
1. El operador ingresa la patente en el Dashboard (`index.php`).
2. Se valida en frontend y backend: longitud máxima 6 caracteres alfanuméricos, mayúsculas automáticas (`^[A-Z0-9]+$`).
3. El operador elige el tipo de servicio (por defecto: *Estacionamiento por Minuto*, o un servicio de *Lavado*).
4. El sistema consulta si la patente corresponde a un **Cliente Mensual** activo. Si es así, se vincula con el plan correspondiente (`es_plan = 1`).
5. Se inserta el registro en la tabla `ingresos` con `fecha_ingreso = NOW()` y `salida = 0`.
6. Se envía la orden de impresión del ticket de ingreso a la impresora Star BSC10 con:
   - Logotipo en cabecera.
   - Folio (ID de ticket).
   - Patente en tamaño doble.
   - Fecha y hora exacta de entrada.
   - Código de barras para escaneo rápido.

### 4.2. Flujo de Cobro de Salidas
1. El cajero ingresa la patente en el panel de cobro.
2. `api/calcular-cobro.php` busca el último ingreso activo (`salida = 0` o `NULL`).
3. **Cálculo de la tarifa según el servicio:**
   - **Estacionamiento por minuto (ID 18):**
     $$\text{minutos} = \max\left(1, \lceil(\text{fecha\_salida} - \text{fecha\_ingreso}) / 60\rceil\right)$$
     $$\text{totalBase} = \max(\text{minutos} \times \text{precio\_minuto}, \text{precio\_minimo})$$
     (Valores predeterminados: \$35 por minuto, \$500 tarifa mínima).
   - **Lavados:** Costo base configurado en `tipo_ingreso` + `precio_extra` proveniente de `lavados_pendientes`.
   - **Planes Mensuales (`es_plan = 1`):** Total a pagar = \$0.
   - **Error de Ingreso (ID 19):** Forzado a \$1 simbólico para anular el registro erróneo sin romper la integridad contable.
4. **Ley de Redondeo de Chile (`redondearSegunLeyChilena`):**
   - Si el pago es en efectivo, el dígito final se ajusta al múltiplo de \$10:
     - Terminados en \$1 a \$5 se redondean hacia abajo a \$0.
     - Terminados en \$6 a \$9 se redondean hacia arriba a \$10.
   - Pagos con tarjeta vía TUU no se redondean.
5. **Vías de Pago:**
   - **Pago con TUU:** Envío asíncrono a la API Haulmer. El POS muestra el monto y el cliente paga con Débito o Crédito. Al confirmarse, emite Boleta Electrónica SII.
   - **Pago Manual:** Si la máquina TUU falla o el cliente paga en efectivo/transferencia sin POS, el modal de contingencia registra la salida en `salidas` con `tipo_pago = 'manual'`.
6. **Cierre de la transacción:**
   - Se inserta en `salidas` (`total`, `fecha_salida`, `metodo_pago`, `tipo_pago`, transacciones TUU si aplica).
   - Se actualiza `ingresos.salida = 1`.
   - Si había un registro en `lavados_pendientes`, se elimina.
   - Se abre la gaveta de dinero (`abrircaja.php`) y se imprime ticket de comprobante de salida.

### 4.3. Módulo de Servicios de Lavado
- Vista dedicada en `secciones/lavados.php`.
- Permite asociar ítems extras al lavado (ej: aspirado profundo, desmanchado de tapiz, lavado de chasis).
- Los datos se guardan temporalmente en `lavados_pendientes` hasta que el auto sale y se cobra en caja.

### 4.4. Módulo de Clientes Mensuales (Abonados)
- Administrado en `secciones/admin.php` (Pestaña "Gestión de Clientes").
- Registro de datos: Nombre completo, RUT, teléfono, patente principal, día fijo de pago (1 al 31), monto del plan mensual y fecha de vencimiento.
- Alertas visuales automáticas:
  - **Activo:** Verde (vigente dentro del período contratado).
  - **Vencido:** Rojo (fecha de vencimiento expirada).
- Botón "Confirmar Pago": Actualiza automáticamente la fecha de vencimiento al siguiente mes calendario.

### 4.5. Módulo de Cuadratura y Cierre de Caja Diaria
- Disponible en `secciones/reporte.php` mediante el botón "Cierre de Caja".
- Consulta `api/api_cierre_caja.php` para una fecha dada (por defecto: hoy).
- Agrupa y discrimina ingresos en:
  - **TUU Oficial:** Tarjeta Débito, Tarjeta Crédito, Efectivo por TUU.
  - **Manual Interno:** Efectivo directo, Transferencias bancarias.
  - **Por Categoría:** Estacionamiento, Lavados, Motos, Promociones.
- Emite un ticket térmico de "Cierre Z" con el resumen fiscal y contable para cuadratura física de la gaveta.

### 4.6. Módulo de Resumen Ejecutivo y Metas
- Consulta `api/api_resumen_ejecutivo.php`.
- Permite al administrador definir una meta mensual en pesos (\$):
  - Opción de cálculo considerando únicamente días laborables (Lunes a Viernes).
  - Opción de incluir o excluir los ingresos por planes mensuales.
- KPIs calculados: Total recaudado, vehículos atendidos, ticket promedio por servicio, porcentaje de meta alcanzada y proyección lineal a fin de mes.

---

## 5. Roles de Usuario, Permisos y Estructura de Datos

### 5.1. Roles y Permisos

| Rol en Sistema | Valor en BD | Acceso Permitido | Restricciones |
| :--- | :--- | :--- | :--- |
| **Administrador** | `'admin'` | Acceso total: Dashboard, Lavados, Reportes, Panel de Administración (`secciones/admin.php`), creación/edición de tarifas, clientes, metas y gestión de usuarios. | Ninguna. |
| **Operador (Cajero)** | `'cajero'` | Acceso operativo: Dashboard (`index.php`), registro de ingresos, cobro de salidas, re-impresión de tickets, tablero de lavados (`lavados.php`) y reportes básicos. | No puede acceder a `admin.php`, no puede cambiar precios por minuto, no puede gestionar usuarios. |

> [!IMPORTANT]
> **Super-administrador reservado:** El usuario `evelyn_dev` es una cuenta técnica de soporte de nivel de desarrollo que se encuentra oculta de la interfaz de usuario en `api/api_usuarios.php` para evitar su eliminación o bloqueo accidental.

### 5.2. Estructura de Tablas de la Base de Datos

#### Tabla: `usuarios`
- `id` (INT, PK, AI): Identificador único del usuario.
- `usuario` (VARCHAR 50, UNIQUE): Nombre de usuario de acceso.
- `password_hash` (VARCHAR 255): Contraseña encriptada con `password_hash(..., PASSWORD_BCRYPT)`.
- `rol` (ENUM `'admin'`, `'cajero'`): Rol de seguridad.
- `fecha_creacion` (TIMESTAMP): Fecha de alta del usuario.

#### Tabla: `ingresos`
- `idautos_estacionados` (INT, PK, AI): Número correlativo / Folio del ticket de entrada.
- `patente` (VARCHAR 10): Patente del vehículo (normalizada en mayúsculas).
- `fecha_ingreso` (DATETIME): Fecha y hora exacta de registro.
- `idtipo_ingreso` (INT, FK a `tipo_ingreso`): Servicio asociado al ingresar.
- `salida` (TINYINT): `0` = dentro del recinto, `1` = egresado y cobrado.
- `sincronizado` (TINYINT): Bandera de sincronización Firebase (0/1).
- `pc_origen` (VARCHAR 20): Identificador de la terminal que registró el ingreso.
- `fecha_sincronizacion` (TIMESTAMP): Última marca de tiempo de sincronización.

#### Tabla: `salidas`
- `id_salida` (INT, PK, AI): Identificador único de salida.
- `id_ingresos` (INT, FK a `ingresos`): Relación directa con el ticket de entrada.
- `fecha_salida` (DATETIME): Fecha y hora exacta de cobro.
- `total` (DECIMAL 10,2): Monto final cobrado al cliente.
- `metodo_pago` (VARCHAR 50): `'EFECTIVO'`, `'TUU'`, `'TRANSFERENCIA'`, `'TARJETA'`.
- `tipo_pago` (ENUM `'tuu'`, `'manual'`): Indica si fue fiscalizado por POS o manual.
- `metodo_tarjeta` (VARCHAR 50): `'debito'`, `'credito'`, `'efectivo'`, `'desconocido'`.
- `tipo_documento` (VARCHAR 50): `'boleta'` (DTE 48) o `'factura'` (DTE 33).
- `rut_cliente` (VARCHAR 20): RUT para facturación ante el SII.
- `transaction_id` (VARCHAR 100): ID devuelto por la pasarela TUU Haulmer.
- `authorization_code` (VARCHAR 100): Código de autorización bancaria TUU.
- `card_type` (VARCHAR 50): Marca de la tarjeta (Visa, Mastercard, etc.).
- `card_last4` (VARCHAR 4): Últimos 4 dígitos del plástico.
- `motivos_extra` (TEXT): Array JSON con los conceptos adicionales cobrados.
- `descripcion_extra` (TEXT): Observaciones libres sobre los extras.
- `precio_extra` (DECIMAL 10,2): Sumatoria del valor de los extras.

#### Tabla: `tipo_ingreso`
- `idtipo_ingresos` (INT, PK): Identificador único del servicio.
  - **ID 17:** *Plan Mensual* (`es_plan = 1`, precio 0).
  - **ID 18:** *Estacionamiento por minuto* (tarifa dinámica por tiempo).
  - **ID 19:** *Error de ingreso* (tarifa \$1 simbólica).
  - **IDs de Lavado:** Lavado simple, completo, camionetas, etc.
- `nombre_servicio` (VARCHAR 100): Nombre mostrado en la UI y en los tickets.
- `precio` (DECIMAL 10,2): Tarifa base en pesos chilenos.
- `descripcion` (TEXT): Alcance del servicio.
- `es_plan` (TINYINT): Indica si es plan mensual exento de cobro por tiempo.
- `activo` (TINYINT): 1 = disponible para selección, 0 = deshabilitado.

#### Tabla: `clientes` (Abonados Mensuales)
- `idclientes` (INT, PK, AI): Identificador del cliente.
- `patente` (VARCHAR 10): Patente asociada al abono.
- `nombres` (VARCHAR 100): Nombre del abonado.
- `apellidos` (VARCHAR 100): Apellidos del abonado.
- `tipo_vehiculo` (VARCHAR 50): Automóvil, SUV, Camioneta, etc.
- `dia_pago_mensual` (INT): Día del mes pactado para el vencimiento (ej: 5).
- `inicio_plan` (DATE): Fecha de inicio del contrato.
- `fecha_proximo_vencimiento` (DATE): Fecha límite de vigencia actual.
- `monto_plan` (DECIMAL 10,2): Valor mensual acordado.
- `notas` (TEXT): Observaciones o teléfonos de contacto.
- `idtipo_ingresos` (INT): FK a `tipo_ingreso` (fijado en 17).

#### Tabla: `precios`
- `id` (INT, PK): 1 (Fila única de configuración).
- `precio_minuto` (INT): Valor de 1 minuto de estacionamiento (ej: \$35).
- `precio_minuto_minimo` (INT): Tarifa base mínima cobrada (ej: \$500).

#### Tabla: `metas_mensuales`
- `id` (INT, PK, AI): Identificador.
- `mes` (INT): Mes (1 a 12).
- `anio` (INT): Año calendario.
- `meta_monto` (DECIMAL 10,2): Meta en pesos chilenos a alcanzar.
- `solo_dias_laborales` (TINYINT): 1 = Lun a Vie, 0 = todos los días.
- `incluir_mensuales` (TINYINT): 1 = sumar clientes mensuales, 0 = solo servicios diarios.

#### Tabla: `lavados_pendientes`
- `id` (INT, PK, AI): Identificador temporal.
- `id_ingreso` (INT, FK a `ingresos`): Ticket de entrada vinculado.
- `patente` (VARCHAR 10): Patente del vehículo.
- `motivos_extra` (TEXT): Conceptos extraordinarios en JSON.
- `descripcion_extra` (TEXT): Detalle de las tareas extras.
- `precio_extra` (DECIMAL 10,2): Valor a sumar al momento del cobro.

---

## 6. Reglas de Oro del Proyecto

> [!CAUTION]
> Cualquier cambio que viole las siguientes reglas provocará caídas en producción o descuadres contables y tributarios.

### 1. El Doble Alias de Conexión a Base de Datos
En [`conexion.php`](file:///e:/Descargas/Nueva%20carpeta%20(9)/sistemaEstacionamiento/conexion.php) y [`config/conexion.php`](file:///e:/Descargas/Nueva%20carpeta%20(9)/sistemaEstacionamiento/config/conexion.php) existe intencionalmente la línea:
```php
$conexion = $conn;
```
**Regla:** NUNCA eliminar ni `$conn` ni `$conexion`. El repositorio posee módulos heredados que consumen `$conexion->prepare()` y módulos modernos que consumen `$conn->prepare()`.

### 2. Timezone Obligatoria: `America/Santiago`
Chile cambia de huso horario dos veces al año (invierno UTC-4 y verano UTC-3). 
- El backend PHP y la sesión MySQL deben ejecutar siempre la sincronización de offset horario (`setMySQLChileTimezone($conn)`).
- En frontend, cualquier objeto `Date` debe formatearse con `timeZone: 'America/Santiago'`.
- NUNCA usar la hora UTC directa para registrar ingresos o salidas; provocaría cobros astronómicos o negativos en la estadía.

### 3. IDs Reservados en la Tabla `tipo_ingreso`
- **ID 17:** Reservado estrictamente para **Plan Mensual** (`es_plan = 1`).
- **ID 18:** Reservado estrictamente para **Estacionamiento por minuto**.
- **ID 19:** Reservado estrictamente para **Error de ingreso** (forzado a \$1).
**Regla:** Jamás reasignar, re-secuenciar o eliminar estos IDs en migraciones SQL. El motor de cobro y los reportes financieros dependen de estos valores exactos.

### 4. Mapeo de Roles: `cajero` (en BD) vs `operador` (en Código/UI)
- En la base de datos, el rol de operador se almacena como `'cajero'`.
- En las sesiones de PHP y en las vistas de la aplicación se presenta y valida como `'operador'`.
- Al consultar usuarios o autenticar: `$_SESSION['rol'] = ($row['rol'] === 'cajero') ? 'operador' : $row['rol'];`.
- Al persistir o crear un usuario: `if ($rol === 'operador') { $rol = 'cajero'; }`.

### 5. Respeto Estricto de la Ley de Redondeo de Chile
- La función [`redondearSegunLeyChilena($monto)`](file:///e:/Descargas/Nueva%20carpeta%20(9)/sistemaEstacionamiento/utils/redondeo_chile.php) debe aplicarse a todo cobro en efectivo antes de persistir o imprimir.
- Los cobros electrónicos por tarjeta en TUU **NO** deben ser redondeados a la baja o alza si el sistema bancario exige el monto exacto, a menos que la boleta fiscal lo estipule.

### 6. Restricciones Críticas de la API TUU (Haulmer RemotePayment v2)
- **No enviar `paymentMethod`** en el payload si se desea que el terminal muestre la selección de tarjeta o efectivo (evita el error fatal `RP-032`).
- **`idempotencyKey`:** Cadena alfanumérica única limpia de caracteres especiales, longitud máxima de **36 caracteres**.
- **`description`:** Longitud máxima de **28 caracteres** (ej: `Estacionamiento ABC123`).
- **`customFields`:** La combinación de `name` + `value` no puede superar **28 caracteres**.
- **`dteType`:** Debe ser `48` para Boleta Electrónica y `33` para Factura Electrónica (la cual exige obligatoriamente `customer.rut` limpio).

### 7. Integridad de la Impresión Térmica
- La librería ESC/POS requiere que las imágenes y logotipos sean monocromáticos puros de 1-bit (blanco y negro), sin canales alpha ni transparencias, y con dimensiones legibles mayores a 0x0 píxeles.
- Si se reemplaza el logotipo de la empresa, debe ejecutarse previamente `tests/fix_logo_problema.php` para generar la versión corregida (`geek_fixed.png` o `image_fixed.jpg`).
- El nombre compartido de la impresora en Windows 7 debe ser exactamente `POSESTACIONAMIENTO`.

### 8. Respuestas JSON Limpias en APIs
- Toda API en el directorio `/api/` debe limpiar buffers de salida (`ob_clean()` o `ob_end_clean()`) y configurar `ini_set('display_errors', '0');` antes de emitir la respuesta JSON con `header('Content-Type: application/json; charset=utf-8');`.
- De lo contrario, advertencias o notices de PHP romperán el parseo de `response.json()` en el frontend.

### 9. Compatibilidad con Windows 7
- No utilizar sintaxis JavaScript moderna incompatible con navegadores legacy (evitar `??=`, optional chaining anidado no soportado, private fields `#`, o métodos de Array muy recientes).
- No implementar alertas modales sincrónicas bloqueantes (`window.alert()` / `window.prompt()`) durante los ciclos automáticos de cobro.

---
*Este documento es la referencia técnica definitiva del proyecto. Cualquier nuevo endpoint, tabla o refactorización debe respetar y actualizar este archivo.*
