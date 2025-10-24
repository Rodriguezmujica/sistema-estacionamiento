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
            $datos_impresion = [
                'tipo' => 'ingreso',
                'nombre_cliente' => 'Cliente General',
                'servicio_cliente' => $row['nombre_servicio'],
                'patente' => $row['patente'],
                'tipo_ingreso' => $row['nombre_servicio'],
                'fecha_ingreso' => $row['fecha_ingreso']
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
            $datos_impresion = [
                'tipo' => 'salida',
                'hora_ingreso' => $row['fecha_ingreso'],
                'hora_egreso' => $row['fecha_salida'],
                'total' => $row['total'],
                'patente' => $row['patente'],
                'metodo_pago' => $row['metodo_pago'] ?: 'MANUAL',
                'nombre_cliente' => 'Cliente General'
            ];
            
        } else {
            throw new Exception('Salida no encontrada');
        }
    } else {
        throw new Exception('Tipo de ticket inválido');
    }
    
    // Llamar al servicio de impresión PHP
    $url_impresion = 'http://localhost:8080/sistemaEstacionamiento/print-service-php/imprimir.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_impresion);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos_impresion));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
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
