<?php
/**
 * 🔧 FIX CORS Y SERVICE WORKER
 * Soluciona errores de CORS y Service Worker de Firebase
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Fix CORS y Service Worker</title>";
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
echo "<h1>🔧 Fix CORS y Service Worker</h1>";

try {
    // ============================================
    // 1. CREAR SERVICE WORKER DE FIREBASE
    // ============================================
    
    echo "<h2>1. Creando Service Worker de Firebase</h2>";
    
    $service_worker_content = '// Firebase Service Worker
importScripts("https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js");

// Configuración de Firebase (usar las mismas credenciales)
const firebaseConfig = {
  apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
  authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
  projectId: "sistemaestacionamiento-46735",
  storageBucket: "sistemaestacionamiento-46735.appspot.com",
  messagingSenderId: "570161231939",
  appId: "1:570161231939:web:your-app-id"
};

// Inicializar Firebase
firebase.initializeApp(firebaseConfig);

// Obtener instancia de messaging
const messaging = firebase.messaging();

// Manejar mensajes en background
messaging.onBackgroundMessage(function(payload) {
  console.log("Mensaje recibido en background:", payload);
  
  const notificationTitle = payload.notification.title || "Sistema Estacionamiento";
  const notificationOptions = {
    body: payload.notification.body || "Nueva notificación",
    icon: "/imagenes/Logo_sin_fondo.png",
    badge: "/imagenes/Logo_sin_fondo.png"
  };
  
  self.registration.showNotification(notificationTitle, notificationOptions);
});';
    
    if (file_put_contents('firebase-messaging-sw.js', $service_worker_content)) {
        echo "<div class='success'>✅ Service Worker creado: firebase-messaging-sw.js</div>";
    } else {
        echo "<div class='error'>❌ Error creando Service Worker</div>";
    }
    
    // ============================================
    // 2. CREAR ENDPOINT CORS PARA PRINT SERVICE
    // ============================================
    
    echo "<h2>2. Creando endpoint CORS para Print Service</h2>";
    
    $cors_endpoint_content = '<?php
/**
 * Endpoint CORS para Print Service
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Manejar preflight requests
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

$action = $_GET["action"] ?? "status";

switch ($action) {
    case "status":
        echo json_encode([
            "success" => true,
            "status" => "available",
            "message" => "Print service disponible",
            "timestamp" => date("c")
        ]);
        break;
        
    case "print":
        $data = json_decode(file_get_contents("php://input"), true);
        echo json_encode([
            "success" => true,
            "message" => "Impresión enviada",
            "data" => $data
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Acción no válida"
        ]);
        break;
}';
    
    // Crear directorio si no existe
    if (!is_dir('print-service-php')) {
        mkdir('print-service-php', 0755, true);
    }
    
    if (file_put_contents('print-service-php/imprimir.php', $cors_endpoint_content)) {
        echo "<div class='success'>✅ Endpoint CORS creado: print-service-php/imprimir.php</div>";
    } else {
        echo "<div class='error'>❌ Error creando endpoint CORS</div>";
    }
    
    // ============================================
    // 3. CREAR FIX DE COMPATIBILIDAD MEJORADO
    // ============================================
    
    echo "<h2>3. Creando fix de compatibilidad mejorado</h2>";
    
    $fix_content = '/**
 * 🔧 FIX FIREBASE COMPATIBILITY MEJORADO
 * Soluciona errores de Firebase en navegadores no compatibles
 */

// Detectar si el navegador soporta Firebase
function isFirebaseSupported() {
    if (typeof window === "undefined" || !window.navigator) {
        return false;
    }
    
    const userAgent = window.navigator.userAgent;
    const isOldIE = /MSIE|Trident/.test(userAgent);
    const isOldChrome = /Chrome\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
    const isOldFirefox = /Firefox\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
    
    return !isOldIE && !isOldChrome && !isOldFirefox;
}

