<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control WebSocket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        #console {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 Control del Servidor WebSocket</h1>
    
    <div id="status" class="status status-info">
        Verificando estado del servidor...
    </div>
    
    <div>
        <button onclick="checkStatus()" class="btn btn-info">📊 Verificar Estado</button>
        <button onclick="closeWebSocket()" class="btn btn-danger">🔌 Cerrar Servidor</button>
        <button onclick="testConnection()" class="btn btn-success">🔗 Probar Conexión</button>
    </div>
    
    <div id="console"></div>
    
    <script>
        function log(message, type = 'info') {
            const console = document.getElementById('console');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'black';
            console.innerHTML += `<div style="color: ${color}">[${timestamp}] ${message}</div>`;
            console.scrollTop = console.scrollHeight;
        }
        
        function updateStatus(message, type) {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status status-${type}`;
        }
        
        async function checkStatus() {
            log('🔍 Verificando estado del servidor WebSocket...');
            
            try {
                const response = await fetch('?pagina=verificar_websocket_status', {
                    method: 'GET',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.websocket_running) {
                    updateStatus('✅ Servidor WebSocket está corriendo', 'success');
                    log(`✅ Servidor WebSocket está corriendo en el puerto ${data.port}`, 'success');
                } else {
                    updateStatus('❌ Servidor WebSocket no está corriendo', 'danger');
                    log('❌ Servidor WebSocket no está corriendo', 'error');
                }
            } catch (error) {
                updateStatus('⚠️ Error verificando el estado', 'danger');
                log('⚠️ Error verificando el estado: ' + error.message, 'error');
            }
        }
        
        async function closeWebSocket() {
            log('🔌 Cerrando servidor WebSocket...');
            
            try {
                const response = await fetch('cerrar_websocket.php');
                const html = await response.text();
                
                // Extraer mensajes de la consola del HTML
                const consoleMatches = html.match(/<script>console\.log\(['"]([^'"]+)['"]\);<\/script>/g);
                if (consoleMatches) {
                    consoleMatches.forEach(match => {
                        const message = match.match(/console\.log\(['"]([^'"]+)['"]\);/)[1];
                        log(message);
                    });
                }
                
                // Esperar un momento y verificar el estado
                setTimeout(() => {
                    checkStatus();
                }, 2000);
                
            } catch (error) {
                log('❌ Error cerrando el servidor: ' + error.message, 'error');
            }
        }
        
        async function testConnection() {
            log('🔗 Probando conexión al WebSocket...');
            
            try {
                const configResponse = await fetch('?pagina=websocket_token', {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {'Accept': 'application/json'}
                });
                if (!configResponse.ok) {
                    throw new Error(`No se pudo obtener credenciales (HTTP ${configResponse.status})`);
                }
                const config = await configResponse.json();
                const ws = new WebSocket(config.url);
                let autenticado = false;
                
                ws.onopen = function() {
                    ws.send(JSON.stringify({tipo: 'autenticar', token: config.token}));
                };

                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    if (data.tipo === 'autenticado') {
                        autenticado = true;
                        log('✅ Conexión autenticada exitosamente', 'success');
                        updateStatus('✅ Conexión WebSocket autenticada', 'success');
                        ws.close(1000, 'Prueba completada');
                    }
                };
                
                ws.onerror = function() {
                    log('❌ Error de conexión WebSocket', 'error');
                    updateStatus('❌ Error de conexión WebSocket', 'danger');
                };
                
                ws.onclose = function() {
                    if (!autenticado) {
                        log('❌ La conexión se cerró antes de autenticar', 'error');
                        updateStatus('❌ No se pudo autenticar la conexión', 'danger');
                    }
                    log('🔌 Conexión cerrada');
                };
                
                // Timeout después de 5 segundos
                setTimeout(() => {
                    if (!autenticado && ws.readyState !== WebSocket.CLOSED) {
                        ws.close();
                        log('⏰ Tiempo de espera agotado al conectar/autenticar', 'error');
                        updateStatus('⏰ Tiempo de espera agotado', 'danger');
                    }
                }, 5000);
                
            } catch (error) {
                log('❌ Error al crear conexión: ' + error.message, 'error');
                updateStatus('❌ Error de conexión', 'danger');
            }
        }
        
        // Verificar estado al cargar la página
        window.onload = function() {
            checkStatus();
        };
    </script>
</body>
</html>
