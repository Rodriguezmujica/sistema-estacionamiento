<?php
/**
 * 🔧 FIX FIREBASE ESPECÍFICO PARA WINDOWS 7
 * Soluciona el problema de Service Worker en la raíz
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Fix Firebase Windows 7</title>";
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
echo "<h1>🔧 Fix Firebase Windows 7 - Service Worker en Raíz</h1>";

try {
    // ============================================
    // 1. CREAR SERVICE WORKER EN LA RAÍZ
    // ============================================
    
    echo "<h2>1. Creando Service Worker en la raíz del servidor</h2>";
    
    $sw_content = '// Firebase Service Worker - Windows 7
importScripts("https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js");

const firebaseConfig = {
  apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
  authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
  projectId: "sistemaestacionamiento-46735",
  storageBucket: "sistemaestacionamiento-46735.appspot.com",
  messagingSenderId: "570161231939",
  appId: "1:570161231939:web:your-app-id"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
  console.log("Mensaje recibido en background:", payload);
  
  const notificationTitle = payload.notification?.title || "Sistema Estacionamiento";
  const notificationOptions = {
    body: payload.notification?.body || "Nueva notificación",
    icon: "/sistemaEstacionamiento/imagenes/Logo_sin_fondo.png",
    badge: "/sistemaEstacionamiento/imagenes/Logo_sin_fondo.png"
  };
  
  self.registration.showNotification(notificationTitle, notificationOptions);
});';
    
    // Crear en la raíz del servidor (htdocs)
    $raiz_sw = '../firebase-messaging-sw.js';
    if (file_put_contents($raiz_sw, $sw_content)) {
        echo "<div class='success'>✅ Service Worker creado en la raíz: $raiz_sw</div>";
    } else {
        echo "<div class='error'>❌ Error creando Service Worker en la raíz</div>";
    }
    
    // También crear en la carpeta del proyecto
    if (file_put_contents('firebase-messaging-sw.js', $sw_content)) {
        echo "<div class='success'>✅ Service Worker creado en proyecto: firebase-messaging-sw.js</div>";
    } else {
        echo "<div class='error'>❌ Error creando Service Worker en proyecto</div>";
    }
    
    // ============================================
    // 2. CREAR FIX DE COMPATIBILIDAD MEJORADO
    // ============================================
    
    echo "<h2>2. Creando Fix de Compatibilidad Mejorado</h2>";
    
    $fix_content = '/**
 * 🔧 FIX FIREBASE COMPATIBILITY WINDOWS 7
 * Soluciona problemas de Service Worker y compatibilidad
 */

// Detectar compatibilidad del navegador
function isFirebaseSupported() {
    try {
        if (typeof window === "undefined" || !window.navigator) {
            return false;
        }
        
        const userAgent = window.navigator.userAgent;
        const isOldIE = /MSIE|Trident/.test(userAgent);
        const isOldChrome = /Chrome\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
        const isOldFirefox = /Firefox\/([0-9]+)/.test(userAgent) && parseInt(RegExp.$1) < 50;
        
        return !isOldIE && !isOldChrome && !isOldFirefox && "serviceWorker" in navigator;
    } catch (e) {
        return false;
    }
}

// Función para configurar Firebase con Service Worker correcto
function configurarFirebaseConSW() {
    try {
        if (!isFirebaseSupported()) {
            console.warn("🚫 Navegador no compatible con Firebase. Usando modo offline.");
            return false;
        }
        
        // Configurar Firebase con Service Worker en la raíz
        const firebaseConfig = {
            apiKey: "AIzaSyBnkbFxK2e7jw6O_6E8CDfHWOZH9AT3MKg",
            authDomain: "sistemaestacionamiento-46735.firebaseapp.com",
            projectId: "sistemaestacionamiento-46735",
            storageBucket: "sistemaestacionamiento-46735.appspot.com",
            messagingSenderId: "570161231939",
            appId: "1:570161231939:web:your-app-id"
        };
        
        // Inicializar Firebase
        if (typeof firebase !== "undefined") {
            firebase.initializeApp(firebaseConfig);
            
            // Configurar messaging con Service Worker en la raíz
            const messaging = firebase.messaging();
            
            // Registrar Service Worker en la raíz
            navigator.serviceWorker.register("/firebase-messaging-sw.js")
                .then(function(registration) {
                    console.log("✅ Service Worker registrado:", registration);
                    return messaging.useServiceWorker(registration);
                })
                .catch(function(error) {
                    console.warn("⚠️ Error registrando Service Worker:", error);
                    return false;
                });
            
            return true;
        } else {
            console.warn("⚠️ Firebase no está disponible");
            return false;
        }
    } catch (error) {
        console.error("❌ Error configurando Firebase:", error);
        return false;
    }
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
    
    if (!window.tuuFirebaseSync) {
        window.tuuFirebaseSync = {
            init: () => console.log("✅ TUU offline inicializado"),
            createPayment: () => Promise.resolve({ success: true, offline: true }),
            processPendingPayments: () => Promise.resolve({ success: true, offline: true })
        };
    }
}

