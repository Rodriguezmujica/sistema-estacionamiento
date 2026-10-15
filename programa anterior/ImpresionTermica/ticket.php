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
$servicio_cliente = trim($_POST["servicio_cliente"] ?? 'Estacionamiento');
$patente = strtoupper(trim($_POST["patente"] ?? ''));
$tipo_ingreso = $_POST["tipo_ingreso"] ?? '';

// Debug: Verificar que se recibe el servicio correctamente
if (empty($servicio_cliente) || $servicio_cliente === '') {
    $servicio_cliente = 'Estacionamiento';
}

if (empty($patente)) {
    $patente = 'SIN-PATENTE';
}

// 🔧 GENERAR CÓDIGO DE BARRAS VÁLIDO PARA CODE39
// CODE39 soporta: 0-9, A-Z, espacios y los caracteres especiales: $ % + - . /
$codigo_barras = '';
if (!empty($tipo_ingreso) && $tipo_ingreso !== 'undefined' && $tipo_ingreso !== 'null') {
    // Limpiar pero mantener letras y números (CODE39 los soporta)
    $codigo_limpio = strtoupper(preg_replace('/[^0-9A-Z]/', '', $tipo_ingreso));
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
    // ========================================
    // 🖼️ LOGO DEL NEGOCIO
    // ========================================
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Intentar cargar e imprimir el logo
    try {
        $logo_path = __DIR__ . "/geek.png";
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
    $printer->text("Perez Rosales #733-C\n");
    $printer->text("Los Rios, Chile\n");
    $printer->text("Tel:+56 9 3395 8739\n");
    
    // ========================================
    // 📅 ENCABEZADO PRINCIPAL
    // ========================================
    $printer->setEmphasis(true);
    $printer->setTextSize(2, 1);
    $printer->text("ESTACIONAMIENTO\n");
    $printer->text("TICKET DE INGRESO\n");
    $printer->setTextSize(1, 1);
    $printer->setEmphasis(false);
    $printer->text("--------------------------------\n");
    
    // ========================================
    // 🚗 DETALLES DEL SERVICIO
    // ========================================
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    
    // Formato que coincide con la imagen
    $printer->text("Ticket: " . $codigo_barras . "\n");
    $printer->text("Patente: " . $patente . "\n");
    $printer->text("Tipo: " . $servicio_cliente . "\n");
    $printer->text("Entrada: " . date("d/m/Y") . "\n");
    $printer->text("Hora: " . date("H:i:s") . "\n");
    $printer->text("--------------------------------\n");
    
    // ========================================
    // 📊 CÓDIGO DE BARRAS
    // ========================================
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    if (!empty($codigo_barras) && strlen($codigo_barras) >= 3) {
        try {
            // CODE39 soporta letras y números
            $printer->barcode($codigo_barras, Printer::BARCODE_CODE39);
            $printer->text("\n");
            $printer->text($codigo_barras . "\n");
        } catch (Exception $e) {
            // Si falla el código de barras, mostrar solo el ID
            $printer->text("ID: " . $codigo_barras . "\n");
        }
    }
    
    // ========================================
    // 👋 PIE DE PÁGINA
    // ========================================
    $printer->text("Conserve este ticket\n");
    $printer->text("Gracias por su visita\n");
    
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