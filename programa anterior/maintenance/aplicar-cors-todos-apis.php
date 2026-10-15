<?php
/**
 * 🔧 APLICAR CORS A TODOS LOS ARCHIVOS API
 * Soluciona el problema de CORS en el sistema híbrido
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Aplicar CORS a APIs</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔧 Aplicando CORS a todos los archivos API</h1>";

try {
    // Headers CORS que vamos a agregar
    $cors_headers = '// 🔧 HABILITAR CORS PARA SISTEMA HÍBRIDO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (pre-flight), responder vacía
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

';
    
    // Archivos principales a modificar
    $archivos_principales = [
        'api/tuu-pago.php',
        'api/get-pending-tuu-payments.php',
        'api/get-pending-tuu-payments-fixed.php',
        'api/registrar-ingreso.php',
        'api/registrar-salida.php',
        'api/api_resumen_ejecutivo.php',
        'api/get-tickets.php',
        'api/get-tickets-simple.php',
        'api/calcular-cobro.php',
        'api/cobrar-lavado.php',
        'api/registrar-lavado.php',
        'api/modificar_ticket.php',
        'api/pago-manual.php',
        'api/api_usuarios.php',
        'api/api_clientes.php',
        'api/api_precios.php',
        'api/api_servicios_lavado.php',
        'api/api_todos_servicios.php',
        'api/api_consulta_fechas.php',
        'api/api_reporte.php',
        'api/api_cierre_caja.php',
        'api/api_config_tuu.php',
        'api/ultimos-ingresos.php',
        'api/buscar_ticket.php',
        'api/verificar-patente.php',
        'api/verificar_timezone.php',
        'api/verificar_estructura.php',
        'api/check-printer.php',
        'api/print.php',
        'api/guardar-token-fcm.php',
        'api/sync-tuu-payment.php',
        'api/tuu-confirm-payment.php',
        'api/check-payment-sync.php',
        'api/check-antix-status.php',
        'api/historial-lavados.php',
        'api/get-servicios-lavado.php',
        'api/get-servicios-simple.php',
        'api/get-usuarios.php',
        'api/modificar-lavado.php',
        'api/reactivar_servicios_lavado.php',
        'api/api_clientes_mensuales.php',
        'api/api_reportes_unificados.php',
        'api/debug_servicios.php',
        'api/debug_ingresos_mes.php',
        'api/test_tuu.php',
        'api/test_api_resumen.php',
        'api/test_consulta_fechas.php'
    ];
    
    $archivos_modificados = 0;
    $archivos_ya_tienen_cors = 0;
    $archivos_no_existen = 0;
    
    foreach ($archivos_principales as $archivo) {
        if (!file_exists($archivo)) {
            echo "<div class='info'>⚠️ Archivo no existe: $archivo</div>";
            $archivos_no_existen++;
            continue;
        }
        
        $contenido = file_get_contents($archivo);
        
        // Verificar si ya tiene CORS
        if (strpos($contenido, 'Access-Control-Allow-Origin') !== false) {
            echo "<div class='info'>ℹ️ Ya tiene CORS: $archivo</div>";
            $archivos_ya_tienen_cors++;
            continue;
        }
        
        // Agregar CORS al inicio del archivo
        $nuevo_contenido = $cors_headers . $contenido;
        
        // Crear backup
        $backup_file = $archivo . '.backup';
        file_put_contents($backup_file, $contenido);
        
        // Aplicar cambios
        if (file_put_contents($archivo, $nuevo_contenido)) {
            echo "<div class='success'>✅ CORS agregado: $archivo</div>";
            $archivos_modificados++;
        } else {
            echo "<div class='error'>❌ Error modificando: $archivo</div>";
        }
    }
    
    // También modificar archivos en SISTEMA-HIBRIDO/COMPARTIDOS/api/
    $archivos_hibridos = [
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/tuu-pago.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/registrar-ingreso.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/registrar-salida.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_resumen_ejecutivo.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/get-tickets.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/get-tickets-simple.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/calcular-cobro.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/cobrar-lavado.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/registrar-lavado.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/modificar_ticket.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/pago-manual.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_usuarios.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_clientes.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_precios.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_servicios_lavado.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_todos_servicios.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_consulta_fechas.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_reporte.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_cierre_caja.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_config_tuu.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/ultimos-ingresos.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/buscar_ticket.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/verificar-patente.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/verificar_timezone.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/verificar_estructura.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/check-printer.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/print.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/historial-lavados.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/get-servicios-lavado.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/get-servicios-simple.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/get-usuarios.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/modificar-lavado.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/reactivar_servicios_lavado.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_clientes_mensuales.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/api_reportes_unificados.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/debug_servicios.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/debug_ingresos_mes.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/test_tuu.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/test_api_resumen.php',
        'SISTEMA-HIBRIDO/COMPARTIDOS/api/test_consulta_fechas.php'
    ];
    
    foreach ($archivos_hibridos as $archivo) {
        if (!file_exists($archivo)) {
            continue;
        }
        
        $contenido = file_get_contents($archivo);
        
        // Verificar si ya tiene CORS
        if (strpos($contenido, 'Access-Control-Allow-Origin') !== false) {
            continue;
        }
        
        // Agregar CORS al inicio del archivo
        $nuevo_contenido = $cors_headers . $contenido;
        
        // Crear backup
        $backup_file = $archivo . '.backup';
        file_put_contents($backup_file, $contenido);
        
        // Aplicar cambios
        if (file_put_contents($archivo, $nuevo_contenido)) {
            echo "<div class='success'>✅ CORS agregado (Híbrido): $archivo</div>";
            $archivos_modificados++;
        }
    }
    
    // Resumen final
    echo "<div class='success'>";
    echo "<h2>🎉 ¡CORS APLICADO EXITOSAMENTE!</h2>";
    echo "<p><strong>Resumen:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Archivos modificados: $archivos_modificados</li>";
    echo "<li>ℹ️ Ya tenían CORS: $archivos_ya_tienen_cors</li>";
    echo "<li>⚠️ No existen: $archivos_no_existen</li>";
    echo "</ul>";
    echo "<p><strong>El sistema híbrido ahora puede comunicarse entre máquinas sin errores CORS.</strong></p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Para verificar:</h3>";
    echo "<ol>";
    echo "<li>Recargar la página: <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li>Verificar que no aparezcan errores CORS en la consola</li>";
    echo "<li>Probar sincronización entre máquinas</li>";
    echo "<li>Ingresar un vehículo y verificar que aparezca en otras máquinas</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR APLICANDO CORS</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
