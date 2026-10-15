<?php
session_start();

require __DIR__ . '/ImpresionTermica/ticket/autoload.php'; // Usar la librería existente
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

/*
    Sistema de impresión mejorado basado en el sistema anterior
    Mantiene toda la funcionalidad que ya funcionaba
*/

// Configuración de impresora (usar la misma del sistema anterior)
$nombre_impresora = "POSESTACIONAMIENTO"; 
$ip_impresora = "192.168.1.100"; // Opción de red

// Recibir parámetros
$tipo_ticket = $_POST["tipo_ticket"] ?? "salida"; // "entrada" o "salida"
$hora_ingreso = $_POST["hora_ingreso"] ?? "";
$hora_egreso = $_POST["hora_egreso"] ?? "";
$total = $_POST["total"] ?? 0;
$patente = $_POST["patente"] ?? "";
$servicio_cliente = $_POST["servicio_cliente"] ?? "";
$nombre_cliente = $_SESSION['nombreCliente'] ?? "";

try {
    // Intentar conectar (red primero, luego Windows)
    $connector = null;
    try {
        $connector = new NetworkPrintConnector($ip_impresora, 9100);
    } catch (Exception $e) {
        $connector = new WindowsPrintConnector($nombre_impresora);
    }
    
    $printer = new Printer($connector);
    echo 1; // Confirmar conexión (como en el sistema anterior)
    
    // Configurar alineación
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Cargar logo (usar el mismo del sistema anterior)
    try {
        $logo = EscposImage::load("ImpresionTermica/geek.png", false);
        $printer->bitImage($logo);
    } catch (Exception $e) {
        // Continuar sin logo si hay error
    }
    
    // Encabezado (personalizar para tu empresa)
    $printer->text("\n" . "LAVADO DE AUTOS LOS RÍOS" . "\n");
    $printer->text("Estacionamiento y lavado de autos" . "\n");
    $printer->text("Dirección: [TU_DIRECCION]" . "\n");
    $printer->text("Teléfono: [TU_TELEFONO]" . "\n");
    
    // Fecha y hora
    date_default_timezone_set("America/Santiago");
    $printer->text("Fecha: " . date("d-m-Y") . "\n");
    $printer->text("-----------------------------" . "\n");
    
    if ($tipo_ticket == "entrada") {
        // TICKET DE ENTRADA (basado en ticket.php original)
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("DETALLE INGRESO\n");
        $printer->text("-----------------------------\n\n");
        
        $printer->text("Hora de ingreso: " . date("H:i:s") . "\n");
        
        if ($nombre_cliente != "") {
            $printer->text("Nombre cliente: " . $nombre_cliente . "\n");
        }
        
        if ($servicio_cliente != "") {
            $printer->text("Servicio: " . $servicio_cliente . "\n");
        }
        
        $printer->text("-----------------------------\n");
        
        // CÓDIGO DE BARRAS DE LA PATENTE (funcionalidad clave del sistema anterior)
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $patente_upper = strtoupper($patente);
        $printer->barcode($patente_upper, Printer::BARCODE_CODE39);
        $printer->text("\n");
        $printer->text($patente_upper . "\n");
        
    } else {
        // TICKET DE SALIDA (basado en ticketsalida.php original)
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("DETALLE SALIDA\n");
        $printer->text("-----------------------------\n\n");
        
        $printer->text("Hora ingreso: " . $hora_ingreso . "\n");
        $printer->text("Hora salida: " . $hora_egreso . "\n");
        $printer->text("Patente: " . strtoupper($patente) . "\n");
        $printer->text("Total: $" . number_format($total, 0, ',', '.') . "\n");
        
        $printer->text("-----------------------------\n");
    }
    
    // Pie de página
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("\n");
    $printer->text("Muchas gracias por su preferencia\n");
    
    // Finalizar impresión
    $printer->feed(3);
    $printer->cut();
    $printer->pulse(); // Para cajón de dinero
    $printer->close();
    
    // Limpiar sesión si es necesario
    if (isset($_SESSION['nombreCliente'])) {
        unset($_SESSION['nombreCliente']);
    }
    
} catch (Exception $e) {
    echo 0; // Error (como en el sistema anterior)
}
?>
