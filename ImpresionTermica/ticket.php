<?php
// filepath: c:\xampp\htdocs\sistemaEstacionamiento\ImpresionTermica\ticket.php
session_start();

require __DIR__ . '/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// 🔧 SUPRIMIR WARNINGS INNECESARIOS
error_reporting(E_ERROR | E_PARSE);

// 🔧 VALIDACIÓN Y SANITIZACIÓN DE DATOS
$nombre_cliente = $_POST["nombre_cliente"] ?? '';
$servicio_cliente = $_POST["servicio_cliente"] ?? 'Estacionamiento';
$patente = strtoupper(trim($_POST["patente"] ?? ''));
$tipo_ingreso = $_POST["tipo_ingreso"] ?? '';

if (empty($patente)) {
    $patente = 'SIN-PATENTE';
}

// 🔧 GENERAR CÓDIGO DE BARRAS VÁLIDO
$codigo_barras = '';
if (!empty($tipo_ingreso) && $tipo_ingreso !== 'undefined' && $tipo_ingreso !== 'null') {
    $codigo_limpio = strtoupper(preg_replace('/[^0-9A-Z\s\$\%\+\-\.\/]/', '', $tipo_ingreso));
    $codigo_barras = !empty($codigo_limpio) ? $codigo_limpio : 'ID' . date('YmdHis');
} else {
    $codigo_barras = 'ID' . date('YmdHis');
}

date_default_timezone_set("America/Santiago");

// 🖨️ MÉTODOS DE CONEXIÓN MÚLTIPLES
$printer = null;
$impresora_usada = '';

// 1️⃣ MÉTODO 1: Conexión por puerto directo (basado en wmic)
$puertos_directos = [
    "USB003",  // El puerto real de POSESTACIONAMIENTO
    "USB004",  // Star BSC10
    "USB005",  // Star BSC10 (Copiar 1)
    "USB006",  // Star BSC10 (Copiar 2)
    "LPT1:",   // Puerto paralelo
    "COM1:",   // Puerto serie
];

foreach ($puertos_directos as $puerto) {
    try {
        $connector = new FilePrintConnector($puerto);
        $printer = new Printer($connector);
        $impresora_usada = "Puerto $puerto";
        break;
    } catch (Exception $e) {
        continue;
    }
}

// 2️⃣ MÉTODO 2: Nombres de impresora exactos (sin espacios)
if (!$printer) {
    $nombres_exactos = [
        "POSESTACIONAMIENTO",
        "Star BSC10",
        "POS4",
        "POS3", 
        "POS2",
        "POS1"
    ];
    
    foreach ($nombres_exactos as $nombre) {
        try {
            $connector = new WindowsPrintConnector($nombre);
            $printer = new Printer($connector);
            $impresora_usada = $nombre;
            break;
        } catch (Exception $e) {
            continue;
        }
    }
}

// 3️⃣ MÉTODO 3: Crear archivo temporal y enviarlo a la impresora
if (!$printer) {
    try {
        $temp_file = tempnam(sys_get_temp_dir(), 'ticket_');
        $connector = new FilePrintConnector($temp_file);
        $printer = new Printer($connector);
        $impresora_usada = "archivo temporal: $temp_file";
    } catch (Exception $e) {
        echo "Error: No se pudo conectar a ninguna impresora.";
        exit;
    }
}

// 📄 IMPRIMIR TICKET
try {
    // Configurar formato
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Encabezado más simple
    $printer->text("\n");
    $printer->text("INVERSIONES ROSNER\n");
    $printer->text("Estacionamiento y Lavado\n");
    $printer->text("Perez Rosales #733-C\n");
    $printer->text("Tel: 63 2 438535\n");
    $printer->text("======================\n");
    $printer->text("Fecha: " . date("d-m-Y H:i:s") . "\n");
    $printer->text("======================\n");
    
    // Detalles del servicio
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("INGRESO:\n");
    
    if (!empty($nombre_cliente)) {
        $printer->text("Cliente: " . $nombre_cliente . "\n");
    }
    
    $printer->text("Servicio: " . $servicio_cliente . "\n");
    $printer->text("Patente: " . $patente . "\n");
    $printer->text("ID: " . $codigo_barras . "\n");
    
    // Código de barras (simplificado)
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("\n");
    
    if (!empty($codigo_barras) && strlen($codigo_barras) >= 3) {
        try {
            // Solo usar números para el código de barras
            $codigo_numerico = preg_replace('/[^0-9]/', '', $codigo_barras);
            if (strlen($codigo_numerico) >= 3) {
                $printer->barcode($codigo_numerico, Printer::BARCODE_CODE39);
            }
            $printer->text("\n");
        } catch (Exception $e) {
            // Si falla, continuar sin código de barras
        }
    }
    
    // Pie de página
    $printer->text("\n");
    $printer->text("GRACIAS POR SU PREFERENCIA\n");
    $printer->text("======================\n");
    
    // Finalizar
    $printer->feed(3);
    
    // Cerrar sin errores
    @$printer->cut();
    @$printer->close();
    
    // 🔧 Si usamos archivo temporal, enviarlo a la impresora
    if (strpos($impresora_usada, 'archivo temporal') !== false) {
        $archivo = str_replace('archivo temporal: ', '', $impresora_usada);
        
        // Intentar enviar el archivo a diferentes impresoras
        $comandos = [
            "copy /b \"$archivo\" POSESTACIONAMIENTO",
            "copy /b \"$archivo\" \"Star BSC10\"",
            "copy /b \"$archivo\" USB003",
            "type \"$archivo\" > POSESTACIONAMIENTO"
        ];
        
        foreach ($comandos as $comando) {
            exec($comando . " 2>nul", $output, $returnCode);
            if ($returnCode === 0) {
                $impresora_usada .= " -> enviado con: $comando";
                break;
            }
        }
        
        // Limpiar archivo temporal
        @unlink($archivo);
    }
    
    // ✅ Respuesta exitosa
    echo "1";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    
    if (isset($printer)) {
        @$printer->close();
    }
}
?>