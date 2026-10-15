-- =====================================================
-- SCRIPT CONSOLIDADO: TABLAS NUEVAS SISTEMA HÍBRIDO
-- Sistema de Estacionamiento Los Ríos
-- =====================================================

-- IMPORTANTE: Ejecutar este script en ambas máquinas (Antix y Windows 7)
-- para que tengan la misma estructura de base de datos

-- =====================================================
-- 1. ACTUALIZAR TABLA SALIDAS (Campos TUU + Lavados)
-- =====================================================

-- Campos para TUU (Pagos con tarjeta)
ALTER TABLE `salidas` 
ADD COLUMN IF NOT EXISTS `metodo_pago` VARCHAR(50) DEFAULT 'EFECTIVO' COMMENT 'EFECTIVO o TUU',
ADD COLUMN IF NOT EXISTS `metodo_tarjeta` VARCHAR(50) NULL COMMENT 'credito, debito, efectivo',
ADD COLUMN IF NOT EXISTS `tipo_documento` VARCHAR(50) DEFAULT 'boleta' COMMENT 'boleta o factura',
ADD COLUMN IF NOT EXISTS `rut_cliente` VARCHAR(20) NULL COMMENT 'RUT del cliente para facturas',
ADD COLUMN IF NOT EXISTS `transaction_id` VARCHAR(100) NULL COMMENT 'ID de transacción TUU',
ADD COLUMN IF NOT EXISTS `authorization_code` VARCHAR(100) NULL COMMENT 'Código de autorización TUU',
ADD COLUMN IF NOT EXISTS `card_type` VARCHAR(50) NULL COMMENT 'Tipo de tarjeta (VISA, MASTERCARD, etc)',
ADD COLUMN IF NOT EXISTS `card_last4` VARCHAR(4) NULL COMMENT 'Últimos 4 dígitos de la tarjeta',
ADD COLUMN IF NOT EXISTS `tipo_pago` ENUM('tuu', 'manual') DEFAULT 'manual' COMMENT 'Tipo de pago para auditoría';

-- Campos para Lavados (Motivos extra)
ALTER TABLE `salidas` 
ADD COLUMN IF NOT EXISTS `motivos_extra` TEXT NULL COMMENT 'JSON array con motivos de cobro extra',
ADD COLUMN IF NOT EXISTS `descripcion_extra` TEXT NULL COMMENT 'Descripción adicional',
ADD COLUMN IF NOT EXISTS `precio_extra` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Precio adicional';

