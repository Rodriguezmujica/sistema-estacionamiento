<?php
/**
 * 📝 EJEMPLO: API de calcular cobro CON sistema de debug
 * 
 * Este es un ejemplo de cómo agregar logging a tu código existente.
 * Compara este archivo con el original en api/calcular-cobro.php
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');

// ⭐ PASO 1: Incluir el sistema de logging
require_once '../debug_logger.php';

// ⭐ PASO 2: Registrar que esta API fue llamada
DebugLogger::api("/api/calcular-cobro.php", $_SERVER['REQUEST_METHOD'], $_POST);

// Conexión a la base de datos
$inicio_conexion = microtime(true);
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
$tiempo_conexion = round((microtime(true) - $inicio_conexion) * 1000, 2);

if ($conexion->connect_error) {
    // ⭐ PASO 3: Registrar errores críticos
    DebugLogger::error("Error de conexión a BD: " . $conexion->connect_error);
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// ⭐ PASO 4: Registrar métricas de rendimiento
DebugLogger::debug("Conexión a BD establecida en {$tiempo_conexion}ms");

$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';

if (!$patente) {
    // ⭐ PASO 5: Registrar advertencias
    DebugLogger::warning("Intento de calcular cobro sin patente", $_POST);
    echo json_encode(['success' => false, 'error' => 'Patente no recibida']);
    exit;
}

// ⭐ PASO 6: Registrar información importante
DebugLogger::info("Calculando cobro para patente: $patente");

// Primero, verificar si hay múltiples ingresos pendientes
$sqlCount = "SELECT COUNT(*) as total FROM ingresos WHERE patente = ? AND (salida = 0 OR salida IS NULL)";
$inicio_query = microtime(true);
$stmtCount = $conexion->prepare($sqlCount);
$stmtCount->bind_param('s', $patente);
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$countRow = $resultCount->fetch_assoc();
$totalPendientes = $countRow['total'];
$stmtCount->close();
$tiempo_query = round((microtime(true) - $inicio_query) * 1000, 2);

// ⭐ PASO 7: Registrar consultas SQL con su tiempo
DebugLogger::sql($sqlCount, $tiempo_query);

// ⭐ PASO 8: Registrar información de debug útil
DebugLogger::debug("Ingresos pendientes encontrados: $totalPendientes");

// Buscar el ingreso activo
$sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, i.idtipo_ingreso,
               t.nombre_servicio, t.precio, t.es_plan,
               lp.motivos_extra, lp.descripcion_extra, lp.precio_extra, lp.nombre_cliente
        FROM ingresos i
        JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos
        LEFT JOIN lavados_pendientes lp ON i.idautos_estacionados = lp.id_ingreso
        WHERE i.patente = ? AND (i.salida = 0 OR i.salida IS NULL)
        ORDER BY i.fecha_ingreso DESC LIMIT 1";

$inicio_query = microtime(true);        
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $patente);
$stmt->execute();
$result = $stmt->get_result();
$tiempo_query = round((microtime(true) - $inicio_query) * 1000, 2);

// ⭐ PASO 9: Registrar consulta compleja con su tiempo
DebugLogger::sql("SELECT ingreso con detalles para patente $patente", $tiempo_query);

if ($row = $result->fetch_assoc()) {
    $id = $row['idautos_estacionados'];
    $fechaIngreso = $row['fecha_ingreso'];
    $nombreServicio = $row['nombre_servicio'];
    $precioServicio = $row['precio'];
    $esPlan = $row['es_plan'];
    $precioExtra = $row['precio_extra'] ?: 0;
    $motivosExtra = $row['motivos_extra'];
    $descripcionExtra = $row['descripcion_extra'];
    $nombreCliente = $row['nombre_cliente'];
    
    // ⭐ PASO 10: Registrar datos encontrados
    DebugLogger::debug("Ingreso encontrado", [
        'id' => $id,
        'fecha_ingreso' => $fechaIngreso,
        'servicio' => $nombreServicio,
        'tipo_ingreso' => $row['idtipo_ingreso']
    ]);
    
    // Calcular el costo según el tipo de servicio
    $total = 0;
    $minutos = 0;
    $tipoCalculo = '';
    $idTipoIngreso = $row['idtipo_ingreso'];
    
    // Si es "Error de ingreso"
    if ($idTipoIngreso == 19 || stripos($nombreServicio, 'ERROR DE INGRESO') !== false) {
        $total = 1;
        $tipoCalculo = 'Error de ingreso';
        $precioExtra = 0;
        
        // ⭐ PASO 11: Registrar casos especiales
        DebugLogger::warning("Cobro de error de ingreso detectado para patente $patente");
    }
    // Si es estacionamiento por minuto
    else if ($idTipoIngreso == 18) {
        $ahora = new DateTime('now', new DateTimeZone('America/Santiago'));
        $ingreso = new DateTime($fechaIngreso, new DateTimeZone('America/Santiago'));
        $minutos = ceil(($ahora->getTimestamp() - $ingreso->getTimestamp()) / 60);
        $minutos = max($minutos, 1);
        
        // Obtener precios desde la tabla de configuración
        $sqlPrecios = "SELECT precio_minuto, precio_minuto_minimo FROM precios WHERE id = 1 LIMIT 1";
        $inicio_query = microtime(true);
        $resultPrecios = $conexion->query($sqlPrecios);
        $tiempo_query = round((microtime(true) - $inicio_query) * 1000, 2);
        DebugLogger::sql($sqlPrecios, $tiempo_query);
        
        if ($resultPrecios && $rowPrecios = $resultPrecios->fetch_assoc()) {
            $precioPorMinuto = intval($rowPrecios['precio_minuto']);
            $precioMinimo = intval($rowPrecios['precio_minuto_minimo']);
        } else {
            $precioPorMinuto = 35;
            $precioMinimo = 500;
            
            // ⭐ PASO 12: Advertir cuando se usan valores por defecto
            DebugLogger::warning("Usando precios por defecto (no encontrados en BD)");
        }
        
        $total = max($minutos * $precioPorMinuto, $precioMinimo);
        $tipoCalculo = 'Por minuto';
        
        // ⭐ PASO 13: Registrar cálculos importantes
        DebugLogger::info("Cobro por minutos calculado", [
            'patente' => $patente,
            'minutos' => $minutos,
            'precio_minuto' => $precioPorMinuto,
            'total' => $total
        ]);

        // Actualizar el tipo de ingreso en la BD
        $sqlUpdate = "UPDATE ingresos SET idtipo_ingreso = 18 WHERE idautos_estacionados = ?";
        $inicio_query = microtime(true);
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->bind_param('i', $id);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        $tiempo_query = round((microtime(true) - $inicio_query) * 1000, 2);
        DebugLogger::sql($sqlUpdate, $tiempo_query);
        
        $nombreServicio = 'Estacionamiento por minuto';
        
    } 
    // Si es un plan
    else if ($esPlan == 1) {
        $total = 0;
        $tipoCalculo = 'Plan mensual';
        
        // ⭐ PASO 14: Registrar planes mensuales
        DebugLogger::info("Cliente con plan mensual", [
            'patente' => $patente,
            'cliente' => $nombreCliente
        ]);
        
    }
    // Si es servicio de lavado o precio fijo
    else {
        $total = $precioServicio + $precioExtra;
        $tipoCalculo = 'Precio fijo';
        
        // ⭐ PASO 15: Registrar servicios de lavado
        DebugLogger::info("Servicio de lavado/precio fijo", [
            'patente' => $patente,
            'servicio' => $nombreServicio,
            'precio_base' => $precioServicio,
            'precio_extra' => $precioExtra,
            'total' => $total
        ]);
    }
    
    // Parsear motivos extra
    $motivosArray = [];
    if ($motivosExtra) {
        try {
            $motivosStr = $motivosExtra;
            if (strpos($motivosStr, '\\') !== false) {
                $motivosStr = str_replace(['\\"', '\\'], ['"', ''], $motivosStr);
                $motivosStr = trim($motivosStr, '"');
            }
            $motivosArray = json_decode($motivosStr, true) ?: [];
        } catch (Exception $e) {
            $motivosArray = [];
            
            // ⭐ PASO 16: Registrar excepciones
            DebugLogger::exception($e);
        }
    }
    
    // ⭐ PASO 17: Registrar éxito de operación
    DebugLogger::info("Cobro calculado exitosamente", [
        'patente' => $patente,
        'total' => $total,
        'tipo' => $tipoCalculo,
        'pendientes' => $totalPendientes
    ]);
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'minutos' => $minutos,
        'id' => $id,
        'patente' => $patente,
        'nombre_servicio' => $nombreServicio,
        'tipo_calculo' => $tipoCalculo,
        'fecha_ingreso' => $fechaIngreso,
        'precio_base' => $precioServicio,
        'precio_extra' => $precioExtra,
        'motivos_extra' => $motivosArray,
        'descripcion_extra' => $descripcionExtra,
        'nombre_cliente' => $nombreCliente,
        'total_pendientes' => $totalPendientes,
        'advertencia' => $totalPendientes > 1 ? "⚠️ Esta patente tiene $totalPendientes registros pendientes. Se cobrará el más reciente." : null
    ], JSON_UNESCAPED_UNICODE);
} else {
    // ⭐ PASO 18: Registrar cuando no se encuentra ingreso
    DebugLogger::warning("No se encontró ingreso activo para patente: $patente");
    
    echo json_encode(['success' => false, 'error' => 'No se encontró ingreso activo para esa patente']);
}

$stmt->close();
$conexion->close();

// ⭐ PASO 19: Registrar tiempo total de ejecución
$tiempo_total = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
DebugLogger::debug("API calcular-cobro completada en {$tiempo_total}ms");
?>

<?php
/*
╔════════════════════════════════════════════════════════════════╗
║   📊 RESUMEN DE MEJORAS CON DEBUG                          ║
╚════════════════════════════════════════════════════════════════╝

✅ VENTAJAS de agregar logging:

1. TRAZABILIDAD
   - Sabes exactamente qué usuario hizo qué y cuándo
   - Puedes rastrear el flujo completo de una operación

2. RENDIMIENTO
   - Detectas qué consultas son lentas
   - Optimizas las partes críticas

3. DEBUGGING
   - Encuentras errores más rápido
   - No necesitas "adivinar" qué está fallando

4. AUDITORÍA
   - Historial completo de operaciones
   - Pruebas para resolver disputas

5. MONITOREO
   - Ves en tiempo real qué está pasando
   - Detectas problemas antes que los usuarios

═══════════════════════════════════════════════════════════════

📝 CÓMO USAR ESTE EJEMPLO:

1. Compara este archivo con el original (api/calcular-cobro.php)

2. Nota las líneas con "⭐ PASO X"

3. Aplica el mismo patrón a tus otros archivos

4. Ejecuta este archivo y revisa los logs en:
   http://localhost/sistemaEstacionamiento/ver_logs.php

═══════════════════════════════════════════════════════════════

🎯 PATRONES CLAVE:

✓ Inicio de operación: DebugLogger::info()
✓ Consultas SQL: DebugLogger::sql() con tiempo
✓ Errores: DebugLogger::error()
✓ Advertencias: DebugLogger::warning()
✓ Datos de debug: DebugLogger::debug()
✓ Excepciones: DebugLogger::exception()

═══════════════════════════════════════════════════════════════
*/
?>


