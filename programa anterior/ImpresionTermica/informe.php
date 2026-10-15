 
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
$usuario = "root";
	$password = "";
	$servidor = "localhost";
	$basededatos = "estacionamiento";
	
	// creación de la conexión a la base de datos con mysql_connect()
	$conexion = mysqli_connect( $servidor, $usuario, "" ) or die ("No se ha podido conectar al servidor de Base de datos");
	
	// Selección del a base de datos a utilizar
	$db = mysqli_select_db( $conexion, $basededatos ) or die ( "Upps! Pues va a ser que no se ha podido conectar a la base de datos" );
	// establecer y realizar consulta. guardamos en variable.
	$fecha= date("Y-m-d");
		$hora=$fecha." 00:00:00";
  $consulta = "SELECT COUNT(*) as cantidad,SUM(s.total) as total,t.nombre_servicio,t.es_plan FROM ingresos i join salidas s on i.idautos_estacionados=s.id_ingresos join tipo_ingreso t on i.idtipo_ingreso=t.idtipo_ingresos and i.fecha_ingreso>='$hora' GROUP BY `t`.`nombre_servicio`"; 
	 
	$resultado = mysqli_query( $conexion, $consulta ) or die ( "Algo ha ido mal en la consulta a la base de datos");
	
	 
	
	// Bucle while que recorre cada registro y muestra cada campo en la tabla.
	
	
	 
	
# Vamos a alinear al centro lo próximo que imprimamos
$printer->setJustification(Printer::JUSTIFY_CENTER);

/*
	Intentaremos cargar e imprimir
	el logo
*/
try{
	//$logo = EscposImage::load("geek.png", false);
   //$printer->bitImage($logo);
}catch(Exception $e){/*No hacemos nada si hay error*/}

/*
	Ahora vamos a imprimir un encabezado
*/

$printer->text("\n"."INFORME DIARIO" . "\n");
 
date_default_timezone_set("America/Santiago");
 $printer->text("Fecha  ");
$printer->text(date("d-m-Y") . "\n");
$printer->text("------------------------------------------" . "\n");
$printer->setJustification(Printer::JUSTIFY_CENTER);
$printer->text("CANTIDAD     NOMBRE DE SERVICIO     TOTAL  \n");
$printer->text("------------------------------------------"."\n\n");

 
 
/*
	Ahora vamos a imprimir los
	productos
*/
	$printer->setJustification(Printer::JUSTIFY_CENTER);
	$suma=0;
	 while ($columna = mysqli_fetch_array( $resultado ))
	{
		$printer->setJustification(Printer::JUSTIFY_LEFT);
		 $printer->text(str_pad($columna['cantidad'], 4," ",STR_PAD_BOTH));
		 
		 $printer->text(str_pad($columna['nombre_servicio'], 32," ",STR_PAD_BOTH));
		  $printer->setJustification(Printer::JUSTIFY_RIGHT);
		 $printer->text(str_pad($columna['total'], 5," ",STR_PAD_LEFT)." \n");
		 $suma+=$columna['total'];
	}
		 
	     

	 
	/*Alinear a la izquierda para la cantidad y el nombre*/
	 
/*
	Terminamos de imprimir
	los productos, ahora va el total
*/
	$printer->setJustification(Printer::JUSTIFY_CENTER);
$printer->text("------------------------------------------\n");
$printer->setJustification(Printer::JUSTIFY_RIGHT);
$printer->text("TOTAL: ".$suma."\n");
 
 

 
 $printer->setJustification(Printer::JUSTIFY_RIGHT);
 
 

$printer->text("\n");
/*
	Podemos poner también un pie de página
*/
 



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
mysqli_close( $conexion );
$printer->close();
 
?>