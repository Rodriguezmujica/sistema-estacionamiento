<?php
/**
 * 🔍 DIAGNÓSTICO DE NAVEGACIÓN ADMIN
 * Para verificar por qué el logo no lleva a index.php
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Diagnóstico Navegación Admin</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".test-link { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }";
echo ".test-link:hover { background: #0056b3; color: white; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 Diagnóstico de Navegación Admin</h1>";

// Obtener información del servidor
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$current_dir = dirname($script_name);

echo "<div class='info'>";
echo "<h3>📊 Información del Servidor:</h3>";
echo "<ul>";
echo "<li><strong>Protocolo:</strong> $protocol</li>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Script actual:</strong> $script_name</li>";
echo "<li><strong>Directorio actual:</strong> $current_dir</li>";
echo "</ul>";
echo "</div>";

// Verificar rutas
echo "<h2>1. Verificación de Rutas</h2>";

$rutas_a_verificar = [
    '../index.php' => 'Ruta desde admin.php a index.php',
    './index.php' => 'Ruta alternativa',
    '/sistemaEstacionamiento/index.php' => 'Ruta absoluta',
    'index.php' => 'Ruta relativa simple'
];

foreach ($rutas_a_verificar as $ruta => $descripcion) {
    $ruta_completa = $protocol . '://' . $host . $current_dir . '/' . $ruta;
    echo "<div class='info'>";
    echo "<strong>$descripcion:</strong><br>";
    echo "Ruta: <code>$ruta</code><br>";
    echo "URL completa: <a href='$ruta_completa' target='_blank'>$ruta_completa</a>";
    echo "</div>";
}

// Verificar archivos
echo "<h2>2. Verificación de Archivos</h2>";

$archivos_a_verificar = [
    '../index.php' => 'index.php (desde admin.php)',
    './index.php' => 'index.php (ruta alternativa)',
    '../imagenes/Logo_sin_fondo.png' => 'Logo (desde admin.php)',
    './imagenes/Logo_sin_fondo.png' => 'Logo (ruta alternativa)'
];

foreach ($archivos_a_verificar as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        $tamaño = filesize($archivo);
        echo "<div class='success'>✅ $descripcion existe ($tamaño bytes)</div>";
    } else {
        echo "<div class='error'>❌ $descripcion NO existe</div>";
    }
}

// Probar navegación
echo "<h2>3. Pruebas de Navegación</h2>";

echo "<div class='info'>";
echo "<h3>🔗 Enlaces de Prueba:</h3>";
echo "<p>Haz clic en estos enlaces para probar la navegación:</p>";

$enlaces_prueba = [
    '../index.php' => 'Desde admin.php (ruta actual)',
    './index.php' => 'Ruta alternativa',
    '/sistemaEstacionamiento/index.php' => 'Ruta absoluta'
];

foreach ($enlaces_prueba as $ruta => $descripcion) {
    $url_completa = $protocol . '://' . $host . $current_dir . '/' . $ruta;
    echo "<a href='$url_completa' class='test-link' target='_blank'>$descripcion</a>";
}
echo "</div>";

// Verificar JavaScript
echo "<h2>4. Verificación de JavaScript</h2>";

echo "<div class='info'>";
echo "<h3>🔧 Posibles problemas de JavaScript:</h3>";
echo "<ul>";
echo "<li>Eventos que interfieren con el enlace</li>";
echo "<li>Prevención del comportamiento por defecto</li>";
echo "<li>Errores de JavaScript que bloquean la navegación</li>";
echo "</ul>";
echo "</div>";

// Generar código corregido
echo "<h2>5. Código Corregido Sugerido</h2>";

echo "<div class='success'>";
echo "<h3>✅ Código HTML corregido para admin.php:</h3>";
echo "<pre>";
echo htmlspecialchars('<a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php" onclick="return true;">
  <img src="../imagenes/Logo_sin_fondo.png" alt="Logo" height="40" class="me-2">
  Estacionamiento Los Ríos
</a>');
echo "</pre>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Si el problema persiste, prueba estas alternativas:</h3>";
echo "<pre>";
echo htmlspecialchars('<!-- Opción 1: Ruta absoluta -->
<a class="navbar-brand fw-bold d-flex align-items-center" href="/sistemaEstacionamiento/index.php">
  <img src="../imagenes/Logo_sin_fondo.png" alt="Logo" height="40" class="me-2">
  Estacionamiento Los Ríos
</a>

<!-- Opción 2: Con JavaScript -->
<a class="navbar-brand fw-bold d-flex align-items-center" href="#" onclick="window.location.href=\'../index.php\'; return false;">
  <img src="../imagenes/Logo_sin_fondo.png" alt="Logo" height="40" class="me-2">
  Estacionamiento Los Ríos
</a>

<!-- Opción 3: Con base href -->
<base href="/sistemaEstacionamiento/">
<a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
  <img src="imagenes/Logo_sin_fondo.png" alt="Logo" height="40" class="me-2">
  Estacionamiento Los Ríos
</a>');
echo "</pre>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔍 Para debuggear en el navegador:</h3>";
echo "<ol>";
echo "<li>Abre las herramientas de desarrollador (F12)</li>";
echo "<li>Ve a la pestaña Console</li>";
echo "<li>Haz clic en el logo y ve si aparecen errores</li>";
echo "<li>Ve a la pestaña Network para ver si se hace la petición</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
