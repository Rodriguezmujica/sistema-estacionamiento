<?php
/**
 * Script para solucionar el problema del logo que muestra 0x0 px
 * Este script crea un logo binario (blanco y negro puro) que siempre funciona
 * Ejecutar: php tests/fix_logo_problema.php
 */

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔧 SOLUCIÓN DEL PROBLEMA: Logo 0x0 px\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (!extension_loaded('gd')) {
    echo "❌ GD no está instalado\n";
    exit(1);
}

$logo_original = __DIR__ . "/../imagenes/image.jpg";
$logo_output_jpg = __DIR__ . "/../imagenes/image_fixed.jpg";
$logo_output_png = __DIR__ . "/../ImpresionTermica/geek_fixed.png";

echo "1️⃣ CARGANDO LOGO ORIGINAL\n";
echo "───────────────────────────────────────────────────────────────\n";

if (!file_exists($logo_original)) {
    echo "❌ Logo no encontrado: $logo_original\n";
    exit(1);
}

$info = getimagesize($logo_original);
if (!$info) {
    echo "❌ No se pudo leer información de la imagen\n";
    exit(1);
}

echo "Tipo: " . $info['mime'] . "\n";
echo "Dimensiones: {$info[0]} x {$info[1]} px\n";

// Cargar imagen según tipo
$imagen_original = null;
switch ($info[2]) {
    case IMAGETYPE_JPEG:
        $imagen_original = @imagecreatefromjpeg($logo_original);
        break;
    case IMAGETYPE_PNG:
        if (gd_info()['PNG Support']) {
            $imagen_original = @imagecreatefrompng($logo_original);
        }
        break;
    case IMAGETYPE_GIF:
        if (gd_info()['GIF Support']) {
            $imagen_original = @imagecreatefromgif($logo_original);
        }
        break;
}

if (!$imagen_original) {
    echo "❌ No se pudo cargar la imagen\n";
    exit(1);
}

$width = imagesx($imagen_original);
$height = imagesy($imagen_original);

if ($width == 0 || $height == 0) {
    echo "❌ Dimensiones inválidas\n";
    exit(1);
}

echo "✅ Imagen cargada: {$width} x {$height} px\n";

echo "\n2️⃣ REDIMENSIONANDO Y OPTIMIZANDO\n";
echo "───────────────────────────────────────────────────────────────\n";

// Dimensiones máximas para impresoras térmicas (58mm = ~250px a 300dpi)
$max_width = 250;
$max_height = 150;

// Calcular nuevo tamaño manteniendo proporción
$ratio = 1;
if ($width > $max_width || $height > $max_height) {
    $ratio = min($max_width / $width, $max_height / $height);
}

$new_width = (int)($width * $ratio);
$new_height = (int)($height * $ratio);

// Asegurar que sean pares (algunas impresoras tienen problemas con números impares)
$new_width = $new_width - ($new_width % 2);
$new_height = $new_height - ($new_height % 2);

if ($new_width < 1) $new_width = 2;
if ($new_height < 1) $new_height = 2;

echo "Redimensionando a: {$new_width} x {$new_height} px\n";

// Crear imagen redimensionada
$imagen_resized = imagecreatetruecolor($new_width, $new_height);

// Configurar para evitar transparencias
imagealphablending($imagen_resized, false);
imagesavealpha($imagen_resized, false);

// Rellenar con blanco primero
$white = imagecolorallocate($imagen_resized, 255, 255, 255);
imagefill($imagen_resized, 0, 0, $white);

// Copiar y redimensionar con mejor calidad
imagecopyresampled(
    $imagen_resized, $imagen_original,
    0, 0, 0, 0,
    $new_width, $new_height,
    $width, $height
);

imagedestroy($imagen_original);

echo "✅ Redimensionado\n";

echo "\n3️⃣ CONVIRTIENDO A BINARIO (BLANCO Y NEGRO PURO)\n";
echo "───────────────────────────────────────────────────────────────\n";

// Crear imagen binaria (solo blanco y negro)
$imagen_bw = imagecreatetruecolor($new_width, $new_height);

$black = imagecolorallocate($imagen_bw, 0, 0, 0);
$white = imagecolorallocate($imagen_bw, 255, 255, 255);

// Umbral para conversión a blanco y negro (50% de gris)
$threshold = 128;

