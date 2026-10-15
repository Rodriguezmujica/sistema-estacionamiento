<?php
/**
 * 🔧 AGREGAR COLUMNAS TUU AUTOMÁTICAMENTE
 * Agrega las columnas necesarias para TUU si no existen
 */

require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Columnas TUU</title>
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
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Agregar Columnas TUU a la Tabla Ingresos</h1>

        <?php
        if (!$conn || $conn->connect_error) {
            echo "<div class='error'>❌ Error de conexión a la base de datos: " . ($conn->connect_error ?? 'Error desconocido') . "</div>";
            exit;
        }

        $columnas = [
            'transaction_id_tuu' => 'VARCHAR(50) NULL',
            'total_calculado_tuu' => 'DECIMAL(10,2) NULL',
            'fecha_intento_tuu' => 'DATETIME NULL'
        ];

        $resultados = [];
        $todoOk = true;

        foreach ($columnas as $columna => $definicion) {
            // Verificar si la columna existe
            $check = $conn->query("SHOW COLUMNS FROM ingresos LIKE '$columna'");
            
            if ($check && $check->num_rows > 0) {
                $resultados[] = [
                    'columna' => $columna,
                    'estado' => 'existe',
                    'mensaje' => "✅ La columna '$columna' ya existe"
                ];
            } else {
                // Agregar la columna
                $sql = "ALTER TABLE ingresos ADD COLUMN $columna $definicion";
                
                if ($conn->query($sql)) {
                    $resultados[] = [
                        'columna' => $columna,
                        'estado' => 'agregada',
                        'mensaje' => "✅ Columna '$columna' agregada exitosamente"
                    ];
                } else {
                    $resultados[] = [
                        'columna' => $columna,
                        'estado' => 'error',
                        'mensaje' => "❌ Error agregando '$columna': " . $conn->error
                    ];
                    $todoOk = false;
                }
            }
        }

        // Mostrar resultados
        echo "<h2>Resultados:</h2>";
        foreach ($resultados as $resultado) {
            $clase = $resultado['estado'] === 'error' ? 'error' : 'success';
            echo "<div class='$clase'>{$resultado['mensaje']}</div>";
        }

        if ($todoOk) {
            echo "<div class='info'>";
            echo "<h3>✅ Verificación Final</h3>";
            
            // Mostrar estructura actualizada
            echo "<h4>Estructura de la tabla 'ingresos' (columnas TUU):</h4>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
            
            $result = $conn->query("SHOW COLUMNS FROM ingresos");
            $columnas_tuu_encontradas = 0;
            while ($row = $result->fetch_assoc()) {
                if (in_array($row['Field'], array_keys($columnas))) {
                    echo "{$row['Field']} - {$row['Type']} - {$row['Null']}\n";
                    $columnas_tuu_encontradas++;
                }
            }
            echo "</pre>";
            
            if ($columnas_tuu_encontradas === count($columnas)) {
                echo "<p><strong>✅ Todas las columnas TUU están presentes en la tabla.</strong></p>";
            }
            
            echo "</div>";
            
            echo "<div style='text-align: center; margin-top: 30px;'>";
            echo "<a href='../tests/verificar-firebase-tuu.php' class='button'>Volver a Verificación Firebase</a>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h3>⚠️ Hubo errores al agregar algunas columnas</h3>";
            echo "<p>Por favor, revisa los errores y ejecuta este script nuevamente.</p>";
            echo "</div>";
        }

        $conn->close();
        ?>
    </div>
</body>
</html>

