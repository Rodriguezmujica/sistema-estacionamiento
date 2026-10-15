-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-11-2025 a las 15:03:38
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "America/Santiago";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `estacionamiento`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `idadmin` int(11) NOT NULL,
  `usuario` varchar(45) DEFAULT NULL,
  `pass` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`idadmin`, `usuario`, `pass`) VALUES
(1, 'admin', '81dc9bdb52d04dc20036dbd8313ed055');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `idclientes` int(11) NOT NULL,
  `rut` varchar(45) DEFAULT NULL,
  `nombres` varchar(45) DEFAULT NULL,
  `apellidos` varchar(45) DEFAULT NULL,
  `tipo_vehiculo` varchar(100) DEFAULT NULL,
  `fecha_inicio_plan` date DEFAULT NULL,
  `fecha_proximo_vencimiento` date DEFAULT NULL,
  `monto_plan` decimal(10,2) DEFAULT 0.00,
  `dia_pago_mensual` int(2) DEFAULT 5,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `patente` varchar(45) DEFAULT NULL,
  `inicio_plan` date DEFAULT NULL,
  `fin_plan` date DEFAULT NULL,
  `fono` varchar(45) DEFAULT NULL,
  `idtipo_ingresos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'sistema_version', '2.0.0', 'Versión del sistema', '2025-10-23 02:27:40'),
(2, 'firebase_enabled', '1', 'Firebase habilitado', '2025-10-23 02:27:40'),
(3, 'tuu_enabled', '1', 'TUU habilitado', '2025-10-23 02:27:40'),
(4, 'sync_interval', '30', 'Intervalo de sincronización en segundos', '2025-10-23 02:27:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_tuu`
--

