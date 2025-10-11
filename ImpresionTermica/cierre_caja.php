<?php
session_start();

require __DIR__ . '/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Impresión de Cierre de Caja en Impresora Térmica
 */

$nombre_impresora = "POSESTACIONAMIENTO";

// Recibir parámetros
$fecha = $_POST["fecha"] ?? date('Y-m-d');
$total_servicios = $_POST["total_servicios"] ?? 0;
$total_ingresos = $_POST["total_ingresos"] ?? 0;

// Desglose de pagos (formato JSON)
$efectivo_manual = intval($_POST["efectivo_manual"] ?? 0);
$tuu_efectivo = intval($_POST["tuu_efectivo"] ?? 0);
$tuu_debito = intval($_POST["tuu_debito"] ?? 0);
$tuu_credito = intval($_POST["tuu_credito"] ?? 0);
$transferencia = intval($_POST["transferencia"] ?? 0);

// Categorías (formato JSON)
$categorias = isset($_POST["categorias"]) ? json_decode($_POST["categorias"], true) : [];

try {
    $connector = new WindowsPrintConnector($nombre_impresora);
    $printer = new Printer($connector);
    
    // Configuración
    date_default_timezone_set("America/Santiago");
    
    // ENCABEZADO
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Logo (opcional)
    try {
        $logo = EscposImage::load("geek.png", false);
        $printer->bitImage($logo);
    } catch(Exception $e) {
        // No hacer nada si no hay logo
    }
    
    $printer->text("\n");
    $printer->text("ESTACIONAMIENTO LOS RIOS\n");
    $printer->text("Perez Rosales #733-C\n");
    $printer->text("Tel: 63 2 438535\n");
    $printer->text("=================================\n");
    $printer->setEmphasis(true);
    $printer->text("CIERRE DE CAJA\n");
    $printer->setEmphasis(false);
    $printer->text("=================================\n\n");
    
    // Fecha y hora
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("Fecha: " . date('d-m-Y', strtotime($fecha)) . "\n");
    $printer->text("Hora impresion: " . date('H:i:s') . "\n");
    $printer->text("---------------------------------\n\n");
    
    // RESUMEN GENERAL
    $printer->setEmphasis(true);
    $printer->text("RESUMEN GENERAL\n");
    $printer->setEmphasis(false);
    $printer->text("Total servicios: " . $total_servicios . "\n");
    $printer->text("Total ingresos: $" . number_format($total_ingresos, 0, ',', '.') . "\n");
    $printer->text("---------------------------------\n\n");
    
    // DESGLOSE POR MÉTODO DE PAGO
    $printer->setEmphasis(true);
    $printer->text("DESGLOSE POR METODO DE PAGO\n");
    $printer->setEmphasis(false);
    $printer->text("\n");
    
    // Efectivo Manual
    if ($efectivo_manual > 0) {
        $printer->text("EFECTIVO (Manual):\n");
        $printer->text("  $" . number_format($efectivo_manual, 0, ',', '.') . "\n");
    }
    
    // TUU Efectivo
    if ($tuu_efectivo > 0) {
        $printer->text("EFECTIVO (TUU - Boleta):\n");
        $printer->text("  $" . number_format($tuu_efectivo, 0, ',', '.') . "\n");
    }
    
    // TUU Débito
    if ($tuu_debito > 0) {
        $printer->text("DEBITO (TUU):\n");
        $printer->text("  $" . number_format($tuu_debito, 0, ',', '.') . "\n");
    }
    
    // TUU Crédito
    if ($tuu_credito > 0) {
        $printer->text("CREDITO (TUU):\n");
        $printer->text("  $" . number_format($tuu_credito, 0, ',', '.') . "\n");
    }
    
    // Transferencia
    if ($transferencia > 0) {
        $printer->text("TRANSFERENCIA:\n");
        $printer->text("  $" . number_format($transferencia, 0, ',', '.') . "\n");
    }
    
    $printer->text("---------------------------------\n\n");
    
    // TOTAL EN CAJA FÍSICA (efectivo que debería estar)
    $total_caja_fisica = $efectivo_manual + $tuu_efectivo;
    $total_electronico = $tuu_debito + $tuu_credito + $transferencia;
    
    $printer->setEmphasis(true);
    $printer->text("EFECTIVO EN CAJA FISICA:\n");
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->setTextSize(2, 2);
    $printer->text("$" . number_format($total_caja_fisica, 0, ',', '.') . "\n");
    $printer->setTextSize(1, 1);
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setEmphasis(false);
    $printer->text("\n");
    
    $printer->text("Pagos electronicos: $" . number_format($total_electronico, 0, ',', '.') . "\n");
    $printer->text("---------------------------------\n\n");
    
    // DESGLOSE POR CATEGORÍA
    if (count($categorias) > 0) {
        $printer->setEmphasis(true);
        $printer->text("DESGLOSE POR SERVICIO\n");
        $printer->setEmphasis(false);
        $printer->text("\n");
        
        foreach ($categorias as $cat) {
            $printer->text($cat['categoria'] . ":\n");
            $printer->text("  " . $cat['cantidad'] . " serv. - $" . number_format($cat['total'], 0, ',', '.') . "\n");
        }
        
        $printer->text("---------------------------------\n\n");
    }
    
    // PIE DE PÁGINA
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("\n");
    $printer->text("Cierre realizado por:\n");
    $printer->text("_______________________\n");
    $printer->text("Firma y nombre\n\n");
    $printer->text("Fecha: " . date('d-m-Y H:i') . "\n\n");
    $printer->text("Gracias por su trabajo\n");
    $printer->text("=================================\n");
    
    // Alimentar papel y cortar
    $printer->feed(3);
    $printer->cut();
    $printer->pulse();
    $printer->close();
    
    echo 1; // Respuesta exitosa
    
} catch (Exception $e) {
    error_log("Error en impresión de cierre de caja: " . $e->getMessage());
    echo 0; // Respuesta de error
}
?>

