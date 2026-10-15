-- SQL para agregar campos de TUU a la tabla salidas
-- Ejecutar este script cuando tengas acceso a la máquina TUU

-- Agregar campos para registrar datos de TUU
ALTER TABLE `salidas` 
ADD COLUMN `metodo_pago` VARCHAR(50) DEFAULT 'EFECTIVO' COMMENT 'EFECTIVO o TUU',
ADD COLUMN `transaction_id` VARCHAR(100) NULL COMMENT 'ID de transacción TUU',
ADD COLUMN `authorization_code` VARCHAR(100) NULL COMMENT 'Código de autorización TUU',
ADD COLUMN `card_type` VARCHAR(50) NULL COMMENT 'Tipo de tarjeta (VISA, MASTERCARD, etc)',
ADD COLUMN `card_last4` VARCHAR(4) NULL COMMENT 'Últimos 4 dígitos de la tarjeta';

-- Agregar índice para búsquedas por transaction_id
CREATE INDEX idx_transaction_id ON salidas(transaction_id);

