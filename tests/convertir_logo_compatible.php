<?php
/**
 * Script para convertir el logo a un formato compatible con impresoras térmicas
 * Ejecutar: php tests/convertir_logo_compatible.php
 * 
 * Este script analiza el logo actual y crea una versión optimizada
 */

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔧 CONVERSIÓN DE LOGO A FORMATO COMPATIBLE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar GD
if (!extension_loaded('gd')) {
    echo "❌ GD no está instalado\n";
    echo "💡 Ejecuta: sudo apt-get install php-gd\n";
    exit(1);
}

$info = gd_info();
if (!$info['JPEG Support']) {
    echo "❌ GD no soporta JPEG\n";
    exit(1);
}

$logo_original = __DIR__ . "/../imagenes/image.jpg";
$logo_convertido = __DIR__ . "/../imagenes/image_compatible.jpg";
$logo_png_compatible = __DIR__ . "/../ImpresionTermica/geek_compatible.png";

echo "1️⃣ ANALIZANDO LOGO ORIGINAL\n";
echo "───────────────────────────────────────────────────────────────\n";

if (!file_exists($logo_original)) {
    echo "❌ Logo original no encontrado: $logo_original\n";
    exit(1);
}

echo "Archivo: " . basename($logo_original) . "\n";
echo "Tamaño: " . number_format(filesize($logo_original) / 1024, 2) . " KB\n";

