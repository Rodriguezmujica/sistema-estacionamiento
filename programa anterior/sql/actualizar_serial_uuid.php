<?php
/**
 * Actualizar a UUID en lugar de ID de dispositivo
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

if ($conexion->connect_error) {
    die("<h2 style='color: red;'>❌ Error de conexión: " . $conexion->connect_error . "</h2>");
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h2 { color: #1976d2; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0dcaf0; border-radius: 4px; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #333; color: white; }
</style>";

echo "<h2>🔧 Actualización a UUID de TUU</h2>";
echo "<hr>";

echo "<div class='info'>";
echo "<h3>🔍 Análisis del Error MR-100:</h3>";
echo "<p>El error indica que el <strong>Device ID (57964)</strong> no está asociado al API Key actual.</p>";
echo "<p>Vamos a probar con los otros identificadores disponibles:</p>";
echo "</div>";

// Probar con diferentes identificadores
$identificadores = [
    'UUID' => '45497a8bbe58fd01',
    'Número de Serie' => '6010b232511900354',
    'ID Original' => '6752d2805d5b1d86'
];

echo "<table>";
echo "<tr><th>Tipo</th><th>Valor</th><th>Acción</th></tr>";
foreach ($identificadores as $tipo => $valor) {
    echo "<tr>";
    echo "<td><strong>$tipo</strong></td>";
    echo "<td><code>$valor</code></td>";
    echo "<td><button onclick=\"actualizar('$valor', '$tipo')\">Usar este</button></td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='warning'>";
echo "<h3>⚠️ Cómo encontrar el identificador correcto:</h3>";
echo "<ol>";
echo "<li>Ve a tu panel TUU: <a href='https://espacio.haulmer.com' target='_blank'>espacio.haulmer.com</a></li>";
echo "<li>Login con tu cuenta</li>";
echo "<li>Ve a: <strong>Configuración → Dispositivos</strong> (o similar)</li>";
echo "<li>Busca tu dispositivo activo</li>";
echo "<li>Verifica qué campo dice <strong>'Device ID'</strong> o <strong>'Serial para API'</strong></li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<h3>Actualización Automática con UUID:</h3>";

// Intentar con UUID por defecto
$uuid = '45497a8bbe58fd01';

$sql = "UPDATE configuracion_tuu 
        SET device_serial = ? 
        WHERE maquina = 'principal'";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $uuid);

if ($stmt->execute()) {
    echo "<div class='success'>";
    echo "<h3>✅ Actualizado a UUID</h3>";
    echo "<p><strong>Nuevo Device Serial:</strong> <code>$uuid</code></p>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
}

$stmt->close();
$conexion->close();

echo "<hr>";
echo "<div class='info'>";
echo "<h3>🧪 Próximo paso: Probar</h3>";
echo "<p>1. <a href='../test_tuu.html' target='_blank'><strong>Abre test_tuu.html</strong></a></p>";
echo "<p>2. Haz clic en 'Actualizar Configuración' para ver el nuevo serial</p>";
echo "<p>3. Prueba el pago nuevamente</p>";
echo "</div>";

echo "<hr>";
echo "<div class='warning'>";
echo "<h3>❓ Si el error persiste:</h3>";
echo "<p><strong>El problema podría ser:</strong></p>";
echo "<ol>";
echo "<li><strong>API Key incorrecta:</strong> Verifica en el panel TUU que el API Key sea el correcto</li>";
echo "<li><strong>Dispositivo no vinculado:</strong> El dispositivo no está asociado a ese Workspace</li>";
echo "<li><strong>Modo integración no habilitado:</strong> En el panel TUU, asegúrate de activar 'Modo Integración'</li>";
echo "</ol>";
echo "<p style='margin-top: 20px;'><strong>👉 Copia el 'Device Serial' o 'Device ID' exacto desde tu panel TUU y dímelo.</strong></p>";
echo "</div>";

?>

<script>
function actualizar(valor, tipo) {
    if (confirm('¿Actualizar Device Serial a ' + tipo + '?')) {
        fetch('actualizar_serial_uuid.php?serial=' + valor + '&tipo=' + tipo)
            .then(response => response.text())
            .then(data => {
                alert('Actualizado a: ' + valor);
                location.reload();
            });
    }
}
</script>

