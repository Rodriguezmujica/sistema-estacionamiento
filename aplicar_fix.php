<?php
/**
 * Aplica la solución automáticamente
 */
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$nuevo_host = $data['host'] ?? '127.0.0.1';

$archivo = __DIR__ . '/conexion.php';

if (!file_exists($archivo)) {
    echo json_encode(['success' => false, 'error' => 'Archivo conexion.php no encontrado']);
    exit;
}

// Hacer backup
$backup = $archivo . '.backup.' . date('YmdHis');
copy($archivo, $backup);

// Leer contenido
$contenido = file_get_contents($archivo);

// Reemplazar
$contenido_nuevo = preg_replace(
    "/\$host\s*=\s*'localhost';/",
    "\$host = '$nuevo_host';",
    $contenido
);

// Guardar
if (file_put_contents($archivo, $contenido_nuevo)) {
    echo json_encode([
        'success' => true,
        'mensaje' => "Archivo modificado. Backup guardado en: $backup"
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo escribir el archivo']);
}
?>


