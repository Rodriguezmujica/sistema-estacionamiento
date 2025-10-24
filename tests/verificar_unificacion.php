<?php
/**
 * 🔍 VERIFICADOR DE UNIFICACIÓN DE CONEXIONES
 * 
 * Este script verifica que todos los archivos estén usando
 * correctamente el archivo de conexión centralizado
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Verificación de Unificación</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .section { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ok { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 12px; 
            font-weight: bold;
        }
        .badge-ok { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <h1>🔍 Verificación de Unificación de Conexiones</h1>";

// ============================================
// 1. VERIFICAR CONEXIÓN PRINCIPAL
// ============================================
echo "<div class='section'>";
echo "<h2>1. Verificación de Archivo Principal</h2>";

if (file_exists('conexion.php')) {
    echo "<p class='ok'>✅ Archivo conexion.php encontrado</p>";
    
    require_once __DIR__ . '/conexion.php';
    
    if (isset($conn) && $conn->ping()) {
        echo "<p class='ok'>✅ Conexión a base de datos exitosa</p>";
        echo "<p>• Host: <strong>{$conn->host_info}</strong></p>";
        echo "<p>• Base de datos: <strong>{$dbname}</strong></p>";
        echo "<p>• Usuario: <strong>{$user}</strong></p>";
        echo "<p>• Charset: <strong>{$conn->character_set_name()}</strong></p>";
    } else {
        echo "<p class='error'>❌ Error de conexión a base de datos</p>";
    }
    
    // Verificar variables
    if (isset($conexion)) {
        echo "<p class='ok'>✅ Variable \$conexion disponible (compatibilidad)</p>";
    }
    if (isset($conn)) {
        echo "<p class='ok'>✅ Variable \$conn disponible</p>";
    }
} else {
    echo "<p class='error'>❌ Archivo conexion.php NO encontrado</p>";
}

echo "</div>";

// ============================================
// 2. VERIFICAR ARCHIVOS API
// ============================================
echo "<div class='section'>";
echo "<h2>2. Verificación de Archivos API</h2>";

$archivos_api = glob('api/*.php');
$total_api = count($archivos_api);
$correctos = 0;
$incorrectos = 0;
$problemas = [];

echo "<table>";
echo "<tr><th>Archivo</th><th>Estado</th><th>Detalles</th></tr>";

foreach ($archivos_api as $archivo) {
    $contenido = file_get_contents($archivo);
    $nombre = basename($archivo);
    
    // Verificar si usa require_once con __DIR__
    $usa_dir = preg_match('/require_once\s+__DIR__\s*\.\s*[\'"]\/\.\.\/conexion\.php/', $contenido);
    
    // Verificar si tiene conexión manual (problema)
    $tiene_mysqli_manual = preg_match('/new\s+mysqli\s*\(/', $contenido);
    
    // Verificar si usa require sin __DIR__ (advertencia)
    $usa_require_sin_dir = preg_match('/require_once\s+[\'"]\.\.\/conexion\.php/', $contenido) && !$usa_dir;
    
    echo "<tr>";
    echo "<td><code>{$nombre}</code></td>";
    
    if ($usa_dir) {
        echo "<td><span class='badge badge-ok'>✅ OK</span></td>";
        echo "<td>Usa <code>__DIR__</code> correctamente</td>";
        $correctos++;
    } elseif ($usa_require_sin_dir) {
        echo "<td><span class='badge badge-warning'>⚠️ ADVERTENCIA</span></td>";
        echo "<td>Usa require sin <code>__DIR__</code></td>";
        $incorrectos++;
        $problemas[] = $nombre;
    } elseif ($tiene_mysqli_manual) {
        echo "<td><span class='badge badge-error'>❌ ERROR</span></td>";
        echo "<td>Tiene conexión manual duplicada</td>";
        $incorrectos++;
        $problemas[] = $nombre;
    } else {
        echo "<td><span class='badge badge-warning'>⚠️ REVISAR</span></td>";
        echo "<td>No se detectó patrón de conexión</td>";
        $incorrectos++;
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<p><strong>Resumen:</strong></p>";
echo "<p class='ok'>✅ Correctos: {$correctos} de {$total_api}</p>";
if ($incorrectos > 0) {
    echo "<p class='error'>❌ Con problemas: {$incorrectos}</p>";
}

echo "</div>";

// ============================================
// 3. VERIFICAR OTROS ARCHIVOS IMPORTANTES
// ============================================
echo "<div class='section'>";
echo "<h2>3. Verificación de Archivos Raíz</h2>";

$archivos_raiz = ['login.php', 'index.php', 'crear_usuarios.php', 'actualizar_tabla_dia_pago.php'];

echo "<table>";
echo "<tr><th>Archivo</th><th>Estado</th><th>Detalles</th></tr>";

foreach ($archivos_raiz as $archivo) {
    if (!file_exists($archivo)) {
        echo "<tr>";
        echo "<td><code>{$archivo}</code></td>";
        echo "<td><span class='badge badge-warning'>⚠️ NO EXISTE</span></td>";
        echo "<td>Archivo no encontrado</td>";
        echo "</tr>";
        continue;
    }
    
    $contenido = file_get_contents($archivo);
    $usa_dir = preg_match('/require.*__DIR__.*conexion\.php/', $contenido);
    $tiene_mysqli = preg_match('/new\s+mysqli\s*\(/', $contenido);
    
    echo "<tr>";
    echo "<td><code>{$archivo}</code></td>";
    
    if ($usa_dir) {
        echo "<td><span class='badge badge-ok'>✅ OK</span></td>";
        echo "<td>Usa <code>__DIR__</code></td>";
    } elseif ($tiene_mysqli) {
        echo "<td><span class='badge badge-error'>❌ ERROR</span></td>";
        echo "<td>Tiene conexión manual</td>";
    } else {
        echo "<td><span class='badge badge-ok'>✅ OK</span></td>";
        echo "<td>No requiere conexión DB</td>";
    }
    
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// ============================================
// 4. RECOMENDACIONES
// ============================================
echo "<div class='section'>";
echo "<h2>4. Recomendaciones para Linux</h2>";

echo "<h3>✅ Configuración Actual</h3>";
echo "<ul>";
echo "<li>Sistema operativo: <strong>" . PHP_OS . "</strong></li>";
echo "<li>Versión PHP: <strong>" . PHP_VERSION . "</strong></li>";
echo "<li>Servidor: <strong>" . $_SERVER['SERVER_SOFTWARE'] . "</strong></li>";
echo "</ul>";

if (stripos(PHP_OS, 'WIN') !== false) {
    echo "<h3>💡 Estás en Windows</h3>";
    echo "<p>Para migrar a Linux (Antix), necesitarás:</p>";
} else {
    echo "<h3>🐧 Estás en Linux</h3>";
    echo "<p>Verifica que tengas configurado:</p>";
}

echo "<ol>";
echo "<li>Crear usuario MySQL específico (no usar 'root')</li>";
echo "<li>Editar <code>conexion.php</code> con las credenciales correctas</li>";
echo "<li>Configurar permisos de archivos:
    <pre>sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
sudo chmod -R 777 /var/www/html/sistemaEstacionamiento/logs</pre>
</li>";
echo "<li>Verificar que Apache y MySQL estén corriendo</li>";
echo "</ol>";

echo "<h3>📚 Documentación Disponible</h3>";
echo "<ul>";
if (file_exists('GUIA_MIGRACION_ANTIX_LINUX.md')) {
    echo "<li class='ok'>✅ <strong>GUIA_MIGRACION_ANTIX_LINUX.md</strong> - Guía completa de migración</li>";
}
if (file_exists('RESUMEN_CAMBIOS_UNIFICACION.md')) {
    echo "<li class='ok'>✅ <strong>RESUMEN_CAMBIOS_UNIFICACION.md</strong> - Resumen de cambios</li>";
}
if (file_exists('conexion_linux.php')) {
    echo "<li class='ok'>✅ <strong>conexion_linux.php</strong> - Archivo de referencia para Linux</li>";
}
echo "</ul>";

echo "</div>";

// ============================================
// 5. RESULTADO FINAL
// ============================================
echo "<div class='section'>";
echo "<h2>5. Resultado Final</h2>";

$porcentaje = ($correctos / max($total_api, 1)) * 100;

if ($porcentaje >= 90) {
    echo "<h3 class='ok'>🎉 ¡EXCELENTE! Sistema unificado correctamente</h3>";
    echo "<p>El {$porcentaje}% de los archivos API están usando el patrón correcto.</p>";
} elseif ($porcentaje >= 70) {
    echo "<h3 class='warning'>⚠️ BIEN - Algunos archivos necesitan atención</h3>";
    echo "<p>El {$porcentaje}% de los archivos están correctos.</p>";
} else {
    echo "<h3 class='error'>❌ ATENCIÓN - Requiere corrección</h3>";
    echo "<p>Solo el {$porcentaje}% de los archivos están correctos.</p>";
}

if (!empty($problemas)) {
    echo "<p><strong>Archivos con problemas:</strong></p>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li><code>{$problema}</code></li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "</body></html>";
?>

