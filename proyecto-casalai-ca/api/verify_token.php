<?php
/**
 * Endpoint API para verificar el token JWT en tiempo real
 * 
 * Este endpoint es usado por el JavaScript para verificar periódicamente
 * si el token JWT del usuario sigue siendo válido.
 */

// Cargar autoload de Composer para Firebase JWT
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../Modelo/Config/Auth.php';

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión para acceder a cookies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Config\Auth;

try {
    // Verificar el estado del token JWT
    $tokenState = Auth::inspectToken();
    $extensionsUsed = isset($_SESSION['session_extensions']) ? (int) $_SESSION['session_extensions'] : 0;
    $maxExtensions = 3;
    $status = $tokenState['status'] ?? 'invalid';
    $isActive = in_array($status, ['valid', 'warning'], true);
    
    if ($isActive) {
        $payload = $tokenState['payload'];
        $timeUntilExpiry = $tokenState['expires_in'];
        
        echo json_encode([
            'success' => true,
            'valid' => true,
            'state' => $status,
            'expires_in' => $timeUntilExpiry,
            'user_id' => $payload['sub'] ?? null,
            'role' => $payload['role'] ?? null,
            'extensions_used' => $extensionsUsed,
            'extensions_remaining' => max(0, $maxExtensions - $extensionsUsed),
            'message' => $status === 'warning' ? 'Token por expirar' : 'Token válido'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'valid' => false,
            'state' => $status,
            'message' => $status === 'expired' ? 'Token expirado' : 'Token inválido o expirado',
            'redirect' => '?pagina=login',
            'extensions_used' => $extensionsUsed,
            'extensions_remaining' => max(0, $maxExtensions - $extensionsUsed)
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'No se pudo verificar el token.'
    ]);
}
