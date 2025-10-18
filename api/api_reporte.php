<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

// Consulta para obtener ingresos activos (sin salida registrada) con información de lavados
$sql = "
SELECT i.idautos_estacionados,
       i.patente, 
       ti.nombre_servicio AS tipo_servicio, 
       i.fecha_ingreso, 
       CASE WHEN ti.nombre_servicio LIKE '%lavado%' OR ti.nombre_servicio LIKE '%MOTOS%' OR (ti.nombre_servicio NOT LIKE '%estacionamiento%' AND ti.nombre_servicio NOT LIKE '%minuto%') THEN 'Sí' ELSE 'No' END AS lavado,
       lp.motivos_extra,
       lp.descripcion_extra,
       lp.precio_extra,
       lp.nombre_cliente,
       (ti.precio + COALESCE(lp.precio_extra, 0)) as total
FROM ingresos i
LEFT JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
LEFT JOIN lavados_pendientes lp ON i.idautos_estacionados = lp.id_ingreso
WHERE i.salida = 0 OR i.salida IS NULL
ORDER BY i.idautos_estacionados DESC
";

$resultado = $conexion->query($sql);

// Verificación de errores en la consulta
if (!$resultado) {
    die(json_encode([
        "error" => $conexion->error,
        "sql"   => $sql
    ]));
}

$datos = [];
while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$conexion->close();
?>
