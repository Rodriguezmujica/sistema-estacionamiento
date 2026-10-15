<?php
/**
 * ✅ VERIFICACIÓN DE ESCRITURA EN FIREBASE REAL-TIME DATABASE
 * Verifica que puedas escribir datos en Firebase
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Escritura Firebase</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
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
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }
        code {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            display: block;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
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
        <h1>✅ Verificación de Escritura en Firebase</h1>
        <p>Esta página verifica que puedas escribir datos en Firebase Real-time Database.</p>

        <?php
        $rtdbUrl = 'https://sistemaestacionamiento-46735-default-rtdb.firebaseio.com';
        $testPath = '/tuu_webhook_notifications/test_' . time() . '.json';
        $testUrl = $rtdbUrl . $testPath;
        
        $testData = [
            'transaction_id' => 'TEST-' . time(),
            'status' => 'test',
            'amount' => 1000,
            'patente' => 'TEST123',
            'fecha_pago' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'test' => true,
            'message' => 'Prueba de escritura desde PHP'
        ];

        echo "<div class='info'>";
        echo "<h3>📡 Información de la Prueba</h3>";
        echo "<strong>URL Base:</strong> <code>$rtdbUrl</code><br>";
        echo "<strong>Ruta de Prueba:</strong> <code>$testPath</code><br>";
        echo "<strong>URL Completa:</strong> <code>$testUrl</code><br>";
        echo "<strong>Datos a escribir:</strong>";
        echo "<code>" . json_encode($testData, JSON_PRETTY_PRINT) . "</code>";
        echo "</div>";

        // Prueba de escritura
        echo "<div class='info'>";
        echo "<h3>🧪 Intentando Escribir Datos...</h3>";
        echo "</div>";

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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            echo "<div class='success'>";
            echo "<h3>✅ ¡Éxito! Escritura Funcionando</h3>";
            echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
            echo "<p><strong>Datos escritos correctamente en:</strong></p>";
            echo "<code>$testPath</code>";
            echo "<p><strong>Respuesta de Firebase:</strong></p>";
            echo "<code>" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "</code>";
            echo "<p><strong>✅ Tu Firebase Real-time Database está configurado correctamente y listo para recibir webhooks de TUU.</strong></p>";
            echo "</div>";

            // Verificar lectura
            echo "<div class='info'>";
            echo "<h3>📖 Verificando Lectura...</h3>";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $readResponse = curl_exec($ch);
            $readHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($readHttpCode == 200) {
                $readData = json_decode($readResponse, true);
                echo "<div class='success'>";
                echo "<p>✅ <strong>Lectura exitosa</strong></p>";
                echo "<p><strong>Datos leídos:</strong></p>";
                echo "<code>" . json_encode($readData, JSON_PRETTY_PRINT) . "</code>";
                echo "</div>";
            }
            echo "</div>";

        } elseif ($httpCode == 403) {
            echo "<div class='error'>";
            echo "<h3>❌ Error 403: Reglas de Seguridad Bloqueando</h3>";
            echo "<p>Las reglas de seguridad de Firebase están bloqueando la escritura.</p>";
            echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
            if ($curlError) {
                echo "<p><strong>Error:</strong> $curlError</p>";
            }
            echo "<p><strong>Respuesta:</strong> " . substr($response, 0, 200) . "</p>";
            echo "<h4>🔧 Solución:</h4>";
            echo "<ol>";
            echo "<li>Ve a <a href='https://console.firebase.google.com/project/sistemaestacionamiento-46735/database' target='_blank'>Firebase Console → Realtime Database → Reglas</a></li>";
            echo "<li>Reemplaza las reglas con este código:</li>";
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
            echo "    \"$other\": {\n";
            echo "      \".read\": false,\n";
            echo "      \".write\": false\n";
            echo "    }\n";
            echo "  }\n";
            echo "}";
            echo "</code>";
            echo "<p>3. Haz clic en <strong>'Publicar'</strong></p>";
            echo "<p>4. Vuelve a ejecutar esta prueba</p>";
            echo "</div>";

        } elseif ($httpCode == 404) {
            echo "<div class='error'>";
            echo "<h3>❌ Error 404: Base de Datos No Encontrada</h3>";
            echo "<p>Aunque la URL responde con <code>null</code>, no se puede escribir en esta ruta específica.</p>";
            echo "<p><strong>Posibles causas:</strong></p>";
            echo "<ul>";
            echo "<li>La base de datos existe pero las reglas están bloqueando completamente</li>";
            echo "<li>La URL de la base de datos es diferente</li>";
            echo "</ul>";
            echo "<p><strong>Solución:</strong></p>";
            echo "<ol>";
            echo "<li>Verifica en Firebase Console la URL exacta de tu Realtime Database</li>";
            echo "<li>Configura las reglas como se muestra arriba</li>";
            echo "</ol>";
            echo "</div>";

        } else {
            echo "<div class='error'>";
            echo "<h3>⚠️ Error Inesperado</h3>";
            echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
            if ($curlError) {
                echo "<p><strong>Error cURL:</strong> $curlError</p>";
            }
            echo "<p><strong>Respuesta:</strong> " . substr($response, 0, 500) . "</p>";
            echo "</div>";
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="verificar-firebase-tuu.php" class="button">Volver a Verificación Completa</a>
            <a href="probar-firebase-rtdb-directo.php" class="button">Probar Conexión Directa</a>
        </div>
    </div>
</body>
</html>

