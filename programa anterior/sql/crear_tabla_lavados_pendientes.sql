-- Crear tabla para guardar información de lavados pendientes de cobro
-- Esta tabla almacena los motivos extra y descripción hasta que se cobre el lavado

CREATE TABLE IF NOT EXISTS `lavados_pendientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ingreso` int(11) NOT NULL,
  `patente` varchar(10) NOT NULL,
  `motivos_extra` TEXT NULL,
  `descripcion_extra` TEXT NULL,
  `precio_extra` DECIMAL(10,2) DEFAULT 0.00,
  `nombre_cliente` VARCHAR(100) NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_id_ingreso` (`id_ingreso`),
  KEY `idx_patente` (`patente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comentarios sobre la tabla:
-- id_ingreso: Referencia al registro en la tabla 'ingresos'
-- patente: Patente del vehículo
-- motivos_extra: JSON array con los motivos seleccionados
-- descripcion_extra: Texto libre con descripción adicional
-- precio_extra: Monto adicional cobrado por motivos especiales
-- nombre_cliente: Nombre del cliente (opcional)
-- fecha_registro: Fecha y hora del registro
