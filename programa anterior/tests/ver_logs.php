<?php
/**
 * 📊 VISOR DE LOGS EN TIEMPO REAL
 * 
 * Muestra todos los logs del sistema con filtros y auto-actualización
 */

require_once 'debug_logger.php';

// Si es una petición AJAX, devolver solo los logs
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $filtro = $_GET['filtro'] ?? 'all';
    $lineas = (int)($_GET['lineas'] ?? 100);
    
    $logs = DebugLogger::getRecentLogs($lineas);
    
    // Aplicar filtro
    if ($filtro != 'all') {
        $logs = array_filter($logs, function($log) use ($filtro) {
            return stripos($log, "[" . strtoupper($filtro) . "]") !== false;
        });
    }
    
    echo json_encode([
        'success' => true,
        'logs' => array_values($logs),
        'total' => count($logs),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Limpiar logs si se solicita
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    DebugLogger::clearLogs();
    header('Location: ver_logs.php?msg=Logs limpiados');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Visor de Logs - Sistema Estacionamiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #1a1a1a;
            color: #f0f0f0;
            font-family: 'Consolas', 'Courier New', monospace;
        }
        .log-container {
            background: #0d0d0d;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1rem;
            height: calc(100vh - 250px);
            overflow-y: auto;
            font-size: 0.9rem;
        }
        .log-line {
            padding: 0.5rem;
            border-left: 3px solid #333;
            margin-bottom: 0.5rem;
            background: #1a1a1a;
            border-radius: 4px;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .log-INFO { border-left-color: #17a2b8; }
        .log-WARNING { border-left-color: #ffc107; }
        .log-ERROR { border-left-color: #dc3545; }
        .log-DEBUG { border-left-color: #6c757d; }
        .log-SQL { border-left-color: #28a745; }
        .log-API { border-left-color: #007bff; }
        .log-PRINT { border-left-color: #e83e8c; }
        
        .timestamp { color: #6c757d; }
        .log-type { 
            font-weight: bold; 
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        .log-type-INFO { background: #17a2b8; color: white; }
        .log-type-WARNING { background: #ffc107; color: black; }
        .log-type-ERROR { background: #dc3545; color: white; }
        .log-type-DEBUG { background: #6c757d; color: white; }
        .log-type-SQL { background: #28a745; color: white; }
        .log-type-API { background: #007bff; color: white; }
        .log-type-PRINT { background: #e83e8c; color: white; }
        
        .stats-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .badge-custom {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .btn-toolbar {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .auto-refresh-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .no-logs {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-3">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-terminal"></i> Visor de Logs en Tiempo Real</h2>
            <div>
                <a href="debug_panel.php" class="btn btn-outline-info btn-sm me-2">
                    <i class="fas fa-bug"></i> Panel Debug
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="stats-card">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="mb-2">Total</div>
                            <span class="badge bg-secondary badge-custom" id="stat-total">0</span>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">Info</div>
                            <span class="badge bg-info badge-custom" id="stat-info">0</span>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">Warnings</div>
                            <span class="badge bg-warning text-dark badge-custom" id="stat-warning">0</span>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">Errores</div>
                            <span class="badge bg-danger badge-custom" id="stat-error">0</span>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">SQL</div>
                            <span class="badge bg-success badge-custom" id="stat-sql">0</span>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">APIs</div>
                            <span class="badge bg-primary badge-custom" id="stat-api">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de herramientas -->
        <div class="btn-toolbar">
            <div class="row w-100 align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-1">Filtrar por tipo:</label>
                    <select class="form-select form-select-sm" id="filtro-tipo">
                        <option value="all">Todos</option>
                        <option value="info">Info</option>
                        <option value="warning">Warnings</option>
                        <option value="error">Errores</option>
                        <option value="sql">SQL</option>
                        <option value="api">APIs</option>
                        <option value="debug">Debug</option>
                        <option value="print">Impresión</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Mostrar últimas:</label>
                    <select class="form-select form-select-sm" id="num-lineas">
                        <option value="50">50 líneas</option>
                        <option value="100" selected>100 líneas</option>
                        <option value="200">200 líneas</option>
                        <option value="500">500 líneas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Auto-refresh:</label>
                    <button class="btn btn-success btn-sm w-100" id="btn-auto-refresh" onclick="toggleAutoRefresh()">
                        <span class="auto-refresh-dot" id="refresh-dot" style="display:none;"></span>
                        <span id="refresh-text">OFF</span>
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">&nbsp;</label>
                    <button class="btn btn-info btn-sm w-100" onclick="cargarLogs()">
                        <i class="fas fa-sync-alt"></i> Refrescar Ahora
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">&nbsp;</label>
                    <button class="btn btn-warning btn-sm w-100" onclick="limpiarPantalla()">
                        <i class="fas fa-eraser"></i> Limpiar Pantalla
                    </button>
                </div>
                <div class="col-md-1">
                    <label class="form-label mb-1">&nbsp;</label>
                    <button class="btn btn-danger btn-sm w-100" onclick="eliminarLogs()" title="Eliminar archivo de logs">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Última actualización -->
        <div class="text-center mb-2">
            <small class="text-muted">Última actualización: <span id="ultima-actualizacion">-</span></small>
        </div>

        <!-- Contenedor de logs -->
        <div class="log-container" id="log-container">
            <div class="no-logs">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Cargando logs...</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let autoRefreshInterval = null;
        let autoScrollEnabled = true;

        // Cargar logs desde el servidor
        function cargarLogs() {
            const filtro = document.getElementById('filtro-tipo').value;
            const lineas = document.getElementById('num-lineas').value;
            
            fetch(`ver_logs.php?ajax=1&filtro=${filtro}&lineas=${lineas}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarLogs(data.logs);
                        actualizarEstadisticas(data.logs);
                        document.getElementById('ultima-actualizacion').textContent = data.timestamp;
                    }
                })
                .catch(error => {
                    console.error('Error al cargar logs:', error);
                    document.getElementById('log-container').innerHTML = 
                        '<div class="no-logs"><i class="fas fa-exclamation-triangle fa-3x mb-3 text-danger"></i><p>Error al cargar logs</p></div>';
                });
        }

        // Mostrar logs en el contenedor
        function mostrarLogs(logs) {
            const container = document.getElementById('log-container');
            
            if (logs.length === 0) {
                container.innerHTML = '<div class="no-logs"><i class="fas fa-inbox fa-3x mb-3"></i><p>No hay logs para mostrar</p></div>';
                return;
            }
            
            let html = '';
            logs.forEach(log => {
                const tipo = extraerTipo(log);
                const estiloClase = tipo ? `log-${tipo}` : 'log-INFO';
                
                html += `<div class="log-line ${estiloClase}">${formatearLog(log)}</div>`;
            });
            
            container.innerHTML = html;
            
            // Auto-scroll al final si está habilitado
            if (autoScrollEnabled) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Extraer tipo de log
        function extraerTipo(log) {
            const match = log.match(/\[(INFO|WARNING|ERROR|DEBUG|SQL|API|PRINT)\]/);
            return match ? match[1] : null;
        }

        // Formatear log con colores
        function formatearLog(log) {
            // Extraer partes del log
            const regex = /\[(.*?)\] \[(.*?)\] \[(.*?)\] \[(.*?)\] (.*)/;
            const match = log.match(regex);
            
            if (!match) {
                return escapeHtml(log);
            }
            
            const [, timestamp, tipo, ip, usuario, mensaje] = match;
            
            return `
                <span class="timestamp">[${timestamp}]</span>
                <span class="log-type log-type-${tipo}">${tipo}</span>
                <span class="text-info">[${ip}]</span>
                <span class="text-warning">[${usuario}]</span>
                <span>${escapeHtml(mensaje)}</span>
            `;
        }

        // Escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Actualizar estadísticas
        function actualizarEstadisticas(logs) {
            const stats = {
                total: logs.length,
                info: 0,
                warning: 0,
                error: 0,
                sql: 0,
                api: 0
            };
            
            logs.forEach(log => {
                const tipo = extraerTipo(log);
                if (tipo) {
                    stats[tipo.toLowerCase()] = (stats[tipo.toLowerCase()] || 0) + 1;
                }
            });
            
            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-info').textContent = stats.info;
            document.getElementById('stat-warning').textContent = stats.warning;
            document.getElementById('stat-error').textContent = stats.error;
            document.getElementById('stat-sql').textContent = stats.sql;
            document.getElementById('stat-api').textContent = stats.api;
        }

        // Toggle auto-refresh
        function toggleAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                document.getElementById('refresh-text').textContent = 'OFF';
                document.getElementById('refresh-dot').style.display = 'none';
                document.getElementById('btn-auto-refresh').classList.remove('btn-success');
                document.getElementById('btn-auto-refresh').classList.add('btn-secondary');
            } else {
                autoRefreshInterval = setInterval(cargarLogs, 3000);
                document.getElementById('refresh-text').textContent = 'ON (3s)';
                document.getElementById('refresh-dot').style.display = 'inline-block';
                document.getElementById('btn-auto-refresh').classList.remove('btn-secondary');
                document.getElementById('btn-auto-refresh').classList.add('btn-success');
                cargarLogs(); // Cargar inmediatamente
            }
        }

        // Limpiar pantalla
        function limpiarPantalla() {
            document.getElementById('log-container').innerHTML = 
                '<div class="no-logs"><i class="fas fa-broom fa-3x mb-3"></i><p>Pantalla limpiada</p></div>';
        }

        // Eliminar logs del servidor
        function eliminarLogs() {
            if (confirm('¿Estás seguro de eliminar TODOS los logs del servidor?\n\nEsta acción no se puede deshacer.')) {
                window.location.href = 'ver_logs.php?clear=1';
            }
        }

        // Event listeners para filtros
        document.getElementById('filtro-tipo').addEventListener('change', cargarLogs);
        document.getElementById('num-lineas').addEventListener('change', cargarLogs);

        // Detectar scroll manual para deshabilitar auto-scroll
        document.getElementById('log-container').addEventListener('scroll', function() {
            const container = this;
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
            autoScrollEnabled = isAtBottom;
        });

        // Cargar logs al inicio
        cargarLogs();
        
        // Auto-iniciar refresh
        setTimeout(() => {
            toggleAutoRefresh();
        }, 1000);
    </script>
</body>
</html>


