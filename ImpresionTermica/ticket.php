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
$nombre_cliente = $_POST["nombre_cliente"] ?? '';
$servicio_cliente = $_POST["servicio_cliente"] ?? 'No especificado';
$patente = $_POST["patente"] ?? '';
$tipo_ingreso = $_POST["tipo_ingreso"] ?? ''; // Para el código de barras

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

$printer->text("\n"."Inversiones Rosner" . "\n");
$printer->text("Estacionamiento y lavado de autos" . "\n");
$printer->text("Direccion: Perez Rosales #733-C" . "\n");
$printer->text("Teléfono: 63 2 438535" . "\n");
date_default_timezone_set("America/Santiago");
 $printer->text("Fecha  ");
$printer->text(date("d-m-Y") . "\n");
$printer->text("-----------------------------" . "\n");
$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer->text("DETALLE INGRESO \n");
$printer->text("-----------------------------"."\n\n");

#La fecha también
date_default_timezone_set("America/Santiago");
$printer->text("Hora de ingreso:  ");
$printer->text(date("H:i:s") . "\n");
/*
	Ahora vamos a imprimir los
	productos
*/
	$printer->setJustification(Printer::JUSTIFY_LEFT);
	if($nombre_cliente!=""){
		
		$printer->text("Nombre cliente: ".$nombre_cliente. " \n");
	    $printer->text("Servicio: ".$servicio_cliente. " \n");
	}else{
		$printer->text("Servicio: ".$servicio_cliente. " \n");
	     

	}
	/*Alinear a la izquierda para la cantidad y el nombre*/
	 
/*
	Terminamos de imprimir
	los productos, ahora va el total
*/
$printer->text("-----------------------------"."\n");
 
 

 
 $printer->setJustification(Printer::JUSTIFY_CENTER);
 $patente=strtoupper($patente);
$printer->barcode($tipo_ingreso, Printer::BARCODE_CODE39);
 
$printer->text("\n");
$printer->text($patente." \n");

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
//$printer->pulse();

/*
	Para imprimir realmente, tenemos que "cerrar"
	la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
*/
$printer->close();
unset($_SESSION['nombreCliente']);
unset($_SESSION['nombre_servicio']);
?>