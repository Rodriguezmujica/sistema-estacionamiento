<?php
/**
 * 🔄 MIGRACIÓN DE MYSQL A FIRESTORE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo ayuda a migrar datos existentes de MySQL a Firestore
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/firebase-config.php';

class FirebaseMigration {
    private $conn;
    private $migrationLog = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Migrar usuarios de MySQL a Firestore
     */
    public function migrateUsuarios() {
        $this->log("Iniciando migración de usuarios...");
        
        $sql = "SELECT id, usuario, password_hash, rol FROM usuarios";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("Error al obtener usuarios: " . $this->conn->error, 'ERROR');
            return false;
        }
        
        $migrated = 0;
        while ($row = $result->fetch_assoc()) {
            $userData = [
                'fields' => [
                    'id' => ['integerValue' => (int)$row['id']],
                    'usuario' => ['stringValue' => $row['usuario']],
                    'password_hash' => ['stringValue' => $row['password_hash']],
                    'rol' => ['stringValue' => $row['rol']],
                    'fecha_creacion' => ['timestampValue' => date('c')],
                    'activo' => ['booleanValue' => true]
                ]
            ];
            
            $response = createFirestoreDocument('usuarios', $userData);
            
            if ($response['status'] === 200) {
                $migrated++;
                $this->log("Usuario migrado: " . $row['usuario']);
            } else {
                $this->log("Error migrando usuario " . $row['usuario'] . ": " . json_encode($response), 'ERROR');
            }
        }
        
        $this->log("Migración de usuarios completada: $migrated usuarios migrados");
        return true;
    }
    
    /**
     * Migrar tickets de estacionamiento
     */
    public function migrateTickets() {
        $this->log("Iniciando migración de tickets...");
        
        $sql = "SELECT * FROM tickets WHERE fecha_salida IS NULL";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("Error al obtener tickets: " . $this->conn->error, 'ERROR');
            return false;
        }
        
        $migrated = 0;
        while ($row = $result->fetch_assoc()) {
            $ticketData = [
                'fields' => [
                    'id' => ['integerValue' => (int)$row['id']],
                    'patente' => ['stringValue' => $row['patente']],
                    'tipo_servicio' => ['stringValue' => $row['tipo_servicio']],
                    'fecha_ingreso' => ['timestampValue' => $row['fecha_ingreso']],
                    'fecha_salida' => ['nullValue' => null],
                    'precio' => ['doubleValue' => (float)$row['precio']],
                    'pagado' => ['booleanValue' => (bool)$row['pagado']],
                    'usuario_id' => ['integerValue' => (int)$row['usuario_id']],
                    'cliente_nombre' => ['stringValue' => $row['cliente_nombre'] ?? '']
                ]
            ];
            
            $response = createFirestoreDocument('tickets', $ticketData);
            
            if ($response['status'] === 200) {
                $migrated++;
                $this->log("Ticket migrado: " . $row['patente']);
            } else {
                $this->log("Error migrando ticket " . $row['patente'] . ": " . json_encode($response), 'ERROR');
            }
        }
        
        $this->log("Migración de tickets completada: $migrated tickets migrados");
        return true;
    }
    
    /**
     * Migrar servicios de lavado
     */
    public function migrateServiciosLavado() {
        $this->log("Iniciando migración de servicios de lavado...");
        
        $sql = "SELECT * FROM servicios_lavado";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("Error al obtener servicios de lavado: " . $this->conn->error, 'ERROR');
            return false;
        }
        
        $migrated = 0;
        while ($row = $result->fetch_assoc()) {
            $servicioData = [
                'fields' => [
                    'id' => ['integerValue' => (int)$row['id']],
                    'patente' => ['stringValue' => $row['patente']],
                    'tipo_lavado' => ['stringValue' => $row['tipo_lavado']],
                    'precio_base' => ['doubleValue' => (float)$row['precio_base']],
                    'precio_extra' => ['doubleValue' => (float)$row['precio_extra']],
                    'motivos_extra' => ['arrayValue' => [
                        'values' => array_map(function($motivo) {
                            return ['stringValue' => $motivo];
                        }, explode(',', $row['motivos_extra'] ?? ''))
                    ]],
                    'fecha_servicio' => ['timestampValue' => $row['fecha_servicio']],
                    'usuario_id' => ['integerValue' => (int)$row['usuario_id']],
                    'cliente_nombre' => ['stringValue' => $row['cliente_nombre'] ?? '']
                ]
            ];
            
            $response = createFirestoreDocument('servicios_lavado', $servicioData);
            
            if ($response['status'] === 200) {
                $migrated++;
                $this->log("Servicio de lavado migrado: " . $row['patente']);
            } else {
                $this->log("Error migrando servicio " . $row['patente'] . ": " . json_encode($response), 'ERROR');
            }
        }
        
        $this->log("Migración de servicios de lavado completada: $migrated servicios migrados");
        return true;
    }
    
    /**
     * Ejecutar migración completa
     */
    public function runFullMigration() {
        $this->log("=== INICIANDO MIGRACIÓN COMPLETA A FIREBASE ===");
        
        $startTime = microtime(true);
        
        // Migrar en orden de dependencias
        $this->migrateUsuarios();
        $this->migrateTickets();
        $this->migrateServiciosLavado();
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->log("=== MIGRACIÓN COMPLETADA EN {$duration} SEGUNDOS ===");
        
        return $this->migrationLog;
    }
    
    /**
     * Generar reporte de migración
     */
    public function generateMigrationReport() {
        $report = "# Reporte de Migración a Firebase\n\n";
        $report .= "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->migrationLog as $log) {
            $level = isset($log['level']) ? $log['level'] : 'INFO';
            $message = $log['message'];
            $timestamp = $log['timestamp'];
            
            $report .= "[$timestamp] [$level] $message\n";
        }
        
        file_put_contents('migration-report-' . date('Y-m-d-H-i-s') . '.txt', $report);
        $this->log("Reporte de migración generado: migration-report-" . date('Y-m-d-H-i-s') . ".txt");
    }
    
    /**
     * Log de migración
     */
    private function log($message, $level = 'INFO') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message
        ];
        
        $this->migrationLog[] = $logEntry;
        echo "[$level] $message\n";
    }
}

// Ejecutar migración si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if ($conn->connect_error) {
        die("Error de conexión a MySQL: " . $conn->connect_error);
    }
    
    $migration = new FirebaseMigration($conn);
    $migration->runFullMigration();
    $migration->generateMigrationReport();
}
?>
