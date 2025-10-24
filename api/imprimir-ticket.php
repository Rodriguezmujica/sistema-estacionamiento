<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

try {
    $tipo = $_POST['tipo'] ?? ''; // 'ingreso' o 'salida'
    $id = $_POST['id'] ?? '';
    
    if (empty($tipo) || empty($id)) {
        throw new Exception('Tipo e ID son requeridos');
    }
    
    if ($tipo === 'ingreso') {
        // Obtener datos del ingreso
        $sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, 
                       ti.nombre_servicio
                FROM ingresos i
                JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                WHERE i.idautos_estacionados = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Preparar datos para impresión de ingreso usando print-service-php
            $fecha_ingreso = new DateTime($row['fecha_ingreso']);
            $datos_impresion = [
                'patente' => $row['patente'],
                'tipo_vehiculo' => 'Vehículo',
                'ticket_id' => $row['idautos_estacionados'],
                'fecha_ingreso' => $fecha_ingreso->format('d-m-Y'),
                'hora_ingreso' => $fecha_ingreso->format('H:i:s')
            ];
            
        } else {
            throw new Exception('Ingreso no encontrado');
        }
        
    } elseif ($tipo === 'salida') {
        // Obtener datos de la salida
        $sql = "SELECT s.id_ingresos, s.fecha_salida, s.total, s.metodo_pago,
                       i.patente, i.fecha_ingreso,
                       ti.nombre_servicio
                FROM salidas s
                JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
                JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                WHERE s.id_ingresos = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Preparar datos para impresión de salida usando print-service-php
            $fecha_ingreso = new DateTime($row['fecha_ingreso']);
            $fecha_salida = new DateTime($row['fecha_salida']);
            
            // Calcular tiempo de estadía
            $intervalo = $fecha_ingreso->diff($fecha_salida);
            $tiempo_estadia = $intervalo->format('%H:%I:%S');
            
            $datos_impresion = [
                'patente' => $row['patente'],
                'fecha_ingreso' => $fecha_ingreso->format('d-m-Y H:i:s'),
                'fecha_salida' => $fecha_salida->format('d-m-Y H:i:s'),
                'tiempo_estadia' => $tiempo_estadia,
                'monto' => $row['total'],
                'metodo_pago' => $row['metodo_pago'] ?: 'MANUAL'
            ];
            
        } else {
            throw new Exception('Salida no encontrada');
        }
    } else {
        throw new Exception('Tipo de ticket inválido');
    }
    
    // Llamar al servicio de impresión PHP
    $url_impresion = 'http://localhost:8080/sistemaEstacionamiento/print-service-php/imprimir.php';
    
    // Preparar datos en el formato correcto que espera imprimir.php
    $payload = [
        'tipo' => $tipo,
        'datos' => $datos_impresion,
        'impresora' => 'POSESTACIONAMIENTO'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_impresion);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception('Error de conexión: ' . $curl_error);
    }
    
    if ($http_code === 200) {
        $resultado = json_decode($response, true);
        if ($resultado && $resultado['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Ticket impreso correctamente',
                'tipo' => $tipo
            ]);
        } else {
            // El servicio responde pero hay un error de impresión (impresora no disponible, etc.)
            $mensaje_error = $resultado['message'] ?? $resultado['error'] ?? 'Error desconocido en la impresión';
            echo json_encode([
                'success' => false,
                'error' => 'Error de impresión: ' . $mensaje_error,
                'tipo' => $tipo
            ]);
        }
    } else {
        throw new Exception("Error HTTP $http_code: " . $response);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
