<?php
/**
 * Script para actualizar todas las rutas después de reorganizar archivos
 */

echo "🔄 Actualizando rutas después de reorganización...\n\n";

// Archivos que necesitan actualización de rutas
$archivos = [
    // APIs que referencian conexion.php
    'api/api_resumen_ejecutivo.php' => '../config/conexion.php',
    'api/api_respaldo.php' => '../config/conexion.php',
    'api/api_exportar_reporte.php' => '../config/conexion.php',
    'api/historial-lavados.php' => '../config/conexion.php',
    'api/registrar-lavado.php' => '../config/conexion.php',
    'api/registrar-salida.php' => '../config/conexion.php',
    'api/cobrar-lavado.php' => '../config/conexion.php',
    'api/calcular-cobro.php' => '../config/conexion.php',
    'api/api_cierre_caja.php' => '../config/conexion.php',
    'api/tuu-pago.php' => '../config/conexion.php',
    'api/tuu-confirm-payment.php' => '../config/conexion.php',
    'api/check-antix-status.php' => '../config/conexion.php',
    'api/get-pending-tuu-payments-fixed.php' => '../config/conexion.php',
    'api/sync-tuu-payment.php' => '../config/conexion.php',
    'api/get-usuarios.php' => '../config/conexion.php',
    'api/get-tickets.php' => '../config/conexion.php',
    'api/get-servicios-simple.php' => '../config/conexion.php',
    'api/get-tickets-simple.php' => '../config/conexion.php',
    'api/get-servicios-lavado.php' => '../config/conexion.php',
    'api/get-pending-tuu-payments.php' => '../config/conexion.php',
    'api/check-payment-sync.php' => '../config/conexion.php',
    'api/registrar-ingreso.php' => '../config/conexion.php',
    'api/api_usuarios.php' => '../config/conexion.php',
    'api/api_config_tuu.php' => '../config/conexion.php',
    'api/api_clientes_mensuales.php' => '../config/conexion.php',
    'api/api_clientes.php' => '../config/conexion.php',
    'api/api_todos_servicios.php' => '../config/conexion.php',
    'api/api_servicios_lavado.php' => '../config/conexion.php',
    'api/api_reporte.php' => '../config/conexion.php',
    'api/api_precios.php' => '../config/conexion.php',
    'api/api_consulta_fechas.php' => '../config/conexion.php',
    'api/debug_servicios.php' => '../config/conexion.php',
    'api/debug_ingresos_mes.php' => '../config/conexion.php',
    'api/verificar_timezone.php' => '../config/conexion.php',
    'api/verificar_estructura.php' => '../config/conexion.php',
    'api/buscar_ticket.php' => '../config/conexion.php',
    'api/reactivar_servicios_lavado.php' => '../config/conexion.php',
    'api/ultimos-ingresos.php' => '../config/conexion.php',
    'api/pago-manual.php' => '../config/conexion.php',
    'api/modificar_ticket.php' => '../config/conexion.php',
    'api/test_consulta_fechas.php' => '../config/conexion.php',
    'api/test_api_resumen.php' => '../config/conexion.php',
    'api/api_reportes_unificados.php' => '../config/conexion.php',
    'api/verificar-patente.php' => '../config/conexion.php',
    'api/modificar-lavado.php' => '../config/conexion.php',
    
    // Tests que referencian conexion.php
    'tests/debug-api-respaldo.php' => '../config/conexion.php',
    'tests/debug-metodos-pago.php' => '../config/conexion.php',
    'tests/test-antix.php' => '../config/conexion.php',
    'tests/debug-admin-antix.php' => '../config/conexion.php',
    'tests/debug-resumen-ejecutivo.php' => '../config/conexion.php',
    'tests/verificar-tablas-faltantes.php' => '../config/conexion.php',
    'tests/debug-tuu-endpoint.php' => '../config/conexion.php',
    'tests/verificar-migracion-completa.php' => '../config/conexion.php',
    'tests/prueba-simple-tuu.php' => '../config/conexion.php',
    'tests/verificar-estructura-salidas.php' => '../config/conexion.php',
    'tests/test-api-debug.php' => '../config/conexion.php',
    'tests/check-tickets-structure.php' => '../config/conexion.php',
    'tests/check-tickets-status.php' => '../config/conexion.php',
    'tests/check-salidas-3.php' => '../config/conexion.php',
    'tests/check-ingresos-structure.php' => '../config/conexion.php',
    'tests/analyze-existing-tables.php' => '../config/conexion.php',
    'tests/test_conexion.php' => '../config/conexion.php',
    'tests/verificar_unificacion.php' => '../config/conexion.php',
    'tests/diagnostico_conexion.php' => '../config/conexion.php',
    'tests/debug_panel.php' => '../config/conexion.php',
    
    // Maintenance que referencian conexion.php
    'maintenance/respaldo_automatico_semanal.php' => '../config/conexion.php',
    'maintenance/optimizar-mysql-windows7.php' => '../config/conexion.php',
    'maintenance/crear-tablas-faltantes.php' => '../config/conexion.php',
    'maintenance/migrate-data.php' => '../config/conexion.php',
    'maintenance/create-test-ticket.php' => '../config/conexion.php',
    'maintenance/create-tables.php' => '../config/conexion.php',
    'maintenance/add-tuu-columns.php' => '../config/conexion.php',
    'maintenance/monitoreo_bd.php' => '../config/conexion.php',
    'maintenance/actualizar_tabla_dia_pago.php' => '../config/conexion.php',
    'maintenance/crear_usuarios.php' => '../config/conexion.php',
    
    // TUU que referencian conexion.php
    'tuu/webhook-tuu.php' => '../config/conexion.php',
    'tuu/tuu-status-websocket.php' => '../config/conexion.php',
    'tuu/webhook-tuu-firebase.php' => '../config/conexion.php',
    'tuu/fcm-webhook-tuu.php' => '../config/conexion.php',
    
    // Firebase que referencian conexion.php
    'firebase/firebase-webhook-tuu.php' => '../config/conexion.php',
    'firebase/firebase-migration.php' => '../config/conexion.php',
    'firebase/firebase-data-migration.php' => '../config/conexion.php',
    
    // Config que referencian conexion.php
    'config/login-firebase.php' => 'conexion.php',
    'config/auth-hybrid.php' => 'conexion.php',
    'config/login.php' => 'conexion.php',
];

