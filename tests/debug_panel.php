<?php
/**
 * 🔍 PANEL DE DEPURACIÓN COMPLETO
 * 
 * Este panel te ayuda a verificar que todo el sistema funcione correctamente:
 * - Estado de la base de datos
 * - Configuración del servidor
 * - Permisos de archivos
 * - Conexiones a impresoras
 * - APIs funcionando
 * - Logs de errores
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set("America/Santiago");

// Función para verificar el estado con emojis
function verificarEstado($condicion, $mensaje_exito, $mensaje_error) {
    if ($condicion) {
        return "✅ <span class='text-success'>$mensaje_exito</span>";
    } else {
        return "❌ <span class='text-danger'>$mensaje_error</span>";
    }
}

// Función para medir tiempo de ejecución
function medirTiempo($callback, $nombre) {
    $inicio = microtime(true);
    $resultado = $callback();
    $fin = microtime(true);
    $tiempo = round(($fin - $inicio) * 1000, 2);
    return [
        'resultado' => $resultado,
        'tiempo' => $tiempo,
        'nombre' => $nombre
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Panel de Depuración - Sistema Estacionamiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-section {
            margin-bottom: 2rem;
            border-left: 4px solid #007bff;
            padding-left: 1rem;
        }
        .debug-ok { color: #28a745; }
        .debug-warning { color: #ffc107; }
        .debug-error { color: #dc3545; }
        .code-block {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .test-item {
            padding: 0.5rem;
            margin: 0.5rem 0;
            border-left: 3px solid #dee2e6;
            background: #f8f9fa;
        }
        .badge-time {
            font-size: 0.8rem;
            font-weight: normal;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-bug"></i> Panel de Depuración del Sistema
                        </h2>
                        <small>Generado: <?= date('d/m/Y H:i:s') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- COLUMNA IZQUIERDA -->
            <div class="col-lg-6">
                
                <!-- 1. ESTADO DEL SERVIDOR PHP -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-server"></i> 1. Estado del Servidor PHP</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        echo "<div class='test-item'>";
                        echo verificarEstado(true, "PHP está funcionando correctamente", "PHP no funciona");
                        echo " - Versión: <strong>" . phpversion() . "</strong>";
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(version_compare(phpversion(), '7.0', '>='), 
                            "Versión de PHP es compatible (≥7.0)", 
                            "Versión de PHP muy antigua");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(extension_loaded('mysqli'), 
                            "Extensión MySQLi está cargada", 
                            "⚠️ MySQLi NO está disponible");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(extension_loaded('json'), 
                            "Extensión JSON está cargada", 
                            "⚠️ JSON NO está disponible");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(extension_loaded('gd'), 
                            "Extensión GD (imágenes) está cargada", 
                            "⚠️ GD NO disponible - problemas con logos");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        $max_execution = ini_get('max_execution_time');
                        echo verificarEstado($max_execution >= 30, 
                            "Tiempo máximo de ejecución: {$max_execution}s", 
                            "Tiempo de ejecución muy corto: {$max_execution}s");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        $memory_limit = ini_get('memory_limit');
                        echo verificarEstado(true, "Memoria límite: {$memory_limit}", "");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(ini_get('display_errors') == 1, 
                            "Display errors: ON (modo desarrollo)", 
                            "Display errors: OFF (modo producción)");
                        echo "</div>";
                        ?>
                    </div>
                </div>

                <!-- 2. BASE DE DATOS -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-database"></i> 2. Conexión a Base de Datos</h5>
                    </div>
                    <div class="card-body">
                        <?php
        $db_test = medirTiempo(function() {
            if (file_exists(__DIR__ . '/conexion.php')) {
                try {
                    require_once __DIR__ . '/conexion.php';
                    
                    // Usar $conn en lugar de $conexion (como está en conexion.php)
                    if (isset($conn) && $conn) {
                        // Verificar conexión
                        $result = $conn->query("SELECT 1");
                        if ($result) {
                            return [
                                'status' => true,
                                'mensaje' => 'Conexión exitosa a la base de datos',
                                'servidor' => $conn->host_info ?? 'N/A',
                                'charset' => $conn->character_set_name()
                            ];
                        }
                    }
                    return ['status' => false, 'mensaje' => 'Variable de conexión no disponible'];
                } catch (Exception $e) {
                    return ['status' => false, 'mensaje' => $e->getMessage()];
                }
            }
            return ['status' => false, 'mensaje' => 'Archivo conexion.php no existe'];
        }, 'Conexión DB');

                        echo "<div class='test-item'>";
                        echo verificarEstado(file_exists(__DIR__ . '/conexion.php'), 
                            "Archivo conexion.php existe", 
                            "⚠️ Archivo conexion.php NO encontrado");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado($db_test['resultado']['status'], 
                            $db_test['resultado']['mensaje'], 
                            "Error: " . $db_test['resultado']['mensaje']);
                        echo " <span class='badge bg-secondary badge-time'>{$db_test['tiempo']}ms</span>";
                        echo "</div>";

                        if ($db_test['resultado']['status']) {
                            echo "<div class='test-item'>";
                            echo "📊 Servidor: <strong>{$db_test['resultado']['servidor']}</strong>";
                            echo "</div>";
                            
                            echo "<div class='test-item'>";
                            echo "🔤 Charset: <strong>{$db_test['resultado']['charset']}</strong>";
                            echo "</div>";

                            // Verificar tablas principales
                            if (isset($conn)) {
                                echo "<div class='test-item'>";
                                echo "<strong>Tablas verificadas:</strong><br>";
                                
                                $tablas = ['ingresos', 'servicios', 'precios', 'vehiculos_lavado'];
                                foreach ($tablas as $tabla) {
                                    $check = $conn->query("SHOW TABLES LIKE '$tabla'");
                                    $existe = $check && $check->num_rows > 0;
                                    echo verificarEstado($existe, 
                                        "Tabla '$tabla' existe", 
                                        "⚠️ Tabla '$tabla' NO existe");
                                    echo "<br>";
                                }
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- 3. ARCHIVOS Y PERMISOS -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-folder-open"></i> 3. Archivos y Permisos</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $archivos_criticos = [
                            'conexion.php' => 'Conexión a base de datos',
                            'index.php' => 'Página principal',
                            'ImpresionTermica/ticket.php' => 'Sistema de impresión',
                            'api/registrar-ingreso.php' => 'API de ingresos',
                            'api/calcular-cobro.php' => 'API de cobros',
                            'JS/main.js' => 'JavaScript principal',
                            'JS/ingreso.js' => 'JavaScript ingresos',
                        ];

                        foreach ($archivos_criticos as $archivo => $descripcion) {
                            $ruta_completa = __DIR__ . '/' . $archivo;
                            $existe = file_exists($ruta_completa);
                            $legible = $existe && is_readable($ruta_completa);
                            
                            echo "<div class='test-item'>";
                            echo verificarEstado($existe, 
                                "✓ $descripcion ($archivo)", 
                                "✗ $descripcion NO encontrado");
                            
                            if ($existe) {
                                if (!$legible) {
                                    echo " <span class='badge bg-danger'>Sin permisos de lectura</span>";
                                }
                                $tamaño = filesize($ruta_completa);
                                if ($tamaño > 0) {
                                    echo " <span class='badge bg-info'>" . number_format($tamaño/1024, 2) . " KB</span>";
                                } else {
                                    echo " <span class='badge bg-warning'>Archivo vacío</span>";
                                }
                            }
                            echo "</div>";
                        }

                        // Verificar carpetas de escritura
                        echo "<hr><strong>Permisos de escritura:</strong>";
                        $carpetas_escritura = [
                            'ImpresionTermica' => 'Carpeta de impresión',
                            'api' => 'Carpeta de APIs'
                        ];

                        foreach ($carpetas_escritura as $carpeta => $desc) {
                            $ruta = __DIR__ . '/' . $carpeta;
                            $escribible = is_writable($ruta);
                            echo "<div class='test-item'>";
                            echo verificarEstado($escribible, 
                                "$desc es escribible", 
                                "⚠️ $desc NO es escribible");
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>

            </div>

            <!-- COLUMNA DERECHA -->
            <div class="col-lg-6">

                <!-- 4. APIS DEL SISTEMA -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-plug"></i> 4. APIs del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $apis = [
                            'api/calcular-cobro.php' => 'Calcular cobros',
                            'api/registrar-ingreso.php' => 'Registrar ingresos',
                            'api/registrar-salida.php' => 'Registrar salidas',
                            'api/ultimos-ingresos.php' => 'Últimos ingresos',
                            'api/api_precios.php' => 'Precios del sistema',
                            'api/buscar_ticket.php' => 'Buscar tickets',
                        ];

                        foreach ($apis as $api => $descripcion) {
                            $ruta = __DIR__ . '/' . $api;
                            $existe = file_exists($ruta);
                            
                            echo "<div class='test-item'>";
                            echo verificarEstado($existe, 
                                "$descripcion", 
                                "⚠️ $descripcion NO encontrada");
                            
                            if ($existe) {
                                // Verificar sintaxis PHP
                                $sintaxis = shell_exec("php -l \"$ruta\" 2>&1");
                                $sintaxis_ok = strpos($sintaxis, 'No syntax errors') !== false;
                                
                                if ($sintaxis_ok) {
                                    echo " <span class='badge bg-success'>Sintaxis OK</span>";
                                } else {
                                    echo " <span class='badge bg-danger'>Error de sintaxis</span>";
                                }
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- 5. SISTEMA DE SESIONES -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-user-lock"></i> 5. Sistema de Sesiones</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        echo "<div class='test-item'>";
                        echo verificarEstado(session_status() === PHP_SESSION_ACTIVE, 
                            "Sesiones PHP activas", 
                            "⚠️ Sesiones NO iniciadas");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        $session_path = session_save_path();
                        echo verificarEstado(!empty($session_path), 
                            "Ruta de sesiones: $session_path", 
                            "⚠️ Ruta de sesiones no configurada");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(file_exists('login.php'), 
                            "Sistema de login existe", 
                            "⚠️ login.php NO encontrado");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(file_exists('logout.php'), 
                            "Sistema de logout existe", 
                            "⚠️ logout.php NO encontrado");
                        echo "</div>";
                        ?>
                    </div>
                </div>

                <!-- 6. SISTEMA DE IMPRESIÓN -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fas fa-print"></i> 6. Sistema de Impresión</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        echo "<div class='test-item'>";
                        echo verificarEstado(file_exists('ImpresionTermica/ticket.php'), 
                            "Script de impresión existe", 
                            "⚠️ Script de impresión NO encontrado");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo verificarEstado(file_exists('ImpresionTermica/ticket/autoload.php'), 
                            "Librería ESC/POS instalada", 
                            "⚠️ Librería ESC/POS NO encontrada");
                        echo "</div>";

                        echo "<div class='test-item'>";
                        $logo_existe = file_exists('ImpresionTermica/geek.png');
                        echo verificarEstado($logo_existe, 
                            "Logo para tickets existe", 
                            "⚠️ Logo NO encontrado (continuará sin logo)");
                        echo "</div>";

                        // Verificar impresoras disponibles (solo en Windows)
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            echo "<div class='test-item'>";
                            echo "<strong>🖨️ Impresoras detectadas:</strong><br>";
                            $impresoras = shell_exec('wmic printer get name 2>nul');
                            if ($impresoras) {
                                $lineas = explode("\n", $impresoras);
                                $contador = 0;
                                foreach ($lineas as $linea) {
                                    $linea = trim($linea);
                                    if (!empty($linea) && $linea != 'Name') {
                                        echo "<span class='badge bg-secondary me-1'>$linea</span>";
                                        $contador++;
                                    }
                                }
                                if ($contador == 0) {
                                    echo "<span class='text-warning'>No se detectaron impresoras</span>";
                                }
                            } else {
                                echo "<span class='text-muted'>No se pudo verificar</span>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- 7. RENDIMIENTO -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-tachometer-alt"></i> 7. Rendimiento del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Test de velocidad de base de datos
                        if (isset($conn) && $conn) {
                            $test_select = medirTiempo(function() use ($conn) {
                                return $conn->query("SELECT 1");
                            }, 'SELECT simple');

                            echo "<div class='test-item'>";
                            echo "⚡ SELECT simple: <strong>{$test_select['tiempo']}ms</strong>";
                            $velocidad = $test_select['tiempo'] < 10 ? 'Excelente' : 
                                        ($test_select['tiempo'] < 50 ? 'Bueno' : 'Lento');
                            echo " <span class='badge bg-info'>$velocidad</span>";
                            echo "</div>";

                            // Test de inserción (sin ejecutar, solo preparar)
                            echo "<div class='test-item'>";
                            echo "📊 Base de datos responde correctamente";
                            echo "</div>";
                        }

                        // Uso de memoria
                        $memoria_usada = memory_get_usage(true);
                        $memoria_pico = memory_get_peak_usage(true);
                        
                        echo "<div class='test-item'>";
                        echo "💾 Memoria usada: <strong>" . number_format($memoria_usada/1024/1024, 2) . " MB</strong>";
                        echo "</div>";

                        echo "<div class='test-item'>";
                        echo "📈 Pico de memoria: <strong>" . number_format($memoria_pico/1024/1024, 2) . " MB</strong>";
                        echo "</div>";

                        // Tiempo de carga de página
                        echo "<div class='test-item'>";
                        echo "⏱️ Tiempo de generación: <strong><span id='tiempo-carga'></span>ms</strong>";
                        echo "</div>";
                        ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- LOGS EN VIVO -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between">
                        <h5><i class="fas fa-terminal"></i> 8. Monitor de Logs en Vivo</h5>
                        <button class="btn btn-sm btn-light" onclick="toggleAutoRefresh()">
                            <i class="fas fa-sync-alt"></i> Auto-refresh: <span id="auto-refresh-status">OFF</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="code-block" id="log-container" style="max-height: 400px; overflow-y: auto;">
                            <?php
                            // Buscar logs de errores de PHP
                            $error_log = ini_get('error_log');
                            if (!empty($error_log) && file_exists($error_log)) {
                                echo "<strong>📄 Archivo: $error_log</strong><br><br>";
                                $ultimas_lineas = array_slice(file($error_log), -20);
                                foreach ($ultimas_lineas as $linea) {
                                    if (stripos($linea, 'error') !== false) {
                                        echo "<span class='text-danger'>" . htmlspecialchars($linea) . "</span>";
                                    } elseif (stripos($linea, 'warning') !== false) {
                                        echo "<span class='text-warning'>" . htmlspecialchars($linea) . "</span>";
                                    } else {
                                        echo htmlspecialchars($linea);
                                    }
                                }
                            } else {
                                echo "<span class='text-muted'>No se encontró archivo de log o está vacío</span>";
                            }
                            ?>
                        </div>
                        <small class="text-muted">Mostrando las últimas 20 líneas del log de errores</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-tools"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" onclick="location.reload()">
                                    <i class="fas fa-sync-alt"></i> Refrescar Panel
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-info w-100" onclick="window.open('<?= $_SERVER['PHP_SELF'] ?>?phpinfo=1', '_blank')">
                                    <i class="fas fa-info-circle"></i> Ver PHP Info
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-success w-100" onclick="testImpresion()">
                                    <i class="fas fa-print"></i> Test Impresión
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="index.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-arrow-left"></i> Volver al Sistema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Calcular tiempo de carga
        window.addEventListener('load', function() {
            const tiempo = performance.now();
            document.getElementById('tiempo-carga').textContent = tiempo.toFixed(2);
        });

        // Auto-refresh
        let autoRefreshInterval = null;
        function toggleAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                document.getElementById('auto-refresh-status').textContent = 'OFF';
            } else {
                autoRefreshInterval = setInterval(() => location.reload(), 5000);
                document.getElementById('auto-refresh-status').textContent = 'ON (5s)';
            }
        }

        // Test de impresión
        function testImpresion() {
            if (confirm('¿Deseas hacer una prueba de impresión?')) {
                window.open('ImpresionTermica/test_impresora.php', '_blank');
            }
        }

        // Scroll automático al final de los logs
        const logContainer = document.getElementById('log-container');
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Si se solicita phpinfo
if (isset($_GET['phpinfo']) && $_GET['phpinfo'] == 1) {
    phpinfo();
    exit;
}
?>

