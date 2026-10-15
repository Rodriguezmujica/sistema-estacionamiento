<?php
/**
 * Configuración Centralizada de Zona Horaria
 * 
 * Este archivo configura la zona horaria de Chile (America/Santiago)
 * que maneja automáticamente el cambio de horario de verano/invierno
 * 
 * Incluir este archivo al inicio de todos los scripts PHP
 */

// Configurar zona horaria para Chile
date_default_timezone_set('America/Santiago');

/**
 * NOTAS IMPORTANTES:
 * 
 * 1. "America/Santiago" es la zona horaria oficial de Chile
 * 2. Maneja automáticamente el cambio de horario de verano (octubre) e invierno (abril)
 * 3. NO usar "Chile/Continental" (está deprecated desde PHP 5.3)
 * 
 * Cambios de Horario en Chile:
 * - Primer sábado de abril: -1 hora (horario de invierno UTC-4)
 * - Primer sábado de septiembre: +1 hora (horario de verano UTC-3)
 * 
 * MySQL:
 * - Asegúrate de que MySQL también esté en la misma zona horaria
 * - Ejecuta: SET time_zone = 'America/Santiago';
 * - O configura en my.ini: default-time-zone='+00:00' y maneja en PHP
 */

// Obtener información de zona horaria actual (para debug)
$tz = new DateTimeZone('America/Santiago');
$now = new DateTime('now', $tz);
$offset = $tz->getOffset($now);
$offset_hours = $offset / 3600;

// Header para debugging (comentar en producción)
// header("X-Timezone: America/Santiago");
// header("X-Offset: UTC" . ($offset_hours >= 0 ? '+' : '') . $offset_hours);

?>

