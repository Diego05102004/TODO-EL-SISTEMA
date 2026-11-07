<?php
// Endpoint para verificar el estado del servidor WebSocket
header('Content-Type: application/json');

function verificarWebSocketServer() {
    $socket = @fsockopen('localhost', 8080, $errno, $errstr, 1);
    
    if ($socket) {
        fclose($socket);
        return true;
    }
    
    return false;
}

$websocket_running = verificarWebSocketServer();

echo json_encode([
    'websocket_running' => $websocket_running,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