$actualizados = 0;
$errores = 0;

foreach ($archivos as $archivo => $nueva_ruta) {
    if (file_exists($archivo)) {
        $contenido = file_get_contents($archivo);
        
        // Patrones a reemplazar
        $patrones = [
            "require_once 'conexion.php';",
            "require_once \"conexion.php\";",
            "require 'conexion.php';",
            "require \"conexion.php\";",
            "include_once 'conexion.php';",
            "include_once \"conexion.php\";",
            "include 'conexion.php';",
            "include \"conexion.php\";",
            "require_once __DIR__ . '/conexion.php';",
            "require_once __DIR__ . \"/conexion.php\";",
            "require_once __DIR__ . '/../conexion.php';",
            "require_once __DIR__ . \"/../conexion.php\";",
        ];
        
        $nuevo_contenido = $contenido;
        $cambios = 0;
        
        foreach ($patrones as $patron) {
            $nuevo_patron = str_replace('conexion.php', $nueva_ruta, $patron);
            if (strpos($nuevo_contenido, $patron) !== false) {
                $nuevo_contenido = str_replace($patron, $nuevo_patron, $nuevo_contenido);
                $cambios++;
            }
        }
        
        if ($cambios > 0) {
            if (file_put_contents($archivo, $nuevo_contenido)) {
                echo "✅ $archivo - $cambios cambios\n";
                $actualizados++;
            } else {
                echo "❌ Error escribiendo $archivo\n";
                $errores++;
            }
        } else {
            echo "⚪ $archivo - Sin cambios necesarios\n";
        }
    } else {
        echo "⚠️  $archivo - No encontrado\n";
    }
}

echo "\n📊 Resumen:\n";
echo "✅ Archivos actualizados: $actualizados\n";
echo "❌ Errores: $errores\n";
echo "🎉 ¡Actualización de rutas completada!\n";
?>
