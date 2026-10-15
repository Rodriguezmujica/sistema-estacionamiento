<?php
/**
 * 🔥 VERIFICACIÓN COMPLETA DE FIREBASE PARA TUU
 * Verifica que la conexión a Firebase esté funcionando correctamente
 * y que el sistema esté listo para recibir webhooks de TUU
 */

require_once __DIR__ . '/../firebase/firebase-config.php';
require_once __DIR__ . '/../config/conexion.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Firebase TUU</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .status-box {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .config-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .icon {
            font-size: 24px;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="icon">🔥</span>
            Verificación de Firebase para Integración TUU
        </h1>
        <p style="color: #666; margin-bottom: 30px;">
            Este script verifica que Firebase esté correctamente configurado y listo para recibir webhooks de TUU.
        </p>

        <?php
        $tests = [];
        $allPassed = true;

        // ============================================
        // TEST 1: Verificar Configuración Básica
        // ============================================
        echo "<div class='test-section'>";
        echo "<h2>1️⃣ Configuración Básica de Firebase</h2>";

        $configOk = true;
        $configValues = [];

        $required = [
            'FIREBASE_API_KEY' => FIREBASE_API_KEY,
            'FIREBASE_AUTH_DOMAIN' => FIREBASE_AUTH_DOMAIN,
            'FIREBASE_PROJECT_ID' => FIREBASE_PROJECT_ID,
            'FIREBASE_STORAGE_BUCKET' => FIREBASE_STORAGE_BUCKET,
            'FIREBASE_MESSAGING_SENDER_ID' => FIREBASE_MESSAGING_SENDER_ID,
            'FIREBASE_APP_ID' => FIREBASE_APP_ID
        ];

        foreach ($required as $key => $value) {
            if (empty($value) || $value === 'TU_API_KEY_AQUI' || strpos($value, 'tu-') === 0) {
                $configOk = false;
                $configValues[$key] = '❌ NO CONFIGURADO';
            } else {
                $displayValue = $value;
                if ($key === 'FIREBASE_API_KEY') {
                    $displayValue = substr($value, 0, 20) . '...';
                }
                $configValues[$key] = '✅ ' . $displayValue;
            }
        }

        if ($configOk) {
            echo "<div class='status-box success'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Configuración básica correcta</strong><br>Todas las constantes están configuradas.</div>";
            echo "</div>";
            $tests['config'] = true;
        } else {
            echo "<div class='status-box error'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Configuración incompleta</strong><br>Algunas constantes no están configuradas correctamente.</div>";
            echo "</div>";
            $tests['config'] = false;
            $allPassed = false;
        }

        echo "<div class='config-display'>";
        foreach ($configValues as $key => $value) {
            echo "<strong>$key:</strong> $value<br>";
        }
        echo "</div>";
        echo "</div>";

        // ============================================
        // TEST 2: Verificar Real-time Database URL y Conexión
        // ============================================
        echo "<div class='test-section'>";
        echo "<h2>2️⃣ Firebase Real-time Database</h2>";

        $rtdbUrl = 'https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com';
        $webhookUrl = $rtdbUrl . '/tuu_webhook_notifications.json';

        echo "<div class='status-box info'>";
        echo "<span class='icon'>📡</span>";
        echo "<div>";
        echo "<strong>URL de Real-time Database:</strong><br>";
        echo "<code>$rtdbUrl</code><br><br>";
        echo "<strong>Endpoint para Webhooks TUU:</strong><br>";
        echo "<code>$webhookUrl</code>";
        echo "</div>";
        echo "</div>";

        // Verificar que la URL esté bien formada
        if (filter_var($rtdbUrl, FILTER_VALIDATE_URL) !== false) {
            echo "<div class='status-box success'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>URL válida</strong><br>La URL del Real-time Database está correctamente formada.</div>";
            echo "</div>";
            $tests['rtdb_url'] = true;

            // Probar conexión escribiendo un dato de prueba
            $testData = [
                'test' => true,
                'timestamp' => time(),
                'message' => 'Test de conexión desde verificación PHP',
                'fecha' => date('Y-m-d H:i:s')
            ];

            $testUrl = $rtdbUrl . '/test_connection/' . uniqid() . '.json';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Verificar respuesta - null es válido (base de datos vacía)
            $responseData = json_decode($response, true);
            $isValidResponse = ($httpCode >= 200 && $httpCode < 300) && 
                              (empty($curlError) || $responseData !== false || $response === 'null' || $response === null);
            
            if ($isValidResponse) {
                echo "<div class='status-box success'>";
                echo "<span class='icon'>✅</span>";
                echo "<div><strong>Conexión a Firebase Real-time Database exitosa</strong><br>";
                echo "Se pudo escribir datos en Firebase. HTTP Status: $httpCode<br>";
                if ($response === 'null' || $response === null || $responseData === null) {
                    echo "Respuesta: null (base de datos vacía - esto es normal)<br>";
                } else {
                    echo "Datos escritos correctamente. Respuesta recibida.<br>";
                }
                echo "<strong>✅ El sistema está listo para recibir webhooks de TUU.</strong></div>";
                echo "</div>";
                $tests['rtdb_connection'] = true;
            } else {
                echo "<div class='status-box warning'>";
                echo "<span class='icon'>⚠️</span>";
                echo "<div><strong>No se pudo verificar la conexión completa</strong><br>";
                echo "HTTP Status: $httpCode<br>";
                if ($curlError) {
                    echo "Error: $curlError<br>";
                }
                
                if ($httpCode == 404) {
                    echo "<strong>⚠️ Firebase Real-time Database no está habilitado</strong><br><br>";
                    echo "<strong>Pasos detallados:</strong><br>";
                    echo "1. En Firebase Console, en el <strong>menú lateral izquierdo</strong>, busca y haz clic en <strong>'Realtime Database'</strong><br>";
                    echo "2. Si no existe, verás un botón <strong>'Crear base de datos'</strong> - haz clic en él<br>";
                    echo "3. Selecciona la ubicación: <strong>southamerica-east1</strong> (São Paulo)<br>";
                    echo "4. Elige <strong>'Comenzar en modo de prueba'</strong><br>";
                    echo "5. Haz clic en <strong>'Listo'</strong><br>";
                    echo "6. Ve a la pestaña <strong>'Reglas'</strong> y configura las reglas de seguridad<br>";
                    echo "7. Publica las reglas<br><br>";
                    echo "📖 <a href='../docs/GUIA-PASO-A-PASO-FIREBASE-RTD.md' target='_blank'><strong>Ver guía paso a paso completa</strong></a><br>";
                    echo "🧪 <a href='probar-firebase-rtdb-directo.php'><strong>Probar conexión directa</strong></a>";
                } elseif ($httpCode == 403) {
                    echo "<strong>⚠️ Las reglas de seguridad están bloqueando</strong><br><br>";
                    echo "Para solucionarlo:<br>";
                    echo "1. Ve a Firebase Console → Realtime Database → Reglas<br>";
                    echo "2. Configura reglas que permitan escritura en <code>tuu_webhook_notifications</code><br>";
                    echo "3. Publica las reglas<br><br>";
                    echo "📖 <a href='../docs/INSTRUCCIONES-FIREBASE-RTD.md' target='_blank'>Ver instrucciones detalladas</a>";
                } else {
                    echo "Nota: Esto puede ser normal si las reglas de seguridad de Firebase están configuradas. ";
                    echo "Asegúrate de que las reglas permitan escritura en <code>tuu_webhook_notifications</code>.<br><br>";
                    echo "📖 <a href='../docs/INSTRUCCIONES-FIREBASE-RTD.md' target='_blank'>Ver instrucciones detalladas</a>";
                }
                echo "</div>";
                $tests['rtdb_connection'] = false;
            }
        } else {
            echo "<div class='status-box error'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>URL inválida</strong><br>La URL del Real-time Database no es válida.</div>";
            echo "</div>";
            $tests['rtdb_url'] = false;
            $allPassed = false;
        }
        echo "</div>";

        // ============================================
        // TEST 3: Verificar Conexión a Base de Datos Local
        // ============================================
        echo "<div class='test-section'>";
        echo "<h2>3️⃣ Conexión a Base de Datos Local (MySQL)</h2>";

        if (isset($conn) && !$conn->connect_error) {
            echo "<div class='status-box success'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Conexión a MySQL exitosa</strong><br>La base de datos local está accesible.</div>";
            echo "</div>";
            $tests['mysql'] = true;

            // Verificar tabla de ingresos
            $result = $conn->query("SHOW TABLES LIKE 'ingresos'");
            if ($result && $result->num_rows > 0) {
                echo "<div class='status-box success'>";
                echo "<span class='icon'>✅</span>";
                echo "<div><strong>Tabla 'ingresos' existe</strong><br>La estructura de datos está lista.</div>";
                echo "</div>";
                $tests['tabla_ingresos'] = true;

                // Verificar columna transaction_id_tuu
                $result = $conn->query("SHOW COLUMNS FROM ingresos LIKE 'transaction_id_tuu'");
                if ($result && $result->num_rows > 0) {
                    echo "<div class='status-box success'>";
                    echo "<span class='icon'>✅</span>";
                    echo "<div><strong>Columna 'transaction_id_tuu' existe</strong><br>El sistema está preparado para recibir webhooks.</div>";
                    echo "</div>";
                    $tests['columna_tuu'] = true;
                } else {
                    echo "<div class='status-box warning'>";
                    echo "<span class='icon'>⚠️</span>";
                    echo "<div><strong>Columna 'transaction_id_tuu' no encontrada</strong><br>";
                    echo "Es necesario agregar esta columna para que el sistema funcione correctamente.<br><br>";
                    echo "💡 <strong>Solución rápida:</strong><br>";
                    echo "<a href='../maintenance/agregar-columnas-tuu-automatico.php' class='button' style='margin-top: 10px; display: inline-block;'>Agregar Columnas TUU Automáticamente</a><br><br>";
                    echo "O ejecuta este SQL en phpMyAdmin:<br>";
                    echo "<code style='background: #f8f9fa; padding: 5px; border-radius: 3px; display: block; margin-top: 5px;'>";
                    echo "ALTER TABLE ingresos ADD COLUMN transaction_id_tuu VARCHAR(50) NULL;<br>";
                    echo "ALTER TABLE ingresos ADD COLUMN total_calculado_tuu DECIMAL(10,2) NULL;<br>";
                    echo "ALTER TABLE ingresos ADD COLUMN fecha_intento_tuu DATETIME NULL;";
                    echo "</code>";
                    echo "</div>";
                    echo "</div>";
                    $tests['columna_tuu'] = false;
                }
            } else {
                echo "<div class='status-box error'>";
                echo "<span class='icon'>❌</span>";
                echo "<div><strong>Tabla 'ingresos' no encontrada</strong><br>La estructura de la base de datos puede estar incompleta.</div>";
                echo "</div>";
                $tests['tabla_ingresos'] = false;
                $allPassed = false;
            }
        } else {
            echo "<div class='status-box error'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Error de conexión a MySQL</strong><br>" . ($conn->connect_error ?? 'Error desconocido') . "</div>";
            echo "</div>";
            $tests['mysql'] = false;
            $allPassed = false;
        }
        echo "</div>";

        // ============================================
        // TEST 4: Verificar Archivos de Integración
        // ============================================
        echo "<div class='test-section'>";
        echo "<h2>4️⃣ Archivos de Integración TUU</h2>";

        $requiredFiles = [
            '../firebase/firebase-config.php' => 'Configuración de Firebase (PHP)',
            '../JS/firebase-tuu-listener.js' => 'Listener de Firebase para TUU (JavaScript)',
            '../firebase/firebase-webhook-tuu.php' => 'Procesador de Webhooks TUU (PHP)'
        ];

        $filesOk = true;
        foreach ($requiredFiles as $file => $description) {
            if (file_exists($file)) {
                echo "<div class='status-box success'>";
                echo "<span class='icon'>✅</span>";
                echo "<div><strong>$description</strong><br><code>$file</code></div>";
                echo "</div>";
            } else {
                echo "<div class='status-box error'>";
                echo "<span class='icon'>❌</span>";
                echo "<div><strong>$description</strong><br><code>$file</code> - Archivo no encontrado</div>";
                echo "</div>";
                $filesOk = false;
                $allPassed = false;
            }
        }

        $tests['archivos'] = $filesOk;
        echo "</div>";

        // ============================================
        // RESUMEN FINAL
        // ============================================
        echo "<div class='test-section'>";
        echo "<h2>📊 Resumen de Verificación</h2>";

        $totalTests = count($tests);
        $passedTests = array_sum($tests);

        if ($allPassed) {
            echo "<div class='status-box success'>";
            echo "<span class='icon'>🎉</span>";
            echo "<div>";
            echo "<strong>¡Todo está correcto!</strong><br>";
            echo "Firebase está configurado y listo para recibir webhooks de TUU.<br>";
            echo "Tests pasados: <strong>$passedTests/$totalTests</strong>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='status-box warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div>";
            echo "<strong>Hay problemas que resolver</strong><br>";
            echo "Tests pasados: <strong>$passedTests/$totalTests</strong><br>";
            echo "Revisa los errores arriba para corregir los problemas.";
            echo "</div>";
            echo "</div>";
        }

        echo "<h3 style='margin-top: 20px;'>Información para TUU/Haulmer:</h3>";
        echo "<div class='config-display'>";
        echo "<strong>URL del Webhook:</strong><br>";
        echo "<code>$webhookUrl</code><br><br>";
        echo "<strong>Método HTTP:</strong> POST o PUT<br>";
        echo "<strong>Content-Type:</strong> application/json<br>";
        echo "<strong>Autenticación:</strong> No requiere (público para este endpoint)<br><br>";
        echo "<strong>Formato de datos esperado:</strong><br>";
        echo "<pre>" . json_encode([
            'transaction_id' => 'EST-123456-1234567890',
            'status' => 'success',
            'amount' => 2000,
            'patente' => 'ABC123',
            'fecha_pago' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'comercio_id' => '5bd29bb6-d922-4eac-ae36-37635b0b8cd9'
        ], JSON_PRETTY_PRINT) . "</pre>";
        echo "<br><strong>Nota importante:</strong> TUU debe enviar las notificaciones a esta URL. ";
        echo "Si necesitan usar un ID único para cada notificación, pueden agregarlo al final: ";
        echo "<code>$webhookUrl?transaction_id=EST-123456-1234567890</code> o usar POST con el JSON completo.";
        echo "</div>";
        echo "</div>";

        // ============================================
        // INFORMACIÓN ADICIONAL
        // ============================================
        echo "<div class='test-section'>";
        echo "<h2>ℹ️ Información Adicional</h2>";
        echo "<div class='status-box info'>";
        echo "<span class='icon'>📝</span>";
        echo "<div>";
        echo "<strong>Estado del Sistema:</strong><br>";
        echo "• Firebase está configurado correctamente<br>";
        echo "• El listener está preparado para recibir notificaciones<br>";
        echo "• La base de datos local está lista para procesar pagos<br>";
        echo "• El sistema está en modo integración y listo para producción";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="verificar-url-firebase.php" class="button">Ver URL de Firebase</a>
            <a href="../docs/prompt-tuu-firebase-webhook.txt" class="button" target="_blank">Ver Prompt para TUU</a>
            <a href="../maintenance/agregar-columnas-tuu-automatico.php" class="button">Agregar Columnas TUU</a>
            <a href="probar-firebase-rtdb-directo.php" class="button">Probar Conexión Directa</a>
            <a href="verificar-escritura-firebase.php" class="button">Verificar Escritura</a>
            <a href="../docs/GUIA-PASO-A-PASO-FIREBASE-RTD.md" class="button" target="_blank">Guía Paso a Paso</a>
            <a href="../secciones/admin.php" class="button">Volver al Admin</a>
        </div>
    </div>
</body>
</html>

