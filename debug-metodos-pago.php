<?php
/**
 * 🔍 DIAGNÓSTICO DE MÉTODOS DE PAGO
 * Para verificar qué datos reales hay en la base de datos
 */

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

echo "🔍 DIAGNÓSTICO DE MÉTODOS DE PAGO\n";
echo "==================================\n\n";

try {
    // 1. Ver todos los registros de salidas con sus métodos de pago
    echo "1. REGISTROS DE SALIDAS (últimos 20):\n";
    echo "-------------------------------------\n";
    
    $sql = "SELECT 
                s.id_ingresos,
                s.metodo_pago,
                s.tipo_pago,
                s.total,
                s.fecha_salida,
                i.patente
            FROM salidas s
            JOIN ingresos i ON s.id_ingresos = i.idautos_estacionados
            ORDER BY s.fecha_salida DESC
            LIMIT 20";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo sprintf("ID: %-3d | Patente: %-8s | Método: %-10s | Tipo: %-8s | Total: $%-8s | Fecha: %s\n",
                $row['id_ingresos'],
                $row['patente'],
                $row['metodo_pago'] ?? 'NULL',
                $row['tipo_pago'] ?? 'NULL',
                number_format($row['total'], 0, ',', '.'),
                $row['fecha_salida']
            );
        }
    }
    
    echo "\n2. CONTEO POR MÉTODO DE PAGO:\n";
    echo "-----------------------------\n";
    
    $sql = "SELECT 
                COALESCE(s.metodo_pago, 'NULL') as metodo_pago,
                COALESCE(s.tipo_pago, 'NULL') as tipo_pago,
                COUNT(*) as cantidad,
                SUM(s.total) as total
            FROM salidas s
            GROUP BY COALESCE(s.metodo_pago, 'NULL'), COALESCE(s.tipo_pago, 'NULL')
            ORDER BY cantidad DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo sprintf("%-15s | %-10s | %3d registros | $%s\n",
                $row['metodo_pago'],
                $row['tipo_pago'],
                $row['cantidad'],
                number_format($row['total'], 0, ',', '.')
            );
        }
    }
    
    echo "\n3. REGISTROS SIN SALIDA (ingresos pendientes):\n";
    echo "---------------------------------------------\n";
    
    $sql = "SELECT 
                i.idautos_estacionados,
                i.patente,
                i.fecha_ingreso,
                ti.precio,
                ti.nombre_servicio
            FROM ingresos i
            JOIN tipo_ingreso ti ON i.idtipo_ingreso = ti.idtipo_ingresos
            WHERE i.salida = 0
            ORDER BY i.fecha_ingreso DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo sprintf("ID: %-3d | Patente: %-8s | Servicio: %-25s | Precio: $%-8s | Fecha: %s\n",
                $row['idautos_estacionados'],
                $row['patente'],
                substr($row['nombre_servicio'], 0, 25),
                number_format($row['precio'], 0, ',', '.'),
                $row['fecha_ingreso']
            );
        }
    }
    
    echo "\n4. TOTAL DE REGISTROS:\n";
    echo "--------------------\n";
    
    $sql = "SELECT 
                (SELECT COUNT(*) FROM ingresos WHERE salida = 1) as servicios_cobrados,
                (SELECT COUNT(*) FROM ingresos WHERE salida = 0) as servicios_pendientes,
                (SELECT COUNT(*) FROM salidas) as registros_salidas";
    
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        echo "Servicios cobrados: " . $row['servicios_cobrados'] . "\n";
        echo "Servicios pendientes: " . $row['servicios_pendientes'] . "\n";
        echo "Registros de salidas: " . $row['registros_salidas'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
