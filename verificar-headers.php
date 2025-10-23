<?php
/**
 * 🔍 VERIFICADOR DE HEADERS
 * Verifica que todos los archivos tengan el logo correcto en el header
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Verificador de Headers</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 Verificador de Headers</h1>";

// Archivos a verificar
$archivos = [
    'index.php' => 'Archivo principal',
    'secciones/admin.php' => 'Panel de administración',
    'secciones/cobro.php' => 'Sistema de cobro',
    'secciones/lavados.html' => 'Página de lavados',
    'secciones/reporte.html' => 'Página de reportes',
    'secciones/productos.html' => 'Página de productos',
    'secciones/quienes-somos.html' => 'Página quiénes somos',
    'secciones/tutoriales.html' => 'Página de tutoriales'
];

$correctos = 0;
$incorrectos = 0;

foreach ($archivos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        $contenido = file_get_contents($archivo);
        
        // Verificar si tiene el logo correcto
        $tiene_logo = strpos($contenido, '<img src="../imagenes/Logo_sin_fondo.png"') !== false || 
                      strpos($contenido, '<img src="./imagenes/Logo_sin_fondo.png"') !== false;
        
        // Verificar si tiene el enlace correcto
        $tiene_enlace = strpos($contenido, 'href="../index.php"') !== false || 
                        strpos($contenido, 'href="./index.php"') !== false;
        
        // Verificar si tiene la clase correcta
        $tiene_clase = strpos($contenido, 'class="navbar-brand fw-bold d-flex align-items-center"') !== false;
        
        if ($tiene_logo && $tiene_enlace && $tiene_clase) {
            echo "<div class='success'>✅ $descripcion - Header correcto</div>";
            $correctos++;
        } else {
            echo "<div class='error'>❌ $descripcion - Header necesita corrección</div>";
            echo "<div class='info'>";
            echo "Logo: " . ($tiene_logo ? "✅" : "❌") . " | ";
            echo "Enlace: " . ($tiene_enlace ? "✅" : "❌") . " | ";
            echo "Clase: " . ($tiene_clase ? "✅" : "❌");
            echo "</div>";
            $incorrectos++;
        }
    } else {
        echo "<div class='warning'>⚠️ $descripcion - Archivo no encontrado</div>";
    }
}

echo "<div class='info'>";
echo "<h3>📊 Resumen:</h3>";
echo "<ul>";
echo "<li>Correctos: $correctos</li>";
echo "<li>Incorrectos: $incorrectos</li>";
echo "<li>Total verificados: " . count($archivos) . "</li>";
echo "</ul>";
echo "</div>";

if ($incorrectos > 0) {
    echo "<div class='warning'>";
    echo "<h3>🛠️ Template correcto para copiar:</h3>";
    echo "<pre>";
    echo htmlspecialchars('<a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
  <img src="../imagenes/Logo_sin_fondo.png" alt="Logo" height="40" class="me-2">
  Estacionamiento Los Ríos
</a>');
    echo "</pre>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