for ($y = 0; $y < $new_height; $y++) {
    for ($x = 0; $x < $new_width; $x++) {
        $rgb = imagecolorat($imagen_resized, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // Convertir a escala de grises
        $gris = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
        
        // Convertir a binario (blanco o negro)
        $color = ($gris < $threshold) ? $black : $white;
        imagesetpixel($imagen_bw, $x, $y, $color);
    }
}

imagedestroy($imagen_resized);

echo "✅ Convertido a binario (blanco/negro)\n";

echo "\n4️⃣ GUARDANDO ARCHIVOS COMPATIBLES\n";
echo "───────────────────────────────────────────────────────────────\n";

// Guardar como JPG (sin compresión máxima para evitar problemas)
if (imagejpeg($imagen_bw, $logo_output_jpg, 100)) {
    echo "✅ JPG guardado: " . basename($logo_output_jpg) . "\n";
    echo "   Tamaño: " . number_format(filesize($logo_output_jpg) / 1024, 2) . " KB\n";
    echo "   Dimensiones: {$new_width} x {$new_height} px\n";
} else {
    echo "❌ Error al guardar JPG\n";
}

// Guardar como PNG también (más compatible)
$gd_info = gd_info();
if ($gd_info['PNG Support']) {
    // Asegurar que es PNG sin transparencia
    $imagen_png = imagecreatetruecolor($new_width, $new_height);
    
    // Rellenar con blanco
    $white_bg = imagecolorallocate($imagen_png, 255, 255, 255);
    imagefill($imagen_png, 0, 0, $white_bg);
    
    // Copiar imagen binaria
    imagecopy($imagen_png, $imagen_bw, 0, 0, 0, 0, $new_width, $new_height);
    
    // Guardar PNG sin transparencia
    imagealphablending($imagen_png, false);
    imagesavealpha($imagen_png, false);
    
    if (imagepng($imagen_png, $logo_output_png, 0)) {
        echo "✅ PNG guardado: " . basename($logo_output_png) . "\n";
        echo "   Tamaño: " . number_format(filesize($logo_output_png) / 1024, 2) . " KB\n";
        echo "   Dimensiones: {$new_width} x {$new_height} px\n";
    } else {
        echo "❌ Error al guardar PNG\n";
    }
    
    imagedestroy($imagen_png);
}

imagedestroy($imagen_bw);

echo "\n5️⃣ VERIFICANDO QUE LOS LOGOS FUNCIONAN\n";
echo "───────────────────────────────────────────────────────────────\n";

require __DIR__ . '/../ImpresionTermica/ticket/autoload.php';
use Mike42\Escpos\EscposImage;

// Probar JPG
if (file_exists($logo_output_jpg)) {
    try {
        $logo = EscposImage::load($logo_output_jpg, false);
        $w = $logo->getWidth();
        $h = $logo->getHeight();
        
        if ($w > 0 && $h > 0) {
            echo "✅ JPG funciona correctamente: {$w} x {$h} px\n";
        } else {
            echo "⚠️ JPG tiene dimensiones inválidas: {$w} x {$h} px\n";
        }
    } catch (Exception $e) {
        echo "❌ Error con JPG: " . $e->getMessage() . "\n";
    }
}

// Probar PNG
if (file_exists($logo_output_png)) {
    try {
        $logo = EscposImage::load($logo_output_png, false);
        $w = $logo->getWidth();
        $h = $logo->getHeight();
        
        if ($w > 0 && $h > 0) {
            echo "✅ PNG funciona correctamente: {$w} x {$h} px\n";
        } else {
            echo "⚠️ PNG tiene dimensiones inválidas: {$w} x {$h} px\n";
        }
    } catch (Exception $e) {
        echo "❌ Error con PNG: " . $e->getMessage() . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ CONVERSIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n💡 PRÓXIMOS PASOS:\n";
echo "\n1. Hacer backup del logo original:\n";
echo "   cp imagenes/image.jpg imagenes/image.jpg.backup\n";
echo "\n2. Usar el logo arreglado:\n";
echo "   cp imagenes/image_fixed.jpg imagenes/image.jpg\n";
echo "   # O para PNG:\n";
echo "   cp ImpresionTermica/geek_fixed.png ImpresionTermica/geek.png\n";
echo "\n3. Probar la impresión:\n";
echo "   php tests/test_imprimir_logo.php\n";
echo "\n";
?>

