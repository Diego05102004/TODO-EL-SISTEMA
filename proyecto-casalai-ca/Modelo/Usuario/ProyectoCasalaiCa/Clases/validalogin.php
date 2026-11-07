<?php
class validalogin
{
    function leesesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['rango'])) {
            $s = $_SESSION['rango'];
        } else {
            $s = "";
        }
        return $s;
    }
    function destruyesesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'] ?? false, $params['httponly'] ?? false);
            }
            session_destroy();
        }
        // Redirigir de forma explícita a la vista de catálogo y terminar la ejecución
        header("Location: ?pagina=catalogo");
        exit;
    }
}