CREATE TABLE `configuracion_tuu` (
  `id` int(11) NOT NULL,
  `maquina` varchar(20) NOT NULL COMMENT 'principal o respaldo',
  `device_serial` varchar(100) NOT NULL COMMENT 'Número de serie del dispositivo TUU',
  `nombre` varchar(100) DEFAULT NULL COMMENT 'Nombre descriptivo',
  `activa` tinyint(1) DEFAULT 0 COMMENT '1 = activa, 0 = inactiva',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_tuu`
--

INSERT INTO `configuracion_tuu` (`id`, `maquina`, `device_serial`, `nombre`, `activa`, `fecha_actualizacion`) VALUES
(1, 'principal', '6010B23251900353', 'TUU Principal - Serial Real 6010B23251900353', 1, '2025-10-11 16:38:10'),
(2, 'respaldo', 'SERIAL_MAQUINA_2_AQUI', 'TUU Respaldo - Caja 2', 0, '2025-10-11 04:44:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firebase_sync_log`
--

CREATE TABLE `firebase_sync_log` (
  `id` int(11) NOT NULL,
  `tabla` varchar(50) NOT NULL,
  `accion` enum('create','update','delete') NOT NULL,
  `registro_id` int(11) NOT NULL,
  `fecha_sync` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('pending','success','error') DEFAULT 'pending',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `idautos_estacionados` int(11) NOT NULL,
  `patente` varchar(6) DEFAULT NULL,
  `fecha_ingreso` timestamp NULL DEFAULT current_timestamp(),
  `hora_salida` datetime DEFAULT NULL,
  `salida` int(11) DEFAULT 0,
  `idtipo_ingreso` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio del servicio',
  `sincronizado` tinyint(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lavados_pendientes`
--

CREATE TABLE `lavados_pendientes` (
  `id` int(11) NOT NULL,
  `id_ingreso` int(11) NOT NULL,
  `patente` varchar(10) NOT NULL,
  `motivos_extra` text DEFAULT NULL,
  `descripcion_extra` text DEFAULT NULL,
  `precio_extra` decimal(10,2) DEFAULT 0.00,
  `nombre_cliente` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_lavado` varchar(100) NOT NULL COMMENT 'Tipo de lavado',
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio del lavado',
  `estado` enum('pendiente','en_proceso','completado','cancelado') DEFAULT 'pendiente' COMMENT 'Estado del lavado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas`
--

CREATE TABLE `metas` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `valor_objetivo` decimal(10,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas_mensuales`
--

CREATE TABLE `metas_mensuales` (
  `id` int(11) NOT NULL,
  `mes` int(11) NOT NULL COMMENT 'Mes (1-12)',
  `anio` int(11) NOT NULL COMMENT 'Año (ej: 2025)',
  `meta_monto` decimal(10,2) NOT NULL COMMENT 'Meta en pesos',
  `solo_dias_laborales` tinyint(1) DEFAULT 1 COMMENT '1 = solo lun-vie, 0 = todos los días',
  `incluir_mensuales` tinyint(1) DEFAULT 0 COMMENT '1 = incluir clientes mensuales, 0 = solo servicios diarios',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creador` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metas_mensuales`
--

INSERT INTO `metas_mensuales` (`id`, `mes`, `anio`, `meta_monto`, `solo_dias_laborales`, `incluir_mensuales`, `fecha_creacion`, `usuario_creador`) VALUES
(1, 10, 2025, 6700000.00, 1, 0, '2025-10-11 02:43:17', NULL),
(3, 1, 2025, 6000000.00, 1, 0, '2025-10-11 03:28:02', NULL),
(4, 2, 2025, 6000000.00, 1, 0, '2025-10-14 01:02:35', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patente_asociada`
--

CREATE TABLE `patente_asociada` (
  `id_cliente` int(11) NOT NULL,
  `patente` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precios`
--

CREATE TABLE `precios` (
  `id` int(11) NOT NULL,
  `precio_minuto_minimo` int(11) NOT NULL,
  `precio_minuto` int(11) NOT NULL,
  `rango_precio_min` int(11) NOT NULL,
  `rango_minimo` int(11) NOT NULL,
  `rango_precio` int(11) NOT NULL,
  `rango_minutos` int(11) NOT NULL,
  `tipo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `precios`
--

INSERT INTO `precios` (`id`, `precio_minuto_minimo`, `precio_minuto`, `rango_precio_min`, `rango_minimo`, `rango_precio`, `rango_minutos`, `tipo`) VALUES
(1, 500, 35, 500, 20, 500, 20, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas`
--

CREATE TABLE `salidas` (
  `id_ingresos` int(11) NOT NULL,
  `fecha_salida` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` int(11) DEFAULT NULL,
  `motivos_extra` text DEFAULT NULL,
  `descripcion_extra` text DEFAULT NULL,
  `precio_extra` decimal(10,2) DEFAULT 0.00,
  `metodo_pago` varchar(50) DEFAULT 'EFECTIVO' COMMENT 'EFECTIVO o TUU',
  `tipo_pago` enum('tuu','manual') DEFAULT 'manual',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT 'ID de transacción TUU',
  `authorization_code` varchar(100) DEFAULT NULL COMMENT 'Código de autorización TUU',
  `card_type` varchar(50) DEFAULT NULL COMMENT 'Tipo de tarjeta (VISA, MASTERCARD, etc)',
  `card_last4` varchar(4) DEFAULT NULL COMMENT 'Últimos 4 dígitos de la tarjeta',
  `metodo_tarjeta` varchar(50) DEFAULT NULL COMMENT 'debito, credito, etc.',
  `tipo_documento` varchar(50) DEFAULT NULL COMMENT 'boleta o factura',
  `rut_cliente` varchar(20) DEFAULT NULL COMMENT 'RUT del cliente para facturas',
  `sincronizado` tinyint(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `patente` varchar(10) NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cliente_nombre` varchar(100) DEFAULT NULL,
  `cliente_telefono` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `tipo_servicio` enum('estacionamiento','lavado','ambos') DEFAULT 'estacionamiento',
  `pagado` tinyint(1) DEFAULT 0,
  `sincronizado` tinyint(1) DEFAULT 0,
  `pc_origen` varchar(20) DEFAULT NULL,
  `fecha_sincronizacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_ingreso`
--

CREATE TABLE `tipo_ingreso` (
  `idtipo_ingresos` int(11) NOT NULL,
  `nombre_servicio` varchar(45) DEFAULT NULL,
  `precio` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `es_plan` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre del servicio',
  `tipo_servicio` enum('estacionamiento','lavado','ambos') DEFAULT 'estacionamiento' COMMENT 'Tipo de servicio'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipo_ingreso`
--

INSERT INTO `tipo_ingreso` (`idtipo_ingresos`, `nombre_servicio`, `precio`, `descripcion`, `es_plan`, `activo`, `nombre`, `tipo_servicio`) VALUES
(2, 'Mensual', 30000, NULL, 1, 0, '', 'estacionamiento'),
(3, 'Visita', 2000, NULL, 0, 0, '', 'estacionamiento'),
(5, 'lavado exterior city y sedan 12', 12000, NULL, 0, 1, '', 'estacionamiento'),
(6, 'lavado exterior suv 15', 15000, NULL, 0, 1, '', 'estacionamiento'),
(7, 'lavado exterior camioneta fango 20', 20000, NULL, 0, 1, '', 'estacionamiento'),
(8, 'Lavado interior citycar y sedanes 12', 12000, '', 0, 1, '', 'estacionamiento'),
(9, 'Lavado interior suv 15', 15000, NULL, 0, 1, '', 'estacionamiento'),
(10, 'lavado interior camioneta fango 20', 20000, NULL, 0, 1, '', 'estacionamiento'),
(11, 'lavado interior y exterior citycar y sed 22', 22000, NULL, 0, 1, '', 'estacionamiento'),
(12, 'lavado interior y exterior suv 25', 25000, NULL, 0, 1, '', 'estacionamiento'),
(13, 'lavado interior y exterior camionetas, fur 30', 30000, NULL, 0, 1, '', 'estacionamiento'),
(14, 'Lavado interior y exterior camionetas g 35', 35000, NULL, 0, 1, '', 'estacionamiento'),
(15, 'lavado motor vapor 20', 20000, NULL, 0, 1, '', 'estacionamiento'),
(16, 'lavado de tapiz 45', 45000, NULL, 0, 1, '', 'estacionamiento'),
(17, 'Total para la venta 70', 70000, NULL, 0, 0, '', 'estacionamiento'),
(18, 'estacionamiento x minuto', 0, NULL, 0, 1, '', 'estacionamiento'),
(19, 'ERROR DE INGRESO', 1, NULL, 0, 1, '', 'estacionamiento'),
(20, 'CONVENIO CITY EXTERIOR', 6000, NULL, 0, 0, '', 'estacionamiento'),
(21, 'CONVENIO OTROS EXTERIOR', 7000, NULL, 0, 0, '', 'estacionamiento'),
(22, 'CONVENIO CITY INTERIOR', 6000, NULL, 0, 0, '', 'estacionamiento'),
(23, '', 0, NULL, 0, 0, '', 'estacionamiento'),
(24, 'CONVENIO CITY COMPLETO', 12000, NULL, 0, 0, '', 'estacionamiento'),
(25, 'CONVENIO OTROS COMPLETO', 15000, NULL, 0, 0, '', 'estacionamiento'),
(26, 'CONVENIO CITY TAPIZ', 29000, NULL, 0, 0, '', 'estacionamiento'),
(27, 'CONVENIO OTROS TAPIZ', 29000, NULL, 0, 0, '', 'estacionamiento'),
(28, 'CONVENIO MOTOR', 15000, NULL, 0, 0, '', 'estacionamiento'),
(29, 'CONVENIO PARA LA VENTA', 40000, NULL, 0, 0, '', 'estacionamiento'),
(30, 'MOTOS 10', 10000, NULL, 0, 1, '', 'estacionamiento'),
(31, 'MOTOS GRANDE 15', 15000, NULL, 0, 1, '', 'estacionamiento'),
(32, 'LAVADO ALFOMBRA CHICO', 10000, NULL, 0, 0, '', 'estacionamiento'),
(33, 'LAVADO DE ALFOMBRA GRANDE', 15000, NULL, 0, 0, '', 'estacionamiento'),
(34, 'DIARIO', 15000, NULL, 0, 0, '', 'estacionamiento'),
(35, 'SANITIZACION', 12000, NULL, 0, 0, '', 'estacionamiento'),
(36, 'PROMO SANITIZACION MAS INTERIOR', 15000, NULL, 0, 0, '', 'estacionamiento'),
(37, 'PROMOCION INVERNAL INT Y EXT', 11900, NULL, 0, 0, '', 'estacionamiento'),
(38, 'PROMOCION INVERNAL INT Y EXT', 11900, NULL, 0, 0, '', 'estacionamiento'),
(39, 'PROMOCION INVERNAL INTERIOR', 6000, NULL, 0, 0, '', 'estacionamiento'),
(40, 'Club mazon', 2500, '', 0, 1, '', 'estacionamiento'),
(41, 'LAVADO INT Y EXTERIOR CAMIONETAS 20', 20000, NULL, 0, 0, '', 'estacionamiento'),
(42, 'LAVADO INT Y EXT FURGONES Y 3 CORRIDAS 25', 25000, NULL, 0, 0, '', 'estacionamiento'),
(43, 'LAVADO INTERIOR FURGONES Y 3 CORRIDAS 13', 13000, NULL, 0, 0, '', 'estacionamiento'),
(44, 'LAVADO EXTERIOR FURGONES Y 3 CORRIDAS 13', 13000, NULL, 0, 0, '', 'estacionamiento'),
(45, 'Servicio modificable (explicar precio y motiv', 0, 'es un servicio modificable para ajustar a un servicio único o especifico.', 0, 1, '', 'estacionamiento'),
(46, 'Lavado Alfombra piso ', 20000, NULL, 0, 0, '', 'estacionamiento'),
(47, 'Estacionamiento 2000', 2000, NULL, 0, 1, '', 'estacionamiento'),
(48, '', 0, NULL, 0, 0, '', 'estacionamiento'),
(49, '', 0, NULL, 0, 0, '', 'estacionamiento');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','cajero') DEFAULT 'cajero'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password_hash`, `rol`) VALUES
(1, 'admin', '$2y$10$X1eT7sIvXbngJdvZ/jDchOX9/h5U5tMXt00gcIBg2tqiLPUM5ht7y', 'admin'),
(2, 'cajero', '$2y$10$6CTwnTQD1sveJoxlgU8poOAsaV6M7v9RKFBAJqRWsADns.I3Y/1Ou', 'cajero'),
(3, 'evelyn_dev', '$2y$10$1G8nOI0hR37LNC2ykPiMzOCOmuU3GRAOB0FzuLiimwf69iWyrFdDq', 'admin');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`idadmin`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`idclientes`),
  ADD KEY `fk_clientes_tipo_ingreso1_idx` (`idtipo_ingresos`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_clave` (`clave`);

--
-- Indices de la tabla `configuracion_tuu`
--
ALTER TABLE `configuracion_tuu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maquina` (`maquina`);

--
-- Indices de la tabla `firebase_sync_log`
--
ALTER TABLE `firebase_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tabla` (`tabla`),
  ADD KEY `idx_fecha_sync` (`fecha_sync`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`idautos_estacionados`),
  ADD KEY `fk_autos_estacionados_tipo_ingresos_idx` (`idtipo_ingreso`),
  ADD KEY `idx_ingresos_fecha_salida` (`fecha_ingreso`,`salida`),
  ADD KEY `idx_ingresos_tipo_salida` (`idtipo_ingreso`,`salida`);

--
-- Indices de la tabla `lavados_pendientes`
--
ALTER TABLE `lavados_pendientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_ingreso` (`id_ingreso`),
  ADD KEY `idx_patente` (`patente`);

--
-- Indices de la tabla `metas`
--
ALTER TABLE `metas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_activa` (`activa`),
  ADD KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`);

--
-- Indices de la tabla `metas_mensuales`
--
ALTER TABLE `metas_mensuales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mes_anio` (`mes`,`anio`);

--
-- Indices de la tabla `patente_asociada`
--
ALTER TABLE `patente_asociada`
  ADD PRIMARY KEY (`id_cliente`,`patente`);

--
-- Indices de la tabla `precios`
--
ALTER TABLE `precios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id_ingresos`,`fecha_salida`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_salidas_fecha` (`fecha_salida`),
  ADD KEY `idx_salidas_metodo` (`metodo_pago`,`tipo_pago`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patente` (`patente`),
  ADD KEY `idx_fecha_ingreso` (`fecha_ingreso`),
  ADD KEY `idx_pagado` (`pagado`),
  ADD KEY `idx_tipo_servicio` (`tipo_servicio`),
  ADD KEY `idx_sincronizado` (`sincronizado`);

--
-- Indices de la tabla `tipo_ingreso`
--
ALTER TABLE `tipo_ingreso`
  ADD PRIMARY KEY (`idtipo_ingresos`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `idadmin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `idclientes` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `configuracion_tuu`
--
ALTER TABLE `configuracion_tuu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `firebase_sync_log`
--
ALTER TABLE `firebase_sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `idautos_estacionados` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lavados_pendientes`
--
ALTER TABLE `lavados_pendientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metas`
--
ALTER TABLE `metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metas_mensuales`
--
ALTER TABLE `metas_mensuales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `precios`
--
ALTER TABLE `precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_ingreso`
--
ALTER TABLE `tipo_ingreso`
  MODIFY `idtipo_ingresos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_tipo_ingreso_fix` FOREIGN KEY (`idtipo_ingresos`) REFERENCES `tipo_ingreso` (`idtipo_ingresos`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD CONSTRAINT `fk_autos_estacionados_tipo_ingresos` FOREIGN KEY (`idtipo_ingreso`) REFERENCES `tipo_ingreso` (`idtipo_ingresos`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
