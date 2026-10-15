<?php
/**
 * Script para crear un logo simple en blanco y negro
 * Útil si el logo original tiene problemas de formato o GD
 * Ejecutar: php tests/crear_logo_simple.php
 */

echo "═══════════════════════════════════════════════════════════════\n";
echo "🎨 CREANDO LOGO SIMPLE EN BLANCO Y NEGRO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar que GD está instalado
if (!extension_loaded('gd')) {
    echo "❌ GD no está instalado\n";
    echo "💡 Ejecuta: sudo apt-get install php-gd\n";
    exit(1);
}

// Dimensiones recomendadas para impresoras térmicas (58mm = ~250px)
$width = 250;
$height = 100;

echo "1️⃣ Creando imagen base ($width x $height px)...\n";
$img = imagecreatetruecolor($width, $height);

// Colores
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);

// Fondo blanco
imagefilledrectangle($img, 0, 0, $width, $height, $white);

// Texto del logo
$text = "INVERSIONES\nROSNER";
$font_size = 4; // Fuente bitmap interna de GD

// Calcular posición para centrar
$text_lines = explode("\n", $text);
$line_height = imagefontheight($font_size);
$total_height = count($text_lines) * $line_height;

$y = ($height - $total_height) / 2;

echo "2️⃣ Dibujando texto...\n";
foreach ($text_lines as $line) {
    $line_width = imagefontwidth($font_size) * strlen($line);
    $x = ($width - $line_width) / 2;
    
    imagestring($img, $font_size, $x, $y, $line, $black);
    $y += $line_height;
}

// Guardar como PNG
echo "3️⃣ Guardando como PNG...\n";
$output_png = __DIR__ . '/../ImpresionTermica/geek_logo_simple.png';
if (imagepng($img, $output_png)) {
    echo "   ✅ Logo PNG creado: geek_logo_simple.png\n";
} else {
    echo "   ❌ Error al crear logo PNG\n";
    exit(1);
}

// Guardar como JPG también
echo "4️⃣ Guardando como JPG...\n";
$output_jpg = __DIR__ . '/../imagenes/image_logo_simple.jpg';
if (imagejpeg($img, $output_jpg, 90)) {
    echo "   ✅ Logo JPG creado: image_logo_simple.jpg\n";
} else {
    echo "   ⚠️ No se pudo crear JPG (puede que JPEG Support no esté habilitado)\n";
}

imagedestroy($img);

// Intentar crear versión mejorada con FreeType si está disponible
if (function_exists('imagettftext')) {
    echo "\n5️⃣ Creando versión mejorada con FreeType...\n";
    
    $img_large = imagecreatetruecolor(500, 200);
    imagefilledrectangle($img_large, 0, 0, 500, 200, $white);
    
    // Buscar fuente TrueType común
    $fonts = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
    ];
    
    $font_encontrada = null;
    foreach ($fonts as $font) {
        if (file_exists($font)) {
            $font_encontrada = $font;
            break;
        }
    }
    
    if ($font_encontrada) {
        echo "   Usando fuente: " . basename($font_encontrada) . "\n";
        imagettftext($img_large, 40, 0, 50, 100, $black, $font_encontrada, "INVERSIONES");
        imagettftext($img_large, 32, 0, 50, 150, $black, $font_encontrada, "ROSNER");
        
        $output_large = __DIR__ . '/../ImpresionTermica/geek_logo_large.png';
        if (imagepng($img_large, $output_large)) {
            echo "   ✅ Logo grande creado: geek_logo_large.png\n";
        }
        imagedestroy($img_large);
    } else {
        echo "   ⚠️ No se encontró fuente TrueType, usando logo simple\n";
    }
} else {
    echo "5️⃣ FreeType no disponible, usando logo simple\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ LOGOS CREADOS EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n📄 Archivos creados:\n";
echo "   - ImpresionTermica/geek_logo_simple.png\n";
echo "   - imagenes/image_logo_simple.jpg\n";

if (file_exists(__DIR__ . '/../ImpresionTermica/geek_logo_large.png')) {
    echo "   - ImpresionTermica/geek_logo_large.png\n";
}

echo "\n💡 Para usarlos:\n";
echo "   Opción 1: Renombrar los archivos originales\n";
echo "     mv ImpresionTermica/geek_logo_simple.png ImpresionTermica/geek.png\n";
echo "     mv imagenes/image_logo_simple.jpg imagenes/image.jpg\n";
echo "\n   Opción 2: Modificar el código para usar estos archivos\n";
echo "\n🧪 Probar los logos:\n";
echo "   php tests/test_imprimir_logo.php\n";
echo "\n";
?>

