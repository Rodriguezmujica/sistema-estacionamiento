<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
header('Content-Type: application/json');

/**
 * API de Pago Manual (Comprobante Interno)
 * 
 * Este endpoint procesa pagos sin conexión a TUU.
 * Se usa cuando:
 * - TUU está caído o sin internet
 * - Es un ingreso por error
 * - Se quiere hacer una prueba/simulación
 * 
 * Genera un comprobante interno sin boleta oficial
 */

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "estacionamiento");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Obtener datos del POST
$id_ingreso = isset($_POST['id_ingreso']) ? intval($_POST['id_ingreso']) : 0;
$patente = isset($_POST['patente']) ? strtoupper(trim($_POST['patente'])) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;
$metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'EFECTIVO';
$motivo_manual = isset($_POST['motivo_manual']) ? trim($_POST['motivo_manual']) : 'Normal';

// Validar datos
if ($id_ingreso <= 0 || empty($patente) || $total < 0) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos']);
    exit;
}

date_default_timezone_set('America/Santiago');
$fecha_salida = date('Y-m-d H:i:s');

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Verificar tipo de ingreso (por si es Error de Ingreso)
    $sql_tipo = "SELECT idtipo_ingreso FROM ingresos WHERE idautos_estacionados = ?";
    $stmt_tipo = $conexion->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $id_ingreso);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    $tipo = $result_tipo->fetch_assoc();
    $stmt_tipo->close();

    // Si es "Error de ingreso" (ID 19), forzar total a $1
    if ($tipo && intval($tipo['idtipo_ingreso']) === 19) {
        $total = 1;
    }

    // 2. Obtener información del lavado pendiente (si existe)
    $motivos_extra = null;
    $descripcion_extra = null;
    $precio_extra = 0;
    
    $sql_pendiente = "SELECT motivos_extra, descripcion_extra, precio_extra FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_pendiente = $conexion->prepare($sql_pendiente);
    $stmt_pendiente->bind_param("i", $id_ingreso);
    $stmt_pendiente->execute();
    $result_pendiente = $stmt_pendiente->get_result();
    
    if ($lavado_pendiente = $result_pendiente->fetch_assoc()) {
        $motivos_extra = $lavado_pendiente['motivos_extra'];
        $descripcion_extra = $lavado_pendiente['descripcion_extra'];
        $precio_extra = floatval($lavado_pendiente['precio_extra']);
    }
    $stmt_pendiente->close();

    // 3. Insertar en tabla salidas (con tipo_pago = 'manual')
    $columnas = ['id_ingresos', 'fecha_salida', 'total', 'metodo_pago', 'tipo_pago'];
    $valores = [$id_ingreso, $fecha_salida, $total, $metodo_pago, 'manual'];
    $tipos = 'isdss';
    
    // Agregar campos del lavado si existen
    if (!empty($motivos_extra)) {
        $columnas[] = 'motivos_extra';
        $valores[] = $motivos_extra;
        $tipos .= 's';
    }
    if (!empty($descripcion_extra)) {
        $columnas[] = 'descripcion_extra';
        $valores[] = $descripcion_extra;
        $tipos .= 's';
    }
    if ($precio_extra > 0) {
        $columnas[] = 'precio_extra';
        $valores[] = $precio_extra;
        $tipos .= 'd';
    }

    $sql_columnas = implode(', ', $columnas);
    $sql_placeholders = implode(', ', array_fill(0, count($columnas), '?'));
    
    $sql_salida = "INSERT INTO salidas ($sql_columnas) VALUES ($sql_placeholders)";
    $stmt_salida = $conexion->prepare($sql_salida);
    $stmt_salida->bind_param($tipos, ...$valores);
    $stmt_salida->execute();
    $stmt_salida->close();
    
    // 4. Actualizar registro de ingreso
    $sql_update = "UPDATE ingresos SET salida = 1 WHERE idautos_estacionados = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("i", $id_ingreso);
    $stmt_update->execute();
    $stmt_update->close();
    
    // 5. Eliminar registro de lavados_pendientes (si existe)
    $sql_eliminar = "DELETE FROM lavados_pendientes WHERE id_ingreso = ?";
    $stmt_eliminar = $conexion->prepare($sql_eliminar);
    $stmt_eliminar->bind_param("i", $id_ingreso);
    $stmt_eliminar->execute();
    $stmt_eliminar->close();
    
    // 6. Obtener información adicional para el comprobante
    $sql_info = "SELECT i.fecha_ingreso, i.patente, ti.nombre_servicio 
                 FROM ingresos i
                 JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
                 WHERE i.idautos_estacionados = ?";
    $stmt_info = $conexion->prepare($sql_info);
    $stmt_info->bind_param("i", $id_ingreso);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $info = $result_info->fetch_assoc();
    $stmt_info->close();
    
    // Confirmar transacción
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pago manual registrado correctamente',
        'tipo_pago' => 'manual',
        'data' => [
            'id_ingreso' => $id_ingreso,
            'patente' => $patente,
            'servicio' => $info['nombre_servicio'] ?? 'N/A',
            'fecha_ingreso' => $info['fecha_ingreso'] ?? null,
            'fecha_salida' => $fecha_salida,
            'total' => $total,
            'metodo_pago' => $metodo_pago,
            'motivo_manual' => $motivo_manual,
            'es_comprobante_interno' => true
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar pago manual: ' . $e->getMessage()
    ]);
}

$conexion->close();
?>

