<?php
require __DIR__ . '/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

$nombre_impresora = "POSESTACIONAMIENTO";

try {
    // Intentar conectar con la impresora
    $connector = new WindowsPrintConnector($nombre_impresora);
    $printer = new Printer($connector);
    
    // Imprimir ticket de prueba
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Intentar cargar el logo
    try {
        $logo = EscposImage::load("geek.png", false);
        $printer->bitImage($logo);
    } catch(Exception $e) {
        // Si no hay logo, continuamos sin él
    }
    
    $printer->text("\n");
    $printer->text("============================\n");
    $printer->text("  PRUEBA DE IMPRESORA  \n");
    $printer->text("============================\n");
    $printer->text("\n");
    
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("Impresora: " . $nombre_impresora . "\n");
    $printer->text("Fecha: " . date("d-m-Y") . "\n");
    $printer->text("Hora: " . date("H:i:s") . "\n");
    $printer->text("\n");
    
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("----------------------------\n");
    $printer->text("CONEXION EXITOSA\n");
    $printer->text("La impresora funciona OK\n");
    $printer->text("----------------------------\n");
    $printer->text("\n");
    
    // Código de barras de prueba
    $printer->barcode("TEST123", Printer::BARCODE_CODE39);
    $printer->text("\nTEST123\n");
    $printer->text("\n");
    
    $printer->feed(3);
    $printer->cut();
    $printer->close();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Impresora funcionando correctamente',
        'impresora' => $nombre_impresora
    ]);
    
} catch (Exception $e) {
    // Error al conectar
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectar con la impresora',
        'error' => $e->getMessage(),
        'impresora' => $nombre_impresora
    ]);
}
?>

