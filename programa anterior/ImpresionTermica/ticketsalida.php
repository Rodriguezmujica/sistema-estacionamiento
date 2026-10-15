<?php
session_start();

require __DIR__ . '/ticket/autoload.php'; //Nota: si renombraste la carpeta a algo diferente de "ticket" cambia el nombre en esta línea
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/*
	Este ejemplo imprime un
	ticket de venta desde una impresora térmica
*/


/*
    Aquí, en lugar de "POS" (que es el nombre de mi impresora)
	escribe el nombre de la tuya. Recuerda que debes compartirla
	desde el panel de control
*/

$nombre_impresora = "POSESTACIONAMIENTO"; 

//recibo parametros


$hora_ingreso=$_POST["hora_ingreso"];
$hora_egreso=$_POST["hora_egreso"];
$total=$_POST["total"];
$patente=$_POST["patente"];
$metodo_pago = $_POST["metodo_pago"] ?? 'MANUAL';
$motivo_manual = $_POST["motivo_manual"] ?? null;

// Usar el nombre oficial de la zona horaria para evitar problemas con cambios de hora.
date_default_timezone_set("America/Santiago");

$connector = new WindowsPrintConnector($nombre_impresora);
$printer = new Printer($connector);
#Mando un numero de respuesta para saber que se conecto correctamente.
echo 1;
/*
	Vamos a imprimir un logotipo
	opcional. Recuerda que esto
	no funcionará en todas las
	impresoras

	Pequeña nota: Es recomendable que la imagen no sea
	transparente (aunque sea png hay que quitar el canal alfa)
	y que tenga una resolución baja. En mi caso
	la imagen que uso es de 250 x 250
*/

# Vamos a alinear al centro lo próximo que imprimamos
$printer->setJustification(Printer::JUSTIFY_CENTER);

/*
	Intentaremos cargar e imprimir
	el logo
*/
try{
    $logo_path = __DIR__ . "/geek.png";
    if (file_exists($logo_path)) {
        $logo = EscposImage::load($logo_path, false);
        $printer->bitImage($logo);
        $printer->text("\n"); // Espacio después del logo
    }
}catch(Exception $e){/*No hacemos nada si hay error*/}

/*
	Ahora vamos a imprimir un encabezado
*/

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
$printer->text("Instagram: lavadodeautoslosrios\n");
$printer->text("================================\n");

// ========================================
// 📅 FECHA Y HORA DE SALIDA
// ========================================
$printer->text("\n");
$printer->setEmphasis(true);
$printer->text("** COMPROBANTE DE SALIDA **\n");
$printer->setEmphasis(false);
$printer->text("Fecha: " . date("d-m-Y") . "\n");
$printer->text("Hora:  " . date("H:i:s") . "\n");
$printer->text("================================\n");

// ========================================
// 🚗 DETALLES DEL SERVICIO
// ========================================
$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer->text("\n");

$printer->text("PATENTE:\n");
$printer->setEmphasis(true);
$printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
$printer->text("  " . strtoupper($patente) . "\n");
$printer->selectPrintMode(); // Reset
$printer->setEmphasis(false);
$printer->text("\n");

$printer->text("HORA INGRESO:\n");
$printer->setEmphasis(true);
$printer->text("  " . $hora_ingreso . "\n");
$printer->setEmphasis(false);
$printer->text("\n");

$printer->text("HORA SALIDA:\n");
$printer->setEmphasis(true);
$printer->text("  " . $hora_egreso . "\n");
$printer->setEmphasis(false);
$printer->text("\n");

// ========================================
// 💰 TOTAL Y MÉTODO DE PAGO
// ========================================
$printer->setJustification(Printer::JUSTIFY_CENTER);
$printer->text("================================\n");
$printer->setEmphasis(true);
$printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
$printer->text("TOTAL: $" . number_format($total, 0, ',', '.') . "\n");
$printer->selectPrintMode(); // Reset
$printer->setEmphasis(false);
$printer->text("================================\n");

$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer->text("\nMÉTODO DE PAGO:\n");
$printer->setEmphasis(true);
$printer->text("  " . $metodo_pago . "\n");
$printer->setEmphasis(false);

// Imprimir el motivo solo si existe (para pagos manuales)
if ($motivo_manual) {
    $printer->text("\nMOTIVO:\n");
    $printer->setEmphasis(true);
    $printer->text("  " . $motivo_manual . "\n");
    $printer->setEmphasis(false);
}
	     

	 
	/*Alinear a la izquierda para la cantidad y el nombre*/
	 
/*
	Terminamos de imprimir
	los productos, ahora va el total
*/
// ========================================
// 👋 PIE DE PÁGINA MEJORADO
// ========================================
 
 

 
 $printer->setJustification(Printer::JUSTIFY_CENTER);
 
 

$printer->text("\n");
$printer->text("================================\n");
$printer->setEmphasis(true);
$printer->text("GRACIAS POR SU PREFERENCIA\n");
$printer->setEmphasis(false);
$printer->text("Vuelva pronto\n");
$printer->text("================================\n");



/*Alimentamos el papel 3 veces*/
$printer->feed(3);

/*
	Cortamos el papel. Si nuestra impresora
	no tiene soporte para ello, no generará
	ningún error
*/
$printer->cut();

/*
	Por medio de la impresora mandamos un pulso.
	Esto es útil cuando la tenemos conectada
	por ejemplo a un cajón
*/
$printer->pulse();

/*
	Para imprimir realmente, tenemos que "cerrar"
	la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
*/
$printer->close();
 
?>