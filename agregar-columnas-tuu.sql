-- =====================================================
-- SCRIPT PARA AGREGAR COLUMNAS TUU A LA TABLA INGRESOS
-- Sistema de Estacionamiento Los Ríos
-- Fecha: 22-10-2025
-- =====================================================

-- =====================================================
-- INSTRUCCIONES DE INSTALACIÓN
-- =====================================================

-- 🐧 ANTIX (PC1) - Servidor Principal:
-- 1. Copiar este archivo a Antix
-- 2. Ejecutar: mysql -u root -p < agregar-columnas-tuu.sql
-- 3. O ejecutar línea por línea en MySQL:
--    mysql -u root -p
--    USE estacionamiento;
--    [copiar y pegar los comandos ALTER TABLE]

-- 🪟 WINDOWS 7 (PC2) - PC de Producción:
-- Opción 1 - phpMyAdmin:
-- 1. Abrir: http://localhost/phpmyadmin
-- 2. Seleccionar base de datos "estacionamiento"
-- 3. Ir a pestaña "Importar"
-- 4. Seleccionar este archivo agregar-columnas-tuu.sql
-- 5. Hacer clic en "Continuar"

-- Opción 2 - SQL directo en phpMyAdmin:
-- 1. Abrir: http://localhost/phpmyadmin
-- 2. Seleccionar base de datos "estacionamiento"
-- 3. Ir a pestaña "SQL"
-- 4. Copiar y pegar los comandos ALTER TABLE de abajo
-- 5. Hacer clic en "Continuar"

-- =====================================================
-- COMANDOS SQL PARA EJECUTAR
-- =====================================================

-- Seleccionar la base de datos
USE estacionamiento;

-- Verificar estructura actual
DESCRIBE ingresos;

-- Agregar columna para ID de transacción TUU
ALTER TABLE ingresos ADD COLUMN transaction_id_tuu VARCHAR(50) NULL;

-- Agregar columna para total calculado por TUU
ALTER TABLE ingresos ADD COLUMN total_calculado_tuu DECIMAL(10,2) NULL;

-- Agregar columna para fecha de intento de pago TUU
ALTER TABLE ingresos ADD COLUMN fecha_intento_tuu DATETIME NULL;

-- Verificar estructura final
DESCRIBE ingresos;

-- Mostrar mensaje de confirmación
SELECT 'Columnas TUU agregadas exitosamente' AS resultado;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================
-- Después de ejecutar, deberías ver estas columnas nuevas:
-- - transaction_id_tuu (VARCHAR(50))
-- - total_calculado_tuu (DECIMAL(10,2))
-- - fecha_intento_tuu (DATETIME)
-- =====================================================
