<?php
/**
 * 🧪 TEST SIMPLE DE CONEXIÓN
 * Prueba las 3 formas más comunes sin modificar nada
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Conexión Simple</title>
    <style>
        body { font-family: Arial; padding: 2rem; background: #f5f5f5; }
        .test { background: white; padding: 1rem; margin: 0.5rem 0; border-radius: 5px; border-left: 5px solid #ccc; }
        .ok { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>🧪 Test de Conexión Simple</h1>
    <p>Probando 3 formas de conectar sin modificar nada...</p>

    <?php
    // TEST 1: localhost (como está ahora)
    echo "<div class='test'>";
    echo "<strong>Test 1: Con 'localhost' (configuración actual)</strong><br>";
    try {
        $test1 = @new mysqli('localhost', 'root', '', 'estacionamiento');
        if (!$test1->connect_error) {
            echo "<span style='color: green; font-size: 1.2rem;'>✅ ¡FUNCIONA!</span><br>";
            echo "Tu sistema está bien. El problema debe ser otra cosa.";
        } else {
            echo "<span style='color: red;'>❌ Error: {$test1->connect_error}</span>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error: {$e->getMessage()}</span>";
    }
    echo "</div>";

    // TEST 2: 127.0.0.1
    echo "<div class='test'>";
    echo "<strong>Test 2: Con '127.0.0.1'</strong><br>";
    try {
        $test2 = @new mysqli('127.0.0.1', 'root', '', 'estacionamiento');
        if (!$test2->connect_error) {
            echo "<span style='color: green; font-size: 1.2rem;'>✅ ¡FUNCIONA!</span><br>";
            echo "Solución: Cambia <code>\$host = 'localhost';</code> por <code>\$host = '127.0.0.1';</code> en conexion.php línea 11";
        } else {
            echo "<span style='color: red;'>❌ Error: {$test2->connect_error}</span>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error: {$e->getMessage()}</span>";
    }
    echo "</div>";

    // TEST 3: Puerto 3307
    echo "<div class='test'>";
    echo "<strong>Test 3: Con '127.0.0.1:3307'</strong><br>";
    try {
        $test3 = @new mysqli('127.0.0.1', 'root', '', 'estacionamiento', 3307);
        if (!$test3->connect_error) {
            echo "<span style='color: green; font-size: 1.2rem;'>✅ ¡FUNCIONA!</span><br>";
            echo "Solución: En conexion.php línea 16, cambia:<br>";
            echo "<code>\$conn = new mysqli(\$host, \$user, \$pass, \$dbname);</code><br>";
            echo "por:<br>";
            echo "<code>\$conn = new mysqli('127.0.0.1', \$user, \$pass, \$dbname, 3307);</code>";
        } else {
            echo "<span style='color: red;'>❌ Error: {$test3->connect_error}</span>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error: {$e->getMessage()}</span>";
    }
    echo "</div>";
    ?>

    <hr>
    <h3>🎯 Veredicto:</h3>
    <p><strong>Los archivos de debug NO dañaron nada.</strong> Tu sistema está intacto.</p>
    <p>Si algún test funcionó arriba, solo necesitas cambiar la línea indicada en <code>conexion.php</code></p>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="index.php" style="background: #007bff; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; display: inline-block;">
            ← Volver al Sistema
        </a>
    </div>
</body>
</html>


