<?php
// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también se debe hacer
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Limpiar también el token JWT
require_once __DIR__ . '/../Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;
Auth::clearTokenCookie();

// Redirigir al catálogo usando la ruta raíz
$root = rtrim(dirname($_SERVER['PHP_SELF']), '/');
header("Location: $root/?pagina=catalogo");
exit();