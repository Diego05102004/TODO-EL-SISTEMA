<?php
// Endpoint para verificar el estado del servidor WebSocket
require_once __DIR__ . '/../Config/WebSocketConfig.php';

use Usuario\ProyectoCasalaiCa\Config\WebSocketConfig;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$websocket_running = false;
try {
    $settings = WebSocketConfig::settings($_SERVER['HTTP_HOST'] ?? null);
    $socket = @fsockopen('127.0.0.1', $settings['port'], $errno, $errstr, 1);
    if ($socket) {
        fclose($socket);
        $websocket_running = true;
    }
} catch (Throwable $e) {
    error_log('No se pudo verificar el servicio WebSocket: ' . $e->getMessage());
}

echo json_encode([
    'websocket_running' => $websocket_running,
    'port' => $settings['port'] ?? null,
    'timestamp' => date('Y-m-d H:i:s')
]);
