<?php
/**
 * API para Re-imprimir Tickets
 * Este script actúa como un puente:
 * 1. Recibe un ID de ticket desde el frontend.
 * 2. Busca todos los datos completos de ese ticket en la base de datos.
 * 3. Envía los datos completos al servicio de impresión.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

// Desactivar errores en pantalla para no corromper la respuesta JSON
ini_set('display_errors', 0);
error_reporting(0);

try {
    $tipo = $_POST['tipo'] ?? null;
    $id = $_POST['id'] ?? null;

    if (!$tipo || !$id) {
        throw new Exception("Tipo de ticket e ID son requeridos.");
    }

    $id = intval($id);
    $datosParaImprimir = [];
    $nombreImpresora = 'POSESTACIONAMIENTO'; // Nombre por defecto para Windows

    if ($tipo === 'ingreso') {
        // Buscar datos del ticket de ingreso
        $sql = "SELECT i.patente, ti.nombre_servicio, i.fecha_ingreso
                FROM ingresos i
                JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                WHERE i.idautos_estacionados = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($fila = $resultado->fetch_assoc()) {
            $fecha = new DateTime($fila['fecha_ingreso']);
            $datosParaImprimir = [
                'patente'       => $fila['patente'],
                'tipo_vehiculo' => $fila['nombre_servicio'],
                'ticket_id'     => $id,
                'fecha_ingreso' => $fecha->format('d-m-Y'),
                'hora_ingreso'  => $fecha->format('H:i:s')
            ];
        } else {
            throw new Exception("No se encontró el ticket de ingreso con ID: $id");
        }
        $stmt->close();

    } elseif ($tipo === 'salida') {
        // Buscar datos del ticket de salida
        $sql = "SELECT i.patente, i.fecha_ingreso, s.fecha_salida, s.total, s.metodo_pago
                FROM salidas s
                JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                WHERE s.id_ingresos = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            $ingreso = new DateTime($fila['fecha_ingreso']);
            $salida = new DateTime($fila['fecha_salida']);
            $intervalo = $ingreso->diff($salida);
            $tiempoEstadia = $intervalo->format('%h h %i min');

            $datosParaImprimir = [
                'ticket_id'     => $id,
                'patente'       => $fila['patente'],
                'fecha_ingreso' => $ingreso->format('d-m-Y H:i'),
                'fecha_salida'  => $salida->format('d-m-Y H:i'),
                'tiempo_estadia'=> $tiempoEstadia,
                'monto'         => $fila['total'],
                'metodo_pago'   => $fila['metodo_pago']
            ];
        } else {
            throw new Exception("No se encontró el ticket de salida con ID: $id");
        }
        $stmt->close();

    } else {
        throw new Exception("Tipo de ticket no válido: $tipo");
    }

    // Ahora, enviar los datos completos al servicio de impresión
    $payload = json_encode([
        'tipo' => $tipo,
        'datos' => $datosParaImprimir,
        'impresora' => $nombreImpresora
    ]);

    // Usar cURL para hacer la petición POST al servicio de impresión
    $ch = curl_init('http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos

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

    echo json_encode(['success' => true, 'message' => "Ticket de $tipo enviado a la impresora."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conexion->close();
?>