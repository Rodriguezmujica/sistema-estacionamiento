<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion.php';

// Aceptar patente por POST o GET
$patente = '';
if (isset($_POST['patente'])) {
    $patente = strtoupper(trim($_POST['patente']));
} elseif (isset($_GET['patente'])) {
    $patente = strtoupper(trim($_GET['patente']));
}

if (!$patente) {
    echo json_encode(['success' => false, 'error' => 'Patente requerida']);
    exit;
}

try {
    $sql = "SELECT 
                i.idautos_estacionados,
                i.patente,
                i.fecha_ingreso,
                ti.nombre_servicio AS tipo_servicio,
                ti.precio,
                s.total,
                s.fecha_salida,
                COALESCE(s.motivos_extra, lp.motivos_extra) as motivos_extra,
                COALESCE(s.descripcion_extra, lp.descripcion_extra) as descripcion_extra,
                COALESCE(s.precio_extra, lp.precio_extra, 0) as precio_extra
            FROM ingresos i
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            LEFT JOIN salidas s ON i.idautos_estacionados = s.id_ingresos
            LEFT JOIN lavados_pendientes lp ON i.idautos_estacionados = lp.id_ingreso
            WHERE i.patente = ? 
            AND ti.nombre_servicio NOT LIKE '%estacionamiento%'
            AND i.idtipo_ingreso NOT IN (18, 19, 47)
            ORDER BY i.fecha_ingreso DESC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Error en prepare(): ' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param('s', $patente);
    $stmt->execute();
    $result = $stmt->get_result();

    $lavados = [];
    $totalLavados = 0;
    $ultimoLavado = null;
    $sumaPrecios = 0;

    while ($row = $result->fetch_assoc()) {
        $lavados[] = $row;
        $totalLavados++;
        $sumaPrecios += floatval($row['precio'] ?? 0);

        if (!$ultimoLavado) {
            $ultimoLavado = $row;
        }
    }

    if ($totalLavados > 0) {
        // Procesar motivos del último lavado
        $motivos = [];
        if (!empty($ultimoLavado['motivos_extra'])) {
            $motivosDecoded = json_decode($ultimoLavado['motivos_extra'], true);
            $motivos = is_array($motivosDecoded) ? $motivosDecoded : [];
        }

        $promedio = $sumaPrecios / $totalLavados;

        echo json_encode([
            'success' => true,
            'patente' => $patente,
            'total_lavados' => $totalLavados,
            'promedio_precio' => round($promedio, 2),
            'ultimo_lavado' => [
                'fecha' => $ultimoLavado['fecha_ingreso'],
                'servicio' => $ultimoLavado['tipo_servicio'],
                'precio' => floatval($ultimoLavado['precio'] ?? 0),
                'precio_extra' => floatval($ultimoLavado['precio_extra'] ?? 0),
                'total' => floatval($ultimoLavado['total'] ?? 0),
                'descripcion' => $ultimoLavado['descripcion_extra'] ?? '',
                'motivos' => $motivos
            ],
            'historial' => $lavados
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'patente' => $patente,
            'total_lavados' => 0,
            'promedio_precio' => 0,
            'ultimo_lavado' => null,
            'historial' => []
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
