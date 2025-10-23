<?php
/**
 * UTILIDADES DE REDONDEO SEGÚN LEY CHILENA
 * 
 * Este archivo contiene funciones para redondear montos
 * según las reglas oficiales del Banco Central de Chile.
 * 
 * En Chile no existe moneda de 5 pesos desde 1991.
 * Reglas oficiales:
 * - 1-4 pesos: redondea hacia abajo (al 0)
 * - 5-9 pesos: redondea hacia arriba (al 10)
 */

/**
 * Redondea un monto según la ley chilena
 * 
 * @param float $monto El monto a redondear
 * @return int El monto redondeado según las reglas chilenas
 */
function redondearSegunLeyChilena($monto) {
    // Convertir a entero para trabajar con centavos
    $montoCentavos = intval($monto);
    
    // Obtener las unidades (último dígito)
    $unidades = $montoCentavos % 10;
    
    if ($unidades >= 1 && $unidades <= 4) {
        // Redondea hacia abajo (al 0)
        return $montoCentavos - $unidades;
    } elseif ($unidades >= 5 && $unidades <= 9) {
        // Redondea hacia arriba (al 10)
        return $montoCentavos + (10 - $unidades);
    } else {
        // Ya está en múltiplo de 10
        return $montoCentavos;
    }
}

/**
 * Calcula y redondea el total de un servicio según la ley chilena
 * 
 * @param float $precioBase Precio base del servicio
 * @param float $precioExtra Precio extra (opcional, default 0)
 * @return int Total redondeado según ley chilena
 */
function calcularTotalConRedondeoChile($precioBase, $precioExtra = 0) {
    $totalBase = floatval($precioBase) + floatval($precioExtra);
    return redondearSegunLeyChilena($totalBase);
}

/**
 * Ejemplos de uso y validación:
 * 
 * redondearSegunLeyChilena(1337) = 1340  // 7 -> 10
 * redondearSegunLeyChilena(1334) = 1330  // 4 -> 0  
 * redondearSegunLeyChilena(1330) = 1330  // 0 -> sin cambio
 * redondearSegunLeyChilena(35)   = 40    // 5 -> 10 (precio por minuto)
 * redondearSegunLeyChilena(34)   = 30    // 4 -> 0
 */
?>
