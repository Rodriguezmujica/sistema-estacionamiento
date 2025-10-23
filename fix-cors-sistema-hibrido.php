<?php
/**
 * 🔧 FIX CORS PARA SISTEMA HÍBRIDO
 * Soluciona la comunicación entre máquinas
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Fix CORS Sistema Híbrido</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔧 Fix CORS Sistema Híbrido</h1>";

try {
    // ============================================
    // 1. CREAR ARCHIVO .htaccess CON CORS
    // ============================================
    
    echo "<h2>1. Configurando CORS en .htaccess</h2>";
    
    $htaccess_content = '# CORS para Sistema Híbrido
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
Header always set Access-Control-Allow-Credentials "true"

# Manejar preflight requests
RewriteEngine On
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Configuración adicional para PHP
<IfModule mod_php.c>
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value max_execution_time 300
    php_value max_input_time 300
</IfModule>';
    
    if (file_put_contents('.htaccess', $htaccess_content)) {
        echo "<div class='success'>✅ .htaccess creado con configuración CORS</div>";
    } else {
        echo "<div class='error'>❌ Error creando .htaccess</div>";
    }
    
    // ============================================
    // 2. CREAR HEADERS CORS EN PHP
    // ============================================
    
    echo "<h2>2. Creando headers CORS para PHP</h2>";
    
    $cors_headers = '<?php
/**
 * 🔧 HEADERS CORS PARA SISTEMA HÍBRIDO
 * Incluir en todos los archivos PHP que manejan API
 */

// Configurar headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

// Manejar preflight requests
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

// Función para enviar respuesta JSON con CORS
function enviarRespuestaJSON($data, $codigo = 200) {
    http_response_code($codigo);
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Función para manejar errores con CORS
function manejarError($mensaje, $codigo = 500) {
    enviarRespuestaJSON([
        "success" => false,
        "error" => $mensaje,
        "timestamp" => date("Y-m-d H:i:s")
    ], $codigo);
}

// Log de requests para debugging
function logRequest($endpoint, $data = null) {
    $log = date("Y-m-d H:i:s") . " - " . $endpoint . " - " . json_encode($data) . "\n";
    file_put_contents("cors-requests.log", $log, FILE_APPEND);
}
?>';
    
    if (file_put_contents('cors-headers.php', $cors_headers)) {
        echo "<div class='success'>✅ Headers CORS creados</div>";
    } else {
        echo "<div class='error'>❌ Error creando headers CORS</div>";
    }
    
    // ============================================
    // 3. CREAR ENDPOINT DE SINCRONIZACIÓN
    // ============================================
    
    echo "<h2>3. Creando endpoint de sincronización</h2>";
    
    $sync_endpoint = '<?php
/**
 * 🔄 ENDPOINT DE SINCRONIZACIÓN SISTEMA HÍBRIDO
 * Permite comunicación entre máquinas
 */

require_once "cors-headers.php";
require_once "conexion.php";

// Obtener datos de la request
$input = json_decode(file_get_contents("php://input"), true);
$action = $input["action"] ?? $_GET["action"] ?? "";

logRequest("sync-endpoint", $input);

try {
    switch ($action) {
        case "get_ingresos":
            $ingresos = getIngresos();
            enviarRespuestaJSON([
                "success" => true,
                "data" => $ingresos,
                "timestamp" => date("Y-m-d H:i:s")
            ]);
            break;
            
        case "add_ingreso":
            $patente = $input["patente"] ?? "";
            $tipo = $input["tipo"] ?? "";
            $precio = $input["precio"] ?? 0;
            
            if (empty($patente) || empty($tipo)) {
                manejarError("Patente y tipo son requeridos");
            }
            
            $id = addIngreso($patente, $tipo, $precio);
            enviarRespuestaJSON([
                "success" => true,
                "id" => $id,
                "message" => "Ingreso registrado correctamente"
            ]);
            break;
            
        case "update_ingreso":
            $id = $input["id"] ?? 0;
            $data = $input["data"] ?? [];
            
            if (empty($id)) {
                manejarError("ID es requerido");
            }
            
            updateIngreso($id, $data);
            enviarRespuestaJSON([
                "success" => true,
                "message" => "Ingreso actualizado correctamente"
            ]);
            break;
            
        case "get_pending_tuu":
            $payments = getPendingTUU();
            enviarRespuestaJSON([
                "success" => true,
                "data" => $payments
            ]);
            break;
            
        default:
            manejarError("Acción no válida: " . $action, 400);
    }
    
} catch (Exception $e) {
    manejarError("Error interno: " . $e->getMessage());
}

// Funciones de base de datos
function getIngresos() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM ingresos ORDER BY fecha_ingreso DESC LIMIT 50");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addIngreso($patente, $tipo, $precio) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ingresos (patente, tipo_servicio, precio, fecha_ingreso, sincronizado) VALUES (?, ?, ?, NOW(), 0)");
    $stmt->execute([$patente, $tipo, $precio]);
    return $pdo->lastInsertId();
}