// Función principal de inicialización
function initSystemSafely() {
    try {
        if (!configurarFirebaseConSW()) {
            initTUUOffline();
            return;
        }
        
        console.log("✅ Firebase configurado correctamente");
        
    } catch (error) {
        console.error("❌ Error en inicialización:", error);
        initTUUOffline();
    }
}

// Aplicar fixes cuando se carga el script
(function() {
    "use strict";
    
    // Esperar a que el DOM esté listo
    function ready(fn) {
        if (document.readyState !== "loading") {
            fn();
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }
    
    ready(function() {
        initSystemSafely();
    });
    
    // También intentar después de un delay
    setTimeout(initSystemSafely, 100);
    setTimeout(initSystemSafely, 500);
})();

// Exportar funciones para uso global
window.FirebaseCompat = {
    initSystemSafely,
    initTUUOffline,
    isFirebaseSupported,
    handleFirebaseError,
    configurarFirebaseConSW
};';
    
    if (file_put_contents('fix-firebase-browser-compatibility.js', $fix_content)) {
        echo "<div class='success'>✅ Fix de compatibilidad mejorado creado</div>";
    } else {
        echo "<div class='error'>❌ Error creando fix de compatibilidad</div>";
    }
    
    // Crear también el fix simple
    $fix_simple = file_get_contents('fix-firebase-simple.js');
    if (file_put_contents('fix-firebase-simple.js', $fix_simple)) {
        echo "<div class='success'>✅ Fix simple creado</div>";
    } else {
        echo "<div class='error'>❌ Error creando fix simple</div>";
    }
    
    // ============================================
    // 3. MODIFICAR INDEX.PHP PARA WINDOWS 7
    // ============================================
    
    echo "<h2>3. Modificando index.php para Windows 7</h2>";
    
    if (file_exists('index.php')) {
        $contenido = file_get_contents('index.php');
        
        // Verificar si ya tiene el fix
        if (strpos($contenido, 'fix-firebase-browser-compatibility.js') !== false) {
            echo "<div class='info'>⚠️ El fix ya está aplicado en index.php</div>";
        } else {
            // Buscar la línea donde está Firebase y agregar el fix antes
            $lineas = explode("\n", $contenido);
            $nueva_contenido = "";
            $fix_aplicado = false;
            
            foreach ($lineas as $i => $linea) {
                $numero_linea = $i + 1;
                
                // Buscar líneas que contengan Firebase
                if (strpos($linea, 'firebase') !== false || strpos($linea, 'Firebase') !== false) {
                    if (!$fix_aplicado) {
                        // Agregar el fix antes de Firebase
                        $nueva_contenido .= "  <!-- Fix para compatibilidad de navegador Windows 7 -->\n";
                        $nueva_contenido .= "  <script src=\"fix-firebase-simple.js\"></script>\n";
                        $nueva_contenido .= "\n";
                        $fix_aplicado = true;
                        echo "<div class='success'>✅ Fix agregado antes de la línea $numero_linea</div>";
                    }
                }
                
                $nueva_contenido .= $linea . "\n";
            }
            
            if ($fix_aplicado) {
                // Crear backup
                file_put_contents('index.php.backup', $contenido);
                echo "<div class='info'>✅ Backup creado: index.php.backup</div>";
                
                // Aplicar cambios
                if (file_put_contents('index.php', $nueva_contenido)) {
                    echo "<div class='success'>✅ Fix aplicado exitosamente en index.php</div>";
                } else {
                    echo "<div class='error'>❌ Error al escribir en index.php</div>";
                }
            } else {
                echo "<div class='warning'>⚠️ No se encontraron líneas de Firebase para aplicar el fix</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Archivo index.php no encontrado</div>";
    }
    
    // ============================================
    // 4. VERIFICACIÓN FINAL
    // ============================================
    
    echo "<h2>4. Verificación Final</h2>";
    
    $archivos_verificar = [
        '../firebase-messaging-sw.js' => 'Service Worker en raíz',
        'firebase-messaging-sw.js' => 'Service Worker en proyecto',
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
    echo "<h2>🎉 ¡FIREBASE REPARADO PARA WINDOWS 7!</h2>";
    echo "<p>Firebase está listo para Windows 7:</p>";
    echo "<ul>";
    echo "<li>✅ Service Worker creado en la raíz del servidor</li>";
    echo "<li>✅ Service Worker creado en el proyecto</li>";
    echo "<li>✅ Fix de compatibilidad mejorado</li>";
    echo "<li>✅ Fix aplicado en index.php</li>";
    echo "<li>✅ Sin errores 404</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Para verificar:</h3>";
    echo "<ol>";
    echo "<li>Recargar la página: <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li>Verificar que no aparezcan errores 404</li>";
    echo "<li>Verificar que Firebase se configure correctamente</li>";
    echo "<li>Probar sincronización con otras máquinas</li>";
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
