<?php
/**
 * Servicio de Impresión PHP para Windows 7
 * Compatible con impresora Star BSC10
 * 
 * Uso: php imprimir.php
 * Este script espera peticiones y las procesa
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir librería de impresión
require_once __DIR__ . '/../ImpresionTermica/ticket/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Función para imprimir ticket de ingreso
 */
function imprimirTicketIngreso($datos, $nombreImpresora) {
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 2);
        $printer->text("ESTACIONAMIENTO\n");
        $printer->text("TICKET DE INGRESO\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Ticket: " . ($datos['ticket_id'] ?? 'N/A') . "\n");
        $printer->text("Patente: " . strtoupper($datos['patente'] ?? 'N/A') . "\n");
        $printer->text("Tipo: " . ($datos['tipo_vehiculo'] ?? 'Auto') . "\n");
        $printer->text("Entrada: " . ($datos['fecha_ingreso'] ?? '') . "\n");
        $printer->text("Hora: " . ($datos['hora_ingreso'] ?? '') . "\n");
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Conserve este ticket\n");
        $printer->text("Gracias por su visita\n");
        $printer->feed(2);
        $printer->cut();
        $printer->close();
        
        return ['success' => true, 'message' => 'Ticket impreso correctamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()];
    }
}

/**
 * Función para imprimir ticket de salida
 */
function imprimirTicketSalida($datos, $nombreImpresora) {
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 2);
        $printer->text("ESTACIONAMIENTO\n");
        $printer->text("COMPROBANTE DE PAGO\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Ticket: " . ($datos['ticket_id'] ?? 'N/A') . "\n");
        $printer->text("Patente: " . strtoupper($datos['patente'] ?? 'N/A') . "\n");
        $printer->text("Entrada: " . ($datos['fecha_ingreso'] ?? '') . "\n");
        $printer->text("Salida: " . ($datos['fecha_salida'] ?? '') . "\n");
        $printer->text("Tiempo: " . ($datos['tiempo_estadia'] ?? '') . "\n");
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setTextSize(2, 2);
        $printer->text("TOTAL: $" . ($datos['monto'] ?? '0') . "\n");
        $printer->setTextSize(1, 1);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Metodo: " . ($datos['metodo_pago'] ?? 'Efectivo') . "\n");
        $printer->text("Fecha: " . ($datos['fecha_pago'] ?? '') . "\n");
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Gracias por su visita\n");
        $printer->feed(2);
        $printer->cut();
        $printer->close();
        
        return ['success' => true, 'message' => 'Ticket impreso correctamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()];
    }
}

/**
 * Función para imprimir ticket de lavado
 */
function imprimirTicketLavado($datos, $nombreImpresora) {
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 2);
        $printer->text("SERVICIO DE LAVADO\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Ticket: " . ($datos['ticket_id'] ?? 'N/A') . "\n");
        $printer->text("Patente: " . strtoupper($datos['patente'] ?? 'N/A') . "\n");
        $printer->text("Servicio: " . ($datos['servicio'] ?? 'Lavado Simple') . "\n");
        $printer->text("Fecha: " . ($datos['fecha'] ?? '') . "\n");
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setTextSize(2, 2);
        $printer->text("TOTAL: $" . ($datos['monto'] ?? '0') . "\n");
        $printer->setTextSize(1, 1);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Gracias por su preferencia\n");
        $printer->feed(2);
        $printer->cut();
        $printer->close();
        
        return ['success' => true, 'message' => 'Ticket impreso correctamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()];
    }
}

/**
 * Función para imprimir cierre de caja
 */
function imprimirCierreCaja($datos, $nombreImpresora) {
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 2);
        $printer->text("CIERRE DE CAJA\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha: " . ($datos['fecha'] ?? date('Y-m-d')) . "\n");
        $printer->text("Hora: " . ($datos['hora'] ?? date('H:i:s')) . "\n");
        $printer->text("Usuario: " . ($datos['usuario'] ?? 'N/A') . "\n");
        $printer->text("--------------------------------\n");
        
        $printer->setEmphasis(true);
        $printer->text("INGRESOS ESTACIONAMIENTO\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text("Efectivo: $" . number_format($datos['efectivo_estacionamiento'] ?? 0, 0, ',', '.') . "\n");
        $printer->text("TUU: $" . number_format($datos['tuu_estacionamiento'] ?? 0, 0, ',', '.') . "\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("--------------------------------\n");
        
        $printer->setEmphasis(true);
        $printer->text("INGRESOS LAVADO\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text("Efectivo: $" . number_format($datos['efectivo_lavado'] ?? 0, 0, ',', '.') . "\n");
        $printer->text("TUU: $" . number_format($datos['tuu_lavado'] ?? 0, 0, ',', '.') . "\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("--------------------------------\n");
        
        $printer->setEmphasis(true);
        $printer->text("TOTALES\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setTextSize(2, 2);
        $printer->text("$" . number_format($datos['total'] ?? 0, 0, ',', '.') . "\n");
        $printer->setTextSize(1, 1);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("--------------------------------\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("\n");
        $printer->text("Firma: ___________________\n");
        $printer->text("\n");
        $printer->feed(2);
        $printer->cut();
        $printer->close();
        
        return ['success' => true, 'message' => 'Cierre de caja impreso correctamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()];
    }
}

/**
 * Función para imprimir ticket de prueba
 */
function imprimirTest($datos, $nombreImpresora) {
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("TEST DE IMPRESION\n");
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        $printer->text(($datos['mensaje'] ?? 'Prueba exitosa') . "\n");
        $printer->text(date('Y-m-d H:i:s') . "\n");
        $printer->text("--------------------------------\n");
        $printer->feed(2);
        $printer->cut();
        $printer->close();
        
        return ['success' => true, 'message' => 'Test impreso correctamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()];
    }
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $tipo = $input['tipo'] ?? null;
    $datos = $input['datos'] ?? [];
    $nombreImpresora = $input['impresora'] ?? 'POSESTACIONAMIENTO'; // Nombre de la impresora en Windows
    
    if (!$tipo) {
        echo json_encode([
            'success' => false,
            'message' => 'Falta el parámetro "tipo"'
        ]);
        exit;
    }
    
    switch ($tipo) {
        case 'ingreso':
            $resultado = imprimirTicketIngreso($datos, $nombreImpresora);
            break;
        case 'salida':
            $resultado = imprimirTicketSalida($datos, $nombreImpresora);
            break;
        case 'lavado':
            $resultado = imprimirTicketLavado($datos, $nombreImpresora);
            break;
        case 'cierre_caja':
            $resultado = imprimirCierreCaja($datos, $nombreImpresora);
            break;
        case 'test':
            $resultado = imprimirTest($datos, $nombreImpresora);
            break;
        default:
            $resultado = [
                'success' => false,
                'message' => 'Tipo de ticket no reconocido: ' . $tipo
            ];
    }
    
    echo json_encode($resultado);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Endpoint de estado
    if (isset($_GET['action']) && $_GET['action'] === 'status') {
        echo json_encode([
            'success' => true,
            'status' => 'online',
            'message' => 'Servicio de impresión PHP activo',
            'version' => '1.0.0'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Servicio de impresión PHP para Windows 7',
            'endpoints' => [
                'POST /imprimir.php' => 'Imprimir ticket',
                'GET /imprimir.php?action=status' => 'Verificar estado'
            ]
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>

