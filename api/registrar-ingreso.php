<?php
// api/registrar-ingreso.php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../conexion.php';
    
    // Verificar que la conexión esté disponible
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $patente = strtoupper(trim($_POST['patente'] ?? ''));
    $idtipo_ingreso = intval($_POST['tipo_servicio'] ?? 0);
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');

    if (!$patente || !$idtipo_ingreso) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    // Validar longitud de patente (máximo 6 caracteres según la base de datos)
    if (strlen($patente) > 6) {
        echo json_encode(['success' => false, 'error' => 'La patente no puede tener más de 6 caracteres']);
        exit;
    }

    // Validar que la patente solo contenga letras y números
    if (!preg_match('/^[A-Z0-9]+$/', $patente)) {
        echo json_encode(['success' => false, 'error' => 'La patente solo puede contener letras y números']);
        exit;
    }

    // Verificar que el tipo de servicio existe
    $stmt = $conn->prepare('SELECT idtipo_ingresos FROM tipo_ingreso WHERE idtipo_ingresos = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $idtipo_ingreso);
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    
    $stmt->bind_result($id_existe);
    $stmt->fetch();
    $stmt->close();

    if (!$id_existe) {
        echo json_encode(['success' => false, 'error' => 'Tipo de servicio no válido']);
        exit;
    }

    // Insertar en ingresos
    $stmt = $conn->prepare('INSERT INTO ingresos (patente, idtipo_ingreso) VALUES (?, ?)');
    if (!$stmt) {
        throw new Exception('Error preparando inserción: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $patente, $idtipo_ingreso);
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando inserción: ' . $stmt->error);
    }
    
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Ingreso registrado correctamente']);
    
} catch (Exception $e) {
    error_log("Error en registrar-ingreso.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Error $e) {
    error_log("Error fatal en registrar-ingreso.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}

if (isset($conn)) {
    $conn->close();
}