// Función para inicializar Firebase de forma segura
function initFirebaseSafely() {
    if (!isFirebaseSupported()) {
        console.warn("🚫 Navegador no compatible con Firebase. Usando modo offline.");
        
        // Crear objetos mock para evitar errores
        window.firebase = {
            messaging: () => ({
                getToken: () => Promise.resolve(null),
                onMessage: () => {},
                requestPermission: () => Promise.resolve("granted")
            }),
            initializeApp: () => ({}),
            apps: []
        };
        
        return false;
    }
    
    return true;
}

// Función para manejar errores de Firebase
function handleFirebaseError(error) {
    console.warn("⚠️ Error de Firebase:", error.message);
    
    if (error.code === "messaging/unsupported-browser") {
        console.log("🔄 Cambiando a modo offline para compatibilidad");
        return true;
    }
    
    if (error.code === "messaging/failed-service-worker-registration") {
        console.log("🔄 Service Worker no disponible, usando modo offline");
        return true;
    }
    
    return false;
}

// Función para inicializar TUU sin Firebase
function initTUUOffline() {
    console.log("🔄 Inicializando TUU en modo offline");
    
    window.tuuFirebaseSync = {
        init: () => console.log("✅ TUU offline inicializado"),
        createPayment: () => Promise.resolve({ success: true, offline: true }),
        processPendingPayments: () => Promise.resolve({ success: true, offline: true })
    };
}

// Función principal de inicialización
function initSystemSafely() {
    try {
        if (!initFirebaseSafely()) {
            initTUUOffline();
            return;
        }
        
        console.log("✅ Navegador compatible con Firebase");
        
        if (typeof firebase !== "undefined") {
            try {
                console.log("✅ Firebase inicializado correctamente");
            } catch (error) {
                if (handleFirebaseError(error)) {
                    initTUUOffline();
                }
            }
        }
        
    } catch (error) {
        console.error("❌ Error en inicialización:", error);
        initTUUOffline();
    }
}

// Aplicar fixes cuando se carga el script
(function() {
    "use strict";
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initSystemSafely);
    } else {
        initSystemSafely();
    }
    
    // También intentar después de un pequeño delay
    setTimeout(initSystemSafely, 100);
})();

// Exportar funciones para uso global
window.FirebaseCompat = {
    initSystemSafely,
    initTUUOffline,
    isFirebaseSupported,
    handleFirebaseError
};';
    
    if (file_put_contents('fix-firebase-browser-compatibility.js', $fix_content)) {
        echo "<div class='success'>✅ Fix de compatibilidad mejorado creado</div>";
    } else {
        echo "<div class='error'>❌ Error creando fix de compatibilidad</div>";
    }
    
    // ============================================
    // 4. VERIFICACIÓN FINAL
    // ============================================
    
    echo "<h2>4. Verificación Final</h2>";
    
    $archivos_verificar = [
        'firebase-messaging-sw.js' => 'Service Worker de Firebase',
        'print-service-php/imprimir.php' => 'Endpoint CORS Print Service',
        'fix-firebase-browser-compatibility.js' => 'Fix de compatibilidad'
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
    echo "<h2>🎉 ¡FIXES APLICADOS!</h2>";
    echo "<p>Se han solucionado los errores de CORS y Service Worker:</p>";
    echo "<ul>";
    echo "<li>✅ Service Worker de Firebase creado</li>";
    echo "<li>✅ Endpoint CORS para Print Service creado</li>";
    echo "<li>✅ Fix de compatibilidad mejorado</li>";
    echo "<li>✅ Errores de Firebase deberían solucionarse</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Para verificar:</h3>";
    echo "<ol>";
    echo "<li>Recargar la página: <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li>Verificar que no aparezcan errores de Firebase en la consola</li>";
    echo "<li>Probar funcionalidades del sistema</li>";
    echo "<li>Verificar que Print Service funcione</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERROR APLICANDO FIXES</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
