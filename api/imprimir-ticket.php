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
        $sql = "SELECT i.idautos_estacionados, i.patente, i.fecha_ingreso, i.hora_ingreso, 
                       i.nombre_cliente, ti.nombre_servicio
                FROM ingresos i
                JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                WHERE i.idautos_estacionados = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Preparar datos para impresión de ingreso
            $datos_impresion = [
                'nombre_cliente' => $row['nombre_cliente'] ?: 'Cliente General',
                'servicio_cliente' => $row['nombre_servicio'],
                'patente' => $row['patente'],
                'tipo_ingreso' => $row['nombre_servicio'],
                'fecha_ingreso' => $row['fecha_ingreso'],
                'hora_ingreso' => $row['hora_ingreso']
            ];
            
            // Llamar al script de impresión de ingreso
            $url_impresion = '../ImpresionTermica/ticket.php';
            
        } else {
            throw new Exception('Ingreso no encontrado');
        }
        
    } elseif ($tipo === 'salida') {
        // Obtener datos de la salida
        $sql = "SELECT s.id_ingresos, s.fecha_salida, s.hora_salida, s.total, s.metodo_pago,
                       i.patente, i.fecha_ingreso, i.hora_ingreso, i.nombre_cliente,
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
            // Preparar datos para impresión de salida
            $datos_impresion = [
                'hora_ingreso' => $row['hora_ingreso'],
                'hora_egreso' => $row['hora_salida'],
                'total' => $row['total'],
                'patente' => $row['patente'],
                'metodo_pago' => $row['metodo_pago'] ?: 'MANUAL',
                'nombre_cliente' => $row['nombre_cliente'] ?: 'Cliente General'
            ];
            
            // Llamar al script de impresión de salida
            $url_impresion = '../ImpresionTermica/ticketsalida.php';
            
        } else {
            throw new Exception('Salida no encontrada');
        }
    } else {
        throw new Exception('Tipo de ticket inválido');
    }
    
    // Realizar la impresión usando cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_impresion);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos_impresion));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo json_encode([
            'success' => true,
            'message' => 'Ticket impreso correctamente',
            'tipo' => $tipo
        ]);
    } else {
        throw new Exception('Error al imprimir el ticket');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