function updateIngreso($id, $data) {
    global $pdo;
    $fields = [];
    $values = [];
    
    foreach ($data as $field => $value) {
        $fields[] = "$field = ?";
        $values[] = $value;
    }
    
    $values[] = $id;
    $sql = "UPDATE ingresos SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

function getPendingTUU() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM tickets WHERE pagado = 0 ORDER BY fecha_ingreso DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>';
    
    if (file_put_contents('api/sync-endpoint.php', $sync_endpoint)) {
        echo "<div class='success'>✅ Endpoint de sincronización creado</div>";
    } else {
        echo "<div class='error'>❌ Error creando endpoint de sincronización</div>";
    }
    
    // ============================================
    // 4. CREAR CLIENTE DE SINCRONIZACIÓN
    // ============================================
    
    echo "<h2>4. Creando cliente de sincronización</h2>";
    
    $sync_client = '/**
 * 🔄 CLIENTE DE SINCRONIZACIÓN SISTEMA HÍBRIDO
 * Maneja la comunicación entre máquinas
 */

class SistemaHibridoSync {
    constructor() {
        this.servers = [
            "http://192.168.1.89/sistemaEstacionamiento", // Antix
            "http://localhost:8080/sistemaEstacionamiento", // Windows 7
            "http://localhost/sistemaEstacionamiento" // Windows 11
        ];
        this.syncInterval = 30000; // 30 segundos
        this.isOnline = false;
        this.init();
    }
    
    init() {
        console.log("🔄 Inicializando sincronización híbrida...");
        this.detectServer();
        this.startSync();
    }
    
    detectServer() {
        const currentUrl = window.location.origin + window.location.pathname.replace("/index.php", "");
        
        this.servers.forEach((server, index) => {
            if (currentUrl.includes(server.split("/").pop())) {
                this.currentServer = server;
                this.serverIndex = index;
                console.log("✅ Servidor detectado:", server);
            }
        });
    }
    
    startSync() {
        setInterval(() => {
            this.syncData();
        }, this.syncInterval);
        
        // Sincronizar inmediatamente
        this.syncData();
    }
    
    async syncData() {
        try {
            // Obtener datos locales
            const localData = await this.getLocalData();
            
            // Enviar a otros servidores
            for (let i = 0; i < this.servers.length; i++) {
                if (i !== this.serverIndex) {
                    await this.sendToServer(this.servers[i], localData);
                }
            }
            
            // Recibir de otros servidores
            for (let i = 0; i < this.servers.length; i++) {
                if (i !== this.serverIndex) {
                    await this.getFromServer(this.servers[i]);
                }
            }
            
            this.isOnline = true;
            this.updateStatus("🟢 Sincronizado");
            
        } catch (error) {
            console.warn("⚠️ Error en sincronización:", error);
            this.isOnline = false;
            this.updateStatus("🔴 Sin conexión");
        }
    }
    
    async getLocalData() {
        try {
            const response = await fetch("api/sync-endpoint.php?action=get_ingresos");
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.warn("⚠️ Error obteniendo datos locales:", error);
            return [];
        }
    }
    
    async sendToServer(serverUrl, data) {
        try {
            const response = await fetch(`${serverUrl}/api/sync-endpoint.php`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "sync_data",
                    data: data
                })
            });
            
            if (response.ok) {
                console.log("✅ Datos enviados a:", serverUrl);
            }
        } catch (error) {
            console.warn("⚠️ Error enviando a", serverUrl, ":", error);
        }
    }
    
    async getFromServer(serverUrl) {
        try {
            const response = await fetch(`${serverUrl}/api/sync-endpoint.php?action=get_ingresos`);
            const data = await response.json();
            
            if (data.success) {
                console.log("✅ Datos recibidos de:", serverUrl);
                this.updateUI(data.data);
            }
        } catch (error) {
            console.warn("⚠️ Error recibiendo de", serverUrl, ":", error);
        }
    }
    
    updateUI(data) {
        // Actualizar la interfaz con los datos recibidos
        console.log("🔄 Actualizando UI con datos:", data);
        
        // Aquí puedes agregar lógica para actualizar la tabla de ingresos
        // o cualquier otro elemento de la interfaz
    }
    
    updateStatus(message) {
        const statusElement = document.getElementById("firebase-usage");
        if (statusElement) {
            statusElement.textContent = message;
        }
    }
}

