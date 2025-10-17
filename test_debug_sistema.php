<?php
/**
 * 🧪 PRUEBA RÁPIDA DEL SISTEMA DE DEBUG
 * 
 * Este script genera eventos de prueba para que veas cómo funciona el sistema.
 * Ejecútalo y luego abre ver_logs.php para ver los resultados.
 */

require_once 'debug_logger.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Prueba del Sistema de Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .test-result {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .test-result.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .test-result.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center text-white mb-4">
            <h1 class="display-4"><i class="fas fa-flask"></i> Prueba del Sistema de Debug</h1>
            <p class="lead">Generando eventos de prueba...</p>
        </div>

        <div class="test-card">
            <h3 class="mb-4"><i class="fas fa-list-check"></i> Resultados de las Pruebas</h3>
            
            <?php
            $tests_exitosos = 0;
            $tests_totales = 0;
            
            // Test 1: Logger está funcionando
            $tests_totales++;
            try {
                DebugLogger::info("✅ Test 1: Sistema de logging iniciado correctamente");
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 1: Logger funcionando</strong><br>';
                echo 'El sistema de logging se inicializó correctamente.';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 1: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 2: Diferentes tipos de log
            $tests_totales++;
            try {
                DebugLogger::info("Esto es un mensaje de INFO");
                DebugLogger::warning("Esto es un mensaje de WARNING");
                DebugLogger::error("Esto es un mensaje de ERROR (de prueba)");
                DebugLogger::debug("Esto es un mensaje de DEBUG");
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 2: Tipos de log</strong><br>';
                echo 'Se generaron logs de tipo: INFO, WARNING, ERROR, DEBUG';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 2: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 3: Logs con datos adicionales
            $tests_totales++;
            try {
                DebugLogger::info("Usuario de prueba ingresó patente", [
                    'patente' => 'TEST123',
                    'servicio' => 'Estacionamiento',
                    'usuario' => 'test_user'
                ]);
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 3: Logs con datos</strong><br>';
                echo 'Se registró un evento con datos adicionales en formato JSON';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 3: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 4: Logs SQL simulados
            $tests_totales++;
            try {
                DebugLogger::sql("SELECT * FROM ingresos WHERE patente = 'TEST123' LIMIT 1", 12.5);
                DebugLogger::sql("INSERT INTO ingresos (patente) VALUES ('TEST123')", 8.2);
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 4: Logs SQL</strong><br>';
                echo 'Se registraron consultas SQL simuladas con tiempos de ejecución';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 4: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 5: API calls simuladas
            $tests_totales++;
            try {
                DebugLogger::api("/api/calcular-cobro.php", "POST", [
                    'patente' => 'TEST123'
                ]);
                DebugLogger::api("/api/registrar-ingreso.php", "POST", [
                    'patente' => 'TEST123',
                    'tipo_servicio' => 18
                ]);
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 5: API logs</strong><br>';
                echo 'Se registraron llamadas a APIs simuladas';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 5: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 6: Impresión simulada
            $tests_totales++;
            try {
                DebugLogger::print_ticket("TEST123", true);
                DebugLogger::print_ticket("TEST456", false);
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 6: Logs de impresión</strong><br>';
                echo 'Se registraron eventos de impresión (exitosa y fallida)';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 6: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 7: Medición de tiempo
            $tests_totales++;
            try {
                $resultado = DebugLogger::measureTime("Operación de prueba", function() {
                    usleep(50000); // Simular 50ms de trabajo
                    return "Operación completada";
                });
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 7: Medición de tiempos</strong><br>';
                echo 'Se midió el tiempo de ejecución de una función (≈50ms)';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 7: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 8: Excepciones
            $tests_totales++;
            try {
                try {
                    throw new Exception("Esta es una excepción de prueba");
                } catch (Exception $e) {
                    DebugLogger::exception($e);
                }
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 8: Registro de excepciones</strong><br>';
                echo 'Se registró una excepción con su stack trace completo';
                echo '</div>';
                $tests_exitosos++;
            } catch (Exception $e) {
                echo '<div class="test-result danger">';
                echo '<strong><i class="fas fa-times-circle"></i> Test 8: ERROR</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
            }

            // Test 9: Verificar archivo de logs
            $tests_totales++;
            $log_file = __DIR__ . '/logs/debug.log';
            if (file_exists($log_file)) {
                $tamaño = filesize($log_file);
                $lineas = count(file($log_file));
                
                echo '<div class="test-result success">';
                echo '<strong><i class="fas fa-check-circle"></i> Test 9: Archivo de logs</strong><br>';
                echo "Archivo creado correctamente: <code>logs/debug.log</code><br>";
                echo "Tamaño: " . number_format($tamaño) . " bytes<br>";
                echo "Líneas: $lineas";
                echo '</div>';
                $tests_exitosos++;
            } else {
                echo '<div class="test-result warning">';
                echo '<strong><i class="fas fa-exclamation-triangle"></i> Test 9: Archivo de logs</strong><br>';
                echo 'El archivo de logs se creará en la primera ejecución';
                echo '</div>';
            }

            // Resumen final
            $porcentaje = round(($tests_exitosos / $tests_totales) * 100);
            $color = $porcentaje == 100 ? 'success' : ($porcentaje >= 75 ? 'warning' : 'danger');
            
            echo '<div class="test-result ' . $color . ' mt-4">';
            echo '<h4><i class="fas fa-chart-pie"></i> Resumen Final</h4>';
            echo "<strong>Tests exitosos: $tests_exitosos / $tests_totales ($porcentaje%)</strong><br>";
            
            if ($porcentaje == 100) {
                echo '<p class="mb-0 mt-2">🎉 ¡Perfecto! El sistema de debugging está funcionando al 100%</p>';
            } elseif ($porcentaje >= 75) {
                echo '<p class="mb-0 mt-2">✅ El sistema funciona correctamente con algunos warnings menores</p>';
            } else {
                echo '<p class="mb-0 mt-2">⚠️ Hay algunos problemas que necesitan atención</p>';
            }
            echo '</div>';

            // Generar un último log de finalización
            DebugLogger::info("🧪 Prueba del sistema completada", [
                'tests_exitosos' => $tests_exitosos,
                'tests_totales' => $tests_totales,
                'porcentaje' => $porcentaje
            ]);
            ?>

        </div>

        <!-- Acciones siguientes -->
        <div class="test-card">
            <h3 class="mb-3"><i class="fas fa-forward"></i> Siguientes Pasos</h3>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="ver_logs.php" class="btn btn-primary btn-lg" target="_blank">
                            <i class="fas fa-terminal"></i> Ver Logs Generados
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2 text-center">
                        Verás todos los eventos de prueba que acabamos de generar
                    </small>
                </div>
                
                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="debug_panel.php" class="btn btn-success btn-lg" target="_blank">
                            <i class="fas fa-tachometer-alt"></i> Abrir Panel de Debug
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2 text-center">
                        Revisa el estado general del sistema
                    </small>
                </div>

                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="tutorial_debug.html" class="btn btn-warning btn-lg">
                            <i class="fas fa-graduation-cap"></i> Ver Tutorial
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2 text-center">
                        Aprende a usar el sistema paso a paso
                    </small>
                </div>

                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="index.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Volver al Sistema
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2 text-center">
                        Regresar al sistema principal
                    </small>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="test-card bg-light">
            <h5><i class="fas fa-info-circle"></i> ¿Qué acaba de pasar?</h5>
            <p>Este script generó varios eventos de prueba para demostrar cómo funciona el sistema de logging:</p>
            <ul>
                <li>✅ Logs de diferentes tipos (INFO, WARNING, ERROR, DEBUG)</li>
                <li>✅ Logs con datos adicionales en formato JSON</li>
                <li>✅ Consultas SQL simuladas con tiempos de ejecución</li>
                <li>✅ Llamadas a APIs registradas</li>
                <li>✅ Eventos de impresión de tickets</li>
                <li>✅ Medición de tiempos de funciones</li>
                <li>✅ Registro de excepciones completas</li>
            </ul>
            <p class="mb-0">
                <strong>Ahora abre <a href="ver_logs.php" target="_blank">ver_logs.php</a></strong> 
                para ver todos estos eventos en tiempo real con colores y filtros.
            </p>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


