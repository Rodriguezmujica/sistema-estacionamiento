<?php
/**
 * 🔄 INTEGRACIÓN TUU + FIREBASE PARA INDEX.PHP
 * Sistema de Estacionamiento Los Ríos
 * 
 * Incluye todos los scripts necesarios para integrar TUU con Firebase
 * Usar en index.php y otros archivos del sistema
 */

// Verificar que estamos en el contexto correcto
if (!defined('SISTEMA_ESTACIONAMIENTO')) {
    define('SISTEMA_ESTACIONAMIENTO', true);
}

// Configuración de rutas
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$firebasePath = $basePath . '/SISTEMA-HIBRIDO/COMPARTIDOS';
?>

<!-- 🔄 INTEGRACIÓN TUU + FIREBASE -->
<script type="module">
    // Importar integración completa
    import tuuFirebaseIntegrationComplete from '<?php echo $firebasePath; ?>/tuu-firebase-integration-complete.js';
    
    // Esperar a que la integración esté lista
    window.addEventListener('tuuFirebaseIntegrationReady', function(event) {
        console.log('🎉 TUU + Firebase integración completa lista:', event.detail);
        
        // Notificar que el sistema está listo
        if (typeof showNotification === 'function') {
            showNotification('Sistema TUU + Firebase sincronizado', 'success');
        }
    });
    
    // Hacer disponible globalmente
    window.tuuFirebaseIntegration = tuuFirebaseIntegrationComplete;
</script>

<!-- 🔄 SCRIPTS DE FIREBASE -->
<script type="module" src="<?php echo $firebasePath; ?>/firebase-config.js"></script>
<script type="module" src="<?php echo $firebasePath; ?>/config-sistema-hibrido.js"></script>
<script type="module" src="<?php echo $firebasePath; ?>/tuu-firebase-sync.js"></script>
<script type="module" src="<?php echo $firebasePath; ?>/tuu-firebase-integration.js"></script>
<script type="module" src="<?php echo $firebasePath; ?>/tuu-payment-interceptor.js"></script>

<!-- 🔄 ESTILOS CSS -->
<style>
    .payment-pending {
        border-left: 4px solid #ffc107;
        background-color: #fff3cd;
    }
    
    .payment-verifying {
        border-left: 4px solid #17a2b8;
        background-color: #d1ecf1;
    }
    
    .payment-completed {
        border-left: 4px solid #28a745;
        background-color: #d4edda;
    }
    
    .payment-failed {
        border-left: 4px solid #dc3545;
        background-color: #f8d7da;
    }
    
    /* CSS del toast eliminado - ahora solo se usa el cuadro de estadísticas */
</style>

<!-- 🔄 INDICADOR DE ESTADO ELIMINADO - Ahora está en el cuadro de estadísticas -->

<script>
    // Función para actualizar el estado en el cuadro de estadísticas
    function updateTUUStatus() {
        const statusElement = document.getElementById('tuu-firebase-status');
        
        if (window.tuuFirebaseIntegration && statusElement) {
            const status = window.tuuFirebaseIntegration.getIntegrationStatus();
            
            if (status.initialized) {
                statusElement.textContent = 'Conectado';
                statusElement.className = 'mb-1 text-success';
            } else {
                statusElement.textContent = 'Desconectado';
                statusElement.className = 'mb-1 text-danger';
            }
        }
    }
    
    // Actualizar estado cada 5 segundos
    setInterval(updateTUUStatus, 5000);
    
    // Actualizar estado inicial
    setTimeout(updateTUUStatus, 2000);
</script>

<?php
// Función para incluir la integración en cualquier archivo
function includeTUUFirebaseIntegration($basePath = null) {
    if (!$basePath) {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }
    
    $firebasePath = $basePath . '/SISTEMA-HIBRIDO/COMPARTIDOS';
    
    echo "<!-- 🔄 INTEGRACIÓN TUU + FIREBASE -->\n";
    echo "<script type=\"module\">\n";
    echo "    import tuuFirebaseIntegrationComplete from '{$firebasePath}/tuu-firebase-integration-complete.js';\n";
    echo "    window.tuuFirebaseIntegration = tuuFirebaseIntegrationComplete;\n";
    echo "</script>\n";
    echo "<script type=\"module\" src=\"{$firebasePath}/firebase-config.js\"></script>\n";
    echo "<script type=\"module\" src=\"{$firebasePath}/config-sistema-hibrido.js\"></script>\n";
    echo "<script type=\"module\" src=\"{$firebasePath}/tuu-firebase-sync.js\"></script>\n";
    echo "<script type=\"module\" src=\"{$firebasePath}/tuu-firebase-integration.js\"></script>\n";
    echo "<script type=\"module\" src=\"{$firebasePath}/tuu-payment-interceptor.js\"></script>\n";
}
?>
