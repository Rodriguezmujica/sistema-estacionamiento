<?php
/**
 * 🔧 SOLUCIÓN AUTOMÁTICA: Error de conexión MariaDB
 * 
 * Error: Host 'localhost' is not allowed to connect to this MariaDB server
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Solucionar Conexión MariaDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
        }
        .solution-box {
            background: #f8f9fa;
            border-left: 5px solid;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 5px;
        }
        .solution-box.success { border-color: #28a745; background: #d4edda; }
        .solution-box.error { border-color: #dc3545; background: #f8d7da; }
        .solution-box.warning { border-color: #ffc107; background: #fff3cd; }
        .solution-box.info { border-color: #17a2b8; background: #d1ecf1; }
        code {
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 4px;
            color: #d63384;
            font-size: 0.9rem;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
        .step-number {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            background: #f093fb;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center text-white mb-4">
            <i class="fas fa-wrench fa-4x mb-3"></i>
            <h1 class="display-4 fw-bold">Solución: Error de Conexión MariaDB</h1>
            <p class="lead">Host 'localhost' is not allowed to connect</p>
        </div>

        <!-- El Problema -->
        <div class="card-custom">
            <div class="card-header bg-danger text-white">
                <h3 class="mb-0"><i class="fas fa-times-circle"></i> El Problema</h3>
            </div>
            <div class="card-body p-4">
                <div class="solution-box error">
                    <h5>Error Actual:</h5>
                    <code>Host 'localhost' is not allowed to connect to this MariaDB server</code>
                </div>
                <p class="lead">
                    Esto significa que <strong>MariaDB no acepta conexiones desde 'localhost'</strong>. 
                    Puede ser por permisos o configuración.
                </p>
            </div>
        </div>

        <!-- Diagnóstico Automático -->
        <div class="card-custom">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="fas fa-search"></i> Diagnóstico Automático</h3>
            </div>
            <div class="card-body p-4">
                <?php
                $diagnostico = [];
                $solucion_encontrada = false;
                
                echo "<h5>🔍 Probando diferentes formas de conectar...</h5>";
                
                // Test 1: Con 'localhost'
                echo "<div class='solution-box'>";
                echo "<strong>Test 1:</strong> Conexión con <code>localhost</code><br>";
                try {
                    $test1 = @new mysqli('localhost', 'root', '', 'estacionamiento');
                    if (!$test1->connect_error) {
                        echo "✅ <span class='text-success'>¡Funciona con localhost!</span>";
                        $diagnostico['localhost'] = true;
                        $solucion_encontrada = true;
                    } else {
                        echo "❌ <span class='text-danger'>Error: {$test1->connect_error}</span>";
                        $diagnostico['localhost'] = false;
                    }
                } catch (Exception $e) {
                    echo "❌ <span class='text-danger'>Error: {$e->getMessage()}</span>";
                    $diagnostico['localhost'] = false;
                }
                echo "</div>";

                // Test 2: Con '127.0.0.1'
                echo "<div class='solution-box'>";
                echo "<strong>Test 2:</strong> Conexión con <code>127.0.0.1</code><br>";
                try {
                    $test2 = @new mysqli('127.0.0.1', 'root', '', 'estacionamiento');
                    if (!$test2->connect_error) {
                        echo "✅ <span class='text-success'>¡Funciona con 127.0.0.1!</span>";
                        $diagnostico['127.0.0.1'] = true;
                        if (!$solucion_encontrada) {
                            $solucion_encontrada = '127.0.0.1';
                        }
                    } else {
                        echo "❌ <span class='text-danger'>Error: {$test2->connect_error}</span>";
                        $diagnostico['127.0.0.1'] = false;
                    }
                } catch (Exception $e) {
                    echo "❌ <span class='text-danger'>Error: {$e->getMessage()}</span>";
                    $diagnostico['127.0.0.1'] = false;
                }
                echo "</div>";

                // Test 3: Puerto alternativo
                echo "<div class='solution-box'>";
                echo "<strong>Test 3:</strong> Conexión con puerto alternativo (3307)<br>";
                try {
                    $test3 = @new mysqli('127.0.0.1', 'root', '', 'estacionamiento', 3307);
                    if (!$test3->connect_error) {
                        echo "✅ <span class='text-success'>¡Funciona con 127.0.0.1:3307!</span>";
                        $diagnostico['127.0.0.1:3307'] = true;
                        if (!$solucion_encontrada) {
                            $solucion_encontrada = '127.0.0.1:3307';
                        }
                    } else {
                        echo "❌ <span class='text-danger'>Error: {$test3->connect_error}</span>";
                        $diagnostico['127.0.0.1:3307'] = false;
                    }
                } catch (Exception $e) {
                    echo "❌ <span class='text-danger'>Error: {$e->getMessage()}</span>";
                    $diagnostico['127.0.0.1:3307'] = false;
                }
                echo "</div>";
                ?>
            </div>
        </div>

        <!-- La Solución -->
        <?php if ($solucion_encontrada): ?>
        <div class="card-custom">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0"><i class="fas fa-check-circle"></i> ¡Solución Encontrada!</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($solucion_encontrada === true): ?>
                    <div class="solution-box success">
                        <h4>✅ Tu conexión funciona con <code>localhost</code></h4>
                        <p>El problema puede estar en otro archivo. Verifica que todos tus archivos PHP usen:</p>
                        <pre><code>$conn = new mysqli('localhost', 'root', '', 'estacionamiento');</code></pre>
                    </div>
                <?php elseif ($solucion_encontrada === '127.0.0.1'): ?>
                    <div class="solution-box warning">
                        <h4>⚠️ Usa <code>127.0.0.1</code> en lugar de <code>localhost</code></h4>
                        <p>MariaDB acepta conexiones desde 127.0.0.1 pero no desde 'localhost'.</p>
                        <p><strong>Sigue estos pasos:</strong></p>
                    </div>
                    
                    <h5 class="mt-4">📝 Modificar archivo conexion.php:</h5>
                    <div class="solution-box info">
                        <p><span class="step-number">1</span> Abre el archivo: <code>conexion.php</code></p>
                        <p><span class="step-number">2</span> Busca la línea 11:</p>
                        <pre><code>$host = 'localhost';</code></pre>
                        <p><span class="step-number">3</span> Cámbiala por:</p>
                        <pre><code>$host = '127.0.0.1';</code></pre>
                        <p><span class="step-number">4</span> Guarda el archivo</p>
                        <p><span class="step-number">5</span> Recarga tu sistema</p>
                    </div>

                    <div class="text-center mt-3">
                        <button class="btn btn-success btn-lg" onclick="aplicarSolucion('127.0.0.1')">
                            <i class="fas fa-magic"></i> Aplicar Solución Automáticamente
                        </button>
                    </div>

                <?php elseif ($solucion_encontrada === '127.0.0.1:3307'): ?>
                    <div class="solution-box warning">
                        <h4>⚠️ Tu MariaDB está en puerto 3307</h4>
                        <p>Necesitas usar <code>127.0.0.1</code> y puerto <code>3307</code>.</p>
                    </div>
                    
                    <h5 class="mt-4">📝 Modificar archivo conexion.php:</h5>
                    <div class="solution-box info">
                        <p><span class="step-number">1</span> Abre el archivo: <code>conexion.php</code></p>
                        <p><span class="step-number">2</span> Busca la línea 11:</p>
                        <pre><code>$host = 'localhost';</code></pre>
                        <p><span class="step-number">3</span> Cámbiala por:</p>
                        <pre><code>$host = '127.0.0.1';</code></pre>
                        <p><span class="step-number">4</span> Busca la línea 16:</p>
                        <pre><code>$conn = new mysqli($host, $user, $pass, $dbname);</code></pre>
                        <p><span class="step-number">5</span> Cámbiala por:</p>
                        <pre><code>$conn = new mysqli($host, $user, $pass, $dbname, 3307);</code></pre>
                        <p><span class="step-number">6</span> Guarda el archivo</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- Si no funciona ninguna -->
        <div class="card-custom">
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Soluciones Manuales</h3>
            </div>
            <div class="card-body p-4">
                <h5>Ninguna conexión automática funcionó. Prueba estas soluciones:</h5>

                <div class="solution-box warning">
                    <h5><span class="step-number">1</span> Verifica que MariaDB esté corriendo</h5>
                    <ul>
                        <li>Abre <strong>XAMPP Control Panel</strong></li>
                        <li>Verifica que MySQL/MariaDB tenga estado <span class="badge bg-success">Running</span></li>
                        <li>Si no está corriendo, haz clic en <strong>Start</strong></li>
                    </ul>
                </div>

                <div class="solution-box info">
                    <h5><span class="step-number">2</span> Crear usuario en MariaDB</h5>
                    <p>Abre phpMyAdmin: <a href="http://localhost:8080/phpmyadmin" target="_blank">http://localhost:8080/phpmyadmin</a></p>
                    <p>Ve a la pestaña <strong>SQL</strong> y ejecuta:</p>
                    <pre><code>CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;</code></pre>
                </div>

                <div class="solution-box info">
                    <h5><span class="step-number">3</span> Configurar bind-address</h5>
                    <p>Abre el archivo de configuración de MariaDB:</p>
                    <code>C:\xampp\mysql\bin\my.ini</code>
                    <p>Busca la línea:</p>
                    <pre><code>bind-address = 127.0.0.1</code></pre>
                    <p>Cámbiala por:</p>
                    <pre><code>bind-address = 0.0.0.0</code></pre>
                    <p>Guarda y reinicia MySQL desde XAMPP Control Panel</p>
                </div>

                <div class="solution-box error">
                    <h5><span class="step-number">4</span> Reinstalar MariaDB en XAMPP</h5>
                    <p>Si nada funciona, puede que necesites:</p>
                    <ul>
                        <li>Hacer backup de tu base de datos</li>
                        <li>Reinstalar XAMPP</li>
                        <li>Restaurar tu base de datos</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acciones Rápidas -->
        <div class="text-center">
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sync-alt"></i> Probar Conexión Ahora
            </a>
            <a href="debug_panel.php" class="btn btn-info btn-lg">
                <i class="fas fa-tachometer-alt"></i> Ver Panel Debug
            </a>
            <a href="http://localhost:8080/phpmyadmin" class="btn btn-secondary btn-lg" target="_blank">
                <i class="fas fa-database"></i> Abrir phpMyAdmin
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function aplicarSolucion(host) {
            if (confirm('¿Deseas que modifique automáticamente el archivo conexion.php?\n\nCambiará localhost por ' + host)) {
                fetch('aplicar_fix.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({host: host})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ¡Archivo modificado exitosamente!\n\nRecarga la página para probar.');
                        window.location.reload();
                    } else {
                        alert('❌ Error: ' + data.error + '\n\nModifica el archivo manualmente.');
                    }
                })
                .catch(error => {
                    alert('❌ Error: ' + error + '\n\nModifica el archivo manualmente.');
                });
            }
        }
    </script>
</body>
</html>


