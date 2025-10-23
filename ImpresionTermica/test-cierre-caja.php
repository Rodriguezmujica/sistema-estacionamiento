<?php
/**
 * 🧪 PRUEBA SIMPLE DE CIERRE DE CAJA
 * Para verificar si el problema está en los datos o en la impresión
 */

// Simular datos de prueba
$datosPrueba = [
    'fecha' => date('Y-m-d'),
    'total_servicios' => 5,
    'total_ingresos' => 15000,
    'efectivo_manual' => 8000,
    'tuu_efectivo' => 2000,
    'tuu_debito' => 3000,
    'tuu_credito' => 2000,
    'transferencia' => 0,
    'categorias' => json_encode([
        ['categoria' => 'Estacionamiento', 'cantidad' => 3, 'total' => 9000],
        ['categoria' => 'Lavados', 'cantidad' => 2, 'total' => 6000]
    ])
];

echo "<h2>🧪 Prueba de Cierre de Caja</h2>";
echo "<hr>";

echo "<h3>1. Datos de prueba:</h3>";
echo "<pre>";
print_r($datosPrueba);
echo "</pre>";

echo "<h3>2. Probar impresión con datos simulados:</h3>";
echo "<form method='POST' action='cierre_caja.php' target='_blank'>";
foreach ($datosPrueba as $key => $value) {
    echo "<input type='hidden' name='$key' value='$value'>";
}
echo "<button type='submit' class='btn btn-primary'>🧪 Probar Impresión con Datos Simulados</button>";
echo "</form>";

echo "<hr>";

echo "<h3>3. Probar con datos reales del día:</h3>";
echo "<form method='GET' action='debug-cierre-caja.php'>";
echo "<label>Fecha: <input type='date' name='fecha' value='" . date('Y-m-d') . "'></label><br><br>";
echo "<button type='submit' class='btn btn-secondary'>🔍 Ver Datos Reales</button>";
echo "</form>";

echo "<hr>";

echo "<h3>4. Verificar logs de error:</h3>";
echo "<p>Revisa los logs de PHP para ver los mensajes de debug:</p>";
echo "<ul>";
echo "<li>Windows: C:\\xampp\\apache\\logs\\error.log</li>";
echo "<li>Linux: /var/log/apache2/error.log</li>";
echo "<li>O ejecuta: <code>tail -f /var/log/apache2/error.log</code></li>";
echo "</ul>";
?>