-- Campos para Sincronización Firebase
ALTER TABLE `salidas` 
ADD COLUMN IF NOT EXISTS `sincronizado` TINYINT(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado',
ADD COLUMN IF NOT EXISTS `pc_origen` VARCHAR(20) NULL COMMENT 'PC que registró la salida',
ADD COLUMN IF NOT EXISTS `fecha_sincronizacion` TIMESTAMP NULL COMMENT 'Fecha de sincronización con Firebase';

-- Índices para optimización
CREATE INDEX IF NOT EXISTS `idx_transaction_id` ON `salidas`(`transaction_id`);
CREATE INDEX IF NOT EXISTS `idx_sincronizado` ON `salidas`(`sincronizado`);
CREATE INDEX IF NOT EXISTS `idx_pc_origen` ON `salidas`(`pc_origen`);

-- =====================================================
-- 2. ACTUALIZAR TABLA INGRESOS (Sincronización)
-- =====================================================

-- Campos para Sincronización Firebase
ALTER TABLE `ingresos` 
ADD COLUMN IF NOT EXISTS `sincronizado` TINYINT(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado',
ADD COLUMN IF NOT EXISTS `pc_origen` VARCHAR(20) NULL COMMENT 'PC que registró el ingreso',
ADD COLUMN IF NOT EXISTS `fecha_sincronizacion` TIMESTAMP NULL COMMENT 'Fecha de sincronización con Firebase';

-- Índices para optimización
CREATE INDEX IF NOT EXISTS `idx_ingresos_sincronizado` ON `ingresos`(`sincronizado`);
CREATE INDEX IF NOT EXISTS `idx_ingresos_pc_origen` ON `ingresos`(`pc_origen`);

-- =====================================================
-- 3. CREAR TABLA METAS MENSUALES
-- =====================================================

CREATE TABLE IF NOT EXISTS `metas_mensuales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mes` INT NOT NULL COMMENT 'Mes (1-12)',
  `anio` INT NOT NULL COMMENT 'Año (ej: 2025)',
  `meta_monto` DECIMAL(10,2) NOT NULL COMMENT 'Meta en pesos',
  `solo_dias_laborales` TINYINT(1) DEFAULT 1 COMMENT '1 = solo lun-vie, 0 = todos los días',
  `incluir_mensuales` TINYINT(1) DEFAULT 0 COMMENT '1 = incluir clientes mensuales, 0 = solo servicios diarios',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `usuario_creador` VARCHAR(50) DEFAULT NULL,
  UNIQUE KEY `mes_anio` (`mes`, `anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar meta de ejemplo para el mes actual
INSERT INTO `metas_mensuales` (`mes`, `anio`, `meta_monto`, `solo_dias_laborales`, `incluir_mensuales`) 
VALUES (MONTH(NOW()), YEAR(NOW()), 5000000, 1, 0)
ON DUPLICATE KEY UPDATE meta_monto = meta_monto;

-- =====================================================
-- 4. CREAR TABLA LAVADOS PENDIENTES
-- =====================================================

CREATE TABLE IF NOT EXISTS `lavados_pendientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ingreso` int(11) NOT NULL,
  `patente` varchar(10) NOT NULL,
  `motivos_extra` TEXT NULL,
  `descripcion_extra` TEXT NULL,
  `precio_extra` DECIMAL(10,2) DEFAULT 0.00,
  `nombre_cliente` VARCHAR(100) NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sincronizado` TINYINT(1) DEFAULT 0 COMMENT '0=No sincronizado, 1=Sincronizado',
  `pc_origen` VARCHAR(20) NULL COMMENT 'PC que registró el lavado',
  PRIMARY KEY (`id`),
  KEY `idx_id_ingreso` (`id_ingreso`),
  KEY `idx_patente` (`patente`),
  KEY `idx_sincronizado` (`sincronizado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. CREAR TABLA CONFIGURACIÓN SISTEMA
-- =====================================================

CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NULL,
  `descripcion` TEXT NULL,
  `tipo` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usuario_actualizacion` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar configuraciones por defecto
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`, `tipo`) VALUES
('firebase_sync_enabled', '1', 'Habilitar sincronización con Firebase', 'boolean'),
('firebase_sync_interval', '30', 'Intervalo de sincronización en segundos', 'number'),
('pc_id', 'PC_UNKNOWN', 'Identificador de la PC actual', 'string'),
('impresora_disponible', '0', 'Indica si esta PC tiene impresora', 'boolean'),
('modo_offline', '0', 'Modo offline activado', 'boolean')
ON DUPLICATE KEY UPDATE valor = valor;

-- =====================================================
-- 6. CREAR TABLA LOGS SINCRONIZACIÓN
-- =====================================================

CREATE TABLE IF NOT EXISTS `logs_sincronizacion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_operacion` ENUM('ingreso', 'salida', 'lavado', 'configuracion') NOT NULL,
  `id_registro` INT NULL,
  `pc_origen` VARCHAR(20) NOT NULL,
  `pc_destino` VARCHAR(20) NULL,
  `estado` ENUM('pendiente', 'enviado', 'recibido', 'procesado', 'error') DEFAULT 'pendiente',
  `datos` JSON NULL,
  `error` TEXT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_procesamiento` TIMESTAMP NULL,
  KEY `idx_tipo_operacion` (`tipo_operacion`),
  KEY `idx_estado` (`estado`),
  KEY `idx_pc_origen` (`pc_origen`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. VERIFICAR ESTRUCTURA
-- =====================================================

-- Mostrar estructura de tablas actualizadas
DESCRIBE `salidas`;
DESCRIBE `ingresos`;
DESCRIBE `metas_mensuales`;
DESCRIBE `lavados_pendientes`;
DESCRIBE `configuracion_sistema`;
DESCRIBE `logs_sincronizacion`;

-- Mostrar resumen de cambios
SELECT 
    'Tablas actualizadas correctamente' as mensaje,
    NOW() as fecha_ejecucion;

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- 1. Este script es compatible con MySQL 5.7+ y MariaDB
-- 2. Usa IF NOT EXISTS para evitar errores si las columnas ya existen
-- 3. Ejecutar en ambas máquinas (Antix y Windows 7)
-- 4. Los índices mejoran el rendimiento de las consultas
-- 5. La tabla logs_sincronizacion permite monitorear el sistema híbrido
-- =====================================================