// Intentar cargar y analizar
try {
    $imagen = @imagecreatefromjpeg($logo_original);
    
    if (!$imagen) {
        echo "⚠️ No se pudo cargar como JPG, intentando otros métodos...\n";
        
        // Intentar detectar el tipo real
        $image_info = getimagesize($logo_original);
        if ($image_info) {
            echo "Tipo detectado: " . $image_info['mime'] . "\n";
            echo "Dimensiones: {$image_info[0]} x {$image_info[1]} px\n";
            
            // Cargar según tipo
            switch ($image_info[2]) {
                case IMAGETYPE_JPEG:
                    $imagen = imagecreatefromjpeg($logo_original);
                    break;
                case IMAGETYPE_PNG:
                    if ($info['PNG Support']) {
                        $imagen = imagecreatefrompng($logo_original);
                    }
                    break;
                case IMAGETYPE_GIF:
                    if ($info['GIF Support']) {
                        $imagen = imagecreatefromgif($logo_original);
                    }
                    break;
            }
        }
    }
    
    if (!$imagen) {
        throw new Exception("No se pudo cargar la imagen con ningún método");
    }
    
    $width_original = imagesx($imagen);
    $height_original = imagesy($imagen);
    
    echo "✅ Imagen cargada exitosamente\n";
    echo "   Dimensiones originales: {$width_original} x {$height_original} px\n";
    
    // Verificar dimensiones válidas
    if ($width_original == 0 || $height_original == 0) {
        throw new Exception("Dimensiones inválidas: {$width_original}x{$height_original}");
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2️⃣ OPTIMIZANDO LOGO\n";
echo "───────────────────────────────────────────────────────────────\n";

// Dimensiones óptimas para impresoras térmicas (58mm de ancho máximo)
// Aproximadamente 250-300px de ancho es ideal
$max_width = 250;
$max_height = 150;

// Calcular nuevas dimensiones manteniendo proporción
if ($width_original > $max_width || $height_original > $max_height) {
    $ratio = min($max_width / $width_original, $max_height / $height_original);
    $new_width = (int)($width_original * $ratio);
    $new_height = (int)($height_original * $ratio);
    echo "Redimensionando: {$width_original}x{$height_original} → {$new_width}x{$new_height} px\n";
} else {
    $new_width = $width_original;
    $new_height = $height_original;
    echo "Dimensiones OK, no necesita redimensionar\n";
}

// Crear nueva imagen
$imagen_nueva = imagecreatetruecolor($new_width, $new_height);

// Habilitar redimensionamiento con antialiasing
imagealphablending($imagen_nueva, false);
imagesavealpha($imagen_nueva, false);

// Redimensionar
imagecopyresampled(
    $imagen_nueva, $imagen,
    0, 0, 0, 0,
    $new_width, $new_height,
    $width_original, $height_original
);

echo "✅ Logo redimensionado\n";

echo "\n3️⃣ CONVIRTIENDO A ESCALA DE GRISES (Mejor para impresoras térmicas)\n";
echo "───────────────────────────────────────────────────────────────\n";

// Convertir a escala de grises
$imagen_gris = imagecreatetruecolor($new_width, $new_height);
for ($x = 0; $x < $new_width; $x++) {
    for ($y = 0; $y < $new_height; $y++) {
        $rgb = imagecolorat($imagen_nueva, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // Fórmula estándar para escala de grises
        $gris = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
        $color_gris = imagecolorallocate($imagen_gris, $gris, $gris, $gris);
        imagesetpixel($imagen_gris, $x, $y, $color_gris);
    }
}

imagedestroy($imagen_nueva);

echo "✅ Convertido a escala de grises\n";

echo "\n4️⃣ GUARDANDO VERSIONES COMPATIBLES\n";
echo "───────────────────────────────────────────────────────────────\n";

// Guardar como JPG
if (imagejpeg($imagen_gris, $logo_convertido, 85)) {
    echo "✅ JPG compatible guardado: " . basename($logo_convertido) . "\n";
    echo "   Tamaño: " . number_format(filesize($logo_convertido) / 1024, 2) . " KB\n";
} else {
    echo "❌ Error al guardar JPG\n";
}

// Guardar como PNG también (si PNG Support está disponible)
if ($info['PNG Support']) {
    // Crear copia para PNG
    $imagen_png = imagecreatetruecolor($new_width, $new_height);
    imagecopy($imagen_png, $imagen_gris, 0, 0, 0, 0, $new_width, $new_height);
    
    if (imagepng($imagen_png, $logo_png_compatible)) {
        echo "✅ PNG compatible guardado: " . basename($logo_png_compatible) . "\n";
        echo "   Tamaño: " . number_format(filesize($logo_png_compatible) / 1024, 2) . " KB\n";
    }
    imagedestroy($imagen_png);
}

imagedestroy($imagen);
imagedestroy($imagen_gris);

echo "\n5️⃣ PROBANDO LOS LOGOS CONVERTIDOS\n";
echo "───────────────────────────────────────────────────────────────\n";

require __DIR__ . '/../ImpresionTermica/ticket/autoload.php';
use Mike42\Escpos\EscposImage;

// Probar JPG convertido
echo "Probando JPG convertido...\n";
try {
    $logo = EscposImage::load($logo_convertido, false);
    echo "✅ JPG convertido: Cargado correctamente\n";
    echo "   Dimensiones: " . $logo->getWidth() . " x " . $logo->getHeight() . " px\n";
} catch (Exception $e) {
    echo "❌ Error con JPG convertido: " . $e->getMessage() . "\n";
}

// Probar PNG convertido si existe
if (file_exists($logo_png_compatible)) {
    echo "\nProbando PNG convertido...\n";
    try {
        $logo = EscposImage::load($logo_png_compatible, false);
        echo "✅ PNG convertido: Cargado correctamente\n";
        echo "   Dimensiones: " . $logo->getWidth() . " x " . $logo->getHeight() . " px\n";
    } catch (Exception $e) {
        echo "❌ Error con PNG convertido: " . $e->getMessage() . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ CONVERSIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n💡 PRÓXIMOS PASOS:\n";
echo "\n1. Hacer backup del logo original:\n";
echo "   cp imagenes/image.jpg imagenes/image.jpg.backup\n";
echo "\n2. Reemplazar con el logo compatible:\n";
echo "   cp imagenes/image_compatible.jpg imagenes/image.jpg\n";
echo "\n   O para PNG:\n";
echo "   cp ImpresionTermica/geek_compatible.png ImpresionTermica/geek.png\n";
echo "\n3. Probar la impresión:\n";
echo "   php tests/test_imprimir_logo.php\n";
echo "\n";
?>