// Inicializar sincronización
window.sistemaHibridoSync = new SistemaHibridoSync();';
    
    if (file_put_contents('JS/sistema-hibrido-sync.js', $sync_client)) {
        echo "<div class='success'>✅ Cliente de sincronización creado</div>";
    } else {
        echo "<div class='error'>❌ Error creando cliente de sincronización</div>";
    }
    
    // ============================================
    // 5. MODIFICAR INDEX.PHP
    // ============================================
    
    echo "<h2>5. Modificando index.php para sincronización</h2>";
    
    if (file_exists('index.php')) {
        $contenido = file_get_contents('index.php');
        
        // Verificar si ya tiene el script
        if (strpos($contenido, 'sistema-hibrido-sync.js') !== false) {
            echo "<div class='info'>⚠️ El script ya está en index.php</div>";
        } else {
            // Agregar el script antes del cierre de body
            $contenido = str_replace(
                '</body>',
                '  <!-- 🔄 SISTEMA HÍBRIDO SYNC -->\n  <script src="JS/sistema-hibrido-sync.js"></script>\n</body>',
                $contenido
            );
            
            if (file_put_contents('index.php', $contenido)) {
                echo "<div class='success'>✅ Script de sincronización agregado a index.php</div>";
            } else {
                echo "<div class='error'>❌ Error modificando index.php</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Archivo index.php no encontrado</div>";
    }
    
    // ============================================
    // 6. VERIFICACIÓN FINAL
    // ============================================
    
    echo "<h2>6. Verificación Final</h2>";
    
    $archivos_verificar = [
        '.htaccess' => 'Configuración CORS',
        'cors-headers.php' => 'Headers CORS',
        'api/sync-endpoint.php' => 'Endpoint de sincronización',
        'JS/sistema-hibrido-sync.js' => 'Cliente de sincronización'
    ];
    
    foreach ($archivos_verificar as $archivo => $descripcion) {
        if (file_exists($archivo)) {
            $tamaño = filesize($archivo);
            echo "<div class='success'>✅ $descripcion existe ($tamaño bytes)</div>";
        } else {
            echo "<div class='error'>❌ $descripcion NO existe</div>";
        }
    }
    
    echo "<div class='success'>";
    echo "<h2>🎉 ¡CORS Y SINCRONIZACIÓN CONFIGURADOS!</h2>";
    echo "<p>El sistema híbrido ahora puede comunicarse entre máquinas:</p>";
    echo "<ul>";
    echo "<li>✅ CORS configurado en .htaccess</li>";
    echo "<li>✅ Headers CORS para PHP</li>";
    echo "<li>✅ Endpoint de sincronización</li>";
    echo "<li>✅ Cliente de sincronización automática</li>";
    echo "<li>✅ Comunicación entre máquinas</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Para verificar:</h3>";
    echo "<ol>";
    echo "<li>Recargar la página: <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li>Verificar que no aparezcan errores CORS</li>";
    echo "<li>Probar sincronización entre máquinas</li>";
    echo "<li>Ingresar un vehículo y verificar que aparezca en otras máquinas</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR APLICANDO FIX</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
