-- =====================================================
-- Script: Agregar campo tipo_pago a tabla salidas
-- Descripción: Permite diferenciar entre boletas TUU oficiales
--              y comprobantes internos manuales
-- =====================================================

-- 1. Agregar el campo tipo_pago
ALTER TABLE `salidas` 
ADD COLUMN `tipo_pago` ENUM('tuu', 'manual') DEFAULT 'manual' 
AFTER `metodo_pago`;

-- 2. Actualizar registros existentes según el método de pago
-- Los que tienen metodo_pago = 'TUU' se marcan como tipo_pago = 'tuu'
UPDATE `salidas` 
SET `tipo_pago` = 'tuu' 
WHERE `metodo_pago` = 'TUU';

-- Los demás se mantienen como 'manual' (el default)
UPDATE `salidas` 
SET `tipo_pago` = 'manual' 
WHERE `metodo_pago` != 'TUU' OR `metodo_pago` IS NULL;

-- 3. Verificar los cambios
SELECT 
    metodo_pago,
    tipo_pago,
    COUNT(*) as cantidad
FROM salidas
GROUP BY metodo_pago, tipo_pago
ORDER BY metodo_pago;

-- =====================================================
-- NOTA: Este campo permitirá:
-- - Diferenciar boletas oficiales de comprobantes internos
-- - Generar reportes separados para auditoría
-- - Identificar ingresos por error o en modo test
-- =====================================================

