-- Agregar campos para motivos de cobro extra en lavados
-- Ejecutar este script para agregar los campos necesarios a la tabla 'salidas'

-- Agregar columna 'motivos_extra' después de 'total'
ALTER TABLE `salidas`
ADD COLUMN `motivos_extra` TEXT NULL AFTER `total`;

-- Agregar columna 'descripcion_extra' después de 'motivos_extra'
ALTER TABLE `salidas`
ADD COLUMN `descripcion_extra` TEXT NULL AFTER `motivos_extra`;

-- Agregar columna 'precio_extra' después de 'descripcion_extra'
ALTER TABLE `salidas`
ADD COLUMN `precio_extra` DECIMAL(10,2) DEFAULT 0.00 AFTER `descripcion_extra`;

-- Comentarios sobre los nuevos campos:
-- motivos_extra: JSON array con los motivos seleccionados (ej: ["hongos", "barro"])
-- descripcion_extra: Texto libre con descripción adicional
-- precio_extra: Monto adicional cobrado por motivos especiales
