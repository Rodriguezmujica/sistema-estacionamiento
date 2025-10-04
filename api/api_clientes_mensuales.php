<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

try {
    // Si se solicita un cliente específico
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $sql = "SELECT * FROM clientes WHERE idclientes = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Cliente no encontrado']);
        }
        exit;
    }
    
    // Obtener todos los clientes mensuales
    $sql = "SELECT 
                idclientes,
                rut,
                nombres,
                apellidos,
                patente,
                inicio_plan,
                fin_plan,
                fono
            FROM clientes 
            ORDER BY fin_plan DESC, nombres ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    
    echo json_encode($clientes);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en consulta: ' . $e->getMessage()
    ]);
}
?>