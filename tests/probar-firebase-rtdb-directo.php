<?php
/**
 * 🧪 PRUEBA DIRECTA DE FIREBASE REAL-TIME DATABASE
 * Prueba la conexión directamente sin usar el SDK
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Directa Firebase RTDB</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .test-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            padding: 10px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Prueba Directa de Firebase Real-time Database</h1>
        <p>Esta página prueba la conexión directamente usando la REST API de Firebase.</p>

        <?php
        $rtdbUrl = 'https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com';
        $testPath = '/test_connection/' . uniqid() . '.json';
        $testUrl = $rtdbUrl . $testPath;
        
        $testData = [
            'test' => true,
            'timestamp' => time(),
            'message' => 'Prueba desde PHP',
            'fecha' => date('Y-m-d H:i:s')
        ];

        echo "<div class='test-box info'>";
        echo "<h3>📡 Información de la Prueba</h3>";
        echo "<strong>URL Base:</strong> <code>$rtdbUrl</code><br>";
        echo "<strong>URL de Prueba:</strong> <code>$testUrl</code><br>";
        echo "<strong>Datos a enviar:</strong>";
        echo "<code>" . json_encode($testData, JSON_PRETTY_PRINT) . "</code>";
        echo "</div>";

        // Prueba 1: Verificar que la Base de Datos Existe (intentando escribir en path permitido)
        echo "<div class='test-box'>";
        echo "<h3>Prueba 1: Verificar que la Base de Datos Existe</h3>";
        echo "<p><em>Nota: No podemos leer la raíz (/.json) porque las reglas de seguridad lo bloquean. En su lugar, verificamos escribiendo en un path permitido.</em></p>";
        
        // Intentar escribir en test_connection para verificar que la BD existe
        $verifyUrl = $rtdbUrl . '/test_connection/verify_' . time() . '.json';
        $verifyData = ['verify' => true, 'timestamp' => time()];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verifyData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            echo "<div class='success'>";
            echo "✅ <strong>Base de datos existe y está configurada correctamente</strong><br>";
            echo "HTTP Status: $httpCode<br>";
            echo "Se pudo escribir en: <code>/test_connection/</code><br>";
            echo "Respuesta: " . (empty($response) || $response === 'null' ? 'null (escritura exitosa)' : substr($response, 0, 100) . '...');
            echo "</div>";
        } elseif ($httpCode == 401 || $httpCode == 403) {
            echo "<div class='error'>";
            echo "⚠️ <strong>Error de permisos (HTTP $httpCode)</strong><br>";
            echo "Las reglas de seguridad están bloqueando el acceso.<br>";
            if ($response) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error'])) {
                    echo "Error: <code>" . htmlspecialchars($errorData['error']) . "</code><br>";
                } else {
                    echo "Respuesta: " . substr($response, 0, 200) . "<br>";
                }
            }
            echo "<br><strong>Solución:</strong><br>";
            echo "1. Ve a <a href='https://console.firebase.google.com/project/sistemaestacionamiento-46735/database' target='_blank'>Firebase Console → Realtime Database → Reglas</a><br>";
            echo "2. Configura estas reglas:<br>";
            echo "<code>";
            echo "{\n";
            echo "  \"rules\": {\n";
            echo "    \"tuu_webhook_notifications\": {\n";
            echo "      \".write\": true,\n";
            echo "      \".read\": true\n";
            echo "    },\n";
            echo "    \"test_connection\": {\n";
            echo "      \".write\": true,\n";
            echo "      \".read\": true\n";
            echo "    },\n";
            echo "    \"\$other\": {\n";
            echo "      \".read\": false,\n";
            echo "      \".write\": false\n";
            echo "    }\n";
            echo "  }\n";
            echo "}";
            echo "</code><br>";
            echo "3. Haz clic en <strong>'Publicar'</strong><br>";
            echo "4. Vuelve a ejecutar esta prueba";
            echo "</div>";
        } elseif ($httpCode == 404) {
            echo "<div class='error'>";
            echo "❌ <strong>Base de datos NO existe o no está habilitada</strong><br>";
            echo "HTTP Status: $httpCode<br>";
            echo "<strong>Solución:</strong><br>";
            echo "1. Ve a <a href='https://console.firebase.google.com/project/sistemaestacionamiento-46735' target='_blank'>Firebase Console</a><br>";
            echo "2. Haz clic en 'Realtime Database' en el menú lateral<br>";
            echo "3. Haz clic en 'Crear base de datos'<br>";
            echo "4. Selecciona ubicación y 'Comenzar en modo de prueba'<br>";
            echo "5. Copia la URL que aparece y verifica que coincida con: <code>$rtdbUrl</code>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "⚠️ <strong>Error inesperado</strong><br>";
            echo "HTTP Status: $httpCode<br>";
            if ($curlError) {
                echo "Error cURL: $curlError<br>";
            }
            echo "Respuesta: " . substr($response, 0, 200);
            echo "</div>";
        }
        echo "</div>";

        // Prueba 2: Intentar escribir datos (si la Prueba 1 fue exitosa)
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "<div class='test-box'>";
            echo "<h3>Prueba 2: Intentar Escribir Datos</h3>";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCodeWrite = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrorWrite = curl_error($ch);
            curl_close($ch);

            if ($httpCodeWrite >= 200 && $httpCodeWrite < 300) {
                echo "<div class='success'>";
                echo "✅ <strong>Escritura exitosa</strong><br>";
                echo "HTTP Status: $httpCodeWrite<br>";
                echo "Datos escritos correctamente en: <code>$testPath</code><br>";
                echo "Respuesta: " . substr($response, 0, 200);
                echo "</div>";
            } elseif ($httpCodeWrite == 403) {
                echo "<div class='error'>";
                echo "❌ <strong>Error 403: Reglas de seguridad bloquean la escritura</strong><br>";
                echo "HTTP Status: $httpCodeWrite<br>";
                echo "<strong>Solución:</strong><br>";
                echo "1. Ve a Firebase Console → Realtime Database → Reglas<br>";
                echo "2. Configura estas reglas:<br>";
                echo "<code>";
                echo "{\n";
                echo "  \"rules\": {\n";
                echo "    \"tuu_webhook_notifications\": {\n";
                echo "      \".write\": true,\n";
                echo "      \".read\": true\n";
                echo "    },\n";
                echo "    \"test_connection\": {\n";
                echo "      \".write\": true,\n";
                echo "      \".read\": true\n";
                echo "    }\n";
                echo "  }\n";
                echo "}";
                echo "</code>";
                echo "3. Haz clic en 'Publicar'";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "⚠️ <strong>Error al escribir</strong><br>";
                echo "HTTP Status: $httpCodeWrite<br>";
                if ($curlErrorWrite) {
                    echo "Error cURL: $curlErrorWrite<br>";
                }
                echo "Respuesta: " . substr($response, 0, 200);
                echo "</div>";
            }
            echo "</div>";

            // Prueba 3: Leer los datos escritos
            if ($httpCodeWrite >= 200 && $httpCodeWrite < 300) {
                echo "<div class='test-box'>";
                echo "<h3>Prueba 3: Leer los Datos Escritos</h3>";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $testUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $httpCodeRead = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCodeRead == 200) {
                    $data = json_decode($response, true);
                    echo "<div class='success'>";
                    echo "✅ <strong>Lectura exitosa</strong><br>";
                    echo "HTTP Status: $httpCodeRead<br>";
                    echo "Datos leídos:<br>";
                    echo "<code>" . json_encode($data, JSON_PRETTY_PRINT) . "</code>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "⚠️ <strong>Error al leer</strong><br>";
                    echo "HTTP Status: $httpCodeRead<br>";
                    echo "Respuesta: " . substr($response, 0, 200);
                    echo "</div>";
                }
                echo "</div>";
            }
        }

        // Resumen
        echo "<div class='test-box info'>";
        echo "<h3>📊 Resumen</h3>";
        echo "<strong>URL Base:</strong> $rtdbUrl<br>";
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "<strong>Estado Base de Datos:</strong> ✅ Existe y está configurada correctamente<br>";
        } elseif ($httpCode == 401 || $httpCode == 403) {
            echo "<strong>Estado Base de Datos:</strong> ⚠️ Existe pero las reglas bloquean el acceso<br>";
        } elseif ($httpCode == 404) {
            echo "<strong>Estado Base de Datos:</strong> ❌ No existe o no está habilitada<br>";
        } else {
            echo "<strong>Estado Base de Datos:</strong> ⚠️ Error inesperado (HTTP $httpCode)<br>";
        }
        if (isset($httpCodeWrite)) {
            echo "<strong>Estado Escritura:</strong> " . ($httpCodeWrite >= 200 && $httpCodeWrite < 300 ? "✅ Funciona" : "❌ Bloqueada") . "<br>";
        }
        echo "</div>";

        // Instrucciones
        if ($httpCode == 404) {
            echo "<div class='test-box error'>";
            echo "<h3>🔧 Instrucciones para Crear Realtime Database</h3>";
            echo "<ol>";
            echo "<li>Ve a <a href='https://console.firebase.google.com/project/sistemaestacionamiento-46735/database' target='_blank'>Firebase Console - Realtime Database</a></li>";
            echo "<li>Haz clic en <strong>'Crear base de datos'</strong></li>";
            echo "<li>Selecciona la ubicación: <strong>southamerica-east1</strong> (São Paulo)</li>";
            echo "<li>Elige <strong>'Comenzar en modo de prueba'</strong></li>";
            echo "<li>Haz clic en <strong>'Listo'</strong></li>";
            echo "<li>Ve a la pestaña <strong>'Reglas'</strong> y configura las reglas de seguridad</li>";
            echo "<li>Publica las reglas</li>";
            echo "<li>Vuelve a ejecutar esta prueba</li>";
            echo "</ol>";
            echo "<p><strong>📖 Guía detallada:</strong> <a href='../docs/GUIA-PASO-A-PASO-FIREBASE-RTD.md' target='_blank'>Ver guía completa</a></p>";
            echo "</div>";
        } elseif ($httpCode == 401 || $httpCode == 403) {
            echo "<div class='test-box error'>";
            echo "<h3>🔧 Instrucciones para Configurar las Reglas de Seguridad</h3>";
            echo "<p><strong>La base de datos existe, pero las reglas de seguridad están bloqueando el acceso.</strong></p>";
            echo "<ol>";
            echo "<li>Ve a <a href='https://console.firebase.google.com/project/sistemaestacionamiento-46735/database' target='_blank'>Firebase Console - Realtime Database → Reglas</a></li>";
            echo "<li>Reemplaza las reglas actuales con estas:</li>";
            echo "</ol>";
            echo "<code>";
            echo "{\n";
            echo "  \"rules\": {\n";
            echo "    \"tuu_webhook_notifications\": {\n";
            echo "      \".write\": true,\n";
            echo "      \".read\": true\n";
            echo "    },\n";
            echo "    \"test_connection\": {\n";
            echo "      \".write\": true,\n";
            echo "      \".read\": true\n";
            echo "    },\n";
            echo "    \"\$other\": {\n";
            echo "      \".read\": false,\n";
            echo "      \".write\": false\n";
            echo "    }\n";
            echo "  }\n";
            echo "}";
            echo "</code>";
            echo "<ol start='3'>";
            echo "<li>Haz clic en <strong>'Publicar'</strong> (botón azul arriba a la derecha)</li>";
            echo "<li>Confirma la publicación</li>";
            echo "<li>Vuelve a ejecutar esta prueba</li>";
            echo "</ol>";
            echo "<p><strong>📖 Guía detallada:</strong> <a href='../docs/GUIA-PASO-A-PASO-FIREBASE-RTD.md' target='_blank'>Ver guía completa</a></p>";
            echo "</div>";
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="verificar-firebase-tuu.php" class="button">Volver a Verificación Completa</a>
            <a href="../docs/GUIA-PASO-A-PASO-FIREBASE-RTD.md" class="button" target="_blank">Ver Guía Completa</a>
        </div>
    </div>
</body>
</html>

