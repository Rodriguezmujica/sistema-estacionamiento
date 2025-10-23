<?php
/**
 * 🔧 SCRIPT DE CORRECCIÓN DE API KEYS
 * Reemplaza todas las API Keys expuestas por variables de entorno
 */

echo "🔧 CORRIGIENDO API KEYS EXPUESTAS\n";
echo "================================\n\n";

// Lista de archivos que contienen la API Key expuesta
$archivos = [
    'webhook-tuu-firebase.php',
    'verificar-transaction-reciente.php', 
    'prueba-simple-tuu.php',
    'api/tuu-pago.php',
    'api/tuu-confirm-payment.php',
    'SISTEMA-HIBRIDO/COMPARTIDOS/api/tuu-pago.php',
    'tuu-status-websocket.php'
];

$apiKeyExposada = 'uIAwXISF5Amug0O7QA16r72a07x10n6jdu4LNzjos3cdz736bGkHf7gM84bQ5CMsaeav0YSy8Y0qOlTdQy5pORoDE82m55HVDLybJFIuCKEwFeogRIBidkUU6nl6ux';

$reemplazo = "getenv('TUU_API_KEY') ?: 'TU_API_KEY_AQUI'";

$archivosCorregidos = 0;
$archivosNoEncontrados = 0;

foreach ($archivos as $archivo) {
    echo "Procesando: $archivo\n";
    
    if (!file_exists($archivo)) {
        echo "  ❌ Archivo no encontrado\n\n";
        $archivosNoEncontrados++;
        continue;
    }
    
    $contenido = file_get_contents($archivo);
    
    if (strpos($contenido, $apiKeyExposada) !== false) {
        // Reemplazar la API Key expuesta
        $contenidoNuevo = str_replace($apiKeyExposada, $reemplazo, $contenido);
        
        // Agregar comentario de seguridad si no existe
        if (strpos($contenidoNuevo, 'API KEY REMOVIDA POR SEGURIDAD') === false) {
            $contenidoNuevo = str_replace(
                "'X-API-Key: ' . " . $reemplazo,
                "// ⚠️ API KEY REMOVIDA POR SEGURIDAD\n        'X-API-Key: ' . " . $reemplazo,
                $contenidoNuevo
            );
        }
        
        // Guardar archivo corregido
        if (file_put_contents($archivo, $contenidoNuevo)) {
            echo "  ✅ API Key reemplazada correctamente\n";
            $archivosCorregidos++;
        } else {
            echo "  ❌ Error al guardar archivo\n";
        }
    } else {
        echo "  ℹ️ No contiene API Key expuesta\n";
    }
    
    echo "\n";
}

echo "RESUMEN DE CORRECCIÓN:\n";
echo "======================\n";
echo "Archivos corregidos: $archivosCorregidos\n";
echo "Archivos no encontrados: $archivosNoEncontrados\n";
echo "Total procesados: " . count($archivos) . "\n\n";

if ($archivosCorregidos > 0) {
    echo "✅ CORRECCIÓN COMPLETADA\n";
    echo "Recuerda:\n";
    echo "1. Crear archivo config-sensible.php con tu API Key real\n";
    echo "2. Hacer commit de los cambios\n";
    echo "3. Considerar regenerar la API Key en TUU\n";
} else {
    echo "ℹ️ No se encontraron API Keys expuestas para corregir\n";
}
?>
