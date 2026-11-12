<?php
/**
 * Endpoint API para extender el token JWT
 * 
 * Este endpoint permite al usuario extender su sesión por un tiempo adicional
 * cuando el token está por expirar.
 */

// Cargar autoload de Composer para Firebase JWT
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Config/Auth.php';

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión para acceder a cookies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Config\Auth;

try {
    // Verificar el token JWT actual
    $payload = Auth::validateToken();
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token inválido o expirado'
        ]);
        exit;
    }

    // Límite de extensiones permitidas
    $maxExtensions = 3;
    $currentExtensions = isset($_SESSION['session_extensions']) ? (int) $_SESSION['session_extensions'] : 0;

    if ($currentExtensions >= $maxExtensions) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Has alcanzado el máximo de extensiones de sesión permitidas.'
        ]);
        exit;
    }
    
    // Extender el token por 30 minutos (1830 segundos)
    $extensionTime = 1830; // 30 minutos
    $newToken = Auth::extendToken($extensionTime);
    
    if ($newToken) {
        // Actualizar contador de extensiones en sesión
        $_SESSION['session_extensions'] = $currentExtensions + 1;

        // Establecer el nuevo token en la cookie
        Auth::setTokenCookie($newToken, $extensionTime);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sesión extendida exitosamente',
            'extended_by' => $extensionTime,
            'expires_in' => $extensionTime,
            'extensions_used' => $_SESSION['session_extensions'],
            'extensions_remaining' => $maxExtensions - $_SESSION['session_extensions']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al extender la sesión'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al extender token: ' . $e->getMessage()
    ]);
}
