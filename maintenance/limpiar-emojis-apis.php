<?php
/**
 * 🧹 LIMPIAR EMOJIS DE ARCHIVOS API
 * Elimina emojis problemáticos que causan errores de JSON
 */

echo "🧹 Limpiando emojis de archivos API...\n";

$archivos_api = glob('api/*.php');

foreach ($archivos_api as $archivo) {
    $contenido = file_get_contents($archivo);
    $contenido_original = $contenido;
    
    // Reemplazar emojis problemáticos
    $contenido = str_replace('🔧', '', $contenido);
    $contenido = str_replace('✅', '', $contenido);
    $contenido = str_replace('❌', '', $contenido);
    $contenido = str_replace('⚠️', '', $contenido);
    $contenido = str_replace('🔄', '', $contenido);
    $contenido = str_replace('📊', '', $contenido);
    $contenido = str_replace('💳', '', $contenido);
    $contenido = str_replace('🎧', '', $contenido);
    $contenido = str_replace('🔔', '', $contenido);
    $contenido = str_replace('🧪', '', $contenido);
    $contenido = str_replace('🛑', '', $contenido);
    $contenido = str_replace('🆕', '', $contenido);
    $contenido = str_replace('🔥', '', $contenido);
    $contenido = str_replace('💾', '', $contenido);
    $contenido = str_replace('🔑', '', $contenido);
    $contenido = str_replace('🌐', '', $contenido);
    $contenido = str_replace('📴', '', $contenido);
    $contenido = str_replace('📤', '', $contenido);
    $contenido = str_replace('📥', '', $contenido);
    $contenido = str_replace('🧹', '', $contenido);
    
    if ($contenido !== $contenido_original) {
        file_put_contents($archivo, $contenido);
        echo "✅ Limpiado: " . basename($archivo) . "\n";
    } else {
        echo "ℹ️ Sin cambios: " . basename($archivo) . "\n";
    }
}

echo "🎉 Limpieza completada!\n";
?>
