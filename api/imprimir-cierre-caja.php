<?php
/**
 * API para Imprimir Cierre de Caja
 * Este script actúa como un puente:
 * 1. Recibe una fecha desde el frontend.
 * 2. Busca todos los datos completos del cierre de caja para esa fecha.
 * 3. Envía los datos completos al servicio de impresión.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

// Desactivar errores en pantalla para no corromper la respuesta JSON
ini_set('display_errors', 0);
error_reporting(0);

try {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $usuario = $_POST['usuario'] ?? 'No especificado';

    if (!$fecha) {
        throw new Exception("La fecha es requerida.");
    }

    $fecha_desde = $fecha . ' 00:00:00';
    $fecha_hasta = $fecha . ' 23:59:59';

    // Lógica para obtener los datos del cierre de caja (similar a api_cierre_caja.php)
    $sqlDesglose = "SELECT 
                        COALESCE(s.metodo_pago, 'EFECTIVO') as metodo_pago,
                        COALESCE(s.tipo_pago, 'manual') as tipo_pago,
                        ti.nombre_servicio,
                        SUM(COALESCE(s.total, ti.precio, 0)) as total
                    FROM ingresos i
                    LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
                    JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                    WHERE i.salida = 1
                    AND COALESCE(s.fecha_salida, i.fecha_ingreso) BETWEEN ? AND ?
                    AND ti.es_plan = 0
                    AND ti.idtipo_ingresos NOT IN (19)
                    GROUP BY metodo_pago, tipo_pago, ti.nombre_servicio";

    $stmt = $conexion->prepare($sqlDesglose);
    $stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $resultDesglose = $stmt->get_result();

    $totales = [
        'efectivo_estacionamiento' => 0,
        'tuu_estacionamiento' => 0,
        'efectivo_lavado' => 0,
        'tuu_lavado' => 0,
        'total' => 0
    ];

    while ($row = $resultDesglose->fetch_assoc()) {
        $metodo = strtoupper($row['metodo_pago'] ?? 'EFECTIVO');
        $tipo_pago = $row['tipo_pago'] ?? 'manual';
        $total_fila = floatval($row['total']);
        $es_lavado = stripos($row['nombre_servicio'], 'lavado') !== false;

        $totales['total'] += $total_fila;

        if ($es_lavado) {
            if ($metodo === 'EFECTIVO') {
                $totales['efectivo_lavado'] += $total_fila;
            } else { // TUU, TRANSFERENCIA, etc.
                $totales['tuu_lavado'] += $total_fila;
            }
        } else { // Estacionamiento
            if ($metodo === 'EFECTIVO') {
                $totales['efectivo_estacionamiento'] += $total_fila;
            } else {
                $totales['tuu_estacionamiento'] += $total_fila;
            }
        }
    }
    $stmt->close();

    $datosParaImprimir = [
        'fecha' => date('d-m-Y', strtotime($fecha)),
        'hora' => date('H:i:s'),
        'usuario' => $usuario,
        'efectivo_estacionamiento' => $totales['efectivo_estacionamiento'],
        'tuu_estacionamiento' => $totales['tuu_estacionamiento'],
        'efectivo_lavado' => $totales['efectivo_lavado'],
        'tuu_lavado' => $totales['tuu_lavado'],
        'total' => $totales['total']
    ];

    // Ahora, enviar los datos completos al servicio de impresión
    $payload = json_encode([
        'tipo' => 'cierre_caja',
        'datos' => $datosParaImprimir,
        'impresora' => 'POSESTACIONAMIENTO'
    ]);

    $ch = curl_init('http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $respuestaImpresion = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("Error de cURL al contactar el servicio de impresión: " . $curl_error);
    }

    $respuestaJson = json_decode($respuestaImpresion, true);
    if ($http_code !== 200 || !$respuestaJson || !$respuestaJson['success']) {
        throw new Exception("El servicio de impresión devolvió un error: " . ($respuestaJson['message'] ?? 'Error desconocido'));
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Cierre de caja enviado a la impresora.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conexion->close();
?>