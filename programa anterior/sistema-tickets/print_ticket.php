<?php
session_start();

require __DIR__ . '/vendor/autoload.php'; // Librería Escpos
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

/*
    Sistema de impresión de tickets para Estacionamiento/Lavado
    Adaptado del sistema anterior de Inversiones Rosner
*/

// Configuración de la impresora
$nombre_impresora = "POSESTACIONAMIENTO"; // Nombre de la impresora compartida
$ip_impresora = "192.168.1.100"; // IP de la impresora de red (alternativa)

// Recibir parámetros del formulario
$hora_ingreso = $_POST["hora_ingreso"] ?? "";
$hora_egreso = $_POST["hora_egreso"] ?? "";
$total = $_POST["total"] ?? 0;
$patente = $_POST["patente"] ?? "";
$tipo_servicio = $_POST["tipo_servicio"] ?? "Estacionamiento";
$tiempo_total = $_POST["tiempo_total"] ?? "";

try {
    // Intentar conectar con impresora de red primero, luego Windows
    $connector = null;
    
    // Opción 1: Impresora de red (más rápida)
    try {
        $connector = new NetworkPrintConnector($ip_impresora, 9100);
    } catch (Exception $e) {
        // Opción 2: Impresora compartida en Windows
        $connector = new WindowsPrintConnector($nombre_impresora);
    }
    
    $printer = new Printer($connector);
    
    // Confirmar conexión exitosa
    echo json_encode(['status' => 'success', 'message' => 'Ticket enviado a impresora']);
    
    // Configurar alineación al centro
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Intentar cargar e imprimir logo (opcional)
    try {
        $logo = EscposImage::load("logo_estacionamiento.png", false);
        $printer->bitImage($logo);
    } catch (Exception $e) {
        // Si no hay logo, continuar sin él
    }
    
    // Encabezado del ticket
    $printer->text("\n" . "LAVADO DE AUTOS LOS RÍOS" . "\n");
    $printer->text("Estacionamiento y Servicios de Lavado" . "\n");
    $printer->text("Dirección: [TU_DIRECCION]" . "\n");
    $printer->text("Teléfono: [TU_TELEFONO]" . "\n");
    
    // Configurar zona horaria de Chile
    date_default_timezone_set("America/Santiago");
    $printer->text("Fecha: " . date("d-m-Y") . "\n");
    $printer->text("Hora: " . date("H:i:s") . "\n");
    $printer->text("-----------------------------" . "\n");
    
    // Detalles del servicio
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("DETALLE DEL SERVICIO\n");
    $printer->text("-----------------------------\n\n");
    
    $printer->text("Tipo: " . $tipo_servicio . "\n");
    $printer->text("Patente: " . strtoupper($patente) . "\n");
    
    if ($tipo_servicio == "Estacionamiento") {
        $printer->text("Hora ingreso: " . $hora_ingreso . "\n");
        $printer->text("Hora salida: " . $hora_egreso . "\n");
        $printer->text("Tiempo total: " . $tiempo_total . "\n");
    } else {
        // Para servicios de lavado
        $printer->text("Hora servicio: " . $hora_egreso . "\n");
        $printer->text("Duración: " . $tiempo_total . "\n");
    }
    
    $printer->text("-----------------------------\n");
    
    // Total
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("TOTAL: $" . number_format($total, 0, ',', '.') . "\n");
    $printer->text("-----------------------------\n");
    
    // Pie de página
    $printer->text("\n");
    $printer->text("¡Gracias por su preferencia!\n");
    $printer->text("Vuelva pronto\n");
    
    // Alimentar papel y cortar
    $printer->feed(3);
    $printer->cut();
    $printer->pulse();
    
    // Cerrar conexión
    $printer->close();
    
} catch (Exception $e) {
    // Manejar errores de impresión
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error al imprimir: ' . $e->getMessage()
    ]);
}
?>
