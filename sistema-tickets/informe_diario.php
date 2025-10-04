<?php
session_start();

require __DIR__ . '/ImpresionTermica/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

/*
    Informe diario mejorado basado en informe.php del sistema anterior
    Mantiene la misma funcionalidad pero con mejoras
*/

// Configuración de impresora
$nombre_impresora = "POSESTACIONAMIENTO";
$ip_impresora = "192.168.1.100";

// Configuración de base de datos (usar la misma del sistema anterior)
$usuario = "root";
$password = "";
$servidor = "localhost";
$basededatos = "estacionamiento";

try {
    // Conectar a base de datos
    $conexion = mysqli_connect($servidor, $usuario, $password) or die("No se ha podido conectar al servidor de Base de datos");
    $db = mysqli_select_db($conexion, $basededatos) or die("No se ha podido conectar a la base de datos");
    
    // Consulta del día actual (misma consulta del sistema anterior)
    $fecha = date("Y-m-d");
    $hora = $fecha . " 00:00:00";
    $consulta = "SELECT COUNT(*) as cantidad, SUM(s.total) as total, t.nombre_servicio, t.es_plan 
                 FROM ingresos i 
                 JOIN salidas s ON i.idautos_estacionados = s.id_ingresos 
                 JOIN tipo_ingreso t ON i.idtipo_ingreso = t.idtipo_ingresos 
                 WHERE i.fecha_ingreso >= '$hora' 
                 GROUP BY t.nombre_servicio";
    
    $resultado = mysqli_query($conexion, $consulta) or die("Error en la consulta a la base de datos");
    
    // Conectar a impresora
    $connector = null;
    try {
        $connector = new NetworkPrintConnector($ip_impresora, 9100);
    } catch (Exception $e) {
        $connector = new WindowsPrintConnector($nombre_impresora);
    }
    
    $printer = new Printer($connector);
    echo 1; // Confirmar conexión
    
    // Configurar impresión
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Logo (opcional)
    try {
        $logo = EscposImage::load("ImpresionTermica/geek.png", false);
        $printer->bitImage($logo);
    } catch (Exception $e) {
        // Continuar sin logo
    }
    
    // Encabezado del informe
    $printer->text("\n" . "INFORME DIARIO" . "\n");
    $printer->text("LAVADO DE AUTOS LOS RÍOS" . "\n");
    
    date_default_timezone_set("Chile/Continental");
    $printer->text("Fecha: " . date("d-m-Y") . "\n");
    $printer->text("------------------------------------------" . "\n");
    
    // Encabezado de columnas
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("CANTIDAD     NOMBRE DE SERVICIO     TOTAL\n");
    $printer->text("------------------------------------------\n\n");
    
    // Imprimir datos
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $suma_total = 0;
    
    while ($columna = mysqli_fetch_array($resultado)) {
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_pad($columna['cantidad'], 4, " ", STR_PAD_BOTH));
        
        // Truncar nombre de servicio si es muy largo
        $nombre_servicio = $columna['nombre_servicio'];
        if (strlen($nombre_servicio) > 32) {
            $nombre_servicio = substr($nombre_servicio, 0, 29) . "...";
        }
        
        $printer->text(str_pad($nombre_servicio, 32, " ", STR_PAD_BOTH));
        
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text(str_pad($columna['total'], 5, " ", STR_PAD_LEFT) . "\n");
        $suma_total += $columna['total'];
    }
    
    // Total del día
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("------------------------------------------\n");
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text("TOTAL DEL DÍA: $" . number_format($suma_total, 0, ',', '.') . "\n");
    
    // Estadísticas adicionales
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("\n");
    $printer->text("ESTADÍSTICAS DEL DÍA:\n");
    
    // Contar total de servicios
    $consulta_count = "SELECT COUNT(*) as total_servicios FROM ingresos WHERE fecha_ingreso >= '$hora'";
    $resultado_count = mysqli_query($conexion, $consulta_count);
    $total_servicios = mysqli_fetch_array($resultado_count)['total_servicios'];
    
    $printer->text("Total de servicios: " . $total_servicios . "\n");
    $printer->text("Promedio por servicio: $" . ($total_servicios > 0 ? number_format($suma_total / $total_servicios, 0, ',', '.') : 0) . "\n");
    
    // Pie de página
    $printer->text("\n");
    $printer->text("Informe generado: " . date("H:i:s") . "\n");
    $printer->text("Gracias por usar nuestro sistema\n");
    
    // Finalizar
    $printer->feed(3);
    $printer->cut();
    $printer->close();
    
    // Cerrar conexión a BD
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo 0; // Error
}
?>
