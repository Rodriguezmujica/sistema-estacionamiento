<?php
/**
 * 🔍 DIAGNÓSTICO DE CONEXIÓN A BASE DE DATOS
 * 
 * Este script te ayuda a identificar el problema de conexión
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnóstico de Conexión BD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 2rem;
        }
        .test-box {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 5px solid #dee2e6;
        }
        .test-box.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .test-box.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .test-box.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔍 Diagnóstico de Conexión a Base de Datos</h1>

        <?php
        echo "<h3>📋 Configuración Actual</h3>";

        // Leer configuración de conexion.php
        if (file_exists(__DIR__ . '/conexion.php')) {
            $contenido = file_get_contents(__DIR__ . '/conexion.php');
            
            // Extraer variables
            preg_match('/\$host\s*=\s*[\'"](.+?)[\'"]/i', $contenido, $host_match);
            preg_match('/\$user\s*=\s*[\'"](.+?)[\'"]/i', $contenido, $user_match);
            preg_match('/\$pass\s*=\s*[\'"](.*)[\'"]/i', $contenido, $pass_match);
            preg_match('/\$dbname\s*=\s*[\'"](.+?)[\'"]/i', $contenido, $db_match);
            
            $host = $host_match[1] ?? 'No detectado';
            $user = $user_match[1] ?? 'No detectado';
            $pass = isset($pass_match[1]) ? ($pass_match[1] === '' ? '(vacío)' : '***') : 'No detectado';
            $dbname = $db_match[1] ?? 'No detectado';
            
            echo "<div class='test-box'>";
            echo "<strong>De conexion.php:</strong><br>";
            echo "Host: <code>$host</code><br>";
            echo "Usuario: <code>$user</code><br>";
            echo "Password: <code>$pass</code><br>";
            echo "Base de datos: <code>$dbname</code><br>";
            echo "</div>";
        } else {
            echo "<div class='test-box error'>";
            echo "❌ Archivo <code>conexion.php</code> no encontrado";
            echo "</div>";
        }

        echo "<h3 class='mt-4'>🧪 Pruebas de Conexión</h3>";

        // TEST 1: Conexión básica a MySQL/MariaDB
        echo "<div class='test-box'>";
        echo "<h5>Test 1: Conexión sin base de datos</h5>";
        
        $puertos = [3306, 3307, 8080, 3308]; // Probar diferentes puertos
        $conexion_exitosa = false;
        $puerto_correcto = null;
        
        foreach ($puertos as $puerto) {
            try {
                $test_conn = @new mysqli('localhost', 'root', '', '', $puerto);
                
                if (!$test_conn->connect_error) {
                    echo "✅ <strong>¡Conexión exitosa!</strong><br>";
                    echo "Puerto correcto: <code>$puerto</code><br>";
                    echo "Versión del servidor: <code>{$test_conn->server_info}</code><br>";
                    echo "Tipo: <code>" . (strpos($test_conn->server_info, 'MariaDB') !== false ? 'MariaDB' : 'MySQL') . "</code>";
                    $conexion_exitosa = true;
                    $puerto_correcto = $puerto;
                    $test_conn->close();
                    break;
                } else {
                    echo "Puerto $puerto: {$test_conn->connect_error}<br>";
                }
            } catch (Exception $e) {
                echo "Puerto $puerto: Error - {$e->getMessage()}<br>";
            }
        }
        
        if (!$conexion_exitosa) {
            echo "<br>❌ <strong>No se pudo conectar en ningún puerto común</strong>";
        }
        echo "</div>";

        // TEST 2: Verificar si la base de datos existe
        if ($conexion_exitosa && $puerto_correcto) {
            echo "<div class='test-box'>";
            echo "<h5>Test 2: Verificar base de datos 'estacionamiento'</h5>";
            
            try {
                $test_conn = new mysqli('localhost', 'root', '', '', $puerto_correcto);
                $result = $test_conn->query("SHOW DATABASES LIKE 'estacionamiento'");
                
                if ($result && $result->num_rows > 0) {
                    echo "✅ Base de datos <code>estacionamiento</code> existe<br>";
                    
                    // Intentar conectarse a la BD
                    $test_conn->select_db('estacionamiento');
                    if ($test_conn->errno) {
                        echo "❌ Error al seleccionar la BD: {$test_conn->error}";
                    } else {
                        echo "✅ Conexión exitosa a la base de datos";
                    }
                } else {
                    echo "❌ Base de datos <code>estacionamiento</code> NO existe<br>";
                    echo "💡 Necesitas crear la base de datos primero";
                }
                $test_conn->close();
            } catch (Exception $e) {
                echo "❌ Error: {$e->getMessage()}";
            }
            echo "</div>";
        }

        // TEST 3: Probar con la configuración actual
        echo "<div class='test-box'>";
        echo "<h5>Test 3: Probar conexión con conexion.php</h5>";
        
        try {
            require_once __DIR__ . '/../config/conexion.php';
            
            if (isset($conn) && !$conn->connect_error) {
                echo "✅ <strong>¡Conexión exitosa usando conexion.php!</strong><br>";
                echo "Info del servidor: <code>{$conn->host_info}</code><br>";
                echo "Charset: <code>{$conn->character_set_name()}</code>";
            } else {
                $error = isset($conn) ? $conn->connect_error : 'Variable $conn no definida';
                echo "❌ Error: $error";
            }
        } catch (Exception $e) {
            echo "❌ Error: {$e->getMessage()}";
        }
        echo "</div>";

        // SOLUCIONES
        echo "<h3 class='mt-4'>💡 Soluciones Posibles</h3>";

        if ($conexion_exitosa && $puerto_correcto && $puerto_correcto != 3306) {
            echo "<div class='test-box warning'>";
            echo "<h5>⚠️ Puerto no estándar detectado</h5>";
            echo "<p>Tu MySQL/MariaDB está en el puerto <strong>$puerto_correcto</strong> (no el estándar 3306).</p>";
            echo "<p><strong>Solución:</strong> Actualiza tu archivo <code>conexion.php</code>:</p>";
            echo "<pre><code>";
            echo "\$host = 'localhost';\n";
            echo "\$user = 'root';\n";
            echo "\$pass = '';\n";
            echo "\$dbname = 'estacionamiento';\n\n";
            echo "// Especificar el puerto\n";
            echo "\$conn = new mysqli(\$host, \$user, \$pass, \$dbname, $puerto_correcto);";
            echo "</code></pre>";
            echo "</div>";
        }

        if (!$conexion_exitosa) {
            echo "<div class='test-box error'>";
            echo "<h5>❌ No se pudo conectar al servidor</h5>";
            echo "<p><strong>Posibles causas:</strong></p>";
            echo "<ul>";
            echo "<li>✓ <strong>Verifica que XAMPP esté corriendo</strong> (abre XAMPP Control Panel)</li>";
            echo "<li>✓ <strong>Inicia MySQL/MariaDB</strong> en XAMPP Control Panel</li>";
            echo "<li>✓ Verifica que no hay otro MySQL corriendo (cierra otros servidores)</li>";
            echo "<li>✓ Revisa el puerto en la configuración de XAMPP (my.ini)</li>";
            echo "</ul>";
            echo "</div>";
        }

        echo "<div class='test-box'>";
        echo "<h5>🔧 Verificación de XAMPP</h5>";
        echo "<ol>";
        echo "<li>Abre <strong>XAMPP Control Panel</strong></li>";
        echo "<li>Verifica que <strong>MySQL</strong> tenga el estado <span class='badge bg-success'>Running</span></li>";
        echo "<li>Si no está corriendo, haz clic en <strong>Start</strong></li>";
        echo "<li>Si da error, revisa el puerto en <strong>Config → my.ini</strong></li>";
        echo "</ol>";
        echo "</div>";

        ?>

        <div class="text-center mt-4">
            <a href="javascript:location.reload()" class="btn btn-primary">
                🔄 Volver a Probar
            </a>
            <a href="index.php" class="btn btn-secondary">
                ← Volver al Sistema
            </a>
        </div>
    </div>
</body>
</html>


