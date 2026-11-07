<?php
/**
 * Endpoint API para invalidar el token JWT y destruir la sesión.
 *
 * Este endpoint limpia la cookie JWT, destruye la sesión y devuelve
 * una respuesta JSON para el frontend.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Config/Auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Config\Auth;

// Destruir la sesión actual
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Limpiar cookie JWT
Auth::clearTokenCookie();

echo json_encode([
    'success' => true,
    'message' => 'Sesión invalidada correctamente'
]);
