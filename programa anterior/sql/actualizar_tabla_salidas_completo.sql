-- Script consolidado para actualizar la tabla salidas
-- Agrega todas las columnas necesarias para el sistema de cobro
-- Compatible con MySQL 5.x y superiores

-- NOTA: Si alguna columna ya existe, verás un error "Duplicate column name"
-- Esto es normal y puedes ignorarlo. El script PHP maneja esto automáticamente.

-- Agregar campos para TUU (uno por uno para compatibilidad)
ALTER TABLE `salidas` ADD COLUMN `metodo_pago` VARCHAR(50) DEFAULT 'EFECTIVO' COMMENT 'EFECTIVO o TUU';

ALTER TABLE `salidas` ADD COLUMN `metodo_tarjeta` VARCHAR(50) NULL COMMENT 'credito, debito, efectivo';

ALTER TABLE `salidas` ADD COLUMN `tipo_documento` VARCHAR(50) DEFAULT 'boleta' COMMENT 'boleta o factura';

ALTER TABLE `salidas` ADD COLUMN `rut_cliente` VARCHAR(20) NULL COMMENT 'RUT del cliente para facturas';

ALTER TABLE `salidas` ADD COLUMN `transaction_id` VARCHAR(100) NULL COMMENT 'ID de transacción TUU';

ALTER TABLE `salidas` ADD COLUMN `authorization_code` VARCHAR(100) NULL COMMENT 'Código de autorización TUU';

ALTER TABLE `salidas` ADD COLUMN `card_type` VARCHAR(50) NULL COMMENT 'Tipo de tarjeta (VISA, MASTERCARD, etc)';

ALTER TABLE `salidas` ADD COLUMN `card_last4` VARCHAR(4) NULL COMMENT 'Últimos 4 dígitos de la tarjeta';

-- Agregar campos para motivos extra de lavados
ALTER TABLE `salidas` ADD COLUMN `motivos_extra` TEXT NULL COMMENT 'JSON array con motivos de cobro extra';

ALTER TABLE `salidas` ADD COLUMN `descripcion_extra` TEXT NULL COMMENT 'Descripción adicional';

ALTER TABLE `salidas` ADD COLUMN `precio_extra` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Precio adicional';

-- Mensaje de confirmación
SELECT 'Tabla salidas actualizada correctamente' as mensaje;

