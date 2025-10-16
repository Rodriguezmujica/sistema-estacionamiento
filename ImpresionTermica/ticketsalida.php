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
	$logo = EscposImage::load("geek.png", false);
   $printer->bitImage($logo);
}catch(Exception $e){/*No hacemos nada si hay error*/}

/*
	Ahora vamos a imprimir un encabezado
*/

$printer->text("\n"."INVERSIONES ROSNER" . "\n");
$printer->text("Estacionamiento y Lavado" . "\n");
$printer->text("Perez Rosales #733-C" . "\n");
$printer->text("Los Rios, Chile" . "\n");
$printer->text("Tel: +56 9 3395 8739" . "\n");
$printer->text("Instagram: lavadodeautoslosrios" . "\n");
$printer->text("================================" . "\n");
$printer->text("Fecha: ");
$printer->text(date("d-m-Y") . "\n");
$printer->text("================================" . "\n");
$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer->text("COMPROBANTE DE SALIDA \n");
$printer->text("-----------------------------"."\n\n");

	$printer->setJustification(Printer::JUSTIFY_LEFT);
	 
		$printer->text("Hora ingreso: ".$hora_ingreso. " \n");
	    $printer->text("Hora salida: ".$hora_egreso. " \n");
	 	$printer->text("patente: ".$patente. " \n");
		$printer->text("Total: $".$total. " \n");
		$printer->text("Metodo Pago: ".$metodo_pago. " \n");
		// Imprimir el motivo solo si existe (para pagos manuales)
		if ($motivo_manual) {
			$printer->text("Motivo: ".$motivo_manual. " \n");
		}
	     

	 
	/*Alinear a la izquierda para la cantidad y el nombre*/
	 
/*
	Terminamos de imprimir
	los productos, ahora va el total
*/
$printer->text("-----------------------------"."\n");
 
 

 
 $printer->setJustification(Printer::JUSTIFY_CENTER);
 
 

$printer->text("\n");
/*
	Podemos poner también un pie de página
*/
 
$printer->text("Muchas gracias por su preferencia\n");



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