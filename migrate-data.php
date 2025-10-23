<?php
/**
 * Migrar datos de tablas originales a nuevas tablas
 * Sistema de Estacionamiento Los Ríos
 */

require_once 'conexion.php';

echo "<h1>🔄 Migración de Datos</h1>";

try {
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
    // Migrar datos de 'ingresos' a 'tickets'
    echo "<h2>🎫 Migrando datos de 'ingresos' a 'tickets'</h2>";
    
    // Primero verificar cuántos registros hay en cada tabla
    $result = $conn->query("SELECT COUNT(*) as count FROM ingresos");
    $ingresosCount = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM tickets");
    $ticketsCount = $result->fetch_assoc()['count'];
    
    echo "<p>Registros en 'ingresos': $ingresosCount</p>";
    echo "<p>Registros en 'tickets': $ticketsCount</p>";
    
    if ($ingresosCount > $ticketsCount) {
        echo "<p>Migrando datos de 'ingresos' a 'tickets'...</p>";
        
        // Obtener estructura de ingresos
        $result = $conn->query("DESCRIBE ingresos");
        $ingresosFields = [];
        while ($row = $result->fetch_assoc()) {
            $ingresosFields[] = $row['Field'];
        }
        
        echo "<p>Campos en 'ingresos': " . implode(', ', $ingresosFields) . "</p>";
        
        // Migrar en lotes de 1000
        $batchSize = 1000;
        $offset = $ticketsCount; // Empezar desde donde terminamos
        $migrated = 0;
        
        while ($offset < $ingresosCount) {
            $sql = "INSERT INTO tickets (patente, fecha_ingreso, tipo_servicio, precio, pagado, cliente_nombre, cliente_telefono, observaciones)
                    SELECT 
                        patente,
                        fecha_ingreso,
                        COALESCE(tipo_servicio, 'estacionamiento') as tipo_servicio,
                        COALESCE(precio, 0) as precio,
                        COALESCE(pagado, 0) as pagado,
                        COALESCE(cliente_nombre, '') as cliente_nombre,
                        COALESCE(cliente_telefono, '') as cliente_telefono,
                        COALESCE(observaciones, '') as observaciones
                    FROM ingresos 
                    LIMIT $batchSize OFFSET $offset";
            
            if ($conn->query($sql)) {
                $migrated += $conn->affected_rows;
                echo "<p>✅ Migrados $migrated registros...</p>";
                $offset += $batchSize;
            } else {
                echo "<p style='color: red;'>❌ Error en migración: " . $conn->error . "</p>";
                break;
            }
        }
        
        echo "<p style='color: green;'>✅ Migración de 'ingresos' completada: $migrated registros</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ No hay datos nuevos para migrar de 'ingresos'</p>";
    }
    
    // Migrar datos de 'lavados_pendientes' a 'servicios_lavado'
    echo "<h2>🚿 Migrando datos de 'lavados_pendientes' a 'servicios_lavado'</h2>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM lavados_pendientes");
    $lavadosCount = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM servicios_lavado");
    $serviciosCount = $result->fetch_assoc()['count'];
    
    echo "<p>Registros en 'lavados_pendientes': $lavadosCount</p>";
    echo "<p>Registros en 'servicios_lavado': $serviciosCount</p>";
    
    if ($lavadosCount > $serviciosCount) {
        echo "<p>Migrando datos de 'lavados_pendientes' a 'servicios_lavado'...</p>";
        
        // Obtener estructura de lavados_pendientes
        $result = $conn->query("DESCRIBE lavados_pendientes");
        $lavadosFields = [];
        while ($row = $result->fetch_assoc()) {
            $lavadosFields[] = $row['Field'];
        }
        
        echo "<p>Campos en 'lavados_pendientes': " . implode(', ', $lavadosFields) . "</p>";
        
        // Migrar en lotes de 100
        $batchSize = 100;
        $offset = $serviciosCount;
        $migrated = 0;
        
        while ($offset < $lavadosCount) {
            $sql = "INSERT INTO servicios_lavado (patente, fecha_servicio, tipo_lavado, precio_base, precio_extra, motivos_extra, cliente_nombre, cliente_telefono, observaciones, completado)
                    SELECT 
                        patente,
                        fecha_servicio,
                        COALESCE(tipo_lavado, 'básico') as tipo_lavado,
                        COALESCE(precio_base, 0) as precio_base,
                        COALESCE(precio_extra, 0) as precio_extra,
                        COALESCE(motivos_extra, '') as motivos_extra,
                        COALESCE(cliente_nombre, '') as cliente_nombre,
                        COALESCE(cliente_telefono, '') as cliente_telefono,
                        COALESCE(observaciones, '') as observaciones,
                        COALESCE(completado, 0) as completado
                    FROM lavados_pendientes 
                    LIMIT $batchSize OFFSET $offset";
            
            if ($conn->query($sql)) {
                $migrated += $conn->affected_rows;
                echo "<p>✅ Migrados $migrated registros...</p>";
                $offset += $batchSize;
            } else {
                echo "<p style='color: red;'>❌ Error en migración: " . $conn->error . "</p>";
                break;
            }
        }
        
        echo "<p style='color: green;'>✅ Migración de 'lavados_pendientes' completada: $migrated registros</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ No hay datos nuevos para migrar de 'lavados_pendientes'</p>";
    }
    
    // Verificar resultado final
    echo "<h2>📊 Resultado Final</h2>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM tickets");
    $ticketsFinal = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM servicios_lavado");
    $serviciosFinal = $result->fetch_assoc()['count'];
    
    echo "<p><strong>Tickets:</strong> $ticketsFinal registros</p>";
    echo "<p><strong>Servicios de Lavado:</strong> $serviciosFinal registros</p>";
    
    echo "<h2>✅ Migración Completada</h2>";
    echo "<p>Ahora puedes probar las APIs nuevamente:</p>";
    echo "<p><a href='test-apis.php'>🧪 Probar APIs</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
