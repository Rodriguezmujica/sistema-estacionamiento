-- =====================================================
-- Script: Crear tabla de metas mensuales
-- Descripción: Permite al admin definir metas de ingreso
--              mensual (solo días laborales lun-vie)
-- =====================================================

-- 1. Crear tabla de metas
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

-- 2. Insertar meta de ejemplo para el mes actual
INSERT INTO `metas_mensuales` (`mes`, `anio`, `meta_monto`, `solo_dias_laborales`, `incluir_mensuales`) 
VALUES (MONTH(NOW()), YEAR(NOW()), 5000000, 1, 0)
ON DUPLICATE KEY UPDATE meta_monto = meta_monto; -- No sobrescribir si ya existe

-- 3. Verificar
SELECT * FROM metas_mensuales;

-- =====================================================
-- NOTAS:
-- - Meta de $5.000.000 es un ejemplo, cambiar según necesidad
-- - solo_dias_laborales = 1 → cuenta solo lunes a viernes
-- - incluir_mensuales = 0 → NO cuenta clientes mensuales en la meta
-- =====================================================

