<?php
require_once __DIR__ . '/../Config/WebSocketConfig.php';

use Usuario\ProyectoCasalaiCa\Config\WebSocketConfig;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Autenticación requerida.']);
    exit;
}

try {
    $settings = WebSocketConfig::settings($_SERVER['HTTP_HOST'] ?? null);
    if ($settings['public_url'] === null) {
        throw new RuntimeException('La URL pública del WebSocket no está configurada.');
    }

    echo json_encode([
        'url' => $settings['public_url'],
        'token' => WebSocketConfig::issueToken((int)$_SESSION['id_usuario']),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('No se pudo emitir configuración de WebSocket: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'El servicio de notificaciones no está disponible.']);
}
