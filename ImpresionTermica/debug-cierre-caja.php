<?php
/**
 * 🔍 DIAGNÓSTICO DE CIERRE DE CAJA
 * Para verificar qué datos se están enviando
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>🔍 Diagnóstico Cierre de Caja</h2>";
echo "<hr>";

// Mostrar todos los datos recibidos
echo "<h3>1. Datos POST recibidos:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
echo "<hr>";

// Mostrar datos GET también
echo "<h3>2. Datos GET recibidos:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";
echo "<hr>";

// Verificar si los datos están vacíos
echo "<h3>3. Verificación de datos:</h3>";
$fecha = $_POST["fecha"] ?? 'NO RECIBIDO';
$total_servicios = $_POST["total_servicios"] ?? 'NO RECIBIDO';
$total_ingresos = $_POST["total_ingresos"] ?? 'NO RECIBIDO';

echo "Fecha: " . $fecha . "<br>";
echo "Total servicios: " . $total_servicios . "<br>";
echo "Total ingresos: " . $total_ingresos . "<br>";

// Verificar desglose de pagos
echo "<h3>4. Desglose de pagos:</h3>";
$efectivo_manual = $_POST["efectivo_manual"] ?? 'NO RECIBIDO';
$tuu_efectivo = $_POST["tuu_efectivo"] ?? 'NO RECIBIDO';
$tuu_debito = $_POST["tuu_debito"] ?? 'NO RECIBIDO';
$tuu_credito = $_POST["tuu_credito"] ?? 'NO RECIBIDO';
$transferencia = $_POST["transferencia"] ?? 'NO RECIBIDO';

echo "Efectivo manual: " . $efectivo_manual . "<br>";
echo "TUU efectivo: " . $tuu_efectivo . "<br>";
echo "TUU débito: " . $tuu_debito . "<br>";
echo "TUU crédito: " . $tuu_credito . "<br>";
echo "Transferencia: " . $transferencia . "<br>";

// Verificar categorías
echo "<h3>5. Categorías:</h3>";
$categorias = $_POST["categorias"] ?? 'NO RECIBIDO';
echo "Categorías (raw): " . $categorias . "<br>";

if ($categorias !== 'NO RECIBIDO') {
    $categorias_decoded = json_decode($categorias, true);
    if ($categorias_decoded) {
        echo "Categorías (decoded):<br>";
        echo "<pre>";
        print_r($categorias_decoded);
        echo "</pre>";
    } else {
        echo "❌ Error decodificando JSON de categorías<br>";
    }
}

echo "<hr>";

// Verificar si hay datos válidos
echo "<h3>6. Diagnóstico:</h3>";
if ($total_servicios === 'NO RECIBIDO' || $total_ingresos === 'NO RECIBIDO') {
    echo "❌ <strong>PROBLEMA:</strong> Los datos básicos no se están recibiendo<br>";
    echo "💡 <strong>SOLUCIÓN:</strong> Verificar que el JavaScript esté enviando los datos correctamente<br>";
} else if ($total_servicios == 0 && $total_ingresos == 0) {
    echo "⚠️ <strong>ADVERTENCIA:</strong> Los datos se reciben pero están en 0<br>";
    echo "💡 <strong>SOLUCIÓN:</strong> Verificar que haya datos en la base de datos para la fecha seleccionada<br>";
} else {
    echo "✅ <strong>OK:</strong> Los datos se están recibiendo correctamente<br>";
}

echo "<hr>";
echo "<h3>7. Prueba de impresión:</h3>";
echo "<p>Si los datos se ven correctos, puedes probar la impresión:</p>";
echo "<form method='POST' action='cierre_caja.php'>";
echo "<input type='hidden' name='fecha' value='$fecha'>";
echo "<input type='hidden' name='total_servicios' value='$total_servicios'>";
echo "<input type='hidden' name='total_ingresos' value='$total_ingresos'>";
echo "<input type='hidden' name='efectivo_manual' value='$efectivo_manual'>";
echo "<input type='hidden' name='tuu_efectivo' value='$tuu_efectivo'>";
echo "<input type='hidden' name='tuu_debito' value='$tuu_debito'>";
echo "<input type='hidden' name='tuu_credito' value='$tuu_credito'>";
echo "<input type='hidden' name='transferencia' value='$transferencia'>";
echo "<input type='hidden' name='categorias' value='$categorias'>";
echo "<button type='submit' class='btn btn-primary'>🧪 Probar Impresión</button>";
echo "</form>";
?>
