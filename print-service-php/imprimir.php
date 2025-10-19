<?php
/**
 * Servicio de Impresión PHP para Windows 7
 * Compatible con impresora Star BSC10
 * 
 * Uso: php imprimir.php
 * Este script espera peticiones y las procesa
 */

// Limpiar cualquier output buffer previo y desactivar errores HTML
while (ob_get_level()) {
    ob_end_clean();
}

// Desactivar display de errores para evitar output adicional
ini_set('display_errors', 0);
ini_set('html_errors', 0);

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
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Función para imprimir ticket de ingreso
 */
function imprimirTicketIngreso($datos, $nombreImpresora) {
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);

        // --- INICIO DE LA SECCIÓN CORREGIDA ---

        // Validar datos necesarios (como en el sistema viejo)
        $patente = strtoupper($datos['patente'] ?? 'SIN-PATENTE');
        $tipo_vehiculo = $datos['tipo_vehiculo'] ?? 'Vehículo';
        $ticket_id = $datos['ticket_id'] ?? 'S/N';
        $fecha_ingreso = $datos['fecha_ingreso'] ?? date('d-m-Y');
        $hora_ingreso = $datos['hora_ingreso'] ?? date('H:i:s');

        // ========================================
        // 🖼️ LOGO DEL NEGOCIO
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        try {
            $logo_path = __DIR__ . "/../ImpresionTermica/geek.png";
            if (file_exists($logo_path)) {
                $logo = EscposImage::load($logo_path, false);
                $printer->bitImage($logo);
                $printer->feed(1);
            }
        } catch(Exception $e) {
            error_log("Error cargando logo: " . $e->getMessage());
        }
        
        // ========================================
        // 📋 ENCABEZADO DEL NEGOCIO
        // ========================================
        $printer->setEmphasis(true);
        $printer->text("INVERSIONES ROSNER\n");
        $printer->setEmphasis(false);
        $printer->text("Estacionamiento y Lavado\n");
        $printer->text("================================\n");
        $printer->text("Perez Rosales #733-C\n");
        $printer->text("Los Rios, Chile\n");
        $printer->text("Tel: +56 9 3395 8739\n");
        $printer->text("================================\n\n");
        
        // Título del ticket
        date_default_timezone_set("America/Santiago");
        $printer->setEmphasis(true);
        $printer->text("TICKET DE INGRESO\n");
        $printer->setEmphasis(false);
        $printer->text("Fecha: $fecha_ingreso\n");
        $printer->text("Hora:  $hora_ingreso\n");
        $printer->text("--------------------------------\n\n");
        
        // ========================================
        // 🚗 DETALLES DEL SERVICIO
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Tipo Vehículo: " . $tipo_vehiculo . "\n");
        $printer->text("--------------------------------\n");
        
        // ========================================
        // 📊 CÓDIGO DE BARRAS (LÓGICA DEL SISTEMA VIEJO)
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setBarcodeHeight(80); // Buena altura para escanear
        $printer->setBarcodeWidth(3);   // Ancho visible
        $printer->barcode($patente, Printer::BARCODE_CODE39); // Usamos la PATENTE y CODE39
        $printer->feed(1);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 1);
        $printer->text($patente . "\n"); // Repetimos la patente en texto grande
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        
        // ========================================
        // 👋 PIE DE PÁGINA
        // ========================================
        $printer->text("\nConserve este ticket para su pago.\n");
        $printer->text("Gracias por su preferencia.\n");
        
        // Finalizar
        $printer->feed(3);
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
        // ========================================
        // 🖼️ LOGO DEL NEGOCIO
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        
        // Intentar cargar e imprimir el logo
        try {
            $logo_path = __DIR__ . "/../ImpresionTermica/geek.png";
            if (file_exists($logo_path)) {
                $logo = EscposImage::load($logo_path, false);
                $printer->bitImage($logo);
                $printer->text("\n"); // Espacio después del logo
            }
        } catch(Exception $e) {
            error_log("Error cargando logo: " . $e->getMessage());
        }
        // ========================================
        // 📋 ENCABEZADO DEL NEGOCIO
        // ========================================
        $printer->text("\n");
        $printer->setEmphasis(true);
        $printer->text("INVERSIONES ROSNER\n");
        $printer->setEmphasis(false);
        $printer->text("Estacionamiento y Lavado\n");
        $printer->text("================================\n");
        $printer->text("Perez Rosales #733-C\n");
        $printer->text("Los Rios, Chile\n");
        $printer->text("Tel: +56 9 3395 8739\n");
        $printer->text("================================\n\n");

        // Título del ticket
        date_default_timezone_set("America/Santiago");
        $printer->setEmphasis(true);
        $printer->text("COMPROBANTE DE PAGO\n");
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");

        // Detalles
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Patente: " . strtoupper($datos['patente'] ?? 'N/A') . "\n");
        $printer->text("Ingreso: " . ($datos['fecha_ingreso'] ?? '') . "\n");
        $printer->text("Salida:  " . ($datos['fecha_salida'] ?? '') . "\n");
        $printer->text("Tiempo: " . ($datos['tiempo_estadia'] ?? '') . "\n");
        $printer->text("--------------------------------\n");
        
        // Total
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setEmphasis(true);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("TOTAL: $" . number_format($datos['monto'] ?? 0, 0, ',', '.') . "\n");
        $printer->selectPrintMode();
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        
        // Método de pago
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Método de Pago: " . ($datos['metodo_pago'] ?? 'Efectivo') . "\n\n");
        
        // ========================================
        // 👋 PIE DE PÁGINA
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Gracias por su preferencia\n");
        $printer->text("Vuelva pronto\n");

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
    $printer = null;
    $connector = null;
    
    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        
        // ========================================
        // 🖼️ LOGO DEL NEGOCIO
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        
        // Intentar cargar e imprimir el logo
        try {
            $logo_path = __DIR__ . "/../ImpresionTermica/geek.png";
            // Verificar que el archivo existe
            if (file_exists($logo_path)) {
                $logo = EscposImage::load($logo_path, false);
                $printer->bitImage($logo);
                $printer->text("\n"); // Espacio después del logo
            }
        } catch(Exception $e) {
            // Si no hay logo, continuar sin él
            error_log("Error cargando logo: " . $e->getMessage());
        }
        // ========================================
        // 📋 ENCABEZADO DEL NEGOCIO
        // ========================================
        $printer->text("\n");
        $printer->setEmphasis(true);
        $printer->text("INVERSIONES ROSNER\n");
        $printer->setEmphasis(false);
        $printer->text("Estacionamiento y Lavado\n");
        $printer->text("================================\n");
        $printer->text("Perez Rosales #733-C\n");
        $printer->text("Los Rios, Chile\n");
        $printer->text("================================\n\n");

        // Título
        $printer->setEmphasis(true);
        $printer->text("SERVICIO DE LAVADO\n");
        $printer->setEmphasis(false);
        $printer->text("Fecha: " . ($datos['fecha'] ?? date('d-m-Y H:i:s')) . "\n");
        $printer->text("--------------------------------\n\n");

        // Detalles
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Ticket: " . ($datos['ticket_id'] ?? 'N/A') . "\n");
        $printer->text("Patente: " . strtoupper($datos['patente'] ?? 'N/A') . "\n");
        $printer->text("Servicio: " . ($datos['servicio'] ?? 'Lavado Simple') . "\n");
        $printer->text("--------------------------------\n");
        
        // Total
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setEmphasis(true);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("TOTAL: $" . number_format($datos['monto'] ?? 0, 0, ',', '.') . "\n");
        $printer->selectPrintMode();
        $printer->setEmphasis(false);
        $printer->text("--------------------------------\n");
        
        // ========================================
        // 📊 CÓDIGO DE BARRAS (USANDO LA PATENTE)
        // ========================================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $patente = strtoupper($datos['patente'] ?? 'SIN-PATENTE');
        
        $printer->setBarcodeHeight(60);
        $printer->setBarcodeWidth(2);
        $printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
        
        try {
            $printer->barcode($patente, Printer::BARCODE_CODE39);
            $printer->feed(1);
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 1);
            $printer->text($patente . "\n"); // Repetir la patente en texto grande
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
        } catch (Exception $e) {
            // Si falla el código de barras, solo mostrar el texto
            error_log("Error generando código de barras: " . $e->getMessage());
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 1);
            $printer->text($patente . "\n");
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
        }
        $printer->feed(1);

        $printer->text("Gracias por su preferencia\n");
        $printer->feed(2);
        $printer->cut();
        
        return ['success' => true, 'message' => 'Ticket impreso correctamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()];
    } finally {
        // Asegurar que la impresora se cierre correctamente
        if ($printer !== null) {
            try {
                $printer->close();
            } catch (Exception $e) {
                error_log("Error cerrando impresora: " . $e->getMessage());
            }
        }
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
    exit;
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Endpoint de estado
    if (isset($_GET['action']) && $_GET['action'] === 'status') {
        echo json_encode([
            'success' => true,
            'status' => 'online',
            'message' => 'Servicio de impresión PHP activo',
            'version' => '1.0.0'
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Servicio de impresión PHP para Windows 7',
            'endpoints' => [
                'POST /imprimir.php' => 'Imprimir ticket',
                'GET /imprimir.php?action=status' => 'Verificar estado'
            ]
        ]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}
?>
