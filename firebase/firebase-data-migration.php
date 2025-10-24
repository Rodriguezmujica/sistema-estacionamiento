<?php
/**
 * 🔄 MIGRACIÓN DE DATOS ESPECÍFICOS DEL SISTEMA
 * Sistema de Estacionamiento Los Ríos
 * 
 * Migración específica para el sistema de estacionamiento
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/firestore-service.php';

class EstacionamientoDataMigration {
    private $conn;
    private $firestore;
    private $migrationLog = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->firestore = getFirestoreService();
    }
    
    /**
     * Migrar usuarios del sistema
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
        $errors = 0;
        
        while ($row = $result->fetch_assoc()) {
            $userData = [
                'id' => (int)$row['id'],
                'usuario' => $row['usuario'],
                'password_hash' => $row['password_hash'],
                'rol' => $row['rol'],
                'fecha_creacion' => new DateTime(),
                'activo' => true,
                'migrado_desde_mysql' => true
            ];
            
            $documentId = 'usuario_' . $row['id'];
            $result_firestore = $this->firestore->createDocument('usuarios', $documentId, $userData);
            
            if ($result_firestore['success']) {
                $migrated++;
                $this->log("Usuario migrado: " . $row['usuario']);
            } else {
                $errors++;
                $this->log("Error migrando usuario " . $row['usuario'] . ": " . json_encode($result_firestore), 'ERROR');
            }
        }
        
        $this->log("Migración de usuarios completada: $migrated usuarios migrados, $errors errores");
        return $migrated > 0;
    }
    
    /**
     * Migrar tickets de estacionamiento
     */
    public function migrateTickets() {
        $this->log("Iniciando migración de tickets...");
        
        $sql = "SELECT * FROM tickets ORDER BY fecha_ingreso DESC";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("Error al obtener tickets: " . $this->conn->error, 'ERROR');
            return false;
        }
        
        $migrated = 0;
        $errors = 0;
        
        while ($row = $result->fetch_assoc()) {
            $ticketData = [
                'id' => (int)$row['id'],
                'patente' => $row['patente'],
                'tipo_servicio' => $row['tipo_servicio'],
                'fecha_ingreso' => new DateTime($row['fecha_ingreso']),
                'fecha_salida' => $row['fecha_salida'] ? new DateTime($row['fecha_salida']) : null,
                'precio' => (float)$row['precio'],
                'pagado' => (bool)$row['pagado'],
                'usuario_id' => (int)$row['usuario_id'],
                'cliente_nombre' => $row['cliente_nombre'] ?? '',
                'migrado_desde_mysql' => true
            ];
            
            $documentId = 'ticket_' . $row['id'];
            $result_firestore = $this->firestore->createDocument('tickets', $documentId, $ticketData);
            
            if ($result_firestore['success']) {
                $migrated++;
                $this->log("Ticket migrado: " . $row['patente'] . " (ID: " . $row['id'] . ")");
            } else {
                $errors++;
                $this->log("Error migrando ticket " . $row['patente'] . ": " . json_encode($result_firestore), 'ERROR');
            }
        }
        
        $this->log("Migración de tickets completada: $migrated tickets migrados, $errors errores");
        return $migrated > 0;
    }
    
    /**
     * Migrar servicios de lavado
     */
    public function migrateServiciosLavado() {
        $this->log("Iniciando migración de servicios de lavado...");
        
        $sql = "SELECT * FROM servicios_lavado ORDER BY fecha_servicio DESC";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("Error al obtener servicios de lavado: " . $this->conn->error, 'ERROR');
            return false;
        }
        
        $migrated = 0;
        $errors = 0;
        
        while ($row = $result->fetch_assoc()) {
            $servicioData = [
                'id' => (int)$row['id'],
                'patente' => $row['patente'],
                'tipo_lavado' => $row['tipo_lavado'],
                'precio_base' => (float)$row['precio_base'],
                'precio_extra' => (float)$row['precio_extra'],
                'motivos_extra' => $row['motivos_extra'] ? explode(',', $row['motivos_extra']) : [],
                'fecha_servicio' => new DateTime($row['fecha_servicio']),
                'usuario_id' => (int)$row['usuario_id'],
                'cliente_nombre' => $row['cliente_nombre'] ?? '',
                'migrado_desde_mysql' => true
            ];
            
            $documentId = 'servicio_' . $row['id'];
            $result_firestore = $this->firestore->createDocument('servicios_lavado', $documentId, $servicioData);
            
            if ($result_firestore['success']) {
                $migrated++;
                $this->log("Servicio de lavado migrado: " . $row['patente'] . " (ID: " . $row['id'] . ")");
            } else {
                $errors++;
                $this->log("Error migrando servicio " . $row['patente'] . ": " . json_encode($result_firestore), 'ERROR');
            }
        }
        
        $this->log("Migración de servicios de lavado completada: $migrated servicios migrados, $errors errores");
        return $migrated > 0;
    }
    
    /**
     * Migrar configuración del sistema
     */
    public function migrateConfiguracion() {
        $this->log("Iniciando migración de configuración...");
        
        $configData = [
            'precio_minuto' => 35,
            'servicios_lavado' => [
                'básico' => 5000,
                'premium' => 8000,
                'completo' => 12000
            ],
            'configuracion_tuu' => [
                'activa' => true,
                'maquina_principal' => 'TUU-001'
            ],
            'fecha_migracion' => new DateTime(),
            'version_sistema' => '2.0-firebase'
        ];
        
        $result = $this->firestore->createDocument('configuracion', 'sistema', $configData);
        
        if ($result['success']) {
            $this->log("Configuración migrada exitosamente");
            return true;
        } else {
            $this->log("Error migrando configuración: " . json_encode($result), 'ERROR');
            return false;
        }
    }
    
    /**
     * Verificar integridad de datos migrados
     */
    public function verificarIntegridad() {
        $this->log("Verificando integridad de datos migrados...");
        
        // Verificar usuarios
        $usuarios_mysql = $this->conn->query("SELECT COUNT(*) as count FROM usuarios")->fetch_assoc()['count'];
        $usuarios_firestore = $this->firestore->listDocuments('usuarios', 1000);
        $usuarios_firestore_count = count($usuarios_firestore['data']['documents'] ?? []);
        
        $this->log("Usuarios MySQL: $usuarios_mysql, Firestore: $usuarios_firestore_count");
        
        // Verificar tickets
        $tickets_mysql = $this->conn->query("SELECT COUNT(*) as count FROM tickets")->fetch_assoc()['count'];
        $tickets_firestore = $this->firestore->listDocuments('tickets', 1000);
        $tickets_firestore_count = count($tickets_firestore['data']['documents'] ?? []);
        
        $this->log("Tickets MySQL: $tickets_mysql, Firestore: $tickets_firestore_count");
        
        // Verificar servicios de lavado
        $servicios_mysql = $this->conn->query("SELECT COUNT(*) as count FROM servicios_lavado")->fetch_assoc()['count'];
        $servicios_firestore = $this->firestore->listDocuments('servicios_lavado', 1000);
        $servicios_firestore_count = count($servicios_firestore['data']['documents'] ?? []);
        
        $this->log("Servicios de lavado MySQL: $servicios_mysql, Firestore: $servicios_firestore_count");
        
        $integridad_ok = ($usuarios_mysql == $usuarios_firestore_count) && 
                        ($tickets_mysql == $tickets_firestore_count) && 
                        ($servicios_mysql == $servicios_firestore_count);
        
        if ($integridad_ok) {
            $this->log("✅ Integridad de datos verificada correctamente");
        } else {
            $this->log("❌ Problemas de integridad detectados", 'ERROR');
        }
        
        return $integridad_ok;
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
        $this->migrateConfiguracion();
        
        // Verificar integridad
        $this->verificarIntegridad();
        
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
        $report .= "**Sistema:** Estacionamiento Los Ríos\n";
        $report .= "**Fecha:** " . date('Y-m-d H:i:s') . "\n";
        $report .= "**Duración:** " . $this->getMigrationDuration() . " segundos\n\n";
        
        $report .= "## Resumen de Migración\n\n";
        
        // Contar por tipo de log
        $success_count = 0;
        $error_count = 0;
        
        foreach ($this->migrationLog as $log) {
            if (isset($log['level']) && $log['level'] === 'ERROR') {
                $error_count++;
            } else {
                $success_count++;
            }
        }
        
        $report .= "- ✅ Operaciones exitosas: $success_count\n";
        $report .= "- ❌ Errores: $error_count\n";
        $report .= "- 📊 Total de operaciones: " . count($this->migrationLog) . "\n\n";
        
        $report .= "## Log Detallado\n\n";
        
        foreach ($this->migrationLog as $log) {
            $level = isset($log['level']) ? $log['level'] : 'INFO';
            $message = $log['message'];
            $timestamp = $log['timestamp'];
            
            $report .= "**[{$timestamp}]** `{$level}` {$message}\n";
        }
        
        $filename = 'migration-report-estacionamiento-' . date('Y-m-d-H-i-s') . '.md';
        file_put_contents($filename, $report);
        $this->log("Reporte de migración generado: $filename");
        
        return $filename;
    }
    
    /**
     * Obtener duración de migración
     */
    private function getMigrationDuration() {
        if (empty($this->migrationLog)) {
            return 0;
        }
        
        $start = strtotime($this->migrationLog[0]['timestamp']);
        $end = strtotime(end($this->migrationLog)['timestamp']);
        
        return $end - $start;
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
    
    $migration = new EstacionamientoDataMigration($conn);
    $migration->runFullMigration();
    $migration->generateMigrationReport();
}
?>
